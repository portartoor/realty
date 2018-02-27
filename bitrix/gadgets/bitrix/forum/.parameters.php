<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:forum.topic.last", $arCurrentValues);

$arParameters = Array(
		"PARAMETERS"=> Array(
			"FID"=>$arComponentProps["PARAMETERS"]["FID"],
			"SORT_BY"=>$arComponentProps["PARAMETERS"]["SORT_BY"],
			"SORT_ORDER"=>$arComponentProps["PARAMETERS"]["SORT_ORDER"],
			"URL_TEMPLATES_MESSAGE"=>$arComponentProps["PARAMETERS"]["URL_TEMPLATES_MESSAGE"],
			"CACHE_TYPE"=>$arComponentProps["PARAMETERS"]["CACHE_TYPE"],
			"CACHE_TIME"=>$arComponentProps["PARAMETERS"]["CACHE_TIME"],
		),
		"USER_PARAMETERS"=> Array(
			"TOPICS_PER_PAGE"=>$arComponentProps["PARAMETERS"]["TOPICS_PER_PAGE"],
			"DATE_TIME_FORMAT"=>$arComponentProps["PARAMETERS"]["DATE_TIME_FORMAT"],
		),
	);


$arParameters["PARAMETERS"]["SORT_BY"]["DEFAULT"] = "LAST_POST_DATE";
$arParameters["PARAMETERS"]["SORT_ORDER"]["DEFAULT"] = "DESC";
$arParameters["PARAMETERS"]["URL_TEMPLATES_MESSAGE"]["DEFAULT"] = "/community/forum/messages/forum#FID#/topic#TID#/message#MID#/";
$arParameters["PARAMETERS"]["CACHE_TYPE"]["DEFAULT"] = "A";
$arParameters["PARAMETERS"]["CACHE_TIME"]["DEFAULT"] = "180";

$arParameters["USER_PARAMETERS"]["TOPICS_PER_PAGE"]["DEFAULT"] = 6;
$arParameters["USER_PARAMETERS"]["DATE_TIME_FORMAT"]["DEFAULT"] = $arParams["DATE_TIME_FORMAT"];
?>
