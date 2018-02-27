<?
final class SoapPortalInvent {
	private $Login = "WP";
	private $Pass = "user_WP";
	private $Url = /*"http://kopiya.invent-realty.ru/kopiya/ws/ws/baza?wsdl"*/"http://web1c.invent-realty.ru/invent/ws/ws/baza?wsdl";
	
	public $Client = null;
	public $Error = "";
	
	function __construct(){
		if(class_exists("SoapClient")){
			try {
				global $Project;
				if(($Project->s_name=="domofey"))
				{
					$this->SetUrl($Project->s_name);
					//die("!!!".$this->Url);
				}
				$this->Client = new SoapClient(
					$this->Url,
					 array (
						"login" => $this->Login,
						"password" => $this->Pass,
	                    "trace" => 1, 
						"exception" => 0
					)
				) or die("1");
			} catch (SoapFault $e) { 
				$this->Error = "Ошибка SOAP: (faultcode: ".$e->faultcode.", faultstring: ".$e->faultstring.")";
			}
		} else {
			$this->Error = "Class not exists: SoapClient.";
		}
	
	}
	
	public function GetFunctions(){
		return $this->Client ? $this->Client->__getFunctions() : array();
	}
	
	public function SetUrl($project_name){
		$this->Url = "http://web1c.invent-realty.ru/".$project_name."/ws/ws/baza?wsdl";
	}
	
	public function GetTypes(){
		return $this->Client ? $this->Client->__getTypes() : array();
	}
	public function NewApplication($Xml = ""){
		return $this->Client ? $this->Client->NewOrder(array("Data" => $Xml)) : "";
	}
	public function CloseApplication($Xml = ""){
		return $this->Client ? $this->Client->CloseOrder(array("Data" => $Xml)) : "";
	}
	public function ChangeCategoryApplication($Xml = ""){
		return $this->Client ? $this->Client->ChangeCategory(array("Data" => $Xml)) : "";
	}
}
class HlBlockElement {
	
	public static $HighloadBlock = Array();
	public static $Entity = Array();
	
	public static function HlBlock($IdIblock = 0){
		if($IdIblock > 0 && CModule::IncludeModule("highloadblock")){
			self::$HighloadBlock[$IdIblock] = \Bitrix\Highloadblock\HighloadBlockTable::getById($IdIblock)->fetch();
			self::$Entity[$IdIblock] = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity(self::$HighloadBlock[$IdIblock]);
		}
	}
	
	public static function GetList($IdIblock = 0,$Select = array(),$Filter = array(),$Order = array(),$Count = 10){
		$Result = null;
		if(!isset(self::$Entity[$IdIblock]))
		{	
			self::HlBlock($IdIblock);
		}
		if(isset(self::$Entity[$IdIblock])){
			$Query = new \Bitrix\Main\Entity\Query(self::$Entity[$IdIblock]);
			$Query->setSelect(empty($Select) ? array("*") : $Select);
			$Query->setFilter($Filter);
			$Query->setOrder($Order);
			$Result = new CDBResult($Query->exec());
			$Result->NavStart($Count);
		}
		return $Result;
	}
	public static function Add($IdIblock = 0,$Fields = array()){
		$Result = null;
		if(!isset(self::$Entity[$IdIblock]))
		{
			self::HlBlock($IdIblock);
		}
		if(isset(self::$Entity[$IdIblock])){
			$DataClass = self::$Entity[$IdIblock]->getDataClass();
			$Result = $DataClass::add($Fields);
		}
		return $Result;
	}
	public static function Update($IdIblock = 0,$Id = 0,$Fields = array()){
		$Result = null;
		if(!isset(self::$Entity[$IdIblock]))
		{
			self::HlBlock($IdIblock);
		}
		if(isset(self::$Entity[$IdIblock]) && $Id > 0 && !empty($Fields)){
			$DataClass = self::$Entity[$IdIblock]->getDataClass();
			$Result = $DataClass::update($Id,$Fields);
		}
		return $Result;
	}
	public static function Remove($IdIblock = 0,$Id){
		$Result = null;
		self::HlBlock($IdIblock);
		if(isset(self::$Entity[$IdIblock])){
			$DataClass = self::$Entity[$IdIblock]->getDataClass();
			$Result = $DataClass::delete($Id);
		}
		return $Result;
	}
}
function custom_mail($to, $subject, $message, $additional_headers, $additional_parameters) 
{ 
	include_once($_SERVER['DOCUMENT_ROOT'].'/lib/phpmailer/PHPMailerAutoload.php'); 
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->SMTPDebug = 0;
	$mail->Debugoutput = 'html';
	$mail->Host = 'smtp.gmail.com';
	$mail->Port = 587;
	$mail->SMTPSecure = 'tls';
	$mail->SMTPAuth = true;
	$mail->Username = "inventrealty@gmail.com";
	$mail->Password = "333741marlen";
	$mail->IsHTML(true); 
	$mail->CharSet = 'UTF-8';
	
	$additional_headers = $mail->HeaderLine('To', $mail->EncodeHeader($mail->SecureHeader($to))).$additional_headers; 
	$additional_headers = $mail->HeaderLine('Subject', $mail->EncodeHeader($mail->SecureHeader($subject))).$additional_headers; 
	$mail->Header = $additional_headers."\n"; 

	$mail->AddAddress($to); 
	$mail->Body = $message; //iconv("utf-8", "windows-1251", $message); 
	$mail->Subject = $subject;//iconv("utf-8", "windows-1251",$subject);	
	$mail->Sender = "inventrealty@gmail.com";	
	$mail->FromName = 'Invent-Realty.ru'; 
	$mail->From = "inventrealty@gmail.com"; 
	return $mail->Send(); 
} 
?>