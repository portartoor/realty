<?  die();
	define("NO_KEEP_STATISTIC", true);
	define("NOT_CHECK_PERMISSIONS", true);
	if(isset($_GET["next"]))
		$_GET["PAGEN_1"]=round($_GET["next"]/1000,0,PHP_ROUND_HALF_DOWN);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
	$Project = new Rights();
	if($Project->s_name=="domofey")die();
	class Clean {
		private $MyTime = 0;
		private $FileId = 0;
		
		private $FileMaxCount =1000; 

		private $StopElm = 0;
		private $StopElmNext = 0;
		private $StopFile = "/clean_interes_next";
		
		private $StopClassFile = "/clean_interes_stop"; 
		
		function __construct(){
			global $Project;
			$data_res = $Project->get_requests_file();
			$this->MyTime = time();
						
			$this->StopFile = $_SERVER["DOCUMENT_ROOT"]."upload/cron".$this->StopFile.$Project->get_postfix().".txt";
			if(file_exists($this->StopFile)){
				$this->StopElm = intval(file_get_contents($this->StopFile));
			} else {
				file_put_contents($this->StopFile,$this->StopElm);
			}
			$this->StopElmNext = $this->StopElm+$this->FileMaxCount;
			file_put_contents($this->StopFile,$this->StopElmNext);
			$this->StopClassFile = $_SERVER["DOCUMENT_ROOT"]."upload/cron".$this->StopClassFile.$Project->get_postfix().".txt";
			
			if(file_exists($this->StopClassFile)){
				if((time()-filemtime($this->StopClassFile))>3600)
				{
					unlink($this->StopClassFile);
					unlink($this->StopFile);
				}
				else
				{
					echo "<center><h1>".file_get_contents($this->StopClassFile)."</h1></center>";
					die();
				}
			}
		}
		function Init(){
			global $Project;
			$data_res = $Project->get_requests_file();
			$postfix = $Project->get_postfix();
			$this->highloadblock = $Project->get_interes();
			//$_GET["PAGEN_3"]=round($this->StopElm/$this->FileMaxCount,0,PHP_ROUND_HALF_DOWN);
			echo $_GET["PAGEN_1"]."!!!<br>";
			$request = HlBlockElement::GetList($this->highloadblock,array("UF_REQUEST_S".$postfix,"UF_REQUEST_F".$postfix,"ID"),array(),array(),$this->FileMaxCount);
			$whole_count = $request->SelectedRowsCount();
			WHILE($request_data = $request->Fetch())
			{
				if(!in_array($request_data["UF_REQUEST_S".$postfix],$arr_clear))
					$arr_clear[]=$request_data["UF_REQUEST_S".$postfix];
				if(!in_array($request_data["UF_REQUEST_F".$postfix],$arr_clear))
					$arr_clear[]=$request_data["UF_REQUEST_F".$postfix];
			}
			$count = sizeof($arr_clear);
			if($count==0)die("all ok");
			/*$arr_ff = Array("LOGIC"=>"OR"); 
			foreach ($arr_clear as $k=>$v)
			{
				$arr_ff[]=Array("ID"=>$v);
			}*/
			echo "!!!<xmp>";print_r($arr_clear);echo "</xmp>!!!";
			
			$request_1 = HlBlockElement::GetList($data_res["hblock"],array("ID"),Array("ID"=>$arr_clear)/*array($arr_ff)*/,array(),$this->FileMaxCount*2);
			WHILE($request_data = $request_1->Fetch())
			{
				if(!empty($request_data))
				{
					$k = array_search($request_data["ID"],$arr_clear);
					if($k!==FALSE)
					{
						echo "unset ".$arr_clear[$k]."<br>";
						unset($arr_clear[$k]);
					}
					else echo "WTF<br>";
				}
			}
			echo "DIFF ".$count." = ".($count - sizeof($arr_clear))."<br>";
			echo "!!!<xmp>";print_r($arr_clear);echo "</xmp>!!!";
			$request = HlBlockElement::GetList($this->highloadblock,array("ID","UF_REQUEST_S".$postfix,"UF_REQUEST_F".$postfix),array("LOGIC"=>"OR",array("UF_REQUEST_S".$postfix=>$arr_clear),array("UF_REQUEST_F".$postfix=>$arr_clear)),array(),$this->FileMaxCount*10);
			WHILE($request_data = $request->Fetch())
			{
				echo "del ".$request_data["ID"]." ".$request_data["UF_REQUEST_S".$postfix]." ".$request_data["UF_REQUEST_F".$postfix]."<br>";
				//die();
				HlBlockElement::Remove($this->highloadblock,$request_data["ID"]);
			}
			if($whole_count<$this->StopElmNext)
				$this->UploadCompleted();
			else
				$this->NextJS();
		}
		
		function UploadCompleted(){
			$Str = "Upload completed date: ".date("d.m.Y H:i:s").".";
			echo "<center><h1>".$Str."</h1></center>";
			file_put_contents($this->StopClassFile,$Str);
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
	$Sa = new Clean();
	$Sa->Init();
?>