<?
require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("BodyClass", "lenta-page");
?>
<script type="text/javascript">
var params = {
   callback:function(){     
     app.alert({title:"addRightButton", text:"Вы нажали правую кнопку!"});        
   },
   name:"Фильтр",
   type:"text"
 };
BXMobileApp.UI.Page.TopBar.addRightButton(params);
</script>
<div class="lenta-wrapper">
	<?$Query = HlBlockElement::GetList(2, array("*"), array(), array(), 20);?>
	<?while($Answer = $Query->Fetch()):?>
	<?
		
		if($Answer["UF_KITCHEN_SQUARE"] == 0){$Answer["UF_KITCHEN_SQUARE"] = "-";}
		if($Answer["UF_LIVING_SQUARE"] == 0){$Answer["UF_LIVING_SQUARE"] = "-";}
		if($Answer["UF_TOTAL_SQUARE"] == 0){$Answer["UF_TOTAL_SQUARE"] = "-";}
		$Addr = "";
		if($Answer["UF_CITY_ID"] != ""){
			$Addr .= $Answer["UF_CITY_ID"];
			if($Answer["UF_CITY_REGION"] != ""){
				$Addr .= ", ".$Answer["UF_CITY_REGION"];
			}
		}
		$Owner = array();
		$Owner = HlBlockElement::GetList(5, array("*"), array("UF_AGENT_ID" => $Answer["UF_AGENT"]), array(), 1)->Fetch();
		
		$ImgSrc = "";
		
		if(!empty($Answer["UF_PHOTO_PREVIEW"])){
			$File = CFile::ResizeImageGet(
				$Answer["UF_PHOTO_PREVIEW"],
				array("width"=>200, "height"=>200), 
				BX_RESIZE_IMAGE_EXACT,
				true
			);
			$ImgSrc = $File["src"];
		} else {
			$PreviewImg = file_get_contents("http://invent-realty.ru/help_portal_ir/get_preview_img_object.php?Id=".$Answer["UF_ID"]);
			$ImgSrc = ($PreviewImg != "" ? $PreviewImg : "");
		}
		$ImgSrc = ($ImgSrc == "" ? "/bitrix/templates/realty/images/soon.jpg" : $ImgSrc);
		
	?>
	<div class="lenta-item">
		<div class="post-item-top-wrap">
			<div class="post-item-top">
				<div class="post-item-top-cont">
					<div class="avatar"></div>
					<?if(!empty($Owner)):?>
					<a class="post-item-top-title" href="/mobile/users/?user_id=<?=$Owner["UF_BITRIX_USER"]?>">
						<?=$Owner["UF_AGENT_NAME"]?>
					</a>
					<?endif;?>
					<div class="post-item-top-topic">
						<span class="post-item-top-arrow">Тип операции</span>
						<span class="post-item-destination post-item-dest-all-users">
							<?if($Answer["UF_OPERATION_TYPE"] == 56){
								echo "Продаётся";
							} elseif($Answer["UF_OPERATION_TYPE"] == 57){
								echo "Сдаётся";
							} elseif($Answer["UF_OPERATION_TYPE"] == 143){
								echo "Покупается";
							} elseif($Answer["UF_OPERATION_TYPE"] == 144){
								echo "Снимается";
							}?>
						</span>
					</div>
					<div id="datetime_block_detail" class="lenta-item-time"><?=date("d.m.Y H:i:s")?></div>
				</div>
			</div>
			<div class="post-item-post-block">
				<div class="post-item-text">
					<div class="post-item-text-requests">
						<div class="post-item-text-requests-left">
							<img src="<?=$ImgSrc?>"/>
						</div>
						<div class="post-item-text-requests-right">
							<div class="post-item-text-requests-title">Заявка №<?=$Answer["UF_ID"]?></div>
							<div class="post-item-text-requests-street"><?=$Answer["UF_ADDR_STREET"]?></div>
							<?if($Addr != ""):?>
								<div class="post-item-text-requests-addr"><?=$Addr?></div>
							<?endif;?>
							<div class="post-item-text-requests-desc">
							<?if(in_array($Answer["UF_OBJ_TYPE"],array(1,2))){ //Квартира, Комната
								echo ($Answer["UF_OBJ_TYPE"] == 1 ? "Квартира" : "Комната")." ";
								echo $Answer["UF_ROOMS"]."-комнатная, ";
								echo $Answer["UF_ETAGE"]."/".$Answer["UF_ETAGE_COUNT"]." этаж, ";
								echo $Answer["UF_TOTAL_SQUARE"]." / ";
								echo $Answer["UF_LIVING_SQUARE"]." / ";
								echo $Answer["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
							} else if(in_array($Answer["UF_OBJ_TYPE"],array(3))){//Индивидуальный дом
								echo ($Answer["UF_OBJ_TYPE"] == 3 ? "Дом" : "Эллинг")." ";
								echo $Answer["UF_ROOMS"]."-комнатный, ";
								echo $Answer["UF_ETAGE_COUNT"]."-этажный, ";
								echo $Answer["UF_TOTAL_SQUARE"]." / ";
								echo $Answer["UF_LIVING_SQUARE"]." / ";
								echo $Answer["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
							} else if(in_array($Answer["UF_OBJ_TYPE"],array(19,24))){//Дача,Эллинг
								echo ($Answer["UF_OBJ_TYPE"] == 24 ? "Эллинг" : "Дача")." ";
								echo $Answer["UF_ROOMS"]."-комнатная, ";
								echo $Answer["UF_TOTAL_SQUARE"]." / ";
								echo $Answer["UF_LIVING_SQUARE"]." / ";
								echo $Answer["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
							} else if(in_array($Answer["UF_OBJ_TYPE"],array(5))){//Земля
								echo "Земля ".$Answer["UF_LOT_SQUARE"]." сот.";
							} else if(in_array($Answer["UF_OBJ_TYPE"],array(20))){//Секция
								echo "Секция ";
								echo $Answer["UF_ROOMS"]."-комнатный, ";
								echo $Answer["UF_TOTAL_SQUARE"]." / ";
								echo $Answer["UF_LIVING_SQUARE"]." / ";
								echo $Answer["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
							} else if(in_array($Answer["UF_OBJ_TYPE"],array(23))){//Гаражи и стоянки
								echo "Гараж ".$Answer["UF_KITCHEN_SQUARE"]." м<sup>2</sup>";
							}?>
							</div>
							<div class="post-item-text-requests-price">
								<?=number_format($Answer["UF_PRICE"], 0, ",", " ")?> руб.
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="post-item-inform-wrap">
				<div 
					class="post-item-informers post-item-inform-likes"
					onclick="app.loadPageBlank({url:'/mobile/test/detail.php?Id=<?=$Answer["ID"]?>'})">
						<div class="post-item-inform-left">Подробнее</div>
				</div>
			</div>
		</div>
	</div>
	<?endwhile;?>
</div>
<style type="text/css">
	.post-item-text-requests {
		overflow: hidden;
	}
	.post-item-text-requests-left {
		float:left;
	}
	.post-item-text-requests-left img {
		width: 100px;
	}
	.post-item-text-requests-right {
		margin: 0px 0px 0px 120px;
	}
	.post-item-text-requests-street,
	.post-item-text-requests-title{
		color: #4b66ab;
		font-size:18px;
		font-weight: bold;
	}
	.post-item-text-requests-addr{
		color: #929191;
		font-size: 16px;
		margin: 10px 0px 10px 0px;
	}
	.post-item-text-requests-desc{
		font-size: 16px;
	}
	.post-item-text-requests-price{
		color: #4b66ab;
		font-size: 18px;
		font-weight: bold;
		margin: 10px 0px 0px 0px;
	}
</style>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")?>