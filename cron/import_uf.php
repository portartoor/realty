<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Импорт пользовательских полей в heighload-инфоблок Заявки");
/*
require_once dirname(__FILE__) . '/../libs/PHPExcel.php';
set_time_limit(60*10);

$all_upd = Array (
	0 => Array(
		"type" => "uf",
		"file" => "uf_source.xls",
		"name" => "SOURCE",
		"hblock" => 2),
	1 => Array(
		"type" => "uf",
		"file" => "uf_status.xls",
		"name" => "STATUS",
		"hblock" => 2),
	2 => Array(
		"type" => "uf",
		"file" => "uf_operation_type.xls",
		"name" => "OPERATION_TYPE",
		"hblock" => 2),
	3 => Array(
		"type" => "uf",
		"file" => "uf_realty_type.xls",
		"name" => "REALTY_TYPE",
		"hblock" => 2),
	4 => Array(
		"type" => "hblock",
		"file" => "obj_type.xls",
		"name" => "OBJ_TYPE",
		"hblock" => 3),
	5 => Array(
		"type" => "hblock",
		"file" => "",
		"name" => "REQUESTS",
		"hblock" => 2),
	6 => Array(
		"type" => "hblock",
		"file" => "realty_type.xls",
		"name" => "REALTY_TYPE",
		"hblock" => 4),
	7 => Array(
		"type" => "hblock",
		"file" => "kladr.xlsx",
		"name" => "KLADR",
		"hblock" => 11)
);

$i=4;
$type=$all_upd[$i]["type"];
$file = $all_upd[$i]["file"];
$name= $all_upd[$i]["name"];
$hblock= $all_upd[$i]["hblock"];


$PHPExcel_file = PHPExcel_IOFactory::load("load/".$file);
foreach ($PHPExcel_file->getWorksheetIterator() as $worksheet) {
	$rows_count = $worksheet->getHighestRow();
	//$rows_count = 19000;
	$columns_count=1; 
	if($i==4)$columns_count=3;
	if($i==7)$columns_count=14;
	echo $rows_count."!";
	if($type=="hblock")
	{	
		echo "???";
		for ($row = 2; $row <= $rows_count; $row++) {
			$value_str = "";
			for ($column = 0; $column < $columns_count; $column++) {
				$cell = $worksheet->getCellByColumnAndRow($column, $row);
				if($cell->getCalculatedValue()==""&&$column==0){$e=false; break;}
				$e[$column]=$cell->getCalculatedValue();
			}
			if($e!==false)
			{
				echo"<xmp>";print_r($e);echo"</xmp>";
				if($i==7)
				{
					HlBlockElement::Add(
					$hblock,
					array(
						"UF_".$name."_TYPE" => $e[0],
						"UF_".$name."_CODE" => $e[1],
						"UF_".$name."_ACTUAL" => $e[2],
						"UF_".$name."_REGION" => $e[3],
						"UF_".$name."_RRR" => $e[4],
						"UF_".$name."_GGG" => $e[5],
						"UF_".$name."_PPP" => $e[6],
						"UF_".$name."_UUU" => $e[7],
						"UF_".$name."_NAME" => $e[8],
						"UF_".$name."_SOKR" => $e[9],
						"UF_".$name."_INDEX" => $e[10],
						"UF_".$name."_CITY_REGION" => $e[11],
						"UF_".$name."_NAME_1" => $e[12],
						"UF_".$name."_YARMARKA" => $e[13]
						)
					);
				}
				else if($i==4)
				{
					HlBlockElement::Add(
					$hblock,
					array(
						"UF_".$name."_NAME" => $e[0],
						"UF_".$name."_ID" => $e[1],
						"UF_".$name."_PARENT" => $e[2]
						)
					);
				}
				else
				{
					HlBlockElement::Add(
					$hblock,
					array(
						"UF_".$name."_NAME" => $cell->getCalculatedValue(),
						"UF_".$name."_ID" => ($row-2))
					);
				}
			}
		}
	}
	else
	{
		for ($row = 2; $row <= $rows_count; $row++) {
			$value_str = "";
			for ($column = 0; $column < $columns_count; $column++) {
				$cell = $worksheet->getCellByColumnAndRow($column, $row);
				if($cell->getCalculatedValue()=="")break;
				$arr_1["n".($row-2)] = Array("VALUE" =>$cell->getCalculatedValue(),"XML_ID"=>$name."_".str_pad($row-2,3,"0",STR_PAD_LEFT)); 
			}
		}
	}
}
//echo"<xmp>";print_r($arr_1);echo"</xmp>";
if($type=="uf")
{
	$arFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields("HLBLOCK_".$hblock);
	if(array_key_exists("UF_".$name, $arFields))
	{
		$FIELD_ID = $arFields["UF_".$name]["ID"];
		$obEnum = new CUserFieldEnum;
		$obEnum->SetEnumValues($FIELD_ID, $arr_1);
	}
}*/
$hblock_from = 16;
$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_".$hblock_from) );
while($arRes = $rsData->Fetch())
{
	$arFields = CUserTypeEntity::GetByID( $arRes["ID"] );
	$hblockid = 25;
	$arFields["ENTITY_ID"] = "HLBLOCK_".$hblockid;
	$arFields["FIELD_NAME"] = $arFields["FIELD_NAME"]."_DF";
	$arFields["XML_ID"] = $arFields["FIELD_NAME"]."_DF";
	unset($arFields["ID"]);
	echo "<xmp>";print_r($arFields);echo "</xmp>";
	/*$obUserField  = new CUserTypeEntity;
	$obUserField->Add($arFields);*/
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>