<?
	define("NO_KEEP_STATISTIC", true);
	define("NOT_CHECK_PERMISSIONS", true);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	require($_SERVER["DOCUMENT_ROOT"]."/import_intrum/example/request/import_class.php");
	
	class XmlData  {
		static $Count = 0;
		public static $XmlArr = array();
		public static $Key = array();
		public static $Val = "";
		public static $Depth=0;		
		public static $Photo_index=0;
	}
	
	class SynApp extends SyncRequestsInClass {
		private $MyTime = 0;
		private $ObjId = 0;
		private $Hl = 2;
		
		private $XmlParser;
		
		private $StopElm = 0;
		private $StopElmNext = 0;
		
		function __construct(){
			parent::__construct();
			$this->MyTime = time();
			$this->ObjId = $_GET["id"];
		}
		
		function Init(){
			$File = null;
			$Data = "";
			if (CModule::IncludeModule("iblock")):
				$Data = Array();
				$ListFields = Array("*");
				$Query = HlBlockElement::GetList($this->Hl,$ListFields,array("UF_ID"=>$this->ObjId,"UF_OPERATION_TYPE"=>Array('143','292','144')),array(),300);
				while($Answer = $Query->Fetch()){
					
					if ($Answer["UF_OPERATION_TYPE"]==144) {
						
						if ($Answer["UF_REALTY_TYPE"]==6) {
							$Id = $Answer["ID"];
							unset($Answer["ID"]);
							foreach($Answer as $key => $value){if(trim($value) == ""){unset($Answer[$key]);}}
							$Data = $Answer;
							$this->NewObj($Data);
						}
						
					}
					else {
						$Id = $Answer["ID"];
						unset($Answer["ID"]);
						foreach($Answer as $key => $value){if(trim($value) == ""){unset($Answer[$key]);}}
						$Data = $Answer;
						$this->NewObj($Data);
					}
				}
				
			endif;
		}
	
		function NewObj($Data = array()){
				$this->AddNewItemB24($Data);
				
					
		}
	}
	global $Sa;
	$Sa = new SynApp();
	$Sa->Init();
?>