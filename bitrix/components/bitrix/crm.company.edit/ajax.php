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
 * 'SAVE_COMPANY'
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


if($action === 'SAVE_COMPANY')
{
	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'SOURCE DATA ARE NOT FOUND!'));
		die();
	}

	$arFields = array(
		'TITLE' => isset($data['title']) ? $data['title'] : '',
		'COMPANY_TYPE' => isset($data['companyType']) ? $data['companyType'] : '',
		'INDUSTRY' => isset($data['industry']) ? $data['industry'] : '',
		'ADDRESS_LEGAL' => isset($data['addressLegal']) ? $data['addressLegal'] : '',
		'FM' => array()
	);

	$email = isset($data['email']) ? $data['email'] : '';
	if($email !== '')
	{
		if(!check_email($email))
		{
			echo CUtil::PhpToJSObject(
				array('ERROR' => GetMessage('CRM_COMPANY_EDIT_INVALID_EMAIL', array('#VALUE#' => $email)))
			);
			die();
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
		$arFields['FM']['PHONE'] = array(
			'n0' => array(
				'VALUE_TYPE' => 'WORK',
				'VALUE' => $phone
			)
		);
	}

	$CrmCompany = new CCrmCompany();
	$ID = $CrmCompany->Add($arFields, true, array('DISABLE_USER_FIELD_CHECK' => true));
	if(is_int($ID) && $ID > 0)
	{
		$data['id'] = $ID;
		$info = CCrmEntitySelectorHelper::PrepareEntityInfo('COMPANY', $ID);
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
			array('ERROR' => $CrmCompany->LAST_ERROR)
		);
	}
}