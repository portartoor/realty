<?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
	$sayavka_id = isset($_GET["REQUEST_ID"])?$_GET["REQUEST_ID"]:"";
	$Project = new Rights();
	$data_res = $Project->get_requests_file();
	$postfix  = $Project->get_postfix();
       $status = 1;
       if($sayavka_id>0)
       {
		  $request = HlBlockElement::GetList($data_res["hblock"],array(),array("ID"=>$sayavka_id),array(),1);
		  $request_data = $request->Fetch();
		  if(!empty($request_data))
		  {
			  $status=($request_data["UF_INNER_STATUS".$postfix]==2||$request_data["UF_INNER_STATUS".$postfix]==3)?"0":"1";
		  }
        }
        die($status);
?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>