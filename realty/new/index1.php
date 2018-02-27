<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
require($_SERVER["DOCUMENT_ROOT"]."/libs/realty_class.php");

function is_old_android($currentDir, $version = '5.0.2'){
	/*if (strpos($currentDir, "mobile") === false)
		return false;*/
	if(strstr($_SERVER['HTTP_USER_AGENT'], 'Android')){
		preg_match('/Android (\d+(?:\.\d+)+)[;)]/', $_SERVER['HTTP_USER_AGENT'], $matches);
		return version_compare($matches[1], $version, '<=');
	}
}
?>
<script src="<?=SITE_TEMPLATE_PATH?>/js/realty_new.js"></script>
<script type="text/javascript">
	var RequiredFields = ["UF_SOURCE","UF_OPERATION_TYPE","UF_REALTY_TYPE","UF_OBJ_TYPE","location","street"];
	var RequiredFieldsLen = RequiredFields.length;
	var i =0;
	$(document).ready(function(){
		for(i =0; i < RequiredFieldsLen;i++){
			if(!($("[name='"+RequiredFields[i]+"']").hasClass("red"))){
				console.log(RequiredFields[i]);
				$("[name='"+RequiredFields[i]+"']").addClass("red");
			}
		}
	});
</script>
<?
if (CModule::IncludeModule("mobileapp"))
   CMobile::Init();

$step = 1; 
if(isset($_GET["step"]))$step=intval($_GET["step"]);
$sayavka_id = isset($_GET["REQUEST_ID"])?$_GET["REQUEST_ID"]:"";
/*if(isset($_GET["nw"])){?><div class="quick_perehod_btn"><div class="ul_div"><a href="javascript:void(0);" class="quick_perehod_1 <?=($step==1)?"active":""?>" data-step="1"><span></span></a><a class="quick_perehod_2 <?=($step==2)?"active":""?>" href="javascript:void(0);" data-step="2"><span></span></a><a href="javascript:void(0);" class="quick_perehod_3 <?=($step==3)?"active":""?>" data-step="3"><span></span></a><a class="quick_perehod_4 <?=($step==4)?"active":""?>" href="javascript:void(0);" data-step="4"><span></span></a><a href="javascript:void(0);" class="quick_perehod_5 <?=($step==5)?"active":""?>" data-step="5"><span></span></a></div></div>
<?
	}*/
if($sayavka_id=="")
{
	global $USER;
	$arr_q = HlBlockElement::GetList(5,array(),array("UF_BITRIX_USER"=>$USER->GetID()),array(),1);
	if($arr_s_client = $arr_q->Fetch()){
		$user_code = $arr_s_client["UF_AGENT_ID"];
	}
	$request = HlBlockElement::GetList(2,array(),array("UF_AGENT"=>$user_code,"UF_INNER_STATUS"=>Array(0,1)),array(),1);
}
else
	$request = HlBlockElement::GetList(2,array(),array("ID"=>$sayavka_id),array(),1);
	$request_data = $request->Fetch();
	if($request_data["UF_INNER_STATUS"]<2)
		$APPLICATION->SetTitle("Добавить новую заявку");
	else
		$APPLICATION->SetTitle("Редактировать заявку");
	if(!empty($request_data))
		$sayavka_id=$request_data["ID"];
	if(isset($_GET["renew"])&&!empty($request_data)&&$request_data["UF_INNER_STATUS"]!=2)
	{
		
		HlBlockElement::Remove(2,$sayavka_id);
		$sayavka_id = "";
		$request_data = Array();
	}
		
	/*if($request_data["UF_OPERATION_TYPE"]==143||$request_data["UF_OPERATION_TYPE"]==144||$request_data["UF_OPERATION_TYPE"]==292)
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
		if(in_array($request_data["UF_OPERATION_TYPE"],array(144)))
		{
			$ty = array_diff($ty,Array("UF_CLIENT_PRICE"));
		}
	}*/
	$ty = Helper_realty::get_array_for_filter(1);
if($step==6)
{
	if(Helper_realty::check_all_fields_not_null())
	{
		if($request_data["UF_INNER_STATUS"]!=2)
			$res = HlBlockElement::Update(2,$sayavka_id,Array("UF_INNER_STATUS"=>2));
		$client_request = HlBlockElement::GetList(10,array(),array("UF_ID_1C"=>$request_data["UF_CONTRAGENT"]),array(),1);
		$client = $client_request->Fetch();
		if($client["UF_CLIENT_STATUS"]!=2)
			$res = HlBlockElement::Update(10,$client["ID"],Array("UF_CLIENT_STATUS"=>2));
		if (strpos($APPLICATION->GetCurDir(), "mobile") !== false)
			header('Location: /mobile/realty/');
		else
			header('Location: /realty/');
	}
	else
	{
		if($request_data["UF_INNER_STATUS"]==0)
			$res = HlBlockElement::Update(2,$sayavka_id,Array("UF_INNER_STATUS"=>1));
		
		if (strpos($APPLICATION->GetCurDir(), "mobile") !== false)
			header('Location: /mobile/realty/new/?REQUEST_ID='.$sayavka_id.'&step=5#unfilled_info');
		else				
			header('Location: /realty/new/?REQUEST_ID='.$sayavka_id.'&step=5#unfilled_info');
	}
	exit;
}
if($step==1)
{
	$client = Array();
	if($sayavka_id!=""&&($request_data["UF_CONTRAGENT"])!="")
	{
		$client_request = HlBlockElement::GetList(10,array(),array("UF_ID_1C"=>$request_data["UF_CONTRAGENT"]),array(),1);
		$client = $client_request->Fetch();
	}
?>
	<?if($sayavka_id!=""&&!isset($_GET["REQUEST_ID"])):?>
		<div id="reset_all">
			<h3>У вас есть не сохранённые черновики. Хотите продолжить заполнять заявку или начать заново?</h3>
			<div class="a_block">
				<a href="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/new/?REQUEST_ID=<?=$sayavka_id?>" class="go_request">Продолжить</a><a class="renew_request" href="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/new/?renew=1">Начать заново</a>
			</div>
		</div>
	<?endif;?>
	<div class="webform_realty">
		<form name="new_object" action="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/new/?step=2" method="POST" enctype="multipart/form-data">
			<div class="sayavka_content">
				<div class="header_desc">Информация о клиенте</div>
				<input type="hidden" name="sessid" id="sessid" value="<?=bitrix_sessid_get()?>">
				<input type="hidden" name="step" value="1">
				<input type="hidden" name="REQUEST_ID" value="<?=$sayavka_id?>">
				<div class="field_to_fill_text">Клиент (ФИО)</div>
				<div class="field_to_fill">
					<input type="hidden" name="UF_AGENTS_ID_1C" value=""/>
					<input type="hidden" name="UF_CONTRAGENT" value="<?=$client["ID"]?>"/>
					<input type="text" placeholder="введите имя" name="UF_AGENTS_FIO" value="<?=$client["UF_FIO"]?>" size="0" <?=($client["UF_FIO"]!=""&&$client["UF_CLIENT_STATUS"]>=2)?"readonly":""?>>				
				</div>
				<div class="field_to_fill_text">Номер телефона</div>
				<div class="field_to_fill">
					<input type="text" placeholder="введите номер телефона" name="UF_AGENTS_PHONE" value="<?=$client["UF_PHONE"]?>" size="0" <?=($client["UF_PHONE"]!=""&&$client["UF_CLIENT_STATUS"]>=2)?"readonly":""?>>				
				</div>
				<div class="field_to_fill_text">Дополнительный телефон</div>
				<div class="field_to_fill addon_phone">
				<table>
					<tr>
						<td width="5%">
							<span class="addon_phone_prefix">8</span>
						</td>
						<td width="20%">
							<input class="addon_phone_code" type="text" placeholder="введите код телефона" name="UF_AGENTS_PHONE_1_CODE" value="<? if ($client["UF_PHONE_1_CODE"]!='') { echo $client["UF_PHONE_1_CODE"]; } else { echo '4012'; } ?>" size="0">
						</td>
						<td width="75%">
							<input class="addon_phone_number" type="text" placeholder="введите номер телефона" name="UF_AGENTS_PHONE_1" value="<?=$client["UF_PHONE_1"]?>" size="0" <?=($client["UF_PHONE_1"]!=""&&$client["UF_CLIENT_STATUS"]>=2)?"readonly":""?>>				
						</td>
					</tr>
				</table>
					<!--<input type="text" placeholder="введите номер телефона" name="UF_AGENTS_PHONE_1" value="<?=$client["UF_PHONE_1"]?>" size="0" <?=($client["UF_PHONE_1"]!=""&&$client["UF_CLIENT_STATUS"]>=2)?"readonly":""?>>-->				
				</div>
				<div class="field_to_fill_text">E-mail</div>
				<div class="field_to_fill">
					<input type="text" placeholder="введите e-mail" name="UF_AGENTS_MAIL" value="<?=$client["UF_MAIL"]?>" size="0" <?=($client["UF_MAIL"]!=""&&$client["UF_CLIENT_STATUS"]>=2)?"readonly":""?>>				
				</div>
				<div class="bl"></div>
				<div id="clients_block"> 
				<?
				if($client["UF_CLIENT_STATUS"]>=2)
				{
				?>
					<a href="#" class="remove_client" >Новый клиент</a>
				<?}
				if($client["UF_CLIENT_STATUS"]!=0)
				{
					$filter = array("UF_CONTRAGENT" => $client["UF_ID_1C"],"!ID" => $sayavka_id);
					$arr_q = HlBlockElement::GetList(2,array(),$filter,array(),10);
					while($arr_s = $arr_q->Fetch()){	
						?>
						<div class="header_desc header_desc_1">Другие заявки выбранного контрагента</div>
						<div class="sayavka_item"><a href="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/new/?REQUEST_ID=<?=$arr_s["ID"]?>">Заявка №<?=$arr_s["ID"]?></a></div>
						<div href="#" class="logo_upload"></div>
						<div class="c_request_info">
						<?
						$arr_q_1 = HlBlockElement::GetList(5,array(),array("UF_AGENT_ID"=>$arr_s["UF_AGENT"]),array(),1);
						if($arr_s_client = $arr_q_1->Fetch())
						{
						?>
							<b>Агент:</b><?
								echo "<a href=\"/company/personal/user/".$arr_s_client["UF_BITRIX_USER"]."/\">".$arr_s_client["UF_AGENT_NAME"]."</a>";
						}
						?><br>
							<b>Статус:</b><?
							$rs = CUserFieldEnum::GetList(array(), array(
									"USER_FIELD_NAME" => "UF_STATUS","ID" =>$arr_s["UF_STATUS"]
									));
									if($st = $rs->GetNext())
									{
										echo $st["VALUE"];
									}
							if($arr_s["UF_REALTY_TYPE"]!=0)
							{
							?><br>
							<b>Тип недвижимости:</b><?
								$rr = HlBlockElement::GetList(4,array(),array("UF_REALTY_TYPE_ID"=>$arr_s["UF_REALTY_TYPE"]),array(),1);
								if($rr_i = $rr->Fetch()){
									echo $rr_i["UF_REALTY_TYPE_NAME"];
								}
							}
							?>
						</div>
						<?
					}		
				}
				?>
				</div>
			</div>
			<div class="managment_btns">
				<input class="big_button" type="submit" name="web_form_submit" value="Далее" />
			</div>
		</form>
	</div>
<?
}
else if($step==2)
{?>
	<form name="new_object" action="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/new/?step=3" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="sessid" id="sessid" value="<?=bitrix_sessid_get()?>">
		<input type="hidden" name="step" value="<?=$step?>">
		<input type="hidden" name="REQUEST_ID" value="<?=$sayavka_id?>">
		<div class="webform_realty"><div class="sayavka_content">
				<div class="header_desc">Информация о заявке</div>
				<div class="field_to_fill_text">Источник</div>
				<? Helper_realty::write_select_uf("SOURCE")?>
				<div class="field_to_fill_text">Статус</div>
				<? Helper_realty::write_select_uf("STATUS")?>
				<div class="field_to_fill_text">Вид операции</div>
				<? Helper_realty::write_select_uf("OPERATION_TYPE")?>
				<div class="bl"></div>
				<div class="header_desc">Информация об объекте</div>
				<div class="field_to_fill_text">Тип недвижимости</div>
				<? Helper_realty::write_select(4,"REALTY_TYPE")?>
				<div class="field_to_fill_text">Тип объекта</div>
				<? 
				Helper_realty::write_select_obj_type();
				?>
				<div class="bl"></div>
				<div id="addr_block_main">
					<div class="header_desc">Адрес объекта</div>
					<input type="hidden" name="UF_LATITUDE" value="<?=$request_data["UF_LATITUDE"]?>">
					<input type="hidden" name="UF_LONGITUDE" value="<?=$request_data["UF_LONGITUDE"]?>">
					<!--
					<div class="field_to_fill_text">Район</div>
					<? /*Helper_realty::write_select_kladr("UF_REGION_ID")*/?>
					<div class="field_to_fill_text">Населённый пункт</div>
					<?/* Helper_realty::write_select_kladr("UF_CITY_ID")*/?>
					<div class="block_to_hide_ul <?=($request_data["UF_OPERATION_TYPE"]==143||$request_data["UF_OPERATION_TYPE"]==144||$request_data["UF_OPERATION_TYPE"]==292)?"hide":""?>">
						<div class="field_to_fill_text">Улица</div>
						<?/* Helper_realty::write_select_kladr("UF_ADDR_STREET")*/?>
					</div>
					-->
					<div id="map_overlay">
					<?$APPLICATION->IncludeComponent(
	"primepix:kladr.address", 
	"realty_add", 
	array(
		"BUILDING_INPUT" => "Y",
		"COMPONENT_TEMPLATE" => "realty_add",
		"DELETE_NOT_IN_KLADR_VALUES" => "Y",
		"DISTRICT_INPUT" => "Y",
		"HIDDEN_KLADR_ID" => "Y",
		"HIDDEN_LABEL" => "Y",
		"HIDDEN_LABEL_MIN" => "Y",
		"HIDDEN_Z_INDEX" => "Y",
		"INCLUDE_JQUERY" => "N",
		"INCLUDE_JQUERY_UI" => "Y",
		"INCLUDE_JQUERY_UI_THEME" => "Y",
		"KEY" => "1111",
		"LOCATION_INPUT" => "Y",
		"REGION_INPUT" => "N",
		"STREET_INPUT" => "Y",
		"TOKEN" => "56f95beb0a69dec4488b45a9",
		"UPDATE_LABELS" => "Y",
		"USE_PAID_KLADR" => "N"
	),
	false
);?>
</div>
<a href="#" class="map_expose">Раскрыть карту</a>
<script>
$(document).ready(function(){
	
	$('.map_expose').click(function(e) {
		e.preventDefault();
		if ($('#map_overlay').hasClass('exposed')) {
			$('#map_overlay').removeClass('exposed');
			$('.map_expose').html('Раскрыть карту');
		}
		else {
			$('#map_overlay').addClass('exposed');
			$('.map_expose').html('Cвернуть карту');
		}
	  });
});	  
</script>
				</div>
				<div class="bl"></div>
				<div id="clients_block_addr"></div>
				<div class="bl"></div>
			</div></div>
			<div class="managment_btns">
				<button class="small_button"  name="web_form_submit">Назад</button><input class="small_button" type="submit" name="web_form_submit" value="Далее" />
			</div>
		</form>
<?
}
else if($step==3||$step==4)
{
	if($step==3&&$request_data["UF_OBJ_TYPE"]==0)
	{?>
		<div class="info_message">Заполните поле "Тип объекта".</div>
	<?}
	if($step==4)
	{?>
		<div class="open_dop"><a href="#" class="open_dop_parameters">Дополнительные параметры</a></div>
	<?}
?>
	<form name="new_object" action="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/new/?step=<?=($step+1)?>" method="POST" enctype="multipart/form-data">
		<div class="webform_realty" <?=($step==4)?"style=\"display:none;\"":""?>>
			<input type="hidden" name="step" value="<?=$step?>">
			<input type="hidden" name="REQUEST_ID" value="<?=$sayavka_id?>">
			<div class="sayavka_content">
				<input type="hidden" name="sessid" id="sessid" value="<?=bitrix_sessid_get()?>">
				<?
				/*if($step==4&&($request_data["UF_OPERATION_TYPE"]==143||$request_data["UF_OPERATION_TYPE"]==144||$request_data["UF_OPERATION_TYPE"]==292))
				{?>
					<div class="header_desc">Адрес объекта</div>
					<div class="field_to_fill_text">Улица</div>
					<? Helper_realty::write_select_kladr("UF_ADDR_STREET")?>
				<?}*/?>
				<div class="header_desc">Информация об объекте <?=($step==4)?"( дополнительные параметры)":""?></div>
				<?
				$ar_help = Array(171,176,259,265);
				$export_arr = HlBlockElement::GetList(3,array(),array("UF_OBJ_TYPE_ID"=>$request_data["UF_OBJ_TYPE"]),array(),1);			
				$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_2") );
				while($arRes = $rsData->Fetch())
				{
					if(!in_array($arRes["ID"],$ar_help))
						array_push($ar_help,$arRes["ID"]);
					if($arRes["ID"]==156)
						array_push($ar_help,269,268,155);
				}
				foreach($ar_help as $field_id)
				{
					$rrr = CUserTypeEntity::GetByID($field_id);
					if(in_array($rrr["FIELD_NAME"],Array("UF_INNER_STATUS","UF_ID","UF_ADDR_INDEX","UF_ADDR_BLOCK","UF_SOURCE","UF_STATUS","UF_OPERATION_TYPE","UF_REALTY_TYPE","UF_AGENT","UF_CONTRAGENT","UF_OBJ_TYPE","UF_REGION_ID","UF_CITY_ID","UF_ADDR_STREET","UF_ADDR_HOUSE"/*,"UF_ADDR_FLAT"*/,"UF_REQUESTS_ID","UF_LATITUDE","UF_LONGITUDE","UF_ADD_DATE","UF_UPDATE_DATE","UF_CITY_REGION")))continue;
					/*if(($request_data["UF_OPERATION_TYPE"]==143||$request_data["UF_OPERATION_TYPE"]==144||$request_data["UF_OPERATION_TYPE"]==292)&&in_array($rrr["FIELD_NAME"],Helper_realty::$array_for_filter_p[1])||($request_data["UF_OPERATION_TYPE"]==56||$request_data["UF_OPERATION_TYPE"]==57||$request_data["UF_OPERATION_TYPE"]==291)&&in_array($rrr["FIELD_NAME"],Helper_realty::$array_for_filter_p[0]))continue;*/
					if(in_array($rrr["FIELD_NAME"],Helper_realty::get_array_for_filter()))continue;
					if(($step==3&&!in_array($rrr["FIELD_NAME"],$ty)&&!in_array($rrr["FIELD_NAME"],Array("UF_PRICE_SELL","UF_PRICE_CUST"))) || ($step==4&&(in_array($rrr["FIELD_NAME"],$ty)||in_array($rrr["FIELD_NAME"],Array("UF_PRICE_SELL","UF_PRICE_CUST")))))continue;
					if(($rrr["USER_TYPE_ID"]=="enumeration"||$rrr["FIELD_NAME"]=="UF_GOAL") )
					{
						?><div class="field_to_fill_text"><?=$rrr["EDIT_FORM_LABEL"]["ru"]?></div>
						<?
						if($rrr["FIELD_NAME"]=="UF_GOAL"){
							Helper_realty::write_select(12,str_replace("UF_","",$rrr["FIELD_NAME"]));}
						else
							Helper_realty::write_select_uf(str_replace("UF_","",$rrr["FIELD_NAME"]));
					}
					else if($rrr["USER_TYPE_ID"]=="boolean")
					{?>
						<div class="field_to_fill_text"><input class="chbx_form" type="checkbox" value="1" name="<?=$rrr["FIELD_NAME"]?>" <?=($request_data[$rrr["FIELD_NAME"]]==1)?"checked=\"checked\"":""?>><?=$rrr["EDIT_FORM_LABEL"]["ru"]?></div>
	
					<?}
					else {
						if($rrr["USER_TYPE_ID"]=="file"||$rrr["USER_TYPE_ID"]=="enumeration")continue;
						?>
						<div class="field_to_fill_text <?/*=(in_array($rrr["FIELD_NAME"],Array("UF_PRICE_SELL","UF_PRICE_CUST")))?"price_ec":""*/?>"><?=$rrr["EDIT_FORM_LABEL"]["ru"]?></div>
						<div class="field_to_fill <?=(in_array($rrr["FIELD_NAME"],Array("UF_CLIENT_PRICE")))?"no_padding_right":""?>">
							<?if($rrr["FIELD_NAME"]=="UF_REKLAMA")
							{?>
								<textarea rows="7" cols="45" <?=(/*$request_data["UF_INNER_STATUS"]==1&&*/in_array($rrr["FIELD_NAME"],$ty)&&strlen($request_data[$rrr["FIELD_NAME"]])==0)?"class=\"red\"":"";?> placeholder="введите <?=strtolower($rrr["EDIT_FORM_LABEL"]["ru"])?>" name="<?=$rrr["FIELD_NAME"]?>" size="0"><?=$request_data[$rrr["FIELD_NAME"]]?></textarea>
							<?}
							else 
							{
								if(strpos($rrr["FIELD_NAME"],"PRICE")!==FALSE)
								{
									$request_data[$rrr["FIELD_NAME"]] = Helper_realty::correct_price($request_data[$rrr["FIELD_NAME"]]);?>
									<input type="tel" class="<?=(/*$request_data["UF_INNER_STATUS"]==1&&*/in_array($rrr["FIELD_NAME"],$ty)&&strlen($request_data[$rrr["FIELD_NAME"]])==0)?"red":"";?>" placeholder="введите <?=strtolower($rrr["EDIT_FORM_LABEL"]["ru"])?>" name="<?=$rrr["FIELD_NAME"]?>" value="<?=$request_data[$rrr["FIELD_NAME"]]?>" size="0" inputmode="numeric" x-inputmode="numeric">
									<?if($rrr["FIELD_NAME"]=="UF_CLIENT_PRICE"):?>
									<style>
									
									</style>
									<div class="column_m_price green"><span id="middle_price_percent"></span></div></div><a id="more_middle_price" href="javascript:void(0);"></a>
									<div class="middle_price_block hide">
									<div class="field_to_fill_text" style="margin-top:0px;">Рекомендованная цена</div>
									<a class="set_price_cl" href="#"><div class="column_m_price"><span><b id="middle_price"></b></span></div></a><a class="find_middle_price"  href="javascript:void(0);">Оценить</a></div>
									<?endif;?>								
								<?}
								else
								{
									?>
									<input type="text" class="<?=(/*$request_data["UF_INNER_STATUS"]==1&&*/in_array($rrr["FIELD_NAME"],$ty)&&strlen($request_data[$rrr["FIELD_NAME"]])==0)?"red":"";?>" placeholder="введите <?=strtolower($rrr["EDIT_FORM_LABEL"]["ru"])?>" name="<?=$rrr["FIELD_NAME"]?>" value="<?=$request_data[$rrr["FIELD_NAME"]]?>" size="0">
									<?
								}
							}?>
						</div>
						<?
					}
				}
					?>
				<div class="bl"></div>
			</div>
		</div>
		<div class="managment_btns">
			<button class="small_button"  name="web_form_submit">Назад</button><input class="small_button" type="submit" name="web_form_submit" value="Далее" />
		</div>
	</form>
<?}
else if($step==5){
	/*?>
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script>
$(function() {
    $(".sortable").sortable();
});
</script>
<?*/
	if($request_data["UF_ENUMERATE_PHOTO"]!="")
		{
			$str_photos = $request_data["UF_ENUMERATE_PHOTO"];
			$arr_photos = explode(";",$str_photos);
			$uploaddir = "1c/foto/".$request_data["UF_ID"]."/";
			$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/".$uploaddir;
			if(file_exists($dir))
			{
				foreach($arr_photos as $k=>$v)
				{
					if($v!=""&&is_file($dir.$v))
					{
						$arFile = CFile::MakeFileArray($dir.$v);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							if($k==0)
								$arr["UF_PHOTO_PREVIEW"]=$fid;
							else
								$arr["UF_PHOTOS"][]=$fid;
						}
					}
				}
			}
			$uploaddir = "1c/foto/P".$request_data["UF_ID"]."/";
			$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/".$uploaddir;
			if(file_exists($dir))
			{
				$files = scandir($dir);
				//foreach($arr_photos as $k=>$v)
				foreach($files as $file_i) 
				{	
					if(is_file($dir.$file_i))
					{
						$arFile = CFile::MakeFileArray($dir.$file_i);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
							$arr["UF_PLAN_PHOTOS"][]=$fid;	
					}
				}
			}
			$uploaddir = "1c/foto/D".$request_data["UF_ID"]."/";
			$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/".$uploaddir;
			if(file_exists($dir))
			{
				$files = scandir($dir);
				foreach($files as $file_i) {
					if(is_file($dir.$file_i))
					{
						$arFile = CFile::MakeFileArray($dir.$file_i);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
							$arr["UF_DOCS"][]=$fid;	
					}
				}
			}
			$arr["UF_ENUMERATE_PHOTO"]="";
			$res = HlBlockElement::Update(2,$sayavka_id,$arr);
			$request_data["UF_PHOTO_PREVIEW"]=$arr["UF_PHOTO_PREVIEW"];
			$request_data["UF_PHOTOS"]=$arr["UF_PHOTOS"];
			$request_data["UF_PLAN_PHOTOS"]=$arr["UF_PLAN_PHOTOS"];
			$request_data["UF_DOCS"]=$arr["UF_DOCS"];
		}
	?>
<script type="text/javascript" src="/bitrix/templates/realty/js/jquery-fileupload.js"></script>
<script type="text/javascript" src="/bitrix/templates/realty/js/photo_upload.js"></script>
<div class="webform_realty">
	<form name="new_object" action="<?if (strpos($APPLICATION->GetCurDir(), "mobile") !== false) echo "/mobile"?>/realty/new/?step=<?=($step+1)?>" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="step" value="<?=$step?>">
		<input type="hidden" name="REQUEST_ID" value="<?=$sayavka_id?>">
		<div class="sayavka_content">
			<input type="hidden" name="sessid" id="sessid" value="<?=bitrix_sessid_get()?>">
			<div class="header_desc">Превью</div>
			<div class="field_to_fill_text">Добавить фото</div>
			<div class="img_block">
				<input name="UF_PHOTO_PREVIEW" class="typefile" size="20" type="file" <?if(is_old_android($APPLICATION->GetCurDir())) {?>onclick="getPhotoOldAndroid(event, {source: 2, destinationType: 0}, $(this));" <?}?><?if(!is_old_android($APPLICATION->GetCurDir())) {?>onchange="prepareUploadGo(event);"<?}?>>
				<ul class="unsortable">
				<?
				//echo CFile::InputFile("UF_PHOTO_PREVIEW", 20, $request_data["UF_PHOTO_PREVIEW"]);
				if ($request_data["UF_PHOTO_PREVIEW"]!=0):
					$descr = CFile::GetFileArray($request_data["UF_PHOTO_PREVIEW"]);
					$descr_arr = json_decode($descr["DESCRIPTION"], true);
					$checkbox="<br><input type=\"checkbox\" ".(($descr_arr["SEND"]=="1")?"checked":"")." /><label>выгружать</label>";
					$img_small = CFile::ResizeImageGet($request_data["UF_PHOTO_PREVIEW"], array('width'=>150, 'height'=>150), BX_RESIZE_IMAGE_EXACT, true);                
					$img = '<li><div class="img_item" data-id="'.$request_data["UF_PHOTO_PREVIEW"].'" data-url="'.CFile::GetPath($request_data["UF_PHOTO_PREVIEW"]).'"><img src="'.$img_small['src'].'"/></div>'.$checkbox.'</li>';
					echo $img;
				endif;
				?>
				</ul>
				<a href="#" class="logo_upload" onclick="$( this ).parent().find('input').first().click(); return false;"></a>
			</div>
			<div class="header_desc">Фото объекта</div>
			<div class="field_to_fill_text">Добавить фото</div>
			<div class="img_block">
				<input name="UF_PHOTOS" class="typefile" multiple size="20" type="file" <?if(is_old_android($APPLICATION->GetCurDir())) {?>onclick="getPhotoOldAndroid(event, {source: 2, destinationType: 0}, $(this));" <?}?><?if(!is_old_android($APPLICATION->GetCurDir())) {?>onchange="prepareUploadGo(event);"<?}?>>
				<ul class="sortable">
				<?
				if (sizeof($request_data["UF_PHOTOS"])>0):
					foreach ($request_data["UF_PHOTOS"] as $k => $v)
					{
						$descr = CFile::GetFileArray($v);
						$descr_arr = json_decode($descr["DESCRIPTION"], true);
						$checkbox="<br><input type=\"checkbox\" ".(($descr_arr["SEND"]=="1")?"checked":"")." /><label>выгружать</label>";
						$img_small = CFile::ResizeImageGet($v, array('width'=>150, 'height'=>150), BX_RESIZE_IMAGE_EXACT, true);                
						$img = '<li><div class="img_item" data-id="'.$v.'" data-url="'.CFile::GetPath($v).'"><img src="'.$img_small['src'].'"/></div>'.$checkbox.'</li>';
						echo $img;
					}
				endif;
				?>
				</ul>
				<a href="#" class="logo_upload" onclick="$( this ).parent().find('input').first().click(); return false;"></a>
			</div>
			<div class="header_desc">Планировки</div>
			<div class="field_to_fill_text">Добавить фото</div>
			<div class="img_block">
				<input name="UF_PLAN_PHOTOS" class="typefile" multiple size="20" type="file" <?if(is_old_android($APPLICATION->GetCurDir())) {?>onclick="getPhotoOldAndroid(event, {source: 2, destinationType: 0}, $(this));" <?}?><?if(!is_old_android($APPLICATION->GetCurDir())) {?>onchange="prepareUploadGo(event);"<?}?>>
				<ul class="sortable">
				<?
				//echo CFile::InputFile("UF_PLAN_PHOTOS", 20, $request_data["UF_PLAN_PHOTOS"]);
				if (sizeof($request_data["UF_PLAN_PHOTOS"])>0):
					?><br><?
					foreach ($request_data["UF_PLAN_PHOTOS"] as $k => $v)
					{
						$descr = CFile::GetFileArray($v);
						$descr_arr = json_decode($descr["DESCRIPTION"], true);
						$checkbox="<br><input type=\"checkbox\" ".(($descr_arr["SEND"]=="1")?"checked":"")." /><label>выгружать</label>";
						$img_small = CFile::ResizeImageGet($v, array('width'=>150, 'height'=>150), BX_RESIZE_IMAGE_EXACT, true);                
						$img = '<li><div class="img_item" data-id="'.$v.'" data-url="'.CFile::GetPath($v).'"><img src="'.$img_small['src'].'"/></div>'.$checkbox.'</li>';
						echo $img;
					}
				endif;
				?>
				</ul>
				<a href="#" class="logo_upload" onclick="$( this ).parent().find('input').first().click(); return false;"></a>
			</div>
			<div class="header_desc">Дополнительные документы</div>
			<div class="field_to_fill_text">Добавить документы</div>
			<div class="img_block">
				<input name="UF_DOCS" class="typefile" multiple size="20" type="file" <?if(is_old_android($APPLICATION->GetCurDir())) {?>onclick="getPhotoOldAndroid(event, {source: 0, destinationType: 0}, $(this));" <?}?><?if(!is_old_android($APPLICATION->GetCurDir())) {?>onchange="prepareUploadGo(event);"<?}?>>
				<ul class="sortable">
				<?
				//echo CFile::InputFile("UF_DOCS", 20, $request_data["UF_DOCS"]);
				if (sizeof($request_data["UF_DOCS"])>0):
					?><br><?
					foreach ($request_data["UF_DOCS"] as $k => $v)
					{
						$descr = CFile::GetFileArray($v);
						$descr_arr = json_decode($descr["DESCRIPTION"], true);
						$checkbox="<br><input type=\"checkbox\" ".(($descr_arr["SEND"]=="1")?"checked":"")." /><label>выгружать</label>";
						echo '<li><div class="img_item" data-id="'.$v.'" data-url="'.CFile::GetPath($v)."\"><img src=\"/images/icons/pdf-reader.jpg\"/></div>".$checkbox.'</li>';
					}
				endif;
				?>
				</ul>
				<a href="#" class="logo_upload" onclick="$( this ).parent().find('input').first().click(); return false;"></a>
			</div>
			<div class="bl"></div>
			<?
			if($request_data["UF_INNER_STATUS"]<1||$request_data["UF_INNER_STATUS"]>2)
			{
				$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_2") );
				$first = 0;
				while($arRes = $rsData->Fetch())
				{
					$rrr = CUserTypeEntity::GetByID($arRes["ID"]);
					if(!in_array($rrr["FIELD_NAME"],$ty)||in_array($rrr["FIELD_NAME"],array("UF_COMMENT_ORDER")))continue;
					if($rrr["USER_TYPE_ID"]=="boolean")continue;
					if(strlen($request_data[$rrr["FIELD_NAME"]])==0)
					{
						if($first==0){
							$first=1;
							?>
							<div id="unfilled_info" class="header_desc" style="color:red;">Для сохранения заявки заполните поля:</div>
								<div class="field_to_fill_text">
							<?
						}
						if ($rrr["FIELD_NAME"]=="UF_CITY_ID" || $rrr["FIELD_NAME"]=="UF_ADDR_STREET" || $rrr["FIELD_NAME"]=="UF_ADDR_HOUSE" || $rrr["FIELD_NAME"]=="UF_SOURCE" || $rrr["FIELD_NAME"]=="UF_OPERATION_TYPE" || $rrr["FIELD_NAME"]=="UF_REALTY_TYPE") {
							$step=2;
						}
						else {
							$step=3;
						}
						if (strpos($APPLICATION->GetCurDir(), "mobile") !== false)
							echo "<a class=\"yakor_l\" href=\"/mobile/realty/new/?step=".$step."&REQUEST_ID=".$request_data["ID"]."#".$rrr["FIELD_NAME"]."\">".$rrr["EDIT_FORM_LABEL"]["ru"]."</a><br>";
						else
							echo "<a class=\"yakor_l\" href=\"/realty/new/?step=".$step."&REQUEST_ID=".$request_data["ID"]."#".$rrr["FIELD_NAME"]."\">".$rrr["EDIT_FORM_LABEL"]["ru"]."</a><br>";
					}
				}
				if($first==1)echo"</div>";
				?><br>
				</div>
			<?}
			?>
		</div>
		<div class="managment_btns">
			<button class="small_button"  name="web_form_submit">Назад</button><input class="small_button" type="submit" name="web_form_submit" value="<?if($request_data["UF_INNER_STATUS"]<2): ?>Добавить<? else: ?>Сохранить<?endif;?>" />
		</div>
<?}?>
	</form>
</div>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>