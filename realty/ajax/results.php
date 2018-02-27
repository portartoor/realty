<?
//$_GET["nw"]=1;
if(isset($_GET["PAGEN_2"])&&!isset($_GET["PAGEN_1"]))
{
	$_GET["PAGEN_1"]=$_GET["PAGEN_2"];
}
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include.php");
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
$Project = new Rights();
$data_res = $Project->get_requests_file();
$postfix  = $Project->get_postfix();

$arRequest = $_POST;
$arSort = Array();
//Очищаем запрос от лишних полей, иначе при поиске будут не правильные результаты
foreach ($arRequest as $key => $requestField)
{
	if (in_array($key, array("interested","update_labels", "delete_not_in_kladr_values", "web_form_submit","UF_OBJ_TYPE_H".$postfix,"kladr_id","zip_code","location_label","location_label_min","street_label","street_label_min","district_label","district_label_min")))
	{
		unset($arRequest[$key]);
	}
	else
	{
		if (empty($requestField))
		{
			unset($arRequest[$key]);
		}	
	}
}
$intersested = Array();
$intersested_out = Array();
$interes_arr = Array(); 
$request_id=0;
if(isset($_GET["interes"])):
	$request_id = $_GET["interes"];
$dbResultObj = HlBlockElement::GetList($Project->get_interes(), array(), array(/*array(*/"UF_REQUEST_S".$postfix=>$request_id/*,"UF_REQUEST_F"=>$request_id,"LOGIC" => "OR")*/), array(), 100);
	$intersested = Array();
	while($arElement = $dbResultObj->Fetch())
	{
		if($arElement["UF_ACTIVE"])
		{ //echo $arElement["ID"]." ";
			if($arElement["UF_REQUEST_S"]==$request_id)
				$intersested[]=$arElement["UF_REQUEST_F"];
			else
				$intersested_out[]=$arElement["UF_REQUEST_S"];
		}
	}
	$interes_arr = array_merge($intersested,$intersested_out);
/*global $USER;
	if($USER->GetID()==752)
	{echo $Project->get_interes();
		print_r($interes_arr);
}*/
endif;
//Если указан ID ищем по нему
if(isset($arRequest["UF_ID".$postfix]))
{
	if($_GET["PAGEN_2"]>1)die();
	$dbResult = HlBlockElement::GetList($data_res["hblock"], array(), array("UF_ID".$postfix => $arRequest["UF_ID".$postfix]), array(), 1);
}
//Иначе задаем фильтр для поиска
else
{
	foreach ($arRequest as $key => $requestField) 
	{
		$key=str_replace($postfix,"",$key);
		if ($key == "sort_field")
		{
			foreach ($requestField as $sortKey => $sortType)
			{
				$arSort[$sortType] = $arRequest["sort_direction"][$sortKey];
			}
			unset($arRequest["sort_field"]);
			unset($arRequest["sort_direction"]);
		}
		//Изменяем названия полей возвращаемых компонентом КЛАДР
		else if ($key == "location")
		{
			$arRequest["UF_CITY_ID"] = "%".$requestField."%";	
			unset($arRequest[$key]);
		}
		else if ($key == "UF_TEXT_CONTAINED")
		{
			//название, описание, комментарий, адресные поля и первые 2 свойства
			$SearchTextArr = array("LOGIC" => "AND");
			$SearchText = explode(" ",$requestField);
			foreach($SearchText as $keyText => $valueText){
				$SearchTextArr[] = array("UF_SEARCH_LINE" => "%".trim($valueText)."%");
			}
			if(!empty($SearchText)){
				$arRequest[] = $SearchTextArr;
			}
			unset($SearchText);
			unset($SearchTextArr);
			unset($arRequest[$key]);
		}
		else if ($key == "street")
		{
			$requestField_res = str_replace("."," ",trim($requestField));
			$request_field_arr = explode(" ",$requestField_res);
			$street_name_to_find = trim($requestField);
			if(sizeof($request_field_arr)>1)
			{
				$arr_street_r = array( 
										"LOGIC" => "AND"
									);
				foreach ($request_field_arr as $mu => $nu)
				{
					if(strlen($nu)>3)
					{
						$street_name_to_find=$nu;
						$arr_street_r[] = Array("UF_ADDR_STREET".$postfix => "%".$nu."%");	
					}
				 }
				if(sizeof($arr_street_r)>2)
				{
					$arRequest[] = $arr_street_r;
				}
				else 
					$arRequest["UF_ADDR_STREET"] = "%".$street_name_to_find."%";
			}
			else
				$arRequest["UF_ADDR_STREET"] = "%".$requestField."%";	
			unset($arRequest[$key]);
		}
		else if ($key == "district")
		{
			$arRequest["UF_REGION_ID"] = "%".$requestField."%";	
			unset($arRequest[$key]);
		}
		//Создаем range для поиска в базе
		else if ((strpos($key, "_FROM") || strpos($key, "_TO")))
		{//echo $key."<br>";
			if(in_array($arRequest["UF_OPERATION_TYPE"],$Project->pokupka)||in_array($arRequest["UF_OPERATION_TYPE"],$Project->siem))
			{
				$fieldName = $key;
				$fieldName = (strpos($key, "_FROM")) ? (">=" . $fieldName) : ("<=" . $fieldName);
				$arRequest[$fieldName] = $requestField;
				unset($arRequest[$key]);
				unset($arRequest[$key.$postfix]);
			}
			else
			{
				$fieldName = str_replace(array("_FROM", "_TO"), "", $key);
				if ($fieldName == "UF_SQUARE") 
					$fieldName = "UF_TOTAL_SQUARE";
				$fieldName = (strpos($key, "_FROM")) ? (">=" . $fieldName) : ("<=" . $fieldName);
				$arRequest[$fieldName] = $requestField;
				unset($arRequest[$key]);
				unset($arRequest[$key.$postfix]);
			}
		}
		else if(strpos($requestField,str_replace("UF_","",$key)."_")!==FALSE)
		{
			//$arRequest[$key.".XML_ID"]=$requestField;
			$rs = CUserFieldEnum::GetList(array(), array(
				"USER_FIELD_NAME" => $key.$postfix,"XML_ID"=>$requestField
			));
			if($ar = $rs->Fetch())
			{
				$arRequest[$key]=$ar["ID"];
				//print_r($ar);
				//echo "!".$key;
			}
			//unset($arRequest[$key]);
		}
	}
	$arRequest[">=UF_INNER_STATUS"] = 0;
	if(isset($_GET["interes"]))
	{	
		$arRequest["!UF_CATEGORY"] = $Project->get_id_C_category();
	}
	$count=0;
	if(isset($_POST["interested"])){
		$arRequest["ID"] = $interes_arr;
	}
	$arr_with_fields = array("ID","UF_ID","UF_PHOTO_PREVIEW","UF_CITY_REGION","UF_CITY_ID","UF_ADDR_STREET","UF_PRICE","UF_OBJ_TYPE","UF_ROOMS","UF_ETAGE","UF_ETAGE_COUNT","UF_TOTAL_SQUARE","UF_LIVING_SQUARE","UF_KITCHEN_SQUARE","UF_GARAGE_SQUARE","UF_CELLAR_SQUARE","UF_LOT_SQUARE","UF_OPERATION_TYPE","UF_ROOMS_FROM","UF_ROOMS_TO","UF_PRICE_FROM","UF_PRICE_TO","UF_SQUARE_TO","UF_SQUARE_FROM","UF_AGENT","UF_CATEGORY","UF_INNER_STATUS");
	//print_r($arRequest);
	$arr_with_fields_to_view = $Project->add_postfix_to_fields_1($arr_with_fields);
	$arRequest_to = $Project->add_postfix_to_fields($arRequest);//print_r($arRequest_to);
	
	$dbResult = HlBlockElement::GetList($data_res["hblock"], $arr_with_fields_to_view, $arRequest_to, $arSort, 10);
	/*if($USER->GetID()==752)
	{
		print_r( $arRequest_to);
	}*/
	$whole_count = $dbResult->SelectedRowsCount();
	if ($whole_count==0&&$_GET["PAGEN_2"]==1)
		die ("<div class=\"not_found\">По вашему запросу ничего не найдено</div>");
	if(isset($_GET["PAGEN_2"])&&($whole_count <= 10*(intval($_GET["PAGEN_2"])-1)||$whole_count==0))die();
}
	$dbResultObj = HlBlockElement::GetList(3, array("UF_OBJ_TYPE_ID","UF_OBJ_TYPE_NAME", "UF_OBJ_TYPE_CLASS"), array(), array(), 100);
	$obj_type_help = Array();
	while($arElement = $dbResultObj->Fetch())
	{
		$obj_type_help[$arElement["UF_OBJ_TYPE_ID"]]=Array("UF_OBJ_TYPE_NAME"=>$arElement["UF_OBJ_TYPE_NAME"],"UF_OBJ_TYPE_CLASS"=>$arElement["UF_OBJ_TYPE_CLASS"]);
	}
	while($arElement = $dbResult->Fetch()) {
		$count++;
		foreach($arElement as $k=>$v)
		{
			$arElement[str_replace($postfix,"",$k)]=$v;
		}
		?>
		<div class="obj_item category_<?=strtolower($arElement["UF_CATEGORY"])?>">
			<div <?if($arElement["UF_ID"]>0):?>class="find_preview obj_image" data-id="<?=$arElement["UF_ID"]?>"<?else:?> class="obj_image"<?endif;?>>
			<?
			//Выводим картинку
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
				/*$Src = "";
				$PreviewImg = file_get_contents("http://invent-realty.ru/help_portal_ir/get_preview_img_object.php?Id=".$arElement["UF_ID"]);
				*/$Src = /*$PreviewImg != "" ? $PreviewImg : */"/bitrix/templates/realty/images/soon.jpg";
				?>
				<img class="load_img" src="<?=$Src?>"/>
				<?
			}?>
				<?if(isset($_GET["interes"])):?>
					<div class="plashka_interes">
						<a href="javascript:void(0)" class="call <?=(in_array($arElement["ID"],$interes_arr))?"":"hide"?>" onclick="call_agent('<?=$arElement["UF_AGENT"]?>');"></a>
						<?/*?><a href="/realty/view/?REQUEST_ID=<?=$arElement["ID"]?>&interes=<?=$_GET["interes"]?>" class="first"></a><?*/?>
						<a href="javascript:void(0)" data-agent="<?=$arElement["UF_AGENT"]?>" data-f="<?=$_GET["interes"]?>" data-t="<?=$arElement["ID"]?>" data-code="<?=($arElement["UF_ID"])?$arElement["UF_ID"]:"*".$arElement["ID"]?>" data-ava="<?=$arElement["UF_PHOTO_PREVIEW"]?>" class="like <?=(in_array($arElement["ID"],$interes_arr))?"active":""?>" onclick="like_obj(this);"></a>
						<?/*if(isset($_POST["interested"])):*/?>
						<a href="javascript:void(0)" class="mail <?=(in_array($arElement["ID"],$interes_arr))?"":"hide"?>" onclick="open_chat_realty('<?=$arElement["UF_AGENT"]?>',<?=$request_id?>,<?=$arElement["ID"]?>,'<?=($arElement["UF_ID"]!="")?$arElement["UF_ID"]:"*".$arElement["ID"]?>','<?=$arElement["UF_PHOTO_PREVIEW"]?>');return false;"></a>
						<?/*endif;*/?>
					</div>
				<?endif;?>
			</div>
			<div class="obj_text_desc">
				<span class="obj_street">
				<?/*switch($arElement["UF_OPERATION_TYPE"]) 
					{
						case 56:
						case 291: $w="продажа ";break;
						case 57: $w="сдача ";break;
						case 143:
						case 292: $w="покупка ";break;
						case 144: $w="съём ";break;
					}*/
					$w=$Project->get_name_for_result($arElement["UF_OPERATION_TYPE"]);
					?>
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
							//$text_e .= ", стоимость - ".$arElement["UF_PRICE"]." тыс. руб.";
							//$text_e .= $arElement["UF_OBJ_TYPE"];
							//echo "!!!!";print_r($obj_type_help);die("dfgdfg");
						}
					if(in_array($arElement["UF_OPERATION_TYPE"],$Project->prodazha)||in_array($arElement["UF_OPERATION_TYPE"],$Project->sdacha)):	
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
					/*
					switch($obj_type_help[$arElement["UF_OBJ_TYPE"]]["UF_OBJ_TYPE_CLASS"])
					{
						
						case "Квартира":
						case "Комната":				
							$description .= mb_convert_case($obj_type_help[$arElement["UF_OBJ_TYPE"]]["UF_OBJ_TYPE_NAME"], MB_CASE_LOWER, "UTF-8")." ".(($arElement["UF_ROOMS"]>0)?$arElement["UF_ROOMS"].
								"-комнатная, ":", ");
							if($arElement["UF_OPERATION_TYPE"]<140)
							{
								$description .= $arElement["UF_ETAGE"]."/".$arElement["UF_ETAGE_COUNT"]." этаж, ".
								$arElement["UF_TOTAL_SQUARE"]."/".$arElement["UF_LIVING_SQUARE"]."/".
								$arElement["UF_KITCHEN_SQUARE"]." кв.м.";
							}
							break;
						case "Здание":
						case "Эллинг":
							$description .= mb_convert_case($obj_type_help[$arElement["UF_OBJ_TYPE"]]["UF_OBJ_TYPE_NAME"], MB_CASE_LOWER, "UTF-8")." ".($arElement["UF_ROOMS"]>0)?$arElement["UF_ROOMS"].
								"-комнатная, ":", ";
							if($arElement["UF_OPERATION_TYPE"]<140)
							{
								$description .= $arElement["UF_TOTAL_SQUARE"]."/".$arElement["UF_LIVING_SQUARE"]."/".
								$arElement["UF_KITCHEN_SQUARE"]." кв.м.";
							}
							break;
						case "Помещение":
						case "Земельный участок":
							$description .= mb_convert_case($obj_type_help[$arElement["UF_OBJ_TYPE"]]["UF_OBJ_TYPE_NAME"], MB_CASE_LOWER, "UTF-8").", ";
							if($arElement["UF_OPERATION_TYPE"]<140)
							{
								$description .= $arElement["UF_TOTAL_SQUARE"]."/".
								$arElement["UF_LIVING_SQUARE"]."/".$arElement["UF_KITCHEN_SQUARE"]." кв.м.";
							}
							break;
						default:
							$description .= mb_convert_case($obj_type_help[$arElement["UF_OBJ_TYPE"]]["UF_OBJ_TYPE_NAME"], MB_CASE_LOWER, "UTF-8").", ";
							if($arElement["UF_OPERATION_TYPE"]<140)
							{
								$description .= $arElement["UF_TOTAL_SQUARE"]."/".
								$arElement["UF_LIVING_SQUARE"]."/".$arElement["UF_KITCHEN_SQUARE"]." кв.м.";
							}
							break;
					}
					if($arElement["UF_OPERATION_TYPE"]>140)
					{
						if($arElement["UF_ROOMS_TO"])
							$description .= "от ".$arElement["UF_ROOMS_FROM"]." до ".$arElement["UF_ROOMS_TO"]." комнат,<br>";
						if($arElement["UF_SQUARE_TO"])
							$description .= " площадью от ".$arElement["UF_SQUARE_FROM"]." до ".$arElement["UF_SQUARE_TO"]." кв.м.,<br>";
						if($arElement["UF_PRICE_TO"])
							$description .= " стоимостью от ".$arElement["UF_PRICE_FROM"]." до ".$arElement["UF_PRICE_TO"]." р.".(($arElement["UF_OPERATION_TYPE"]==144)?" в месяц":"").",<br>";
					}
					echo $description;
				*/
					?>
				</div>
				<?/*if($arElement["UF_OPERATION_TYPE"]<140):*/?>
					<span class="obj_price"><?=number_format($arElement["UF_PRICE"], 0, ",", " ")?> руб.</span>
				<?/*endif;*/?>
			</div>
		</div>
		<?
	}
	if ($count==0)
		echo "<div class=\"not_found\">По вашему запросу ничего не найдено</div>";

?>