<?
if($_GET["next"])
{
	$_GET["PAGEN_3"]=$_GET["next"];
}
	define("NO_KEEP_STATISTIC", true);
	define("NOT_CHECK_PERMISSIONS", true);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	require($_SERVER["DOCUMENT_ROOT"]."/import_intrum/example/request/import_class.php");
	
	class SynAppAll extends SyncRequestsInClass {
		private $MyTime = 0;
		private $Page = 1;
		private $Hl = 2;
	
		private $XmlParser;
		private $FileMaxCount =10; 
		private $countMax =0;
		
		private $StopElm = 0;
		private $StopElmNext = 0;			
		private $StopFile = "/syn_request_in_next";

		
		
		function __construct(){
			parent::__construct();
			$this->MyTime = time();
            if(isset($_GET["next"]))
				$this->Page = $_GET["next"];
			else 
				$this->Page = 1; 
		}
		function Init(){
			$File = null;
			$Data = "";
			if (CModule::IncludeModule("iblock")):
				$Data = Array();
				$ListFields = Array("*");	
				$filter = array("UF_OPERATION_TYPE"=>Array('143','292','144'));
				$Query = HlBlockElement::GetList($this->Hl,$ListFields,$filter,array(),$this->FileMaxCount);
				$this->countMax = $Query->SelectedRowsCount();
				if(intval($this->Page) * $this->FileMaxCount > $this->countMax)
				{
					$this->UploadCompleted();
				}
				else
				{
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
					$this->NextJS();
				}
			endif;
		}
		function UploadCompleted(){
			$Str = "Upload completed date: ".date("d.m.Y H:i:s").".";
			echo "<center><h1>".$Str."</h1></center>";
			//file_put_contents($this->StopClassFile,$Str);
		}
		function NewObj($Data = array()){
			//echo "<pre>";print_r($Data);echo "</pre>";
			$this->AddNewItemB24($Data);
		}
		function NextJS(){
			global $Project;
			?>
			<center><h1>Page: <?=$this->Page?></h1></center>
			<center><h1>Time: <?=(time()-$this->MyTime);?> sec.</h1></center>
			<script type="text/javascript">
				var IntervalId = setInterval( function() { 
						window.location.href = "?next=<?=intval($this->Page)+1?>";
						clearInterval(IntervalId);
					}, 
					5000
				);
			</script>
			<?
		}
	}
	global $Sa;
	$Sa = new SynAppAll();
	$Sa->Init();
?>