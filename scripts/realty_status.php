<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$filename = $_SERVER['DOCUMENT_ROOT']."/upload/realty_status.php";

if(!CSite::InGroup(array(1)))die("Нет прав доступа");
if(isset($_GET["active"])&&$_GET["active"]=="Y")
{
	file_put_contents($filename,"Y");
	echo "Раздел <a href='http://portal.invent-realty.ru/realty/'>relaty</a> включен";
}
else if(isset($_GET["active"])&&$_GET["active"]=="N")
{
	file_put_contents($filename,"N");
	echo "Раздел <a href='http://portal.invent-realty.ru/realty/'>relaty</a> выключен";
}
else {

	?>
	<script src="/bitrix/templates/realty/js/jquery-1.12.1.min.js"></script>
	<script>
	$( document ).ready(function() {
		$( ".btn" ).click(function() {
			$.get( "/scripts/realty_status.php?active="+$(this).data("active"), function( data ) {
			  $( ".answer" ).html( data ); 
			});
		});
	});
	</script>
	<style>
		.btn {
			background:#004080;
			display:inline-block;
			color:#fff;
			text-align:center;
			width:200px;
			height:80px;
			line-height:80px;
			font-size:18px;
			border:1px solid #fff;
			text-decoration:none;
			text-transform:uppercase;
		}
		.btn:hover {
			background:#003366;
		}
	</style>
	<a class="btn" data-active="Y" href="#">Включить</a><a href="#" class="btn" data-active="N">Выключить</a>
	<div class="answer"></div>
<?}
?>