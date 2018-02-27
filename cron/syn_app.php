<?
	define("NO_KEEP_STATISTIC", true);
	define("NOT_CHECK_PERMISSIONS", true);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	require($_SERVER["DOCUMENT_ROOT"]."/cron/sync_requests_class.php");
	require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
	$Project = new Rights();
	
	class XmlData  {
		static $Count = 0;
		public static $XmlArr = array();
		public static $Key = array();
		public static $Val = "";
		public static $Depth=0;		
		public static $Photo_index=0;
		public static function SetStart($Name){
			if($Name=="ITEM")self::$Depth++;
			if(self::$Depth==1)self::$Key[] = $Name;
		}
		public static function SetData($Value){
			self::$Val .= trim($Value);
		}
		public static function SetEnd($Name){
			global $Sa;
			if($Name == "ITEM" && self::$Depth==2)
			{
				self::$Photo_index++;
			}
			else if($Name=="UF_PHOTOS")
			{
				self::$Photo_index=0;
			}
			else if(self::$Depth==2)
			{
				self::$XmlArr["UF_PHOTOS"][self::$Photo_index][strtoupper($Name)] = trim(self::$Val);
			}
			else if($Name == "ITEM" && !empty(self::$XmlArr)){
				$Sa->NewXml(self::$XmlArr);
				self::$XmlArr = array();
			}
			else {
				self::$XmlArr[strtoupper($Name)] = trim(self::$Val);
			}
			
			self::$Val = "";
			self::$Key = array();
			if($Name=="ITEM")self::$Depth--;
		}
	}
	
	function SetElmHandStart($Parser,$Name,$Attrs){XmlData::SetStart($Name);}
	function SetElmHandStop($Parser,$Name){XmlData::SetEnd($Name);}
	function SetCharDataHand($Parser, $Value){XmlData::SetData($Value);}
	
	class SynApp extends SyncRequest {

		private $MyTime = 0;
		
		private $FileName = "orders_#ID#.txt";
		private $FileSearch = "#ID#";
		private $FileId = 0;
		
		private $FileMaxCount = 150; 
		
		private $Path = "orders.xml";
		private $XmlParser;
		private $NewDir = "/orders/";
		
		private $StopElm = 0;
		private $StopElmNext = 0;
		private $StopFile = "/syn_app_next.txt";
		
		private $StopClassFile = "/syn_app_stop.txt"; 
		
		function __construct(){
			parent::__construct();
			$this->MyTime = time();
				
			global $Project;
			$file = $Project->get_map();
			$this->Path = $_SERVER["DOCUMENT_ROOT"]."upload/1c/".$file.$this->Path;
			if(!file_exists($this->Path)){die("File not found: ".$this->Path);}
			
			$this->StopFile = $_SERVER["DOCUMENT_ROOT"]."upload/cron".$this->StopFile;
			if(file_exists($this->StopFile)){
				$this->StopElm = intval(file_get_contents($this->StopFile));
			} else {
				file_put_contents($this->StopFile,$this->StopElm);
			}
			$this->StopElmNext = $this->StopElm+$this->FileMaxCount;
			
			$this->StopClassFile = $_SERVER["DOCUMENT_ROOT"]."upload/cron".$this->StopClassFile;
			
			if(file_exists($this->StopClassFile)){
				echo "<center><h1>".file_get_contents($this->StopClassFile)."</h1></center>";
				die();
			}
		}
		function Init(){
			$File = null;
			$Data = "";
			$this->XmlParser = xml_parser_create();
			xml_set_element_handler($this->XmlParser,"SetElmHandStart","SetElmHandStop");
			xml_set_character_data_handler($this->XmlParser, "SetCharDataHand");
			if ($File = fopen($this->Path, "r")) {
				while ($Data = fread($File, 4096)) {
				    xml_parse($this->XmlParser, $Data, feof($File));
				}
				fclose($File);
			} else {
				die("File not read: ".$this->Path);
			}
			xml_parser_free($this->XmlParser);
			
			/*$this->NewXml(XmlData::$XmlArr);*/
			//$this->UploadCompleted();
		}
		
		function UploadCompleted(){
			$nowdata = date("d.m.Y H:i:s");
			$Str = "Upload complete date: ".$nowdata.".";
			echo "<center><h1>".$Str."</h1></center>";
			file_put_contents($this->StopClassFile,$Str);
		}

		function NewXml($Data = array()){
			if($this->FileId >= $this->StopElm){
				if($this->FileId == $this->StopElmNext){
					file_put_contents($this->StopFile,$this->FileId);
					$this->NextJS();
					die();
				}
				//echo "<pre>";print_r($Data);echo "</pre>";//die();
				$this->AddNewItem($Data);
				//$FileName = $this->NewDir.str_replace($this->FileSearch,$this->FileId,$this->FileName);
				//file_put_contents($FileName,json_encode($Data));
			}
			++$this->FileId;
		}
		function NextJS(){
			global $Project;
			?>
			<center><h1>Pack: <?=$this->StopElm?> to <?=$this->StopElmNext?></h1></center>
			<center><h1>Time: <?=(time()-$this->MyTime);?> sec.</h1></center>
			<script type="text/javascript">
				var IntervalId = setInterval( function() { 
						window.location.href = "?project=<?=$Project->s_name?>&next=<?=$this->StopElmNext?>";
						clearInterval(IntervalId);
					}, 
					5000
				);
			</script>
			<?
		}
	}
	global $Sa;
	$Sa = new SynApp();
	$Sa->Init();
?>