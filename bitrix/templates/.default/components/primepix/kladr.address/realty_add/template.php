<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?global $request_data;?>
<script src="https://api-maps.yandex.ru/2.0-stable/?load=package.full&lang=ru-RU" type="text/javascript"></script>
<div id="kladr-address" class="kladr-address">
    <? if($arResult['SETTINGS']['INCLUDE_JQUERY_UI_THEME']): ?><link href="http://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css" rel="stylesheet" type="text/css" /><? endif ?>
    <? if($arResult['SETTINGS']['INCLUDE_JQUERY']): ?><script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script><? endif ?>
    <? if($arResult['SETTINGS']['INCLUDE_JQUERY_UI']): ?><script src="http://code.jquery.com/ui/1.10.2/jquery-ui.js"></script><? endif ?>
    <? foreach($arResult['OPTIONS'] as $key => $val): ?>
        <input name="<?= $key ?>" type="hidden" value="<?= $val ?>" />
    <? endforeach ?>

    <? foreach($arResult['COMMON_FIELDS'] as $field): ?>
        <? if($field['LABEL']): ?><label for="<?= $field['NAME'] ?>"><?= $field['LABEL'] ?></label><? endif ?>
        <input name="<?= $field['NAME'] ?>" type="<?= $field['HIDDEN'] ? 'hidden' : 'text' ?>" value="<?
					if($field['NAME']=="kladr_id")echo $request_data["UF_ADDR_BLOCK"];
					else if($field['NAME']=="zip_code") echo $request_data["UF_ADDR_INDEX"];
					?>" />
    <? endforeach ?>
	<input name="region" type="hidden" autocomplete="off" data-kladr-type="region" data-kladr-obj="3900000000000">
    <? foreach($arResult['FIELDS'] as $fields): ?>
            <? foreach($fields as $field): ?>
				<?
					if($field['NAME']=="location")$field['LABEL']="Город/н. пункт";
					if($field['NAME']=="district")$val_wr = $request_data["UF_REGION_ID"];
					else if($field['NAME']=="location")$val_wr = $request_data["UF_CITY_ID"];
					else if($field['NAME']=="street") $val_wr = $request_data["UF_ADDR_STREET"];
					else if($field['NAME']=="building")$val_wr = $request_data["UF_ADDR_HOUSE"];
					$val_arr = explode(" ",$val_wr);
				?>
                <? if($field['LABEL']): ?><div class="field_to_fill_text<?=($field['NAME']=="building"&&($request_data["UF_OPERATION_TYPE"]==143||$request_data["UF_OPERATION_TYPE"]==144))?" hide":"";?>"><?= $field['LABEL'] ?><label><?=(count($val_arr)>1&&$val_arr[count($val_arr)-1]!="")?("(".$val_arr[count($val_arr)-1].")"):""?></label></div><? endif ?>
				<?if(!$field['HIDDEN']):?><div class="field_to_fill<?=($field['NAME']=="building"&&($request_data["UF_OPERATION_TYPE"]==143||$request_data["UF_OPERATION_TYPE"]==144))?" hide":"";?>"><?endif;?>
					<input name="<?= $field['NAME'] ?>" type="<?= $field['HIDDEN'] ? 'hidden' : 'text' ?>" value="<?
					echo $val_arr[0];
					foreach($val_arr as $k=>$v)
					{	
						if($k==0||$k==count($val_arr)-1)continue;
						echo " ".$v;
					}
					?>" class="c_kladr"/>
				<?if(!$field['HIDDEN']):?></div><?endif;?>
				<?if($field['NAME']=="street"):
					$arr_city_reg=Array("Балтийский р-н","Ленинградский р-н","Московский р-н","Октябрьский р-н","Центральный р-н");
				?>
					<div class="field_to_fill_text <?=(strpos($request_data["UF_CITY_ID"],"Калининград")!==FALSE)?"":"hide"?>">Район города</div>
					<select class="no_select <?=(strpos($request_data["UF_CITY_ID"],"Калининград")!==FALSE)?"":"hide"?>" name="UF_CITY_REGION">
						<option value="">-</option>
						<?foreach($arr_city_reg as $k=>$v):?>
							<option value="<?=$v?>" <?=($request_data["UF_CITY_REGION"]==$v)?"selected":""?>><?=$v?></option>
						<?endforeach?>
					</select>
					<div class="bl"></div>
				<?endif;?>
            <? endforeach ?>
    <? endforeach ?>

	<? if($arResult['SETTINGS']['USE_PAID_KLADR']): ?>
		<script src="<?= $templateFolder ?>/jquery.primepix.kladrpaid.min.js"></script>
	<? else: ?>
		<script src="<?= $templateFolder ?>/jquery.primepix.kladr.min.js"></script>
	<? endif ?>  
    <script src="<?= $templateFolder ?>/controller.js"></script>
    <script>KladrApiControllerInit('<?= $arResult['TOKEN'] ?>', '<?= $arResult['KEY'] ?>');</script>
	<br><br><br><br>
	<div id="map" style="width: 100%; height: 400px"></div>
</div>