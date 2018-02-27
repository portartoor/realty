<?php
namespace Bitrix\Crm\Integration\Channel;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Mail\MailboxTable;
use Bitrix\Crm\Integration\IntranetManager;

Loc::loadMessages(__FILE__);

class EmailTracker extends ChannelTracker
{
	const GROUP_ID = 'EMAIL';

	/** @var EmailTracker|null  */
	private static $instance = null;
	/** @var bool|null  */
	private $isEnabled = null;
	/** @var array|null  */
	private $isInUse = null;
	/** @var array|null  */
	private $mailboxMap = null;

	public function __construct()
	{
		parent::__construct(ChannelType::EMAIL);
	}
	/**
	 * Get manager instance
	 * @return EmailTracker
	 */
	public static function getInstance()
	{
		if(self::$instance !== null)
		{
			return self::$instance;
		}
		return (self::$instance = new EmailTracker());
	}
	/**
	 * Add instance of this manager to collection
	 * @param array $instances Destination collection.
	 */
	public static function registerInstance(array &$instances)
	{
		$instance = self::getInstance();
		$instances[$instance->getTypeID()] = $instance;
	}

	/**
	 * Get Items
	 * @return array
	 * @throws Main\ArgumentException
	 */
	protected function getMailboxMap()
	{
		if($this->mailboxMap !== null)
		{
			return $this->mailboxMap;
		}

		$this->mailboxMap = array();

		$dbResult = MailboxTable::getList(
			array(
				'filter' => array('LID' => SITE_ID, 'ACTIVE' => 'Y'),
				'select' => array('ID', 'OPTIONS', 'USER_ID')
			)
		);

		while($mailbox = $dbResult->fetch())
		{
			$options = is_array($mailbox) && isset($mailbox['OPTIONS']) && is_array($mailbox['OPTIONS'])
				? $mailbox['OPTIONS'] : array();
			$flags = isset($options['flags']) && is_array($options['flags'])
				? $options['flags'] : array();

			if(in_array('crm_connect', $flags, true))
			{
				$this->mailboxMap[$mailbox['ID']] = isset($mailbox['USER_ID']) ? (int)$mailbox['USER_ID'] : 0;
			}
		}
		return $this->mailboxMap;
	}

	protected function prepareOriginID(array $params)
	{
		$userID = $params['USER_ID'] ? (int)$params['USER_ID'] : 0;
		$mailboxID = $params['MAILBOX_ID'] ? (int)$params['MAILBOX_ID'] : 0;
		return "{$userID}|{$mailboxID}";
	}

	protected function parseOriginID($originID)
	{
		$result = array('USER_ID' => 0, 'MAILBOX_ID' => 0);
		if($originID !== '')
		{
			$parts = explode('|', $originID);
			$count = is_array($parts) ? count($parts) : 0;
			if($count >= 1)
			{
				$result['USER_ID'] = (int)$parts[0];
			}
			if($count >= 2)
			{
				$result['MAILBOX_ID'] = (int)$parts[1];
			}
		}
		return $result;
	}

	//region IChannelTracker
	/**
	 * Check if current manager enabled.
	 * @return bool
	 */
	public function isEnabled()
	{
		if($this->isEnabled === null)
		{
			$this->isEnabled = ModuleManager::isModuleInstalled('mail') && Loader::includeModule('mail');
		}
		return $this->isEnabled;
	}
	/**
	 * Check if email in use.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public function isInUse(array $params = null)
	{
		if(!$this->isEnabled())
		{
			return false;
		}

		if(!is_array($params))
		{
			$params = array();
		}

		$originID = isset($params['ORIGIN_ID']) ? $params['ORIGIN_ID'] : '';
		$originData = $this->parseOriginID($originID);
		return $originData['MAILBOX_ID'] > 0;
	}
	/**
	 * Get service URL.
	 * @param array $params Array of channel parameters.
	 * @return string
	 * @throws Main\ArgumentNullException
	 * @internal param EmailChannelOrigin $origin Channel Origin.
	 */
	public function getUrl(array $params = null)
	{
		if(!$this->isEnabled())
		{
			return '';
		}

		if(!is_array($params))
		{
			$params = array();
		}

		$originID = isset($params['ORIGIN_ID']) ? $params['ORIGIN_ID'] : '';
		$originData = $this->parseOriginID($originID);
		$userID = $originData['USER_ID'];
		if($userID === 0)
		{
			return '/crm/configs/emailtracker/';
		}

		return $userID === \CCrmSecurityHelper::GetCurrentUserID()
			? Option::get('socialnetwork', 'user_page', SITE_DIR.'company/personal/', SITE_ID).'mail/?page=home'
			: '';
	}
	/**
	 * Check if current user has permission to configure email.
	 * @param array $params Array of channel parameters.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public function checkConfigurationPermission(array $params = null)
	{
		if(!$this->isEnabled())
		{
			return false;
		}

		if(!is_array($params))
		{
			$params = array();
		}

		$originID = isset($params['ORIGIN_ID']) ? $params['ORIGIN_ID'] : '';
		$originData = $this->parseOriginID($originID);
		$userID = $originData['USER_ID'];
		if($userID === 0)
		{
			return \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission();
		}

		$currentUserID = \CCrmSecurityHelper::GetCurrentUserID();
		return ($userID === $currentUserID || IntranetManager::isSubordinate($userID, $currentUserID));
	}
	/**
	 * Create channel group info items.
	 * @return IChannelGroupInfo[]
	 */
	public function prepareChannelGroupInfos()
	{
		return array(
			self::GROUP_ID => new ChannelGroupInfo(
				$this,
				self::GROUP_ID,
				'E-mail',
				100,
				false
			)
		);
	}
	/**
	 * Create channel info items.
	 * @return IChannelInfo[]
	 */
	public function prepareChannelInfos()
	{
		if(!$this->isEnabled())
		{
			return array();
		}

		$mailboxMap = $this->getMailboxMap();
		$userMap = array_flip($mailboxMap);

		$sort = 1;
		$results = array();
		if(\CCrmAuthorizationHelper::CheckConfigurationUpdatePermission())
		{
			$results[] = new ChannelInfo(
				$this,
				ChannelType::EMAIL,
				Loc::getMessage('EMAIL_CHANNEL_COMPANY'),
				$this->prepareOriginID(
					array(
						'USER_ID' => 0,
						'MAILBOX_ID' => isset($userMap[0]) ? $userMap[0] : 0
					)
				),
				'',
				$sort,
				self::GROUP_ID
			);
			$sort++;
		}

		$currentUserID = \CCrmSecurityHelper::GetCurrentUserID();
		if($currentUserID > 0)
		{
			$results[] = new ChannelInfo(
				$this,
				ChannelType::EMAIL,
				Loc::getMessage('EMAIL_CHANNEL_PERSONAL'),
				$this->prepareOriginID(
					array(
						'USER_ID' => $currentUserID,
						'MAILBOX_ID' => isset($userMap[$currentUserID]) ? $userMap[$currentUserID] : 0
					)
				),
				'',
				$sort,
				self::GROUP_ID
			);
			//$sort++;
		}

		//Deferred
		/*
		$effectiveUserIDs = array();
		foreach($userMap as $userID => $mailboxID)
		{
			if($userID === $currentUserID)
			{
				continue;
			}

			if(IntranetManager::isSubordinate($userID, $currentUserID))
			{
				$effectiveUserIDs[] = $userID;
			}
		}

		$userNames = $this->prepareUserNames($effectiveUserIDs);
		foreach($effectiveUserIDs as $userID)
		{
			$results[] = new ChannelInfo(
				$this,
				ChannelType::EMAIL,
				Loc::getMessage(
					'EMAIL_CHANNEL_CUSTOM_USER',
					array('#USER_NAME#' => isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]")
				),
				$this->prepareOriginID(array('USER_ID' => $userID, 'MAILBOX_ID' => $userMap[$userID])),
				'',
				$sort,
				self::GROUP_ID
			);
			$sort++;
		}
		*/
		return $results;
	}
	/**
	 * Prepare channel caption
	 * @param array|null $params Array of channel parameters.
	 * @return string
	 */
	public function prepareCaption(array $params = null)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$originID = isset($params['ORIGIN_ID']) ? $params['ORIGIN_ID'] : '';
		$originData = $this->parseOriginID($originID);
		$userID = $originData['USER_ID'];
		if($userID <= 0)
		{
			return Loc::getMessage('EMAIL_CHANNEL_COMPANY');
		}
		if($userID === \CCrmSecurityHelper::GetCurrentUserID())
		{
			return Loc::getMessage('EMAIL_CHANNEL_PERSONAL');
		}

		$userName = $this->prepareUserName($userID);
		return Loc::getMessage(
			'EMAIL_CHANNEL_CUSTOM_USER',
			array('#USER_NAME#' => $userName !== '' ? $userName : "[{$userID}]")
		);
	}
	//endregion
}