<?
class Rights
{
	public $s_name = "";
	public $prodazha = Array(56,450,454,291);
	public $sdacha = Array(57,451);
	public $pokupka = Array(452,455,143,292);
	public $siem = Array(453,144);
	function __construct(){
		if(NOT_CHECK_PERMISSIONS===TRUE)
		{
			$this->s_name = isset($_GET["project"])?($_GET["project"]):"invent";
		}
		else
		{
			global $USER; 
			if(in_array(32, CUser::GetUserGroup($USER->GetID())))
				$this->s_name = "domofey";
			else 
				$this->s_name = "invent";
		}
	}
	public function get_requests_file(){
		$all_upd = Array (
			2 => Array(
				"type" => "hblock",
				"file" => "B24_Catalogs",
				"name" => "REQUESTS",
				"hblock" => 2),
			20 => Array(
				"type" => "hblock",
				"file" => "domofey/d_B24_Catalogs",
				"name" => "REQUESTS",
				"hblock" => 20)
		);
		if($this->s_name=="invent")
			$i=2;
		elseif($this->s_name=="domofey")
			$i=20;
		$res = $all_upd[$i];
		return $res;
	}
	public function get_map(){
		$i="";
		if($this->s_name=="domofey")
			$i=$this->s_name;	
		return $i;
	}
	public function get_clients_hb_id(){
		$i=0;
		if($this->s_name=="invent")
			$i=10;
		elseif($this->s_name=="domofey")
			$i=19;
		return $i;
	}
	public function get_agents_hb_id(){
		$i=0;
		if($this->s_name=="invent")
			$i=5;
		elseif($this->s_name=="domofey")
			$i=18;
		return $i;
	}
	public function get_call_hb_id(){
		$i=0;
		if($this->s_name=="invent")
			$i=28;
		elseif($this->s_name=="domofey")
			$i=29;
		return $i;
	}
	public function get_postfix(){
		$p="";
		if($this->s_name=="invent")
			$p="";
		elseif($this->s_name=="domofey")
			$p="_DF";
		return $p;
	}
	public function add_postfix_to_fields($arr){
		$p = $this->get_postfix();
		$arr_to=Array();
		foreach($arr as $k=>$v)
		{
			if($k=="ID"||$k=="!ID")
				$arr_to[$k]=$v;
			else if(strpos($k,$p)===FALSE)
			{
				$arr_to[$k.$p]=$v;
			}
			else
			{
				$arr_to[$k]=$v;
			}
		}
		return $arr_to;
	}
	public function add_postfix_to_fields_1($arr){
		$p = $this->get_postfix();
		$arr_to=Array();
		foreach($arr as $k=>$v)
		{
			if($v=="ID"||$v=="!ID")
				$arr_to[$k]=$v;
			else
				$arr_to[$k]=$v.$p;
		}
		return $arr_to;
	}
	public function get_interes(){
		$i = 0;
		if($this->s_name == "domofey")
			$i = 24;
		else if($this->s_name == "invent")
			$i = 13;
		return $i;
	}
	public function status_close_hb(){
		$i = 0;
		if($this->s_name == "domofey")
			$i = 26;
		else if($this->s_name == "invent")
			$i = 15;
		return $i;
	}
	public function status_change_hb(){
		$i = 0;
		if($this->s_name == "domofey")
			$i = 25;
		else if($this->s_name == "invent")
			$i = 16;
		return $i;
	}
	public function get_id_C_category(){
		if($this->s_name == "domofey")
			$i = 505;
		else if($this->s_name == "invent")
			$i = 323;
		return $i;
	}
	public function get_order($i=0){
		$ar=Array();
		if($this->s_name == "domofey")
		{
			if($i==0)
				$ar=Array(343,347,355,384);
			else if($i==1)
				$ar=Array(352,354,375);
		}
		else if($this->s_name == "invent")
		{
			if($i==0)
				$ar=Array(171,176,259,265);
			else if($i==1)
				$ar=Array(269,268,155);
		}
		return $ar;
	}
	public function get_name_for_result($v){
		$w="";
		$prodazha = $this->prodazha;
		$sdacha = $this->sdacha;
		$pokupka = $this->pokupka;
		$siem = $this->siem;
		if(in_array($v,$prodazha))
			$w="продажа ";
		else if(in_array($v,$sdacha))
			$w="сдача ";
		else if(in_array($v,$pokupka))
			$w="покупка ";
		else if(in_array($v,$siem))
			$w="съём ";
		return $w;
	}
	public function get_name_for_view($v){
		$w="";
		$prodazha = $this->prodazha;
		$sdacha = $this->sdacha;
		$pokupka = $this->pokupka;
		$siem = $this->siem;
		if(in_array($v,$prodazha))
			$w="Продаётся ";
		else if(in_array($v,$sdacha))
			$w="Сдаётся ";
		else if(in_array($v,$pokupka))
			$w="Покупается ";
		else if(in_array($v,$siem))
			$w="Снимается ";
		return $w;
	}
}
?>