<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
$APPLICATION->AddHeadScript('/bitrix/js/crm/crm.js');

$readOnly = !isset($arResult['READ_ONLY']) || $arResult['READ_ONLY']; //Only READ_ONLY access by defaul
$containerID = $arResult['PREFIX'].'_container';
?>
<div id="<?=$containerID?>" class="crm-product-row-container">
	<? $productAddBtnID = $arResult['PREFIX'].'_product_button';  ?>
	<? $buttonContainerID = $arResult['PREFIX'].'_product_button_container';  ?>
	<?if(!$readOnly):?>
	<ul id="<?=$buttonContainerID?>" class="crm-view-actions">
		<li id="<?=$productAddBtnID?>">
			<i></i><span><?=htmlspecialcharsbx(GetMessage('CRM_FF_CHOISE'))?></span>
		</li>
	</ul>
	<?endif;?>
	<? $productContainerID = $arResult['PREFIX'].'_product_table';  ?>
	<table id="<?= $productContainerID ?>" class="crm-view-table <?= $arResult['CONTAINER_CLASS'] ?>" style="<?= count($arResult['PRODUCT_ROWS']) == 0 ? 'display:none;' : '' ?>">
		<thead>
			<tr>
				<td>&nbsp;</td>
				<td><?= htmlspecialcharsbx(GetMessage('CRM_PRODUCT_ROW_COL_TTL_NAME')) ?></td>
				<td><?= htmlspecialcharsbx(GetMessage('CRM_PRODUCT_ROW_COL_TTL_QUANTITY')) ?></td>
				<td><?= htmlspecialcharsbx(GetMessage('CRM_PRODUCT_ROW_COL_TTL_PRICE')) ?></td>
				<td class="crm-view-table-column-edit">&nbsp;</td>
			</tr>
		</thead>
		<tbody>
		<?
		$productEditorCfg = array(
			'ownerType' => $arResult['OWNER_TYPE'],
			'ownerID' => $arResult['OWNER_ID'],
			'currencyID' => $arResult['CURRENCY_ID'],
			'currencyFormat' => $arResult['CURRENCY_FORMAT'],
			'formID' => $arResult['FORM_ID'],
			'containerID' => $containerID,
			'buttonContainerID' => $buttonContainerID,
			'productContainerID' => $productContainerID,
			'productAddBtnID' => $productAddBtnID,
			'dataFieldName' => $arResult['PRODUCT_DATA_FIELD_NAME'],
			'readOnly' => $readOnly ? 'Y' : 'N',
			'savingMode' => $arResult['SAVING_MODE'] == 'ONCHANGE' ? 2 : 1,
			'productClass' => isset($arResult['ROW_CLASS'][0]) ? $arResult['ROW_CLASS'] : '',
			'items' => array()
		);

		$productClass = $productEditorCfg['productClass'] = isset($arResult['ROW_CLASS']) ? $arResult['ROW_CLASS'] : '';

		for($i = 0, $count = count($arResult['PRODUCT_ROWS']); $i < $count; $i++)
		{
			$row = $arResult['PRODUCT_ROWS'][$i];
			$rowID = $arResult['PREFIX'].'_product_row_'.strval($i);

			$productEditorCfg['items'][] =
				array(
					'rowID' => $rowID,
					'settings' => array(
						'ID' => $row['ID'],
						'PRODUCT_ID' => $row['PRODUCT_ID'],
						'PRODUCT_NAME' => $row['PRODUCT_NAME'],
						'QUANTITY' => intval($row['QUANTITY']),
						'PRICE' => doubleval($row['PRICE'])
					)
				);

			$rowClass = $productClass;
			if(($i + 1) % 2 === 0)
			{
				if($rowClass !== '')
				{
					$rowClass .= ' ';
				}

				$rowClass .= 'crm-product-row-even';
			}
			?>

			<tr id="<?=$rowID?>"<?=$rowClass !== '' ? ' class="'.htmlspecialcharsbx($rowClass).'"' : ''?>>
				<td>
				<?if(!$readOnly):?>
					<span class="crm-view-table-column-delete"></span>
				<?endif;?>
				</td>
				<td class="crm-product-column-name">
					<?
					if ($row['PRODUCT_NAME'] == "OrderDelivery")
						echo htmlspecialcharsbx(GetMessage("CRM_PRODUCT_ROW_DELIVERY"));
					elseif ($row['PRODUCT_NAME'] == "OrderDiscount")
						echo htmlspecialcharsbx(GetMessage("CRM_PRODUCT_ROW_DISCOUNT"));
					else
						echo htmlspecialcharsbx($row['PRODUCT_NAME']);
					?>
				</td>
				<td class="crm-product-column-qty">
					<span class="crm-product-field crm-product-field-input">
						<span class="crm-product-field-text"><?= sprintf('%d', $row['QUANTITY']) ?></span>
					</span>
				</td>
				<td class="crm-product-column-price">
					<span class="crm-product-field crm-product-field-input">
						<span class="crm-product-field-text"><?= sprintf('%.2f', $row['PRICE']) ?></span>
					</span>
				</td>
				<td class="crm-view-table-column-edit">
				<?if(!$readOnly):?>
					<span class="crm-product-action-edit"></span>
				<?endif;?>
				</td>
			</tr>
		<?}?>
		</tbody>
	</table>

	<div id="<?= $arResult['PREFIX'] ?>_product_sum_total_container" class="crm-view-table-total">
		<div class="crm-view-table-total-inner">
			<?= htmlspecialcharsbx(GetMessage('CRM_PRODUCT_SUM_TOTAL')) ?>:
			<? $productEditorCfg['SUM_TOTAL_ID'] = $arResult['PREFIX'].'_sum_total';  ?>
			<strong id="<?= htmlspecialcharsbx($productEditorCfg['SUM_TOTAL_ID']) ?>" class="crm-view-table-total-value"><?= CCrmCurrency::MoneyToString($arResult['SUM_TOTAL'], $arResult['CURRENCY_ID']) ?></strong>
			<input type="hidden" name="<?= htmlspecialcharsbx($arResult['PRODUCT_DATA_FIELD_NAME']) ?>" value="" />
		</div>
	</div>
</div>
<script type="text/javascript">
if (!typeof(BX.CrmProductEditor) != 'undefined')
{
	BX.CrmProductSavingMode =
	{
		onsubmit: 1,
		onchange: 2
	};

	BX.CrmProductEditor = function ()
	{
		this._id = '';
		this._settings = {};
		this._currencyId = '';
		this._currencyFormat = '#';
		this._products = [];
		this._dlgId = '';
		this._savingMode = BX.CrmProductSavingMode.onsubmit;
	};

	BX.CrmProductEditor.prototype =
	{
		initialize:function (id, config)
		{
			this._id = id;
			this._settings = config ? config : {};

			var items = typeof(this._settings['items']) != 'undefined' ? this._settings['items'] : [];
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];
				var rowID = item['rowID'];

				var settings = item['settings'];
				settings['readOnly'] = this.getSetting('readOnly', 'N');

				this._products.push(BX.CrmProduct.create(settings, document.getElementById(rowID), this));
			}

			this._ajustStyles();

			var addBtn = document.getElementById(this.getSetting('productAddBtnID', ''));
			if(addBtn)
			{
				BX.bind(
					addBtn,
					"click",
					BX.delegate(this.handleAddProductButtonClick, this)
				);
			}

			this._currencyId = this.getSetting('currencyID', '');
			this._currencyFormat = this.getSetting('currencyFormat', '#');

			var form = this.getForm();
			if(form)
			{
				BX.bind(form, 'submit', BX.delegate(this.handleFormSubmit, this));
			}

			//for new entities saving mode always is onsubmit
			this._savingMode = this.getSetting('ownerID', 0) == 0
				? BX.CrmProductSavingMode.onsubmit
				: parseInt(this.getSetting('savingMode', BX.CrmProductSavingMode.onsubmit));
		},
		isReadOnly: function()
		{
			return this.getSetting('readOnly', 'N') == 'Y';
		},
		setReadOnly: function(readOnly)
		{
			this.setSetting('readOnly', readOnly ? 'Y' : 'N');
			var buttonContainer = BX(this.getSetting('buttonContainerID'));
			if(buttonContainer)
			{
				buttonContainer.style.display = readOnly  ? 'none' : '';
			}

			for(var i = 0; i < this._products.length; i++)
			{
				this._products[i].setReadOnly(readOnly);
			}
		},
		getForm: function()
		{
			var formID = this.getSetting('formID', '');
			return BX.type.isNotEmptyString(formID) ? BX('form_' + formID) : null;
		},
		getTable: function()
		{
			var tableID = this.getSetting('productContainerID', '');
			return BX.type.isNotEmptyString(tableID) ? BX(tableID) : null;
		},
		getCurrencyElement: function()
		{
			var form = this.getForm();
			return form ? BX.findChild(form, { 'tag':'select', 'attr':{ 'name': 'CURRENCY_ID' } }, true, false) : null;
		},
		getExchRateElement: function()
		{
			var form = this.getForm();
			return form ? BX.findChild(form, { 'tag':'input', 'attr':{ 'name': 'EXCH_RATE' } }, true, false) : null;
		},
		getSetting:function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting:function(name, value)
		{
			this._settings[name] = value;
		},
		handleBeforeSearch: function(data)
		{
			//{ 'entityType','postData'};
			if(data['entityType'] == 'product')
			{
				var currency = this.getCurrencyElement();
				if(currency)
				{
					data['postData']['CURRENCY_ID'] = currency.value;
				}

				var exchRate = this.getExchRateElement();
				if(exchRate)
				{
					var s = exchRate.value;
					data['postData']['EXCH_RATE'] = BX.type.isNotEmptyString(s) ? parseFloat(s) : 0.0;
				}
			}
		},
		handleFormSubmit: function(e)
		{
			if(this._savingMode != BX.CrmProductSavingMode.onsubmit)
			{
				return;
			}

			var form = this.getForm();
			if(!form)
			{
				return;
			}

			var field = BX.findChild(form, {'tag':'input', 'attr':{'type':'hidden', 'name': this.getSetting('dataFieldName', 'PRODUCT_ROW_DATA')}}, true, false);
			if(!field)
			{
				return;
			}

			var json = '';
			if(this._products.length > 0)
			{
				for(var i = 0; i < this._products.length; i++)
				{
					json += (json.length > 0 ? ', ' : '') + this._products[i].toJson();
				}

				json = '[' + json + ']';
			}

			field.value = json;
		},
		handleAddProductButtonClick: function(e)
		{
			if(BX.type.isNotEmptyString(this._dlgId))
			{
				obCrm[this._dlgId].Open();
			}
		},
		handleProductAddition: function(data)
		{
			var item = typeof(data['product']) != 'undefined' && typeof(data['product'][0]) != 'undefined' ? data['product'][0] : null;
			if(!item)
			{
				return;
			}

			var table = this.getTable();
			var row = table.tBodies[0].insertRow(-1);
			//row.className = 'crm-product-row';
			var className = this.getSetting('productClass', '');
			if(className.length > 0)
			{
				row.className = className;
			}

			var settings =
			{
				'PRODUCT_ID': item['id'],
				'PRODUCT_NAME': item['title'],
				'QUANTITY': 1,
				'PRICE': typeof(item['customData']) != 'undefined' && typeof(item['customData']['price']) != 'undefined' ? parseFloat(item['customData']['price']) : 1.0
			};

			var product = BX.CrmProduct.create(settings, row, this);
			this._products.push(product);
			product.layout();

			//switch all products to readonly mode before sum total refreshing
			for(var i = 0; i < this._products.length; i++)
			{
				var curProduct = this._products[i];
				if(!curProduct.isViewMode())
				{
					curProduct.toggleMode();
				}
			}

			this._ajustStyles();

			if(table.style.display == 'none')
			{
				table.style.display = '';
			}

			BX.onCustomEvent(this, 'productAdd', [ { "product": product } ]);
			this.refreshSumTotal();

			if(this._savingMode == BX.CrmProductSavingMode.onchange)
			{
				var self = this;
				BX.ajax(
					{
						'url': '/bitrix/components/bitrix/crm.product_row.list/ajax.php?<?= bitrix_sessid_get() ?>',
						'method': 'POST',
						'dataType': 'json',
						'data':
						{
							'MODE': 'ADD_PRODUCT',
							'OWNER_TYPE': this.getSetting('ownerType', ''),
							'OWNER_ID': this.getSetting('ownerID', 0),
							'PRODUCT_ID': product.getProductId(),
							'QUANTITY': product.getQuantity(),
							'PRICE': product.getPrice(),
							'CURRENCY_ID': this._currencyId
						},
						onsuccess: function(data)
						{
							if(self._processAjaxError(data))
							{
								return;
							}
							if(typeof(data['PRODUCT_ROW']) != 'undefined')
							{
								var settings = data['PRODUCT_ROW'];
								if(typeof(settings['ID']) != 'undefined')
								{
									product.setId(settings['ID']);
								}
							}
						},
						onfailure: function(data)
						{
							self._processAjaxError(data);
						}
					});
			}
		},
		handleProductDeletion: function(product)
		{
			if(!window.confirm('<?= CUtil::addslashes(GetMessage('CRM_PRODUCT_ROW_DELETION_CONFIRM')) ?>'))
			{
				return false;
			}

			if(this._savingMode == BX.CrmProductSavingMode.onchange)
			{
				var self = this;
				BX.ajax(
					{
						'url': '/bitrix/components/bitrix/crm.product_row.list/ajax.php?<?= bitrix_sessid_get() ?>',
						'method': 'POST',
						'dataType': 'json',
						'data':
						{
							'MODE': 'REMOVE_PRODUCT',
							'OWNER_TYPE': this.getSetting('ownerType', ''),
							'OWNER_ID': this.getSetting('ownerID', 0),
							'ID': product.getId()
						},
						onsuccess: function(data)
						{
							if(!self._processAjaxError(data))
							{
								self._deleteProduct(product);
								self._ajustStyles();
							}
						},
						onfailure: function(data)
						{
							self._processAjaxError(data);
						}
					});
			}
			else
			{
				this._deleteProduct(product);
				this._ajustStyles();
			}

			return true;
		},
		_ajustStyles: function()
		{
			for(var i = 0; i < this._products.length; i++)
			{
				var curRow = this._products[i].getContainer();
				if((i + 1) % 2 === 0)
				{
					BX.addClass(curRow, 'crm-product-row-even');
				}
				else
				{
					BX.removeClass(curRow, 'crm-product-row-even');
				}
			}
		},
		_deleteProduct: function(product)
		{
			for(var i = 0; i < this._products.length; i++)
			{
				if(this._products[i] == product)
				{
					this._products.splice(i, 1);
					break;
				}
			}

			if(this._products.length == 0)
			{
				var container = this.getTable();
				if(container.style.display !== 'none')
				{
					container.style.display = 'none';
				}
			}

			BX.onCustomEvent(this, 'productRemove', [ { "product": product } ]);
			this.refreshSumTotal();
		},
		onBeginProductEdit: function(product)
		{
			for(var i = 0; i < this._products.length; i++)
			{
				var curProduct = this._products[i];
				if(curProduct === product)
				{
					continue;
				}

				if(!curProduct.isViewMode())
				{
					curProduct.toggleMode();
				}
			}

			this.refreshSumTotal();
		},
		onCompleteProductEdit: function(product)
		{
			this.refreshSumTotal();

			if(this._savingMode !== BX.CrmProductSavingMode.onchange)
			{
				return;
			}

			var self = this;
			BX.ajax(
				{
					'url': '/bitrix/components/bitrix/crm.product_row.list/ajax.php?<?= bitrix_sessid_get() ?>',
					'method': 'POST',
					'dataType': 'json',
					'data':
					{
						'MODE': 'UPDATE_PRODUCT',
						'OWNER_TYPE': this.getSetting('ownerType', ''),
						'OWNER_ID': this.getSetting('ownerID', 0),
						'ID': product.getId(),
						'PRODUCT_ID': product.getProductId(),
						'QUANTITY': product.getQuantity(),
						'PRICE': product.getPrice(),
						'CURRENCY_ID': this._currencyId
					},
					onsuccess: function(data)
					{
						self._processAjaxError(data)
					},
					onfailure: function(data)
					{
						self._processAjaxError(data)
					}
				});
		},
		refreshSumTotal: function()
		{
			var el = BX(this.getSetting('SUM_TOTAL_ID', 'sum_total'));

			var ttl = 0.0;
			for(var i = 0; i < this._products.length; i++)
			{
				var curProduct = this._products[i];
				curProduct.saveSettings();
				ttl += curProduct.getSetting('QUANTITY', 0) * curProduct.getSetting('PRICE', 0.0);
			}

			ttl = ttl.toFixed(2);
			el.innerHTML = this._currencyFormat.replace(/#/g, ttl);
			BX.onCustomEvent(this, 'sumTotalChange', [ttl]);
		},
		registerProductDialogId: function(dlgId)
		{
			this._dlgId = dlgId;
		},
		getCurrencyId: function()
		{
			return this._currencyId;
		},
		setCurrencyId: function(currencyId)
		{
			if(this._currencyId === currencyId)
			{
				return;
			}

			this.calculateProductPrices(currencyId);
		},
		getProductCount: function()
		{
			return this._products.length;
		},
		convertMoney: function(srcSum, srcCurrencyId, dstCurrencyId, callback)
		{
			var self = this;
			BX.ajax(
				{
					'url': '/bitrix/components/bitrix/crm.product_row.list/ajax.php?<?= bitrix_sessid_get() ?>',
					'method': 'POST',
					'dataType': 'json',
					'data':
					{
						'MODE' : 'CONVERT_MONEY',
						'OWNER_TYPE': this.getSetting('ownerType', ''),
						'OWNER_ID': this.getSetting('ownerID', 0),
						'DATA':
						{
							'SRC_SUM': srcSum,
							'SRC_CURRENCY_ID': srcCurrencyId,
							'DST_CURRENCY_ID': dstCurrencyId
						}
					},
					onsuccess: function(data)
					{
						if(data['SUM'])
						{
							if(self._processAjaxError(data))
							{
								return;
							}

							try
							{
								callback(parseFloat(data['SUM']));
							}
							catch(ex)
							{
							}
						}
					},
					onfailure: function(data)
					{
						self._processAjaxError(data)
					}
				});
		},
		calculateProductPrices: function(dstCurrencyId)
		{
			var prevId = this._currencyId;
			this._currencyId = dstCurrencyId;

			var exchRate = this.getExchRateElement();

			var srcData = [];
			for(var i = 0; i < this._products.length; i++)
			{
				var p = this._products[i];
				srcData.push({ 'ID':p.getSetting('PRODUCT_ID', '0'), 'PRICE':p.getSetting('PRICE', '1') });
			}

			var self = this;
			BX.ajax(
			{
				'url': '/bitrix/components/bitrix/crm.product_row.list/ajax.php?<?= bitrix_sessid_get() ?>',
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'MODE' : 'CALC_PRODUCT_PRICES',
					'OWNER_TYPE': this.getSetting('ownerType', ''),
					'OWNER_ID': this.getSetting('ownerID', 0),
					'DATA':
					{
						'SRC_CURRENCY_ID': prevId,
						'SRC_EXCH_RATE': exchRate ? parseFloat(exchRate.value) : 0,
						'DST_CURRENCY_ID': dstCurrencyId,
						'PRODUCTS': srcData
					}
				},
				onsuccess: function(data)
				{
					//if(typeof(data['CURRENCY_ID'])){
					//	currency.value = data['CURRENCY_ID'];
					//}

					if(typeof(data['EXCH_RATE']) && exchRate)
					{
						exchRate.value = parseFloat(data['EXCH_RATE']);
					}

					if(data['PRODUCS'])
					{
						if(self._processAjaxError(data))
						{
							return;
						}

						for(var i = 0; i < data['PRODUCS'].length; i++)
						{
							var p = data['PRODUCS'][i];
							var s = self._products[i].getSettings();
							s['PRICE'] = parseFloat(p['PRICE']);
							self._products[i].setSettings(s);
						}
						self._currencyFormat = data['CURRENCY_FORMAT'] ? data['CURRENCY_FORMAT'] : '#';
						self.refreshSumTotal();
					}

					if(data['PRODUCT_POPUP_ITEMS'] && self._dlgId.length > 0)
					{
						obCrm[self._dlgId].SetPopupItems('product', data['PRODUCT_POPUP_ITEMS']);
					}
				},
				onfailure: function(data)
				{
					self._processAjaxError(data)
				}
			});
		},
		_processAjaxError: function(data)
		{
			if(typeof(data['ERROR']) == 'undefined')
			{
				return false;
			}

			var error = data['ERROR'];
			if(typeof(BX.CrmProductEditorErrors[error]) != 'undefined')
			{
				error = BX.CrmProductEditorErrors[error];
			}
			else if(error == 'OWNER_TYPE_NOT_FOUND'
				|| error == 'OWNER_ID_NOT_FOUND'
				|| error == 'SOURCE_DATA_NOT_FOUND'
				|| error == 'ID_NOT_FOUND'
				|| error == 'PRODUCT_ID_NOT_FOUND')
			{
				// Process invalid request errors
				error = BX.CrmProductEditorErrors['INVALID_REQUEST_ERROR'];
			}

			this.showError(error);
			return true;
		},
		showError: function(msg)
		{
			alert(msg);
		}
	};

	BX.CrmProductEditor.items = {};

	BX.CrmProductEditor.get = function (id)
	{
		return typeof(this.items[id]) != 'undefined' ? this.items[id] : null;
	};

	BX.CrmProductEditor.getDefault = function ()
	{
		return typeof(this.items['__default']) != 'undefined' ? this.items['__default'] : null;
	};

	BX.CrmProductEditor.create = function (id, config)
	{
		var self = new BX.CrmProductEditor();
		self.initialize(id, config);
		this.items[id] = self;
		if(typeof(this.items['__default']) == 'undefined')
		{
			this.items['__default'] = self;
		}
		return self;
	};

	BX.CrmProduct = function ()
	{
		this._viewMode = true;
		this._settings = {};
		this._container = this._editor = null;
		this._elements = {};
		this._documentClickHandler = BX.delegate(this._handleDocumentClick, this);
		this._enableNotify = true;
	};

	BX.CrmProduct.prototype = {
		initialize:function (settings, row, editor)
		{
			this._settings = settings ? settings : {};

			this._settings['QUANTITY'] = parseInt(this.getSetting('QUANTITY', 1));
			this._settings['PRICE'] = parseFloat(this.getSetting('PRICE', 0));

			this._container = row;
			this._editor = editor;

			var deleteBtn = BX.findChild(
				row,
				{ 'tag':'span', 'class':'crm-view-table-column-delete' },
				true,
				false
			);
			if(deleteBtn)
			{
				BX.bind(deleteBtn,
					'click',
					BX.delegate(this.handleDeleteClick, this)
				);
				deleteBtn.setAttribute('title', BX.CrmProductEditorMessages.deleteButtonTitle);
			}

			var editBtn = BX.findChild(
				row,
				{ 'tag':'span', 'class':'crm-product-action-edit' },
				true,
				false
			);
			if(editBtn)
			{
				BX.bind(editBtn,
					'click',
					BX.delegate(this.handleEditClick, this)
				);
				editBtn.setAttribute('title', BX.CrmProductEditorMessages.editButtonTitle);
			}
			var views = BX.findChild(
					row,
					{ 'tag':'span', 'class':'crm-product-field-input' },
					true,
					true
				);

			if(BX.type.isArray(views))
			{
				for(var i = 0; i < views.length; i++)
				{
					BX.bind(
						views[i],
						'click',
						BX.delegate(this._handleViewClick, this)
					);
				}
			}
		},
		layout:function ()
		{
			var row = this._container;

			if(this._viewMode)
			{
				BX.removeClass(row, 'crm-product-row-edit');
			}
			else
			{
				BX.addClass(row, 'crm-product-row-edit');
			}

			var ro = this.getSetting('readOnly', 'N') == 'Y';

			// cleanup
			this._elements = {};

			BX.cleanNode(row);

			// button 'delete'
			var cell = row.insertCell(-1);
			var deleteBtn = document.createElement('SPAN');
			if(!ro)
			{
				deleteBtn.className = 'crm-view-table-column-delete';
				deleteBtn.setAttribute('title', BX.CrmProductEditorMessages.deleteButtonTitle);
				BX.bind(
					deleteBtn,
					'click',
					BX.delegate(this.handleDeleteClick, this)
				);
			}
			else
			{
				deleteBtn.style.display = 'none';
			}

			cell.appendChild(deleteBtn);

			// cell 'name'
			cell = row.insertCell(-1);
			cell.className = 'crm-product-column-name';
			cell.appendChild(document.createTextNode(this.getSetting('PRODUCT_NAME', '')));

			// cell 'qty'
			cell = row.insertCell(-1);
			cell.className = 'crm-product-column-qty';
			if (!this._viewMode)
			{
				cell.appendChild(this._prepareFieldEdit({ 'name':'QUANTITY' }));
			}
			else
			{
				cell.appendChild(this._prepareFieldView({ 'name':'QUANTITY' }));
			}

			// cell 'price'
			cell = row.insertCell(-1);
			cell.className = 'crm-product-column-price';
			if (!this._viewMode)
			{
				cell.appendChild(this._prepareFieldEdit({ 'name':'PRICE', 'value': parseFloat(this.getSetting('PRICE', 1)).toFixed(2) }));
			}
			else
			{
				cell.appendChild(this._prepareFieldView({ 'name':'QUANTITY', 'value': parseFloat(this.getSetting('PRICE', 1)).toFixed(2) }));
			}

			if(this._viewMode)
			{
				var views = BX.findChild(
					row,
					{ 'tag':'span', 'class':'crm-product-field-input' },
					true,
					true
				);

				if(BX.type.isArray(views))
				{
					for(var i = 0; i < views.length; i++)
					{
						BX.bind(
							views[i],
							'click',
							BX.delegate(this._handleViewClick, this)
						);
					}
				}
			}
			else
			{
				var editors = BX.findChild(
					row,
					{ 'tag':'span', 'class':'crm-product-field-input' },
					true,
					true
				);

				if(BX.type.isArray(editors))
				{
					for(var j = 0; j < editors.length; j++)
					{
						BX.bind(
							editors[j],
							'click',
							BX.delegate(this._handleEditorClick, this)
						);
					}
				}
			}
			// button 'edit'
			cell = row.insertCell(-1);
			cell.className = 'crm-view-table-column-edit';
			var editBtn = document.createElement('SPAN');
			if(!ro)
			{
				editBtn.className = 'crm-product-action-edit';
				editBtn.setAttribute('title', BX.CrmProductEditorMessages.editButtonTitle);
				BX.bind(
					editBtn,
					'click',
					BX.delegate(this.handleEditClick, this)
				);
			}
			else
			{
				editBtn.style.display = 'none';
			}
			cell.appendChild(editBtn);
		},
		clean: function()
		{
			if(this._container)
			{
				this._container.parentNode.removeChild(this._container);
			}
		},
		isReadOnly: function()
		{
			return this.getSetting('readOnly', 'N') == 'Y';
		},
		setReadOnly: function(readOnly)
		{
			this.setSetting('readOnly', readOnly ? 'Y' : 'N');
			this.layout();
		},
		getContainer: function()
		{
			return this._container;
		},
		_prepareFieldEdit: function(config)
		{
			this._elements[config.name] = BX.create(
				'INPUT',
				{
					props:
					{
						type: typeof(config['type']) != 'undefined' ? config['type'] : 'text',
						className: 'crm-product-element-input',
						value: typeof(config['value']) != 'undefined' ? config['value'] : this.getSetting(config.name, '')
					}
				}
			);
			return (
				BX.create(
					'SPAN',
					{
						props: { className: 'crm-product-field crm-product-field-input' },
						children:
							[
								BX.create(
									'SPAN',
									{
										props: { className: 'crm-product-field-value' },
										children: [ this._elements[config.name] ]
									}
								)
							]
					}
				)
			);
		},
		_prepareFieldView: function(config)
		{
			return (
				BX.create(
					'SPAN',
					{
						props: { className: 'crm-product-field crm-product-field-input' },
						children:
							[
								BX.create(
									'SPAN',
									{
										props: { className: 'crm-product-field-text' },
										text: typeof(config['value']) != 'undefined' ? config['value'] : this.getSetting(config.name, '')
									}
								)
							]
					}
				)
				);
		},
		handleDeleteClick:function (e)
		{
			if(this.isReadOnly())
			{
				return;
			}

			if(!this._viewMode)
			{
				this._enableNotify = false;
				this.toggleMode();
				this._enableNotify = true;
			}

			if(this._editor.handleProductDeletion(this))
			{
				this.clean();
				BX.PreventDefault(e);
			}
		},
		handleEditClick:function (e)
		{
			if(this.isReadOnly())
			{
				return;
			}

			this.toggleMode();
			BX.PreventDefault(e);
		},
		_handleDocumentClick: function(e)
		{
			if(!this._viewMode)
			{
				this.toggleMode();
			}
		},
		_handleViewClick: function(e)
		{
			this.toggleMode();
			return BX.PreventDefault(e);
		},
		_handleEditorClick: function(e)
		{
			return BX.PreventDefault(e);
		},
		toggleMode:function ()
		{
			if(!this._viewMode)
			{
				this.saveSettings();
			}

			this._viewMode = !this._viewMode;

			if(this._enableNotify)
			{
				if(this._viewMode)
				{
					this._editor.onCompleteProductEdit(this);
				}
				else
				{
					this._editor.onBeginProductEdit(this);
				}
			}

			var row = this._container;
			if(!this._viewMode)
			{
				BX.bind(
					document.body,
					'click',
					this._documentClickHandler
				);
			}
			else
			{
				BX.unbind(
					document.body,
					'click',
					this._documentClickHandler
				);
			}

			this.layout();
		},
		getSetting:function (name, dafaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : dafaultval;
		},
		setSetting: function(name, value)
		{
			this._settings[name] = value;
		},
		saveSettings:function ()
		{
			if (this._viewMode)
			{
				return;
			}

			var el = this._elements['QUANTITY'];
			if(BX.type.isDomNode(el))
			{
				var qty = parseInt(el.value);
				this._settings['QUANTITY'] = isNaN(qty) || qty <= 0 ? 1 : qty;
			}

			el = this._elements['PRICE'];
			if(BX.type.isDomNode(el))
			{
				var price = parseFloat(el.value);
				this._settings['PRICE'] = isNaN(price) ? 1 : price;
			}
		},
		getSettings:function ()
		{
			return this._settings;
		},
		setSettings:function(settings)
		{
			this._settings = settings ? settings : {};
			this.layout();
		},
		getId: function()
		{
			return parseInt(this.getSetting('ID', 0));
		},
		setId: function(id)
		{
			this._settings['ID'] = parseInt(id);
		},
		getProductId: function()
		{
			return parseInt(this.getSetting('PRODUCT_ID', 0));
		},
		getQuantity: function()
		{
			return parseInt(this.getSetting('QUANTITY', 1));
		},
		getPrice: function()
		{
			return parseFloat(this.getSetting('PRICE', 1));
		},
		isViewMode: function()
		{
			return this._viewMode;
		},
		toJson: function()
		{
			var json = '';
			if(!this._viewMode)
			{
				this.saveSettings();
			}

			json += "'ID':" + this.getId().toString();
			json += ", 'PRODUCT_ID':" + this.getProductId().toString();
			json += ", 'QUANTITY':" + this.getQuantity().toString();
			json += ", 'PRICE':" + this.getPrice().toString();

			return '{' + json + '}';
		}
	};

	BX.CrmProduct.create = function (settings, row, editor)
	{
		var self = new BX.CrmProduct();
		self.initialize(settings, row, editor);
		return self;
	};
}

BX.CrmProductEditorMessages =
{
	"editButtonTitle": "<?= CUtil::JSEscape(GetMessage('CRM_EDIT_BTN_TTL'))?>",
	"deleteButtonTitle": "<?= CUtil::JSEscape(GetMessage('CRM_DEL_BTN_TTL'))?>"
};

BX.CrmProductEditorErrors =
{
	"PERMISSION_DENIED": "<?= CUtil::JSEscape(GetMessage('CRM_PERMISSION_DENIED_ERROR'))?>",
	"INVALID_REQUEST_ERROR": "<?= CUtil::JSEscape(GetMessage('CRM_INVALID_REQUEST_ERROR'))?>"
};

<?if(!$readOnly):?>
BX.ready(
	function()
	{
		var editor = BX.CrmProductEditor.create(
			'<?= $arResult['PREFIX'] ?>_product_editor',
			<?= CUtil::PhpToJSObject($productEditorCfg) ?>
		);

		var dlgID = CRM.Set(
			BX('<?= CUtil::JSEscape($productAddBtnID) ?>'),
			'<?= CUtil::JSEscape($productAddBtnID) ?>',
			'',
			<?echo CUtil::PhpToJsObject(CCrmProductHelper::PreparePopupItems($arResult['CURRENCY_ID']));?>,
			false,
			false,
			['product'],
			{
				'ok': '<?= htmlspecialcharsbx(CUtil::JSEscape(GetMessage('CRM_FF_OK'))) ?>',
				'cancel': '<?= htmlspecialcharsbx(CUtil::JSEscape(GetMessage('CRM_FF_CANCEL'))) ?>',
				'close': '<?= htmlspecialcharsbx(CUtil::JSEscape(GetMessage('CRM_FF_CLOSE'))) ?>',
				'wait': '<?= htmlspecialcharsbx(CUtil::JSEscape(GetMessage('CRM_FF_SEARCH'))) ?>',
				'noresult': '<?= htmlspecialcharsbx(CUtil::JSEscape(GetMessage('CRM_FF_NO_RESULT'))) ?>',
				'add' : '<?= htmlspecialcharsbx(CUtil::JSEscape(GetMessage('CRM_FF_CHOISE'))) ?>',
				'edit' : '<?= htmlspecialcharsbx(CUtil::JSEscape(GetMessage('CRM_FF_CHANGE'))) ?>',
				'search' : '<?= htmlspecialcharsbx(CUtil::JSEscape(GetMessage('CRM_FF_SEARCH'))) ?>',
				'last' : '<?= htmlspecialcharsbx(CUtil::JSEscape(GetMessage('CRM_FF_LAST'))) ?>'
			},
			true
		);
		if(typeof(obCrm[dlgID]) != 'undefined')
		{
			obCrm[dlgID].AddOnSaveListener(BX.proxy(editor.handleProductAddition,editor));
			obCrm[dlgID].AddOnBeforeSearchListener(BX.proxy(editor.handleBeforeSearch,editor));

			editor.registerProductDialogId(dlgID);
		}
	}
);
<?endif;?>
</script>