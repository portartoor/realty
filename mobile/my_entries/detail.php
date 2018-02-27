<?
require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("BodyClass", "lenta-page");
?>
<div class="lenta-wrapper">
	<div class="lenta-item">
		<div class="post-item-top-wrap">
			<div class="post-item-top">
				<div class="post-item-top-cont">
					<div class="avatar"></div>
					<a class="post-item-top-title" href="/mobile/users/?user_id=<?=$Owner["UF_BITRIX_USER"]?>">
						Test
					</a>
					<div class="post-item-top-topic">
						<span class="post-item-top-arrow">Тип операции</span>
						<span class="post-item-destination post-item-dest-all-users">
							Test
						</span>
					</div>
					<div id="datetime_block_detail" class="lenta-item-time"><?=date("d.m.Y H:i:s")?></div>
				</div>
			</div>
			<div class="post-item-post-block">
				<div class="post-item-text">
					<div class="post-item-text-requests">
						Test
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")?>