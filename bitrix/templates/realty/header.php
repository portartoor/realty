<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
IncludeTemplateLangFile(__FILE__);
$filename = $_SERVER['DOCUMENT_ROOT']."/upload/realty_status.php";
if(/*!CSite::InGroup(array(1))&&*/file_exists($filename)&&file_get_contents($filename)=="N"){
	die("<h2 style='color:red;'>База недвижиомсти закрыта,<br>
проводятся регламентные работы</h2>");
}
use Bitrix\Main\Page\AssetShowTargetType;

$platform = "android";
if (CModule::IncludeModule("mobileapp"))
{
	$platform = CMobile::$platform;
	if(!defined("SKIP_MOBILEAPP_INIT"))
		CMobile::Init();
}
else
{
	die();
}

?><!DOCTYPE html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<link href="<?=SITE_TEMPLATE_PATH?>/css/reset.css" type="text/css" rel="stylesheet">
	<?$APPLICATION->ShowHead();?>
	<title><?$APPLICATION->ShowTitle()?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
	<script src="<?=SITE_TEMPLATE_PATH?>/js/jquery-1.12.1.min.js"></script>
	<script src="<?=SITE_TEMPLATE_PATH?>/js/main.js"></script>
	<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/js/fancybox/jquery.fancybox.css?v=2.1.5" type="text/css" media="screen" />
	<script type="text/javascript" src="<?=SITE_TEMPLATE_PATH?>/js/fancybox/jquery.fancybox.pack.js?v=2.1.5"></script>
	<?/*<script type="text/javascript" src="<?=SITE_TEMPLATE_PATH?>/js/jquery.maskedinput.js"></script><?*/?>
</head>
<body>
<?
if ( CSite::InGroup( array(1) ) )$APPLICATION->ShowPanel = true;
$APPLICATION->ShowPanel();?>
<header><div class="h_ins"><?$APPLICATION->ShowTitle()?></div><div class="menu_main_contant_bg"></div><a href="#" class="menu_main"></a>
		<div id="menu_main_contant"><a href="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/" class="punkt_5"><span>Главная</span></a>
			<a href="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/new/" class="punkt_1"><span>Добавить новую заявку</span></a>
			<a href="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/search/?map=1" class="punkt_2"><span>Поиск</span></a>
			<a href="javascript:scroll_to_0()" class="punkt_3 bx-notifier-notify" onclick="if (BX.IM) { BXIM.openMessenger(); }"><span>Уведомления</span>
			</a><a href="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/my/" class="punkt_4"><span>Мои заявки</span></a>
			<a href="/company/personal/user/<?
			global $USER;
			echo $USER->GetId();
			?>/tasks/" class="punkt_5"><span>Задачи</span></a>
			<?/*if($APPLICATION->GetCurDir()=="/realty/new/"&&isset($_GET["step"])&&isset($_GET["REQUEST_ID"])):?>
				<a href="/realty/new/?REQUEST_ID=<?=$_GET["REQUEST_ID"]?>&step=<?=(intval($_GET["step"])-1)?>" class="punkt_6"><span>Назад</span></a>
				<a href="/realty/new/?REQUEST_ID=<?=$_GET["REQUEST_ID"]?>&step=<?=(intval($_GET["step"])+1)?>" class="punkt_7"><span>Далее</span></a>
			<?endif;*/?>
		</div>
	<?if($APPLICATION->GetCurDir()=="/realty/new/"||$APPLICATION->GetCurDir()=="/mobile/realty/new/"/*&&isset($_GET["nw"])*/):
			$step = 1; 
			if(isset($_GET["step"]))$step=intval($_GET["step"]);?>
			<div class="quick_perehod_btn"><div class="ul_div"><a href="javascript:void(0);" class="quick_perehod_1 <?=($step==1)?"active":""?>" data-step="1"><span></span></a><a class="quick_perehod_2 <?=($step==2)?"active":""?>" href="javascript:void(0);" data-step="2"><span></span></a><a href="javascript:void(0);" class="quick_perehod_3 <?=($step==3)?"active":""?>" data-step="3"><span></span></a><a class="quick_perehod_4 <?=($step==4)?"active":""?>" href="javascript:void(0);" data-step="4"><span></span></a><a href="javascript:void(0);" class="quick_perehod_5 <?=($step==5)?"active":""?>" data-step="5"><span></span></a></div></div>
			<?
			if($step==5/*&&($USER->GetId()==752||$USER->GetId()==480)*/){
				?>
				<div class="ul_div_photo_mode"><a href="#" class="photo_mode_1 quick_perehod_5 active" data-step="5"><span style="background:none;">Стандарт</span></a><a href="javascript:void(0);" class="photo_mode_2" data-step="5"><span>Фотограф</span></a></div>
				<?
			}
			?>
		<?endif;?>
</header>
<div id="content">