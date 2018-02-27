<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div class="video-conf">
<?
if ($arResult["NEED_AUTH"] == "Y")
{
	$APPLICATION->AuthForm(GetMessage("VIDEO_NEED_AUTH"));
}
else
{
	if(!empty($arResult["arAvConf"]))
	{
		?>
		<?=GetMessage("VIDEO_JOIN_CONF")?>
		<?
		foreach($arResult["arAvConf"] as $conf)
		{
			?>
			<p><b><a href="<?=$conf["Url"]?>" onclick="window.open('<?= CUtil::JSEscape($conf["Url"])?>', '', 'location=yes,status=no,scrollbars=yes,resizable=yes,width=1000,height=600,top='+Math.floor((screen.height - 600)/2-14)+',left='+Math.floor((screen.width - 1000)/2-5)); return false;"><?=$conf["NAME"]?></a></b>
			<?
			if($conf["lTimeH"] > 0)
				echo GetMessage("VIDEO_TIME_LEFT_H", Array("#HOURS#" => $conf["lTimeH"], "#MIN#" => $conf["lTimeM"]));
			else
				echo GetMessage("VIDEO_TIME_LEFT_M", Array("#MIN#" => $conf["lTimeM"]));
			?>
			<br />
			<?=GetMessage("VIDEO_DATE", Array("#FROM#" => $conf["ACTIVE_FROM"], "#TO#" => $conf["ACTIVE_TO"]))?>
			<br />
			<?=GetMessage("VIDEO_MEMBERS")?> 
			<?
			$i = 0;
			foreach($conf["MEMBERS"] as $val)
			{
				if($i!=0)
					echo ", ";
				$i++;
				echo $val["NAME"]." ".$val["LAST_NAME"];
				if($val["OWNER"] == "Y")
					echo " ".GetMessage("VIDEO_OWNER");
			}
			
			if(strlen($conf["UrlToConfEnd"]) > 0)
			{
				?><br /><a href="<?=$conf["UrlToConfEnd"]?>" title="<?=GetMessage("VIDEO_END_CONF")?>"><?=GetMessage("VIDEO_END_CONF")?></a><?
			}
			?>
			</p>
			<?
		}
	}
	else
		echo GetMessage("VIDEO_NO_CONF");
	if(strlen($arResult["NoteText"]) > 0)
		echo "<p>".$arResult["NoteText"]."</p>";
}
?>
</div>