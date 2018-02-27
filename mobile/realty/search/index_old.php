<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Поиск");

//Подключение вспомогательного класса
//class Helper_realty
//Методы
//write_select($hblock,$name) 
//Выводит на страницу "select" с именем UF_$name и свойствами из справочника $hblock
//write_select_uf($name) 
//Выводит на страницу "select" с именем UF_$name и свойствами из справочника UF_$name
//write_select_kladr($name)
//Выводит на страницу "select" с поиском по КЛАДР и именем $name
//write_select_obj_type()
//Выводит на страницу "select" с варинтами выбора типа объекта
//write_sort_input($name, $label, $additional_class)
//Выводит блок для выбора сортировки формы
require($_SERVER["DOCUMENT_ROOT"]."/libs/realty_class.php");

//Выбираем поля для вывода на страницу
$arRequestFields = array("UF_ROOMS_FROM", "UF_ROOMS_TO", "UF_ID", "UF_PRICE_FROM", "UF_PRICE_TO", "UF_SQUARE_FROM",
	"UF_SQUARE_TO", "UF_REMONT_STATUS", "UF_HEATING");
$rsData = CUserTypeEntity::GetList(array(), array("ENTITY_ID" => "HLBLOCK_2"));
while($arReqUf = $rsData->Fetch())
{
	if (in_array($arReqUf["FIELD_NAME"], $arRequestFields)) 
	{
		$arUfData = CUserTypeEntity::GetByID($arReqUf["ID"]);
		$arFields[] = $arUfData;
	}
}
?>
<div class="webform_realty">
	<form id="search_object" name="search_object" action="" method="POST" enctype="multipart/form-data">
		<div class="filter_sort">
			<?
			Helper_realty::write_sort_input("ADD_DATE", "Дата", "border_gray_right");
			Helper_realty::write_sort_input("PRICE", "Цена");
			?>
		</div>
		<div class="search_content">
			<div class="field_to_fill_text">Тип недвижимости</div>
			<?
			Helper_realty::write_select(4, "REALTY_TYPE");
			?>
			<div class="field_to_fill_text">Тип объекта</div>
			<?
			Helper_realty::write_select_obj_type();
			?>
			<?
			//Выводим компонент для поиска по КЛАДР
			$APPLICATION->IncludeComponent(
				"primepix:kladr.address", 
				"realty", 
				array(
					"BUILDING_INPUT" => "N",
					"COMPONENT_TEMPLATE" => ".default",
					"DELETE_NOT_IN_KLADR_VALUES" => "Y",
					"DISTRICT_INPUT" => "Y",
					"HIDDEN_KLADR_ID" => "Y",
					"HIDDEN_LABEL" => "Y",
					"HIDDEN_LABEL_MIN" => "Y",
					"HIDDEN_Z_INDEX" => "Y",
					"INCLUDE_JQUERY" => "N",
					"INCLUDE_JQUERY_UI" => "Y",
					"INCLUDE_JQUERY_UI_THEME" => "Y",
					"KEY" => "1111",
					"LOCATION_INPUT" => "Y",
					"REGION_INPUT" => "N",
					"STREET_INPUT" => "Y",
					"TOKEN" => "56f95beb0a69dec4488b45a9",
					"UPDATE_LABELS" => "Y",
					"USE_PAID_KLADR" => "N",
					"REGIONID" => "39"
				),
				false
			);?>
			<?
				//Перебераем все поля, для вывода
				foreach ($arFields as $arField)
				{
					//Выводим поля со справчниками в виде "select".
					if ($arField["USER_TYPE_ID"] == "enumeration") 
					{
						?>
						<div class="field_to_fill_text"><?=$arField["EDIT_FORM_LABEL"]["ru"]?></div>
						<?
						Helper_realty::write_select_uf(str_replace("UF_", "", $arField["FIELD_NAME"]));
					}
					//Выводим поля с границами для выбора
					else if (strpos($arField["FIELD_NAME"], "FROM"))
					{
						$clearName = str_replace(array("UF_", "_FROM"), "", $arField["FIELD_NAME"]);
						$clearLabel = str_replace(" от", "", $arField["EDIT_FORM_LABEL"]["ru"]);
						?>
						<div class="field_to_fill_text"><?=$clearLabel?></div>
						<div class="form_field">
							<div class="range_from">
								<span>от</span>
								<input type="text" name="UF_<?=$clearName?>_FROM" value="" size="0">
							</div><?
							?><div class="range_to">
								<span>до</span>
								<input type="text" name="UF_<?=$clearName?>_TO" value="" size="0">
							</div>
						</div>
						<?
					}
					//Если находим поле с "TO", ничего не делаем, т.к. вывели его раньше вместе с "FROM"
					else if (strpos($arField["FIELD_NAME"], "TO"))
					{
					}
					//Во всех остальных случаях выводим обычные поля "text" для ввода данных
					else
					{
					?>
						<div class="field_to_fill_text"><?=$arField["EDIT_FORM_LABEL"]["ru"]?></div>
						<div class="field_to_fill">
							<input type="text" placeholder="введите <?=strtolower($arField["EDIT_FORM_LABEL"]["ru"])?>"<?
								?>name="<?=$arField["FIELD_NAME"]?>" value="" size="0">	
						</div>					
					<?
					}
				}
			?>
		</div>
		<?if ($_GET["map"] == 1) {?>
		<div id="showMap">Показать на карте</div>
		<div class="map-block">
			<span class="map-warning">Показываются только объекты с указанными координатами!</span>
			<div class="draw-on-map">
			</div>
			<div class="main-map-block">
				<canvas id="canv" width="0" height="0"></canvas>
				<div id="map"></div>
			</div>
		</div>
		<?}?>
		<input class="big_button" type="submit" name="web_form_submit" value="Искать" />
	</form>
	<div id="result_arr" data-page="0"></div>
</div>
<?if ($_GET["map"] == 1) {?>
<script src="http://mourner.github.io/simplify-js/simplify.js"></script>
<script type="text/javascript" src="https://api-maps.yandex.ru/2.1/?lang=ru_RU"></script>
<script type="text/javascript" src="/bitrix/templates/realty/js/realty_search.js"></script>
<?}?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>