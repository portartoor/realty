<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (intval($USER->GetID()) <= 0)
	return;

if (!CModule::IncludeModule('im'))
	return;

$CIMNotify = new CIMNotify(false, Array(
	'hide_link' => false
));
$arResult = $CIMNotify->GetNotifyList();

$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "ml-notify");
$GLOBALS["APPLICATION"]->SetPageProperty("Viewport", "user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=290");

$arUnreaded = $CIMNotify->GetUnreadNotify(Array('SPEED_CHECK' => 'N', 'USE_TIME_ZONE' => 'N'));

$notifyList = array();

if ($arUnreaded['result'])
{
	$notifyList = $arUnreaded["notify"];

	foreach($arResult as $key =>$notify)
	{
		if(!array_key_exists($key, $notifyList))
		{
			$notifyList[$key] = $notify;
		}
	}

	$arResult = $notifyList;
}
if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>