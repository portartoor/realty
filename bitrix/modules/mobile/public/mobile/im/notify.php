<?
require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

CModule::IncludeModule('im');

\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("api_version", CMobile::getApiVersion());
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("platform", CMobile::getPlatform());
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("im-notify", 'v2');
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("im-version", IM_MOBILE_CACHE_VERSION);
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("user", $USER->GetId());

$APPLICATION->IncludeComponent("bitrix:mobile.im.notify", ".default", array(), false, Array("HIDE_ICONS" => "Y"));
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")?>