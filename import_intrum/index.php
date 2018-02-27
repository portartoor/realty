<!DOCTYPE html>
<html>
    <head></head>
    <body>
		<style>
		.myButton {
			-moz-box-shadow: 0px 0px 0px 2px #9fb4f2;
			-webkit-box-shadow: 0px 0px 0px 2px #9fb4f2;
			box-shadow: 0px 0px 0px 2px #9fb4f2;
			background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #7892c2), color-stop(1, #476e9e));
			background:-moz-linear-gradient(top, #7892c2 5%, #476e9e 100%);
			background:-webkit-linear-gradient(top, #7892c2 5%, #476e9e 100%);
			background:-o-linear-gradient(top, #7892c2 5%, #476e9e 100%);
			background:-ms-linear-gradient(top, #7892c2 5%, #476e9e 100%);
			background:linear-gradient(to bottom, #7892c2 5%, #476e9e 100%);
			filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#7892c2', endColorstr='#476e9e',GradientType=0);
			background-color:#7892c2;
			-moz-border-radius:10px;
			-webkit-border-radius:10px;
			border-radius:10px;
			border:1px solid #4e6096;
			display:inline-block;
			cursor:pointer;
			color:#ffffff;
			font-family:Arial;
			font-size:19px;
			padding:12px 37px;
			text-decoration:none;
			text-shadow:0px 1px 0px #283966;
		}
		.myButton:hover {
			background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #476e9e), color-stop(1, #7892c2));
			background:-moz-linear-gradient(top, #476e9e 5%, #7892c2 100%);
			background:-webkit-linear-gradient(top, #476e9e 5%, #7892c2 100%);
			background:-o-linear-gradient(top, #476e9e 5%, #7892c2 100%);
			background:-ms-linear-gradient(top, #476e9e 5%, #7892c2 100%);
			background:linear-gradient(to bottom, #476e9e 5%, #7892c2 100%);
			filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#476e9e', endColorstr='#7892c2',GradientType=0);
			background-color:#476e9e;
		}
		.myButton:active {
			position:relative;
			top:1px;
		}
		.enjoy-css {
			display: inline-block;
			-webkit-box-sizing: content-box;
			-moz-box-sizing: content-box;
			box-sizing: content-box;
			float: none;
			z-index: auto;
			width: 146px;
			height: auto;
			position: static;
			cursor: default;
			opacity: 1;
			margin: 0;
			padding: 10px 20px;
			overflow: visible;
			border: 1px solid #b7b7b7;
			-webkit-border-radius: 3px;
			border-radius: 3px;
			font: normal 16px/normal "Times New Roman", Times, serif;
			color: rgba(0,142,198,1);
			-o-text-overflow: clip;
			text-overflow: clip;
			background: rgba(252,252,252,1);
			-webkit-box-shadow: 2px 2px 2px 0 rgba(0,0,0,0.2) inset;
			box-shadow: 2px 2px 2px 0 rgba(0,0,0,0.2) inset;
			text-shadow: 1px 1px 0 rgba(255,255,255,0.66) ;
			-webkit-transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
			-moz-transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
			-o-transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
			transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
			-webkit-transform: none;
			transform: none;
			-webkit-transform-origin: 50% 50% 0;
			transform-origin: 50% 50% 0;
		}
		.return {
			margin: 20px 10px;
			font-size:22px;
			color:blue;
		}
		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script>
			$(document).ready(function(){
  
			   $("#import_one").click(function(){
				   var id = $("#num_obj").val();
				   
				   $.ajax({

						url: "/import_intrum/example/request/import_one.php",
						type: 'GET',
						data: {id:id},
						beforeSend: function() {
							$(".return").html('<img src="http://portal.invent-realty.ru/images/default.gif">');
						},
						success:function(data){
							$(".return").html(data);
						}

					});
				   
			   });
			   
			   $("#import_multiple").click(function(){
				   var id_from = $("#id_from").val();
				   var id_to = $("#id_to").val();
				   
				   $.ajax({

						url: "/import_intrum/example/request/import_multiple.php",
						type: 'GET',
						data: {id_from:id_from,id_to:id_to},
						beforeSend: function() {
							$(".return").html('<img src="http://portal.invent-realty.ru/images/default.gif">');
						},
						success:function(data){
							$(".return").html(data);
						}

					});
				   
			   });
			});
		</script>
		<h1>Импорт объектов в базу INTRUM</h1>
		<input id="num_obj" type="text" class="enjoy-css" placeholder="Номер заявки"> или груповая выгрузка: <input id="id_from" type="text" class="enjoy-css" placeholder="Начальный ID"> <input id="id_to" type="text" class="enjoy-css" placeholder="Финальный ID"><br><br>
		<a id="import_one" class="myButton">Один объект</a>
		<a id="import_multiple" class="myButton">Груповая выгрузка</a>
		<a target="_blank" href="/import_intrum/example/request/import_all.php" class="myButton">Все объекты (Bitrix24)</a>
		<!--<a target="_blank" href="/import_intrum/example/request/import.php" class="myButton">Все объекты (xml файл)</a>
		<a target="_blank" title="/ulpoad/intrum/request_in.xml" href="/import_intrum/example/request/import.php" class="myButton">Все объекты (из файла)</a>-->
		<div class="return">
		
		</div>
    </body>
</html>