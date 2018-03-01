function add_metki_g(position){
			$.ajax({
			  url: "/realty/ajax/get_near_places_info.php?location="+position[0]+","+position[1],
			  dataType: 'json'
			})
			.done(function( data ) {
				var obj = data; 
				for(i in obj.results){
					ps = [obj.results[i].geometry.location.lat,obj.results[i].geometry.location.lng];

					var plk =  new ymaps.Placemark(ps, {
							hintContent: obj.results[i].name,
							balloonContentHeader: obj.results[i].name,
							balloonContentBody: '<strong>Адрес: </strong>'+obj.results[i].vicinity,
							name: obj.results[i].name,
						}, {
							iconLayout: 'default#image',
							iconImageHref: obj.results[i].icon,
							iconImageSize: [25, 25],
							iconImageOffset: [-3, -25]
						});
					plk.description = obj.results[i].name;
					myMap.geoObjects.add(plk);
				}
			});
		}
function change_geo_code(){
	var str="Калининградская область "+$("input[name=district_label]").val()+" "+$("input[name=district]").val()+" "+$("input[name=location_label]").val()+" "+$("input[name=location]").val()+" "+$("input[name=street_label]").val()+" "+$("input[name=street]").val()+" "+$("input[name=building_label]").val()+" "+$("input[name=building]").val();
	var myGeocoder = ymaps.geocode(str);
	myGeocoder.then(
		function (res) {
			var position = 	res.geoObjects.get(0).geometry.getCoordinates();
			if(jQuery.isEmptyObject(placemark))
			{
				placemark =  new ymaps.Placemark(position);
				myMap.geoObjects.add(placemark);
			}
			else
			{
				placemark.geometry.setCoordinates(position);
			}
			myMap.panTo(position);
			add_metki_g(position);
			$("input[name=UF_LATITUDE]").val(position[0]);
			$("input[name=UF_LONGITUDE]").val(position[1]);
			save_geo_position();
		},
		function (err) {
			console.log("error with geocode by yandex");
		}
	);
}
function save_geo_position(){
	$.getJSON("/realty/save_input.php?name=UF_LATITUDE&value="+$("input[name=UF_LATITUDE]").val()+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val())
			.done(function( data ) {
				if($("input[name=REQUEST_ID]").val()=="")$("input[name=REQUEST_ID]").val(data.REQUEST_ID);
				$.getJSON("/realty/save_input.php?name=UF_LONGITUDE&value="+$("input[name=UF_LONGITUDE]").val()+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val());
			});
}
function inputChangeinBd(el,ar) {
	var url_sv = "";
	var dop_str= "";
	val = "";
	if(($(el).attr('name'))!=undefined && $(el).attr('name').indexOf('PRICE') >= 0)
	{
		var clientPrice = 0;
		var priceSell = 0;
		var priceCust = 0;
		if($("input[name=UF_CLIENT_PRICE]").length)
			clientPrice = parseInt($("input[name=UF_CLIENT_PRICE]").val().replace(/\s/g, ''));
		if($("input[name=UF_PRICE_SELL]").length)
			priceSell   = parseInt($("input[name=UF_PRICE_SELL]").val().replace(/\s/g, ''));
		if($("input[name=UF_PRICE_CUST]").length)
			priceCust   = parseInt($("input[name=UF_PRICE_CUST]").val().replace(/\s/g, ''));
		if (isNaN(clientPrice)) clientPrice = 0;
		if (isNaN(priceSell)) priceSell = 0;
		if (isNaN(priceCust)) priceCust = 0;		
		val = clientPrice + priceSell + priceCust;
		if($("input[name=UF_PRICE]").length)
		{	
			if ($("input[name=UF_CLIENT_PRICE]").val()=='') {
				$("input[name=UF_PRICE]").val('');
			}
			else {
				$("input[name=UF_PRICE]").val(abc2(val.toString()));
			}
			if($(el).attr('name')!="UF_PRICE")inputChangeinBd("input[name=UF_PRICE]");
		}
	}
	if($(el).attr("name")=="district")
	{
		if((typeof(ar) != "undefined")&&ar[0]=="district")val = ar[1]+" "+$("input[name=district_label_min]").val();
		if((typeof(ar) != "undefined")&&ar[0]=="district"&&ar[1]=="")val = "";
		url_sv = "UF_REGION_ID";
		$("#kladr-address input[name=location]").val("");
		$("#kladr-address input[name=street]").val("");
		$("#kladr-address input[name=building]").val("");
		dop_str = "&UF_ADDR_HOUSE=&UF_CITY_ID=&UF_ADDR_STREET=&UF_ADDR_INDEX="+$("#kladr-address input[name=zip_code]").val();
		if(typeof(ar) == "undefined")
			val = $(el).val();
	}
	else if($(el).attr("name")=="location")
	{
		if((typeof(ar) != "undefined")&&ar[0]=="location")val = ar[1]+" "+$("input[name=location_label_min]").val();
		if((typeof(ar) != "undefined")&&ar[0]=="location"&&ar[1]=="")val = "";
		url_sv = "UF_CITY_ID";
		$("#kladr-address input[name=street]").val("");
		$("#kladr-address input[name=building]").val("");
		dop_str = "&UF_ADDR_HOUSE=&UF_ADDR_STREET=&UF_ADDR_INDEX="+$("#kladr-address input[name=zip_code]").val();
		if(typeof(ar) == "undefined")
			val = $(el).val();
	}
	else if($(el).attr("name")=="street")
	{
		if((typeof(ar) != "undefined")&&ar[0]=="street")val = ar[1]+" "+$("input[name=street_label_min]").val();
		if((typeof(ar) != "undefined")&&ar[0]=="street"&&ar[1]=="")val = "";
		url_sv = "UF_ADDR_STREET";
		$("#kladr-address input[name=building]").val("");
		dop_str = "&UF_ADDR_HOUSE=&UF_ADDR_INDEX="+$("#kladr-address input[name=zip_code]").val();
		if(typeof(ar) == "undefined")
			val = $(el).val();
	}
	else if($(el).attr("name")=="building")
	{
		if((typeof(ar) != "undefined")&&ar[0]=="building")val = ar[1];
		if((typeof(ar) != "undefined")&&ar[0]=="building"&&ar[1]=="")val = "";
		url_sv = "UF_ADDR_HOUSE";
		if(typeof(ar) == "undefined")
			val = $(el).val();
	}
	else
	{
		url_sv = $(el).attr("name");
		val = $(el).val();
	}
	var container = $('form[name=new_object]');
	if (container.has(el).length !== 0){
		$.getJSON("/realty/save_input.php?name="+url_sv+"&value="+val+dop_str+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val()+"&UF_CONTRAGENT="+$("input[name=UF_CONTRAGENT]").val())
		.done(function( data ) {
			if($(el).attr("name")=="building"||$(el).attr("name")=="street"||$(el).attr("name")=="location"||$(el).attr("name")=="district")
				change_geo_code();
			if($("input[name=REQUEST_ID]").val()=="")$("input[name=REQUEST_ID]").val(data.REQUEST_ID);
			if($(el).val()!="")$(el).removeClass("red");
			if($(el).attr("name")=="location")
			   {
				   if($(el).val().indexOf('Калининград') >= 0)
				   {
					   $("select[name=UF_CITY_REGION]").removeClass("hide");
					   $("select[name=UF_CITY_REGION]").prev().removeClass("hide");
				   }
				   else
				   {
					 $("select[name=UF_CITY_REGION]").addClass("hide");
					 $("select[name=UF_CITY_REGION]").prev().addClass("hide");  
				   }
					
			   }
			if(typeof(data.UF_CONTRAGENT)!='undefined')
			{
				$("input[name=UF_CONTRAGENT]").val(data.UF_CONTRAGENT);
			}
			if(typeof(data.client)!='undefined')
			{
				$("#clients_block").empty();
				$("#clients_block").append("<div class=\"header_desc\">Выберите зарегистрированного клиента:</div>");
				$(data.client).each(function(i) {
					$("#clients_block").append("<div class=\"client_info_block\"><div class=\"c_fio\">"+$( this )[i].UF_FIO+"</div><div class=\"c_phone\">"+$( this )[i].UF_PHONE+"</div><div class=\"c_phone_1\">"+$( this )[i].UF_PHONE_1+"</div><div class=\"c_mail\">"+$( this )[i].UF_MAIL+"</div><div class=\"c_id_1c\">"+$( this )[i].UF_ID_1C+"</div><a href=\"#\" id=\""+$( this )[i].ID+"\" class=\"take_client\">Выбрать</a>"+$( this )[i].text+"</div>");
				  });
				 $("#clients_block").addClass("red");
				  $('html, body').animate({ scrollTop: $("#clients_block").offset().top }, "slow");
				  $( "a.take_client" ).click(function() {
					$("input[name=UF_CONTRAGENT]").val($(this).parent().children( ".c_id_1c" ).html());
					$("input[name=UF_AGENTS_FIO]").val($(this).parent().children( ".c_fio" ).html());				console.log($(this).parent().children( ".c_fio" ).html());
					$("input[name=UF_AGENTS_FIO]").attr('readonly', true);
					$("input[name=UF_AGENTS_PHONE]").val($(this).parent().children( ".c_phone" ).html());
					$("input[name=UF_AGENTS_PHONE_1]").val($(this).parent().children( ".c_phone_1" ).html());
					if($(this).parent().children( ".c_phone_1" ).html()!=="")
					{
						$("input[name=UF_AGENTS_PHONE_1]").attr('readonly', true);
					}
					$("input[name=UF_AGENTS_PHONE]").attr('readonly', true);
					$("input[name=UF_AGENTS_MAIL]").val($(this).parent().children( ".c_mail" ).html());
					if($(this).parent().children( ".c_mail" ).html()!=="")
					{
						$("input[name=UF_AGENTS_MAIL]").attr('readonly', true);
					}
					$('html, body').animate({ scrollTop: $("#content").offset().top }, "slow");
					
					$.getJSON( "/realty/save_input.php?name=UF_CONTRAGENT&value="+$(this).attr('id')+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val())
					.done(function( data ) {
						$("#clients_block").removeClass("red");
						$("#clients_block").empty();
						$("#clients_block").html("<a href=\"#\" class=\"remove_client\">Новый клиент</a>");
						$( "a.remove_client" ).click(function() {
							$("input[name=UF_CONTRAGENT]").val("");
							$("input[name=UF_AGENTS_FIO]").val("");	
							$("input[name=UF_AGENTS_PHONE]").val("");
							$("input[name=UF_AGENTS_PHONE_1]").val(""); 
							$("input[name=UF_AGENTS_MAIL]").val(""); 
							$("input[name=UF_AGENTS_FIO]").attr('readonly', false);
							$("input[name=UF_AGENTS_PHONE]").attr('readonly', false);
							$("input[name=UF_AGENTS_PHONE_1]").attr('readonly', false);
							$("input[name=UF_AGENTS_MAIL]").attr('readonly', false);
							
							$.getJSON( "/realty/save_input.php?name=UF_CONTRAGENT&value=0&REQUEST_ID="+$("input[name=REQUEST_ID]").val())
							.done(function( data ) {
							});
							$("#clients_block").empty();
							return false;
						 });
					});
					return false;
				 });
			}
			if(typeof(data.client_addr)!='undefined')
			{
				$("#clients_block_addr").empty();
				$("#clients_block_addr").append("<div class=\"header_desc\">Похожие заявки:</div>");
				$("#clients_block_addr").append(data.client_addr);
				$("#clients_block_addr").addClass("red");
				$('html, body').animate({ scrollTop: $("#clients_block_addr").offset().top }, "slow");
			}
			if(typeof(data.UF_CITY_REGION)!='undefined')
			{
				$("select[name=UF_CITY_REGION]").val(data.UF_CITY_REGION);
			}
			
		});
	}
 }
function abc2(n) {
		n = n.replace(/ /g,"");
		return (n + "").split("").reverse().join("").replace(/(\d{3})/g, "$1 ").split("").reverse().join("").replace(/^ /, "");
}
var inProgress = false; 
$(document).ready(function(){
	$("input[type=tel]").bind('keyup',function(e) { 
			if($(this).attr('name').indexOf('PRICE') >= 0)
			{
				$(this).val(abc2($(this).val()));
			}
	   });
	  $("input[type=tel]").bind('blur',function(e) {
		if($(this).attr('name').indexOf('PRICE') >= 0)
			$(this).change();
	  });
	/*
	  $("input[name=UF_PRICE]").bind('keyup',function(e) { 
			$(this).val(abc2($(this).val()));
	   });
	  $("input[name=UF_PRICE]").bind('blur',function(e) {
		$(this).change();
	  });
	  $("input[name=UF_CLIENT_PRICE]").bind('keyup',function(e) {
			$(this).val(abc2($(this).val()));
	  });
	  $("input[name=UF_CLIENT_PRICE]").bind('blur',function(e) {
			$(this).change();
	  });
	  $("input[name=UF_PRICE_SELL]").bind('keyup',function(e) {
			$(this).val(abc2($(this).val()));
	  });
	  $("input[name=UF_PRICE_SELL]").bind('blur',function(e) {
			$(this).change();
	  });
	  $("input[name=UF_PRICE_CUST]").bind('keyup',function(e) {
			$(this).val(abc2($(this).val()));
	  });
	  $("input[name=UF_PRICE_CUST]").bind('blur',function(e) {
			$(this).change();
	  });
	  $("input[name=UF_AGENTS_PHONE_1]").bind('keyup',function(e) {
		if($(this).val()=="")$(this).val("8 (4012) ");
	  });
	  $("input[name=UF_AGENTS_PHONE_1]").bind('blur',function(e) {
			if($(this).val()=="+7 (4012) ")$(this).val("");
			$(this).change();
	  });
	  $("input[name=UF_AGENTS_PHONE_1]").bind('click',function(e) {
		if($(this).val()=="")$(this).val("8 (4012) ");
		$(this).change();
	  });*/
  /*$( ".logo_upload" ).click(function() {
	  $( this ).parent().find("input").first().click();
	});*/
  $( ".field_to_fill input[type='text']" ).bind("change",function() {/*if(!$(this).hasClass("c_kladr"))*/inputChangeinBd(this);});
  $( ".field_to_fill input[type='tel']" ).bind("change",function() {if(!$(this).hasClass("c_kladr"))inputChangeinBd(this);});
  $( ".field_to_fill textarea" ).change(function() {
		var el = this;
		var container = $('form[name=new_object]');
		if (container.has(el).length !== 0){
			url_sv = $(el).attr("name");
			val = $(el).val();
			$.getJSON("/realty/save_input.php?name="+url_sv+"&value="+val+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val())
				.done(function( data ) {
					if($("input[name=REQUEST_ID]").val()=="")$("input[name=REQUEST_ID]").val(data.REQUEST_ID);
					if($(el).val()!="")$(el).removeClass("red");
				});
		}
  });
  $('input.chbx_form').click (function(){
	  var thischeck = $(this);
	  var val=0;
	  if ( thischeck.is(':checked') ) {val=1;}
	  $.getJSON("/realty/save_input.php?name="+$(this).attr("name")+"&value="+val+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val())
				.done(function( data ) {
					if($("input[name=REQUEST_ID]").val()=="")$("input[name=REQUEST_ID]").val(data.REQUEST_ID);
				});
	});
  $( "a.remove_client" ).click(function() {
	$("input[name=UF_CONTRAGENT]").val("");
	$("input[name=UF_AGENTS_FIO]").val("");	
	$("input[name=UF_AGENTS_PHONE]").val("");
	$("input[name=UF_AGENTS_PHONE_1]").val(""); 
	$("input[name=UF_AGENTS_MAIL]").val(""); 
	$("input[name=UF_AGENTS_FIO]").attr('readonly', false);
	$("input[name=UF_AGENTS_PHONE]").attr('readonly', false);
	$("input[name=UF_AGENTS_PHONE_1]").attr('readonly', false);
	$("input[name=UF_AGENTS_MAIL]").attr('readonly', false);
	
	$.getJSON( "/realty/save_input.php?name=UF_CONTRAGENT&value=&REQUEST_ID="+$("input[name=REQUEST_ID]").val())
	.done(function( data ) {
	});
	$("#clients_block").empty();
	return false;
 });
 $( "select" ).change(function() {
	var select_str = "";
	var el = $(this);
	if(!$(this).hasClass("no_select"))select_str="&select=1";
	var postfix = "";
	if($(el).attr("name").indexOf("_DF")!=-1)
		postfix = "_DF";
	if($(el).attr("name").indexOf("UF_REALTY_TYPE")!=-1)
	{
		if($(this).val()=="6")
		{
			$("select[name=UF_OBJ_TYPE"+postfix+"]").html($("#OBJ_TYPE_6").html());
		}
		else
		{
			$("select[name=UF_OBJ_TYPE"+postfix+"]").html($("#OBJ_TYPE_0").html());
		}
	}
	if($(el).attr("name")=="UF_OPERATION_TYPE"+postfix)
	{
		if($(el).val()=="OPERATION_TYPE_002"||$(el).val()=="OPERATION_TYPE_003")
		{
			$(".block_to_hide_ul").addClass("hide");
		}
		else $(".block_to_hide_ul").removeClass("hide");
	}
	var container = $('form[name=new_object]');
	if (container.has(el).length !== 0){
		$.getJSON( "/realty/save_input.php?name="+$(this).attr("name")+select_str+"&value="+$(this).val()+"&REQUEST_ID="+$("input[name=REQUEST_ID]").val())
		.done(function( data ) {
			if($(el).val()!="")$(el).removeClass("red");
			if($("input[name=REQUEST_ID]").val()=="")$("input[name=REQUEST_ID]").val(data.REQUEST_ID);
	   });
	   if($(this).attr("name")=="UF_OPERATION_TYPE"+postfix)
	   {
			var parent = $("input[name=building]").parent();
			if($(this).val()=="OPERATION_TYPE_002"||$(this).val()=="OPERATION_TYPE_003")
			{
				$(parent).addClass("hide");
				$(parent).prev().addClass("hide");
			}
			else
			{
				$(parent).removeClass("hide");
				$(parent).prev().removeClass("hide");
			}
	   }
	 }
	 if($( "form[name=my_requests]" ).length||$( "form[name=search_object]" ).length)null_page("n");
 });
 sort_filter_search();
 $('button.small_button').click(function(){
		if (window.location.href.indexOf("mobile") >= 0 )
			window.location.href = "/mobile/realty/new/?step="+($( "input[name='step']" ).val()-1)+"&REQUEST_ID="+$( "input[name='REQUEST_ID']" ).val();
		else
			window.location.href = "/realty/new/?step="+($( "input[name='step']" ).val()-1)+"&REQUEST_ID="+$( "input[name='REQUEST_ID']" ).val();
		return false;
	});
 $(".open_dop_parameters").click(function(){
		$(".webform_realty").toggle();
		return false;
	});
  $(".open_dop_parameters_dog").click(function(){
		$(".open_dop_parameters_dog_det").toggle();
		$('html, body').animate({ scrollTop: $(".open_dop_parameters_dog_det").offset().top }, "slow");
		return false;
	});
 $(".menu_main").click(function(){
		$("#menu_main_contant").toggleClass("active");
		$(".menu_main_contant_bg").show();
		return false;
	});
	$("html").click(function(e) {
		if($(e.target).closest("#menu_main_contant").length==0) {$("#menu_main_contant").removeClass("active");$(".menu_main_contant_bg").hide();}
		if($(e.target).closest(".price_ec").length==0&&$(e.target).closest("input[name=UF_CLIENT_PRICE]").length==0) {
			$(".price_ec").hide();
		}
	});
	$('form[name=my_requests] input[type=submit]').click(function(){
		event.preventDefault();
		$('html, body').animate({
			scrollTop: $("#result_arr").offset().top
		}, 2000);
		null_page(0);
		send_search_filter($("form[name=my_requests]").serialize());
		return false;
	});
	$("form[name=search_object]").submit(function(event) {
		event.preventDefault();
		$('html, body').animate({
			scrollTop: $("#result_arr").offset().top
		}, 2000);
		/*$("#result_arr").empty();*/
		null_page(0);
		var data = $(this).serialize();
		if (Map.points !== "undefined")
		{
			$.each(Map.points, function(i, item) {
				data += "&ID[]=" + item;
			});
		}
		send_search_filter(data);
	});
	window.onscroll = function() {
		//if((!$( "form[name=my_requests]" ).length)&&(!$( "form[name=search_object]" ).length))return;
		if(!$("#result_arr").length)return;
		if(inProgress||$(document).height()-$(document).scrollTop()>1000||$("#result_arr").data( "page" )=="n")return;
		inProgress=true;
		if($( "form[name=my_requests]" ).length)
			send_search_filter($("form[name=my_requests]").serialize());
		else if($( "form[name=search_object]" ).length)
		{
			var data = $("form[name=search_object]").serialize();
			if (Map.points !== "undefined")
			{
				$.each(Map.points, function(i, item) {
					data += "&ID[]=" + item;
				});
			}
			send_search_filter(data);
		}
		else if($( "form[name=new_object]" ).length)
		{
			send_search_same();
		}
	};
	$("a.img_item_b").fancybox({
        'transitionIn'  :   'elastic',
        'transitionOut' :   'elastic',
        'speedIn'       :   600, 
        'speedOut'      :   200, 
        'overlayShow'   :   false
    });
	$("a.fancybox").fancybox({
        'transitionIn'  :   'elastic',
        'transitionOut' :   'elastic',
        'speedIn'       :   600, 
        'speedOut'      :   200, 
        'overlayShow'   :   false
    });
	view_page_scripts();
	$("#my_request_show").click();
	$('.menu_action_block a.edit').click (function(){
	  var el = this;
	  event.preventDefault();
	  $.getJSON("/realty/ajax/check_status.php?REQUEST_ID="+$("input[name=REQUEST_ID]").val())
				.done(function( data ) {
					if(data=="0")
					{
						alert("Заявка находится в процессе синхронизации с 1С, временно не доступна для редактирования!");
					}
					else
					{	
						console.log($(el).attr("href")+"!!!");
						window.location.replace($(el).attr("href"));
					}
				});
	});
	$(".call_client_request").click(function(){
		if($(this).hasClass("no_active"))
		{
			 event.preventDefault();
			 alert("Слишком поздно. Заявка передана другому агенту.");
		}
		else {
			$.getJSON("/realty/ajax/change_call_status.php?REQUEST_ID="+$("input[name=REQUEST_ID]").val());
		}
	});
});
function null_page(s)
{
	$("#result_arr").data( "page",s);
}
function readonly(){
	$('input').attr('readonly', 'readonly');
	$('textarea').attr('readonly', 'readonly');
	$('select').attr('disabled', true);
	$(".open_dop_parameters_view").click(function(){
		$(".hide_act").toggle();
		if ($(".hide_act").length > 0) { 
			$('html, body').animate({ scrollTop: $(".hide_act").offset().top }, "slow");
		}
		return false;
	});
}
function like_obj(e){
	$(e).toggleClass("active");
	var val = 0;
	if($(e).hasClass("active"))
		val = 1;
	var r_f_c="";
	if($("input[name=REQUEST_CODE]").val()=="")
		r_f_c="*"+$(e).data("f");
	else 
		r_f_c=$("input[name=REQUEST_CODE]").val();
	$.get('/realty/ajax/interes_b.php?REQUEST_ID='+$(e).data("f")+"&obj="+$(e).data("t")+"&interes="+val+"&a="+$(e).data("ava")+"&r_t_c="+$(e).data("code")+"&r_f_c="+r_f_c+"&agent_code="+$(e).data("agent"), 
		function(data) {
			$(e).parent().children("a").removeClass("hide");
			$("a.like_1").not(e).toggleClass("active");
			$(".del_s a").not(e).toggleClass("active");
			if(val==1)$(".menu_action_block .mail").addClass("active");
			else $(".menu_action_block .mail").removeClass("active");
		});
	return false;
}
function call_agent(agent_code){
	$.get('/realty/ajax/agent_phone.php?agent_code='+agent_code, 
		function(PhoneNumber) {
			if(PhoneNumber!="")
			{
				window.location.href="tel://"+PhoneNumber;
			}
		});
	return false;
}
function open_chat_realty(agent_code,request_from,request_to,request_to_code,avatar) {
		$.get('/realty/ajax/chat_id_by_requests.php?agent_code='+agent_code+'&r_f='+request_from+'&r_t='+request_to+'&r_t_c='+request_to_code+'&a='+avatar, 
			function(id) {
				if (BX.IM) { BXIM.openMessenger(id); scroll_to_0(461);
				}
			});
	return false;
}
function scroll_to_0(top_v){
	top_v = top_v || 0;
	$('html, body').animate({ scrollTop: top_v }, "slow");
	void(0);
}
function view_page_scripts(){
	$(".menu_action_block .star").click(function(){
		$(".menu_action_block a").removeClass("active");
		$(".menu_action_block a.star").addClass("active");
		$.get('/realty/search/index.php?ajax&interes&REQUEST_ID='+$("input[name=REQUEST_ID]").val(), 
			function(data) {
				$('#request_view').html(data);
				/*$('#content .container-norm').removeClass("first_w");*/
				/*$('html, body').animate({ scrollTop: $(".container-norm").offset().top }, "slow");*/
				sort_filter_search();
				$("form[name=search_object]").submit(function(event) {
					$("#result_arr").empty();
					null_page(0);
					var data = $(this).serialize();
					send_search_filter(data);
					event.preventDefault();
				});
				$("form[name=search_object]").submit();
				$('html, body').animate({ scrollTop: $("#result_arr").offset().top }, "slow");
			});
		return false;	
	});
	$(".menu_action_block .first").click(function(){
		$(".menu_action_block a").removeClass("active");
		$(".menu_action_block a.first").addClass("active");
		$.get(window.location.pathname+window.location.search+'&ajax', 
			function(data) {
				$('#content').html(data);
				view_page_scripts();
			});
		return false;	
	});
	$(".menu_action_block .close_request").click(function(){
		$(".menu_action_block a").removeClass("active");
		$(".menu_action_block a.close_request").addClass("active");
		$.get("/realty/ajax/close.php"+window.location.search+'&ajax', 
			function(data) {
				$("#request_view").html(data);
			});
		return false;	
	});
	$(".menu_action_block .change_status").click(function(){
		$(".menu_action_block a").removeClass("active");
		$(".menu_action_block a.change_status").addClass("active");
		$.get("/realty/ajax/status.php"+window.location.search+'&ajax', 
			function(data) {
				$("#request_view").html(data);
			});
		return false;	
	});	
	$(".menu_action_block .like").click(function(){
		$(".menu_action_block a").removeClass("active");
		$(".menu_action_block a.like").addClass("active");
		$("#request_view").html("<span class=\"header_class\">Мои интересы</span><div id=\"result_arr\" data-page=\"0\"></div>");
		var data = {"interested":1};
		send_search_filter(data,1);
		return false;	
	});
}
function send_search_filter(data,intrs){
	intrs = intrs || 0;
	$("#result_arr").data( "page",parseInt($("#result_arr").data( "page" ))+1);
	var s="";
	if($("form[name=search_object]").data("interes")==1||intrs==1)
		s="&interes="+$("input[name=REQUEST_ID]").val();
	var app = "";
	if (window.location.href.indexOf("mobile") >= 0 )
		app = "app=true&";
	$.post('/realty/ajax/results.php?' + app + 'PAGEN_2='+$("#result_arr").data( "page" )+s,data, 
		function(data) {
			if(parseInt($("#result_arr").data( "page" ))==1)
				$('#result_arr').html(data);
			else
				$('#result_arr').append(data);
			if(data==""||$("#result_arr .not_found").length)
				$("#result_arr").data( "page" ,"n")
			inProgress=false;
			$(".load_img").each(function() { 
			  var el = this;
			  $( this ).removeClass("load_img");
			  $.get( "/realty/ajax/get_preview_img_object.php?Id="+$(el).parent().data("id"), function( data ) {
				    if(data!="")
				    {
						$(el).attr( "src", data );
				    }
					else
					{
						$.get( "/realty/ajax/get_preview_img_object.php?site_nw=1&Id="+$(el).parent().data("id"), function( data ) {
							if(data!="")
								$(el).attr( "src", data );
						});
					}
				});
			});
		});
}
function send_search_same(){
	$("#result_arr").data( "page",parseInt($("#result_arr").data( "page" ))+1);
	s="&REQUEST_ID="+$("input[name=REQUEST_ID]").val();
	var app = "";
	if (window.location.href.indexOf("mobile") >= 0 )
		app = "app=true&";
	$.get('/realty/new/same_obj_list.php?ajax=1&' + app + 'PAGEN_3='+$("#result_arr").data( "page" )+s, 
		function(data) {
			if(parseInt($("#result_arr").data( "page" ))==1)
				$('#result_arr').html(data);
			else
				$('#result_arr').append(data);
			if(data==""||$("#result_arr .not_found").length)
				$("#result_arr").data( "page" ,"n")
			inProgress=false;
			$(".load_img").each(function() { 
			  var el = this;
			  $( this ).removeClass("load_img");
			  $.get( "/realty/ajax/get_preview_img_object.php?Id="+$(el).parent().data("id"), function( data ) {
				    if(data!="")
				    {
						$(el).attr( "src", data );
				    }
					else
					{
						$.get( "/realty/ajax/get_preview_img_object.php?site_nw=1&Id="+$(el).parent().data("id"), function( data ) {
							if(data!="")
								$(el).attr( "src", data );
						});
					}
				});
			});
		});
}
function send_request_close_form (form) {
	var inputs   = "form[name=" + form[0].name + "] :input",
		notEmpty = true;
	$(inputs).each(function() {
		if($.trim($(this).val()).length == 0)
			notEmpty = false;
	});
	if (notEmpty)
	{
		var data = $(form).serialize();
		var url = "/realty/ajax/close_handler.php";
		$.post(url, data)
			.done(function() {
				$(".webform_realty").html("<span class=\"header_class\">Запрос на закрытие принят.</span>");
			})
			.fail(function() {
				$(".webform_realty").html("<span class=\"header_class\">Произошла ошибка. Повторите запрос позже.</span>");
			});			
	}
	else
	{
		$(inputs).each(function() {
			if($.trim($(this).val()).length == 0)
				$(this).css("border", "1px solid red");
		});
	}
}
function send_status_change_form (form) {
	var inputs   = "form[name=" + form[0].name + "] :input",
		notEmpty = true;
	$(inputs).each(function() {
		if($.trim($(this).val()).length == 0)
			notEmpty = false;
	});
	if (notEmpty)
	{	
		var data = $(form).serialize();
		var url = "/realty/ajax/status_handler.php";
		$.post(url, data)
			.done(function() {
				$(".webform_realty").html("<span class=\"header_class\">Запрос на смену категории принят.</span>");
			})
			.fail(function() {
				$(".webform_realty").html("<span class=\"header_class\">Произошла ошибка. Повторите запрос позже.</span>");
			});
	}
	else
	{
		$(inputs).each(function() {
			if($.trim($(this).val()).length == 0)
				$(this).css("border", "1px solid red");
		});
	}			
}
function sort_filter_search(){
	$(".sort_asc, .sort_desc").click(function() {
	$("#result_arr").data( "page",0);
	 var elem = $(this);
	 var parentElem = elem.parent();
	 if(elem.hasClass("active_sort"))
	 {
		 elem.removeClass("active_sort");
		 parentElem.find(".sort_field").prop('disabled', true);
		 parentElem.find(".sort_type").prop('disabled', true);
	 }
	 else
	 {
		 parentElem.children(".active_sort").removeClass("active_sort");
		 elem.addClass("active_sort");
		 parentElem.find(".sort_field").prop('disabled', false);
		 parentElem.find(".sort_type").prop('disabled', false);
		 if (elem.hasClass("sort_asc"))
			parentElem.children(".sort_type").val("ASC");
		else
			parentElem.children(".sort_type").val("DESC");
	 }
	 $("form[name=search_object]").submit();
	 $("#my_request_show").click();
 });
}