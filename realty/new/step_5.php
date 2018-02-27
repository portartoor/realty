<?
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
	$Project = new Rights();
	$data_res = $Project->get_requests_file();
	$postfix  = $Project->get_postfix();
	$sayavka_id = isset($_GET["REQUEST_ID"])?$_GET["REQUEST_ID"]:"";
	$request = HlBlockElement::GetList($data_res["hblock"],array(),array("ID"=>$sayavka_id),array(),1);
	$time_s = date("d.m.Y H:i:s");
	if(!($request_data = $request->Fetch()))
	{?>
		<div class="request-step-5-error">Не выбрана заявка.</div>
	<?}
	if(isset($_FILES["Step5File"]) && isset($_POST["RequestId"]) && isset($_POST["Step"])){
		$str="";
		$file_arr = (array)$_FILES["Step5File"];
		$file_arr["MODULE_ID"]="main";
		$file_arr["name"]=preg_replace("/\?.*$/","",$file_arr["name"]);
		foreach ($file_arr as $k=>$v)
		{
			$str=$str." ".$k."=".$v." ";
		}
		$cat = "photos";
		preg_match('/^([^_]*)_/',$file_arr["name"],$matches);
		if($matches[1]!="")$cat =$matches[1];
		if($cat=="preview")
			$fild_name_clean="UF_PHOTO_PREVIEW";
		else if($cat=="plans")
			$fild_name_clean="UF_PLAN_PHOTOS";
		else if($cat=="docs")
			$fild_name_clean="UF_DOCS";
		else
			$fild_name_clean="UF_PHOTOS";
		$Result = array("Status" => false,"Mess" => $str,"Full"=> "", "Id"=>"" );
		$ext = explode(".",$file_arr["name"]);	
		if($ext[sizeof($ext)-1]!="")
		{
			$ext_f = $ext[sizeof($ext)-1];
			if($ext_f=="jpeg"||$ext_f=="peg")$ext_f="jpg";
			$length=5;
			$file_arr["name"]= $cat."_".substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length).".".$ext_f;
		}
		$FileId = CFile::SaveFile($file_arr,"realty_files"); 
		if($FileId > 0){
			$File = CFile::GetByID($FileId)->Fetch();
			if($File["WIDTH"] > $File["HEIGHT"]){
				$user_code = "";
				global $USER;
				$arr_q = HlBlockElement::GetList( $Project->get_agents_hb_id(),array(),array("UF_BITRIX_USER".$postfix=>$USER->GetID(),"!UF_AGENT_ID".$postfix=>"new_user_%"),array(),1);
				if($arr_s_client = $arr_q->Fetch()){
					$user_code = $arr_s_client["UF_AGENT_ID".$postfix];
				}
				//$fild_name_clean="UF_PHOTOS";
				$photo_template = Array(
					"CHANGED" => 1,
					"USER" => $user_code,
					"PHOTO" => ($fild_name_clean=="UF_PHOTOS")?1:0,
					"PLAN" => ($fild_name_clean=="UF_PLAN_PHOTOS")?1:0,
					"DOC" => ($fild_name_clean=="UF_DOCS")?1:0,
					"PREVIEW" =>($fild_name_clean=="UF_PHOTO_PREVIEW")?1:0,
					"SEND" => 0,
					"ORDER" => 30
				);
				CFile::UpdateDesc($FileId, json_encode($photo_template));
				$Result["Status"] = true;
				$img_small = CFile::ResizeImageGet($FileId, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_EXACT, true);   
				$Result["Mess"] = $img_small["src"]/*"/upload/".$File["SUBDIR"]."/".$File["FILE_NAME"]*/;
				$Result["Full"] = "/upload/".$File["SUBDIR"]."/".$File["FILE_NAME"];
				$Result["Id"] =$FileId;
				$fild_name = $fild_name_clean.$postfix;
				if($cat=="preview")
				{
					$arr_images = $FileId;/*echo json_encode($Result);die();*/
				}
				else
				{
					$arr_images = $request_data[$fild_name_clean.$postfix];
					$arr_images[]=$FileId;
				}
				$res = HlBlockElement::Update($data_res["hblock"],$sayavka_id,Array($fild_name=>$arr_images,"UF_UPDATE_DATE".$postfix=>$time_s));
				
			} else {
				CFile::Delete($FileId);	
				$Result["Mess"] = "Можно загружать только горизонтальные изображения";
			}
		}

		echo json_encode($Result);
		die();	
	}
	
	//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");

	$ObjectType = $request_data["UF_OBJ_TYPE".$postfix];
	CModule::IncludeModule("iblock");
	
	$UrlLoadFile = (CMain::IsHTTPS() ? "https" : "http")."://".$_SERVER["HTTP_HOST"].SITE_DIR;
	$UrlLoadFile .= "mobile/realty/new/step_5.php?REQUEST_ID=".$sayavka_id;
	$UrlLoadFile = CUtil::JSEscape($UrlLoadFile);
	//die($UrlLoadFile);
?>
<script type="text/javascript" src="/bitrix/templates/realty/js/photo_upload.js"></script>
<input type="hidden" name="sessid" id="sessid" value="<?=bitrix_sessid_get()?>">
<input type="hidden" name="step" value="5">
<input type="hidden" name="REQUEST_ID" value="<?=$sayavka_id?>">
<div class="request-step-5">
<?if(intval($request_data["UF_OBJ_TYPE".$postfix]) == 0):?>
	<div class="request-step-5-error">У заявки не заполнен тип объекта недвижимости.</div>
<?else:?>
	<?
	/*
	<div class="img_block">
		<input name="UF_PHOTOS" class="typefile" multiple size="20" type="file" <?if(is_old_android($APPLICATION->GetCurDir())) {?>onclick="getPhotoOldAndroid(event, {source: 2, destinationType: 0}, $(this));" <?}?><?if(!is_old_android($APPLICATION->GetCurDir())) {?>onchange="prepareUploadGo(event);"<?}?>>
		<ul class="sortable">
		<?*/
		if (sizeof($request_data["UF_PHOTOS".$postfix])>0):
			foreach ($request_data["UF_PHOTOS".$postfix] as $k => $v)
			{
				$descr = CFile::GetFileArray($v);
				$descr_arr = json_decode($descr["DESCRIPTION"], true);
				$checkbox="<br><input type=\"checkbox\" ".(($descr_arr["SEND"]=="1")?"checked":"")." /><label>выгружать</label>";
				$img_small = CFile::ResizeImageGet($v, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_EXACT, true);                
				$img = '<li><div class="img_item" data-id="'.$v.'" data-url="'.CFile::GetPath($v).'"><img src="'.$img_small['src'].'"/></div>'.$checkbox.'</li>';
				$cat = "all";
				preg_match('/^([^_]*)_/',$descr["FILE_NAME"],$matches);
				if($matches[1]!="")$cat =$matches[1];
				//echo $cat."!";
				$arr_imgs_cats[$cat][]= $img;
			}
		endif;
		/*?>
		</ul>
		<a href="#" class="logo_upload" onclick="$( this ).parent().find('input').first().click(); return false;"></a>
	</div>
	*/
	
	//$ObjectType = 1;
	$Query = CIBlockElement::GetList(
		array("PROPERTY_FIELD_2" => "ASC","SORT" => "ASC"),
		array("ACTIVE" => "Y","IBLOCK_ID" => 34,"PROPERTY_FIELD_1" => $ObjectType),
		false,
		false,
		array("ID","NAME","CODE","PROPERTY_FIELD_1","PROPERTY_FIELD_2","PREVIEW_TEXT","DETAIL_PICTURE")
	);?>
	<?while($Answer = $Query->Fetch()):?>
		<div class="request-step-5-title">
			<div><?=$Answer["NAME"]?></div>
			<a href="javascript:void(0)" onclick="BXMobileApp.PageManager.loadPageBlank({
			   url: '<?=SITE_DIR?>mobile/realty/new/instructions_foto_object.php?$REQUEST_ID=<?=$sayavka_id?>&Id=<?=$Answer["ID"]?>',
			   title: 'Инструкция'
		   });">
				<?=$Answer["PROPERTY_FIELD_2_VALUE"] == "Y" ? "Образец" : "Инструкция";
				if(!isset($arr_imgs_cats[$Answer["CODE"]])&&$Answer["PROPERTY_FIELD_2_VALUE"] == "Y")
				{
					$img_small = CFile::ResizeImageGet($Answer["DETAIL_PICTURE"], array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_EXACT, true); 
					?>
					<br>
					<img src="<?=$img_small['src']?>">
					<span class="plus">+</span>
					<?
				}
				?>
			</a>
			<?if($Answer["PREVIEW_TEXT"] != ""):?>
			<span>(<?=$Answer["PREVIEW_TEXT"]?>)</span>
			<?endif;?>
		</div>
		<?if($Answer["PROPERTY_FIELD_2_VALUE"] == "Y"):?>
		<div class="request-step-5-foto">
			<div 
				onclick="Step5.ShowTakePhoto(this,<?=$Answer["ID"]?>,'<?=$Answer["NAME"]?>','<?=$Answer["CODE"]?>')" 
				class="request-step-5-foto-item request-step-5-add">
				<img src="/bitrix/templates/realty/images/photo_upload.png"/>
			</div>
			<div class="img_block">
				<input name="UF_PHOTOS" class="typefile" multiple size="20" type="hidden" >
				<ul class="sortable">
				<?
				if(isset($arr_imgs_cats[$Answer["CODE"]])):
					foreach ($arr_imgs_cats[$Answer["CODE"]] as $k=>$v)
					{
						echo $v;
					}
					unset($arr_imgs_cats[$Answer["CODE"]]);
				endif;
				?>
				</ul>
			</div>
		</div>
		<?else:?>
			<div class="request-step-5-title">
				<div>Превью</div>
			</div>
			<div class="request-step-5-foto">
				<div 
					onclick="Step5.ShowTakePhoto(this,0,'','preview')" 
					class="request-step-5-foto-item request-step-5-add">
					<img src="/bitrix/templates/realty/images/photo_upload.png"/>
				</div>
				<div class="img_block">
					<input name="UF_PHOTO_PREVIEW" class="typefile" multiple size="20" type="hidden" >
					<ul class="sortable">
						<?
						if ($request_data["UF_PHOTO_PREVIEW".$postfix]>0)
						{
							$v = $request_data["UF_PHOTO_PREVIEW".$postfix];
							$descr = CFile::GetFileArray($v);
							$descr_arr = json_decode($descr["DESCRIPTION"], true);
							$checkbox="<br><input type=\"checkbox\" ".(($descr_arr["SEND"]=="1")?"checked":"")." /><label>выгружать</label>";
							$img_small = CFile::ResizeImageGet($v, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_EXACT, true);                
							$img = '<li><div class="img_item" data-id="'.$v.'" data-url="'.CFile::GetPath($v).'"><img src="'.$img_small['src'].'"/></div>'.$checkbox.'</li>';
							echo $img;
						}
						?>
					</ul>
				</div>
			</div>
		<?endif;?>
	<?endwhile;?>
<?endif;?>
	<?
		$first=false;
		foreach ($arr_imgs_cats as $k=>$v)
		{
			foreach ($v as $k1=>$v1)
			{
				if(!$first){
					?>
					<div class="request-step-5-title">
						<div>Другие фото</div>
					</div>
					<div class="request-step-5-foto">
						<div class="img_block">
							<input name="UF_PHOTOS" class="typefile" multiple size="20" type="hidden" >
							<ul class="sortable">
					<?
					$first=true;
				}
				echo $v1;
			}
			
		}
		if($first){
			?>
							</ul>
						</div>
					</div>	
			<?
		}
	?>
	<div class="request-step-5-title">
		<div>Планировки</div>
	</div>
	<div class="request-step-5-foto">
		<div 
			onclick="Step5.ShowTakePhoto(this,0,'','plans')" 
			class="request-step-5-foto-item request-step-5-add">
			<img src="/bitrix/templates/realty/images/photo_upload.png"/>
		</div>
		<div class="img_block">
			<input name="UF_PLAN_PHOTOS" class="typefile" multiple size="20" type="hidden" >
			<ul class="sortable">
				<?
				foreach ($request_data["UF_PLAN_PHOTOS".$postfix] as $k=>$v)
				{
					$descr = CFile::GetFileArray($v);
					$descr_arr = json_decode($descr["DESCRIPTION"], true);
					$checkbox="<br><input type=\"checkbox\" ".(($descr_arr["SEND"]=="1")?"checked":"")." /><label>выгружать</label>";
					$img_small = CFile::ResizeImageGet($v, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_EXACT, true);                
					$img = '<li><div class="img_item" data-id="'.$v.'" data-url="'.CFile::GetPath($v).'"><img src="'.$img_small['src'].'"/></div>'.$checkbox.'</li>';
					echo $img;
				}
				?>
			</ul>
		</div>
	</div>
	<div class="request-step-5-title">
		<div>Документы</div>
	</div>
	<div class="request-step-5-foto">
		<div 
			onclick="Step5.ShowTakePhoto(this,0,'','docs')" 
			class="request-step-5-foto-item request-step-5-add">
			<img src="/bitrix/templates/realty/images/photo_upload.png"/>
		</div>
		<div class="img_block">
			<input name="UF_DOCS" class="typefile" multiple size="20" type="hidden" >
			<ul class="sortable">
				<?
				foreach ($request_data["UF_DOCS".$postfix] as $k=>$v)
				{
					$descr = CFile::GetFileArray($v);
					$descr_arr = json_decode($descr["DESCRIPTION"], true);
					$checkbox="<br><input type=\"checkbox\" ".(($descr_arr["SEND"]=="1")?"checked":"")." /><label>выгружать</label>";
					$img_small = CFile::ResizeImageGet($v, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_EXACT, true);                
					$img = '<li><div class="img_item" data-id="'.$v.'" data-url="'.CFile::GetPath($v).'"><img src="'.$img_small['src'].'"/></div>'.$checkbox.'</li>';
					echo $img;
				}
				?>
			</ul>
		</div>
	</div>
</div>
<script type="text/javascript">
var Step5 = {
	CurObj: null,
	FotoDownloadType:null,
	Ft:null,
	Step:5,
	RequestId: "<?=$sayavka_id?>",
	FtOptions:null,
	Params:{},
	Id:"",
	Name:"",
	Code:"",
	JsonData:{},
	ShowTakePhoto:function(Obj,Id,Name,Code){
		this.Id = Id;
		this.Name = Name;
		this.CurObj = Obj;
		this.Code = Code;
		this.FotoDownloadType.show();
	},
	LoadPhoto:function(Url){
		this.Ft = new FileTransfer();
		this.FtOptions = new FileUploadOptions();
		this.FtOptions.fileKey = "Step5File";
		this.FtOptions.fileName = this.Code+"_"+Url.substr(Url.lastIndexOf('/')+1);
		this.FtOptions.mimeType = "image/jpeg";
		
		
		this.Params = {}
		this.Params.RequestId = this.RequestId;
		this.Params.Step = this.Step;
		this.Params.Id = this.Id;
		this.Params.Name = this.Code+"_"+this.Name;
		
		this.FtOptions.params = this.Params;
		
		this.Ft.upload(
			Url,
			'<?=$UrlLoadFile?>',
			function(Result){
				if(Result.responseCode == 200){
					Step5.LoadPhotoResult(Result.response);
				} else {
					alert("Произошла ошибка повторите попытку.");
				}
			}, 
			function(Error){
				alert("Произошла ошибка повторите попытку.");
			}, 
			this.FtOptions
		);
	},
	LoadPhotoResult:function(Data){
		Step5.JsonData = JSON.parse(Data); 
		if(Step5.JsonData.Status){
			if(this.Code=="preview")
				$(Step5.CurObj).parent().find('.sortable').html(Step5.GetStrHtml(Step5.JsonData));
			else
				$(Step5.CurObj).parent().find('.sortable').append(Step5.GetStrHtml(Step5.JsonData));
			add_spec_functionality_to_images();
		} else {
			alert(Step5.JsonData.Mess);
		}
	},
	GetStrHtml:function(Url){
		return "<li><div class=\"img_item\" data-id=\""+Url.Id+"\" data-url=\""+Url.Full+"\"><img src=\""+Url.Mess+"\" /></div><br><input type=\"checkbox\"><label>выгружать</label></li>";
	}
}
Step5.FotoDownloadType = new BXMobileApp.UI.ActionSheet({
	buttons: [
		{
			title: "Фото",
			callback: function(){
				app.takePhoto({
					source: 1,
					correctOrientation: true,
					targetWidth: 1000,
					targetHeight: 1000,
					callback: function(FileUri){
						Step5.LoadPhoto(FileUri);
					}
				});
			}
		},{
			title: "Галерея",
			callback: function(){
				app.takePhoto({
					source: 0,
					correctOrientation: true,
					targetWidth: 1000,
					targetHeight: 1000,
					callback: function(FileUri) {
						Step5.LoadPhoto(FileUri);
					}
				});
			}
		}
	]}, 
	"textPanelSheet"
);
</script>
<?//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>