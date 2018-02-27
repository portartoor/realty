<?
if(isset($_GET["ajax"]))
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include.php");
else
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
}
require_once($_SERVER["DOCUMENT_ROOT"]."/libs/realty_class.php");?>
<?
	$sayavka_id = isset($_GET["REQUEST_ID"])?$_GET["REQUEST_ID"]:"";
	global $USER;
	$request = HlBlockElement::GetList(2,array(),array("ID"=>$sayavka_id),array(),1);
	$request_data = $request->Fetch();
	if(!empty($request_data))
	{
		$sayavka_id=$request_data["ID"];
	}
	else die("Нет такого объекта");
?>	
<?if ($sayavka_id != ""):?>
	<span class="header_class">Смена категории заявки</span>
	<form id="status_change" name="STATUS_CHANGE" action="#" method="POST" enctype="multipart/form-data" onsubmit="send_status_change_form($(this));return false;">
		<div class="sayavka_content">
			<input type="hidden" name="REQUEST_ID" value="<?=$sayavka_id?>">
			<input type="hidden" name="UF_REQUEST_ID" value="<?=$request_data["UF_ID"]?>">
			<input type="hidden" name="UF_AGENT" value="<?=$request_data["UF_AGENT"]?>">
			<div class="field_to_fill_text">Дата</div>
			<div class="field_to_fill relative">
				<input type="text" placeholder="" name="UF_DATE_CREATE" id="UF_DATE_CREATE" onfocus="setTimeout(function(){$('#UF_DATE_CREATE').parent().find('.bx-calendar-icon').click()},300);" onchange="$(this).css('border', '')" value="<?=date("d.m.Y H:i:s")?>" size="">
				<span onclick="BX.calendar({node:this, field:'UF_DATE_CREATE', form: 'STATUS_CHANGE', bTime: true, currentTime: '<?=(time()+date("Z")+CTimeZone::GetOffset())?>', bHideTime: false});" class="calendar-icon"></span>			
			</div>
			<div class="field_to_fill_text">Какую категорию присвоить</div>
			<div class="field_to_fill">
				<?Helper_realty::write_select_uf_not_xml("CATEGORY",0,true)?>
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