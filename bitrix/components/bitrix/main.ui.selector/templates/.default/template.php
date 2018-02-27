<?
/**
 * @var CBitrixComponentTemplate $this
 * @var $arParams
 * @var $arResult
 * @global $APPLICATION
 */
$component = $this->getComponent();
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $USER;

\CJSCore::init(array('socnetlogdest'));
?>
	<script>
		BX.ready(function() {
			BX.Main.Selector.create({
				id: '<?=$arParams['ID']?>',
				pathToAjax: '<?=$component->getPath()?>/ajax.php',
				containerId: BX('<?=$arParams['CONTAINER_ID']?>'),
				inputId: BX('<?=$arParams['INPUT_ID']?>'),
				tagId: BX('<?=$arParams['TAG_ID']?>'),
				bindNode: BX('<?=$arParams['BIND_ID']?>'),
				options: <?=\CUtil::phpToJSObject($arParams["OPTIONS"])?>,
				callback : {
					select: <?=(!empty($arParams["CALLBACK"]["select"]) ? $arParams["CALLBACK"]["select"] : 'null')?>,
					unSelect: <?=(!empty($arParams["CALLBACK"]["unSelect"]) ? $arParams["CALLBACK"]["unSelect"] : 'null')?>,
					openDialog: <?=(!empty($arParams["CALLBACK"]["openDialog"]) ? $arParams["CALLBACK"]["openDialog"] : 'null')?>,
					closeDialog: <?=(!empty($arParams["CALLBACK"]["closeDialog"]) ? $arParams["CALLBACK"]["closeDialog"] : 'null')?>,
					openSearch: <?=(!empty($arParams["CALLBACK"]["openSearch"]) ? $arParams["CALLBACK"]["openSearch"] : 'null')?>,
					closeSearch: <?=(!empty($arParams["CALLBACK"]["closeSearch"]) ? $arParams["CALLBACK"]["closeSearch"] : 'null')?>,
					openEmailAdd: <?=(!empty($arParams["CALLBACK"]["openEmailAdd"]) ? $arParams["CALLBACK"]["openEmailAdd"] : 'null')?>,
					closeEmailAdd: <?=(!empty($arParams["CALLBACK"]["closeEmailAdd"]) ? $arParams["CALLBACK"]["closeEmailAdd"] : 'null')?>
				},
				items : {
					selected: <?=\CUtil::phpToJSObject($arParams['ITEMS_SELECTED'])?>,
					hidden: <?=\CUtil::phpToJSObject($arParams['ITEMS_HIDDEN'])?>
				},
				entities: {
					users: <?=\CUtil::phpToJSObject($arResult['ENTITIES']['USERS'])?>,
					groups: <?=\CUtil::phpToJSObject($arResult['ENTITIES']['GROUPS'])?>,
					sonetgroups: <?=\CUtil::phpToJSObject($arResult['ENTITIES']['SONETGROUPS'])?>,
					department: <?=\CUtil::phpToJSObject($arResult['ENTITIES']['DEPARTMENTS'])?>
				}
			});
		});
	</script>

<?


?>