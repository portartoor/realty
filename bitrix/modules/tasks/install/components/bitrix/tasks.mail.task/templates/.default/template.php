<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<?$data = $arResult['DATA']['TASK'];?>
<?$originator = $data[\Bitrix\Tasks\Manager\Task\Originator::getCode(true)];?>
<?$path = \Bitrix\Tasks\UI\Task::fillActionPath($arParams['PATH_TO_TASKS_TASK'], $data['ID'])?>

<table cellpadding="0" cellspacing="0" border="0" align="left" style="border-collapse: collapse;mso-table-lspace: 0pt;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: 14px;width: 100%;">
	<!---->
	<tr>
		<td align="left" valign="top" style="border-collapse: collapse;border-spacing: 0;padding: 3px 15px 8px 0;text-align: left;">
			<table>
				<td width="50" style="border-collapse: collapse;border-spacing: 0;padding: 0 17px 0 0;width: 50px;;">
					<img height="50" width="50" src="<?=$originator['AVATAR']?>" alt="user" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;border-radius: 50%;display: block;">
				</td>
				<td width="" style="border-collapse: collapse;border-spacing: 0;padding: 0 17px 0 0;">
					<a href="<?=$path?>" target="_blank" style="color:#586777;font-size: 14px;font-weight: bold;vertical-align: top; text-decoration: none;"><?=htmlspecialcharsbx($originator['NAME_FORMATTED'])?></a>
					<img height="12" width="20" src="<?=$arResult['TEMPLATE_FOLDER']?>/img/arrow.gif" alt="&rarr;" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;display: inline;font-size: 19px;vertical-align: top;line-height: 15px;">
								<span style="color: #7f7f7f;font-size: 14px;vertical-align: top;">
									<span style="color: #7f7f7f;font-size: 14px;vertical-align: top;"><?=Loc::getMessage('TASKS_CREATED_TASK_'.$originator['PERSONAL_GENDER'])?></span>
								</span>
				</td>
			</table>
		</td>
	</tr>
	<!---->
	<tr>
		<td valign="top" style="background: #fffae3; border-collapse: collapse;border-spacing: 0;color: #000000;font-size: 14px;vertical-align: top;padding: 18px 15px 10px;">
			<b style="font-size:18px;"><?=htmlspecialcharsbx($data['TITLE'])?></b>
			<br />
			<br />
			<?if((string) $data['DEADLINE'] != ''):?>
			<div style="color:#4c4b44;font-size:13px;">
				<?=Loc::getMessage('TASKS_DEADLINE')?>: <b style="font-weight:normal;color:#000;margin-right:10px"><?=htmlspecialcharsbx($data['DEADLINE'])?></b>
			</div>
			<br />
			<?endif?>
			<?if($data['STATUS'] == CTasks::METASTATE_EXPIRED):?>
				<div style="border-radius:2px;font-size:13px;display: inline-block;background: #fcbe9e;padding:10px 15px;color:#000;"><?=Loc::getMessage('TASKS_EXPIRED')?></div>
			<?else:?>
				<div style="border-radius:2px;font-size:13px;display: inline-block;background: #<?if($data["REAL_STATUS"] == CTasks::STATE_DEFERRED):?>fee178<?else:?>e3f1b8<?endif?>;padding:10px 15px;color:#000;"><?=Loc::getMessage("TASKS_STATUS_".$data["REAL_STATUS"])?><?if((string) $data["STATUS_CHANGED_DATE"] != ''):?><?if($arResult['S_NEEDED']):?> <?=Loc::getMessage('TASKS_SIDEBAR_START_DATE')?><?endif?> <b><?=htmlspecialcharsbx($data["STATUS_CHANGED_DATE"])?></b><?endif?></div>
			<?endif?>
			<br />
			<br />
			<?if((string) $data['DESCRIPTION'] != ''):?>
			<b><?=Loc::getMessage('TASKS_DESCRIPTION')?>:</b>
			<p style="margin-top:5px;font-size:14px;"><?=$data['DESCRIPTION']?></p>
			<?endif?>
			<?if(!empty($data['SE_CHECKLIST'])):?>
			<b><?=Loc::getMessage('TASKS_CHECKLIST')?>:</b>
			<p style="margin-top:5px;font-size:14px;">
				<?$i = 1;?>
				<?foreach($data['SE_CHECKLIST'] as $item):?>
					<?if(\Bitrix\Tasks\UI\Task\CheckList::checkIsSeparatorValue($item['TITLE'])):?>
						------------------------ <br />
					<?else:?>
						– <?=$item['TITLE_HTML']?> <br />
					<?endif?>
					<?$i++;?>

					<?if($i > $arResult['CHECKLIST_LIMIT']):?>
						<?break;?>
					<?endif?>

				<?endforeach?>
			</p>
			<?if($arResult['CHECKLIST_MORE']):?>
				<a href="<?=$path?>" style="border-bottom: 1px dashed #969999;text-decoration:none; color:#969999; font-size:11px;"><?=Loc::getMessage('TASKS_MORE')?> <?=intval($arResult['CHECKLIST_MORE'])?></a>
			<?endif?>
			<br />
			<br />
			<?endif?>

			<?/*
			<b><?=Loc::getMessage('TASKS_FILES')?>:</b>
			<p style="margin-top:5px;font-size:14px;">
				<a href="#" target="_blank" style="color: #146cc5;font-size:12px;">document.docx</a><br />
				<a href="#" target="_blank" style="color: #146cc5;font-size:12px;">presentation.ppt</a>
			</p>
			*/?>

		</td>
	</tr>
	<!---->
	<tr>
		<td valign="top" align="center" style="border-collapse: collapse;border-spacing: 0;border-top: 1px solid #edeef0;padding: 33px 0 20px;">
			<table cellspacing="0" cellpadding="0" border="0" align="center" style="border-collapse: collapse;mso-table-lspace: 0pt;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
				<tr>
					<td style="border-collapse: collapse;border-spacing: 0;background-color: #44c8f2;padding: 0;">
						<a target="_blank" href="<?=$path?>" style="color: #ffffff;background-color: #44c8f2;border: 8px solid #44c8f2;border-radius: 2px;display: block;font-family: Helvetica, Arial, sans-serif;font-size: 12px;font-weight: bold;padding: 4px;text-transform: uppercase;text-decoration: none;"><?=Loc::getMessage('TASKS_GOTO_TASK')?></a>
					</td>
				</tr>
			</table>
		</td>
	</tr>