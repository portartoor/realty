<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	CModule::IncludeModule("iblock");
	$Query = CIBlockElement::GetList(
		array(),
		array("ACTIVE" => "Y","IBLOCK_ID" => 34,"ID" => $_REQUEST["Id"]),
		false,
		false,
		array("ID","NAME","DETAIL_PICTURE","DETAIL_TEXT","PROPERTY_FIELD_2")
	)->Fetch();
?>
<style> 
	h1 {
		text-align:center;
		margin:20px auto;
		font-size:24px;
	}
	.plan {
		margin:0 20px;
		font-size:18px;
	}
</style>
<script src="<?=SITE_TEMPLATE_PATH?>/js/realty_new.js"></script>
<input type="hidden" name="REQUEST_ID" value="<?=$_REQUEST["REQUEST_ID"]?>">
<?if(!empty($Query)):
	if($Query["PROPERTY_FIELD_2_VALUE"]=="Y"&&intval($Query["DETAIL_PICTURE"])>0):
	?>	
		<h1>
			<?=$Query["NAME"]?>
		</h1>
		<img style="width:100%;" src="<?=CFile::GetPath($Query["DETAIL_PICTURE"])?>" />
	<?
	else:?>
		<h1>
			<?=$Query["NAME"]?>
		</h1>
		<p class="plan">
			<?=$Query["DETAIL_TEXT"]?>
		</p>
	<?endif;
else:?>
	<div>Элемент не найден.</div>
<?endif;?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>