<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("");

set_time_limit(60*10);

$type="hblock";
$file = "s1.xml";
/*$hblock= 5;*/
if (file_exists("load/".$file)) {
   $xml = simplexml_load_file("load/".$file);
	foreach($xml->requests as $l=>$lv)
	{
		print_r($lv);
	}
		echo "!!!!!!";
		print_r($xml);
}
else {
    exit('Не удалось открыть файл '.$file.'.');
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>