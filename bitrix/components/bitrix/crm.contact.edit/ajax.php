<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}
/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'SAVE_CONTACT'
 */
global $DB, $USER, $APPLICATION;

if (!$USER->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));
CUtil::JSPostUnescape();
$GLOBALS['APPLICATION']->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if(strlen($action) == 0)
{
	echo CUtil::PhpToJSObject(
		array('ERROR' => 'INVALID DATA!')
	);
	die();
}


if($action === 'SAVE_CONTACT')
{
	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'SOURCE DATA ARE NOT FOUND!'));
		die();
	}

	$ID = isset($data['id']) ? intval($data['id']) : 0;

	$arFields = array(
		'NAME' => isset($data['name']) ? $data['name'] : '',
		'SECOND_NAME' => isset($data['secondName']) ? $data['secondName'] : '',
		'LAST_NAME' => isset($data['lastName']) ? $data['lastName'] : ''
	);

	$email = isset($data['email']) ? $data['email'] : '';
	if($email !== '')
	{
		if(!isset($arFields['FM']))
		{
			$arFields['FM'] = array();
		}
		$arFields['FM']['EMAIL'] = array(
			'n0' => array(
				'VALUE_TYPE' => 'WORK',
				'VALUE' => $email
			)
		);
	}

	$phone = isset($data['phone']) ? $data['phone'] : '';
	if($phone !== '')
	{
		if(!isset($arFields['FM']))
		{
			$arFields['FM'] = array();
		}
		$arFields['FM']['PHONE'] = array(
			'n0' => array(
				'VALUE_TYPE' => 'WORK',
				'VALUE' => $phone
			)
		);
	}

	$CrmContact = new CCrmContact();
	if($ID > 0)
	{
		if($CrmContact->Update($ID, $arFields, true, array('DISABLE_USER_FIELD_CHECK' => true)))
		{
			$info = CCrmEntitySelectorHelper::PrepareEntityInfo('CONTACT', $ID);
			echo CUtil::PhpToJSObject(
				array(
					'DATA' => $data,
					'INFO' => array(
						'title' => $info['TITLE'],
						'url' => $info['URL']
					)
				)
			);
		}
	}
	else
	{
		$ID = $CrmContact->Add($arFields, true, array('DISABLE_USER_FIELD_CHECK' => true));
		if(is_int($ID) && $ID > 0)
		{
			$data['id'] = $ID;
			$info = CCrmEntitySelectorHelper::PrepareEntityInfo(
				'CONTACT',
				$ID,
				array('NAME_TEMPLATE' => isset($_POST['NAME_TEMPLATE']) ? $_POST['NAME_TEMPLATE'] : '')
			);
			echo CUtil::PhpToJSObject(
				array(
					'DATA' => $data,
					'INFO' => array(
						'title' => $info['TITLE'],
						'url' => $info['URL']
					)
				)
			);
		}
		else
		{
			echo CUtil::PhpToJSObject(
				array('ERROR' => $CrmContact->LAST_ERROR)
			);
		}
	}
}
