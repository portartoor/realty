<?
class Helper_realty
{
	public static $arr_realty_type = Array();
	public static $array_for_filter = Array(
		"Здание" => Array("UF_ADDR_HOUSE","UF_ETAGE_COUNT","UF_TOTAL_SQUARE","UF_LIVING_SQUARE",
					"UF_KITCHEN_SQUARE","UF_LOT_SQUARE","UF_ROOMS","UF_CLIENT_PRICE","UF_PRICE"/*,"UF_PRICE_SELL","UF_PRICE_CUST"*/
					,"UF_REMONT_STATUS","UF_HOUSE_TYPE","UF_WATER","UF_HEATING",
					"UF_REKLAMA","UF_WWW","UF_NARUZHN_REKLAMA","UF_N_REKLAMA"),
		"Земельный участок" => Array("UF_LOT_SQUARE",
							"UF_CLIENT_PRICE","UF_PRICE"/*,"UF_PRICE_SELL","UF_PRICE_CUST"*/
							,"UF_LAND_OWNER_TYPE",
							/*"UF_ENTRANCE","UF_CONSTRUCT_PERM","UF_OWNER_RIGHTS",*/"UF_REKLAMA",
							"UF_WWW","UF_NARUZHN_REKLAMA","UF_N_REKLAMA","UF_OWNER_RIGHTS"),
		"Квартира" => Array("UF_ADDR_HOUSE","UF_ADDR_FLAT","UF_ETAGE","UF_ETAGE_COUNT","UF_TOTAL_SQUARE","UF_LIVING_SQUARE",
						"UF_KITCHEN_SQUARE","UF_ROOMS",
						"UF_CLIENT_PRICE","UF_PRICE"/*,"UF_PRICE_SELL","UF_PRICE_CUST"*/
						,"UF_REMONT_STATUS",
						"UF_SANUSEL_TYPE","UF_MATERIAL","UF_WATER","UF_HEATING","UF_REKLAMA","UF_WWW",
						"UF_NARUZHN_REKLAMA","UF_N_REKLAMA"),
		"Комната" => Array("UF_ADDR_HOUSE","UF_ADDR_FLAT","UF_ETAGE","UF_ETAGE_COUNT","UF_TOTAL_SQUARE","UF_LIVING_SQUARE",
						"UF_KITCHEN_SQUARE","UF_ROOMS",
						"UF_CLIENT_PRICE","UF_PRICE"/*,"UF_PRICE_SELL","UF_PRICE_CUST"*/
						,"UF_REMONT_STATUS",
						"UF_SANUSEL_TYPE","UF_MATERIAL","UF_WATER","UF_HEATING","UF_REKLAMA","UF_WWW",
						"UF_NARUZHN_REKLAMA","UF_N_REKLAMA"),
		"Эллинг" => Array("UF_ADDR_HOUSE","UF_ETAGE_COUNT","UF_TOTAL_SQUARE","UF_LIVING_SQUARE",
					"UF_KITCHEN_SQUARE","UF_GARAGE_SQUARE","UF_CELLAR_SQUARE","UF_LOT_SQUARE","UF_ROOMS",
					"UF_CLIENT_PRICE","UF_PRICE"/*,"UF_PRICE_SELL","UF_PRICE_CUST"*/
					,"UF_REMONT_STATUS","UF_GAS","UF_LAND_OWNER_TYPE","UF_HOUSE_TYPE","UF_MATERIAL","UF_WATER","UF_HEATING",
					"UF_REKLAMA","UF_WWW","UF_NARUZHN_REKLAMA","UF_N_REKLAMA"),
		"Помещение" => Array("UF_ADDR_HOUSE","UF_ADDR_FLAT","UF_ETAGE_COUNT","UF_ETAGE","UF_TOTAL_SQUARE",
						"UF_CLIENT_PRICE","UF_PRICE"/*,"UF_PRICE_SELL","UF_PRICE_CUST"*/,"UF_ENTRY","UF_ELECTICITY","UF_REMONT_STATUS"),
						
		"Гаражи и стоянки" => Array()
	);
	public static $array_for_filter_s = Array(
		0 => Array("UF_PRICE_FROM","UF_PRICE_TO","UF_SQUARE_FROM","UF_SQUARE_TO","UF_ROOMS_FROM","UF_ROOMS_TO"),
		1 => Array("UF_PRICE_FROM","UF_PRICE_TO","UF_SQUARE_FROM","UF_SQUARE_TO")
	);
	public static $array_for_filter_p = Array(
		0 => Array("UF_PRICE_FROM","UF_PRICE_TO","UF_SQUARE_FROM","UF_SQUARE_TO","UF_ROOMS_FROM","UF_ROOMS_TO"),
		1 => Array("UF_ADDR_HOUSE","UF_ETAGE_COUNT","UF_TOTAL_SQUARE","UF_LIVING_SQUARE",
					"UF_KITCHEN_SQUARE","UF_GARAGE_SQUARE","UF_CELLAR_SQUARE","UF_LOT_SQUARE","UF_ROOMS","UF_PRICE",
					"UF_CLIENT_PRICE","UF_REMONT_STATUS","UF_GAS","UF_LAND_OWNER_TYPE","UF_HOUSE_TYPE","UF_WATER","UF_HEATING",
					"UF_REKLAMA","UF_WWW","UF_NARUZHN_REKLAMA","UF_N_REKLAMA","UF_ENTRANCE","UF_CONSTRUCT_PERM","UF_OWNER_RIGHTS","UF_ADDR_FLAT",
					"UF_ETAGE","UF_SANUSEL_TYPE","UF_MATERIAL","UF_ADDR_STREET","UF_PRICE_SELL","UF_PRICE_CUST")
	);
	function get_array_for_filter($var=0){
		global $request_data;
		global $Project;
		$arr_export = Array();
		$ty = Array();
		//if(isset($_GET["nw"]))print_r($Project->pokupka);
		if($var==1)
		{
			if(in_array($request_data["UF_OPERATION_TYPE"],array_merge($Project->pokupka,$Project->siem)/*Array(143,144,292)*/))
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
				if(in_array($request_data["UF_OPERATION_TYPE"],$Project->siem/*array(144)*/))
				{
					$ty = array_diff($ty,Array("UF_CLIENT_PRICE"));
				}
				if(in_array($request_data["UF_OPERATION_TYPE"],array_merge($Project->prodazha,$Project->sdacha)/*array(56,57,291)*/))
				{
					$ty = array_merge($ty,Array("UF_REKLAMA","UF_NARUZHN_REKLAMA","UF_N_REKLAMA","UF_WWW"));
				}
			}
			$arr_export = $ty;
			$arr_export[]="UF_COMMENT_ORDER";
		}
		else
		{
			if(in_array($request_data["UF_OPERATION_TYPE"],array_merge($Project->pokupka,$Project->siem)/*Array(143,144,292)*/))
					$arr_export = Helper_realty::$array_for_filter_p[1];
			else if(in_array($request_data["UF_OPERATION_TYPE"],array_merge($Project->prodazha,$Project->sdacha)))
					$arr_export = Helper_realty::$array_for_filter_p[0];
		}
		return $arr_export;
	} 
	function write_select($hblock,$name,$hide=0,$postfix=""){
		global $request_data,$ty, $Project;
		$name_clean = str_replace($Project->get_postfix(),"",$name);
		?>
		<select name="UF_<?=$name.$postfix?>" class="no_select <?=(/*$request_data["UF_INNER_STATUS"]!=0&&*/intval($request_data["UF_".$name])==0&&in_array("UF_".$name,$ty))?"red":""?> <?=($hide)?"only_view_select":""?>">
			<option value="">-</option>
			<?
			$filter=Array();
			if($request_data["UF_REALTY_TYPE"]==6&&$name_clean=="GOAL")
			{
				$filter=Array("UF_GOAL_PARENT"=>6);
			}
			else if(($request_data["UF_REALTY_TYPE"]==1||$request_data["UF_OBJ_TYPE"]==5)&&$name_clean=="GOAL")
			{
				$filter=Array("UF_GOAL_PARENT"=>1);
			}
			$export_arr = HlBlockElement::GetList($hblock,array(),$filter,array(),1000);
			if(in_array($name_clean,array("REALTY_TYPE","GOAL")))$postfix="";
			while($arr = $export_arr->Fetch()){
				$ni=(($name_clean=="GOAL")?$arr["UF_GOAL_PARENT".$postfix]:"").$arr["UF_".$name_clean."_ID".$postfix];
				?><option value="<?=$ni?>" <?=($ni==$request_data["UF_".$name])?"selected":""?>><?=$arr["UF_".$name_clean."_NAME".$postfix];?></option><?
				if($name_clean=="REALTY_TYPE")
				{
					self::$arr_realty_type[$arr["UF_".$name_clean."_ID"]]=$arr["UF_".$name_clean."_NAME"];
				}
			}
			?>
		</select>
			<?
	}
	function write_select_uf($name,$hide=0){
			global $request_data, $ty, $Project;
			$name_clean = str_replace($Project->get_postfix(),"",$name);
			$rs = CUserFieldEnum::GetList(array(), ($name=="STATUS")?array(
				"USER_FIELD_ID" => 96
			):array("USER_FIELD_NAME" => "UF_".$name));
			$admin=0;
			if($name=="STATUS")
			{
				global $USER;
				$arUserGroups = CUser::GetUserGroup($USER->GetId());
				if (in_array(30,$arUserGroups))$admin=1;
			}
			$admin=0;
			?>
			<select name="UF_<?=$name?>" <?=($name_clean=="STATUS"&&$admin==0||$hide)?"disabled=\"disabled\" class=\"only_view_select\"":""?> <?=(/*$request_data["UF_INNER_STATUS"]!=0&&*/in_array("UF_".$name_clean,$ty)&&intval($request_data["UF_".$name])==0)?"class=\"red\"":""?>>
				<option value="">-</option>
			   <?
				while($ar = $rs->GetNext())
				{
					if($name=="CATEGORY"&&$ar["ID"]<317)continue;
				?>
					<option value="<?=$ar["XML_ID"]?>" <?=($ar["ID"]==$request_data["UF_".$name]||!($request_data["UF_".$name])&&$ar["ID"]==51)?"selected":""?>><?=$ar["VALUE"];?></option>
					<?
				}
				?>
			</select>
			<?
	}
	function write_select_uf_not_xml($name,$hide=0,$reset_border=false,$smena_sayavki=0){
			global $request_data,$ty, $Project;
			$ar_f=Array();
			if($name=="STATUS"){
				$ar_f = array("USER_FIELD_ID" => ($Project->s_name=="domofey")?316:96);
			}
			else if($name=="CATEGORY"&&$smena_sayavki==1){
				$ar_f = array("USER_FIELD_ID" => ($Project->s_name=="domofey")?425:285);
			}
			else 
			{
				$ar_f =array("USER_FIELD_NAME" => "UF_".$name);
			}
			$rs = CUserFieldEnum::GetList(array(), $ar_f);
			$admin=0;
			if($name=="STATUS")
			{
				global $USER;
				$arUserGroups = CUser::GetUserGroup($USER->GetId());
				if (in_array(30,$arUserGroups))$admin=1;
			}
			$admin=0;
			?>
			<select name="UF_<?=$name?>" <?=($name=="STATUS"&&$admin==0||$hide)?"disabled=\"disabled\" class=\"only_view_select\"":""?> <?=(/*$request_data["UF_INNER_STATUS"]!=0&&*/in_array("UF_".$name,$ty)&&intval($request_data["UF_".$name])==0)?"class=\"red\"":""?><?=($reset_border)?"onchange=\"$(this).css('border', '')\"":""?>>
			<option value="">-</option>
		   <?
			while($ar = $rs->GetNext())
			{
			?>
				<option value="<?=$ar["ID"]?>" <?=($ar["ID"]==$request_data["UF_".$name]||!($request_data["UF_".$name])&&$ar["ID"]==51)?"selected":""?>><?=$ar["VALUE"];?></option>
				<?
			}
		?>
			</select>
			<?
	}	
	function write_select_kladr($name){
		global $request_data,$ty;
		$filter=Array();
		if($name=="UF_REGION_ID")
		{
			$filter=Array("UF_KLADR_TYPE"=>2);
		}
		else if($name=="UF_CITY_ID") {
			$filter=Array("UF_KLADR_TYPE"=>Array(3,4),"UF_KLADR_CODE"=>($request_data["UF_REGION_ID"]!="")?substr($request_data["UF_REGION_ID"],0,8)."%":"390 000%");
		}
		else if($name=="UF_ADDR_STREET") {
			if($request_data["UF_CITY_ID"]!="")
				$filter=Array("UF_KLADR_TYPE"=>5, "UF_KLADR_CODE"=>($request_data["UF_CITY_ID"]!="")?substr($request_data["UF_CITY_ID"],0,12)."%":"390 000 010%");
		}
		?>
		<select class="no_select <?=(/*$request_data["UF_INNER_STATUS"]!=0&&*/intval($request_data[$name])==0&&in_array($name,$ty))?"red":""?>" name="<?=$name?>">
			<option value=""></option>
			<?
			if(!empty($filter)):
				$export_arr = HlBlockElement::GetList(11,array("UF_KLADR_NAME","UF_KLADR_CODE","UF_KLADR_SOKR"),$filter,array(),5000);
				while($arr = $export_arr->Fetch()){
					?><option <?=($arr["UF_KLADR_CODE"]==$request_data[$name])?"selected":""?> value="<?=$arr["UF_KLADR_CODE"]?>"><?=$arr["UF_KLADR_NAME"]." ".$arr["UF_KLADR_SOKR"];?></option><?
				}
			endif;
			?>
		</select>
		<?
	}
	function write_select_obj_type($hide=0,$red=1,$postfix=""){
		global $request_data;
		$hblock = 3;
		$name = "OBJ_TYPE";
	?>
		<select name="UF_OBJ_TYPE_H<?=$postfix?>" <?=($hide)?"disabled=\"disabled\" class=\"only_view_select no_select\"":"class=\"no_select\""?> style="display:none;">
			<option value="">-</option>
			<?
			$export_arr = HlBlockElement::GetList($hblock,array(),array(),array(),100);
			$old_value = 0;
			$ins = 0;
			echo "<optgroup id=\"OBJ_TYPE_0\"><option value=\"\"></option>";
			while($arr = $export_arr->Fetch()){
				if($arr["UF_".$name."_PARENT"]!=$old_value) {echo "</optgroup>"; echo "<optgroup id=\"OBJ_TYPE_".$arr["UF_".$name."_PARENT"]."\"><option value=\"\"></option>";$ins = 1;/*label=\"".Helper_realty::$arr_realty_type[$arr["UF_".$name."_PARENT"]]."\"*/}
				?><option value="<?=$arr["UF_".$name."_ID"]?>" <?=($arr["UF_".$name."_ID"]==$request_data["UF_".$name])?"selected":""?>><?=$arr["UF_".$name."_NAME"];?></option><?
				$old_value = $arr["UF_".$name."_PARENT"];
			}
			echo "</optgroup>";
			?>
		</select>
		<select name="UF_OBJ_TYPE<?=$postfix?>" <?=($hide)?"disabled=\"disabled\"":"" ?> class="no_select <?=(intval($request_data["UF_".$name])==0&&$red==1/*&&$request_data["UF_INNER_STATUS"]==1*/)?"red":""?> <?=($hide)?"only_view_select":""?>">
			<option value="">-</option>
			<?
			$export_arr = HlBlockElement::GetList($hblock,array(),array("UF_OBJ_TYPE_PARENT"=>($request_data["UF_REALTY_TYPE"]==6)?6:0),array(),100);
			$old_value = 0;
			$ins = 0;
			while($arr = $export_arr->Fetch()){
				?><option value="<?=$arr["UF_".$name."_ID"]?>" <?=($arr["UF_".$name."_ID"]==$request_data["UF_".$name])?"selected":""?>><?=$arr["UF_".$name."_NAME"];?></option><?
			}
			?>
		</select>
	<?
	}
	function write_sort_input($name = "", $label = "", $additional_class = "")
	{
		if ($additional_class != "") $additional_class = " ".$additional_class;
		?>
		<div class="sort_block<?=$additional_class?>">
			<input class="sort_field" type="hidden" name="sort_field[]" value="UF_<?=$name?>" <?=($name!="ADD_DATE")?"disabled":""?>>
			<input class="sort_type" type="hidden" name="sort_direction[]" value="DESC" <?=($name!="ADD_DATE")?"disabled":""?>>
			<span><?=$label?>:</span>
			<div class="sort_asc"></div>
			<div class="sort_desc <?=($name=="ADD_DATE")?"active_sort":""?>"></div>
		</div>
		<?
	}
	function check_all_fields_not_null(){
		global $request_data,$ty, $Project;
		$bool_arr=Array("UF_COMMENT_ORDER");
		$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_2","USER_TYPE_ID"=>"boolean") );
		while($arRes = $rsData->Fetch())
		{
			$bool_arr[]=$arRes["FIELD_NAME"];
		}
		foreach ($ty as $k=>$v)
		{
			if(in_array($v,array("UF_NARUZHN_REKLAMA","UF_N_REKLAMA","UF_WWW","UF_PRICE_CUST","UF_PRICE_SELL")))continue;
			if($Project->s_name=="domofey"&&in_array($v,array("UF_LAND_OWNER_TYPE")))continue;
			if(in_array($v,$bool_arr))continue;
			if(strlen($request_data[$v])==0||$request_data[$v]=="0")
			{//die("!!!".$v);
				return false;
			}
		}
		return true;
	}
	function correct_phone($phone) {
		$phone = str_replace(Array(" ","-","(",")"),"",$phone);
		if(strlen($phone)<=6&&strlen($phone)>0) $phone = "+74012".$phone;
		$phone = preg_replace("/^(8)(\d+)/","+7$2",$phone);
		echo $phone;
	}
	function correct_price($price) {
		$price = str_replace(' ', '', $price);
		$n = number_format($price, 0, ",", " ");
		if($n==0)$n="";
		return $n;
	}
}
?>