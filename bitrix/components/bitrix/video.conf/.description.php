<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("VC_TEMPLATE_NAME"),
	"DESCRIPTION" => GetMessage("VC_TEMPLATE_DESCRIPTION"),
	"ICON" => "/images/icon.gif",
	"SORT" => 250,
	"PATH" => array(
		"ID" => "service",
		"CHILD" => array(
			"ID" => "video",
			"NAME" => GetMessage("VIDEO_NAME")
		)
	),
);
?>