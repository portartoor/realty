<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
?>
<script type="text/javascript">
function crm_product_delete_grid(title, message, btnTitle, path)
{
	var d =
		new BX.CDialog(
		{
			title: title,
			head: '',
			content: message,
			resizable: false,
			draggable: true,
			height: 70,
			width: 300
		}
	);

	var _BTN = [
		{
			title: btnTitle,
			id: 'crmOk',
			'action': function ()
			{
				window.location.href = path;
				BX.WindowManager.Get().Close();
			}
		},
		BX.CDialog.btnCancel
	];
	d.ClearButtons();
	d.SetButtons(_BTN);
	d.Show();
}
</script>
<?php
	$arResult['GRID_DATA'] = $arColumns = array();
	foreach ($arResult['HEADERS'] as $arHead)
	{
		$arColumns[$arHead['id']] = false;
	}
	foreach($arResult['PRODUCTS'] as $sKey =>  $arProduct)
	{
		$arActions = array();
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_PRODUCT_SHOW_TITLE'),
			'TEXT' => GetMessage('CRM_PRODUCT_SHOW'),
			'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($arProduct['PATH_TO_PRODUCT_SHOW']).'\');',
			'DEFAULT' => true
		);

		if ($arProduct['EDIT'])
		{
			$arActions[] =  array(
				'ICONCLASS' => 'edit',
				'TITLE' => GetMessage('CRM_PRODUCT_EDIT_TITLE'),
				'TEXT' => GetMessage('CRM_PRODUCT_EDIT'),
				'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($arProduct['PATH_TO_PRODUCT_EDIT']).'\');'
			);
		}

		if ($arProduct['DELETE'] && !$arResult['INTERNAL'])
		{
			$arActions[] = array('SEPARATOR' => true);
			$arActions[] =  array(
				'ICONCLASS' => 'delete',
				'TITLE' => GetMessage('CRM_PRODUCT_DELETE_TITLE'),
				'TEXT' => GetMessage('CRM_PRODUCT_DELETE'),
				'ONCLICK' => 'crm_product_delete_grid(\''.CUtil::JSEscape(GetMessage('CRM_PRODUCT_DELETE_TITLE')).'\', \''.CUtil::JSEscape(sprintf(GetMessage('CRM_PRODUCT_DELETE_CONFIRM'), htmlspecialcharsbx($arProduct['NAME']))).'\', \''.CUtil::JSEscape(GetMessage('CRM_PRODUCT_DELETE')).'\', \''.CUtil::JSEscape($arProduct['PATH_TO_PRODUCT_DELETE']).'\')'
			);
		}

		$sectionLink = '';
		if(isset($arProduct['SECTION_ID'])
			&&  array_key_exists($arProduct['SECTION_ID'], $arResult['SECTIONS']))
		{
			$sectionData = $arResult['SECTIONS'][$arProduct['SECTION_ID']];
			$sectionLink = '<a href="'.htmlspecialcharsbx($sectionData['LIST_URL']).'">'.htmlspecialcharsbx($sectionData['NAME']).'</a>';
		}

		$arResult['GRID_DATA'][] = array(
			'id' => $arProduct['ID'],
			'actions' => $arActions,
			'data' => $arProduct,
			'editable' => $arProduct['EDIT'] ? true : $arColumns,
			'columns' => array(
				'NAME' => '<a target="_self" href="'.$arProduct['PATH_TO_PRODUCT_SHOW'].'">'.$arProduct['NAME'].'</a>',
				'PRICE' => CCrmProduct::FormatPrice($arProduct),
				'SECTION_ID' => $sectionLink
			)
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.grid',
		'',
		array
		(
			'GRID_ID' => $arResult['GRID_ID'],
			'HEADERS' => $arResult['HEADERS'],
			'SORT' => $arResult['SORT'],
			'SORT_VARS' => $arResult['SORT_VARS'],
			'ROWS' => $arResult['GRID_DATA'],
			'FOOTER' =>
				array
				(
					array
					(
						'title' => GetMessage('CRM_ALL'),
						'value' => $arResult['ROWS_COUNT']
					)
				),
			'EDITABLE' => !$arResult['PERMS']['WRITE'] || $arResult['INTERNAL'] ? 'N' : 'Y',
			'ACTIONS' =>
				array
				(
					'delete' => $arResult['PERMS']['DELETE'],
					'list' => array()
				),
			'ACTION_ALL_ROWS' => true,
			'NAV_OBJECT' => $arResult['NAV_OBJECT'],
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => $arResult['TAB_ID'],
			'AJAX_MODE' => $arResult['INTERNAL'] ? 'N' : 'Y',
			'FILTER' => $arResult['FILTER'],
			'FILTER_PRESETS' => $arResult['FILTER_PRESETS']
		),
		$component
	);
?>