<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

require_once(substr(__FILE__, 0, strlen(__FILE__) - strlen("/include.php"))."/bx_root.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/start.php");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/virtual_io.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/virtual_file.php");


$application = \Bitrix\Main\Application::getInstance();
$application->initializeExtendedKernel(array(
	"get" => $_GET,
	"post" => $_POST,
	"files" => $_FILES,
	"cookie" => $_COOKIE,
	"server" => $_SERVER,
	"env" => $_ENV
));

//define global application object
$GLOBALS["APPLICATION"] = new CMain;

if(defined("SITE_ID"))
	define("LANG", SITE_ID);

if(defined("LANG"))
{
	if(defined("ADMIN_SECTION") && ADMIN_SECTION===true)
		$db_lang = CLangAdmin::GetByID(LANG);
	else
		$db_lang = CLang::GetByID(LANG);

	$arLang = $db_lang->Fetch();

	if(!$arLang)
	{
		throw new \Bitrix\Main\SystemException("Incorrect site: ".LANG.".");
	}
}
else
{
	$arLang = $GLOBALS["APPLICATION"]->GetLang();
	define("LANG", $arLang["LID"]);
}

$lang = $arLang["LID"];
if (!defined("SITE_ID"))
	define("SITE_ID", $arLang["LID"]);
define("SITE_DIR", $arLang["DIR"]);
define("SITE_SERVER_NAME", $arLang["SERVER_NAME"]);
define("SITE_CHARSET", $arLang["CHARSET"]);
define("FORMAT_DATE", $arLang["FORMAT_DATE"]);
define("FORMAT_DATETIME", $arLang["FORMAT_DATETIME"]);
define("LANG_DIR", $arLang["DIR"]);
define("LANG_CHARSET", $arLang["CHARSET"]);
define("LANG_ADMIN_LID", $arLang["LANGUAGE_ID"]);
define("LANGUAGE_ID", $arLang["LANGUAGE_ID"]);

$context = $application->getContext();
$context->setLanguage(LANGUAGE_ID);
$context->setCulture(new \Bitrix\Main\Context\Culture($arLang));

$request = $context->getRequest();
if (!$request->isAdminSection())
{
	$context->setSite(SITE_ID);
}

$application->start();

$GLOBALS["APPLICATION"]->reinitPath();

if (!defined("POST_FORM_ACTION_URI"))
{
	define("POST_FORM_ACTION_URI", htmlspecialcharsbx(GetRequestUri()));
}

$GLOBALS["MESS"] = array();
$GLOBALS["ALL_LANG_FILES"] = array();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/tools.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/database.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/main.php");
IncludeModuleLangFile(__FILE__);

error_reporting(COption::GetOptionInt("main", "error_reporting", E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE) & ~E_STRICT & ~E_DEPRECATED);

if(!defined("BX_COMP_MANAGED_CACHE") && COption::GetOptionString("main", "component_managed_cache_on", "Y") <> "N")
{
	define("BX_COMP_MANAGED_CACHE", true);
}

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/filter_tools.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/ajax_tools.php");

/*ZDUyZmZY2Y3ZmMxOTU5ZTcyMzQwMmNkYjVlNDM3ZjI0YThhNTM=*/$GLOBALS['_____877677651']= array(base64_decode('R2V0TW9kd'.'WxlRX'.'ZlbnR'.'z'),base64_decode(''.'RXhlY3V0ZU1v'.'Z'.'HVsZ'.'U'.'V2Z'.'W50RXg='));; $GLOBALS['____2137762471']= array(base64_decode('ZGVm'.'a'.'W5'.'l'),base64_decode(''.'c3Ryb'.'G'.'Vu'),base64_decode('YmFzZTY0X2RlY2'.'9kZ'.'Q=='),base64_decode('dW'.'5'.'zZXJ'.'pYWxpemU='),base64_decode('aX'.'Nf'.'YXJyY'.'Xk='),base64_decode('Y2'.'9'.'1bnQ'.'='),base64_decode('aW'.'5fYXJ'.'yYXk='),base64_decode('c'.'2'.'Vy'.'a'.'W'.'FsaXpl'),base64_decode('Y'.'mFzZTY0'.'X'.'2VuY29kZ'.'Q='.'='),base64_decode('c3Ry'.'bGVu'),base64_decode('YXJyY'.'Xl'.'f'.'a'.'2V5X2V'.'4aXN0'.'cw=='),base64_decode('YXJy'.'Y'.'Xlfa2'.'V5X2V4aXN'.'0c'.'w=='),base64_decode('bWt0aW1l'),base64_decode('ZG'.'F0'.'ZQ='.'='),base64_decode('ZGF0ZQ='.'='),base64_decode('YXJyYX'.'lfa2V'.'5X2V4aXN0cw=='),base64_decode(''.'c3'.'Ryb'.'GVu'),base64_decode('YXJ'.'yYX'.'lf'.'a2V5X2V4aXN'.'0'.'cw=='),base64_decode('c3R'.'ybG'.'Vu'),base64_decode('YX'.'JyYXl'.'f'.'a2V5X2V4'.'aXN0cw=='),base64_decode(''.'YXJyYXlfa2V5X2V4aXN0'.'cw=='),base64_decode(''.'bW'.'t0aW1l'),base64_decode('ZGF0ZQ=='),base64_decode('ZGF'.'0ZQ'.'=='),base64_decode('bW'.'V0aG9kX'.'2V4'.'aXN0'.'cw=='),base64_decode('Y2Fs'.'bF91c'.'2VyX'.'2'.'Z1bmNfYXJyYXk'.'='),base64_decode('c'.'3RybGV'.'u'),base64_decode('YXJ'.'yYX'.'lfa'.'2'.'V'.'5X2V4aXN'.'0'.'cw=='),base64_decode('YXJy'.'YXl'.'fa'.'2'.'V5X2'.'V'.'4aXN'.'0cw='.'='),base64_decode('c2Vy'.'aWFsa'.'X'.'pl'),base64_decode('YmFzZTY0X2'.'VuY29kZQ=='),base64_decode(''.'c3'.'Ryb'.'GVu'),base64_decode(''.'Y'.'XJyYXlf'.'a2V5X2'.'V4'.'aXN0cw='.'='),base64_decode('YXJ'.'yYX'.'lfa'.'2V5'.'X2'.'V'.'4a'.'X'.'N0cw=='),base64_decode('YX'.'Jy'.'Y'.'Xlfa2V5X2V4aX'.'N0cw=='),base64_decode('aXNf'.'YX'.'J'.'yY'.'Xk'.'='),base64_decode('YX'.'JyYXlfa2V5'.'X2V'.'4'.'aXN0'.'cw='.'='),base64_decode('c2VyaWF'.'sa'.'Xpl'),base64_decode('YmFzZ'.'TY0'.'X2Vu'.'Y29kZQ=='),base64_decode('YXJyY'.'Xlfa2'.'V'.'5X2V4aXN0cw='.'='),base64_decode('YXJy'.'YXlfa2V'.'5'.'X'.'2'.'V4aXN'.'0cw=='),base64_decode('c'.'2VyaWFsa'.'X'.'pl'),base64_decode('YmFzZTY0X'.'2'.'VuY2'.'9kZ'.'Q'.'='.'='),base64_decode(''.'aXNfYX'.'JyY'.'X'.'k='),base64_decode('a'.'XNf'.'Y'.'XJyYXk='),base64_decode('a'.'W'.'5fY'.'XJy'.'Y'.'Xk='),base64_decode('YXJy'.'YXl'.'fa'.'2V5'.'X2V4'.'a'.'XN0'.'cw='.'='),base64_decode('aW5fY'.'XJyYXk='),base64_decode(''.'bWt0aW1'.'l'),base64_decode('ZG'.'F0'.'Z'.'Q=='),base64_decode('ZG'.'F0'.'ZQ=='),base64_decode('ZG'.'F0ZQ=='),base64_decode(''.'bWt0a'.'W1l'),base64_decode('Z'.'GF0ZQ'.'=='),base64_decode('ZGF0ZQ'.'=='),base64_decode(''.'a'.'W5'.'fYXJ'.'yYX'.'k'.'='),base64_decode('YX'.'Jy'.'YXlfa2V5X2V4aXN0'.'cw=='),base64_decode('YXJ'.'yYXlf'.'a2V'.'5X2V4'.'aXN0c'.'w='.'='),base64_decode(''.'c2Vy'.'aWFsaXpl'),base64_decode(''.'YmFz'.'ZT'.'Y0X2V'.'uY'.'29kZ'.'Q=='),base64_decode('YXJ'.'yYX'.'lfa2V'.'5X2V4a'.'XN0cw=='),base64_decode(''.'a'.'W50dm'.'Fs'),base64_decode('dG'.'ltZQ=='),base64_decode('YXJyYXlfa2V5'.'X2V4'.'a'.'XN0c'.'w='.'='),base64_decode('ZmlsZV'.'9'.'leGlz'.'dH'.'M'.'='),base64_decode('c'.'3RyX3J'.'lcGx'.'hY2U='),base64_decode('Y2xh'.'c3'.'NfZX'.'hpc3Rz'),base64_decode('ZGVmaW5l'));; function ___728448833($_2058782014){static $_2128576227= false; if($_2128576227 == false) $_2128576227=array('S'.'U5U'.'Uk'.'FORVR'.'fRURJVElPTg'.'==','WQ==','bWFpbg='.'=','f'.'m'.'Nw'.'Z'.'l9t'.'YXBfdmFsdW'.'U=','','ZQ==','Zg==','ZQ'.'='.'=','R'.'g==','WA'.'='.'=','Zg==','bWFp'.'bg==','fmNwZl9tYXB'.'f'.'d'.'mFsdWU=','UG9ydGFs',''.'R'.'g==','ZQ='.'=','ZQ'.'==','WA==','Rg'.'==','RA==','RA==','bQ==',''.'ZA='.'=','W'.'Q==',''.'Zg==','Z'.'g'.'==','Z'.'g==','Zg==','UG9ydGFs','Rg==',''.'ZQ==','ZQ==','WA==','Rg'.'==','R'.'A'.'==','RA='.'=',''.'bQ==','ZA==',''.'WQ='.'=',''.'bWFp'.'bg==','T24=','U2'.'V0d'.'GluZ3NDaGFuZ2U=','Z'.'g==','Zg'.'==',''.'Zg==',''.'Zg==','bW'.'Fpbg='.'=','fmNwZl9tYXBfd'.'mFsdWU=','ZQ'.'='.'=',''.'ZQ==',''.'ZQ'.'==','R'.'A='.'=','Z'.'Q='.'=','ZQ'.'==','Z'.'g'.'==','Z'.'g==','Zg==','ZQ==','bWFpbg='.'=','f'.'mN'.'wZ'.'l9t'.'YXB'.'fdmF'.'sdWU=','ZQ='.'=','Zg'.'='.'=','Zg==','Zg==','Zg==','bW'.'Fp'.'bg==',''.'fm'.'N'.'wZl9tYX'.'Bfd'.'mFsdWU=',''.'ZQ==','Zg==','UG9ydGF'.'s','UG9'.'y'.'dGFs','ZQ'.'==','ZQ'.'==','U'.'G9'.'ydGF'.'s',''.'Rg==','WA==','Rg==',''.'R'.'A==','ZQ==','ZQ==','RA'.'==','bQ==','ZA='.'=','W'.'Q==',''.'ZQ==','W'.'A==','ZQ==','Rg==','ZQ='.'=','RA==','Zg'.'==',''.'ZQ==','RA==','Z'.'Q==',''.'bQ==','ZA==',''.'WQ==','Zg==','Z'.'g==','Zg==',''.'Z'.'g='.'=','Zg='.'=','Z'.'g==','Zg==','Zg==','bWFpb'.'g'.'==','fmNwZl9tYXBfdmFsdWU=','ZQ==','ZQ==','U'.'G9'.'ydGFs',''.'Rg==','W'.'A='.'=','VFlQRQ'.'==','REF'.'URQ==','RkVBVFVSRVM=','RVhQSVJ'.'F'.'RA'.'==','V'.'FlQRQ'.'==','RA='.'=','V'.'FJZX'.'0RBWVNf'.'Q09VTl'.'Q=','REFURQ==','VF'.'JZX0'.'RBWVNfQ0'.'9VTlQ=',''.'RVhQ'.'SVJFRA==','RkVBV'.'FV'.'S'.'RV'.'M=','Z'.'g='.'=',''.'Zg==','RE9'.'DV'.'U1'.'FT'.'lRfU'.'k9PV'.'A==','L2'.'JpdH'.'JpeC'.'9tb2'.'R1'.'bGVzLw==','L2luc3RhbGw'.'vaW5kZXgucGh'.'w','Lg==',''.'Xw'.'='.'=',''.'c'.'2Vhcm'.'No',''.'Tg==','','',''.'QUNUSVZF','WQ==','c2'.'9jaWFs'.'b'.'mV0d2'.'9ya'.'w'.'='.'=','YW'.'xs'.'b'.'3dfZ'.'nJpZWxkc'.'w==','WQ'.'==',''.'SUQ'.'=',''.'c29jaWFsb'.'mV0d29yaw==','YWxsb3dfZnJ'.'pZWxkcw==','SU'.'Q=','c29jaWFsbm'.'V'.'0'.'d29yaw'.'='.'=','YWxsb3dfZnJpZWxk'.'cw==','Tg==','','','QUN'.'US'.'VZ'.'F','WQ==',''.'c'.'2'.'9jaWFsbmV'.'0d'.'29yaw==','YWxsb3d'.'fbWl'.'jcm9ibG9n'.'X3Vz'.'Z'.'XI'.'=','W'.'Q==','SUQ=','c29jaWFsbmV0d29'.'yaw==','Y'.'Wxsb3df'.'bWlj'.'cm9ib'.'G9n'.'X3'.'V'.'zZXI=','SUQ=',''.'c29jaWFsbmV0d29yaw==','Y'.'Wxsb3dfbWljc'.'m9'.'ib'.'G9nX3'.'Vz'.'ZX'.'I=','c2'.'9'.'j'.'aWFsbmV0d29ya'.'w==','YWx'.'sb3df'.'bW'.'ljcm9i'.'bG9nX2dyb'.'3V'.'w','WQ==',''.'SUQ'.'=','c29jaWFsbmV0d'.'2'.'9yaw==','YWx'.'sb3d'.'f'.'b'.'W'.'l'.'jc'.'m9ibG'.'9nX2dyb3V'.'w',''.'SUQ=','c29j'.'aW'.'FsbmV0d29yaw==',''.'Y'.'Wxsb3'.'dfb'.'Wljcm9i'.'bG9nX2dyb3Vw','T'.'g'.'==','','','QUNUS'.'VZ'.'F',''.'WQ==','c'.'29jaWFsbmV0d'.'29yaw==','YWx'.'s'.'b3df'.'Zm'.'lsZXNfd'.'X'.'Nl'.'cg==','WQ==','SUQ=','c'.'29jaWF'.'sbmV0d29yaw==',''.'YW'.'xsb3dfZmlsZXNf'.'dXNlcg==','SUQ=','c29jaWFs'.'bmV0d2'.'9ya'.'w==','YWx'.'sb'.'3dfZmlsZXNfdXNlcg==',''.'Tg'.'==','','','QU'.'NUSVZF','WQ==',''.'c29jaWFsbmV'.'0d29'.'yaw==',''.'Y'.'Wxs'.'b'.'3d'.'fY'.'mx'.'vZ191c2'.'V'.'y',''.'WQ==','SUQ=','c29'.'jaWFsbmV0d29ya'.'w'.'==','Y'.'W'.'x'.'sb'.'3dfYmxv'.'Z191c2'.'Vy','SUQ=','c29jaW'.'Fsbm'.'V0d'.'29yaw==','YWxsb3dfYmxv'.'Z1'.'91c2Vy','Tg==','','','QUNUSVZF','WQ==',''.'c29jaWFsbmV0d2'.'9'.'yaw==',''.'YWxsb3dfcG'.'hv'.'dG'.'9fd'.'XNlcg==','WQ='.'=',''.'SUQ=',''.'c29j'.'aWFsbmV0d'.'2'.'9y'.'a'.'w='.'=','YWxs'.'b'.'3dfcGh'.'vd'.'G9'.'fdX'.'Nlcg'.'==','SUQ=','c29jaWFs'.'bm'.'V0d29ya'.'w'.'==','YW'.'xs'.'b3'.'dfcGh'.'vdG9fdXN'.'l'.'cg==','Tg'.'='.'=','','','Q'.'UN'.'USVZ'.'F',''.'WQ'.'==','c29j'.'aWFs'.'bmV'.'0d29yaw='.'=','YW'.'xsb3dfZm9'.'y'.'dW1fdXN'.'lcg='.'=','WQ='.'=','SUQ=','c29jaWFsbmV0d2'.'9yaw'.'='.'=','YWxsb3df'.'Zm9ydW'.'1fdXNlcg'.'==',''.'SU'.'Q=','c'.'29ja'.'W'.'FsbmV0d29yaw==','Y'.'Wxsb3'.'df'.'Zm9ydW1fdXNl'.'cg'.'==','Tg==','','','QUNUSV'.'ZF','WQ==','c29jaWFsb'.'mV0d29y'.'aw==',''.'YWx'.'s'.'b3dfd'.'GFza3'.'NfdXNlcg'.'==','WQ==','SU'.'Q=','c29j'.'aWFs'.'b'.'m'.'V0d2'.'9yaw==','YW'.'x'.'sb3dfd'.'GF'.'za3'.'Nf'.'dXNlcg==','SUQ=','c29ja'.'WFsbm'.'V0d29ya'.'w='.'=',''.'YWxsb'.'3df'.'dGFza'.'3Nf'.'dX'.'Nlcg==','c29'.'jaW'.'FsbmV0d29'.'yaw==','YW'.'xsb3dfdGFz'.'a3NfZ'.'3JvdXA=',''.'W'.'Q==','SUQ=','c29ja'.'WFsbmV'.'0d2'.'9y'.'aw'.'='.'=','Y'.'Wxsb3dfdGFza3NfZ3J'.'vdXA=',''.'SUQ=',''.'c'.'29jaWFsb'.'mV0d29ya'.'w==','YWxsb3dfdG'.'F'.'za3'.'Nf'.'Z3JvdXA=','dGF'.'za3M=','Tg==','','','QU'.'NUSVZF','W'.'Q==','c2'.'9jaWFsbmV'.'0'.'d29'.'yaw==','YWxsb3d'.'f'.'Y2Fs'.'ZW'.'5'.'kYXJfd'.'XNlcg==','W'.'Q==','SUQ=','c'.'29ja'.'WFsbmV0d29yaw'.'==',''.'Y'.'Wxs'.'b'.'3'.'d'.'fY2'.'F'.'sZW5kYXJfdXN'.'l'.'cg='.'=','SUQ=','c2'.'9jaWF'.'sbm'.'V0'.'d29y'.'aw==','YWxsb3dfY2FsZW'.'5k'.'YX'.'J'.'fd'.'XNlc'.'g'.'==','c29j'.'aWFsbmV0d29y'.'aw'.'==','YW'.'x'.'sb3dfY2FsZW5k'.'YXJfZ3'.'JvdXA=','WQ'.'==','SUQ=','c'.'29j'.'aWFsbmV0d29yaw'.'==','YWxsb3df'.'Y2FsZ'.'W5'.'kYXJ'.'fZ3JvdXA=','SUQ=','c29j'.'a'.'W'.'F'.'sbmV0'.'d2'.'9yaw'.'==',''.'YWxs'.'b3dfY2'.'Fs'.'ZW5kYXJfZ3Jv'.'dXA=','Y2'.'F'.'sZW5kYX'.'I=','Q'.'U'.'NUSVZF','WQ==','T'.'g==',''.'ZX'.'h0cmFu'.'Z'.'XQ'.'=','aW'.'J'.'sb2Nr','T'.'25B'.'Z'.'n'.'R'.'lckl'.'CbG9ja0V'.'sZ'.'W1lb'.'nR'.'VcGRhdGU'.'=','aW50cmFuZXQ=','Q0'.'ludHJhbm'.'V0RXZlbnRI'.'YW'.'5kbGVycw==','U1'.'BSZWdpc3Rlc'.'lVwZGF'.'0ZWRJdGVt',''.'Q0ludHJhb'.'mV0U'.'2hhcmVw'.'b2ludD'.'o6'.'QWdlbn'.'RM'.'a'.'XN'.'0cygp'.'Ow==','aW'.'50cm'.'FuZXQ=',''.'Tg='.'=','Q0ludH'.'Jhb'.'mV0U2hh'.'cmV'.'wb'.'2l'.'udD'.'o6QW'.'dlbnR'.'RdW'.'V1ZS'.'gp'.'Ow==','a'.'W50cm'.'FuZXQ=',''.'Tg==','Q0'.'ludHJhbmV0'.'U'.'2h'.'h'.'cmVw'.'b'.'2ludDo'.'6QWd'.'lbnRVcGR'.'h'.'dGUoK'.'Ts=','aW5'.'0cmFuZ'.'XQ=','Tg==','aW'.'J'.'s'.'b2'.'Nr','T'.'2'.'5BZnRlckl'.'C'.'b'.'G9ja0'.'Vs'.'ZW1'.'l'.'bnRBZGQ=','aW50cmFuZX'.'Q'.'=','Q0l'.'udHJhbmV0R'.'XZlbnRIY'.'W5kbGVy'.'cw==','U1'.'BS'.'ZWdpc3RlclVw'.'ZG'.'F0'.'Z'.'WR'.'Jd'.'GVt','aWJsb2'.'Nr',''.'T25'.'BZn'.'RlcklCb'.'G9ja0V'.'sZW1lbnRVcGRhdGU=','aW50c'.'mFuZX'.'Q=',''.'Q'.'0ludH'.'JhbmV0RXZlbnRIYW5kbG'.'Vyc'.'w==','U1BS'.'ZWdpc3R'.'lclVwZGF0'.'ZWRJdGV'.'t',''.'Q0l'.'u'.'dHJ'.'hbmV0U2'.'h'.'hcmVwb'.'2ludDo6QWd'.'lbnRMaXN0cygpOw==','aW'.'50c'.'m'.'FuZXQ=','Q0ludHJhbmV0'.'U'.'2hhcmV'.'wb2ludDo6QWd'.'lbnR'.'RdWV1ZSgpOw==',''.'a'.'W50c'.'mFuZXQ=','Q0'.'lud'.'HJhb'.'mV0U2hh'.'cmVwb'.'2ludD'.'o6QWdlbnRVcGR'.'h'.'dGU'.'o'.'KTs=',''.'aW50'.'c'.'mFu'.'Z'.'XQ=','Y3Jt','bWFpbg==','T25CZWZvcm'.'V'.'Qcm9sb'.'2c=','b'.'W'.'Fpbg='.'=','Q1dp'.'e'.'mFyZFNv'.'bFBhbm'.'VsSW'.'50'.'cmFuZ'.'XQ=','U'.'2h'.'vd1BhbmVs','L'.'21vZ'.'HVsZ'.'XMvaW50cmFuZ'.'XQvcG'.'FuZW'.'xf'.'YnV'.'0dG9u'.'LnBocA'.'==',''.'RU5DT0RF','WQ'.'==');return base64_decode($_2128576227[$_2058782014]);}; $GLOBALS['____2137762471'][0](___728448833(0), ___728448833(1)); class CBXFeatures{ private static $_2135151542= 30; private static $_1932365147= array( "Portal" => array( "CompanyCalendar", "CompanyPhoto", "CompanyVideo", "CompanyCareer", "StaffChanges", "StaffAbsence", "CommonDocuments", "MeetingRoomBookingSystem", "Wiki", "Learning", "Vote", "WebLink", "Subscribe", "Friends", "PersonalFiles", "PersonalBlog", "PersonalPhoto", "PersonalForum", "Blog", "Forum", "Gallery", "Board", "MicroBlog", "WebMessenger",), "Communications" => array( "Tasks", "Calendar", "Workgroups", "Jabber", "VideoConference", "Extranet", "SMTP", "Requests", "DAV", "intranet_sharepoint", "timeman", "Idea", "Meeting", "EventList", "Salary", "XDImport",), "Enterprise" => array( "BizProc", "Lists", "Support", "Analytics", "crm", "Controller",), "Holding" => array( "Cluster", "MultiSites",),); private static $_902016406= false; private static $_2084158217= false; private static function Initialize(){ if(self::$_902016406 == false){ self::$_902016406= array(); foreach(self::$_1932365147 as $_884394707 => $_2120061271){ foreach($_2120061271 as $_139888527) self::$_902016406[$_139888527]= $_884394707;}} if(self::$_2084158217 == false){ self::$_2084158217= array(); $_1525935114= COption::GetOptionString(___728448833(2), ___728448833(3), ___728448833(4)); if($GLOBALS['____2137762471'][1]($_1525935114)>(200*2-400)){ $_1525935114= $GLOBALS['____2137762471'][2]($_1525935114); self::$_2084158217= $GLOBALS['____2137762471'][3]($_1525935114); if(!$GLOBALS['____2137762471'][4](self::$_2084158217)) self::$_2084158217= array();} if($GLOBALS['____2137762471'][5](self::$_2084158217) <=(1020/2-510)) self::$_2084158217= array(___728448833(5) => array(), ___728448833(6) => array());}} public static function InitiateEditionsSettings($_1026410113){ self::Initialize(); $_344609911= array(); foreach(self::$_1932365147 as $_884394707 => $_2120061271){ $_1737677681= $GLOBALS['____2137762471'][6]($_884394707, $_1026410113); self::$_2084158217[___728448833(7)][$_884394707]=($_1737677681? array(___728448833(8)): array(___728448833(9))); foreach($_2120061271 as $_139888527){ self::$_2084158217[___728448833(10)][$_139888527]= $_1737677681; if(!$_1737677681) $_344609911[]= array($_139888527, false);}} $_1819033439= $GLOBALS['____2137762471'][7](self::$_2084158217); $_1819033439= $GLOBALS['____2137762471'][8]($_1819033439); COption::SetOptionString(___728448833(11), ___728448833(12), $_1819033439); foreach($_344609911 as $_1474913709) self::ExecuteEvent($_1474913709[min(182,0,60.666666666667)], $_1474913709[round(0+1)]);} public static function IsFeatureEnabled($_139888527){ if($GLOBALS['____2137762471'][9]($_139888527) <= 0) return true; self::Initialize(); if(!$GLOBALS['____2137762471'][10]($_139888527, self::$_902016406)) return true; if(self::$_902016406[$_139888527] == ___728448833(13)) $_1325914002= array(___728448833(14)); elseif($GLOBALS['____2137762471'][11](self::$_902016406[$_139888527], self::$_2084158217[___728448833(15)])) $_1325914002= self::$_2084158217[___728448833(16)][self::$_902016406[$_139888527]]; else $_1325914002= array(___728448833(17)); if($_1325914002[min(4,0,1.3333333333333)] != ___728448833(18) && $_1325914002[(187*2-374)] != ___728448833(19)){ return false;} elseif($_1325914002[(209*2-418)] == ___728448833(20)){ if($_1325914002[round(0+0.2+0.2+0.2+0.2+0.2)]< $GLOBALS['____2137762471'][12]((886-2*443),(974-2*487),(986-2*493), Date(___728448833(21)), $GLOBALS['____2137762471'][13](___728448833(22))- self::$_2135151542, $GLOBALS['____2137762471'][14](___728448833(23)))){ if(!isset($_1325914002[round(0+2)]) ||!$_1325914002[round(0+1+1)]) self::MarkTrialPeriodExpired(self::$_902016406[$_139888527]); return false;}} return!$GLOBALS['____2137762471'][15]($_139888527, self::$_2084158217[___728448833(24)]) || self::$_2084158217[___728448833(25)][$_139888527];} public static function IsFeatureInstalled($_139888527){ if($GLOBALS['____2137762471'][16]($_139888527) <= 0) return true; self::Initialize(); return($GLOBALS['____2137762471'][17]($_139888527, self::$_2084158217[___728448833(26)]) && self::$_2084158217[___728448833(27)][$_139888527]);} public static function IsFeatureEditable($_139888527){ if($GLOBALS['____2137762471'][18]($_139888527) <= 0) return true; self::Initialize(); if(!$GLOBALS['____2137762471'][19]($_139888527, self::$_902016406)) return true; if(self::$_902016406[$_139888527] == ___728448833(28)) $_1325914002= array(___728448833(29)); elseif($GLOBALS['____2137762471'][20](self::$_902016406[$_139888527], self::$_2084158217[___728448833(30)])) $_1325914002= self::$_2084158217[___728448833(31)][self::$_902016406[$_139888527]]; else $_1325914002= array(___728448833(32)); if($_1325914002[(241*2-482)] != ___728448833(33) && $_1325914002[(217*2-434)] != ___728448833(34)){ return false;} elseif($_1325914002[(1368/2-684)] == ___728448833(35)){ if($_1325914002[round(0+0.25+0.25+0.25+0.25)]< $GLOBALS['____2137762471'][21](min(22,0,7.3333333333333),(1080/2-540),(900-2*450), Date(___728448833(36)), $GLOBALS['____2137762471'][22](___728448833(37))- self::$_2135151542, $GLOBALS['____2137762471'][23](___728448833(38)))){ if(!isset($_1325914002[round(0+1+1)]) ||!$_1325914002[round(0+0.66666666666667+0.66666666666667+0.66666666666667)]) self::MarkTrialPeriodExpired(self::$_902016406[$_139888527]); return false;}} return true;} private static function ExecuteEvent($_139888527, $_1049746371){ if($GLOBALS['____2137762471'][24]("CBXFeatures", "On".$_139888527."SettingsChange")) $GLOBALS['____2137762471'][25](array("CBXFeatures", "On".$_139888527."SettingsChange"), array($_139888527, $_1049746371)); $_1246723639= $GLOBALS['_____877677651'][0](___728448833(39), ___728448833(40).$_139888527.___728448833(41)); while($_1597163112= $_1246723639->Fetch()) $GLOBALS['_____877677651'][1]($_1597163112, array($_139888527, $_1049746371));} public static function SetFeatureEnabled($_139888527, $_1049746371= true, $_487261888= true){ if($GLOBALS['____2137762471'][26]($_139888527) <= 0) return; if(!self::IsFeatureEditable($_139888527)) $_1049746371= false; $_1049746371=($_1049746371? true: false); self::Initialize(); $_258934370=(!$GLOBALS['____2137762471'][27]($_139888527, self::$_2084158217[___728448833(42)]) && $_1049746371 || $GLOBALS['____2137762471'][28]($_139888527, self::$_2084158217[___728448833(43)]) && $_1049746371 != self::$_2084158217[___728448833(44)][$_139888527]); self::$_2084158217[___728448833(45)][$_139888527]= $_1049746371; $_1819033439= $GLOBALS['____2137762471'][29](self::$_2084158217); $_1819033439= $GLOBALS['____2137762471'][30]($_1819033439); COption::SetOptionString(___728448833(46), ___728448833(47), $_1819033439); if($_258934370 && $_487261888) self::ExecuteEvent($_139888527, $_1049746371);} private static function MarkTrialPeriodExpired($_884394707){ if($GLOBALS['____2137762471'][31]($_884394707) <= 0 || $_884394707 == "Portal") return; self::Initialize(); if(!$GLOBALS['____2137762471'][32]($_884394707, self::$_2084158217[___728448833(48)]) || $GLOBALS['____2137762471'][33]($_884394707, self::$_2084158217[___728448833(49)]) && self::$_2084158217[___728448833(50)][$_884394707][(176*2-352)] != ___728448833(51)) return; if(isset(self::$_2084158217[___728448833(52)][$_884394707][round(0+0.4+0.4+0.4+0.4+0.4)]) && self::$_2084158217[___728448833(53)][$_884394707][round(0+0.5+0.5+0.5+0.5)]) return; $_344609911= array(); if($GLOBALS['____2137762471'][34]($_884394707, self::$_1932365147) && $GLOBALS['____2137762471'][35](self::$_1932365147[$_884394707])){ foreach(self::$_1932365147[$_884394707] as $_139888527){ if($GLOBALS['____2137762471'][36]($_139888527, self::$_2084158217[___728448833(54)]) && self::$_2084158217[___728448833(55)][$_139888527]){ self::$_2084158217[___728448833(56)][$_139888527]= false; $_344609911[]= array($_139888527, false);}} self::$_2084158217[___728448833(57)][$_884394707][round(0+1+1)]= true;} $_1819033439= $GLOBALS['____2137762471'][37](self::$_2084158217); $_1819033439= $GLOBALS['____2137762471'][38]($_1819033439); COption::SetOptionString(___728448833(58), ___728448833(59), $_1819033439); foreach($_344609911 as $_1474913709) self::ExecuteEvent($_1474913709[(249*2-498)], $_1474913709[round(0+0.5+0.5)]);} public static function ModifyFeaturesSettings($_1026410113, $_2120061271){ self::Initialize(); foreach($_1026410113 as $_884394707 => $_1237759901) self::$_2084158217[___728448833(60)][$_884394707]= $_1237759901; $_344609911= array(); foreach($_2120061271 as $_139888527 => $_1049746371){ if(!$GLOBALS['____2137762471'][39]($_139888527, self::$_2084158217[___728448833(61)]) && $_1049746371 || $GLOBALS['____2137762471'][40]($_139888527, self::$_2084158217[___728448833(62)]) && $_1049746371 != self::$_2084158217[___728448833(63)][$_139888527]) $_344609911[]= array($_139888527, $_1049746371); self::$_2084158217[___728448833(64)][$_139888527]= $_1049746371;} $_1819033439= $GLOBALS['____2137762471'][41](self::$_2084158217); $_1819033439= $GLOBALS['____2137762471'][42]($_1819033439); COption::SetOptionString(___728448833(65), ___728448833(66), $_1819033439); self::$_2084158217= false; foreach($_344609911 as $_1474913709) self::ExecuteEvent($_1474913709[(139*2-278)], $_1474913709[round(0+0.2+0.2+0.2+0.2+0.2)]);} public static function SaveFeaturesSettings($_771578044, $_1196089376){ self::Initialize(); $_1615728338= array(___728448833(67) => array(), ___728448833(68) => array()); if(!$GLOBALS['____2137762471'][43]($_771578044)) $_771578044= array(); if(!$GLOBALS['____2137762471'][44]($_1196089376)) $_1196089376= array(); if(!$GLOBALS['____2137762471'][45](___728448833(69), $_771578044)) $_771578044[]= ___728448833(70); foreach(self::$_1932365147 as $_884394707 => $_2120061271){ if($GLOBALS['____2137762471'][46]($_884394707, self::$_2084158217[___728448833(71)])) $_1313225423= self::$_2084158217[___728448833(72)][$_884394707]; else $_1313225423=($_884394707 == ___728448833(73))? array(___728448833(74)): array(___728448833(75)); if($_1313225423[(233*2-466)] == ___728448833(76) || $_1313225423[(1492/2-746)] == ___728448833(77)){ $_1615728338[___728448833(78)][$_884394707]= $_1313225423;} else{ if($GLOBALS['____2137762471'][47]($_884394707, $_771578044)) $_1615728338[___728448833(79)][$_884394707]= array(___728448833(80), $GLOBALS['____2137762471'][48]((870-2*435), min(58,0,19.333333333333),(1124/2-562), $GLOBALS['____2137762471'][49](___728448833(81)), $GLOBALS['____2137762471'][50](___728448833(82)), $GLOBALS['____2137762471'][51](___728448833(83)))); else $_1615728338[___728448833(84)][$_884394707]= array(___728448833(85));}} $_344609911= array(); foreach(self::$_902016406 as $_139888527 => $_884394707){ if($_1615728338[___728448833(86)][$_884394707][(189*2-378)] != ___728448833(87) && $_1615728338[___728448833(88)][$_884394707][(168*2-336)] != ___728448833(89)){ $_1615728338[___728448833(90)][$_139888527]= false;} else{ if($_1615728338[___728448833(91)][$_884394707][min(202,0,67.333333333333)] == ___728448833(92) && $_1615728338[___728448833(93)][$_884394707][round(0+0.5+0.5)]< $GLOBALS['____2137762471'][52]((808-2*404),(1340/2-670), min(82,0,27.333333333333), Date(___728448833(94)), $GLOBALS['____2137762471'][53](___728448833(95))- self::$_2135151542, $GLOBALS['____2137762471'][54](___728448833(96)))) $_1615728338[___728448833(97)][$_139888527]= false; else $_1615728338[___728448833(98)][$_139888527]= $GLOBALS['____2137762471'][55]($_139888527, $_1196089376); if(!$GLOBALS['____2137762471'][56]($_139888527, self::$_2084158217[___728448833(99)]) && $_1615728338[___728448833(100)][$_139888527] || $GLOBALS['____2137762471'][57]($_139888527, self::$_2084158217[___728448833(101)]) && $_1615728338[___728448833(102)][$_139888527] != self::$_2084158217[___728448833(103)][$_139888527]) $_344609911[]= array($_139888527, $_1615728338[___728448833(104)][$_139888527]);}} $_1819033439= $GLOBALS['____2137762471'][58]($_1615728338); $_1819033439= $GLOBALS['____2137762471'][59]($_1819033439); COption::SetOptionString(___728448833(105), ___728448833(106), $_1819033439); self::$_2084158217= false; foreach($_344609911 as $_1474913709) self::ExecuteEvent($_1474913709[min(192,0,64)], $_1474913709[round(0+0.25+0.25+0.25+0.25)]);} public static function GetFeaturesList(){ self::Initialize(); $_2103600248= array(); foreach(self::$_1932365147 as $_884394707 => $_2120061271){ if($GLOBALS['____2137762471'][60]($_884394707, self::$_2084158217[___728448833(107)])) $_1313225423= self::$_2084158217[___728448833(108)][$_884394707]; else $_1313225423=($_884394707 == ___728448833(109))? array(___728448833(110)): array(___728448833(111)); $_2103600248[$_884394707]= array( ___728448833(112) => $_1313225423[(214*2-428)], ___728448833(113) => $_1313225423[round(0+0.25+0.25+0.25+0.25)], ___728448833(114) => array(),); $_2103600248[$_884394707][___728448833(115)]= false; if($_2103600248[$_884394707][___728448833(116)] == ___728448833(117)){ $_2103600248[$_884394707][___728448833(118)]= $GLOBALS['____2137762471'][61](($GLOBALS['____2137762471'][62]()- $_2103600248[$_884394707][___728448833(119)])/ round(0+17280+17280+17280+17280+17280)); if($_2103600248[$_884394707][___728448833(120)]> self::$_2135151542) $_2103600248[$_884394707][___728448833(121)]= true;} foreach($_2120061271 as $_139888527) $_2103600248[$_884394707][___728448833(122)][$_139888527]=(!$GLOBALS['____2137762471'][63]($_139888527, self::$_2084158217[___728448833(123)]) || self::$_2084158217[___728448833(124)][$_139888527]);} return $_2103600248;} private static function InstallModule($_1519569502, $_2117109138){ if(IsModuleInstalled($_1519569502) == $_2117109138) return true; $_1330260880= $_SERVER[___728448833(125)].___728448833(126).$_1519569502.___728448833(127); if(!$GLOBALS['____2137762471'][64]($_1330260880)) return false; include_once($_1330260880); $_420424365= $GLOBALS['____2137762471'][65](___728448833(128), ___728448833(129), $_1519569502); if(!$GLOBALS['____2137762471'][66]($_420424365)) return false; $_1298036473= new $_420424365; if($_2117109138){ if(!$_1298036473->InstallDB()) return false; $_1298036473->InstallEvents(); if(!$_1298036473->InstallFiles()) return false;} else{ if(CModule::IncludeModule(___728448833(130))) CSearch::DeleteIndex($_1519569502); UnRegisterModule($_1519569502);     } return true;} private static function OnRequestsSettingsChange($_139888527, $_1049746371){ self::InstallModule("form", $_1049746371);} private static function OnLearningSettingsChange($_139888527, $_1049746371){ self::InstallModule("learning", $_1049746371);} private static function OnJabberSettingsChange($_139888527, $_1049746371){ self::InstallModule("xmpp", $_1049746371);} private static function OnVideoConferenceSettingsChange($_139888527, $_1049746371){ self::InstallModule("video", $_1049746371);} private static function OnBizProcSettingsChange($_139888527, $_1049746371){ self::InstallModule("bizprocdesigner", $_1049746371);} private static function OnListsSettingsChange($_139888527, $_1049746371){ self::InstallModule("lists", $_1049746371);} private static function OnWikiSettingsChange($_139888527, $_1049746371){ self::InstallModule("wiki", $_1049746371);} private static function OnSupportSettingsChange($_139888527, $_1049746371){ self::InstallModule("support", $_1049746371);} private static function OnControllerSettingsChange($_139888527, $_1049746371){ self::InstallModule("controller", $_1049746371);} private static function OnAnalyticsSettingsChange($_139888527, $_1049746371){ self::InstallModule("statistic", $_1049746371);} private static function OnVoteSettingsChange($_139888527, $_1049746371){ self::InstallModule("vote", $_1049746371);} private static function OnFriendsSettingsChange($_139888527, $_1049746371){ if($_1049746371) $_1231127057= "Y"; else $_1231127057= ___728448833(131); $_1732266684= CSite::GetList(($_1737677681= ___728448833(132)),($_1342446479= ___728448833(133)), array(___728448833(134) => ___728448833(135))); while($_945381169= $_1732266684->Fetch()){ if(COption::GetOptionString(___728448833(136), ___728448833(137), ___728448833(138), $_945381169[___728448833(139)]) != $_1231127057){ COption::SetOptionString(___728448833(140), ___728448833(141), $_1231127057, false, $_945381169[___728448833(142)]); COption::SetOptionString(___728448833(143), ___728448833(144), $_1231127057);}}} private static function OnMicroBlogSettingsChange($_139888527, $_1049746371){ if($_1049746371) $_1231127057= "Y"; else $_1231127057= ___728448833(145); $_1732266684= CSite::GetList(($_1737677681= ___728448833(146)),($_1342446479= ___728448833(147)), array(___728448833(148) => ___728448833(149))); while($_945381169= $_1732266684->Fetch()){ if(COption::GetOptionString(___728448833(150), ___728448833(151), ___728448833(152), $_945381169[___728448833(153)]) != $_1231127057){ COption::SetOptionString(___728448833(154), ___728448833(155), $_1231127057, false, $_945381169[___728448833(156)]); COption::SetOptionString(___728448833(157), ___728448833(158), $_1231127057);} if(COption::GetOptionString(___728448833(159), ___728448833(160), ___728448833(161), $_945381169[___728448833(162)]) != $_1231127057){ COption::SetOptionString(___728448833(163), ___728448833(164), $_1231127057, false, $_945381169[___728448833(165)]); COption::SetOptionString(___728448833(166), ___728448833(167), $_1231127057);}}} private static function OnPersonalFilesSettingsChange($_139888527, $_1049746371){ if($_1049746371) $_1231127057= "Y"; else $_1231127057= ___728448833(168); $_1732266684= CSite::GetList(($_1737677681= ___728448833(169)),($_1342446479= ___728448833(170)), array(___728448833(171) => ___728448833(172))); while($_945381169= $_1732266684->Fetch()){ if(COption::GetOptionString(___728448833(173), ___728448833(174), ___728448833(175), $_945381169[___728448833(176)]) != $_1231127057){ COption::SetOptionString(___728448833(177), ___728448833(178), $_1231127057, false, $_945381169[___728448833(179)]); COption::SetOptionString(___728448833(180), ___728448833(181), $_1231127057);}}} private static function OnPersonalBlogSettingsChange($_139888527, $_1049746371){ if($_1049746371) $_1231127057= "Y"; else $_1231127057= ___728448833(182); $_1732266684= CSite::GetList(($_1737677681= ___728448833(183)),($_1342446479= ___728448833(184)), array(___728448833(185) => ___728448833(186))); while($_945381169= $_1732266684->Fetch()){ if(COption::GetOptionString(___728448833(187), ___728448833(188), ___728448833(189), $_945381169[___728448833(190)]) != $_1231127057){ COption::SetOptionString(___728448833(191), ___728448833(192), $_1231127057, false, $_945381169[___728448833(193)]); COption::SetOptionString(___728448833(194), ___728448833(195), $_1231127057);}}} private static function OnPersonalPhotoSettingsChange($_139888527, $_1049746371){ if($_1049746371) $_1231127057= "Y"; else $_1231127057= ___728448833(196); $_1732266684= CSite::GetList(($_1737677681= ___728448833(197)),($_1342446479= ___728448833(198)), array(___728448833(199) => ___728448833(200))); while($_945381169= $_1732266684->Fetch()){ if(COption::GetOptionString(___728448833(201), ___728448833(202), ___728448833(203), $_945381169[___728448833(204)]) != $_1231127057){ COption::SetOptionString(___728448833(205), ___728448833(206), $_1231127057, false, $_945381169[___728448833(207)]); COption::SetOptionString(___728448833(208), ___728448833(209), $_1231127057);}}} private static function OnPersonalForumSettingsChange($_139888527, $_1049746371){ if($_1049746371) $_1231127057= "Y"; else $_1231127057= ___728448833(210); $_1732266684= CSite::GetList(($_1737677681= ___728448833(211)),($_1342446479= ___728448833(212)), array(___728448833(213) => ___728448833(214))); while($_945381169= $_1732266684->Fetch()){ if(COption::GetOptionString(___728448833(215), ___728448833(216), ___728448833(217), $_945381169[___728448833(218)]) != $_1231127057){ COption::SetOptionString(___728448833(219), ___728448833(220), $_1231127057, false, $_945381169[___728448833(221)]); COption::SetOptionString(___728448833(222), ___728448833(223), $_1231127057);}}} private static function OnTasksSettingsChange($_139888527, $_1049746371){ if($_1049746371) $_1231127057= "Y"; else $_1231127057= ___728448833(224); $_1732266684= CSite::GetList(($_1737677681= ___728448833(225)),($_1342446479= ___728448833(226)), array(___728448833(227) => ___728448833(228))); while($_945381169= $_1732266684->Fetch()){ if(COption::GetOptionString(___728448833(229), ___728448833(230), ___728448833(231), $_945381169[___728448833(232)]) != $_1231127057){ COption::SetOptionString(___728448833(233), ___728448833(234), $_1231127057, false, $_945381169[___728448833(235)]); COption::SetOptionString(___728448833(236), ___728448833(237), $_1231127057);} if(COption::GetOptionString(___728448833(238), ___728448833(239), ___728448833(240), $_945381169[___728448833(241)]) != $_1231127057){ COption::SetOptionString(___728448833(242), ___728448833(243), $_1231127057, false, $_945381169[___728448833(244)]); COption::SetOptionString(___728448833(245), ___728448833(246), $_1231127057);}} self::InstallModule(___728448833(247), $_1049746371);} private static function OnCalendarSettingsChange($_139888527, $_1049746371){ if($_1049746371) $_1231127057= "Y"; else $_1231127057= ___728448833(248); $_1732266684= CSite::GetList(($_1737677681= ___728448833(249)),($_1342446479= ___728448833(250)), array(___728448833(251) => ___728448833(252))); while($_945381169= $_1732266684->Fetch()){ if(COption::GetOptionString(___728448833(253), ___728448833(254), ___728448833(255), $_945381169[___728448833(256)]) != $_1231127057){ COption::SetOptionString(___728448833(257), ___728448833(258), $_1231127057, false, $_945381169[___728448833(259)]); COption::SetOptionString(___728448833(260), ___728448833(261), $_1231127057);} if(COption::GetOptionString(___728448833(262), ___728448833(263), ___728448833(264), $_945381169[___728448833(265)]) != $_1231127057){ COption::SetOptionString(___728448833(266), ___728448833(267), $_1231127057, false, $_945381169[___728448833(268)]); COption::SetOptionString(___728448833(269), ___728448833(270), $_1231127057);}} self::InstallModule(___728448833(271), $_1049746371);} private static function OnSMTPSettingsChange($_139888527, $_1049746371){ self::InstallModule("mail", $_1049746371);} private static function OnExtranetSettingsChange($_139888527, $_1049746371){ $_1279797435= COption::GetOptionString("extranet", "extranet_site", ""); if($_1279797435){ $_1816237590= new CSite; $_1816237590->Update($_1279797435, array(___728448833(272) =>($_1049746371? ___728448833(273): ___728448833(274))));} self::InstallModule(___728448833(275), $_1049746371);} private static function OnDAVSettingsChange($_139888527, $_1049746371){ self::InstallModule("dav", $_1049746371);} private static function OntimemanSettingsChange($_139888527, $_1049746371){ self::InstallModule("timeman", $_1049746371);} private static function Onintranet_sharepointSettingsChange($_139888527, $_1049746371){ if($_1049746371){ RegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", "intranet", "CIntranetEventHandlers", "SPRegisterUpdatedItem"); RegisterModuleDependences(___728448833(276), ___728448833(277), ___728448833(278), ___728448833(279), ___728448833(280)); CAgent::AddAgent(___728448833(281), ___728448833(282), ___728448833(283), round(0+500)); CAgent::AddAgent(___728448833(284), ___728448833(285), ___728448833(286), round(0+75+75+75+75)); CAgent::AddAgent(___728448833(287), ___728448833(288), ___728448833(289), round(0+1800+1800));} else{ UnRegisterModuleDependences(___728448833(290), ___728448833(291), ___728448833(292), ___728448833(293), ___728448833(294)); UnRegisterModuleDependences(___728448833(295), ___728448833(296), ___728448833(297), ___728448833(298), ___728448833(299)); CAgent::RemoveAgent(___728448833(300), ___728448833(301)); CAgent::RemoveAgent(___728448833(302), ___728448833(303)); CAgent::RemoveAgent(___728448833(304), ___728448833(305));}} private static function OncrmSettingsChange($_139888527, $_1049746371){ if($_1049746371) COption::SetOptionString("crm", "form_features", "Y"); self::InstallModule(___728448833(306), $_1049746371);} private static function OnClusterSettingsChange($_139888527, $_1049746371){ self::InstallModule("cluster", $_1049746371);} private static function OnMultiSitesSettingsChange($_139888527, $_1049746371){ if($_1049746371) RegisterModuleDependences("main", "OnBeforeProlog", "main", "CWizardSolPanelIntranet", "ShowPanel", 100, "/modules/intranet/panel_button.php"); else UnRegisterModuleDependences(___728448833(307), ___728448833(308), ___728448833(309), ___728448833(310), ___728448833(311), ___728448833(312));} private static function OnIdeaSettingsChange($_139888527, $_1049746371){ self::InstallModule("idea", $_1049746371);} private static function OnMeetingSettingsChange($_139888527, $_1049746371){ self::InstallModule("meeting", $_1049746371);} private static function OnXDImportSettingsChange($_139888527, $_1049746371){ self::InstallModule("xdimport", $_1049746371);}} $GLOBALS['____2137762471'][67](___728448833(313), ___728448833(314));/**/			//Do not remove this

//component 2.0 template engines
$GLOBALS["arCustomTemplateEngines"] = array();

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/urlrewriter.php");

/**
 * Defined in dbconn.php
 * @param string $DBType
 */

\Bitrix\Main\Loader::registerAutoLoadClasses(
	"main",
	array(
		"CSiteTemplate" => "classes/general/site_template.php",
		"CBitrixComponent" => "classes/general/component.php",
		"CComponentEngine" => "classes/general/component_engine.php",
		"CComponentAjax" => "classes/general/component_ajax.php",
		"CBitrixComponentTemplate" => "classes/general/component_template.php",
		"CComponentUtil" => "classes/general/component_util.php",
		"CControllerClient" => "classes/general/controller_member.php",
		"PHPParser" => "classes/general/php_parser.php",
		"CDiskQuota" => "classes/".$DBType."/quota.php",
		"CEventLog" => "classes/general/event_log.php",
		"CEventMain" => "classes/general/event_log.php",
		"CAdminFileDialog" => "classes/general/file_dialog.php",
		"WLL_User" => "classes/general/liveid.php",
		"WLL_ConsentToken" => "classes/general/liveid.php",
		"WindowsLiveLogin" => "classes/general/liveid.php",
		"CAllFile" => "classes/general/file.php",
		"CFile" => "classes/".$DBType."/file.php",
		"CTempFile" => "classes/general/file_temp.php",
		"CFavorites" => "classes/".$DBType."/favorites.php",
		"CUserOptions" => "classes/general/user_options.php",
		"CGridOptions" => "classes/general/grids.php",
		"CUndo" => "/classes/general/undo.php",
		"CAutoSave" => "/classes/general/undo.php",
		"CRatings" => "classes/".$DBType."/ratings.php",
		"CRatingsComponentsMain" => "classes/".$DBType."/ratings_components.php",
		"CRatingRule" => "classes/general/rating_rule.php",
		"CRatingRulesMain" => "classes/".$DBType."/rating_rules.php",
		"CTopPanel" => "public/top_panel.php",
		"CEditArea" => "public/edit_area.php",
		"CComponentPanel" => "public/edit_area.php",
		"CTextParser" => "classes/general/textparser.php",
		"CPHPCacheFiles" => "classes/general/cache_files.php",
		"CDataXML" => "classes/general/xml.php",
		"CXMLFileStream" => "classes/general/xml.php",
		"CRsaProvider" => "classes/general/rsasecurity.php",
		"CRsaSecurity" => "classes/general/rsasecurity.php",
		"CRsaBcmathProvider" => "classes/general/rsabcmath.php",
		"CRsaOpensslProvider" => "classes/general/rsaopenssl.php",
		"CASNReader" => "classes/general/asn.php",
		"CBXShortUri" => "classes/".$DBType."/short_uri.php",
		"CFinder" => "classes/general/finder.php",
		"CAccess" => "classes/general/access.php",
		"CAuthProvider" => "classes/general/authproviders.php",
		"IProviderInterface" => "classes/general/authproviders.php",
		"CGroupAuthProvider" => "classes/general/authproviders.php",
		"CUserAuthProvider" => "classes/general/authproviders.php",
		"CTableSchema" => "classes/general/table_schema.php",
		"CCSVData" => "classes/general/csv_data.php",
		"CSmile" => "classes/general/smile.php",
		"CSmileGallery" => "classes/general/smile.php",
		"CSmileSet" => "classes/general/smile.php",
		"CGlobalCounter" => "classes/general/global_counter.php",
		"CUserCounter" => "classes/".$DBType."/user_counter.php",
		"CUserCounterPage" => "classes/".$DBType."/user_counter.php",
		"CHotKeys" => "classes/general/hot_keys.php",
		"CHotKeysCode" => "classes/general/hot_keys.php",
		"CBXSanitizer" => "classes/general/sanitizer.php",
		"CBXArchive" => "classes/general/archive.php",
		"CAdminNotify" => "classes/general/admin_notify.php",
		"CBXFavAdmMenu" => "classes/general/favorites.php",
		"CAdminInformer" => "classes/general/admin_informer.php",
		"CSiteCheckerTest" => "classes/general/site_checker.php",
		"CSqlUtil" => "classes/general/sql_util.php",
		"CHTMLPagesCache" => "classes/general/cache_html.php",
		"CFileUploader" => "classes/general/uploader.php",
		"LPA" => "classes/general/lpa.php",
		"CAdminFilter" => "interface/admin_filter.php",
		"CAdminList" => "interface/admin_list.php",
		"CAdminListRow" => "interface/admin_list.php",
		"CAdminTabControl" => "interface/admin_tabcontrol.php",
		"CAdminForm" => "interface/admin_form.php",
		"CAdminFormSettings" => "interface/admin_form.php",
		"CAdminTabControlDrag" => "interface/admin_tabcontrol_drag.php",
		"CAdminDraggableBlockEngine" => "interface/admin_tabcontrol_drag.php",
		"CJSPopup" => "interface/jspopup.php",
		"CJSPopupOnPage" => "interface/jspopup.php",
		"CAdminCalendar" => "interface/admin_calendar.php",
		"CAdminViewTabControl" => "interface/admin_viewtabcontrol.php",
		"CAdminTabEngine" => "interface/admin_tabengine.php",
	)
);

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$DBType."/agent.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$DBType."/user.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$DBType."/event.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/menu.php");
AddEventHandler("main", "OnAfterEpilog", array("\\Bitrix\\Main\\Data\\ManagedCache", "finalize"));
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$DBType."/usertype.php");

if(file_exists(($_fname = $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/update_db_updater.php")))
{
	$US_HOST_PROCESS_MAIN = False;
	include($_fname);
}

$GLOBALS["APPLICATION"]->AddJSKernelInfo(
	'main',
	array(
		'/bitrix/js/main/core/core.js', '/bitrix/js/main/core/core_ajax.js', '/bitrix/js/main/json/json2.min.js',
		'/bitrix/js/main/core/core_ls.js', '/bitrix/js/main/core/core_popup.js', '/bitrix/js/main/core/core_tooltip.js',
		'/bitrix/js/main/core/core_date.js','/bitrix/js/main/core/core_timer.js', '/bitrix/js/main/core/core_fx.js',
		'/bitrix/js/main/core/core_window.js', '/bitrix/js/main/core/core_autosave.js', '/bitrix/js/main/rating_like.js',
		'/bitrix/js/main/session.js', '/bitrix/js/main/dd.js', '/bitrix/js/main/utils.js',
		'/bitrix/js/main/core/core_dd.js', '/bitrix/js/main/core/core_webrtc.js'
	)
);


$GLOBALS["APPLICATION"]->AddCSSKernelInfo(
	'main',
	array(
		'/bitrix/js/main/core/css/core.css', '/bitrix/js/main/core/css/core_popup.css',
		'/bitrix/js/main/core/css/core_tooltip.css', '/bitrix/js/main/core/css/core_date.css'
	)
);

//Park core uploader
$GLOBALS["APPLICATION"]->AddJSKernelInfo(
	'coreuploader',
	array(
		'/bitrix/js/main/core/core_uploader/common.js',
		'/bitrix/js/main/core/core_uploader/uploader.js',
		'/bitrix/js/main/core/core_uploader/file.js',
		'/bitrix/js/main/core/core_uploader/queue.js',
	)
);

if(file_exists(($_fname = $_SERVER["DOCUMENT_ROOT"]."/bitrix/init.php")))
	include_once($_fname);

if(($_fname = getLocalPath("php_interface/init.php", BX_PERSONAL_ROOT)) !== false)
	include_once($_SERVER["DOCUMENT_ROOT"].$_fname);

if(($_fname = getLocalPath("php_interface/".SITE_ID."/init.php", BX_PERSONAL_ROOT)) !== false)
	include_once($_SERVER["DOCUMENT_ROOT"].$_fname);

if(!defined("BX_FILE_PERMISSIONS"))
	define("BX_FILE_PERMISSIONS", 0644);
if(!defined("BX_DIR_PERMISSIONS"))
	define("BX_DIR_PERMISSIONS", 0755);

//global var, is used somewhere
$GLOBALS["sDocPath"] = $GLOBALS["APPLICATION"]->GetCurPage();

if((!(defined("STATISTIC_ONLY") && STATISTIC_ONLY && substr($GLOBALS["APPLICATION"]->GetCurPage(), 0, strlen(BX_ROOT."/admin/"))!=BX_ROOT."/admin/")) && COption::GetOptionString("main", "include_charset", "Y")=="Y" && strlen(LANG_CHARSET)>0)
	header("Content-Type: text/html; charset=".LANG_CHARSET);

if(COption::GetOptionString("main", "set_p3p_header", "Y")=="Y")
	header("P3P: policyref=\"/bitrix/p3p.xml\", CP=\"NON DSP COR CUR ADM DEV PSA PSD OUR UNR BUS UNI COM NAV INT DEM STA\"");

//licence key
$LICENSE_KEY = "";
if(file_exists(($_fname = $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/license_key.php")))
	include($_fname);
if($LICENSE_KEY == "" || strtoupper($LICENSE_KEY) == "DEMO")
	define("LICENSE_KEY", "DEMO");
else
	define("LICENSE_KEY", $LICENSE_KEY);

header("X-Powered-CMS: Bitrix Site Manager (".(LICENSE_KEY == "DEMO"? "DEMO" : md5("BITRIX".LICENSE_KEY."LICENCE")).")");
if (COption::GetOptionString("main", "update_devsrv", "") == "Y")
	header("X-DevSrv-CMS: Bitrix");

define("BX_CRONTAB_SUPPORT", defined("BX_CRONTAB"));

if(COption::GetOptionString("main", "check_agents", "Y")=="Y")
{
	define("START_EXEC_AGENTS_1", microtime());
	$GLOBALS["BX_STATE"] = "AG";
	$GLOBALS["DB"]->StartUsingMasterOnly();
	CAgent::CheckAgents();
	$GLOBALS["DB"]->StopUsingMasterOnly();
	define("START_EXEC_AGENTS_2", microtime());
	$GLOBALS["BX_STATE"] = "PB";
}

//session initialization
ini_set("session.cookie_httponly", "1");

if($domain = $GLOBALS["APPLICATION"]->GetCookieDomain())
	ini_set("session.cookie_domain", $domain);

if(COption::GetOptionString("security", "session", "N") === "Y"	&& CModule::IncludeModule("security"))
	CSecuritySession::Init();

session_start();

foreach (GetModuleEvents("main", "OnPageStart", true) as $arEvent)
	ExecuteModuleEventEx($arEvent);

//define global user object
$GLOBALS["USER"] = new CUser;

//session control from group policy
$arPolicy = $GLOBALS["USER"]->GetSecurityPolicy();
$currTime = time();
if(
	(
		//IP address changed
		$_SESSION['SESS_IP']
		&& strlen($arPolicy["SESSION_IP_MASK"])>0
		&& (
			(ip2long($arPolicy["SESSION_IP_MASK"]) & ip2long($_SESSION['SESS_IP']))
			!=
			(ip2long($arPolicy["SESSION_IP_MASK"]) & ip2long($_SERVER['REMOTE_ADDR']))
		)
	)
	||
	(
		//session timeout
		$arPolicy["SESSION_TIMEOUT"]>0
		&& $_SESSION['SESS_TIME']>0
		&& $currTime-$arPolicy["SESSION_TIMEOUT"]*60 > $_SESSION['SESS_TIME']
	)
	||
	(
		//session expander control
		isset($_SESSION["BX_SESSION_TERMINATE_TIME"])
		&& $_SESSION["BX_SESSION_TERMINATE_TIME"] > 0
		&& $currTime > $_SESSION["BX_SESSION_TERMINATE_TIME"]
	)
	||
	(
		//signed session
		isset($_SESSION["BX_SESSION_SIGN"])
		&& $_SESSION["BX_SESSION_SIGN"] <> bitrix_sess_sign()
	)
	||
	(
		//session manually expired, e.g. in $User->LoginHitByHash
	isSessionExpired()
	)
)
{
	$_SESSION = array();
	@session_destroy();

	//session_destroy cleans user sesssion handles in some PHP versions
	//see http://bugs.php.net/bug.php?id=32330 discussion
	if(COption::GetOptionString("security", "session", "N") === "Y"	&& CModule::IncludeModule("security"))
		CSecuritySession::Init();

	session_id(md5(uniqid(rand(), true)));
	session_start();
	$GLOBALS["USER"] = new CUser;
}
$_SESSION['SESS_IP'] = $_SERVER['REMOTE_ADDR'];
$_SESSION['SESS_TIME'] = time();
if(!isset($_SESSION["BX_SESSION_SIGN"]))
	$_SESSION["BX_SESSION_SIGN"] = bitrix_sess_sign();

//session control from security module
if(
	(COption::GetOptionString("main", "use_session_id_ttl", "N") == "Y")
	&& (COption::GetOptionInt("main", "session_id_ttl", 0) > 0)
	&& !defined("BX_SESSION_ID_CHANGE")
)
{
	if(!array_key_exists('SESS_ID_TIME', $_SESSION))
	{
		$_SESSION['SESS_ID_TIME'] = $_SESSION['SESS_TIME'];
	}
	elseif(($_SESSION['SESS_ID_TIME'] + COption::GetOptionInt("main", "session_id_ttl")) < $_SESSION['SESS_TIME'])
	{
		if(COption::GetOptionString("security", "session", "N") === "Y" && CModule::IncludeModule("security"))
		{
			CSecuritySession::UpdateSessID();
		}
		else
		{
			session_regenerate_id();
		}
		$_SESSION['SESS_ID_TIME'] = $_SESSION['SESS_TIME'];
	}
}

define("BX_STARTED", true);

if (isset($_SESSION['BX_ADMIN_LOAD_AUTH']))
{
	define('ADMIN_SECTION_LOAD_AUTH', 1);
	unset($_SESSION['BX_ADMIN_LOAD_AUTH']);
}

if(!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS!==true)
{
	$bLogout = isset($_REQUEST["logout"]) && (strtolower($_REQUEST["logout"]) == "yes");

	if($bLogout && $GLOBALS["USER"]->IsAuthorized())
	{
		$GLOBALS["USER"]->Logout();
		LocalRedirect($GLOBALS["APPLICATION"]->GetCurPageParam('', array('logout')));
	}

	// authorize by cookies
	if(!$GLOBALS["USER"]->IsAuthorized())
	{
		$GLOBALS["USER"]->LoginByCookies();
	}

	$arAuthResult = false;

	//http basic and digest authorization
	if(($httpAuth = $GLOBALS["USER"]->LoginByHttpAuth()) !== null)
	{
		$arAuthResult = $httpAuth;
		$GLOBALS["APPLICATION"]->SetAuthResult($arAuthResult);
	}

	//Authorize user from authorization html form
	if(isset($_REQUEST["AUTH_FORM"]) && $_REQUEST["AUTH_FORM"] <> '')
	{
		$bRsaError = false;
		if(COption::GetOptionString('main', 'use_encrypted_auth', 'N') == 'Y')
		{
			//possible encrypted user password
			$sec = new CRsaSecurity();
			if(($arKeys = $sec->LoadKeys()))
			{
				$sec->SetKeys($arKeys);
				$errno = $sec->AcceptFromForm(array('USER_PASSWORD', 'USER_CONFIRM_PASSWORD'));
				if($errno == CRsaSecurity::ERROR_SESS_CHECK)
					$arAuthResult = array("MESSAGE"=>GetMessage("main_include_decode_pass_sess"), "TYPE"=>"ERROR");
				elseif($errno < 0)
					$arAuthResult = array("MESSAGE"=>GetMessage("main_include_decode_pass_err", array("#ERRCODE#"=>$errno)), "TYPE"=>"ERROR");

				if($errno < 0)
					$bRsaError = true;
			}
		}

		if($bRsaError == false)
		{
			if(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
				$USER_LID = LANG;
			else
				$USER_LID = false;

			if($_REQUEST["TYPE"] == "AUTH")
			{
				$arAuthResult = $GLOBALS["USER"]->Login($_REQUEST["USER_LOGIN"], $_REQUEST["USER_PASSWORD"], $_REQUEST["USER_REMEMBER"]);
			}
			elseif($_REQUEST["TYPE"] == "OTP")
			{
				$arAuthResult = $GLOBALS["USER"]->LoginByOtp($_REQUEST["USER_OTP"], $_REQUEST["OTP_REMEMBER"], $_REQUEST["captcha_word"], $_REQUEST["captcha_sid"]);
			}
			elseif($_REQUEST["TYPE"] == "SEND_PWD")
			{
				$arAuthResult = CUser::SendPassword($_REQUEST["USER_LOGIN"], $_REQUEST["USER_EMAIL"], $USER_LID, $_REQUEST["captcha_word"], $_REQUEST["captcha_sid"]);
			}
			elseif($_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST["TYPE"] == "CHANGE_PWD")
			{
				$arAuthResult = $GLOBALS["USER"]->ChangePassword($_REQUEST["USER_LOGIN"], $_REQUEST["USER_CHECKWORD"], $_REQUEST["USER_PASSWORD"], $_REQUEST["USER_CONFIRM_PASSWORD"], $USER_LID, $_REQUEST["captcha_word"], $_REQUEST["captcha_sid"]);
			}
			elseif(COption::GetOptionString("main", "new_user_registration", "N") == "Y" && $_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST["TYPE"] == "REGISTRATION" && (!defined("ADMIN_SECTION") || ADMIN_SECTION!==true))
			{
				$arAuthResult = $GLOBALS["USER"]->Register($_REQUEST["USER_LOGIN"], $_REQUEST["USER_NAME"], $_REQUEST["USER_LAST_NAME"], $_REQUEST["USER_PASSWORD"], $_REQUEST["USER_CONFIRM_PASSWORD"], $_REQUEST["USER_EMAIL"], $USER_LID, $_REQUEST["captcha_word"], $_REQUEST["captcha_sid"]);
			}

			if($_REQUEST["TYPE"] == "AUTH" || $_REQUEST["TYPE"] == "OTP")
			{
				//special login form in the control panel
				if($arAuthResult === true && defined('ADMIN_SECTION') && ADMIN_SECTION === true)
				{
					//store cookies for next hit (see CMain::GetSpreadCookieHTML())
					$GLOBALS["APPLICATION"]->StoreCookies();
					$_SESSION['BX_ADMIN_LOAD_AUTH'] = true;
					echo '<script type="text/javascript">window.onload=function(){top.BX.AUTHAGENT.setAuthResult(false);};</script>';
					die();
				}
			}
		}
		$GLOBALS["APPLICATION"]->SetAuthResult($arAuthResult);
	}
	elseif(!$GLOBALS["USER"]->IsAuthorized())
	{
		//Authorize by unique URL
		$GLOBALS["USER"]->LoginHitByHash();
	}
}

//application password scope control
if(($applicationID = $GLOBALS["USER"]->GetParam("APPLICATION_ID")) !== null)
{
	$appManager = \Bitrix\Main\Authentication\ApplicationManager::getInstance();
	if($appManager->checkScope($applicationID) !== true)
	{
		$event = new \Bitrix\Main\Event("main", "onApplicationScopeError", Array('APPLICATION_ID' => $applicationID));
		$event->send();

		CHTTP::SetStatus("403 Forbidden");
		die();
	}
}

//define the site template
if(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
{
	$siteTemplate = "";
	if(is_string($_REQUEST["bitrix_preview_site_template"]) && $_REQUEST["bitrix_preview_site_template"] <> "" && $GLOBALS["USER"]->CanDoOperation('view_other_settings'))
	{
		//preview of site template
		$signer = new Bitrix\Main\Security\Sign\Signer();
		try
		{
			//protected by a sign
			$requestTemplate = $signer->unsign($_REQUEST["bitrix_preview_site_template"], "template_preview".bitrix_sessid());

			$aTemplates = CSiteTemplate::GetByID($requestTemplate);
			if($template = $aTemplates->Fetch())
			{
				$siteTemplate = $template["ID"];

				//preview of unsaved template
				if(isset($_GET['bx_template_preview_mode']) && $_GET['bx_template_preview_mode'] == 'Y' && $GLOBALS["USER"]->CanDoOperation('edit_other_settings'))
				{
					define("SITE_TEMPLATE_PREVIEW_MODE", true);
				}
			}
		}
		catch(\Bitrix\Main\Security\Sign\BadSignatureException $e)
		{
		}
	}
	if($siteTemplate == "")
	{
		$siteTemplate = CSite::GetCurTemplate();
	}
	define("SITE_TEMPLATE_ID", $siteTemplate);
	define("SITE_TEMPLATE_PATH", getLocalPath('templates/'.SITE_TEMPLATE_ID, BX_PERSONAL_ROOT));
}

//magic parameters: show page creation time
if(isset($_GET["show_page_exec_time"]))
{
	if($_GET["show_page_exec_time"]=="Y" || $_GET["show_page_exec_time"]=="N")
		$_SESSION["SESS_SHOW_TIME_EXEC"] = $_GET["show_page_exec_time"];
}

//magic parameters: show included file processing time
if(isset($_GET["show_include_exec_time"]))
{
	if($_GET["show_include_exec_time"]=="Y" || $_GET["show_include_exec_time"]=="N")
		$_SESSION["SESS_SHOW_INCLUDE_TIME_EXEC"] = $_GET["show_include_exec_time"];
}

//magic parameters: show include areas
if(isset($_GET["bitrix_include_areas"]) && $_GET["bitrix_include_areas"] <> "")
	$GLOBALS["APPLICATION"]->SetShowIncludeAreas($_GET["bitrix_include_areas"]=="Y");

//magic sound
if($GLOBALS["USER"]->IsAuthorized())
{
	$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
	if(!isset($_COOKIE[$cookie_prefix.'_SOUND_LOGIN_PLAYED']))
		$GLOBALS["APPLICATION"]->set_cookie('SOUND_LOGIN_PLAYED', 'Y', 0);
}

//magic cache
\Bitrix\Main\Page\Frame::shouldBeEnabled();

//magic short URI
if(defined("BX_CHECK_SHORT_URI") && BX_CHECK_SHORT_URI && CBXShortUri::CheckUri())
{
	//local redirect inside
	die();
}

foreach(GetModuleEvents("main", "OnBeforeProlog", true) as $arEvent)
	ExecuteModuleEventEx($arEvent);

if((!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS!==true) && (!defined("NOT_CHECK_FILE_PERMISSIONS") || NOT_CHECK_FILE_PERMISSIONS!==true))
{
	$real_path = $request->getScriptFile();

	if(!$GLOBALS["USER"]->CanDoFileOperation('fm_view_file', array(SITE_ID, $real_path)) || (defined("NEED_AUTH") && NEED_AUTH && !$GLOBALS["USER"]->IsAuthorized()))
	{
		/** @noinspection PhpUndefinedVariableInspection */
		if($GLOBALS["USER"]->IsAuthorized() && $arAuthResult["MESSAGE"] == '')
			$arAuthResult = array("MESSAGE"=>GetMessage("ACCESS_DENIED").' '.GetMessage("ACCESS_DENIED_FILE", array("#FILE#"=>$real_path)), "TYPE"=>"ERROR");

		if(defined("ADMIN_SECTION") && ADMIN_SECTION==true)
		{
			if ($_REQUEST["mode"]=="list" || $_REQUEST["mode"]=="settings")
			{
				echo "<script>top.location='".$GLOBALS["APPLICATION"]->GetCurPage()."?".DeleteParam(array("mode"))."';</script>";
				die();
			}
			elseif ($_REQUEST["mode"]=="frame")
			{
				echo "<script type=\"text/javascript\">
					var w = (opener? opener.window:parent.window);
					w.location.href='".$GLOBALS["APPLICATION"]->GetCurPage()."?".DeleteParam(array("mode"))."';
				</script>";
				die();
			}
			elseif(defined("MOBILE_APP_ADMIN") && MOBILE_APP_ADMIN==true)
			{
				echo json_encode(Array("status"=>"failed"));
				die();
			}
		}

		/** @noinspection PhpUndefinedVariableInspection */
		$GLOBALS["APPLICATION"]->AuthForm($arAuthResult);
	}
}

       //Do not remove this

if(isset($REDIRECT_STATUS) && $REDIRECT_STATUS==404)
{
	if(COption::GetOptionString("main", "header_200", "N")=="Y")
		CHTTP::SetStatus("200 OK");
}
