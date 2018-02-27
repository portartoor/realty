<?
if(isset($_GET["ajax"]))
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include.php");
else
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	$APPLICATION->SetTitle("Поиск");
	?>
	
	<?
}
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
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");?>
<?$Project = new Rights();
$data_res = $Project->get_requests_file();
$postfix  = $Project->get_postfix();

if(isset($_GET["REQUEST_ID"]))
{
	$request = HlBlockElement::GetList($data_res["hblock"],array(),array("ID"=>$_GET["REQUEST_ID"]),array(),1);
	$request_data = $request->Fetch();
	$request_data["UF_REMONT_STATUS".$postfix]="";
	$request_data["UF_HEATING".$postfix]="";
	$request_data["UF_ADDR_STREET".$postfix]="";
	$request_data["UF_CATEGORY".$postfix]="";
	foreach($request_data as $k=>$v)
	{
		$request_data[str_replace($postfix,"",$k)]=$v;
	}
	
	switch($request_data["UF_OPERATION_TYPE".$postfix])
	{
		case 56:$request_data["UF_OPERATION_TYPE".$postfix]=143; break;
		case 57:$request_data["UF_OPERATION_TYPE".$postfix]=144; break;
		case 143:$request_data["UF_OPERATION_TYPE".$postfix]=56; break;
		case 144:$request_data["UF_OPERATION_TYPE".$postfix]=57; break;
		case 291:$request_data["UF_OPERATION_TYPE".$postfix]=292; break;
		case 292:$request_data["UF_OPERATION_TYPE".$postfix]=291; break;
		case 450:$request_data["UF_OPERATION_TYPE".$postfix]=452; break;
		case 452:$request_data["UF_OPERATION_TYPE".$postfix]=450; break;
		case 451:$request_data["UF_OPERATION_TYPE".$postfix]=453; break;
		case 453:$request_data["UF_OPERATION_TYPE".$postfix]=451; break;
		case 454:$request_data["UF_OPERATION_TYPE".$postfix]=455; break;
		case 455:$request_data["UF_OPERATION_TYPE".$postfix]=454; break;
	}
	if(isset($_GET["interes"])&&!isset($_GET["ajax"])):?>
		<span class="header_class_1"><?="Заявка №".((intval($request_data["UF_ID"])!=0)?$request_data["UF_ID"]:"*".$request_data["ID"])?></span>
	<?endif;
}
if(!isset($_GET["ajax"/*"REQUEST_ID"*/])){?>
	<div class="webform_realty"><?}?>
<?
if(isset($_GET["REQUEST_ID"]))
{
	?><span class="header_class">Подбор интересов</span><?
}
//Выбираем поля для вывода на страницу
$arRequestFields = array("UF_ROOMS_FROM", "UF_ROOMS_TO", "UF_ID", "UF_PRICE_FROM", "UF_PRICE_TO", "UF_SQUARE_FROM",
	"UF_SQUARE_TO", "UF_REMONT_STATUS", "UF_HEATING","UF_CATEGORY");
$rsData = CUserTypeEntity::GetList(Array("SORT"=>"ASC"), array("ENTITY_ID" => "HLBLOCK_".$data_res["hblock"]));
while($arReqUf = $rsData->Fetch())
{//if(isset($_GET["nw"])){echo "<xmp>";print_r($arReqUf);echo "</xmp>";}
	$arReqUf["FIELD_NAME"] = str_replace($postfix ,"",$arReqUf["FIELD_NAME"]);
	if (in_array($arReqUf["FIELD_NAME"], $arRequestFields)) 
	{
		$arUfData = CUserTypeEntity::GetByID($arReqUf["ID"]);
		$arFields[] = $arUfData;
	}
}?>
	<form name="search_object" action="" method="POST" enctype="multipart/form-data" <?=(isset($_GET["interes"]))?"data-interes='1'":""?>>
		<div class="filter_sort">
			<?
			Helper_realty::write_sort_input("ADD_DATE".$postfix, "Дата", "border_gray_right");
			Helper_realty::write_sort_input("PRICE".$postfix, "Цена");
			?>
		</div>
		<div class="search_content">
			<div class="field_to_fill_text">Поиск по ключевым словам</div>
			<div class="field_to_fill">
				<input type="text" value="" name="UF_TEXT_CONTAINED<?=$postfix?>"/>
			</div>
			<div class="field_to_fill_text">Тип операции</div>
			<?
			Helper_realty::write_select_uf("OPERATION_TYPE".$postfix);
			?>
			<div class="field_to_fill_text">Тип недвижимости</div>
			<?
			Helper_realty::write_select(4, "REALTY_TYPE",0,$postfix);
			?>
			<div class="field_to_fill_text">Тип объекта</div>
			<?
			Helper_realty::write_select_obj_type(0,0,$postfix);
			?>
			<?
			//Выводим компонент для поиска по КЛАДР
			$APPLICATION->IncludeComponent(
				"primepix:kladr.address", 
				"realty_search_form", 
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
				if(!in_array($request_data["UF_OPERATION_TYPE".$postfix],$Project->prodazha)&&!in_array($request_data["UF_OPERATION_TYPE".$postfix],$Project->sdacha))
				{
					$clearName="SQUARE";
					$x=$request_data["UF_TOTAL_SQUARE".$postfix];
					$request_data["UF_".$clearName."_FROM".$postfix]=0/*max(0,intval($x-$x/10))*/;
					$request_data["UF_".$clearName."_TO".$postfix]=0/*$x+intval($x/10)*/;
					$clearName="PRICE";
					$x=$request_data["UF_".$clearName.$postfix];
					$request_data["UF_".$clearName."_FROM".$postfix]=max(0,intval($x-$x/15));
					$request_data["UF_".$clearName."_TO".$postfix]=$x+intval($x/15);
					$clearName="ROOMS";
					$x=$request_data["UF_".$clearName.$postfix];
					if($x!="")
					{
						$request_data["UF_".$clearName."_FROM".$postfix]=max(1,$x-1);
						$request_data["UF_".$clearName."_TO".$postfix]=$x+1;
					}
				}
				else
				{
					$clearName="SQUARE";
					if(($request_data["UF_".$clearName."_TO".$postfix]==0&&$request_data["UF_".$clearName."_FROM".$postfix]>0) || ($request_data["UF_".$clearName."_FROM".$postfix]==$request_data["UF_".$clearName."_TO".$postfix]))
					{
						$x=$request_data["UF_".$clearName."_FROM".$postfix];
						$request_data["UF_".$clearName."_FROM".$postfix]=0/*max(0,intval($x-$x/15))*/;
						$request_data["UF_".$clearName."_TO".$postfix]=/*$x+intval($x/15)*/0;
					}
					else{
						/*$x=$request_data["UF_".$clearName."_FROM".$postfix];
						$request_data["UF_".$clearName."_FROM".$postfix]=max(0,intval($x-$x/5));
						$x=$request_data["UF_".$clearName."_TO".$postfix];
						$request_data["UF_".$clearName."_TO".$postfix]=$x+intval($x/5);*/
						$request_data["UF_".$clearName."_FROM".$postfix]=0;
						$request_data["UF_".$clearName."_TO".$postfix]=0;
					}
					$clearName="PRICE";
					if(true || ($request_data["UF_".$clearName."_TO".$postfix]==0&&$request_data["UF_".$clearName."_FROM".$postfix]>0) || ($request_data["UF_".$clearName."_FROM".$postfix]==$request_data["UF_".$clearName."_TO".$postfix]))
					{
						$x=$request_data["UF_".$clearName."_FROM".$postfix];
						$request_data["UF_".$clearName."_FROM".$postfix]=max(0,intval($x-$x*15/100));
						$request_data["UF_".$clearName."_TO".$postfix]=$x+intval($x*15/100);
					}
				}
				if($request_data["UF_".$clearName."_FROM".$postfix]==0)$request_data["UF_".$clearName."_FROM".$postfix]="";
				if($request_data["UF_".$clearName."_TO".$postfix]==0)$request_data["UF_".$clearName."_TO".$postfix]="";
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
						$clearName = str_replace(array("UF_", "_FROM",$postfix), "", $arField["FIELD_NAME"]);
						$clearLabel = str_replace(" от", "", $arField["EDIT_FORM_LABEL"]["ru"]);
						$IsPrice = preg_match("/price/i",$clearName);
						$InputType = $IsPrice ? "hidden" : "text";
						?>
						<div class="field_to_fill_text"><?=$clearLabel?></div>
						<div class="form_field">
							<div class="range_from">
								<span>от</span>
								<?if($IsPrice):?>
								<input class="custom-number-format" type="text" value="<?=($request_data["UF_".$clearName."_FROM".$postfix]>0)?$request_data["UF_".$clearName."_FROM".$postfix]:""?>"/>
								<?endif;?>
								<input 
									type="<?=$InputType?>" 
									name="UF_<?=$clearName?>_FROM<?=$postfix?>" 
									value="<?=($request_data["UF_".$clearName."_FROM".$postfix]>0)?$request_data["UF_".$clearName."_FROM".$postfix]:""?>">
							</div><?
							?><div class="range_to">
								<span>до</span>
								<?if($IsPrice):?>
								<input class="custom-number-format" type="text" value="<?=($request_data["UF_".$clearName."_TO".$postfix]>0)?$request_data["UF_".$clearName."_TO".$postfix]:""?>"/>
								<?endif;?>
								<input 
									type="<?=$InputType?>" 
									name="UF_<?=$clearName?>_TO<?=$postfix?>" 
									value="<?=($request_data["UF_".$clearName."_TO".$postfix]>0)?$request_data["UF_".$clearName."_TO".$postfix]:""?>">
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
		<div id="showMap">Поиск по карте</div>
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
		<input class="big_button" type="submit" name="web_form_submit" value="Поиск" />
		<input class="big_button" type="reset" name="web_form_reset" value="Очистить" />
	</form>
	<div id="result_arr" data-page="n"></div>
<?if(!isset($_GET["ajax"/*"REQUEST_ID"*/])){?></div><?}?>
<?if ($_GET["map"] == 1) {?>
<script src="http://mourner.github.io/simplify-js/simplify.js"></script>
<script type="text/javascript" src="https://api-maps.yandex.ru/2.1/?lang=ru_RU"></script>
<script type="text/javascript" src="/bitrix/templates/realty/js/realty_search.js"></script>
<?}?>
<script type="text/javascript">
	
	$(document).ready(function(){
		$(".custom-number-format").keyup(function(event){
			var MyNumber = ($(this).val()).replace(/ /g,"");
			var MyNumberFormat = number_format(MyNumber, 0, "", " ");
			$(this).val((MyNumberFormat == 0 ? "0" : MyNumberFormat));
			$(this).next().val((MyNumberFormat == 0 ? "0" : MyNumber));
		});
		
	});
	function number_format( number, decimals, dec_point, thousands_sep ) {

		var i, j, kw, kd, km;

		if( isNaN(decimals = Math.abs(decimals)) ){
			decimals = 2;
		}
		if( dec_point == undefined ){
			dec_point = ",";
		}
		if( thousands_sep == undefined ){
			thousands_sep = ".";
		}

		i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

		j = ((j = i.length) > 3) ? (j % 3) : 0;
		
		km = (j ? i.substr(0, j) + thousands_sep : "");
		kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
		kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");
		return km + kw + kd;
	}


</script>
<?if(!isset($_GET["ajax"])) require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>