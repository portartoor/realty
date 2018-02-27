<?
if(isset($_GET["ajax"]))
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include.php");
else
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
}
require_once($_SERVER["DOCUMENT_ROOT"]."/libs/realty_class.php");
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
	$Project = new Rights();
	$data_res = $Project->get_requests_file();
	$postfix  = $Project->get_postfix();
	
	$sayavka_id = isset($_GET["REQUEST_ID"])?$_GET["REQUEST_ID"]:"";
	global $USER;
	$request = HlBlockElement::GetList($data_res["hblock"],array(),array("ID"=>$sayavka_id),array(),1);
	$request_data = $request->Fetch();
	if($postfix!="")
	{
		foreach($request_data as $k=>$v)
		{
			$request_data[str_replace($postfix,"",$k)]=$v;
		}
	}
	if(!empty($request_data))
	{
		$sayavka_id=$request_data["ID"];
	}
	else die("Нет такого объекта");
?>
<?if ($sayavka_id != ""):?>
	<span class="header_class">Закрытие заявки</span>
	<form id="request_close" name="REQUEST_CLOSE" action="#" method="POST" enctype="multipart/form-data" onsubmit="send_request_close_form($(this));return false;">
		<div class="sayavka_content">
			<input type="hidden" name="REQUEST_ID" value="<?=$sayavka_id?>">
			<input type="hidden" name="UF_REQUEST_ID" value="<?=$request_data["UF_ID"]?>">
			<input type="hidden" name="UF_AGENT" value="<?=$request_data["UF_AGENT"]?>">
			<div class="field_to_fill_text">Дата закрытия</div>
			<div class="field_to_fill relative">
				<input type="text" placeholder="" name="UF_DATE_CLOSE" id="UF_DATE_CLOSE" onfocus="setTimeout(function(){$('#UF_DATE_CLOSE').parent().find('.bx-calendar-icon').click()},300);" onchange="$(this).css('border', '')" value="<?=date("d.m.Y H:i:s")?>" size="">
				<span onclick="BX.calendar({node:this, field:'UF_DATE_CLOSE', form: 'REQUEST_CLOSE', bTime: true, currentTime: '<?=(time()+date("Z")+CTimeZone::GetOffset())?>', bHideTime: false});" class="calendar-icon"></span>			
			</div>
			<div class="field_to_fill_text">Причина закрытия</div>
			<div class="field_to_fill">
				<?Helper_realty::write_select_uf_not_xml("CLOSE_REASON".$postfix,0,true)?>
			</div>
			<div class="field_to_fill_text">Комментарий</div>
			<div class="field_to_fill">
				<textarea rows="7" cols="45" placeholder="введите комментарий" name="UF_COMMENT" size="0" onchange="$(this).css('border', '')"></textarea>
			</div>
			<div class="full-block">
				<input class="big_button" type="submit" name="web_form_submit" value="Отправить"/>
			</div>
		</div>
	</form>
<?endif;?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>