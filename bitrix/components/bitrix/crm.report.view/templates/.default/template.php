<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->IncludeComponent(
	'bitrix:report.view',
	'',
	array(
		'REPORT_ID' => $arParams['REPORT_ID'],
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => $arResult['REPORT_HELPER_CLASS']
	),
	false
);

CUtil::InitJSCore(array('ajax', 'popup'));
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

$arFiles = array();

//CCrmCompany
$arCompanyTypeList = CCrmStatus::GetStatusList('COMPANY_TYPE');
$arCompanyIndustryList = CCrmStatus::GetStatusList('INDUSTRY');
$obRes = CCrmCompany::GetList(
	array('ID' => 'DESC'),
	array(),
	array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO'),
	50
);
$arCompanies = array();
while ($arRes = $obRes->Fetch())
{
	if (!empty($arRes['LOGO']) && !isset($arFiles[$arRes['LOGO']]))
	{
		if ($arFile = CFile::GetFileArray($arRes['LOGO']))
		{
			$arFiles[$arRes['LOGO']] = CHTTP::URN2URI($arFile['SRC']);
		}
	}

	$arRes['SID'] = $arRes['ID'];

	$arDesc = Array();
	if (isset($arCompanyTypeList[$arRes['COMPANY_TYPE']]))
		$arDesc[] = $arCompanyTypeList[$arRes['COMPANY_TYPE']];
	if (isset($arCompanyIndustryList[$arRes['INDUSTRY']]))
		$arDesc[] = $arCompanyIndustryList[$arRes['INDUSTRY']];

	$arCompanies[] = array(
		'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
		'desc' => implode(', ', $arDesc),
		'id' => $arRes['SID'],
		'url' => CComponentEngine::MakePathFromTemplate(
			COption::GetOptionString('crm', 'path_to_company_show'),
			array('company_id' => $arRes['ID'])
		),
		'image' => isset($arFiles[$arRes['LOGO']]) ? $arFiles[$arRes['LOGO']] : '',
		'type'  => 'company',
		'selected' => false
	);
}

//CrmContact
$arContactTypeList = CCrmStatus::GetStatusList('CONTACT_TYPE');
$obRes = CCrmContact::GetList(
	array('LAST_NAME' => 'ASC', 'NAME' => 'ASC'),
	array(),
	array('ID', 'FULL_NAME', 'COMPANY_TITLE', 'PHOTO')
);
$arContacts = array();
while ($arRes = $obRes->Fetch())
{
	if (!empty($arRes['PHOTO']) && !isset($arFiles[$arRes['PHOTO']]))
	{
		if ($arFile = CFile::GetFileArray($arRes['PHOTO']))
		{
			$arFiles[$arRes['PHOTO']] = CHTTP::URN2URI($arFile['SRC']);
		}
	}

	$arContacts[] =
		array(
			'id' => $arRes['ID'],
			'url' => CComponentEngine::MakePathFromTemplate(
				COption::GetOptionString('crm', 'path_to_contact_show'),
				array('contact_id' => $arRes['ID'])
			),
			'title' => (str_replace(array(';', ','), ' ', $arRes['FULL_NAME'])),
			'desc' => empty($arRes['COMPANY_TITLE'])? '': $arRes['COMPANY_TITLE'],
			'image' => isset($arFiles[$arRes['PHOTO']])? $arFiles[$arRes['PHOTO']] : '',
			'type' => 'contact',
			'selected' => false
		);
}
//CrmLead
$arLeads = array();
$obRes = CCrmLead::GetList(
	array('TITLE' => 'ASC'),
	array(),
	array('ID', 'TITLE', 'FULL_NAME', 'STATUS_ID')
);
while ($arRes = $obRes->Fetch())
{
	$arLeads[] =
		array(
			'id' => $arRes['ID'],
			'url' => CComponentEngine::MakePathFromTemplate(
				COption::GetOptionString('crm', 'path_to_lead_show'),
				array('lead_id' => $arRes['ID'])
			),
			'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
			'desc' => $arRes['FULL_NAME'],
			'type' => 'lead',
			'selected' => false
		)
	;
}
?>
<script type="text/javascript" src="/bitrix/js/crm/crm.js"></script>

<div id="report-chfilter-examples-custom" style="display: none;">

	<div class="filter-field filter-field-company chfilter-field-COMPANY_BY" callback="crmCompanySelector">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field filter-field-contact chfilter-field-CONTACT_BY" callback="crmContactSelector">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field filter-field-lead chfilter-field-LEAD_BY" callback="crmLeadSelector">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-LEAD_BY.STATUS_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('STATUS'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-LEAD_BY.SOURCE_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('SOURCE'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-type chfilter-field-TYPE_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arTypes = CCrmStatus::GetStatusList('DEAL_TYPE'); ?>
			<? foreach($arTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-currency chfilter-field-CURRENCY_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arTypes = CCrmCurrencyHelper::PrepareListItems(); ?>
			<? foreach($arTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-EVENT_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('EVENT_TYPE'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-STAGE_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arStages = CCrmStatus::GetStatusList('DEAL_STAGE'); ?>
			<? foreach($arStages as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? foreach($arCompanyTypeList as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-COMPANY_BY.INDUSTRY_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('INDUSTRY'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-COMPANY_BY.EMPLOYEES_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('EMPLOYEES'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-CONTACT_BY.TYPE_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? foreach($arContactTypeList as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-CONTACT_BY.SOURCE_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('SOURCE'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-ORIGINATOR_BY.ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<?  $arOriginatorList = CCrmExternalSaleHelper::PrepareListItems() ?>
			<? foreach($arOriginatorList as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

</div>

<?$this->SetViewTarget("sidebar_tools_1", 100);?>
<? $reportCurrencyID = CCrmReportHelper::GetReportCurrencyID(); ?>
<div class="sidebar-block">
	<b class="r2"></b>
	<b class="r1"></b>
	<b class="r0"></b>
	<div class="sidebar-block-inner">
		<div class="filter-block">
			<label for="crmReportCurrencyID" class="filter-field-title"><?= str_replace('#CURRENCY#', CCrmCurrency::GetCurrencyName(CCrmCurrency::GetAccountCurrencyID()), GetMessage('CRM_REPORT_CURRENCY_INFO')) ?></label>
		</div>
	</div>
	<i class="r0"></i>
	<i class="r1"></i>
	<i class="r2"></i>
</div>
<?$this->EndViewTarget();?>
<script type="text/javascript">
	var crmCompanyElements = <? echo CUtil::PhpToJsObject($arCompanies); ?>;
	var crmContactElements = <? echo CUtil::PhpToJsObject($arContacts); ?>;
	var crmLeadElements = <? echo CUtil::PhpToJsObject($arLeads); ?>;

	var crmCompanyDialogID = '';
	var crmContactDialogID = '';
	var crmLeadDialogID = '';

	var crmCompanySelector_LAST_CALLER = null;
	var crmContactSelector_LAST_CALLER = null;
	var crmLeadSelector_LAST_CALLER = null;

	function openCrmEntityDialog(name, typeName, elements, caller, onClose)
	{
		var dlgID = CRM.Set(caller,
			name,
			typeName, //subName for dlgID
			elements,
			false,
			false,
			[typeName],
			{
				'company': '<?=CUtil::JSEscape(GetMessage('CRM_FF_COMPANY'))?>',
				'contact': '<?=CUtil::JSEscape(GetMessage('CRM_FF_CONTACT'))?>',
				'lead': '<?=CUtil::JSEscape(GetMessage('CRM_FF_LEAD'))?>',
				'ok': '<?=CUtil::JSEscape(GetMessage('CRM_FF_OK'))?>',
				'cancel': '<?=CUtil::JSEscape(GetMessage('CRM_FF_CANCEL'))?>',
				'close': '<?=CUtil::JSEscape(GetMessage('CRM_FF_CLOSE'))?>',
				'wait': '<?=CUtil::JSEscape(GetMessage('CRM_FF_SEARCH'))?>',
				'noresult': '<?=CUtil::JSEscape(GetMessage('CRM_FF_NO_RESULT'))?>',
				'add' : '<?=CUtil::JSEscape(GetMessage('CRM_FF_CHOISE'))?>',
				'edit' : '<?=CUtil::JSEscape(GetMessage('CRM_FF_CHANGE'))?>',
				'search' : '<?=CUtil::JSEscape(GetMessage('CRM_FF_SEARCH'))?>',
				'last' : '<?=CUtil::JSEscape(GetMessage('CRM_FF_LAST'))?>'
			},
			true
		);

		var dlg = obCrm[dlgID];
		dlg.AddOnSaveListener(onClose);
		dlg.Open();

		return dlgID;
	}

	function crmCompanySelector(caller)
	{
		crmCompanySelector_LAST_CALLER = caller;
		crmCompanyDialogID =  openCrmEntityDialog('company', 'company', crmCompanyElements, crmCompanySelector_LAST_CALLER, onCrmCompanyDialogClose);
	}

	function onCrmCompanyDialogClose(arElements)
	{
		if(!arElements || typeof(arElements['company']) == 'undefined')
		{
			return;
		}

		var element = arElements['company']['0'];
		if(element)
		{
			crmCompanySelectorCatch({ 'id':element['id'], 'name':element['title'] });
		}
		else
		{
			crmCompanySelectorCatch(null);
		}

		obCrm[crmCompanyDialogID].RemoveOnSaveListener(onCrmCompanyDialogClose);
	}

	function crmCompanySelectorCatch(item)
	{
		if(item && BX.type.isNotEmptyString(item['name']))
		{
			crmCompanySelector_LAST_CALLER.value = BX.util.htmlspecialchars(item['name'] + ' [' + item['id'] + ']');
		}
		else
		{
			crmCompanySelector_LAST_CALLER.value = '';
		}

		var h = BX.findNextSibling(crmCompanySelector_LAST_CALLER, { 'tag':'input', 'attr':{ 'type':'hidden' } });
		h.value = item ? item['id'] : '';
	}

	function crmCompanySelectorClear(e)
	{
		crmCompanySelector_LAST_CALLER = BX.findChild(this.parentNode, { 'tag':'input', 'class':'webform-field-textbox'});

		BX.PreventDefault(e);
		crmCompanySelectorCatch(null);
	}

	function crmContactSelector(caller)
	{
		crmContactSelector_LAST_CALLER = caller;
		crmContactDialogID =  openCrmEntityDialog('contact', 'contact', crmContactElements, crmContactSelector_LAST_CALLER, onCrmContactDialogClose);
	}

	function onCrmContactDialogClose(arElements)
	{
		if(!arElements || typeof(arElements['contact']) == 'undefined')
		{
			return;
		}

		var element = arElements['contact']['0'];
		if(element)
		{
			crmContactSelectorCatch({ 'id':element['id'], 'name':element['title'] });
		}
		else
		{
			crmContactSelectorCatch(null);
		}

		obCrm[crmContactDialogID].RemoveOnSaveListener(onCrmContactDialogClose);
	}

	function crmContactSelectorCatch(item)
	{
		if(item && BX.type.isNotEmptyString(item['name']))
		{
			crmContactSelector_LAST_CALLER.value = BX.util.htmlspecialchars(item['name'] + ' [' + item['id'] + ']');
		}
		else
		{
			crmContactSelector_LAST_CALLER.value = '';
		}

		var h = BX.findNextSibling(crmContactSelector_LAST_CALLER, { 'tag':'input', 'attr':{ 'type':'hidden' } });
		h.value = item ? item['id'] : '';
	}

	function crmContactSelectorClear(e)
	{
		crmContactSelector_LAST_CALLER = BX.findChild(this.parentNode, { 'tag':'input', 'class':'webform-field-textbox'});

		BX.PreventDefault(e);
		crmContactSelectorCatch(null);
	}

	function crmLeadSelector(caller)
	{
		crmLeadSelector_LAST_CALLER = caller;
		crmLeadDialogID =  openCrmEntityDialog('lead', 'lead', crmLeadElements, crmLeadSelector_LAST_CALLER, onCrmLeadDialogClose);
	}

	function onCrmLeadDialogClose(arElements)
	{
		if(!arElements || typeof(arElements['lead']) == 'undefined')
		{
			return;
		}

		var element = arElements['lead']['0'];
		if(element)
		{
			crmLeadSelectorCatch({ 'id':element['id'], 'name':element['title'] });
		}
		else
		{
			crmLeadSelectorCatch(null);
		}

		obCrm[crmLeadDialogID].RemoveOnSaveListener(onCrmLeadDialogClose);
	}

	function crmLeadSelectorCatch(item)
	{
		if(item && BX.type.isNotEmptyString(item['name']))
		{
			crmLeadSelector_LAST_CALLER.value = BX.util.htmlspecialchars(item['name'] + ' [' + item['id'] + ']');
		}
		else
		{
			crmLeadSelector_LAST_CALLER.value = '';
		}

		var h = BX.findNextSibling(crmLeadSelector_LAST_CALLER, { 'tag':'input', 'attr':{ 'type':'hidden' } });
		h.value = item ? item['id'] : '';
	}

	function crmLeadSelectorClear(e)
	{
		crmLeadSelector_LAST_CALLER = BX.findChild(this.parentNode, { 'tag':'input', 'class':'webform-field-textbox'});

		BX.PreventDefault(e);
		crmLeadSelectorCatch(null);
	}

	BX.ready(function()
	{
		window.setTimeout(
			function()
			{
				// Company
				var company = BX.findChild(BX('report-rewrite-filter'), { 'class':'chfilter-field-COMPANY_BY' }, true);
				if(company)
				{
					BX.bind(
						BX.findChild(company, { 'tag':'input', 'class':'webform-field-textbox' }, true),
						'click',
						function(e)
						{
							if(!e)
							{
								e = window.event;
							}

							crmCompanySelector(this);
							BX.PreventDefault(e);
						}
					);
					BX.bind(BX.findChild(company, { 'tag':'a', 'class':'webform-field-textbox-clear' }, true), 'click', crmCompanySelectorClear);
				}

				// Contact
				var contact = BX.findChild(BX('report-rewrite-filter'), { 'class':'chfilter-field-CONTACT_BY' }, true);
				if(contact)
				{
					BX.bind(
						BX.findChild(contact, { 'tag':'input', 'class':'webform-field-textbox' }, true),
						'click',
						function(e)
						{
							if(!e)
							{
								e = window.event;
							}

							crmContactSelector(this);
							BX.PreventDefault(e);
						}
					);
					BX.bind(BX.findChild(contact, { 'tag':'a', 'class':'webform-field-textbox-clear' }, true), 'click', crmContactSelectorClear);
				}

				// Lead
				var lead = BX.findChild(BX('report-rewrite-filter'), { 'class':'chfilter-field-LEAD_BY' }, true);
				if(lead)
				{
					BX.bind(
						BX.findChild(lead, { 'tag':'input', 'class':'webform-field-textbox' }, true),
						'click',
						function(e)
						{
							if(!e)
							{
								e = window.event;
							}

							crmLeadSelector(this);
							BX.PreventDefault(e);
						}
					);
					BX.bind(BX.findChild(lead, { 'tag':'a', 'class':'webform-field-textbox-clear' }, true), 'click', crmLeadSelectorClear);
				}
			},
		500);
	});
</script>
