<?

CModule::AddAutoloadClasses(
	"mobile",
	array(
		"CMobileEvent" => "classes/general/mobile_event.php",
		"CMobileHelper" => "classes/general/mobile_helper.php",
		"MobileApplication" => "classes/general/mobile_event.php",
	)
);

CJSCore::RegisterExt('mobile_voximplant', array(
	'js' => '/bitrix/js/mobile/mobile_voximplant.js',
));

CJSCore::RegisterExt('mobile_ui', array(
	'js' => '/bitrix/js/mobile/mobile_ui.js',
	'lang' => '/bitrix/modules/mobile/lang/'.LANGUAGE_ID.'/mobile_ui_messages.php',
	'css' => '/bitrix/js/mobile/css/mobile_ui.css',
	));
CJSCore::RegisterExt('mobile_crm', array(
	'js'   => '/bitrix/js/mobile/mobile_crm.js',
	'lang' => '/bitrix/modules/mobile/lang/'.LANGUAGE_ID.'/crm_js_messages.php',
));
