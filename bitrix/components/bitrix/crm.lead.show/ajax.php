<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}
/*
 * ONLY 'POST' SUPPORTED
 * SUPPORTED MODES:
 * UPDATE - update lead field
 * GET_USER_INFO
 */
global $USER, $APPLICATION;

if (!$USER->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

CUtil::JSPostUnescape();
$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$mode = isset($_POST['MODE']) ? $_POST['MODE'] : '';
if(!isset($mode[0]))
{
	echo CUtil::PhpToJSObject(array('ERROR'=>'MODE IS NOT DEFINED!'));
	die();
}

if($mode === 'GET_USER_INFO')
{
	$result = array();

	$userProfileUrlTemplate = isset($_POST['USER_PROFILE_URL_TEMPLATE']) ? $_POST['USER_PROFILE_URL_TEMPLATE'] : '';
	if(!CCrmInstantEditorHelper::PrepareUserInfo(
		isset($_POST['USER_ID']) ? intval($_POST['USER_ID']) : 0,
		$result,
		array('USER_PROFILE_URL_TEMPLATE' => $userProfileUrlTemplate)))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'COULD NOT PREPARE USER INFO!'));
	}
	else
	{
		echo CUtil::PhpToJSObject(array('USER_INFO' => $result));
	}
	die();
}

$type = isset($_POST['OWNER_TYPE']) ? strtoupper($_POST['OWNER_TYPE']) : '';
if($type !== 'L')
{
	echo CUtil::PhpToJSObject(array('ERROR'=>'OWNER_TYPE IS NOT SUPPORTED!'));
	die();
}

$CCrmLead = new CCrmLead();
if ($CCrmLead->cPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'WRITE'))
{
	echo CUtil::PhpToJSObject(array('ERROR'=>'PERMISSION DENIED!'));
	die();
}

if($mode === 'UPDATE')
{
	$ID = isset($_POST['OWNER_ID']) ? $_POST['OWNER_ID'] : 0;
	if($ID <= 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'ID IS INVALID OR NOT DEFINED!'));
		die();
	}

	$fieldNames = array();
	if(isset($_POST['FIELD_NAME']))
	{
		if(is_array($_POST['FIELD_NAME']))
		{
			$fieldNames = $_POST['FIELD_NAME'];
		}
		else
		{
			$fieldNames[] = $_POST['FIELD_NAME'];
		}
	}

	if(count($fieldNames) == 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'FIELD_NAME IS NOT DEFINED!'));
		die();
	}

	$fieldValues = array();
	if(isset($_POST['FIELD_VALUE']))
	{
		if(is_array($_POST['FIELD_VALUE']))
		{
			$fieldValues = $_POST['FIELD_VALUE'];
		}
		else
		{
			$fieldValues[] = $_POST['FIELD_VALUE'];
		}
	}

	$arFields = CCrmLead::GetByID($ID);
	if(is_array($arFields))
	{
		CCrmInstantEditorHelper::PrepareUpdate(CCrmOwnerType::Lead, $arFields, $fieldNames, $fieldValues);
		if($CCrmLead->Update($ID, $arFields))
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Lead,
				$ID,
				CCrmBizProcEventType::Edit,
				($arErrors = array())
			);

			$result = array();
			$count = count($fieldNames);
			for($i = 0; $i < $count; $i++)
			{
				$fieldName = $fieldNames[$i];
				if(strpos($fieldName, 'FM.') === 0)
				{
					//Filed name like 'FM.PHONE.WORK.1279'
					$fieldParams = explode('.', $fieldName);
					if(count($fieldParams) >= 3)
					{
						$result[$fieldName] = array(
							'VIEW_HTML' =>
								CCrmViewHelper::PrepareMultiFieldHtml(
									$fieldParams[1],
									array(
										'VALUE_TYPE_ID' => $fieldParams[2],
										'VALUE' => isset($fieldValues[$i]) ? $fieldValues[$i] : ''
									)
								)
						);
					}
				}
			}

			if(!empty($result))
			{
				echo CUtil::PhpToJSObject(
					array('DATA' => $result)
				);
			}
		}
	}
}
die();
?>