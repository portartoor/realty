<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Security\Sign\Signer;

try
{
	$signer = new Signer();
	$arResult['FILTER_ENCODED_SIGNED'] = $signer->sign(base64_encode(serialize($arResult['FILTER'])));
}
catch(\Bitrix\Main\SystemException $e)
{
}