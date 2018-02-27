<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<?if ($arResult["isFormErrors"] == "Y"):?><?=$arResult["FORM_ERRORS_TEXT"];?><?endif;?>

<?=$arResult["FORM_NOTE"]?>

<?if ($arResult["isFormNote"] != "Y")
{
?>
<?=$arResult["FORM_HEADER"]?>
<?
/***********************************************************************************
						form questions
***********************************************************************************/
class Helper_realty
{
	public static $arr_realty_type = Array();
	
	function write_select($hblock,$name){
		if($name=="REALTY_TYPE")
		{
			global $arr_realty_type,$arr_realty_type_1;
		}
		?>
		<select>
			<?
			$export_arr = HlBlockElement::GetList($hblock,array(),array(),array(),100);
			while($arr = $export_arr->Fetch()){
				?><option value="<?=$arr["UF_".$name."_ID"]?>"><?=$arr["UF_".$name."_NAME"];?></option><?
				if($name=="REALTY_TYPE")
					self::$arr_realty_type[$arr["UF_".$name."_ID"]]=$arr["UF_".$name."_NAME"];
			}
			?>
		</select>
			<?
	}
	function write_select_uf($name){
			$rs = CUserFieldEnum::GetList(array(), array(
				"USER_FIELD_NAME" => "UF_".$name,
			));?>
			<select>
		   <?
			while($ar = $rs->GetNext())
			{?>
				<option value="<?=$ar["XML_ID"]?>"><?=$ar["VALUE"];?></option>
				<?
			}
		?>
			</select>
			<?
	}
	function write_select_kladr($name){
		$filter=Array();
		if($name=="REGION")
		{
			$filter=Array("UF_KLADR_TYPE"=>2);
		}
	?>
		<select name="<?=$name?>">
			<?
			$export_arr = HlBlockElement::GetList(11,array("UF_KLADR_NAME","UF_KLADR_CODE","UF_KLADR_SOKR"),$filter,array(),100);
			while($arr = $export_arr->Fetch()){
				?><option value="<?=$arr["UF_KLADR_CODE"]?>"><?=$arr["UF_KLADR_NAME"]." ".$arr["UF_KLADR_SOKR"];?></option><?
			}
			?>
		</select>
	<?
	}
}

	foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion)
	{
	?>
		
				<?if (is_array($arResult["FORM_ERRORS"]) && array_key_exists($FIELD_SID, $arResult['FORM_ERRORS'])):?>
				<span class="error-fld" title="<?=$arResult["FORM_ERRORS"][$FIELD_SID]?>"></span>
				<?endif;?>
				<div class="field_to_fill"><?=$arQuestion["CAPTION"]?><?if ($arQuestion["REQUIRED"] == "Y"):?><?=$arResult["REQUIRED_SIGN"];?><?endif;?></div>
				<?/*=$arQuestion["IS_INPUT_CAPTION_IMAGE"] == "Y" ? "<br />".$arQuestion["IMAGE"]["HTML_CODE"] : ""*/?>
				<div class="field_to_fill">
					<?=$arQuestion["HTML_CODE"]?>
				</div>
	<?
	} //endwhile
	?>
	<div class="bl"></div>
	<div class="field_to_fill">Источник</div>
	<? Helper_realty::write_select_uf("SOURCE")?>
	<div class="field_to_fill">Статус</div>
	<? Helper_realty::write_select_uf("STATUS")?>
	<div class="field_to_fill">Вид операции</div>
	<? Helper_realty::write_select_uf("OPERATION_TYPE")?>
	<div class="bl"></div>
	<div class="field_to_fill">Тип недвижимости</div>
	<? Helper_realty::write_select(4,"REALTY_TYPE")?>
	<div class="field_to_fill">Тип объекта</div>
	<? 
	$hblock=3;
	$name = "OBJ_TYPE";?>
	<select>
		<?
		$export_arr = HlBlockElement::GetList($hblock,array(),array(),array(),100);
		$old_value = 0;
		$ins = 0;
		while($arr = $export_arr->Fetch()){
			if($arr["UF_".$name."_PARENT"]!=$old_value) {if($old_value!=0)echo "</optgroup>"; echo "<optgroup label=\"".Helper_realty::$arr_realty_type[$arr["UF_".$name."_PARENT"]]."\">";$ins = 1;}
			?><option value="<?=$arr["UF_".$name."_ID"]?>"><?=$arr["UF_".$name."_NAME"];?></option><?
			$old_value = $arr["UF_".$name."_PARENT"];
		}
		if($old_value!=0)echo "</optgroup>";
		?>
	</select>
	<div class="field_to_fill">Адрес объекта</div>
	<div class="field_to_fill">
		<input type="text" placeholder="введите адрес" name="addr" value="" size="0">				
	</div>
	<div id="addr_block">
		<div class="field_to_fill">Район</div>
		<? Helper_realty::write_select_kladr("REGION")?>
		<div class="field_to_fill">Город</div>
		<? Helper_realty::write_select(6,"CITY")?>
		<div class="field_to_fill">Улица</div>
		<? Helper_realty::write_select(9,"STREETS")?>
		<div class="field_to_fill">Дом</div>
		<div class="field_to_fill">
		<input type="text" placeholder="введите дом" name="addr_house" value="" size="0">				
		</div>
		<div class="field_to_fill">Квартира</div>
		<div class="field_to_fill">
		<input type="text" placeholder="введите номер квартиры" name="addr_flat" value="" size="0">				
		</div>
	</div>
	<div class="bl"></div>
	
	<input class="big_button" <?=(intval($arResult["F_RIGHT"]) < 10 ? "disabled=\"disabled\"" : "");?> type="submit" name="web_form_submit" value="Добавить" />
	<?/*if ($arResult["F_RIGHT"] >= 15):?>
	&nbsp;<input type="hidden" name="web_form_apply" value="Y" /><input type="submit" name="web_form_apply" value="<?=GetMessage("FORM_APPLY")?>" />
	<?endif;*/?>
	<?/*&nbsp;<input type="reset" value="<?=GetMessage("FORM_RESET");?>" />*/?>
<p>
<?/*=$arResult["REQUIRED_SIGN"];?> - <?=GetMessage("FORM_REQUIRED_FIELDS")*/?>
</p>
<?=$arResult["FORM_FOOTER"]?>
<?
} //endif (isFormNote)
?>