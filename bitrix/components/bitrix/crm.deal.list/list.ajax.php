<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	return;
}

global $APPLICATION;

if (isset($_REQUEST['MODE']) && $_REQUEST['MODE'] === 'SEARCH')
{
	if($userPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
	{
		return;
	}

	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();

	// Limit count of items to be found
	$nPageTop = 50;		// 50 items by default
	if (isset($_REQUEST['LIMIT_COUNT']) && ($_REQUEST['LIMIT_COUNT'] >= 0))
	{
		$rawNPageTop = (int) $_REQUEST['LIMIT_COUNT'];
		if ($rawNPageTop === 0)
			$nPageTop = false;		// don't limit
		elseif ($rawNPageTop > 0)
			$nPageTop = $rawNPageTop;
	}

	$search = trim($_REQUEST['VALUE']);
	$multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y'? true: false;
	$arFilter = array();
	if (is_numeric($search))
		$arFilter['ID'] = (int) $search;
	else if (preg_match('/(.*)\[(\d+?)\]/i'.BX_UTF_PCRE_MODIFIER, $search, $arMatches))
	{
		$arFilter['ID'] = (int) $arMatches[2];
		$arFilter['%TITLE'] = trim($arMatches[1]);
		$arFilter['LOGIC'] = 'OR';
	}
	else
		$arFilter['%TITLE'] = $search;

	$arDealStageList = CCrmStatus::GetStatusListEx('DEAL_STAGE');
	$arSelect = array('ID', 'TITLE', 'STAGE_ID');
	$arOrder = array('TITLE' => 'ASC');
	$arData = array();
	$obRes = CCrmDeal::GetList($arOrder, $arFilter, $arSelect, $nPageTop);
	$arFiles = array();
	while ($arRes = $obRes->Fetch())
	{
		$arData[] =
			array(
				'id' => $multi? 'D_'.$arRes['ID']: $arRes['ID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_deal_show'),
					array(
						'deal_id' => $arRes['ID']
					)
				),
				'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
				'desc' => isset($arDealStageList[$arRes['STAGE_ID']])? $arDealStageList[$arRes['STAGE_ID']]: '',
				'type' => 'deal'
			)
		;
	}

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arData);
	die();
}
elseif (isset($_REQUEST['ACTION']) && $_REQUEST['ACTION'] === 'SAVE_PROGRESS')
{
	$ID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;
	$typeName = isset($_REQUEST['TYPE']) ? $_REQUEST['TYPE'] : '';
	$stageID = isset($_REQUEST['VALUE']) ? $_REQUEST['VALUE'] : '';

	$targetTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Deal);
	if($stageID === '' || $ID <= 0  || $typeName !== $targetTypeName)
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Invalid data!')
		);
		die();
	}

	$entityAttrs = $userPerms->GetEntityAttr($targetTypeName, array($ID));
	if (!$userPerms->CheckEnityAccess($targetTypeName, 'WRITE', $entityAttrs[$ID]))
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Access denied!')
		);
		die();
	}

	$arFields = CCrmDeal::GetByID($ID, false);

	if(!is_array($arFields))
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Not found!')
		);
		die();
	}

	$arFields['STAGE_ID'] = $stageID;
	$CCrmDeal = new CCrmDeal(false);
	if($CCrmDeal->Update($ID, $arFields, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
	{
		$arErrors = array();
		CCrmBizProcHelper::AutoStartWorkflows(
			CCrmOwnerType::Deal,
			$ID,
			CCrmBizProcEventType::Edit,
			$arErrors
		);
	}

	$APPLICATION->RestartBuffer();
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject(
		array(
			'TYPE' => $targetTypeName,
			'ID' => $ID,
			'VALUE' => $stageID
		)
	);
	die();
}
?>
