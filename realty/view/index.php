<?
if(isset($_GET["ajax"]))
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include.php");
else
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
}
require($_SERVER["DOCUMENT_ROOT"]."/libs/realty_class.php");?>
<script>
$(document).ready(function(){
		readonly();
		<?if(isset($_GET["after_add_interes"])):?>$(".star").click();<?endif;?>
});
</script>
<link rel="stylesheet" type="text/css" href="<?=SITE_TEMPLATE_PATH?>/js/jcarousel/jcarousel.basic.css"/>
<script type="text/javascript" src="<?=SITE_TEMPLATE_PATH?>/js/jcarousel/jquery.jcarousel.js"></script>
<script type="text/javascript" src="<?=SITE_TEMPLATE_PATH?>/js/jcarousel/jcarousel.basic.js"></script>
<?
	$sayavka_id = isset($_GET["REQUEST_ID"])?$_GET["REQUEST_ID"]:"";
	global $USER;
	require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
	$Project = new Rights();
	$data_res = $Project->get_requests_file();
	$postfix  = $Project->get_postfix();
	$agents_hblock = $Project->get_agents_hb_id();
	$client_hblock = $Project->get_clients_hb_id();
	$request = HlBlockElement::GetList($data_res["hblock"],array(),array("ID"=>$sayavka_id),array(),1);
	$request_data = $request->Fetch();
	if(!empty($request_data))
	{
		$sayavka_id=$request_data["ID"];
		foreach($request_data as $k=>$v)
		{
			$request_data[str_replace($postfix,"",$k)]=$v;
		}
	}
	else die("Нет такого объекта");
	$request = HlBlockElement::GetList($agents_hblock,array(),array("UF_AGENT_ID".$postfix=>$request_data["UF_AGENT"]),array(),1);
	$agent = $request->Fetch();
	foreach($agent as $k=>$v)
	{
		$agent[str_replace($postfix,"",$k)]=$v;
	}
	//print_r($agent);
	$i_m_active = false;
	$coordinate = false;
	if($agent["UF_BITRIX_USER"]!=$USER->GetID()&&$request_data["UF_COORDINATOR"]==1)
	{
		$call = HlBlockElement::GetList($Project->get_call_hb_id(),array(),array("UF_REQ_ID".$postfix=>$request_data["ID"],"UF_ACT".$postfix=>1),array(),1);
		if($arr_call = $call->Fetch()){
			$request_ag = HlBlockElement::GetList($agents_hblock,array(),array("UF_BITRIX_USER".$postfix=>$USER->GetID()),array(),1);
			$agent_me = $request_ag->Fetch();
			$user_code = $agent_me["UF_AGENT_ID".$postfix];
			$coordinate = true;
			if($arr_call["UF_AGENTCODE_C".$postfix]==$user_code)
			{
				$i_m_active=true;
			}					
		}
	}
	?>
	<?if(($agent["UF_BITRIX_USER"]==$USER->GetID()&&!isset($_GET["interes"]))||$USER->GetID()==752||$USER->GetId()==480):?>
		<?
		$request = HlBlockElement::GetList($Project->get_interes(), array(), array(/*array(*/"UF_REQUEST_S".$postfix=>$sayavka_id/*,"UF_REQUEST_F".$postfix=>$sayavka_id,"LOGIC" => "OR")*/,"UF_ACTIVE".$postfix=>1), Array(), 100);
		$cnt=intval($request->SelectedRowsCount());
		?>
		<div class="menu_action_block">
			<!--<a href="#" class="call"></a>-->
			<a href="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/new/?REQUEST_ID=<?=$sayavka_id?>" class="edit"></a>
			<a href="#" class="first active"></a>
			<a href="#" class="like"><?=$cnt?></a>
			<a href="#" class="star"></a>
			<a href="#" class="change_status"></a>
			<a href="#" class="close_request"></a>
		</div>
	<?endif;?>
	<?if(isset($_GET["interes"])):?>
		<?
		$interested = 0;
		$request_id_s = $_GET["interes"];
		$dbResultObj = HlBlockElement::GetList($Project->get_interes(), array(), array(array(array("UF_REQUEST_S".$postfix=>$request_id_s,"UF_REQUEST_F".$postfix=>$request_data["ID"]),array("UF_REQUEST_S".$postfix=>$request_data["ID"],"UF_REQUEST_F".$postfix=>$request_id_s),"LOGIC" => "OR"),"UF_ACTIVE".$postfix=>1), array(), 1);
		if($arElement = $dbResultObj->Fetch())
		{	
			$interested=1;
		}?>
		<!--<div class="plashka_interes" style="width: 79px;">-->
		<div class="menu_action_block">
			<a href="#" data-agent="<?=$request_data["UF_AGENT"]?>" data-f="<?=$_GET["interes"]?>" data-t="<?=$request_data["ID"]?>" data-code="<?=($request_data["UF_ID"])?$request_data["UF_ID"]:"*".$request_data["ID"]?>" data-ava="<?=$request_data["UF_PHOTO_PREVIEW"]?>" class="like_1 <?=($interested==1)?"active":""?>" onclick="like_obj(this);return false;"></a>
			<a href="javascript:void(0)" class="mail <?=($interested==1)?"active":""?>" onclick="open_chat_realty('<?=$request_data["UF_AGENT"]?>',<?=$_GET["interes"]?>,<?=$request_data["ID"]?>,'<?=($request_data["UF_ID"]!="")?$request_data["UF_ID"]:"*".$request_data["ID"]?>','<?=$request_data["UF_PHOTO_PREVIEW"]?>');return false;"></a>
		</div>
		<!--</div>-->
	<?endif;?>
	<input type="hidden" name="REQUEST_ID" value="<?=$sayavka_id?>">
	<input type="hidden" name="REQUEST_CODE" value="<?=$request_data["UF_ID"]?>">
	<?
	/*if($request_data["UF_OPERATION_TYPE"]==143||$request_data["UF_OPERATION_TYPE"]==144)
	{
		$ty =  Helper_realty::$array_for_filter_s[0];
		$ty = array_merge($ty,Array("UF_CONTRAGENT","UF_SOURCE","UF_STATUS","UF_OPERATION_TYPE","UF_REALTY_TYPE","UF_OBJ_TYPE"));
	}
	else
	{
		$export_arr = HlBlockElement::GetList(3,array(),array("UF_OBJ_TYPE_ID"=>$request_data["UF_OBJ_TYPE"]),array(),1);
		$ty = Array();
		if($arr = $export_arr->Fetch()){
			$class = $arr["UF_OBJ_TYPE_CLASS"];
			$ty = Helper_realty::$array_for_filter[$class];
		}
		$ty = array_merge($ty,Array("UF_CONTRAGENT","UF_SOURCE","UF_STATUS","UF_OPERATION_TYPE","UF_REALTY_TYPE","UF_OBJ_TYPE","UF_CITY_ID","UF_ADDR_STREET"));
	}
	if($request_data["UF_REALTY_TYPE"]==6)
	{
		$ty = array_diff($ty,Array("UF_ADDR_FLAT","UF_ETAGE_COUNT"));
		if(in_array($request_data["UF_OPERATION_TYPE"],array(57,144)))
			$ty = array_diff($ty,Array("UF_CLIENT_PRICE"));
	}*/
	$ty = Helper_realty::get_array_for_filter(1);

	$client = Array();
	if($sayavka_id!=""&&($request_data["UF_CONTRAGENT"])!="")
	{
		$client_request = HlBlockElement::GetList($client_hblock,array(),array("UF_ID_1C".$postfix=>$request_data["UF_CONTRAGENT"]),array(),1);
		$client = $client_request->Fetch();
		foreach($client as $k=>$v)
		{
			$client[str_replace($postfix,"",$k)]=$v;
		}
	}
?>
<span class="header_class_1"><?="Заявка №".((intval($request_data["UF_ID"])!=0)?$request_data["UF_ID"]:"*".$request_data["ID"])?></span>
<?if(isset($_GET["interes"])):?>
<div class="del_s">
	<a href="javascript:void(0)" data-agent="<?=$request_data["UF_AGENT"]?>" data-f="<?=$_GET["interes"]?>" data-t="<?=$request_data["ID"]?>" data-code="<?=($request_data["UF_ID"])?$request_data["UF_ID"]:"*".$request_data["ID"]?>" data-ava="<?=$request_data["UF_PHOTO_PREVIEW"]?>" class="<?=($interested==1)?"active":""?>" onclick="like_obj(this);">Удалить из интереса</a>
</div>
<?endif?>
<div class="webform_realty" id="request_view">
	
	<?if($agent["UF_BITRIX_USER"]==$USER->GetID()&&!isset($_GET["interes"])):?>
		<span class="header_class">Информация о заявке</span>
	<?endif;?>
	<?if ($request_data["UF_PHOTO_PREVIEW"]!=0||(is_array($request_data["UF_PHOTOS"])&&sizeof($request_data["UF_PHOTOS"])>0)):?>
	<div class="jcarousel-wrapper">
		<div class="jcarousel">
			<ul>
				<?
				if ($request_data["UF_PHOTO_PREVIEW"]!=0):
					$img_small = CFile::ResizeImageGet($request_data["UF_PHOTO_PREVIEW"], array('width'=>373, 'height'=>250), BX_RESIZE_IMAGE_EXACT, true);                
					$img = '<li><img height="250" src="'.$img_small['src'].'"/></li>';
					echo $img;
				endif;
				if (is_array($request_data["UF_PHOTOS"])&&sizeof($request_data["UF_PHOTOS"])>0):
						foreach ($request_data["UF_PHOTOS"] as $k => $v)
						{
							$img_small = CFile::ResizeImageGet($v, array('width'=>373, 'height'=>250), BX_RESIZE_IMAGE_EXACT, true);                
							$img = '<li><img height="250" src="'.$img_small['src'].'"/></li>';
							echo $img;
						}
				endif;?>
			</ul>
		</div>     
		<a href="#" class="jcarousel-control-prev"></a>
		<a href="#" class="jcarousel-control-next"></a>
		<p class="jcarousel-pagination">
		</p>
	</div>
	<?
	else:
	if($request_data["UF_ID"]>0&&in_array($request_data["UF_OPERATION_TYPE"],array_merge($Project->prodazha,$Project->sdacha))){
	?>
		<?
		if(is_dir($_SERVER["DOCUMENT_ROOT"]."/upload/1c/foto/".$request_data["UF_ID"]))
		{
			?>
			<div class="jcarousel-wrapper">
				<div class="jcarousel">
					<ul>
			<?
			$file = glob($_SERVER["DOCUMENT_ROOT"]."/upload/1c/foto/".$request_data["UF_ID"]."/*",GLOB_NOSORT);
			foreach ($file as $ky=>$vy)
			{
				$destinationFile = $_SERVER["DOCUMENT_ROOT"]."/upload/1c/slider/".$request_data["UF_ID"]."_".$ky.".jpg";
				CFile::ResizeImageFile($vy, $destinationFile, array("width"=>373,"height"=>250), BX_RESIZE_IMAGE_EXACT);
				//die(str_replace("../..","",$file[0]));
				?><li><img height="250" src="<? echo("/upload/1c/slider/".$request_data["UF_ID"]."_".$ky.".jpg");?>"/></li><?
			}?>
					</ul>
				</div>     
				<a href="#" class="jcarousel-control-prev"></a>
				<a href="#" class="jcarousel-control-next"></a>
				<p class="jcarousel-pagination">
				</p>
			</div>
			<?
		}
		else
		{
			/*
			$PreviewImg = file_get_contents("http://invent-realty.ru/help_portal_ir/get_preview_img_object.php?Id=".$request_data["UF_ID"]);
			?>
			<?if($PreviewImg != ""):?>
			<div data-id="<?=$request_data["UF_ID"]?>" class="find_preview" style="text-align: center;">
				<a class="fancybox" href="<?=$PreviewImg?>">
					<img height="250" src="<?=$PreviewImg?>"/>
				</a>
			</div>
			<?else:*/?>
			<div data-id="<?=$request_data["UF_ID"]?>" class="find_preview" style="text-align: center;">
				<a class="fancybox" href="/bitrix/templates/realty/images/soon.jpg">
					<img height="250" src="/bitrix/templates/realty/images/soon.jpg"/>
				</a>
			</div>
			<?/*endif;*/?>
	<?
		}
	}
	endif;
	$first=1;
	foreach ($request_data["UF_PLAN_PHOTOS"] as $k => $v)
	{
		$img_small = CFile::GetPath($v);     
		if($first==1)
		{
			$first=0;
			?>
			<div id="planirovki_block">
			<a href="<?=$img_small?>" rel="group" class="img_item_b" id="planirovki_btn"><span>Планировки</span></a>
			<?
		}
		else
		{           
			$img = '<a rel="group" class="hide img_item_b" href="'.$img_small.'"></a>';
			echo $img;
		}
	}
	if($first==0)
		echo "</div>";
	else
	{
		$file = scandir($_SERVER["DOCUMENT_ROOT"]."/upload/1c/foto/P".$request_data["UF_ID"]."/"/*,GLOB_NOSORT*/);
		foreach ($file as $ky=>$vy)
		{
			if(is_file($_SERVER["DOCUMENT_ROOT"]."/upload/1c/foto/P".$request_data["UF_ID"]."/".$vy))
			{
				//$destinationFile = $_SERVER["DOCUMENT_ROOT"]."/upload/1c/slider/".$request_data["UF_ID"]."_".$ky.".jpg";
				//CFile::ResizeImageFile($vy, $destinationFile, array("width"=>373,"height"=>250), BX_RESIZE_IMAGE_EXACT);
				//die(str_replace("../..","",$file[0]));
				$img_small="http://portal.invent-realty.ru/upload/1c/foto/P".$request_data["UF_ID"]."/".$vy;/*"http://".str_replace("/var/www/admin/data/www/","",$vy);*/
				if($first==1)
				{
					$first=0;
					?>
					<div id="planirovki_block">
					<a href="<?=$img_small?>" rel="group" class="img_item_b" id="planirovki_btn"><span>Планировки</span></a>
					<?
				}
				else
				{           
					$img = '<a rel="group" class="hide img_item_b" href"='.$img_small.'"></a>';
					echo $img;
				}
			}
		}
		if($first==0)
			echo "</div>";
	}
	?>
	<div class="request_view_info">
		<span class="obj_price">
		<?
		/*switch($request_data["UF_OPERATION_TYPE"]) 
		{
			case 56:$w="Продаётся ";break;
			case 57:$w="Сдаётся ";break;
			case 143:$w="Покупается ";break;
			case 144:$w="Снимается ";break;
			case 291:$w="Продаётся ";break;
			case 292:$w="Покупается ";break;
		}*/
		$w=$Project->get_name_for_view($request_data["UF_OPERATION_TYPE"]);
		echo $w;
		$dbResultObj = HlBlockElement::GetList(3, array("UF_OBJ_TYPE_NAME", "UF_OBJ_TYPE_CLASS"), array("UF_OBJ_TYPE_ID" => $request_data["UF_OBJ_TYPE"]), array(), 1);
		$arResultObj = $dbResultObj->Fetch();
		$arElement["UF_OBJ_TYPE"] = $arResultObj;
		switch($arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_CLASS"])
		{
			case "Квартира":
			case "Комната":				
				$description = ((intval($request_data["UF_ROOMS"])>0)?$request_data["UF_ROOMS"]."-комнатная ":"").strtolower($arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"]);
				if($request_data["UF_ADDR_STREET"]):
					$description.= "<br>".$request_data["UF_ADDR_STREET"].", ";
				endif;
				if($request_data["UF_TOTAL_SQUARE"]):
					$description.= $request_data["UF_TOTAL_SQUARE"]." кв.м.";
				endif;
				if($request_data["UF_CITY_ID"]):
					$description.= "<br>".$request_data["UF_CITY_ID"].".";
				endif;
				break;					
			case "Здание":
			case "Эллинг":
				$description = ((intval($request_data["UF_ROOMS"])>0)?$request_data["UF_ROOMS"]."-комнатная ":"").strtolower($arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"]).", ".$request_data["UF_TOTAL_SQUARE"]."/".$request_data["UF_LIVING_SQUARE"]."/".
					$request_data["UF_KITCHEN_SQUARE"]." кв.м.";
				if($request_data["UF_ADDR_STREET"]):
					$description.= "<br>".$request_data["UF_ADDR_STREET"].", ";
				endif;
				if($request_data["UF_CITY_ID"]):
					$description.= "<br>".$request_data["UF_CITY_ID"];
				endif;
				break;
			case "Помещение":
			case "Земельный участок":
				$description = strtolower($arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"])." ".$request_data["UF_TOTAL_SQUARE"]."/".
					$request_data["UF_LIVING_SQUARE"]."/".$request_data["UF_KITCHEN_SQUARE"]." кв.м.";
				if($request_data["UF_ADDR_STREET"]):
					$description.= "<br>".$request_data["UF_ADDR_STREET"].", ";
				endif;
				if($request_data["UF_CITY_ID"]):
					$description.= "<br>".$request_data["UF_CITY_ID"];
				endif;
				break;
			default:
				$description = strtolower($arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"])." ".$request_data["UF_TOTAL_SQUARE"]." кв.м.";
				break;
		}
		echo $description;
		?>
		</span>
		<div class="request_view_info_ins"><?
			echo $request_data["UF_REKLAMA"];
		?></div>
		<?if($request_data["UF_PRICE"]):?>
			<span class="obj_price"><?=number_format($request_data["UF_PRICE"], 0, ",", " ")?> руб.</span>
		<?endif;?>
	</div>
<div class="container-norm first_w">
	<div class="sayavka_content">
		<?
			$CodeObject = file_get_contents("http://invent-realty.ru/help_portal_ir/get_code_object.php?Id=".$request_data["UF_ID"]);
		?>
		<?if($CodeObject != ""):?>
		<div class="header_desc">
			<a target="_blank" href="http://invent-realty.ru/<?=$CodeObject?>/">Поделиться</a>
		</div>
		<?endif;?>
		<?if($agent["UF_BITRIX_USER"]==$USER->GetID()||isset($_GET["nw"])):?>
			<div class="header_desc">Информация о заявке</div>
			<div class="field_to_fill_text">Источник</div>
			<? Helper_realty::write_select_uf("SOURCE".$postfix,1)?>
			<div class="field_to_fill_text">Статус</div>
			<? Helper_realty::write_select_uf("STATUS".$postfix,1)?>
			<div class="field_to_fill_text">Вид операции</div>
			<? Helper_realty::write_select_uf("OPERATION_TYPE".$postfix,1)?>
			<div class="bl"></div>
		<?endif;?>
		<div class="header_desc">Информация об объекте</div>
		<div class="field_to_fill_text">Тип недвижимости</div>
		<? Helper_realty::write_select(4,"REALTY_TYPE",1,$postfix)?>
		<div class="field_to_fill_text">Тип объекта</div>
		<? 
		Helper_realty::write_select_obj_type(1,1,$postfix);
		?>
		<div class="bl"></div>
		<div id="addr_block_main" class="hide_border_all">
			<div class="header_desc">Адрес объекта</div>
			<!--
			<div class="field_to_fill_text">Район</div>
			<? /*Helper_realty::write_select_kladr("UF_REGION_ID")*/?>
			<div class="field_to_fill_text">Населённый пункт</div>
			<?/* Helper_realty::write_select_kladr("UF_CITY_ID")*/?>
			<div class="block_to_hide_ul <?=($request_data["UF_OPERATION_TYPE"]==143||$request_data["UF_OPERATION_TYPE"]==144)?"hide":""?>">
				<div class="field_to_fill_text">Улица</div>
				<?/* Helper_realty::write_select_kladr("UF_ADDR_STREET")*/?>
			</div>
			-->
			<?$APPLICATION->IncludeComponent("primepix:kladr.address", "realty_view", Array(
				"BUILDING_INPUT" => "Y",	// Дома
					"COMPONENT_TEMPLATE" => ".default",
					"DELETE_NOT_IN_KLADR_VALUES" => "Y",	// Удалять значения которых нет в КЛАДР
					"DISTRICT_INPUT" => "Y",	// Района
					"HIDDEN_KLADR_ID" => "Y",	// Код объекта в КЛАДР
					"HIDDEN_LABEL" => "Y",	// Подписи
					"HIDDEN_LABEL_MIN" => "Y",	// Сокращения подписей
					"HIDDEN_Z_INDEX" => "Y",	// Почтовый индекс
					"INCLUDE_JQUERY" => "N",	// Подключить jQuery
					"INCLUDE_JQUERY_UI" => "Y",	// Подключить jQuery UI
					"INCLUDE_JQUERY_UI_THEME" => "Y",	// Подключить тему jQuery UI
					"KEY" => "1111",	// Ключ для доступа к КЛАДР API
					"LOCATION_INPUT" => "Y",	// Населённого пункта
					"REGION_INPUT" => "N",	// Области
					"STREET_INPUT" => "Y",	// Улицы
					"TOKEN" => "56f95beb0a69dec4488b45a9",	// Токен для доступа к КЛАДР API
					"UPDATE_LABELS" => "Y",	// Обновлять подписи при вводе
					"USE_PAID_KLADR" => "N",	// "REGIONID" => "39"
				),
				false
			);?>
		</div>
		<?
		if($postfix!="")
			$rrr = CUserTypeEntity::GetByID(382);
		else
			$rrr = CUserTypeEntity::GetByID(248);?>
		<div class="field_to_fill_text"><?=$rrr["EDIT_FORM_LABEL"]["ru"]?></div>
		<div class="field_to_fill">	
			<input type="text" <?=($request_data["UF_INNER_STATUS"]==1&&in_array($rrr["FIELD_NAME"],$ty)&&strlen($request_data[$rrr["FIELD_NAME"]])==0)?"class=\"red only_view_select\"":"class=\"only_view_select\"";?> placeholder="не заполнено поле <?=strtolower($rrr["EDIT_FORM_LABEL"]["ru"])?>" name="<?=$rrr["FIELD_NAME"]?>" value="<?=$request_data[$rrr["FIELD_NAME"]]?>" size="0">
		</div>
		<div class="bl"></div>
	</div>
	<?
	for($step=3;$step<=4;$step++)
	{
		if($step==4)
		{
			?>	
				<div class="open_dop"><a href="#" class="open_dop_parameters_view">Дополнительные параметры</a></div>
			<?
		}
		?>
		<div class="hide_border_all sayavka_content <?=($step==4)?"hide_act hide":""?>">
		<div class="header_desc">Информация об объекте <?=($step==4)?"( дополнительные параметры)":""?></div>
		<?
		if($postfix!="")
			$ar_help = Array();
		else
			$ar_help = $Project->get_order(0);
		$export_arr = HlBlockElement::GetList(3,array(),array("UF_OBJ_TYPE_ID"=>$request_data["UF_OBJ_TYPE"]),array(),1);			
		$rsData = CUserTypeEntity::GetList(Array("SORT"=>"ASC"), array("ENTITY_ID"=>"HLBLOCK_".$data_res["hblock"]) );
		while($arRes = $rsData->Fetch())
		{
			if(!in_array($arRes["ID"],$ar_help))
				array_push($ar_help,$arRes["ID"]);
			if($arRes["ID"]==155)
				array_push($ar_help,156);
			if($arRes["ID"]==375)
				array_push($ar_help,331);
		}
		foreach($ar_help as $field_id)
		{
			
			$rrr = CUserTypeEntity::GetByID($field_id);
			$rrr["FIELD_NAME"] = str_replace($postfix,"",$rrr["FIELD_NAME"]);
			if(in_array($rrr["FIELD_NAME"],Array("UF_INNER_STATUS","UF_ID","UF_ADDR_INDEX","UF_ADDR_BLOCK","UF_SOURCE","UF_STATUS","UF_OPERATION_TYPE","UF_REALTY_TYPE","UF_AGENT","UF_CONTRAGENT","UF_OBJ_TYPE","UF_REGION_ID","UF_CITY_ID","UF_ADDR_STREET","UF_ADDR_HOUSE"/*,"UF_ADDR_FLAT"*/,"UF_REQUESTS_ID","UF_LATITUDE","UF_LONGITUDE","UF_CITY_REGION")))continue;
			//if(($request_data["UF_OPERATION_TYPE"]==143||$request_data["UF_OPERATION_TYPE"]==144)&&in_array($rrr["FIELD_NAME"],Helper_realty::$array_for_filter_p[1])||($request_data["UF_OPERATION_TYPE"]==56||$request_data["UF_OPERATION_TYPE"]==57)&&in_array($rrr["FIELD_NAME"],Helper_realty::$array_for_filter_p[0]))continue;
			if(in_array($rrr["FIELD_NAME"],Helper_realty::get_array_for_filter()))continue;
			if($step==3&&!in_array($rrr["FIELD_NAME"],$ty)||$step==4&&in_array($rrr["FIELD_NAME"],$ty))continue;
			if(($rrr["USER_TYPE_ID"]=="enumeration"||$rrr["FIELD_NAME"]=="UF_GOAL") )
			{
				?><div class="field_to_fill_text"><?=$rrr["EDIT_FORM_LABEL"]["ru"]?></div>
				<?
				if($rrr["FIELD_NAME"]=="UF_GOAL"){
					Helper_realty::write_select(12,str_replace("UF_","",$rrr["FIELD_NAME"]),0,$postfix);}
				else
					Helper_realty::write_select_uf(str_replace("UF_","",$rrr["FIELD_NAME"]).$postfix,1);
			}
			else if($rrr["USER_TYPE_ID"]=="boolean")
			{?>
				<div class="field_to_fill_text"><input disabled="disabled" class="chbx_form" type="checkbox" value="1" name="<?=$rrr["FIELD_NAME"]?>" <?=($request_data[$rrr["FIELD_NAME"]]==1)?"checked=\"checked\"":""?>><?=$rrr["EDIT_FORM_LABEL"]["ru"]?></div>
			<?}
			else {
				if($rrr["USER_TYPE_ID"]=="file"||$rrr["USER_TYPE_ID"]=="enumeration")continue;
				
				if($rrr["FIELD_NAME"] == "UF_ADDR_FLAT" && $agent["UF_BITRIX_USER"]!=$USER->GetID()){
					continue;
				}
				
				?>
				<div class="field_to_fill_text"><?=$rrr["EDIT_FORM_LABEL"]["ru"]?></div>
				<div class="field_to_fill">
					<?if($rrr["FIELD_NAME"]=="UF_REKLAMA")
					{?>
						<textarea rows="7" cols="45" <?=($request_data["UF_INNER_STATUS"]==1&&in_array($rrr["FIELD_NAME"],$ty)&&strlen($request_data[$rrr["FIELD_NAME"]])==0)?"class=\"red\"":"";?> placeholder="не заполнено поле <?=strtolower($rrr["EDIT_FORM_LABEL"]["ru"])?>" name="<?=$rrr["FIELD_NAME"]?>" size="0"><?=$request_data[$rrr["FIELD_NAME"]]?></textarea>
					<?}
					else 
					{
						if(in_array($rrr["FIELD_NAME"],array("UF_PRICE","UF_CLIENT_PRICE")))
							$request_data[$rrr["FIELD_NAME"]] = Helper_realty::correct_price($request_data[$rrr["FIELD_NAME"]]);?>
						<input type="text" <?=($request_data["UF_INNER_STATUS"]==1&&in_array($rrr["FIELD_NAME"],$ty)&&strlen($request_data[$rrr["FIELD_NAME"]])==0)?"class=\"red\"":"";?> placeholder="не заполнено поле <?=strtolower($rrr["EDIT_FORM_LABEL"]["ru"])?>" name="<?=$rrr["FIELD_NAME"]?>" value="<?=$request_data[$rrr["FIELD_NAME"]]?>" size="0">				
					<?}?>
				</div>
				<?
			}
			?>
				<div class="bl"></div>
			<?
		}
		if($step==4)
		{
		?>
			<?if (!empty($request_data["UF_DOCS"])):?>
			<div class="header_desc">Дополнительные документы</div>
			<div class="img_block">
				<?
				echo CFile::InputFile("UF_DOCS", 20, $request_data["UF_DOCS"]);
				?><br><?
				foreach ($request_data["UF_DOCS"] as $k => $v)
					echo '<a target="_blank" class="img_item_b" href="'.CFile::GetPath($v)."\"><img src=\"/images/icons/pdf-reader.jpg\"/></a>";
				?>
			</div>
			<?endif;?>
			<div class="bl"></div>
		<?
		}
		?>
		</div>
		<?
	}
?>
	<div class="sayavka_content">
		<div class="bl"></div>
		<div id="addr_block_main">
			<div class="header_desc">Информация об агенте</div>
			<div class="field_to_fill agent_view">
				<?=(intval($agent["UF_BITRIX_USER"])!=0)?("<a href=\"/company/personal/user/".$agent["UF_BITRIX_USER"]."/\">".$agent["UF_AGENT_NAME"]."</a>"):$agent["UF_AGENT_NAME"]?>
			</div>
		</div>
	</div>
	<?if($agent["UF_BITRIX_USER"]==$USER->GetID()||$i_m_active):?>
		<div class="sayavka_content">
			<div class="header_desc">Информация о клиенте</div>
			<input type="hidden" name="sessid" id="sessid" value="<?=bitrix_sessid_get()?>">
			<input type="hidden" name="step" value="1">
			<input type="hidden" name="REQUEST_ID" value="<?=$sayavka_id?>">
			<div class="field_to_fill_text">Клиент (ФИО)</div>
			<div class="field_to_fill">
				<input type="hidden" name="UF_AGENTS_ID_1C" value=""/>
				<input type="hidden" name="UF_CONTRAGENT" value="<?=$client["ID"]?>"/>
				<input type="text" class="only_view_select" placeholder="не заполнено поле имя" name="UF_AGENTS_FIO" value="<?=$client["UF_FIO"]?>" size="0" <?=($client["UF_FIO"]!=""&&$client["UF_CLIENT_STATUS"]>=2)?"readonly":""?>>				
			</div>
			<div class="field_to_fill_text">Номер телефона</div>
			<div class="field_to_fill">
				<input type="text" class="only_view_select" placeholder="не заполнено поле номер телефона" name="UF_AGENTS_PHONE" value="<?=$client["UF_PHONE"]?>" size="0" <?=($client["UF_PHONE"]!=""&&$client["UF_CLIENT_STATUS"]>=2)?"readonly":""?>>				
			</div>
			<div class="field_to_fill_text">Дополнительный телефон</div>
			<div class="field_to_fill">
				<input type="text" class="only_view_select" placeholder="не заполнено поле номер телефона" name="UF_AGENTS_PHONE_1" value="<?=$client["UF_PHONE_1"]?>" size="0" <?=($client["UF_PHONE_1"]!=""&&$client["UF_CLIENT_STATUS"]>=2)?"readonly":""?>>				
			</div>
			<div class="field_to_fill_text">E-mail</div>
			<div class="field_to_fill">
				<input type="text" class="only_view_select" placeholder="не заполнено поле e-mail" name="UF_AGENTS_MAIL" value="<?=$client["UF_MAIL"]?>" size="0" <?=($client["UF_MAIL"]!=""&&$client["UF_CLIENT_STATUS"]>=2)?"readonly":""?>>				
			</div>
			<div class="bl"></div>
			<div id="clients_block"> 
			</div>
		</div>
	<?endif;?>
	<a class="big_button orange call_client_request <?=($coordinate&&!$i_m_active)?"no_active":""?>" href="tel:<?
		if($agent["UF_BITRIX_USER"]==$USER->GetID()||$i_m_active)
			echo Helper_realty::correct_phone($client["UF_PHONE"]);
		else
		{
			$rsUser = CUser::GetByID($agent["UF_BITRIX_USER"]);
			$arUser = $rsUser->Fetch();
			echo Helper_realty::correct_phone($arUser["PERSONAL_MOBILE"]);
		}	
		?>">Связаться c <?=($agent["UF_BITRIX_USER"]==$USER->GetID()||$i_m_active||$coordinate)?"клиентом":"агентом"?></a>
	<br><br><br><br><br><br><br><br><br>
</div></div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>