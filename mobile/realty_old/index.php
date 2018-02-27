<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
if($USER->GetID()==752):?>
				<?
							$APPLICATION->IncludeComponent("bitrix:main.post.form", "mobile", array(
							"FORM_ACTION_URL" => "",//SITE_DIR."mobile/log/".(intval($_REQUEST["group_id"]) > 0 ? "?group_id=".intval($_REQUEST["group_id"]) : ""), // post action
							/*"SOCNET_GROUP_ID" => intval($_REQUEST["group_id"]),
							"POST_PROPERTY" => $arPostProperty,
							"FORM_ID" => "blogPostForm",
							"FORM_TARGET" => "_self",
							"POST_ID" => intval($_REQUEST["post_id"])*/
						),
						false,
						Array("HIDE_ICONS" => "N")
					);
					?> 
			<?endif?>