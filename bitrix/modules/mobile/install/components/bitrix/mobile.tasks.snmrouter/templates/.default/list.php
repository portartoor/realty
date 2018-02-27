<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var CMain $APPLICATION
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 * @var \Bitrix\Main\HttpRequest $request
 */
$request = \Bitrix\Main\Context::getCurrent()->getRequest();

if ($request->getPost("search"))
{
	$post = array("search" => $request->getPost("search"));
	CUtil::decodeURIComponent($post);
	$_GET["F_SEARCH_ALT"] = $post["search"];
}
$this->__component->arResult = $APPLICATION->IncludeComponent(
	'bitrix:tasks.list',
	'.default',
	$arParams + array("FORCE_LIST_MODE" => "Y"),
	$this->__component
);
