<?php
namespace Bitrix\Rest;

use Bitrix\Main;


/**
 * Class PlacementTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> APP_ID int optional
 * <li> PLACEMENT string(255) mandatory
 * <li> PLACEMENT_HANDLER string(255) mandatory
 * <li> TITLE string(255) optional
 * <li> COMMENT string(255) optional
 * <li> DATE_CREATE datetime optional
 * </ul>
 *
 * @package Bitrix\Rest
 **/
class PlacementTable extends Main\Entity\DataManager
{
	const PLACEMENT_DEFAULT = 'DEFAULT';

	const ERROR_PLACEMENT_NOT_FOUND = 'ERROR_PLACEMENT_NOT_FOUND';

	protected static $handlersListCache = array();

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_rest_placement';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'APP_ID' => array(
				'data_type' => 'integer',
			),
			'PLACEMENT' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'PLACEMENT_HANDLER' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'TITLE' => array(
				'data_type' => 'string',
			),
			'COMMENT' => array(
				'data_type' => 'string',
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
			),
			'REST_APP' => array(
				'data_type' => 'Bitrix\Rest\AppTable',
				'reference' => array('=this.APP_ID' => 'ref.ID'),
			),
		);
	}

	/**
	 * Returns list of placement handlers. Use \Bitrix\Rest\PlacementTable::getHandlersList.
	 *
	 * @param string $placement Placement ID.
	 *
	 * @return Main\DB\Result
	 */
	public static function getHandlers($placement)
	{
		$dbRes = static::getList(array(
			'filter' => array(
				'=PLACEMENT' => $placement,
				'=REST_APP.ACTIVE' => AppTable::ACTIVE,
				'=REST_APP.INSTALLED' => AppTable::INSTALLED,
			),
			'select' => array(
				'ID', 'TITLE', 'COMMENT', 'APP_ID',
				'APP_NAME' => 'REST_APP.APP_NAME',
			),
		));
		return $dbRes;
	}

	/**
	 * Removes all application placement handlers.
	 *
	 * @param int $appId Application ID.
	 *
	 * @return Main\DB\Result
	 */
	public static function deleteByApp($appId)
	{
		$connection = Main\Application::getConnection();

		return $connection->query("DELETE FROM ".static::getTableName()." WHERE APP_ID='".intval($appId)."'");
	}

	/**
	 * Returns cached list of placement handlers.
	 *
	 * @param string $placement Placement ID
	 *
	 * @return array
	 */
	public static function getHandlersList($placement)
	{
		if(!array_key_exists($placement, static::$handlersListCache))
		{
			static::$handlersListCache[$placement] = array();
			$dbRes = static::getHandlers($placement);
			while($handler = $dbRes->fetch())
			{
				static::$handlersListCache[$placement][] = $handler;
			}
		}

		return static::$handlersListCache[$placement];
	}

	public static function onBeforeUpdate(Main\Entity\Event $event)
	{
		return static::checkUniq($event);
	}

	public static function onBeforeAdd(Main\Entity\Event $event)
	{
		return static::checkUniq($event, true);
	}

	protected static function checkUniq(Main\Entity\Event $event, $add = false)
	{
		$result = new Main\Entity\EventResult();
		$data = $event->getParameter("fields");

		$dbRes = static::getList(array(
			'filter' => array(
				'=APP_ID' => $data['APP_ID'],
				'=PLACEMENT' => $data['PLACEMENT'],
				'=PLACEMENT_HANDLER' => $data['PLACEMENT_HANDLER'],
			),
			'select' => array('ID')
		));

		if($dbRes->fetch())
		{
			$result->addError(new Main\Entity\EntityError(
				"Handler already binded"
			));
		}
		elseif($add)
		{
			$result->modifyFields(array(
				"DATE_CREATE" => new Main\Type\DateTime(),
			));
		}

		return $result;
	}
}