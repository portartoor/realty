<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$answer=Array();
$answer["err"]="";
CEvent::SendImmediate(
				"SEND_MAIL_TO_USER",
				"s1",
				array(
					"EMAIL_TO" => $_POST["mail"],  
				),
				"Y",
				121
			);
/*$CONTRACT_ID = "s1";
$EMAIL_TO = "wasilevskaja@yandex.ru";
$arEventFields = array(
    "ID"                  => $CONTRACT_ID,
    "EMAIL_TO"            => implode(",", $EMAIL_TO),
    ); 
$arrSITE =  CAdvContract::GetSiteArray($CONTRACT_ID);
echo "!".CEvent::Send("SEND_MAIL_TO_USER", $arrSITE, $arEventFields,"Y",121);
/*use Bitrix\Main\Mail\Event;
Event::send(array(
    "EVENT_NAME" => "SEND_MAIL_TO_USER",
    "LID" => "s1",
    "C_FIELDS" => array(
        "EMAIL_TO" => "wasilevskaja@gmail.com",
    ),
)); */
echo json_encode($answer); 
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>