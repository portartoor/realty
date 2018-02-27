if (!BX.VoxImplant)
	BX.VoxImplant = function() {};

BX.VoxImplant.rentPhone = function() {};

BX.VoxImplant.rentPhone.init = function(params)
{
	BX.VoxImplant.rentPhone.publicFolder = params.publicFolder;
	BX.VoxImplant.rentPhone.selectPlaceholder = params.selectPlaceholder;
	BX.VoxImplant.rentPhone.numbersPlaceholder = params.numbersPlaceholder;
	BX.VoxImplant.rentPhone.verifiedAddressesPlaceholder = params.verifiedAddressesPlaceholder;
	BX.VoxImplant.rentPhone.location = params.location;

	BX.VoxImplant.rentPhone.country = false;
	BX.VoxImplant.rentPhone.countryTypes = {};
	BX.VoxImplant.rentPhone.countryStates = {};
	BX.VoxImplant.rentPhone.countryRegion = {};
	BX.VoxImplant.rentPhone.countryRegionNumbers = {};
	BX.VoxImplant.rentPhone.countryVerifiedAddresses = {};
	BX.VoxImplant.rentPhone.countryRegionNumberCount = 0;

	BX.VoxImplant.rentPhone.currentCountry = '';
	BX.VoxImplant.rentPhone.currentCountryState = '';
	BX.VoxImplant.rentPhone.currentCountryCategory = '';
	BX.VoxImplant.rentPhone.currentCountryRegion = '';
	BX.VoxImplant.rentPhone.currentNumber = '';
	BX.VoxImplant.rentPhone.currentAddressVerification = null;
	BX.VoxImplant.rentPhone.phoneNumberInstallationPrice = 0;
	BX.VoxImplant.rentPhone.phoneNumberMonthPrice = 0;
	BX.VoxImplant.rentPhone.phoneNumberFullPrice = 0;
	BX.VoxImplant.rentPhone.phoneNumberCurrency = 'RUR';
	BX.VoxImplant.rentPhone.containerNode = BX('vi_rent_options_div');

	BX.VoxImplant.rentPhone.getCountry();

	BX.ready(function(){
		BX.bind(BX('vi_rent_options'), 'click', function(e){
			if (BX('vi_rent_options_div').style.display == 'none')
			{
				BX.removeClass(BX(this), 'webform-button-create');
				BX('vi_rent_options_div').style.display = 'block';
			}
			else
			{
				BX.addClass(BX(this), 'webform-button-create');
				BX('vi_rent_options_div').style.display = 'none';
			}
			BX.PreventDefault(e);
		});
	});
};

BX.VoxImplant.rentPhone.getCountry = function()
{
	if (BX.VoxImplant.rentPhone.blockAjax)
		return true;

	if (!BX.VoxImplant.rentPhone.country)
	{
		BX.VoxImplant.rentPhone.blockAjax = true;
		BX.showWait(BX.VoxImplant.rentPhone.containerNode);
		BX.ajax({
			url: '/bitrix/components/bitrix/voximplant.config.rent/ajax.php?VI_GET_COUNTRY',
			method: 'POST',
			dataType: 'json',
			timeout: 60,
			data: {'VI_GET_COUNTRY': 'Y', 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function(data)
			{
				BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
				BX.VoxImplant.rentPhone.blockAjax = false;
				if (data.ERROR == '')
				{
					BX.VoxImplant.rentPhone.country = {};
					for (var countryCode in data.RESULT)
					{
						BX.VoxImplant.rentPhone.country[countryCode] = data.RESULT[countryCode];
						BX.VoxImplant.rentPhone.countryTypes[countryCode] = data.RESULT[countryCode].CATEGORIES;
					}

					BX.VoxImplant.rentPhone.drawSelectBox('country');
				}
				else
				{
					alert(BX.message('VI_CONFIG_RENT_AJAX_ERROR'));
				}
			}, this),
			onfailure: function(){
				BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
				BX.VoxImplant.rentPhone.blockAjax = false;
			}
		});
	}
	else
	{
		BX.VoxImplant.rentPhone.drawSelectBox('country');
	}
};
BX.VoxImplant.rentPhone.getCountryCategoryParams = function()
{
	var count = 0;
	var hasGeographic = false;
	var defaultType = '';

	for (var countryType in BX.VoxImplant.rentPhone.countryTypes[BX.VoxImplant.rentPhone.currentCountry])
	{
		if (defaultType == '')
		{
			defaultType = countryType;
		}
		if (countryType == 'GEOGRAPHIC')
		{
			hasGeographic = true;
		}
		count++;
	}

	if (hasGeographic)
	{
		defaultType = 'GEOGRAPHIC';
	}

	return {'TYPE': defaultType, 'COUNT': count}
};
BX.VoxImplant.rentPhone.getCountryRegionParams = function()
{
	var count = 0;
	var defaultRegion = '';
	var defaultRegionCount = 0;

	for (var regionId in BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState])
	{
		if (defaultRegion == '')
		{
			defaultRegion = BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][regionId].REGION_ID;
			defaultRegionCount = BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][regionId].PHONE_COUNT;
		}
		count++;
	}

	return {'REGION_ID': defaultRegion, 'REGION_COUNT': defaultRegionCount, 'COUNT': count}
};

BX.VoxImplant.rentPhone.getState = function()
{
	BX.VoxImplant.rentPhone.drawSelectBox('country');
	BX.VoxImplant.rentPhone.drawSelectBox('countryCategory');
	if (BX.VoxImplant.rentPhone.currentCountry != '-' && BX.VoxImplant.rentPhone.currentCountryCategory != '-' && (!BX.VoxImplant.rentPhone.countryStates[BX.VoxImplant.rentPhone.currentCountry] || !BX.VoxImplant.rentPhone.countryStates[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory]))
	{
		var ajaxCurrentCountry = BX.VoxImplant.rentPhone.currentCountry;
		var ajaxCurrentCountryCategory = BX.VoxImplant.rentPhone.currentCountryCategory;
		BX.showWait(BX.VoxImplant.rentPhone.containerNode);
		BX.ajax({
			url: '/bitrix/components/bitrix/voximplant.config.rent/ajax.php?VI_GET_STATE',
			method: 'POST',
			dataType: 'json',
			timeout: 60,
			data: {
				'VI_GET_STATE': 'Y',
				'COUNTRY_CODE': BX.VoxImplant.rentPhone.currentCountry,
				'COUNTRY_CATEGORY': BX.VoxImplant.rentPhone.currentCountryCategory,
				'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function(data)
			{
				BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
				if (data.ERROR == '')
				{
					if (!BX.VoxImplant.rentPhone.countryStates[BX.VoxImplant.rentPhone.currentCountry])
						BX.VoxImplant.rentPhone.countryStates[BX.VoxImplant.rentPhone.currentCountry] = {};

					if (!BX.VoxImplant.rentPhone.countryStates[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory])
						BX.VoxImplant.rentPhone.countryStates[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory] = {};

					for (var countryStateCode in data.RESULT)
					{
						BX.VoxImplant.rentPhone.countryStates[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][countryStateCode] = data.RESULT[countryStateCode];
					}
					if (ajaxCurrentCountry == BX.VoxImplant.rentPhone.currentCountry && ajaxCurrentCountryCategory == BX.VoxImplant.rentPhone.currentCountryCategory)
						BX.VoxImplant.rentPhone.drawSelectBox('state');
				}
				else
				{
					alert(BX.message('VI_CONFIG_RENT_AJAX_ERROR'));
				}
			}, this)
		});
	}
	else
	{
		BX.VoxImplant.rentPhone.drawSelectBox('state');
	}
};

BX.VoxImplant.rentPhone.getCountryCategory = function()
{
	var params = BX.VoxImplant.rentPhone.getCountryCategoryParams();
	if (params.COUNT > 1)
	{
		BX.VoxImplant.rentPhone.drawSelectBox('countryCategory');
	}
	else if (params.TYPE != '')
	{
		BX.VoxImplant.rentPhone.currentCountryCategory = params.TYPE;
		BX.VoxImplant.rentPhone.drawSelectBox('country');
		BX.VoxImplant.rentPhone.getState();
	}
};

BX.VoxImplant.rentPhone.getRegion = function()
{
	BX.VoxImplant.rentPhone.drawSelectBox('country');
	BX.VoxImplant.rentPhone.drawSelectBox('countryCategory');
	if (BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry]['CATEGORIES'][BX.VoxImplant.rentPhone.currentCountryCategory].COUNTRY_HAS_STATES === true)
		BX.VoxImplant.rentPhone.drawSelectBox('state');

	BX.showWait(BX.VoxImplant.rentPhone.containerNode);
	if (BX.VoxImplant.rentPhone.currentCountry != '-' && BX.VoxImplant.rentPhone.currentCountryState != '-' && BX.VoxImplant.rentPhone.currentCountryCategory != '-' && (!BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry] || !BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory] || !BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState]))
	{
		var ajaxCurrentCountry = BX.VoxImplant.rentPhone.currentCountry;
		var ajaxCurrentCountryCategory = BX.VoxImplant.rentPhone.currentCountryCategory;
		var ajaxCurrentCountryState = BX.VoxImplant.rentPhone.currentCountryState;
		BX.showWait(BX.VoxImplant.rentPhone.containerNode);
		BX.ajax({
			url: '/bitrix/components/bitrix/voximplant.config.rent/ajax.php?VI_GET_REGION',
			method: 'POST',
			dataType: 'json',
			timeout: 60,
			data: {
				'VI_GET_REGION': 'Y',
				'COUNTRY_CODE': BX.VoxImplant.rentPhone.currentCountry,
				'COUNTRY_CATEGORY': BX.VoxImplant.rentPhone.currentCountryCategory,
				'COUNTRY_STATE': BX.VoxImplant.rentPhone.currentCountryState,
				'VI_AJAX_CALL' : 'Y',
				'sessid': BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function(data)
			{
				BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
				if (data.ERROR == '')
				{
					if (!BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry])
						BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry] = {};

					if (!BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory])
						BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory] = {};

					if (!BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState])
						BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState] = {};

					for (var countryRegionCode in data.RESULT)
					{
						BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][countryRegionCode] = data.RESULT[countryRegionCode];
					}
					if (ajaxCurrentCountry == BX.VoxImplant.rentPhone.currentCountry && ajaxCurrentCountryState == BX.VoxImplant.rentPhone.currentCountryState && ajaxCurrentCountryCategory == BX.VoxImplant.rentPhone.currentCountryCategory)
						BX.VoxImplant.rentPhone.drawSelectBox('region');
				}
				else
				{
					alert(BX.message('VI_CONFIG_RENT_AJAX_ERROR'));
				}
			}, this)
		});
	}
	else
	{
		BX.VoxImplant.rentPhone.drawSelectBox('region');
	}
};

BX.VoxImplant.rentPhone.getVerifiedAddresses = function()
{
	var _this = BX.VoxImplant.rentPhone;
	var currentRegionParameters = _this.getCurrentRegionParameters();

	if (!currentRegionParameters)
	{
		_this.drawNumberBox();
		return;
	}

	if(currentRegionParameters && currentRegionParameters.REGULATION_ADDRESS_TYPE != '')
	{
		BX.showWait(BX.VoxImplant.rentPhone.containerNode);
		BX.ajax({
			url: '/bitrix/components/bitrix/voximplant.config.rent/ajax.php?VI_GET_VERIFIED_ADDRESSES',
			method: 'POST',
			dataType: 'json',
			timeout: 60,
			data: {
				'VI_GET_VERIFIED_ADDRESSES': 'Y',
				'COUNTRY_CODE': _this.currentCountry,
				'COUNTRY_REGION': currentRegionParameters.REGION_CODE,
				'COUNTRY_CATEGORY': _this.currentCountryCategory,
				'VI_AJAX_CALL' : 'Y',
				'sessid': BX.bitrix_sessid()
			},
			onsuccess: function(data)
			{
				BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
				if (data.ERROR == '')
				{
					_this.countryRegion[_this.currentCountry][_this.currentCountryCategory][_this.currentCountryState][_this.currentCountryRegion].addressVerification = data.RESULT;
					_this.drawVerifiedAddresses();
				}
				else
				{
					alert(BX.message('VI_CONFIG_RENT_AJAX_ERROR'));
				}
			}
		});


	}
	else
	{
		_this.getNumbers();
	}
};

BX.VoxImplant.rentPhone.getNumbers = function()
{
	if (BX.VoxImplant.rentPhone.currentCountryRegion == '-' || BX.VoxImplant.rentPhone.currentCountryRegion == '')
		return false;


	BX.showWait(BX.VoxImplant.rentPhone.containerNode);
	if (
			BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry].CAN_LIST_PHONES && (
		   !BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry]
		|| !BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory]
		|| !BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState]
		|| !BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][BX.VoxImplant.rentPhone.currentCountryRegion]
	))
	{
		BX.ajax({
			url: '/bitrix/components/bitrix/voximplant.config.rent/ajax.php?VI_GET_PHONE_NUMBERS',
			method: 'POST',
			dataType: 'json',
			timeout: 60,
			data: {
				'VI_GET_PHONE_NUMBERS': 'Y',
				'COUNTRY_CODE': BX.VoxImplant.rentPhone.currentCountry,
				'COUNTRY_REGION': BX.VoxImplant.rentPhone.currentCountryRegion,
				'COUNTRY_CATEGORY': BX.VoxImplant.rentPhone.currentCountryCategory,
				'VI_AJAX_CALL' : 'Y',
				'sessid': BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function(data)
			{
				BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
				if (data.ERROR == '')
				{
					if (!BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry])
						BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry] = {};

					if (!BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory])
						BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory] = {};

					if (!BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState])
						BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState] = {};

					if (!BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][BX.VoxImplant.rentPhone.currentCountryRegion])
						BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][BX.VoxImplant.rentPhone.currentCountryRegion] = {};

					for (var number in data.RESULT)
					{
						BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][BX.VoxImplant.rentPhone.currentCountryRegion][number] = data.RESULT[number];
					}
					BX.VoxImplant.rentPhone.drawNumberBox();
				}
				else
				{
					alert(BX.message('VI_CONFIG_RENT_AJAX_ERROR'));
				}
			}, this)
		});
	}
	else
	{
		BX.VoxImplant.rentPhone.drawNumberBox();
	}
};

BX.VoxImplant.rentPhone.drawSelectBox = function(name)
{
	BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
	if (name == 'state')
	{
		if (BX.VoxImplant.rentPhone.currentCountry == '-')
		{
			return false;
		}
		else if (BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry]['CATEGORIES'][BX.VoxImplant.rentPhone.currentCountryCategory].COUNTRY_HAS_STATES !== true)
		{
			BX.VoxImplant.rentPhone.currentCountryState = "";
			BX.VoxImplant.rentPhone.getRegion();
			return false;
		}
	}
	else if (name == 'countryCategory')
	{
		var params = BX.VoxImplant.rentPhone.getCountryCategoryParams();
		if (params.COUNT <= 1)
		{
			return false;
		}
	}
	else if (name == 'region')
	{
		if (BX.VoxImplant.rentPhone.currentCountryState == '-')
		{
			return false;
		}
		BX.VoxImplant.rentPhone.countryRegionNumberCount = 0;

		var params = BX.VoxImplant.rentPhone.getCountryRegionParams();
		if (params.COUNT == 1)
		{
			BX.VoxImplant.rentPhone.currentCountryRegion = params.REGION_ID;
			BX.VoxImplant.rentPhone.countryRegionNumberCount = params.REGION_COUNT;
			BX.VoxImplant.rentPhone.verifiedAddressesPlaceholder.innerHTML = '';
			BX.VoxImplant.rentPhone.numbersPlaceholder.innerHTML = '';
			BX.VoxImplant.rentPhone.currentNumber = "";
			BX.VoxImplant.rentPhone.getVerifiedAddresses();
			return false;
		}
		else if (params.COUNT == 0)
		{
			BX.VoxImplant.rentPhone.currentCountryRegion = 0;
			BX.VoxImplant.rentPhone.getVerifiedAddresses();
			return false;
		}
	}
	var items = [];
	if (name == 'country')
	{
		items.push(
			BX.create("option", {attrs:{'value': '-'}, style:{'color': '#888888'}, html: BX.message('VI_CONFIG_RENT_COUNTRY')})
		);
		for (var countryCode in BX.VoxImplant.rentPhone.country)
		{
			var attrs = {'value': countryCode};
			if (BX.VoxImplant.rentPhone.currentCountry == countryCode)
				attrs['selected'] = 'true';

			items.push(
				BX.create("option", {attrs:attrs, html: BX.VoxImplant.rentPhone.country[countryCode].COUNTRY_NAME})
			);
		}
		BX.VoxImplant.rentPhone.selectPlaceholder.innerHTML = '';
	}
	else if (name == 'countryCategory')
	{
		items.push(
			BX.create("option", {attrs:{'value': '-'}, style:{'color': '#888888'}, html: BX.message('VI_CONFIG_RENT_PHONE_NUMBER')})
		);
		for (var countryType in BX.VoxImplant.rentPhone.countryTypes[BX.VoxImplant.rentPhone.currentCountry])
		{
			if (countryType == 'MOSCOW495')
				continue;

			var attrs = {'value': countryType};
			if (BX.VoxImplant.rentPhone.currentCountryCategory == countryType)
				attrs['selected'] = 'true';

			var localization = BX.VoxImplant.rentPhone.countryTypes[BX.VoxImplant.rentPhone.currentCountry][countryType].PHONE_TYPE;
			if (BX.message['VI_CONFIG_RENT_'+localization])
			{
				localization = BX.message('VI_CONFIG_RENT_'+localization);
			}

			items.push(
				BX.create("option", {attrs:attrs, html: localization})
			);
		}
	}
	else if (name == 'state')
	{
		items.push(
			BX.create("option", {attrs:{'value': '-'}, style:{'color': '#888888'}, html: BX.message('VI_CONFIG_RENT_STATE')})
		);
		for (var countryStateCode in BX.VoxImplant.rentPhone.countryStates[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory])
		{
			var attrs = {'value': countryStateCode};
			if (BX.VoxImplant.rentPhone.currentCountryState == countryStateCode)
				attrs['selected'] = 'true';

			items.push(
				BX.create("option", {attrs:attrs, html: BX.VoxImplant.rentPhone.countryStates[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][countryStateCode]})
			);
		}
	}
	else if (name == 'region')
	{
		items.push(
			BX.create("option", {attrs:{'value': '-'}, style:{'color': '#888888'}, html: BX.message(BX.VoxImplant.rentPhone.currentCountryCategory == 'TOLLFREE'? 'VI_CONFIG_RENT_CATEGORY': 'VI_CONFIG_RENT_REGION')})
		);

		if (BX.VoxImplant.rentPhone.currentCountry == 'RU')
		{
			var customSortForRu = [1, 15, 2];
			for (var i = 0; i < customSortForRu.length; i++)
			{
				if (!BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][customSortForRu[i]])
					continue;

				items.push(
					BX.create("option", {attrs:{
						'value': BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][customSortForRu[i]].REGION_ID,
						'data-count': BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][customSortForRu[i]].PHONE_COUNT},
						html: BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][customSortForRu[i]].REGION_NAME
					})
				);
				BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][customSortForRu[i]].HIDE = true;
			}
		}
		var arRegion = BX.util.objectSort(BX.VoxImplant.rentPhone.countryRegion[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState], 'REGION_NAME', 'asc');
		for (var i = 0; i < arRegion.length; i++)
		{
			if (arRegion[i].HIDE)
				continue;

			items.push(
				BX.create("option", {attrs:{'value': arRegion[i].REGION_ID, 'data-count': arRegion[i].PHONE_COUNT}, html: arRegion[i].REGION_NAME})
			);
		}
	}

	var selectBox = BX.create("div", {
		props : { className : "tel-set-item-select-wrap"},
		children: [
			BX.create("select", {
				props : { className : "tel-set-item-select" },
				events:
				{
					change : function(e)
					{
						if (name == 'country')
						{
							BX.VoxImplant.rentPhone.currentCountry = this.options[this.selectedIndex].value;
							BX.VoxImplant.rentPhone.currentCountryCategory = '-';
							BX.VoxImplant.rentPhone.currentCountryState = '-';
							BX.VoxImplant.rentPhone.currentCountryRegion = '-';
							BX.VoxImplant.rentPhone.verifiedAddressesPlaceholder.innerHTML = '';
							BX.VoxImplant.rentPhone.numbersPlaceholder.innerHTML = '';
							BX.VoxImplant.rentPhone.currentNumber = "";
							BX.VoxImplant.rentPhone.getCountry();
							BX.VoxImplant.rentPhone.getCountryCategory();
						}
						else if (name == 'countryCategory')
						{
							BX.VoxImplant.rentPhone.currentCountryCategory = this.options[this.selectedIndex].value;
							BX.VoxImplant.rentPhone.currentCountryState = '-';
							BX.VoxImplant.rentPhone.currentCountryRegion = '-';
							BX.VoxImplant.rentPhone.verifiedAddressesPlaceholder.innerHTML = '';
							BX.VoxImplant.rentPhone.numbersPlaceholder.innerHTML = '';
							BX.VoxImplant.rentPhone.currentNumber = "";
							BX.VoxImplant.rentPhone.getState();
						}
						else if (name == 'state')
						{
							BX.VoxImplant.rentPhone.currentCountryState = this.options[this.selectedIndex].value;
							BX.VoxImplant.rentPhone.currentCountryRegion = '-';
							BX.VoxImplant.rentPhone.verifiedAddressesPlaceholder.innerHTML = '';
							BX.VoxImplant.rentPhone.numbersPlaceholder.innerHTML = '';
							BX.VoxImplant.rentPhone.currentNumber = "";
							BX.VoxImplant.rentPhone.getRegion();
						}
						else if (name == 'region')
						{
							BX.VoxImplant.rentPhone.currentCountryRegion = this.options[this.selectedIndex].value;
							BX.VoxImplant.rentPhone.countryRegionNumberCount = this.options[this.selectedIndex].getAttribute('data-count');
							BX.VoxImplant.rentPhone.verifiedAddressesPlaceholder.innerHTML = '';
							BX.VoxImplant.rentPhone.numbersPlaceholder.innerHTML = '';
							BX.VoxImplant.rentPhone.currentNumber = "";
							BX.VoxImplant.rentPhone.getVerifiedAddresses();
						}
					}
				},
				children: items
			})
		]
	});
	BX.VoxImplant.rentPhone.selectPlaceholder.appendChild(selectBox);

};

BX.VoxImplant.rentPhone.getCurrentRegionParameters = function()
{
	var _this = BX.VoxImplant.rentPhone;
	if(_this.countryRegion[_this.currentCountry]
		&&_this.countryRegion[_this.currentCountry][_this.currentCountryCategory]
		&&_this.countryRegion[_this.currentCountry][_this.currentCountryCategory][_this.currentCountryState]
		&&_this.countryRegion[_this.currentCountry][_this.currentCountryCategory][_this.currentCountryState][_this.currentCountryRegion]
	)
		return _this.countryRegion[_this.currentCountry][_this.currentCountryCategory][_this.currentCountryState][_this.currentCountryRegion];
	else
		return null;
};

BX.VoxImplant.rentPhone.drawVerifiedAddresses = function()
{
	var verifiedAddressesElement;
	var inputElements = [];
	var _this = BX.VoxImplant.rentPhone;
	var currentRegionParameters = _this.getCurrentRegionParameters();
	var priceHtml = '';
	if(!currentRegionParameters || !currentRegionParameters.addressVerification)
		return false;

	var uploadUrl = (function()
	{
		var uploadUrl;
		var parameters;
		var _this = BX.VoxImplant.rentPhone;
		var currentRegionParameters = _this.getCurrentRegionParameters();

		parameters = {
			'SHOW_UPLOAD_IFRAME': 'Y',
			'UPLOAD_COUNTRY_CODE': _this.currentCountry,
			'UPLOAD_ADDRESS_TYPE': (currentRegionParameters ? currentRegionParameters.REGULATION_ADDRESS_TYPE : ''),
			'UPLOAD_PHONE_CATEGORY': _this.currentCountryCategory,
			'UPLOAD_REGION_CODE': (currentRegionParameters ? currentRegionParameters.REGION_CODE : '')
		};
		return _this.publicFolder + 'configs.php?' + BX.ajax.prepareData(parameters);
	})();

	BX.VoxImplant.rentPhone.setPriceFields();
	if(BX.VoxImplant.rentPhone.phoneNumberInstallationPrice > 0)
		priceHtml = BX.message('VI_CONFIG_RENT_INSTALLATION_PRICE') + ': <strong>' + BX.message('VI_CONFIG_RENT_FEE_'+BX.VoxImplant.rentPhone.phoneNumberCurrency).replace('#MONEY#', BX.VoxImplant.rentPhone.phoneNumberInstallationPrice) + '</strong><br>';

	if(BX.VoxImplant.rentPhone.phoneNumberMonthPrice > 0)
		priceHtml = priceHtml + BX.message('VI_CONFIG_RENT_MONTH_PRICE') + ': <strong>' + BX.message('VI_CONFIG_RENT_FEE_'+BX.VoxImplant.rentPhone.phoneNumberCurrency).replace('#MONEY#', BX.VoxImplant.rentPhone.phoneNumberMonthPrice) + '</strong>';


	BX.VoxImplant.rentPhone.verifiedAddressesPlaceholder.innerHTML = '';
	if(currentRegionParameters.addressVerification.VERIFICATIONS_AVAILABLE && currentRegionParameters.addressVerification.VERIFIED_ADDRESS.length > 0)
	{
		currentRegionParameters.addressVerification.VERIFIED_ADDRESS.map(function(address)
		{
			var inputElement;

			inputElement = BX.create('div', {props: {className: 'tel-set-list-nums-wrap'}, children: [
				BX.create('span', {props: {className: 'tel-set-list-item'}, children: [
					BX.create('input', {
						attrs: {type: 'radio', value: address.ID, name: 'verified-address-id', id: 'verified-address-id-'+address.ID},
						props: {className: 'tel-set-list-item-radio'},
						events: {
							change: function(e)
							{
								BX.VoxImplant.rentPhone.currentAddressVerification = this.value;
							}
						}
					}),
					BX.create('label', {
						attrs: {for: 'verified-address-id-'+address.ID},
						props: {className: 'tel-set-list-item-num'},
						text: _this.formatVerifiedAddress(address)
					})
				]})
			]});

			inputElements.push(inputElement);
		});

		verifiedAddressesElement = BX.create('div', {props:{className: 'tel-set-list-nums'}, children:[
			BX.create('div', {
				props: {className: 'tel-set-list-nums-title'},
				html: BX.message('VI_CONFIG_SELECT_ADDRESS_1') + ' <span class="webform-button-ajax"><a href="' + uploadUrl + '">' + BX.message('VI_CONFIG_SELECT_ADDRESS_2') + '</a></span>'
			}),
			BX.create('div', {props: {className: 'tel-set-separate'}}),
			BX.create('div', {children: inputElements})
		]});

		_this.getNumbers();
	}
	else if(currentRegionParameters.addressVerification.VERIFICATIONS_PENDING > 0)
	{
		//todo: should we inform client of pending verifications?
	}
	else
	{
		verifiedAddressesElement = BX.create('div', {children: [
			BX.create('div', {props: {className: "tel-set-separate" }}),
			BX.create('div', {
				props: {className: "tel-set-list-nums-title"},
				html: BX.message('VI_CONFIG_ADDRESS_VERIFICATION_REQUIRED')
			}),
			BX.create('div', {props: {className: "tel-set-separate" }}),
			BX.create("div", {
				attrs: { style: 'line-height: 33px'},
				props : { className : "tel-set-amount-text" },
				html: priceHtml
			}),
			BX.create("div", {props : { className : "tel-set-amount-block" }, children:[
				BX.create("div", {
					props : {className : "webform-button webform-button-create"},
					html: '<span class="webform-button-left"></span><span class="webform-button-text">'+BX.message('VI_CONFIG_UPLOAD_ADDRESS_VERIFICATION')+'</span><span class="webform-button-right"></span>',
					events:
					{
						click : function(e)
						{
							document.location.href = uploadUrl;
						}
					}
				})
			]})
		]});
	}
	BX.VoxImplant.rentPhone.verifiedAddressesPlaceholder.appendChild(verifiedAddressesElement);
};

BX.VoxImplant.rentPhone.drawNumberBox = function()
{
	var priceHtml = '';

	BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
	if (BX.VoxImplant.rentPhone.currentCountryRegion == '-' || BX.VoxImplant.rentPhone.currentCountryCategory == '' || BX.VoxImplant.rentPhone.currentCountryCategory == '-')
	{
		return false;
	}
	BX.VoxImplant.rentPhone.setPriceFields();
	if(BX.VoxImplant.rentPhone.phoneNumberInstallationPrice > 0)
		priceHtml = BX.message('VI_CONFIG_RENT_INSTALLATION_PRICE') + ': <strong>' + BX.message('VI_CONFIG_RENT_FEE_'+BX.VoxImplant.rentPhone.phoneNumberCurrency).replace('#MONEY#', BX.VoxImplant.rentPhone.phoneNumberInstallationPrice) + '</strong><br>';

	if(BX.VoxImplant.rentPhone.phoneNumberMonthPrice > 0)
		priceHtml = priceHtml + BX.message('VI_CONFIG_RENT_MONTH_PRICE') + ': <strong>' + BX.message('VI_CONFIG_RENT_FEE_'+BX.VoxImplant.rentPhone.phoneNumberCurrency).replace('#MONEY#', BX.VoxImplant.rentPhone.phoneNumberMonthPrice) + '</strong>';

	var specialHeader = null;
	var specialHeaderText = '';
	if (BX.VoxImplant.rentPhone.currentCountry == 'RU')
	{
		if (BX.VoxImplant.rentPhone.currentCountryCategory == 'TOLLFREE')
		{
			specialHeaderText = BX.message('VI_CONFIG_RENT_RU_TOLLFREE_2');
			specialHeaderText = specialHeaderText.replace('#LINK1_START#', '<a href="'+BX.message('VI_CONFIG_RENT_RU_TOLLFREE_LINK')+'" target="_blank">');
			specialHeaderText = specialHeaderText.replace('#LINK1_END#', '</a>');
			specialHeaderText = specialHeaderText.replace('#LINK2_START#', '<a href="'+BX.message('VI_CONFIG_RENT_TARIFF_LINK')+'" target="_blank">');
			specialHeaderText = specialHeaderText.replace('#LINK2_END#', '</a>');
		}
		else if (BX.VoxImplant.rentPhone.currentCountryCategory == 'TOLLFREE804')
		{
			specialHeaderText = BX.message('VI_CONFIG_RENT_RU_TOLLFREE804_2');
			specialHeaderText = specialHeaderText.replace('#LINK1_START#', '<a href="'+BX.message('VI_CONFIG_RENT_RU_TOLLFREE804_LINK')+'" target="_blank">');
			specialHeaderText = specialHeaderText.replace('#LINK1_END#', '</a>');
			specialHeaderText = specialHeaderText.replace('#LINK2_START#', '<a href="'+BX.message('VI_CONFIG_RENT_TARIFF_LINK')+'" target="_blank">');
			specialHeaderText = specialHeaderText.replace('#LINK2_END#', '</a>');
		}
	}
	else if (BX.VoxImplant.rentPhone.currentCountryCategory == 'TOLLFREE')
	{
		specialHeaderText = BX.message('VI_CONFIG_RENT_TEXT_TOLLFREE_2');
		specialHeaderText = specialHeaderText.replace('#LINK1_START#', '<a href="'+BX.message('VI_CONFIG_RENT_TARIFF_LINK')+'" target="_blank">');
		specialHeaderText = specialHeaderText.replace('#LINK1_END#', '</a>');
	}

	var requiredVerification = false;
	if (BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry]['CATEGORIES'][BX.VoxImplant.rentPhone.currentCountryCategory].REQUIRED_VERIFICATION)
	{
		var requiredVerificationText = BX.message('VI_CONFIG_RENT_TEXT_REQUIRE_VERIFICATION');
		requiredVerificationText = requiredVerificationText.replace('#URL_START#', '<a href="'+BX.VoxImplant.rentPhone.publicFolder+'configs.php" target="_blank">');
		requiredVerificationText = requiredVerificationText.replace('#URL_END#', '</a>');

		if (specialHeaderText)
		{
			specialHeaderText = specialHeaderText+'<br><br>'+requiredVerificationText;
		}
		else
		{
			specialHeaderText = requiredVerificationText;
		}
		requiredVerification = true;
	}


	if (specialHeaderText != '')
	{
		specialHeader = BX.create("div", {children:[
			BX.create("div", {props : { className : "tel-set-list-special-header" }, html: specialHeaderText})
		]});
	}

	if (   BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry]
		&& BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory]
		&& BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState]
		&& BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][BX.VoxImplant.rentPhone.currentCountryRegion])
	{
		var phoneList = [];
		var phoneListObj = BX.VoxImplant.rentPhone.countryRegionNumbers[BX.VoxImplant.rentPhone.currentCountry][BX.VoxImplant.rentPhone.currentCountryCategory][BX.VoxImplant.rentPhone.currentCountryState][BX.VoxImplant.rentPhone.currentCountryRegion];

		for (var phoneId in phoneListObj)
		{
			BX.VoxImplant.rentPhone.phoneNumberFullPrice = parseFloat(phoneListObj[phoneId].FULL_PRICE);
			BX.VoxImplant.rentPhone.phoneNumberMonthPrice = parseFloat(phoneListObj[phoneId].MONTH_PRICE);
			BX.VoxImplant.rentPhone.phoneNumberInstallationPrice = parseFloat(phoneListObj[phoneId].INSTALLATION_PRICE);
			BX.VoxImplant.rentPhone.phoneNumberCurrency = phoneListObj[phoneId].CURRENCY;

			priceHtml = '';
			if(BX.VoxImplant.rentPhone.phoneNumberInstallationPrice > 0)
				priceHtml = BX.message('VI_CONFIG_RENT_INSTALLATION_PRICE') + ': <strong>' + BX.message('VI_CONFIG_RENT_FEE_'+BX.VoxImplant.rentPhone.phoneNumberCurrency).replace('#MONEY#', BX.VoxImplant.rentPhone.phoneNumberInstallationPrice) + '</strong><br>';

			if(BX.VoxImplant.rentPhone.phoneNumberMonthPrice > 0)
				priceHtml = priceHtml + BX.message('VI_CONFIG_RENT_MONTH_PRICE') + ': <strong>' + BX.message('VI_CONFIG_RENT_FEE_'+BX.VoxImplant.rentPhone.phoneNumberCurrency).replace('#MONEY#', BX.VoxImplant.rentPhone.phoneNumberMonthPrice) + '</strong>';

			var phoneName = '+'+phoneId;
			if (BX.VoxImplant.rentPhone.currentCountry == 'RU' && (BX.VoxImplant.rentPhone.currentCountryCategory == 'TOLLFREE' || BX.VoxImplant.rentPhone.currentCountryCategory == 'TOLLFREE804'))
			{
				phoneName = '8'+phoneName.substr(2);
			}

			phoneList.push(
				BX.create("span", {
					props : { className : "tel-set-list-item" },
					children: [
						BX.create("input", {
							attrs: {
								id: 'phone'+phoneId, name: 'tel-set-list-item', value: phoneId, type: 'radio',
								'data-country-code': phoneListObj[phoneId].COUNTRY_CODE,
								'data-region-id': phoneListObj[phoneId].REGION_ID,
								'data-phone-number': phoneListObj[phoneId].PHONE_NUMBER
							},
							props : { className : "tel-set-list-item-radio" },
							events:
							{
								click : function(e)
								{
									BX.VoxImplant.rentPhone.currentCountry = this.getAttribute('data-country-code');
									BX.VoxImplant.rentPhone.currentCountryRegion = this.getAttribute('data-region-id');
									BX.VoxImplant.rentPhone.currentNumber = this.getAttribute('data-phone-number');
								},
								change : function(e)
								{
									BX.VoxImplant.rentPhone.currentCountry = this.getAttribute('data-country-code');
									BX.VoxImplant.rentPhone.currentCountryRegion = this.getAttribute('data-region-id');
									BX.VoxImplant.rentPhone.currentNumber = this.getAttribute('data-phone-number');
								}
							}
						}),
						BX.create("label", {attrs: {'for': 'phone'+phoneId}, props : { className : "tel-set-list-item-num" }, html: phoneName})
					]
				})
			);
		}

		var phoneBox = BX.create("div", {
			children: [
				BX.create("div", {props : { className : "tel-set-list-nums" },children:[
					specialHeader,
					BX.create("div", {props : { className : "tel-set-list-nums-title" }, html: BX.message('VI_CONFIG_RENT_LIST_PHONES')}),
					BX.create("div", {props : { className : "tel-set-separate" }}),
					phoneList.length >0 ? BX.create("div", {props : { className : "tel-set-list-nums-wrap" }, children: phoneList}): BX.create("div", {props : { className : "tel-set-list-nums-title" }, attrs: { style: 'margin:0'}, html: BX.message('VI_CONFIG_RENT_NO_PHONES') })
				]}),
				BX.create("div", {props : { className : "tel-set-separate" }}),
				BX.create("div", {attrs: { style: 'line-height: 33px'}, props : { className : "tel-set-amount-text" }, html: phoneList.length >0 ? priceHtml : ''}),
				BX.create("div", {props : { className : "tel-set-amount-block" }, children:[
					BX.create("div", {
						props : { className : "webform-button webform-button-create" },
						html: '<span class="webform-button-left"></span><span class="webform-button-text">'+(requiredVerification? BX.message('VI_CONFIG_RESERVE_BTN'): BX.message('VI_CONFIG_RENT_BTN'))+'</span><span class="webform-button-right"></span>',
						events:
						{
							click : function(e)
							{
								BX.VoxImplant.rentPhone.attachPhone();
							}
						}
					})
				]})
			]
		});
	}
	else if (BX.VoxImplant.rentPhone.countryRegionNumberCount > 0)
	{
		var phoneBox = BX.create("div", {
			children: [
				specialHeader,
				BX.create("div", {props : { className : "tel-set-separate" }}),
				BX.create("div", {props : { className : "tel-set-list-nums-title" }, html: BX.message('VI_CONFIG_RENT_WITHOUT_CHOICE')}),
				BX.create("div", {props : { className : "tel-set-separate" }}),
				BX.create("div", {attrs: { style: 'line-height: 33px'}, props : { className : "tel-set-amount-text" }, html: priceHtml}),
				BX.create("div", {props : { className : "tel-set-amount-block" }, children:[
					BX.create("div", {
						props : { className : "webform-button webform-button-create" },
						html: '<span class="webform-button-left"></span><span class="webform-button-text">'+(requiredVerification? BX.message('VI_CONFIG_RESERVE_BTN'): BX.message('VI_CONFIG_RENT_BTN'))+'</span><span class="webform-button-right"></span>',
						events:
						{
							click : function(e)
							{
								BX.VoxImplant.rentPhone.attachPhone();
							}
						}
					})
				]})
			]
		});
	}
	else
	{
		var phoneBox = BX.create("div", {
			children: [
				BX.create("div", {props : { className : "tel-set-list-nums" },children:[
					specialHeader,
					BX.create("div", {props : { className : "tel-set-list-nums-title" }, html: BX.message('VI_CONFIG_RENT_LIST_PHONES')}),
					BX.create("div", {props : { className : "tel-set-separate" }}),
					BX.create("div", {props : { className : "tel-set-list-nums-title" }, attrs: { style: 'margin:0'}, html: BX.message('VI_CONFIG_RENT_NO_PHONES') })
				]}),
				BX.create("div", {props : { className : "tel-set-separate" }})
			]
		});
	}

	BX.VoxImplant.rentPhone.verifiedAddressesPlaceholder.innerHTML = '';
	BX.VoxImplant.rentPhone.numbersPlaceholder.innerHTML = '';
	BX.VoxImplant.rentPhone.numbersPlaceholder.appendChild(phoneBox);
};

BX.VoxImplant.rentPhone.attachPhone = function(type)
{
	if (BX.VoxImplant.rentPhone.blockAjax)
		return true;

	if (!(BX.VoxImplant.rentPhone.currentCountry && BX.VoxImplant.rentPhone.currentCountryCategory))
	{
		alert(BX.message('VI_CONFIG_RENT_AJAX_ERROR_2'));
		return false;
	}
	if (!(BX.VoxImplant.rentPhone.currentCountry && BX.VoxImplant.rentPhone.currentCountryRegion))
	{
		alert(BX.message('VI_CONFIG_RENT_AJAX_NUMBER'));
		return false;
	}
	if (BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry].CAN_LIST_PHONES && BX.VoxImplant.rentPhone.currentNumber == "")
	{
		alert(BX.message('VI_CONFIG_RENT_AJAX_NUMBER'));
		return false;
	}

	var currentRegionParameters = BX.VoxImplant.rentPhone.getCurrentRegionParameters();
	if (currentRegionParameters && currentRegionParameters.IS_NEED_REGULATION_ADDRESS && !BX.VoxImplant.rentPhone.currentAddressVerification)
	{
		alert(BX.message('VI_CONFIG_SELECT_ADDRESS_ERROR'));
		return false;
	}

	var priceLabel = BX.message('VI_CONFIG_RENT_FEE_'+BX.VoxImplant.rentPhone.phoneNumberCurrency).replace('#MONEY#', BX.VoxImplant.rentPhone.phoneNumberMonthPrice);
	if (!confirm(BX.message('VI_CONFIG_RENT_WARN').replace('#MONEY#', priceLabel)))
	{
		return false;
	}

	var count = 1;
	var number = "";
	if (BX.VoxImplant.rentPhone.currentNumber != "")
	{
		count = 0;
		number = BX.VoxImplant.rentPhone.currentNumber;
	}

	BX.showWait(BX.VoxImplant.rentPhone.containerNode);
	BX.VoxImplant.rentPhone.blockAjax = true;
	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.rent/ajax.php?VI_RENT_NUMBER',
		method: 'POST',
		dataType: 'json',
		timeout: 60,
		data: {
			'VI_RENT_NUMBER': 'Y',
			'PRE_MONEY_CHECK': BX.VoxImplant.rentPhone.phoneNumberFullPrice,
			'CURRENT_NUMBER': number,
			'REGION_ID': BX.VoxImplant.rentPhone.currentCountryRegion,
			'COUNTRY_CODE': BX.VoxImplant.rentPhone.currentCountry,
			'COUNTRY_STATE': BX.VoxImplant.rentPhone.currentCountryState,
			'COUNTRY_CATEGORY': BX.VoxImplant.rentPhone.currentCountryCategory,
			'ADDRESS_VERIFICATION': BX.VoxImplant.rentPhone.currentAddressVerification,
			'VI_AJAX_CALL' : 'Y',
			'sessid': BX.bitrix_sessid()
		},
		onsuccess: BX.delegate(function(data)
		{
			BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
			BX.VoxImplant.rentPhone.blockAjax = false;
			if (data.ERROR == '')
			{
				location.href = BX.VoxImplant.rentPhone.publicFolder+'edit.php?ID='+data.RESULT.ID+'&NEW=Y'
			}
			else if (data.ERROR == 'ATTACHED')
			{
				alert(BX.message('VI_CONFIG_RENT_WAS_ATTACHED'));
			}
			else if (data.ERROR == 'NOT_VERIFIED')
			{
				alert(BX.message('VI_CONFIG_RENT_NOT_VERIFIED'));
			}
			else if (data.ERROR == 'NO_MONEY')
			{
				alert(BX.message('VI_CONFIG_RENT_MONEY_LOW'));
			}
			else
			{
				alert(BX.message('VI_CONFIG_RENT_AJAX_ERROR_2'));
			}
		}, this),
		onfailure: function(){
			BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
			BX.VoxImplant.rentPhone.blockAjax = false;
		}
	});
};

BX.VoxImplant.rentPhone.unlinkPhone = function(id)
{
	if (BX.VoxImplant.rentPhone.blockAjax)
		return true;

	if (!confirm(BX.message('VI_CONFIG_RENT_PHONE_DELETE_CONFIRM')))
	{
		return false;
	}
	BX.showWait(BX.VoxImplant.rentPhone.containerNode);

	BX.VoxImplant.rentPhone.blockAjax = true;
	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.rent/ajax.php?VI_UNLINK_NUMBER',
		method: 'POST',
		dataType: 'json',
		timeout: 60,
		data: {'VI_UNLINK_NUMBER': 'Y', 'NUMBER_ID': id, 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
		onsuccess: BX.delegate(function(data)
		{
			BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
			BX.VoxImplant.rentPhone.blockAjax = false;
			if (data.ERROR == '')
			{
				BX('phone-confing-unlink-'+id).style.display = 'none';
				BX('phone-confing-link-'+id).style.display = 'inline-block';
			}
		}, this),
		onfailure: function(){
			BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
			BX.VoxImplant.rentPhone.blockAjax = false;
		}
	});
};

BX.VoxImplant.rentPhone.cancelUnlinkPhone = function(id)
{
	if (BX.VoxImplant.rentPhone.blockAjax)
		return true;

	BX.showWait(BX.VoxImplant.rentPhone.containerNode);

	BX.VoxImplant.rentPhone.blockAjax = true;
	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.rent/ajax.php?VI_CANCEL_UNLINK_NUMBER',
		method: 'POST',
		dataType: 'json',
		timeout: 60,
		data: {'VI_CANCEL_UNLINK_NUMBER': 'Y', 'NUMBER_ID': id, 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
		onsuccess: BX.delegate(function(data)
		{
			BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
			BX.VoxImplant.rentPhone.blockAjax = false;
			if (data.ERROR == '')
			{
				BX('phone-confing-unlink-'+id).style.display = 'inline-block';
				BX('phone-confing-link-'+id).style.display = 'none';
			}
		}, this),
		onfailure: function(){
			BX.closeWait(BX.VoxImplant.rentPhone.containerNode);
			BX.VoxImplant.rentPhone.blockAjax = false;
		}
	});
};

BX.VoxImplant.rentPhone.setPriceFields = function()
{
	BX.VoxImplant.rentPhone.phoneNumberFullPrice = 0;
	if (parseFloat(BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry]['CATEGORIES'][BX.VoxImplant.rentPhone.currentCountryCategory].FULL_PRICE) > 0)
		BX.VoxImplant.rentPhone.phoneNumberFullPrice = parseFloat(BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry]['CATEGORIES'][BX.VoxImplant.rentPhone.currentCountryCategory].FULL_PRICE);

	BX.VoxImplant.rentPhone.phoneNumberMonthPrice = 0;
	if (parseFloat(BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry]['CATEGORIES'][BX.VoxImplant.rentPhone.currentCountryCategory].MONTH_PRICE) > 0)
		BX.VoxImplant.rentPhone.phoneNumberMonthPrice = parseFloat(BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry]['CATEGORIES'][BX.VoxImplant.rentPhone.currentCountryCategory].MONTH_PRICE);

	BX.VoxImplant.rentPhone.phoneNumberInstallationPrice = 0;
	if (parseFloat(BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry]['CATEGORIES'][BX.VoxImplant.rentPhone.currentCountryCategory].INSTALLATION_PRICE) > 0)
		BX.VoxImplant.rentPhone.phoneNumberInstallationPrice = parseFloat(BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry]['CATEGORIES'][BX.VoxImplant.rentPhone.currentCountryCategory].INSTALLATION_PRICE);

	BX.VoxImplant.rentPhone.phoneNumberCurrency = 'RUR';
	if (BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry]['CATEGORIES'][BX.VoxImplant.rentPhone.currentCountryCategory].CURRENCY)
		BX.VoxImplant.rentPhone.phoneNumberCurrency = BX.VoxImplant.rentPhone.country[BX.VoxImplant.rentPhone.currentCountry]['CATEGORIES'][BX.VoxImplant.rentPhone.currentCountryCategory].CURRENCY;
};

BX.VoxImplant.rentPhone.drawOnPlaceholder = function(children)
{
	BX.VoxImplant.rentPhone.placeholder.innerHTML = '';
	BX.adjust(BX.VoxImplant.rentPhone.placeholder, {children: children});
};

BX.VoxImplant.rentPhone.formatVerifiedAddress = function(address)
{
	var result = '';
	var field;
	var addressFields = ['ZIP_CODE', 'COUNTRY', 'CITY', 'STREET', 'BUILDING_NUMBER'];

	if(address.BUILDING_LETTER)
		address.BUILDING_NUMBER += '-' + address.BUILDING_LETTER;

	var first = true;
	addressFields.forEach(function(field)
	{
		if(address[field])
		{
			result += (first ? '' : ', ') + address[field];
			first = false;
		}
	});

	return result;
};