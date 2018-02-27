function prepareUploadGo(event)
{
	files = event.target.files;
	event.stopPropagation(); // Stop stuff happening
	event.preventDefault(); // Totally stop stuff happening

	// START A LOADING SPINNER HERE

	// Create a formdata object and add the files
	var data = new FormData();
	$.each(files, function(key, value)
	{
		data.append(key, value);
	});
	data.append("name", event.target.name);
	data.append("REQUEST_ID", $("input[name=REQUEST_ID]").val());
	$.ajax({
		url: '/realty/save_files.php',
		type: 'POST',
		data: data,
		cache: false,
		dataType: 'json',
		processData: false, // Don't process the files
		contentType: false, // Set content type to false as jQuery will tell the server its a query string request
		  beforeSend: function() {
			//console.log('$(this): '+$("input[name="+event.target.name+"]").parent().attr('class'));
			$("input[name="+event.target.name+"]").parent().children('.logo_upload').addClass('disable');
			var Imgs = $("input[name="+event.target.name+"]").parent().html();
			$("input[name="+event.target.name+"]").parent().html(Imgs+'<img class="loader_gif" src="/images/default.gif">');
		  },
		success: function(data, textStatus, jqXHR)
		{

			$("input[name="+event.target.name+"]").parent().children('.loader_gif').remove();
			$("input[name="+event.target.name+"]").parent().children('.logo_upload').removeClass('disable');
			//$("input[name="+event.target.name+"]").parent().append('<script>$(document).ready(function(){$( ".logo_upload" ).click(function() {$( this ).parent().children(".typefile").click();});});</script>');
			
			if(typeof data.error === 'undefined')
			{
				// Success so call function to process the form
				$.each(data.files, function(key, value)
				{
					showThumb(event.target.name, value);
				});	
				$(".img_block input[type=checkbox]").change(
					function (event){
						var photo_id = $(this).parents("li").first().find(".img_item").data("id");
						$.getJSON("/realty/save_files.php?send="+Number($(this).is(":checked"))+"&name="+$(this).parents(".img_block").find(":first-child").attr("name")+"&value="+photo_id+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val());
					}
				);				
			}
			else
			{
				// Handle errors here
				console.log('ERRORS: ' + data.error);
			}
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			// Handle errors here
			console.log('ERRORS: ' + textStatus);
			// STOP LOADING SPINNER
		}
	});
}

function showThumb (name, value)
{
	if(name=="UF_PHOTO_PREVIEW")
	{
		$("input[name="+name+"]").parent().find("ul").html("<li>"+value+"</li>");
	}
	else
	{
		$("input[name="+name+"]").parent().find("ul").first().append("<li>"+value+"</li>");
	}
	var divdbl_el = $("input[name="+name+"]").parent().find("div.img_item");
	divdbl_el.dblclick(function ()
	{
		window.open($(this).attr('data-url'));
	});
	divdbl_el.click(function ()
	{
		var el = this;
		var ast = setInterval(function() 
		{ 
			$(el).append("<a href=\"#\" class=\"del_img\">Удалить</a>");
			$(el).append("<a href=\"#\" class=\"arrow_r_img\">&rarr;</a>");
			$(el).append("<a href=\"#\" class=\"arrow_l_img\">&larr;</a>");
			$(el).find("a.del_img").click(function (event)
			{
				event.preventDefault();
				$.getJSON("/realty/save_files.php?del=1&name="+$(this).parents(".img_block").find(":first-child").attr("name")+"&value="+$(this).parent().attr("data-id")+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val());
				$(this).parents("li").first().remove();
			});
			$(el).find("a.arrow_r_img").click(
				function (event){
					event.preventDefault();	
					if($(this).parents("ul.sortable").length>0)
					{
						var parent_li = $(this).parent().parent();
						if($(parent_li).next().length>0)
						{
							$(parent_li).insertAfter($(parent_li).next());
						}
						var order="";
						var elem = $(this).parents("ul.sortable").first();
						$(elem).find("div.img_item").each(function( index ) {order = order +","+($(this).data("id"))})
						$.getJSON("/realty/save_files.php?move=1&name="+$(this).parents(".img_block").find(":first-child").attr("name")+"&value="+order+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val());
					}
				}
			);
			$(el).find("a.arrow_l_img").click(
				function (event){
					event.preventDefault();	
					if($(this).parents("ul.sortable").length>0)
					{
						var parent_li = $(this).parent().parent();
						if($(parent_li).prev().length>0)
						{
							$(parent_li).insertBefore($(parent_li).prev());
						}
						var order="";
						var elem = $(this).parents("ul.sortable").first();
						$(elem).find("div.img_item").each(function( index ) {order = order +","+($(this).data("id"))})
						$.getJSON("/realty/save_files.php?move=1&name="+$(this).parents(".img_block").find(":first-child").attr("name")+"&value="+order+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val());
					}
				}
			);
			$(el).find(".del_img").first().fadeIn(700).delay(3000).fadeOut(1000);
			$(el).find(".arrow_r_img").first().fadeIn(700).delay(3000).fadeOut(1000);
			$(el).find(".arrow_l_img").first().fadeIn(700).delay(3000).fadeOut(1000);
			var ast_1 = setInterval(function() 
			{ 
				$(el).find(".del_img").first().remove(); $(el).find(".arrow_l_img").first().remove();$(el).find(".arrow_r_img").first().remove();clearInterval(ast_1);
			}, 5000);
			clearInterval(ast);
		},100);
	});	
}

function dataURItoBlob(dataURI) 
{
    // convert base64 data component to raw binary data held in a string
    var byteString;
	byteString = atob(dataURI.URI);
    // separate out the mime component
	var mimeString = 'image/jpeg';
    // write the bytes of the string to a typed array
    var ia = new Uint8Array(byteString.length);
    for (var i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
    }
	
    return new Blob([ia], {type:mimeString});
}

function getPhotoOldAndroid(event, options, object) 
{
	event.stopPropagation();
	event.preventDefault();
	if (!options.callback)
		options.callback = function (imageURI) {
			var name = object.prop("name"),
			    data = new FormData(),
				blob = dataURItoBlob({URI:imageURI});
			blob.lastModifiedDate = new Date();
			blob.filename = "blob.jpg";
			data.append("image", blob, "blob.jpg");
			data.append("name", name);
			data.append("REQUEST_ID", $("input[name=REQUEST_ID]").val());			
			$.ajax({
				url: '/realty/save_files.php',
				type: 'POST',
				data: data,
				cache: false,
				dataType: 'json',
				processData: false,
				contentType: false,
				beforeSend: function() {
					$("input[name="+name+"]").parent().children('.logo_upload').addClass('disable');
					var Imgs = $("input[name="+name+"]").parent().html();
					$("input[name="+name+"]").parent().html(Imgs+'<img class="loader_gif" src="/images/default.gif">');
				},
				success: function(data, textStatus, jqXHR)
				{
					$("input[name="+name+"]").parent().children('.loader_gif').remove();
					$("input[name="+name+"]").parent().children('.logo_upload').removeClass('disable');
					
					if(typeof data.error === 'undefined')
					{
						// Success so call function to process the form
						$.each(data.files, function(key, value)
						{
							showThumb(name, value);							
						});	
						$(".img_block input[type=checkbox]").change(
							function (event){
								var photo_id = $(this).parents("li").first().find(".img_item").data("id");
								$.getJSON("/realty/save_files.php?send="+Number($(this).is(":checked"))+"&name="+$(this).parents(".img_block").find(":first-child").attr("name")+"&value="+photo_id+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val());
							}
						);
					}
					else
					{
						alert('ERRORS: ' + data.error);
					}
				},
				error: function(jqXHR, textStatus, errorThrown)
				{
					alert('ERRORS: ' + textStatus);
				}
			});			
		}
	if (!options.fail)
		options.fail = function (message)
		{
			console.log('Failed because: ' + message);
		};
	var params = {
		quality: ((typeof options.quality != "undefined") ? options.quality : 80),
		correctOrientation: (options.correctOrientation || false),
		targetWidth: (options.targetWidth || false),
		targetHeight: (options.targetHeight || false),
		sourceType: ((typeof options.source != "undefined") ? options.source : 0),
		mediaType: ((typeof options.mediaType != "undefined") ? options.mediaType : 0),
		allowEdit: ((typeof options.allowEdit != "undefined") ? options.allowEdit : false),
		saveToPhotoAlbum: ((typeof options.saveToPhotoAlbum != "undefined") ? options.saveToPhotoAlbum : false)
	};

	if (options.destinationType !== undefined)
		params.destinationType = options.destinationType;
	navigator.camera.getPicture(options.callback, options.fail, params);
}
function add_spec_functionality_to_images() 
{
	var divdbl = $('.img_block div.img_item').not(".ready");
	divdbl.addClass('ready');
	divdbl.dblclick(function (){
			window.open($(this).attr('data-url'));
		});
	$(".img_block input[type=checkbox]").change(
		function (event){
			var photo_id = $(this).parents("li").first().find(".img_item").data("id");
			$.getJSON("/realty/save_files.php?send="+Number($(this).is(":checked"))+"&name="+$(this).parents(".img_block").find(":first-child").attr("name")+"&value="+photo_id+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val());
		}
	);
	divdbl.click(function (){
		var el = this;
		var ast = setInterval(function() { 
			$(el).append("<a href=\"#\" class=\"del_img\">Удалить</a>");
			$(el).append("<a href=\"#\" class=\"arrow_r_img\">&rarr;</a>");
			$(el).append("<a href=\"#\" class=\"arrow_l_img\">&larr;</a>");
			$(el).find("a.del_img").click(
				function (event){
					event.preventDefault();								
					$.getJSON("/realty/save_files.php?del=1&name="+$(this).parents(".img_block").find(":first-child").attr("name")+"&value="+$(this).parent().attr("data-id")+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val());
					$(this).parents("li").first().remove();
				}
			);
			$(el).find("a.arrow_r_img").click(
				function (event){
					event.preventDefault();	
					if($(this).parents("ul.sortable").length>0)
					{
						var parent_li = $(this).parent().parent();
						if($(parent_li).next().length>0)
						{
							$(parent_li).insertAfter($(parent_li).next());
						}
						var order="";
						var elem = $(this).parents("ul.sortable").first();
						$(elem).find("div.img_item").each(function( index ) {order = order +","+($(this).data("id"))})
						$.getJSON("/realty/save_files.php?move=1&name="+$(this).parents(".img_block").find(":first-child").attr("name")+"&value="+order+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val());
					}
				}
			);
			$(el).find("a.arrow_l_img").click(
				function (event){
					event.preventDefault();	
					if($(this).parents("ul.sortable").length>0)
					{
						var parent_li = $(this).parent().parent();
						if($(parent_li).prev().length>0)
						{
							$(parent_li).insertBefore($(parent_li).prev());
						}
						var order="";
						var elem = $(this).parents("ul.sortable").first();
						$(elem).find("div.img_item").each(function( index ) {order = order +","+($(this).data("id"))})
						$.getJSON("/realty/save_files.php?move=1&name="+$(this).parents(".img_block").find(":first-child").attr("name")+"&value="+order+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val());
					}
				}
			);
			$(el).find(".del_img").first().fadeIn(700).delay(3000).fadeOut(1000);
			$(el).find(".arrow_r_img").first().fadeIn(700).delay(3000).fadeOut(1000);
			$(el).find(".arrow_l_img").first().fadeIn(700).delay(3000).fadeOut(1000);
			var ast_1 = setInterval(function() { $(el).find(".del_img").first().remove(); $(el).find(".arrow_r_img").first().remove(); $(el).find(".arrow_l_img").first().remove();clearInterval(ast_1); }, 5000); 
			clearInterval(ast);
			},100);
	});
}
$(document).ready(function(){
	//$('input[type=file]').on('change', prepareUploadGo);
	add_spec_functionality_to_images();
});