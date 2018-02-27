<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage blog
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Blog;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Data\Cache;

Loc::loadMessages(__FILE__);

class BlogUser
{
	const CACHE_ID = 'blog_users';
	const CACHE_DIR = '/blog/';

//	this values was be hardcoded in components. If we want customization - need add settings and remake
	private static $avatarSizes = array(
		'COMMENT' => array('WIDTH' => 30, 'HEIGHT' => 30),
		'POST' => array('WIDTH' => 100, 'HEIGHT' => 100),
	);
	
	private $cacheTime = 0;
	
	
	public function __construct($cacheTime = false)
	{
		if ($cacheTime > 0)
			$this->cacheTime = intval($cacheTime);
	}
	
	public function setCacheTime($cacheTime)
	{
		if ($cacheTime > 0)
			$this->cacheTime = $cacheTime;
	}
	
	
	public function getUsers($ids = array())
	{
		if ($this->cacheTime > 0)
		{
			$result = $this->getUsersFromCache();

//			get only currents ids
			if (!empty($ids))
			{
				$newResult = array();
				foreach ($ids as $id)
					$newResult[$id] = $result[$id];
				$result = $newResult;
				unset($newResult);
			}
		}
		
		else
		{
			$result = $this->getUsersFromDB($ids);
		}
		
		return $result;
	}
	
	
	/**
	 * Get blog users data from cache. If them not exist on cache - get new data from DB and write on cache.
	 * False if error.
	 *
	 * @return array|bool
	 */
	private function getUsersFromCache()
	{
		$cache = Cache::createInstance();
		if ($cache->initCache($this->cacheTime, self::CACHE_ID, self::createCacheDir()))
		{
			$result = $cache->getVars();
		}
		elseif ($cache->startDataCache())
		{
			$result = self::getUsersFromDB();
			$cache->endDataCache($result);
		}
		else
		{
			$result = false;
		}
		
		return $result;
	}
	
	
	/**
	 * Delete all data from blog users cache
	 *
	 * @return mixed
	 */
	public static function cleanCache()
	{
		$cache = Cache::createInstance();
		
		return $cache->cleanDir(self::CACHE_ID, self::createCacheDir());
	}
	
	
	private static function createCacheDir()
	{
		return '/' . SITE_ID . self::CACHE_DIR;
	}
	
	
	/**
	 * Catch data from CUser, CBlogUser and formatted them to array to save in cache
	 */
	private function getUsersFromDB($ids = array())
	{
		$result = array();

//		BLOG users
		$filter = array();
		if (!empty($ids))
			$filter["=ID"] = $ids;
		$resBlogUsers = Internals\BlogUserTable::getList(array(
			'select' => array('ID', 'USER_ID', 'ALIAS', 'AVATAR', 'USER.PERSONAL_PHOTO', 'USER.LOGIN', 'USER.NAME', 'USER.LAST_NAME'),
			'filter' => $filter,
		));
		
		while ($row = $resBlogUsers->fetch())
		{
			$row["BlogUser"] = array(
				"ALIAS" => $row["ALIAS"],
				"~ALIAS" => htmlspecialcharsex($row["ALIAS"]),
			);

//			Avatars for post and for comments
			$row["BlogUser"]["AVATAR_file"] = intval($row["AVATAR"]) > 0 ?
				\CFile::GetFileArray($row["AVATAR"]) :
				\CFile::GetFileArray($row["BLOG_INTERNALS_BLOG_USER_USER_PERSONAL_PHOTO"]);
			if ($row["BlogUser"]["AVATAR_file"] !== false)
			{
				foreach (self::$avatarSizes as $key => $avatarSize)
				{
					$row["BlogUser"]["Avatar_resized"][$avatarSize['WIDTH'] . '_' . $avatarSize['HEIGHT']] = \CFile::ResizeImageGet(
						$row["BlogUser"]["AVATAR_file"],
						array("width" => $avatarSize['WIDTH'], "height" => $avatarSize['HEIGHT']),
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					$row["BlogUser"]["AVATAR_img"][$avatarSize['WIDTH'] . '_' . $avatarSize['HEIGHT']] = \CFile::ShowImage(
						$row["BlogUser"]["Avatar_resized"][$avatarSize['WIDTH'] . '_' . $avatarSize['HEIGHT']]["src"],
						$avatarSize['WIDTH'],
						$avatarSize['HEIGHT'],
						"border=0 align='right'"
					);
				}
			}

//			create correct name from alias, login and names
			$row["AUTHOR_NAME"] = self::GetUserName(
				$row["ALIAS"],
				$row["BLOG_INTERNALS_BLOG_USER_USER_NAME"],
				$row["BLOG_INTERNALS_BLOG_USER_USER_LAST_NAME"],
				$row["BLOG_INTERNALS_BLOG_USER_USER_LOGIN"]
			);
			$row["~AUTHOR_NAME"] = htmlspecialcharsex($row["AUTHOR_NAME"]);

//			array for User data
			$row["arUser"] = array(
				"ID" => $row["USER_ID"],
				"NAME" => $row["BLOG_INTERNALS_BLOG_USER_USER_NAME"],
				"LAST_NAME" => $row["BLOG_INTERNALS_BLOG_USER_USER_LAST_NAME"],
				"LOGIN" => $row["BLOG_INTERNALS_BLOG_USER_USER_LOGIN"],
			);
			$row["arUser"] = \CBlogTools::htmlspecialcharsExArray($row["arUser"]);
			
			$result[$row['ID']] = $row;
		}
		
		return $result;
	}
	
	
	/**
	 * Return users ids of post author and comments authors (for this post)
	 *
	 * @param $postId
	 */
	public static function getCommentAuthorsIdsByPostId($postId)
	{
		if (!$postId)
			throw new ArgumentNullException('post ID');
		$postId = intval($postId);
		$result = array();
		
		$resComment = \CBlogComment::GetList(array(), array("POST_ID" => $postId), false, false, array("AUTHOR_ID"));
		while ($comment = $resComment->Fetch())
			$result[$comment["AUTHOR_ID"]] = $comment["AUTHOR_ID"];
		
		return $result;
	}
	
	/**
	 * Return users ids of blog posts
	 * @param $blogId
	 */
	public static function getPostAuthorsIdsByBlogId($blogId)
	{
		if (!$blogId)
			throw new ArgumentNullException('blog ID');
		$blogId = intval($blogId);
		$result = array();
		
		$resPost = \CBlogPost::GetList(array(), array("BLOG_ID" => $blogId), false, false, array("AUTHOR_ID"));
		while ($post = $resPost->Fetch())
			$result[$post["AUTHOR_ID"]] = $post["AUTHOR_ID"];
		
		return $result;
	}
	
	
	/**
	 * Creat correctly blog user name from name, alias and login
	 *
	 * @param $alias
	 * @param $name
	 * @param $lastName
	 * @param $login
	 * @param string $secondName
	 * @return string
	 *
	 */
	public static function GetUserName($alias, $name, $lastName, $login, $secondName = "")
	{
		$result = "";
		
		$canUseAlias = \COption::GetOptionString("blog", "allow_alias", "Y");
		if ($canUseAlias == "Y")
			$result = $alias;
		
		if (strlen($result) <= 0)
		{
			$result = \CUser::FormatName(
				\CSite::GetNameFormat(false),
				array("NAME" => $name,
					"LAST_NAME" => $lastName,
					"SECOND_NAME" => $secondName,
					"LOGIN" => $login),
				true,
				false
			);
		}
		
		return $result;
	}
	
	public static function GetUserNameEx($user, $blogUser, $params)
	{
		$result = "";
		if (!$params["bSoNet"])
		{
			$canUseAlias = \COption::GetOptionString("blog", "allow_alias", "Y");
			if ($canUseAlias == "Y")
				$result = $blogUser["ALIAS"];
		}
		
		if (strlen($result) <= 0)
		{
			$params["NAME_TEMPLATE"] = $params["NAME_TEMPLATE"] ? $params["NAME_TEMPLATE"] : \CSite::GetNameFormat();
			$params["NAME_TEMPLATE"] = str_replace(
				array("#NOBR#", "#/NOBR#"),
				array("", ""),
				$params["NAME_TEMPLATE"]
			);
			$isUseLogin = $params["SHOW_LOGIN"] != "N" ? true : false;
			
			$result = \CUser::FormatName(
				$params["NAME_TEMPLATE"],
				$user,
				$isUseLogin,
				false
			);
		}
		
		return $result;
	}
}
