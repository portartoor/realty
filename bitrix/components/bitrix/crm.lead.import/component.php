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
if ($CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'IMPORT'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $USER_FIELD_MANAGER;

$CCrmFieldMulti = new CCrmFieldMulti();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);
$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => 'ID'),
	array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE')),
	array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME')),
	array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_LAST_NAME')),
	array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_SECOND_NAME')),
	array('id' => 'FULL_NAME', 'name' => GetMessage('CRM_COLUMN_FULL_NAME')),
);

$CCrmFieldMulti->ListAddHeaders($arResult['HEADERS']);

$arResult['HEADERS'] = array_merge($arResult['HEADERS'], array(
	array('id' => 'COMPANY_TITLE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TITLE')),
	array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_POST')),
	array('id' => 'ADDRESS', 'name' => GetMessage('CRM_COLUMN_ADDRESS')),
	array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS')),
	array('id' => 'STATUS_ID', 'name' => GetMessage('CRM_COLUMN_STATUS')),
	array('id' => 'STATUS_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_STATUS_DESCRIPTION')),
	array('id' => 'PRODUCT_ID',  'name' => GetMessage('CRM_COLUMN_PRODUCT_ID')),
	array('id' => 'PRODUCT_PRICE', 'name' => GetMessage('CRM_COLUMN_PRODUCT_PRICE')),
	array('id' => 'PRODUCT_QUANTITY', 'name' => GetMessage('CRM_COLUMN_PRODUCT_QUANTITY')),
	array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_OPPORTUNITY')),
	array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID')),
	array('id' => 'SOURCE_ID', 'name' => GetMessage('CRM_COLUMN_SOURCE')),
	array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_SOURCE_DESCRIPTION')),
	array('id' => 'OPENED', 'name' => GetMessage('CRM_COLUMN_OPENED'))
));

$CCrmUserType->ListAddHeaders($arResult['HEADERS'], true);

$arRequireFields = Array();
$arRequireFields['TITLE'] = GetMessage('CRM_COLUMN_TITLE');

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath('PATH_TO_LEAD_LIST', $arParams['PATH_TO_LEAD_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_LEAD_IMPORT'] = CrmCheckPath('PATH_TO_LEAD_IMPORT', $arParams['PATH_TO_LEAD_IMPORT'], $APPLICATION->GetCurPage().'?import');

//Download sample
$filename = $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead.import/sample.csv';
if(isset($_REQUEST['getSample']) && $_REQUEST['getSample'] == 'csv')
{
	$APPLICATION->RestartBuffer();

	Header("Content-Type: application/force-download");
	Header("Content-Type: application/octet-stream");
	Header("Content-Type: application/download");
	Header("Content-Disposition: attachment;filename=lead.csv");
	Header("Content-Transfer-Encoding: binary");

	// add UTF-8 BOM marker
	if (defined('BX_UTF') && BX_UTF)
		echo chr(239).chr(187).chr(191);

	$statusList = CCrmStatus::GetStatusListEx('STATUS');
	$sourceList = CCrmStatus::GetStatusListEx('SOURCE');

	$arDemo = array(
		'TITLE' => GetMessage('CRM_SAMPLE_TITLE'),
		'NAME' => GetMessage('CRM_SAMPLE_NAME'),
		'LAST_NAME' => GetMessage('CRM_SAMPLE_LAST_NAME'),
		'POST' => GetMessage('CRM_SAMPLE_POST'),
		'STATUS_ID' => $statusList['NEW'],
		'SOURCE_ID' => $sourceList['SELF'],
		'OPENED' => GetMessage('MAIN_YES'),
		'EMAIL_HOME' => GetMessage('CRM_SAMPLE_EMAIL')
	);

	$arProduct =  CCrmProduct::GetByOriginID('CRM_DEMO_PRODUCT_BX_CMS');
	if($arProduct)
	{
		$arDemo['PRODUCT_ID'] = $arProduct['~NAME'];
		$arDemo['PRODUCT_QUANTITY'] = '1';
		$arDemo['PRODUCT_PRICE'] = $arDemo['OPPORTUNITY'] = $arProduct['~PRICE'];
		$arDemo['CURRENCY_ID'] = $arProduct['~CURRENCY_ID'];
	}
	else
	{
		$arDemo['OPPORTUNITY'] = GetMessage('CRM_SAMPLE_OPPORTUNITY');
		$arDemo['CURRENCY_ID'] = GetMessage('CRM_SAMPLE_CURRENCY_ID');
	}

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
	$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);

	require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/classes/general/csv_data.php');

	$arStatus['STATUS_LIST'] = CCrmStatus::GetStatusListEx('STATUS');
	$arStatus['SOURCE_LIST'] = CCrmStatus::GetStatusListEx('SOURCE');
	$arStatus['OPENED_LIST'] = array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'));

	$csvFile = new CCSVData();
	$csvFile->LoadFile($_SESSION['CRM_IMPORT_FILE']);
	$csvFile->SetFieldsType('R');
	$csvFile->SetPos($_SESSION['CRM_IMPORT_FILE_POS']);
	$csvFile->SetFirstHeader($_SESSION['CRM_IMPORT_FILE_FIRST_HEADER']);
	$csvFile->SetDelimiter($_SESSION['CRM_IMPORT_FILE_SEPORATOR']);

	$arResult = array(
		'import' => 0,
		'error' => 0,
		'error_data' => array()
	);

	$CCrmLead = new CCrmLead();
	$arLeads = array();

	$filePos = 0;
	while($arData = $csvFile->Fetch())
	{
		$arResult['column'] = count($arData);
		$leadlID = '';

		$arLead = array(
			'__CSV_DATA__' => array($arData)
		);

		$arProductRow = array();
		foreach ($arData as $key => $data)
		{
			if (isset($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]) && !empty($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]))
			{
				$currentKey = strtoupper($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]);
				$data = trim(htmlspecialcharsback($data));

				if ($currentKey === 'ID')
				{
					$leadlID = $data;
					continue;
				}

				if (empty($data))
				{
					continue;
				}

				if ($currentKey == 'STATUS_ID')
				{
					if(isset($arStatus['STATUS_LIST'][$data]))
					{
						// 1. Try to interpret value as ID
						$arLead[$currentKey] = $data;
					}
					else
					{
						// 2. Try to interpret value as TITLE. If not found leave value as is
						$result = array_search($data, $arStatus['STATUS_LIST']);
						$arLead[$currentKey] = $result !== false ? $result : $data;
					}
				}
				else if ($currentKey == 'SOURCE_ID')
				{
					if(isset($arStatus['SOURCE_LIST'][$data]))
					{
						// 1. Try to interpret value as ID
						$arLead[$currentKey] = $data;
					}
					else
					{
						$result = array_search($data, $arStatus['SOURCE_LIST']);
						$arLead[$currentKey] = $result !== false ? $result : $data;
					}
				}
				else if ($currentKey  == 'CURRENCY_ID')
				{
					$currency = CCrmCurrency::GetByName($data);
					if(!$currency)
					{
						$currency = CCrmCurrency::GetByID($data);
					}

					$arLead[$currentKey] = $currency ? $currency['CURRENCY'] : CCrmCurrency::GetBaseCurrencyID();
				}
				else if ($currentKey  == 'PRODUCT_ID')
				{
					// For compatibility
					$arProduct = CCrmProduct::GetByOriginID('CRM_PROD_'.$data);
					if(is_array($arProduct))
					{
						$arProductRow = array(
							'PRODUCT_ID' => $arProduct['ID'],
							'QUANTITY' => 1
						);
						// PRICE equals to OPPORTUNITY. We will set PRICE latter
					}
					else
					{
						$arProduct = CCrmProduct::GetByName($data);
						if($arProduct)
						{
							$arProductRow['PRODUCT_ID'] = $arProduct['ID'];
						}
						else
						{
							if(CCrmProduct::Add(array('NAME' => $data)))
							{
								$arProductRow['PRODUCT_ID'] = $arProduct['ID'];
							}
							else
							{
								$arResult['error']++;
								$arResult['error_data'][] = array(
									'message' => CCrmProduct::GetLastError(),
									'data' => $arData
								);
							}
						}
					}
				}
				elseif($currentKey == 'PRODUCT_PRICE')
				{
					// Process price only if product has been resolved
					if(isset($arProductRow['PRODUCT_ID']))
					{
						$arProductRow['PRICE'] = doubleval($data);
					}
				}
				elseif($currentKey == 'PRODUCT_QUANTITY')
				{
					// Process quntity only if product has been resolved
					if(isset($arProductRow['PRODUCT_ID']))
					{
						$arProductRow['QUANTITY'] = intval($data);
					}
				}
				else if ($currentKey  == 'OPENED')
				{
					$arLead[$currentKey] = isset($arStatus[$currentKey.'_LIST'][$data])? $data: array_search($data, $arStatus[$currentKey.'_LIST']);
					if ($arLead[$currentKey] === false)
						unset($arLead[$currentKey]);
				}
				else if ($currentKey  == 'FULL_NAME')
				{
					$data = explode(' ', $data);
					if (count($data) > 1)
					{
						$arLead['NAME'] = isset($arLead['NAME'])? $arLead['NAME'].' '.$data[0]: $data[0];
						$arLead['LAST_NAME'] = isset($arLead['LAST_NAME'])? $arLead['LAST_NAME'].' '.$data[1]: $data[1];
					}
					else
						$arLead['NAME'] = isset($arLead['NAME'])? $arLead['NAME'].' '.$data[0]: $data[0];

					unset($arLead[$currentKey]);
				}
				else
				{
					// Finaly try to internalize user type values
					$arLead[$currentKey] = $CCrmUserType->Internalize($currentKey, $data, ',');
				}
			}
		}

		if (!isset($arLead['TITLE']))
		{
			$arLead['TITLE'] = trim((isset($arLead['NAME'])? $arLead['NAME']: '').' '.(isset($arLead['LAST_NAME'])? $arLead['LAST_NAME']: ''));
		}

		if(isset($arProductRow['PRODUCT_ID']))
		{
			if(!isset($arLead['PRODUCT_ROWS']))
			{
				$arLead['PRODUCT_ROWS'] = array();
			}

			$arLead['PRODUCT_ROWS'][] = $arProductRow;
		}

		$canBreak = true; // We cant break while read multiproduct lead

		if($leadlID !== '')
		{
			if(isset($arLeads[$leadlID]))
			{
				$canBreak = false;

				// Merging of source data
				$arPrevLead = $arLeads[$leadlID];
				$arLead['__CSV_DATA__'] = array_merge($arLead['__CSV_DATA__'], $arPrevLead['__CSV_DATA__']);

				// Try to merge product rows
				if(isset($arPrevLead['PRODUCT_ROWS']))
				{
					if(isset($arLead['PRODUCT_ROWS']))
					{
						$arLead['PRODUCT_ROWS'] = array_merge($arLead['PRODUCT_ROWS'], $arPrevLead['PRODUCT_ROWS']);
					}
					else
					{
						$arLead['PRODUCT_ROWS'] = $arPrevLead['PRODUCT_ROWS'];
					}
				}
				unset($arLeads[$leadlID]);
			}
		}
		else
		{
			$leadlID = uniqid();
		}

		// For compatibility only. Try sync product PRICE
		if(isset($arLead['PRODUCT_ROWS'])
			&& count($arLead['PRODUCT_ROWS']) == 1
			&& !isset($arLead['PRODUCT_ROWS'][0]['PRICE'])
			&& isset($arLead['OPPORTUNITY']))
		{
			$arLead['PRODUCT_ROWS'][0]['PRICE'] = doubleval($arLead['OPPORTUNITY']);
		}

		if($canBreak && count($arLeads) >= 20)
		{
			break;
		}

		$arLeads[$leadlID] = $arLead;
		$filePos = $csvFile->GetPos();
	}

	foreach($arLeads as $arLead)
	{
		CCrmFieldMulti::PrepareFields($arLead);
		$arLead['PERMISSION'] = 'IMPORT';

		if (!$CCrmLead->Add($arLead))
		{
			$arResult['error']++;
			$arResult['error_data'][] = Array(
				'message' => $arLead['RESULT_MESSAGE'],
				'data' => $arLead['__CSV_DATA__']
			);
		}
		else if (!empty($arLead))
		{
			if(isset($arLead['PRODUCT_ROWS']) && count($arLead['PRODUCT_ROWS']) > 0)
			{
				if(!CCrmLead::SaveProductRows($arLead['ID'], $arLead['PRODUCT_ROWS']))
				{
					$arResult['error']++;
					$arResult['error_data'][] = array(
						'message' => CCrmProductRow::GetLastError(), // HACK: Get error from nested class
						'data' => $arLead['__CSV_DATA__']
					);
				}
			}
			$arResult['import']++;
		}
	}

	$_SESSION['CRM_IMPORT_FILE_POS'] = $filePos;
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

			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST'], array()));
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

		LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST'], array()));
	}
}

$arResult['FORM_ID'] = 'CRM_LEAD_IMPORT';

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

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead/include/nav.php');

?>