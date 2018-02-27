<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Мои заявки");

//Подключение вспомогательного класса
//class Helper_realty
//Методы
//write_select($hblock,$name) 
//Выводит на страницу "select" с именем UF_$name и свойствами из справочника $hblock
//write_select_uf($name) 
//Выводит на страницу "select" с именем UF_$name и свойствами из справочника UF_$name
//write_select_kladr($name)
//Выводит на страницу "select" с поиском по КЛАДР и именем $name
//write_select_obj_type()
//Выводит на страницу "select" с варинтами выбора типа объекта
require($_SERVER["DOCUMENT_ROOT"]."/libs/realty_class.php");
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
$Project = new Rights();
$data_res = $Project->get_requests_file();
$postfix  = $Project->get_postfix();
$agents_hblock = $Project->get_agents_hb_id();
//Выбираем поля для вывода на страницу
$arRequestFields = array("UF_OPERATION_TYPE","UF_CATEGORY"/*,"UF_REALTY_TYPE"*/);
$rsData = CUserTypeEntity::GetList(array(), array("ENTITY_ID" => "HLBLOCK_".$data_res["hblock"]));
while($arReqUf = $rsData->Fetch())
{
	if (in_array($arReqUf["FIELD_NAME"], $arRequestFields)) 
	{
		$arUfData = CUserTypeEntity::GetByID($arReqUf["ID"]);
		$arFields[] = $arUfData;
	}
}
?>
<div class="webform_realty">
	<form name="my_requests" action="/realty/ajax/results.php" method="POST" enctype="multipart/form-data">
		<div class="search_content">
			<div class="field_to_fill_text">Тип недвижимости</div>
			<?
			Helper_realty::write_select(4, "REALTY_TYPE",0,$postfix);
			?>
			<div class="field_to_fill_text">Тип объекта</div>
			<?
			Helper_realty::write_select_obj_type(0,0,$postfix);

			//Перебераем все поля, для вывода
			foreach ($arFields as $arField)
			{
				//Выводим поля со справчниками в виде "select".
				if ($arField["USER_TYPE_ID"] == "enumeration") 
				{
					?>
					<div class="field_to_fill_text"><?=$arField["EDIT_FORM_LABEL"]["ru"]?></div>
					<?
					Helper_realty::write_select_uf(str_replace("UF_", "", $arField["FIELD_NAME"]));
				}
				//Во всех остальных случаях выводим обычные поля "text" для ввода данных
				else
				{
				?>
					<div class="field_to_fill_text"><?=$arField["EDIT_FORM_LABEL"]["ru"]?></div>
					<div class="field_to_fill">
						<input type="text" placeholder="введите <?=strtolower($arField["EDIT_FORM_LABEL"]["ru"])?>"<?
							?>name="<?=$arField["FIELD_NAME"]?>" value="" size="0">	
					</div>					
				<?
				}
			}
			global $USER;
			$arUserGroups = CUser::GetUserGroup($USER->GetId());
			if (!in_array(30,$arUserGroups))
				echo "<div style=\"display:none;\">";
			else 
				echo "<div>";
				?>
				<div class="field_to_fill_text">Агент</div>
				<?
				$user_code="";
				$arr_q = HlBlockElement::GetList($agents_hblock,array(),array("UF_BITRIX_USER".$postfix=>$USER->GetID()),array(),1);
				if($arr_s_client = $arr_q->Fetch()){
					$user_code = $arr_s_client["UF_AGENT_ID".$postfix];
				}
				else 
				{
					$rsUser = CUser::GetByID($USER->GetID());
					$arUser = $rsUser->Fetch();
					foreach ($arUser as $k=>$v)
					{
						$arUser[str_replace($postfix,"",$k)]=$v;
					}
					$arr_q = HlBlockElement::GetList($agents_hblock,array(),array(array( 
																		"LOGIC" => "AND",
																		"UF_AGENT_NAME".$postfix => "%".$arUser["NAME"]."%",
																		"UF_AGENT_NAME".$postfix => "%".$arUser["LAST_NAME"]."%"
																		),
																"UF_BITRIX_USER".$postfix=>NULL),
																	array(),1);
					if($arUser["NAME"]!=""&&$arUser["LAST_NAME"]!=""&&$arr_s_client = $arr_q->Fetch())
					{
						$res = HlBlockElement::Update($agents_hblock,$arr_s_client["ID"],Array("UF_BITRIX_USER".$postfix=>$USER->GetID()));
						$user_code = $arr_s_client["UF_AGENT_ID".$postfix];
					}
					else
					{
						$user_code = "new_user_".$USER->GetID();
						$res = HlBlockElement::Add($agents_hblock,$Project->add_postfix_to_fields(Array("UF_BITRIX_USER"=>$USER->GetID(),"UF_AGENT_ID"=>$user_code,"UF_AGENT_NAME"=>$arUser["NAME"]." ".$arUser["LAST_NAME"])));
					}
				}
				$request_data["UF_AGENT"]=$user_code;
				$request_data["UF_AGENT".$postfix]=$user_code;
				Helper_realty::write_select($agents_hblock, "AGENT",0,$postfix);
				?>
				</div>
		</div>
		<div class="filter_sort">
			<?
			Helper_realty::write_sort_input("ADD_DATE".$postfix, "Дата", "border_gray_right");
			Helper_realty::write_sort_input("PRICE".$postfix, "Цена");
			?>
		</div>
		<input id="my_request_show" class="big_button" type="submit" name="web_form_submit" value="Поиск" />
	</form>
	<div id="result_arr" data-page="0"></div>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>