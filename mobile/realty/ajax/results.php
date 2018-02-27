<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include.php");
$arRequest = $_POST;
$arSort = Array();
//Очищаем запрос от лишних полей, иначе при поиске будут не правильные результаты
foreach ($arRequest as $key => $requestField)
{
	if (in_array($key, array("interested","update_labels", "delete_not_in_kladr_values", "web_form_submit","UF_OBJ_TYPE_H","kladr_id","zip_code","location_label","location_label_min","street_label","street_label_min","district_label","district_label_min")))
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
	$dbResultObj = HlBlockElement::GetList(13, array(), array(array("UF_REQUEST_S"=>$request_id,"UF_REQUEST_F"=>$request_id,"LOGIC" => "OR")), array(), 100);
	$intersested = Array();
	while($arElement = $dbResultObj->Fetch())
	{
		if($arElement["UF_ACTIVE"])
		{
			if($arElement["UF_REQUEST_S"]==$request_id)
				$intersested[]=$arElement["UF_REQUEST_F"];
			else
				$intersested_out[]=$arElement["UF_REQUEST_S"];
		}
	}
	$interes_arr = array_merge($intersested,$intersested_out);
endif;
//Если указан ID ищем по нему
if(isset($arRequest["UF_ID"]))
{
	if($_GET["PAGEN_2"]>1)die();
	$dbResult = HlBlockElement::GetList(2, array(), array("UF_ID" => $arRequest["UF_ID"]), array(), 1);
}
//Иначе задаем фильтр для поиска
else
{
	foreach ($arRequest as $key => $requestField) 
	{
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
		else if ($key == "street")
		{
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
		{
			$fieldName = str_replace(array("_FROM", "_TO"), "", $key);
			if ($fieldName == "UF_SQUARE") 
				$fieldName = "UF_TOTAL_SQUARE";
			$fieldName = (strpos($key, "_FROM")) ? (">=" . $fieldName) : ("<=" . $fieldName);
			$arRequest[$fieldName] = $requestField;
			unset($arRequest[$key]);
		}
		else if(strpos($requestField,str_replace("UF_","",$key)."_")!==FALSE)
		{
			//$arRequest[$key.".XML_ID"]=$requestField;
			$rs = CUserFieldEnum::GetList(array(), array(
				"USER_FIELD_NAME" => $key,"XML_ID"=>$requestField
			));
			if($ar = $rs->Fetch())
			{
				$arRequest[$key]=$ar["ID"];
				//print_r($ar);
			}
			//unset($arRequest[$key]);
		}
	}
	$dbResultObj = HlBlockElement::GetList(3, array("UF_OBJ_TYPE_ID","UF_OBJ_TYPE_NAME", "UF_OBJ_TYPE_CLASS"), array(), array(), 100);
	$obj_type_help = Array();
	while($arElement = $dbResultObj->Fetch())
	{
		$obj_type_help[$arElement["UF_OBJ_TYPE_ID"]]=Array("UF_OBJ_TYPE_NAME"=>$arElement["UF_OBJ_TYPE_NAME"],"UF_OBJ_TYPE_CLASS"=>$arElement["UF_OBJ_TYPE_CLASS"]);
	}
	$arRequest[">=UF_INNER_STATUS"] = 0;
	$count=0;
	if(isset($_POST["interested"]))
		$arRequest["ID"] = $interes_arr;
	$dbResult = HlBlockElement::GetList(2, array("ID","UF_ID","UF_PHOTO_PREVIEW","UF_ADDR_STREET","UF_CITY_REGION","UF_CITY_ID","UF_ADDR_STREET","UF_PRICE","UF_OBJ_TYPE","UF_ROOMS","UF_ETAGE","UF_ETAGE_COUNT","UF_TOTAL_SQUARE","UF_LIVING_SQUARE","UF_KITCHEN_SQUARE","UF_GARAGE_SQUARE","UF_CELLAR_SQUARE","UF_LOT_SQUARE","UF_OPERATION_TYPE","UF_ROOMS_FROM","UF_ROOMS_TO","UF_PRICE_FROM","UF_PRICE_TO","UF_SQUARE_TO","UF_SQUARE_FROM","UF_AGENT"), $arRequest, $arSort, 10);
	$whole_count = $dbResult->SelectedRowsCount();
	if ($whole_count==0&&$_GET["PAGEN_2"]==1)
		die ("<div class=\"not_found\">По вашему запросу ничего не найдено</div>");
	if(isset($_GET["PAGEN_2"])&&($whole_count <= 10*(intval($_GET["PAGEN_2"])-1)||$whole_count==0))die();
}
	while($arElement = $dbResult->Fetch()) {
		$count++;
		?>
		<div class="obj_item">
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
				?>
				<img src="/bitrix/templates/realty/images/soon.jpg"/>
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
				<span class="obj_street"><a href="/realty/view/?REQUEST_ID=<?=$arElement["ID"]?><?=(isset($_GET["interes"]))?"&interes=".$_GET["interes"]:""?>">
					<? echo "Заявка №".((intval($arElement["UF_ID"])!=0)?$arElement["UF_ID"]:"*".$arElement["ID"])?>
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
					switch($arElement["UF_OPERATION_TYPE"]) 
					{
						case 56:$w="Продаётся ";break;
						case 57:$w="Сдаётся ";break;
						case 143:$w="Покупается ";break;
						case 144:$w="Снимается ";break;
					}
					$description = $w;
					//echo $obj_type_help[$arElement["UF_OBJ_TYPE"]]["UF_OBJ_TYPE_CLASS"]." ".$obj_type_help[$arElement["UF_OBJ_TYPE"]]["UF_OBJ_TYPE_NAME"]."!";
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
					?>
				</div>
				<?if($arElement["UF_OPERATION_TYPE"]<140):?>
					<span class="obj_price"><?=number_format($arElement["UF_PRICE"], 0, ",", " ")?> руб.</span>
				<?endif;?>
			</div>
		</div>
		<?
	}
	if ($count==0)
		echo "<div class=\"not_found\">По вашему запросу ничего не найдено</div>";

?>