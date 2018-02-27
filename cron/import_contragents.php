<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Импорт контрагентов");

require_once dirname(__FILE__) . '/../libs/PHPExcel.php';
set_time_limit(60*10);

$type="hblock";
$file = "contragents.xlsx";
$hblock= 5;


$PHPExcel_file = PHPExcel_IOFactory::load("load/".$file);
foreach ($PHPExcel_file->getWorksheetIterator() as $worksheet) {
	$rows_count = $worksheet->getHighestRow();
	//$rows_count = 19000;
	$columns_count=2; 
	echo $rows_count."!";
	if($type=="hblock")
	{	
		for ($row = 1; $row <= $rows_count; $row++) {
			for ($column = 0; $column < $columns_count; $column++) {
				$cell = $worksheet->getCellByColumnAndRow($column, $row);
				if($cell->getCalculatedValue()==""&&$column==0){$e=false; break;}
				$e[$column]=$cell->getCalculatedValue();
			}
			if($e!==false)
			{
				echo $e[0]." ".$e[1]."<br>";
				/*HlBlockElement::Add(
				$hblock,
				array(
					"UF_AGENT_NAME" => $e[0],
					"UF_AGENT_ID" => $e[1])
				);*/
			}
		}
	}
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>