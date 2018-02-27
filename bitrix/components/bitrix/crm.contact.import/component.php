<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$CrmPerms = new CCrmPerms($USER->GetID());
if ($CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'IMPORT'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $USER_FIELD_MANAGER;

$CCrmFieldMulti = new CCrmFieldMulti();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmContact::$sUFEntityID);
$arResult['HEADERS'] = array(
	array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME')),
	array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_LAST_NAME')),
	array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_SECOND_NAME')),
	array('id' => 'FULL_NAME', 'name' => GetMessage('CRM_COLUMN_FULL_NAME')),
	array('id' => 'PHOTO', 'name' => GetMessage('CRM_COLUMN_PHOTO')),
	array('id' => 'COMPANY_TITLE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TITLE'))
);

$CCrmFieldMulti->ListAddHeaders($arResult['HEADERS']);

$arResult['HEADERS'] = array_merge($arResult['HEADERS'], array(
	array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_POST')),
	array('id' => 'ADDRESS', 'name' => GetMessage('CRM_COLUMN_ADDRESS')),
	array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS')),
	array('id' => 'TYPE_ID', 'name' => GetMessage('CRM_COLUMN_TYPE')),
	array('id' => 'SOURCE_ID', 'name' => GetMessage('CRM_COLUMN_SOURCE')),
	array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_SOURCE_DESCRIPTION')),
	array('id' => 'EXPORT', 'name' => GetMessage('CRM_COLUMN_EXPORT')),
	array('id' => 'OPENED', 'name' => GetMessage('CRM_COLUMN_OPENED'))
));

$CCrmUserType->ListAddHeaders($arResult['HEADERS'], true);

$arRequireFields = Array();
$arRequireFields['FULL_NAME'] = GetMessage('CRM_RF_FULL_NAME');

$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_IMPORT'] = CrmCheckPath('PATH_TO_CONTACT_IMPORT', $arParams['PATH_TO_CONTACT_IMPORT'], $APPLICATION->GetCurPage().'?import');

if(isset($_REQUEST['getSample']) && $_REQUEST['getSample'] == 'csv')
{
	$APPLICATION->RestartBuffer();

	Header("Content-Type: application/force-download");
	Header("Content-Type: application/octet-stream");
	Header("Content-Type: application/download");
	Header("Content-Disposition: attachment;filename=contact.csv");
	Header("Content-Transfer-Encoding: binary");

	// add UTF-8 BOM marker
	if (defined('BX_UTF') && BX_UTF)
		echo chr(239).chr(187).chr(191);

	$typeList = CCrmStatus::GetStatusListEx('CONTACT_TYPE');
	$sourceList = CCrmStatus::GetStatusListEx('SOURCE');

	$arDemo = array(
		'NAME' => GetMessage('CRM_SAMPLE_NAME'),
		'LAST_NAME' => GetMessage('CRM_SAMPLE_LAST_NAME'),
		'TYPE_ID' => $typeList['SUPPLIER'],
		'SOURCE_ID' => $sourceList['TRADE_SHOW'],
		'PHONE_MOBILE' => GetMessage('CRM_SAMPLE_PHONE'),
		'EMAIL_WORK' => GetMessage('CRM_SAMPLE_EMAIL'),
		'EXPORT' => GetMessage('MAIN_YES'),
		'OPENED' => GetMessage('MAIN_YES')
	);

	foreach($arResult['HEADERS'] as $arField):
		echo '"', str_replace('"', '""', $arField['name']),'";';
	endforeach;
	echo "\n";
	foreach($arResult['HEADERS'] as $arField):
		echo isset($arDemo[$arField['id']])? '"'.str_replace('"', '""', $arDemo[$arField['id']]).'";': '"";';
	endforeach;
	echo "\n";
	die();
}
else if (isset($_REQUEST['import']) && isset($_SESSION['CRM_IMPORT_FILE']))
{
	$APPLICATION->RestartBuffer();

	global 	$USER_FIELD_MANAGER;
	$CCrmFieldMulti = new CCrmFieldMulti();
	$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmContact::$sUFEntityID);

	require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/classes/general/csv_data.php');

	$arStatus['TYPE_LIST'] = CCrmStatus::GetStatusListEx('CONTACT_TYPE');
	$arStatus['SOURCE_LIST'] = CCrmStatus::GetStatusListEx('SOURCE');
	$arStatus['EXPORT_LIST'] = $arStatus['OPENED_LIST'] = array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'));

	$csvFile = new CCSVData();
	$csvFile->LoadFile($_SESSION['CRM_IMPORT_FILE']);
	$csvFile->SetFieldsType('R');
	$csvFile->SetPos($_SESSION['CRM_IMPORT_FILE_POS']);
	$csvFile->SetFirstHeader($_SESSION['CRM_IMPORT_FILE_FIRST_HEADER']);
	$csvFile->SetDelimiter($_SESSION['CRM_IMPORT_FILE_SEPORATOR']);

	$arResult = Array();
	$arResult['import'] = 0;
	$arResult['error'] = 0;
	$arResult['error_data'] = array();
	$arRows = Array();
	$CCrmContact = new CCrmContact();

	while($arData = $csvFile->Fetch())
	{
		$arResult['column'] = count($arData);
		$arContact = Array();
		foreach ($arData as $key => $data)
		{
			if (isset($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]) && !empty($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]))
			{
				$currentKey = strtoupper($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]);

				if ($currentKey == 'ID')
					continue;

				$data = trim(htmlspecialcharsback($data));

				if ($currentKey == 'TYPE_ID')
				{
					$arContact[$currentKey] = isset($arStatus['TYPE_LIST'][$data])? $data: array_search($data, $arStatus['TYPE_LIST']);
				}
				else if ($currentKey == 'SOURCE_ID')
				{
					$arContact[$currentKey] = isset($arStatus['SOURCE_LIST'][$data])? $data: array_search($data, $arStatus['SOURCE_LIST']);
				}
				else if ($currentKey  == 'CURRENCY_ID')
				{
					$arContact[$currentKey] = isset($arStatus['CURRENCY_LIST'][$data])? $data: array_search($data, $arStatus['CURRENCY_LIST']);
				}
				else if ($currentKey == 'PHOTO')
				{
					if(CCrmUrlUtil::HasScheme($data) && CCrmUrlUtil::IsSecureUrl($data))
					{
						$data = CFile::MakeFileArray($data);
						if (is_array($data) && strlen(CFile::CheckImageFile($data)) === 0)
						{
							$arContact[$currentKey] = array_merge($data, array('MODULE_ID' => 'crm'));
						}
					}
				}
				else if ($currentKey == 'COMPANY_TITLE')
				{
					$obRes = CCrmCompany::GetList(array(), array('TITLE' => $data), array('ID'));
					if (($arRow = $obRes->Fetch()) !== false)
						$arContact['COMPANY_ID'] = $arRow['ID'];
				}
				else if ($currentKey  == 'EXPORT' || $currentKey  == 'OPENED')
				{
					$arContact[$currentKey] = isset($arStatus[$currentKey.'_LIST'][$data])? $data: array_search($data, $arStatus[$currentKey.'_LIST']);
					if ($arContact[$currentKey] === false)
						unset($arContact[$currentKey]);
				}
				else if ($currentKey  == 'FULL_NAME')
				{
					$data = explode(' ', $data);
					if (count($data) > 1)
					{
						$arContact['NAME'] = isset($arContact['NAME'])? $arContact['NAME'].' '.$data[0]: $data[0];
						$arContact['LAST_NAME'] = isset($arContact['LAST_NAME'])? $arContact['LAST_NAME'].' '.$data[1]: $data[1];
					}
					else
						$arContact['NAME'] = isset($arContact['NAME'])? $arContact['NAME'].' '.$data[0]: $data[0];

					unset($arContact[$currentKey]);
				}
				else
				{
					// Finaly try to internalize user type values
					$arContact[$currentKey] = $CCrmUserType->Internalize($currentKey, $data, ',');
				}
			}
		}

		CCrmFieldMulti::PrepareFields($arContact);
		$arContact['PERMISSION'] = 'IMPORT';
		if (!$CCrmContact->Add($arContact))
		{
			$arResult['error']++;
			$arResult['error_data'][] = Array(
				'message' => $arContact['RESULT_MESSAGE'],
				'data' => $arData
			);
		}
		else if (!empty($arContact))
			$arResult['import']++;

		if ($arResult['import'] == 20)
			break;
	}
	$_SESSION['CRM_IMPORT_FILE_POS'] = $csvFile->GetPos();
	$_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'] = false;

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arResult);
	die();
}

$strError = '';
$arResult['STEP'] = isset($_POST['step'])? intval($_POST['step']): 1;
if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	if (isset($_POST['next']))
	{
		if ($arResult['STEP'] == 1)
		{
			if ($_FILES['IMPORT_FILE']['error'] > 0)
				ShowError(GetMessage('CRM_CSV_NF_ERROR'));
			elseif (($strError = CFile::CheckFile($_FILES['IMPORT_FILE'], 0, 0, 'csv,txt')) == '')
			{
				$arFields = Array(''=>'');
				$arFieldsUpper = Array();
				foreach($arResult['HEADERS'] as $arField):
					//echo '"'.$arField['name'].'";';
					$arFields[$arField['id']] = $arField['name'];
					$arFieldsUpper[$arField['id']] = strtoupper($arField['name']);
					if ($arField['mandatory'] == 'Y')
						$arRequireFields[$arField['id']] = $arField['name'];
				endforeach;

				if (isset($_SESSION['CRM_IMPORT_FILE']))
					unset($_SESSION['CRM_IMPORT_FILE']);

				$sTmpFilePath = CTempFile::GetDirectoryName(12, 'crm');
				CheckDirPath($sTmpFilePath);
				$_SESSION['CRM_IMPORT_FILE_SKIP_EMPTY'] = isset($_POST['IMPORT_FILE_SKIP_EMPTY']) && $_POST['IMPORT_FILE_SKIP_EMPTY'] == 'Y'? true: false;
				$_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'] = isset($_POST['IMPORT_FILE_FIRST_HEADER']) && $_POST['IMPORT_FILE_FIRST_HEADER'] == 'Y'? true: false;
				$_SESSION['CRM_IMPORT_FILE'] = $sTmpFilePath.md5($_FILES['IMPORT_FILE']['tmp_name']).'.tmp';
				$_SESSION['CRM_IMPORT_FILE_POS'] = 0;
				move_uploaded_file($_FILES['IMPORT_FILE']['tmp_name'], $_SESSION['CRM_IMPORT_FILE']);
				@chmod($_SESSION['CRM_IMPORT_FILE'], BX_FILE_PERMISSIONS);

				if ($_POST['IMPORT_FILE_SEPORATOR'] == 'semicolon')
					$_SESSION['CRM_IMPORT_FILE_SEPORATOR'] = ';';
				elseif ($_POST['IMPORT_FILE_SEPORATOR'] == 'comma')
					$_SESSION['CRM_IMPORT_FILE_SEPORATOR'] = ',';
				elseif ($_POST['IMPORT_FILE_SEPORATOR'] == 'tab')
					$_SESSION['CRM_IMPORT_FILE_SEPORATOR'] = "\t";
				elseif ($_POST['IMPORT_FILE_SEPORATOR'] == 'space')
					$_SESSION['CRM_IMPORT_FILE_SEPORATOR'] = ' ';

				require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/classes/general/csv_data.php');

				$csvFile = new CCSVData();
				$csvFile->LoadFile($_SESSION['CRM_IMPORT_FILE']);
				$csvFile->SetFieldsType('R');
				$csvFile->SetFirstHeader(false);
				$csvFile->SetDelimiter($_SESSION['CRM_IMPORT_FILE_SEPORATOR']);

				$iRow = 1;
				$arHeader = Array();
				$arRows = Array();
				while($arData = $csvFile->Fetch())
				{
					if ($iRow == 1)
					{
						foreach($arData as $key => $value):
							if ($_SESSION['CRM_IMPORT_FILE_SKIP_EMPTY'] && empty($value))
								continue;
							if ($_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'])
								$arHeader[$key] = empty($value)? GetMessage('CRM_COLUMN_HEADER').' '.($key+1): trim($value);
							else
								$arHeader[$key] = GetMessage('CRM_COLUMN_HEADER').' '.($key+1);
						endforeach;
						if (!$_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'])
							foreach($arHeader as $key => $value)
								$arRows[$iRow][$key] = $arData[$key];
					}
					else
						foreach($arHeader as $key => $value)
							$arRows[$iRow][$key] = $arData[$key];

					if ($iRow > 5)
						break;

					$iRow++;
				}
				$_SESSION['CRM_IMPORT_FILE_HEADERS'] = $arHeader;

				$arResult['FIELDS']['tab_2'] = array();
				if (count($arRequireFields)>0)
				{
					ob_start();
					?>
					<div class="crm_import_require_fields">
						<?=GetMessage('CRM_REQUIRE_FIELDS')?>: <b><?=implode('</b>, <b>', $arRequireFields)?></b>.
					</div>
					<?
					$sVal = ob_get_contents();
					ob_end_clean();
					$arResult['FIELDS']['tab_2'][] = array(
						'id' => 'IMPORT_REQUIRE_FIELDS',
						'name' => "",
						'colspan' => true,
						'type' => 'custom',
						'value' => $sVal
					);
				}
				foreach ($arHeader as $key => $value)
				{
					$arResult['FIELDS']['tab_2'][] = array(
						'id' => 'IMPORT_FILE_FIELD_'.$key,
						'name' => $value,
						'items' => $arFields,
						'type' => 'list',
						'value' => isset($arFields[strtoupper($value)])? strtoupper($value): array_search(strtoupper($value), $arFieldsUpper),
					);
				}
				$arResult['FIELDS']['tab_2'][] = array(
					'id' => 'IMPORT_ASSOC_EXAMPLE',
					'name' => GetMessage('CRM_SECTION_IMPORT_ASSOC_EXAMPLE'),
					'type' => 'section'
				);
				ob_start();
				?>
				<div id="crm_import_example" class="crm_import_example">
					<table cellspacing="0" cellpadding="0" class="crm_import_example_table">
						<tr>
							<?foreach ($arHeader as $key => $value):?>
								<th><?=htmlspecialcharsbx($value)?></th>
							<?endforeach;?>
						</tr>
						<?foreach ($arRows as $arRow):?>
							<tr>
							<?foreach ($arRow as $row):?>
								<td><?=htmlspecialcharsbx($row)?></td>
							<?endforeach;?>
							</tr>
						<?endforeach;?>
					</table>
				</div>
				<script type="text/javascript">
					windowSizes = BX.GetWindowSize(document);
					if (windowSizes.innerWidth > 1024)
						BX('crm_import_example').style.width = '870px';
					if (windowSizes.innerWidth > 1280)
						BX('crm_import_example').style.width = '1065px';
				</script>
				<?
				$sVal = ob_get_contents();
				ob_end_clean();
				$arResult['FIELDS']['tab_2'][] = array(
					'id' => 'IMPORT_ASSOC_EXAMPLE_TABLE',
					'name' => "",
					'colspan' => true,
					'type' => 'custom',
					'value' => $sVal
				);
				if (count($arHeader) == 1)
					ShowError(GetMessage('CRM_CSV_SEPORATOR_ERROR'));
				else
					$arResult['STEP'] = 2;
			}
			else
				ShowError($strError);

		}
		else if ($arResult['STEP'] == 2)
		{
			$arResult['FIELDS']['tab_3'] = array();

			$arConfig = Array();
			foreach ($_POST as $key => $value)
				if(strpos($key, 'IMPORT_FILE_FIELD_') !== false)
					$_SESSION['CRM_'.$key] = $value;

			ob_start();
			?>
				<div class="crm_import_entity"><?=GetMessage('CRM_IMPORT_FINISH')?>: <span id="crm_import_entity">0</span> <span id="crm_import_entity_progress"><img src="/bitrix/components/bitrix/crm.contact.import/templates/.default/images/wait.gif" align="absmiddle"></span></div>
				<div id="crm_import_error" class="crm_import_error"><?=GetMessage('CRM_IMPORT_ERROR')?>: <span id="crm_import_entity_error">0</span></div>
				<div id="crm_import_example" class="crm_import_example" style="display:none">
					<table cellspacing="0" cellpadding="0" class="crm_import_example_table" id="crm_import_example_table">
						<tbody id="crm_import_example_table_body">
						<tr>
							<?foreach ($_SESSION['CRM_IMPORT_FILE_HEADERS'] as $key => $value):?>
								<th><?=htmlspecialcharsbx($value)?></th>
							<?endforeach;?>
						</tr>
						</tbody>
					</table>
				</div>
				<script type="text/javascript">
					windowSizes = BX.GetWindowSize(document);
					BX('crm_import_example').style.height = "44px";
					if (windowSizes.innerWidth > 1024)
						BX('crm_import_example').style.width = '870px';
					if (windowSizes.innerWidth > 1280)
						BX('crm_import_example').style.width = '1065px';
					crmImportAjax('<?=$APPLICATION->GetCurPage()?>?import');
				</script>
			<?
			$sVal = ob_get_contents();
			ob_end_clean();
			$arResult['FIELDS']['tab_3'][] = array(
				'id' => 'IMPORT_FINISH',
				'name' => "",
				'colspan' => true,
				'type' => 'custom',
				'value' => $sVal
			);
			$arResult['STEP'] = 3;
		}
		else if ($arResult['STEP'] == 3)
		{
			@unlink($_SESSION['CRM_IMPORT_FILE']);
			foreach ($_SESSION as $key => $value)
				if(strpos($key, 'CRM_IMPORT_FILE') !== false)
					unset($_SESSION[$key]);

			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST'], array()));
		}
		else
			$arResult['STEP'] = 1;
	}
	else if (isset($_POST['previous']))
	{
		@unlink($_SESSION['CRM_IMPORT_FILE']);
		foreach ($_SESSION as $key => $value)
			if(strpos($key, 'CRM_IMPORT_FILE') !== false)
				unset($_SESSION[$key]);

		$arResult['STEP'] = 1;
	}
	else if (isset($_POST['cancel']))
	{
		@unlink($_SESSION['CRM_IMPORT_FILE']);
		foreach ($_SESSION as $key => $value)
			if(strpos($key, 'CRM_IMPORT_FILE') !== false)
				unset($_SESSION[$key]);

		LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST'], array()));
	}
}

$arResult['FORM_ID'] = 'CRM_CONTACT_IMPORT';

$arResult['FIELDS']['tab_1'] = array();
if (defined('BX_UTF') && BX_UTF)
{
	ob_start();
	?>
	<div class="crm_import_require_fields">
		<?=GetMessage('CRM_REQUIRE_UTF8')?>
	</div>
	<?
	$sVal = ob_get_contents();
	ob_end_clean();
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'CRM_REQUIRE_UTF8',
		'name' => "",
		'colspan' => true,
		'type' => 'custom',
		'value' => $sVal
	);
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE'),
	'params' => array(),
	'type' => 'file',
	'required' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_EXAMPLE',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_EXAMPLE'),
	'params' => array(),
	'type' => 'label',
	'value' => '<a href="?getSample=csv">'.GetMessage('CRM_DOWNLOAD').'</a>'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_FORMAT',
	'name' => GetMessage('CRM_SECTION_IMPORT_FILE_FORMAT'),
	'type' => 'section'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_SEPORATOR',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR'),
	'items' => Array(
		'semicolon' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR_SEMICOLON'),
		'comma' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR_COMMA'),
		'tab' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR_TAB'),
		'space' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR_SPACE'),
	),
	'type' => 'list',
	'value' => isset($_POST['IMPORT_FILE_SEPORATOR'])? $_POST['IMPORT_FILE_SEPORATOR']: 'semicolon'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_FIRST_HEADER',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_FIRST_HEADER'),
	'type' => 'checkbox',
	'value' => isset($_POST['IMPORT_FILE_FIRST_HEADER']) && $_POST['IMPORT_FILE_FIRST_HEADER'] == 'N'? 'N': 'Y'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_SKIP_EMPTY',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_SKIP_EMPTY'),
	'type' => 'checkbox',
	'value' => isset($_POST['IMPORT_FILE_SKIP_EMPTY']) && $_POST['IMPORT_FILE_SKIP_EMPTY'] == 'N'? 'N': 'Y'
);

for ($i = 1; $i <= 3; $i++):
	if ($arResult['STEP'] != $i)
		$arResult['FIELDS']['tab_'.$i] = array();
endfor;

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.contact/include/nav.php');

?>