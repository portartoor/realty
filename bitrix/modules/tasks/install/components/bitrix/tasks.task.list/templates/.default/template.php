<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<?if(is_array($arResult['ERROR']['FATAL']) && !empty($arResult['ERROR']['FATAL'])):?>
	<?foreach($arResult['ERROR']['FATAL'] as $error):?>
		<?=ShowError($error['MESSAGE'])?>
	<?endforeach?>
<?else:?>

	<?if(is_array($arResult['ERROR']['WARNING'])):?>
		<?foreach($arResult['ERROR']['WARNING'] as $error):?>
			<?=ShowError($error['MESSAGE'])?>
		<?endforeach?>
	<?endif?>

	<div id="sls-<?=$arResult['TEMPLATE_DATA']['RANDOM_TAG']?>">
		<pre>
		<?print_r($arResult);?>
		</pre>
	</div>

	<?
	if((string) $arResult['TEMPLATE_DATA']['EXTENSION_ID'] != '')
	{
		CJSCore::Init($arResult['TEMPLATE_DATA']['EXTENSION_ID']);
	}
	?>
	<script>

		if (!window.BX && top.BX) window.BX = top.BX;

		var q = new BX.Tasks.Util.Query(<?=CUtil::PhpToJSObject(array(
			'url' => $this->__component->getPath().'/ajax.php'
		))?>);

		/*
		q.add('socialnetwork.user.checkmemberofgroup');
		q.add('task.elapsedtime.get', {id: 1});

		q.bindEvent('executed', function(response){
			console.dir('Done!');
			console.dir(response);
		});

		q.execute();
		*/

	</script>

<?endif?>