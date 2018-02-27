<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

 global $DB;
/*$strSql = "
	SELECT
		`UF_ID`,
		`ID`,
		COUNT(*) as `c1`,
		GROUP_CONCAT(DISTINCT `ID` SEPARATOR ',') as `gr`
	FROM `requests`
	WHERE `UF_ID`>0
	GROUP BY `UF_ID`
	ORDER BY `c1` desc, `UF_UPDATE_DATE` desc
	LIMIT 10
	";
$results = $DB->Query($strSql);
while ($row = $results->Fetch())
{
	if($row["c1"]>1)
	{
		echo "<xmp>";
		print_r($row);
		echo "</xmp>";
		$arr = explode(",",$row["gr"]);
		unset($arr[0]);
		$str_del = implode(",",$arr);
		$strSql_d = "DELETE FROM `requests`
        WHERE `ID` IN ($str_del)
        LIMIT ".($row["c1"]-1);
		$results = $DB->Query($strSql_d);
		echo $strSql_d;
	}
	else  die("all ok");
}
*/
/*$strSql = "
	SELECT
		`UF_ID_DF`,
		`ID`,
		COUNT(*) as `c1`,
		GROUP_CONCAT(DISTINCT `ID` SEPARATOR ',') as `gr`
	FROM `domofey_requests`
	WHERE `UF_ID_DF`>0
	GROUP BY `UF_ID_DF`
	ORDER BY `c1` desc, `UF_UPDATE_DATE_DF` desc
	LIMIT 10
	";
$results = $DB->Query($strSql);
while ($row = $results->Fetch())
{
	if($row["c1"]>1)
	{
		echo "<xmp>";
		print_r($row);
		echo "</xmp>";
		$arr = explode(",",$row["gr"]);
		unset($arr[0]);
		$str_del = implode(",",$arr);
		$strSql_d = "DELETE FROM `requests`
        WHERE `ID` IN ($str_del)
        LIMIT ".($row["c1"]-1);
		$results = $DB->Query($strSql_d);
		echo $strSql_d;
	}
	else  die("all ok 1");
}*/
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>