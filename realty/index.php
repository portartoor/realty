<?
//Проверяем какой шаблон сайта сейчас используется
//Чтобы понять откуда страницу открывают. 
//Из приложения или через браузер
if ('SITE_TEMPLATE_ID' == "mobile_app")
	$APPLICATION->SetAdditionalCSS("/bitrix/templates/realty/template_styles_for_app.css");
else
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	$APPLICATION->SetTitle("Главный экран");	
}
global $USER;
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
$Project = new Rights();
$postfix  = $Project->get_postfix();	
$agents_hblock = $Project->get_agents_hb_id();
	$user_code="";
	$arr_q = HlBlockElement::GetList($agents_hblock,array(),array("UF_BITRIX_USER".$postfix=>$USER->GetID()),array(),1);
	if($arr_s_client = $arr_q->Fetch()){
		$user_code = $arr_s_client["UF_AGENT_ID".$postfix];
	}
	else {
		$rsUser = CUser::GetByID($USER->GetID());
		$arUser = $rsUser->Fetch();	
		$arr_q = HlBlockElement::GetList($agents_hblock,array(),array(array( 
															"LOGIC" => "AND",
															"UF_AGENT_NAME".$postfix => "%".$arUser["NAME"]."%",
															"UF_AGENT_NAME".$postfix => "%".$arUser["LAST_NAME"]."%"
															),
													"UF_BITRIX_USER".$postfix=>NULL),
														array(),1);
		if(strlen($arUser["NAME"])>3&&strlen($arUser["LAST_NAME"])>3&&$arr_s_client = $arr_q->Fetch())
		{
			$res = HlBlockElement::Update($agents_hblock,$arr_s_client["ID"],Array("UF_BITRIX_USER".$postfix=>$USER->GetID()));
			$user_code = $arr_s_client["UF_AGENT_ID".$postfix];
		}
	}
	if(in_array($USER->GetID(),Array(720,752)))
		$user_code = "new_user_".$USER->GetID();
	if(false&&($user_code==""||(strpos($user_code,"new_user_")!==FALSE&&!in_array($user_code,Array("new_user_720","new_user_752")))))
	{
		echo "<div class=\"cover_all_window\"></div><div id=\"reset_all\"><h3 >Пользователь не зарегистрирован в МПА. Обратитесь к администратору.<h3></div>";
	}
	else
	{
?><div class="main_menu">
	<a href="new/" class="punkt_1"><span>Добавить новую заявку</span></a>
	<a href="search/?map=1" class="punkt_2"><span>Поиск</span></a>
	<a href="/" class="punkt_3"><span>Уведомления (Лента)</span></ai>
	<a href="my/" class="punkt_4"><span>Мои заявки</span></a>
	<a href="/company/personal/user/<?
			global $USER;
			echo $USER->GetId();
			?>/tasks/" class="punkt_5"><span>Задачи</span></a>
	<?if($USER->IsAdmin()):?>
	<a href="new/step_5.php" class="punkt_4"><span>Тест</span></a>
	<?endif;?>
</div>
<!--<a class="current_request" href="my/">
	<span>Количество текущих заявок</span>
	<div class="current_request_abs">
		<?/*	$arr_q = HlBlockElement::GetList(5,array(),array("UF_BITRIX_USER"=>$USER->GetID()),array(),1);
			if($arr_s_client = $arr_q->Fetch()){
				$user_code = $arr_s_client["UF_AGENT_ID"];
			}?>
		<?  $request = HlBlockElement::GetList(2,array(),array("UF_AGENT"=>$user_code,"UF_INNER_STATUS"=>Array(0,1,2)),array(),100);
			echo $request->SelectedRowsCount();*/
		?>
	</div>
</a>-->
<?/*
if(!preg_match("/mobile/i",$_SERVER["HTTP_USER_AGENT"])){
	$APPLICATION->IncludeComponent("bitrix:system.auth.form", "main", Array(
		"COMPONENT_TEMPLATE" => ".default",
			"FORGOT_PASSWORD_URL" => "/?forgot_password=yes",	// Страница забытого пароля
			"PROFILE_URL" => "/company/personal/user/",	// Страница профиля
			"REGISTER_URL" => "",	// Страница регистрации
			"SHOW_ERRORS" => "N",	// Показывать ошибки
		),
		false
	);
}*/
	}
if (SITE_TEMPLATE_ID != "mobile_app")require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>