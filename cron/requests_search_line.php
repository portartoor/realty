<?
	define("NO_KEEP_STATISTIC", true);
	define("NOT_CHECK_PERMISSIONS", true);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

	class RequestsSearchLine {
		
		private $Hl = 2;
		
		private function GetFieldNameHl($Arg0 = array(),$Arg1 = array()){
			$Data = array();
			$Query = CUserTypeEntity::GetList(array(), $Arg0);
			while($Answer = $Query->Fetch()){
				if(in_array($Answer["FIELD_NAME"],$Arg1)){continue;}
				$Data[] = $Answer["FIELD_NAME"];
			}
			return $Data;
		}
		
		public function Run(){
			$ListStrFields = $this->GetFieldNameHl(
				array(
					"ENTITY_ID"=>"HLBLOCK_".$this->Hl,
					"USER_TYPE_ID" => "string"
				),
				array("UF_SEARCH_LINE")
			);
			$this->Set($ListStrFields);

		}
		private function Set($ListFields = array()){
			$ListFields[] = "ID";
			$Query = HlBlockElement::GetList($this->Hl,$ListFields,array("UF_SEARCH_LINE" => false),array(),300);
			while($Answer = $Query->Fetch()){
				$Id = $Answer["ID"];
				unset($Answer["ID"]);
				foreach($Answer as $key => $value){if(trim($value) == ""){unset($Answer[$key]);}}
				$SearchLine = "";
				$SearchLine = str_replace("\n"," ",str_replace("\r"," ",trim(implode(", ",$Answer))));
				if($SearchLine != ""){
					HlBlockElement::Update($this->Hl,$Id,array("UF_SEARCH_LINE" => $SearchLine));
				}
			}
		}
	}
	$Rsl = new RequestsSearchLine();
	$Rsl->Run();
	//$Query = HlBlockElement::GetList(2,$ListFields,array("UF_SEARCH_LINE" => false),array(),100);
	//echo $Query->SelectedRowsCount();
?>