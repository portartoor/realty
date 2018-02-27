<?
if(isset($_GET["ajax"]))
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
}
else
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	$APPLICATION->SetTitle("Список похожих объектов");
	?><script src="<?=SITE_TEMPLATE_PATH?>/js/realty_new.js"></script><?
}
require($_SERVER["DOCUMENT_ROOT"]."/libs/realty_class.php");
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
$Project = new Rights();
$data_res = $Project->get_requests_file();
$postfix  = $Project->get_postfix();
$request = HlBlockElement::GetList($data_res["hblock"],array(),array("ID"=>$_GET["REQUEST_ID"]),array(),1);
$request_data = $request->Fetch();
foreach($request_data as $k=>$v)
{
	$request_data[str_replace($postfix,"",$k)]=$v;
}
$ty = Helper_realty::get_array_for_filter(1);
$filter = Array();
foreach($ty as $k4=>$v4)
{
	if(in_array($v4,Array("UF_CLIENT_PRICE","UF_PRICE","UF_NARUZHN_REKLAMA","UF_N_REKLAMA","UF_WWW","UF_REKLAMA","UF_ADDR_STREET","UF_COMMENT_ORDER","UF_ADDR_HOUSE","UF_ADDR_FLAT","UF_CONTRAGENT","UF_SOURCE","UF_STATUS","UF_ETAGE","UF_ETAGE_COUNT","UF_TOTAL_SQUARE","UF_LIVING_SQUARE",
	"UF_KITCHEN_SQUARE","UF_SANUSEL_TYPE","UF_MATERIAL","UF_WATER","UF_HEATING")))continue;
	{
		if($Project->s_name=="domofey"&&$v4=="UF_LAND_OWNER_TYPE")
		{
			$v4="UF_LAND_O_TYPE";
		}
		$filter[$v4]=$request_data[$v4];
	}
}
$filter["!ID"] = $request_data["ID"];
$filter["UF_CITY_REGION"] = $request_data["UF_CITY_REGION"];
$arSort = Array(/*"UF_TOTAL_SQUARE"=>"ASC"*/);
if(!isset($_GET["ajax"]))
{
	?>
	<div class="similar_object">
	<div class="webform_realty"> 
	<form name="new_object" action="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/new/?step=3" method="POST" enctype="multipart/form-data">
		<div class="sayavka_content" style="padding-bottom: 0px;">
			<a href="#" style="font-size:18px;text-decoration:none;" onclick="history.go(-1);">&#8592; <u>Назад</u></a><br><br>
			<div class="field_to_fill_text ">Цена клиента</div>
			<div class="field_to_fill no_padding_right">
				<?
					$rrr["FIELD_NAME"]="UF_CLIENT_PRICE";
					$rrr["EDIT_FORM_LABEL"]["ru"]="Цена клиента";
					$request_data[$rrr["FIELD_NAME"]] = Helper_realty::correct_price($request_data[$rrr["FIELD_NAME"]]);?>
					<input type="tel" class="<?=(strlen($request_data[$rrr["FIELD_NAME"]])==0)?"red":"";?>" placeholder="введите <?=strtolower($rrr["EDIT_FORM_LABEL"]["ru"])?>" name="<?=$rrr["FIELD_NAME"]?>" value="<?=$request_data[$rrr["FIELD_NAME"]]?>" size="0" inputmode="numeric" x-inputmode="numeric">				
					<div class="column_m_price green percent"><span id="middle_price_percent"></span></div>
					<a id="more_middle_price" href="javascript:void(0);"></a>
					<div class="middle_price_block hide">
						<div class="field_to_fill_text" style="margin-top:0px;">Рекомендованная цена</div>
						<a class="set_price_cl" href="#"><div class="column_m_price" style="width:100%;"><span><b id="middle_price"></b></span></div></a>
					</div>
			</div>
		</div>
	<div id="result_arr" data-page="1">
	<input type="hidden" name="REQUEST_ID" value="<?=$_GET["REQUEST_ID"]?>">
<?
}
	$arr_to = array("ID","UF_ID","UF_PHOTO_PREVIEW","UF_ADDR_STREET","UF_CITY_REGION","UF_CITY_ID","UF_ADDR_STREET","UF_PRICE","UF_OBJ_TYPE","UF_ROOMS","UF_ETAGE","UF_ETAGE_COUNT","UF_TOTAL_SQUARE","UF_LIVING_SQUARE","UF_KITCHEN_SQUARE","UF_GARAGE_SQUARE","UF_CELLAR_SQUARE","UF_LOT_SQUARE","UF_OPERATION_TYPE","UF_ROOMS_FROM","UF_ROOMS_TO","UF_PRICE_FROM","UF_PRICE_TO","UF_SQUARE_TO","UF_SQUARE_FROM","UF_AGENT","UF_CATEGORY","UF_INNER_STATUS");
	$arr_to_view = $Project->add_postfix_to_fields_1($arr_to);
	$filter_to_view = $Project->add_postfix_to_fields($filter);

	$dbResult = HlBlockElement::GetList($data_res["hblock"], $arr_to_view, $filter_to_view, $arSort, 10);
	$whole_count = $dbResult->SelectedRowsCount();
	if ($whole_count==0&&$_GET["PAGEN_3"]==1)
		die ("<div class=\"not_found\">По вашему запросу ничего не найдено</div>");
	if(isset($_GET["PAGEN_3"])&&($whole_count <= 10*(intval($_GET["PAGEN_3"])-1)||$whole_count==0))die();

	$dbResultObj = HlBlockElement::GetList(3, array("UF_OBJ_TYPE_ID","UF_OBJ_TYPE_NAME", "UF_OBJ_TYPE_CLASS"), array(), array(), 100);
	$obj_type_help = Array();
	while($arElement = $dbResultObj->Fetch())
	{
		$obj_type_help[$arElement["UF_OBJ_TYPE_ID"]]=Array("UF_OBJ_TYPE_NAME"=>$arElement["UF_OBJ_TYPE_NAME"],"UF_OBJ_TYPE_CLASS"=>$arElement["UF_OBJ_TYPE_CLASS"]);
	}
	while($arElement = $dbResult->Fetch()) {
		foreach($arElement as $k=>$v)
		{
			$arElement[str_replace($postfix,"",$k)]=$v;
		}
		$count++;
		?>
		<div class="obj_item category_<?=strtolower($arElement["UF_CATEGORY"])?>">
			<div <?if($arElement["UF_ID"]>0):?>class="find_preview obj_image" data-id="<?=$arElement["UF_ID"]?>"<?else:?> class="obj_image"<?endif;?>>
			<?
			if(!empty($arElement["UF_PHOTO_PREVIEW"]))
			{
				$file = CFile::ResizeImageGet($arElement["UF_PHOTO_PREVIEW"], array('width'=>399, 'height'=>297), 
					BX_RESIZE_IMAGE_EXACT, true);
				?>
				<img src="<?=$file["src"]?>"/>
				<?
			}
			else
			{
				$Src = "/bitrix/templates/realty/images/soon.jpg";
				?>
				<img class="load_img" src="<?=$Src?>"/>
				<?
			}?>
				<?if(isset($_GET["interes"])):?>
					<div class="plashka_interes">
						<a href="javascript:void(0)" class="call <?=(in_array($arElement["ID"],$interes_arr))?"":"hide"?>" onclick="call_agent('<?=$arElement["UF_AGENT"]?>');"></a>
						<a href="javascript:void(0)" data-agent="<?=$arElement["UF_AGENT"]?>" data-f="<?=$_GET["interes"]?>" data-t="<?=$arElement["ID"]?>" data-code="<?=($arElement["UF_ID"])?$arElement["UF_ID"]:"*".$arElement["ID"]?>" data-ava="<?=$arElement["UF_PHOTO_PREVIEW"]?>" class="like <?=(in_array($arElement["ID"],$interes_arr))?"active":""?>" onclick="like_obj(this);"></a>
						<a href="javascript:void(0)" class="mail <?=(in_array($arElement["ID"],$interes_arr))?"":"hide"?>" onclick="open_chat_realty('<?=$arElement["UF_AGENT"]?>',<?=$request_id?>,<?=$arElement["ID"]?>,'<?=($arElement["UF_ID"]!="")?$arElement["UF_ID"]:"*".$arElement["ID"]?>','<?=$arElement["UF_PHOTO_PREVIEW"]?>');return false;"></a>
					</div>
				<?endif;?>
			</div>
			<div class="obj_text_desc">
				<span class="obj_street">
				<?switch($arElement["UF_OPERATION_TYPE"]) 
					{
						case 56:
						case 291: $w="продажа ";break;
						case 57: $w="сдача ";break;
						case 143:
						case 292: $w="покупка ";break;
						case 144: $w="съём ";break;
					}?>
				<a 
					<?=($arElement["UF_INNER_STATUS"]==5)?"class=\"striked\"":""?>
					<?if(isset($_REQUEST["app"])):?>
					onclick="if(typeof app != 'undefined'){app.loadPageBlank({url:/mobile/realty/view/?REQUEST_ID=<?=$arElement["ID"]?><?=(isset($_GET["interes"]))?"&interes=".$_GET["interes"]:""?>'});return false;}"
					<?endif;?>
					href="<?if (isset($_REQUEST["app"])) echo "/mobile"?>/realty/view/?REQUEST_ID=<?=$arElement["ID"]?><?=(isset($_GET["interes"]))?"&interes=".$_GET["interes"]:""?>">
					<? echo "Заявка №".((intval($arElement["UF_ID"])!=0)?$arElement["UF_ID"]:"*".$arElement["ID"])." ".$w?>
					<? echo($arElement["UF_ADDR_STREET"]!="")?("<br>".$arElement["UF_ADDR_STREET"]."."):""?>
					</a>
				</span>
				<?if($arElement["UF_CITY_ID"]!=""):?>
				<span class="obj_city"><?=$arElement["UF_CITY_ID"]?><?if($arElement["UF_CITY_REGION"]!=""):?>, <?=$arElement["UF_CITY_REGION"]?><?endif;?></span>
				<?endif;?>
				<div class="obj_desc">
					<?
					//$arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"] = mb_convert_case($arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"], MB_CASE_LOWER, "UTF-8");
						if ($arElement["UF_TOTAL_SQUARE"] == "") $arElement["UF_TOTAL_SQUARE"] = "-";
						if ($arElement["UF_LIVING_SQUARE"] == "") $arElement["UF_LIVING_SQUARE"] = "-";
						if ($arElement["UF_KITCHEN_SQUARE"] == "") $arElement["UF_KITCHEN_SQUARE"] = "-";
						$description = $w;
						//echo $obj_type_help[$arElement["UF_OBJ_TYPE"]]["UF_OBJ_TYPE_CLASS"]." ".$obj_type_help[$arElement["UF_OBJ_TYPE"]]["UF_OBJ_TYPE_NAME"]."!";
						
						if($arElement["UF_KITCHEN_SQUARE"] == 0){$arElement["UF_KITCHEN_SQUARE"] = "-";}
						if($arElement["UF_LIVING_SQUARE"] == 0){$arElement["UF_LIVING_SQUARE"] = "-";}
						if($arElement["UF_TOTAL_SQUARE"] == 0){$arElement["UF_TOTAL_SQUARE"] = "-";}
						$name_e = "";
						$text_e = "";
						if(in_array($arElement["UF_OBJ_TYPE"],array(1,2))){ //Квартира, Комната
							$name_e = ($arElement["UF_OBJ_TYPE"] == 1 ? "Квартира" : "Комната")." ";
							$text_e = $name_e;
							if($arElement["UF_OBJ_TYPE"] == 2 )
								$text_e .= "в ".$arElement["UF_ROOMS"]."-комнатной квартире, ";
							else
								$text_e .= $arElement["UF_ROOMS"]."-комнатная, ";
							$text_e .= $arElement["UF_ETAGE"]."/".$arElement["UF_ETAGE_COUNT"]." этаж, ";
							$text_e .= $arElement["UF_TOTAL_SQUARE"]." / ";
							$text_e .= $arElement["UF_LIVING_SQUARE"]." / ";
							$text_e .= $arElement["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
						} else if(in_array($arElement["UF_OBJ_TYPE"],array(3))){//Индивидуальный дом
							$name_e = ($arElement["UF_OBJ_TYPE"] == 3 ? "Дом" : "Эллинг")." ";
							$text_e = $name_e;
							$text_e .= $arElement["UF_ROOMS"]."-комнатный, ";
							$text_e .= $arElement["UF_ETAGE_COUNT"]."-этажный, ";
							$text_e .= $arElement["UF_TOTAL_SQUARE"]." / ";
							$text_e .= $arElement["UF_LIVING_SQUARE"]." / ";
							$text_e .=  $arElement["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
						} else if(in_array($arElement["UF_OBJ_TYPE"],array(19,24))){//Дача,Эллинг
							$name_e = ($arElement["UF_OBJ_TYPE"] == 24 ? "Эллинг" : "Дача")." ";
							$text_e = $name_e;
							$text_e .= $arElement["UF_ROOMS"]."-комнатная, ";
							$text_e .= $arElement["UF_TOTAL_SQUARE"]." / ";
							$text_e .= $arElement["UF_LIVING_SQUARE"]." / ";
							$text_e .= $arElement["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
						} else if(in_array($arElement["UF_OBJ_TYPE"],array(5))){//Земля
							$name_e = "Земля ";
							$text_e = $name_e;
							$text_e .= $arElement["UF_LOT_SQUARE"]." сот.";
						} else if(in_array($arElement["UF_OBJ_TYPE"],array(20))){//Секция
							$name_e = "Секция ";
							$text_e = $name_e;
							$text_e .=  $arElement["UF_ROOMS"]."-комнатный, ";
							$text_e .=  $arElement["UF_TOTAL_SQUARE"]." / ";
							$text_e .=  $arElement["UF_LIVING_SQUARE"]." / ";
							$text_e .=  $arElement["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
						} else if(in_array($arElement["UF_OBJ_TYPE"],array(23))){//Гаражи и стоянки
							$name_e = "Гараж ";
							$text_e = $name_e;
							$text_e .=  $arElement["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
						}
						else 
						{
							$name_e = $obj_type_help[$arElement["UF_OBJ_TYPE"]]["UF_OBJ_TYPE_NAME"];
							$text_e = $name_e;
							if($arElement["UF_ETAGE"]>0||$arElement["UF_ETAGE_COUNT"]>0)
								$text_e .= ", ".$arElement["UF_ETAGE"]."/".$arElement["UF_ETAGE_COUNT"]." этаж";
							if($arElement["UF_TOTAL_SQUARE"]>0)
								$text_e .= ", площадь - ".$arElement["UF_TOTAL_SQUARE"]." м<sup>2</sup>";
						}
					if(in_array($arElement["UF_OPERATION_TYPE"],Array(56,291,57))):	
						echo $text_e;
					else:
						echo trim($name_e);
						if($arElement["UF_ROOMS_FROM"]>0):
						?>, комнат - <?=$arElement["UF_ROOMS_FROM"]?><?
						endif;
						?>, площадь - <?=$arElement["UF_SQUARE_FROM"]?> м<sup>2</sup>
						<?
						$arElement["UF_PRICE"]=$arElement["UF_PRICE_FROM"];
					endif;
					?>
				</div>
					<span class="obj_price"><?=number_format($arElement["UF_PRICE"], 0, ",", " ")?> руб.</span>
			</div>
		</div>
		<?
	}
	if ($count==0)
		echo "<div class=\"not_found\">По вашему запросу ничего не найдено</div>";
if(!isset($_GET["ajax"]))
{
	?>
	</div>
	</form>
	</div>
	</div>
	<?
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
}?>