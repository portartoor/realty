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
		private $ObjId_from = 0;
		private $ObjId_to = 0;
		private $Step = 30;
		private $StepNumber = 1;
		private $StepsCount = 0;
		private $start_id = 0;
		private $Hl = 2;
		
		private $XmlParser;
		
		private $StopElm = 0;
		private $StopElmNext = 0;
		
		function __construct(){
			parent::__construct();
			$this->MyTime = time();
			$this->ObjId_from = $_GET["id_from"];
			$this->ObjId_to = $_GET["id_to"];
		}
		
		function Init(){
			$File = null;
			$Data = "";

			$CheckArray_arenda = Array();
			$CheckArray_pokupka = Array();
			
			if (CModule::IncludeModule("iblock")):
				$Data = Array();
				$ListFields = Array("*");
				
				$count = $this->ObjId_to-$this->ObjId_from;
				$i=0;
				
				$this->StepsCount = $count/$this->Step;
				
				if (isset ($_GET["start_id"]) && isset ($_GET["StepNumber"])) {
					$this->start_id = $_GET["start_id"];
					$this->StepNumber = $_GET["StepNumber"];
				}
				else {
					$this->start_id = $this->ObjId_from;
				}
				
				if ($this->start_id<=$this->ObjId_to) {
					while ($i<=$this->Step) {
						$i++;
						
						if ($this->start_id<=$this->ObjId_to) {
							
							$Query = HlBlockElement::GetList($this->Hl,$ListFields,array("UF_ID"=>$this->start_id,"UF_OPERATION_TYPE"=>Array('143','292','144')),array(),1);
							while($Answer = $Query->Fetch()){
								
								if ($Answer["UF_OPERATION_TYPE"]==144) {
									if ($Answer["UF_REALTY_TYPE"]==6) {
										$Id = $Answer["ID"];
										unset($Answer["ID"]);
										foreach($Answer as $key => $value){if(trim($value) == ""){unset($Answer[$key]);}}
										$Data = $Answer;
										$this->NewObj($Data);
										
										$CheckArray_arenda[]=$Id;
									}
								}
								else {
									$Id = $Answer["ID"];
									unset($Answer["ID"]);
									foreach($Answer as $key => $value){if(trim($value) == ""){unset($Answer[$key]);}}
									$Data = $Answer;
									$this->NewObj($Data);
									
									$CheckArray_pokupka[]=$Id;
								}
								
							}
							
							$this->start_id++;
						}
						else {
							break;
						}
						
					}
					
					$this->NextJS();
				}
				else {
				
					?>
					<center><h1>Upload Complete!</h1></center>
					<?
					
				}
				
			endif;
		}
	
		function NewObj($Data = array()){
				$this->AddNewItemB24($Data);
		}
		
		function NextJS(){
			?>
			<center><h1>Step: <?=$this->StepNumber?></h1></center>
			<center><h1>Time: <?=(time()-$this->MyTime);?> sec.</h1></center>
			<?
			$this->StepNumber++;
			?>
			<script type="text/javascript">
				var IntervalId = setInterval( function() { 
						window.location.href = "/import_intrum/example/request/import_multiple.php?id_from=<?=$this->ObjId_from;?>&id_to=<?=$this->ObjId_to;?>&StepNumber=<?=$this->StepNumber;?>&start_id=<?=$this->start_id;?>";
						clearInterval(IntervalId);
					}, 
					3000
				);
			</script>
			<?
		}
		
	}
	
	
	global $Sa;
	$Sa = new SynApp();
	$Sa->Init();
?>