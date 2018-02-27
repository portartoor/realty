<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage faceid
 * @copyright  2001-2016 Bitrix
 */

namespace Bitrix\Faceid;

use Bitrix\Crm\LeadTable;
use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

\Bitrix\Main\Loader::includeModule('crm');

/**
 * Class TrackingVisitorsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> FACE_ID int mandatory
 * <li> CRM_ID int mandatory
 * <li> VK_ID string(50) mandatory
 * <li> FIRST_VISIT datetime mandatory
 * <li> PRELAST_VISIT datetime mandatory
 * <li> LAST_VISIT datetime mandatory
 * <li> LAST_VISIT_ID int mandatory
 * <li> VISITS_COUNT int mandatory
 * </ul>
 *
 * @package Bitrix\Faceid
 **/

class TrackingVisitorsTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_faceid_tracking_visitors';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			new Main\Entity\IntegerField('FILE_ID'),
			new Main\Entity\IntegerField('FACE_ID'),
			new Main\Entity\IntegerField('CRM_ID'),
			new Main\Entity\StringField('VK_ID'),
			new Main\Entity\DatetimeField('FIRST_VISIT'),
			new Main\Entity\DatetimeField('PRELAST_VISIT'),
			new Main\Entity\DatetimeField('LAST_VISIT'),
			new Main\Entity\IntegerField('LAST_VISIT_ID'),
			new Main\Entity\IntegerField('VISITS_COUNT'),
		);
	}
	/**
	 * Returns validators for VK_ID field.
	 *
	 * @return array
	 */
	public static function validateVkId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}

	public static function toJson($visitor, $confidence = 0, $returnAsArray = false)
	{
		$visitInfo = FormatDate('j F, H:i', $visitor['LAST_VISIT']->getTimestamp()).' | ';

		if ($visitor['VISITS_COUNT'] == 1)
		{
			$visitInfo .= Loc::getMessage('FACEID_VISITORS_NEW');
		}
		else
		{
			$visitInfo .= sprintf(
				Loc::getMessage('FACEID_VISITOR_VISITS'),
				$visitor['VISITS_COUNT'], FormatDate('j F, H:i', $visitor['PRELAST_VISIT']->getTimestamp())
			);
		}

		// crm
		$crmRow = null;
		if (!empty($visitor['CRM_ID']))
		{
			$crmRow = LeadTable::getById($visitor['CRM_ID'])->fetch();
		}

		$jsonResult = array(
			'visitor_id' => $visitor['ID'],
			'visit_info' => $visitInfo,
			'last_visit' => (string) $visitor['LAST_VISIT'],
			'last_visit_ts' => $visitor['LAST_VISIT']->getTimestamp(),
			'prelast_visit' => (string) $visitor['PRELAST_VISIT'],
			'visits_count' => $visitor['VISITS_COUNT'],
			'name' => $visitor['CRM_ID'] ? $crmRow['TITLE'] : Loc::getMessage('FACEID_VISITOR')." ".$visitor['ID'],
			'crm_url' => $visitor['CRM_ID'] ? '/crm/lead/show/'.$visitor['CRM_ID'].'/' : '',
			'vk_id' => $visitor['VK_ID'],
			'shot_src' => \CFile::GetPath($visitor['FILE_ID']),
			'confidence' => $confidence
		);

		return $returnAsArray ? $jsonResult : \Bitrix\Main\Web\Json::encode($jsonResult);
	}
}