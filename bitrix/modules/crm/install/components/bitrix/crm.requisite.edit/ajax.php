<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmRequisiteEditEndResonse'))
{
	function __CrmRequisiteEditEndResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

if (!CModule::IncludeModule('crm'))
{
	__CrmRequisiteEditEndResonse(array('ERROR' => 'Could not include crm module.'));
}
/*
 * 'FIND_LOCALITIES'
 */

if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid())
{
	__CrmRequisiteEditEndResonse(array('ERROR' => 'Access denied.'));
}
if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
	__CrmRequisiteEditEndResonse(array('ERROR' => 'Request method is not allowed.'));
}

//\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
CUtil::JSPostUnescape();
$GLOBALS['APPLICATION']->RestartBuffer();
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if($action === 'FIND_LOCALITIES')
{
	$localityType = isset($_POST['LOCALITY_TYPE']) ? $_POST['LOCALITY_TYPE'] : 'COUNTRY';
	$needle = isset($_POST['NEEDLE']) ? $_POST['NEEDLE'] : '';
	if($localityType === 'COUNTRY')
	{
		$result = \Bitrix\Crm\EntityAddress::getCountries(array('CAPTION' => $needle));
		__CrmRequisiteEditEndResonse(array('DATA' => array('ITEMS' => $result)));
	}
	else
	{
		__CrmRequisiteEditEndResonse(array('ERROR' => "Locality '{$localityType}' is not supported in current context."));
	}
}
elseif($action === 'RESOLVE_EXTERNAL_CLIENT')
{
	$propertyTypeID = isset($_POST['PROPERTY_TYPE_ID']) ? strtoupper($_POST['PROPERTY_TYPE_ID']) : '';
	$propertyValue = isset($_POST['PROPERTY_VALUE']) ? $_POST['PROPERTY_VALUE'] : '';
	$countryID = isset($_POST['COUNTRY_ID']) ? $_POST['COUNTRY_ID'] : '';

	$result = \Bitrix\Crm\Integration\ClientResolver::resolve(
		$propertyTypeID,
		$propertyValue,
		$countryID
	);
	__CrmRequisiteEditEndResonse(array('DATA' => array('ITEMS' => $result)));
}
elseif($action === 'GET_ENTITY_ADDRESS')
{
	$entityTypeID = isset($_POST['ENTITY_TYPE_ID']) ? (int)$_POST['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
	$entityID = isset($_POST['ENTITY_ID']) ? (int)$_POST['ENTITY_ID'] : 0;
	$typeID = isset($_POST['TYPE_ID']) ? (int)$_POST['TYPE_ID'] : 0;

	$fields = \Bitrix\Crm\EntityAddress::getByOwner($typeID, $entityTypeID, $entityID);
	__CrmRequisiteEditEndResonse(
		array(
			'DATA' => array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'FIELDS' => $fields
			)
		)
	);
}
else
{
	__CrmRequisiteEditEndResonse(array('ERROR' => "Action '{$action}' is not supported in current context."));
}