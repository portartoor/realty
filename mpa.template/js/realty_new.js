$(document).ready(function(){
	$('input[type=submit]').click(function(){
		if (window.location.href.indexOf("mobile") >= 0 )
			window.location.href = "/mobile/realty/new/?step="+($( "input[name='step']" ).val()-(-1))+"&REQUEST_ID="+$( "input[name='REQUEST_ID']" ).val();			
		else
			window.location.href = "/realty/new/?step="+($( "input[name='step']" ).val()-(-1))+"&REQUEST_ID="+$( "input[name='REQUEST_ID']" ).val();
		return false;
	});
	$('.quick_perehod_btn a,.photo_mode_1').click(function(){
		if (window.location.href.indexOf("mobile") >= 0 )
			window.location.href = "/mobile/realty/new/?step="+$(this).data("step")+"&REQUEST_ID="+$( "input[name='REQUEST_ID']" ).val();			
		else
			window.location.href = "/realty/new/?step="+$(this).data("step")+"&REQUEST_ID="+$( "input[name='REQUEST_ID']" ).val();
		return false;
	});
	$(window).on("scroll", function(e) {
	  if ($(window).scrollTop() > 50) {
		$(".quick_perehod_btn").addClass("fixed");
	  } else {
	 	$(".quick_perehod_btn").removeClass("fixed");
	  }
	  
	});
	var url = window.location.href;
	var hash = url.substring(url.indexOf("#")+1);
	if(url.indexOf("#")!=-1&&hash!="")
	{
		if($("input[name="+hash+"]").length)
			$('html,body').animate({
				scrollTop: $("input[name="+hash+"]").offset().top
			}, 500);
		else if($("select[name="+hash+"]").length)
			$('html,body').animate({
				scrollTop: $("select[name="+hash+"]").offset().top
			}, 500);
	}
    $("#more_middle_price").click(function(){
		$.get('/realty/new/same_obj.php?price=1&REQUEST_ID='+$("input[name=REQUEST_ID]").val(), 
			function(data) {
				$(".middle_price_block").removeClass("hide");
				$('#middle_price').html(data);	
				find_percent_middle_price();
			});
   });
    $("#more_middle_price").click();
	$(".middle_price_block a.set_price_cl").click(function(){
		event.preventDefault();
		if (parseInt($('#middle_price').html().replace(/\s/g, ''))>0)
		{
			$("input[name=UF_CLIENT_PRICE]").val($('#middle_price').html());
			$("input[name=UF_CLIENT_PRICE]").change();
			$(".column_m_price").last().removeClass("red").removeClass("green");
			$('#middle_price_percent').html("100%");
		}
	});
   function find_percent_middle_price() {
	   if($('#middle_price').length > 0)
	   {
		   var numb_per_1 = parseInt($('#middle_price').html().replace(/\s/g, ''));
		   var numb_per_2 = parseInt($("input[name=UF_CLIENT_PRICE]").val().replace(/\s/g, ''));
		   var numb_per_sr = 0;
		   if(numb_per_1<numb_per_2&&numb_per_1>0)
		   {
			   numb_per_sr = Math.round(numb_per_2*100/ numb_per_1)-100;
			   $(".column_m_price.percent").addClass("red").removeClass("green");
			   numb_per_sr="+"+numb_per_sr;
		   }
		   else if(numb_per_1>numb_per_2&&numb_per_1>0)
		   {
			   numb_per_sr = 100-Math.round(numb_per_2*100/ numb_per_1);
			   console.log(numb_per_2*100/ numb_per_1);
			   $(".column_m_price.percent").removeClass("red").addClass("green");
			   numb_per_sr="-"+numb_per_sr;
		   }  
			else
			{
				numb_per_sr=100;
				$(".column_m_price.percent").removeClass("red").removeClass("green");
			} 
		   $('#middle_price_percent').html(numb_per_sr+"%");	
	   }
   }
   $(".find_middle_price").click(function(){
	   if (window.location.href.indexOf("mobile") >= 0 )
			window.location.href = "/mobile/realty/new/same_obj_list.php?step=3&REQUEST_ID="+$( "input[name='REQUEST_ID']" ).val();
		else
			window.location.href = "/realty/new/same_obj_list.php?step=3&REQUEST_ID="+$( "input[name='REQUEST_ID']" ).val();
		return false;
   });   
   $(".photo_mode_2").click(function(){
	   $(".photo_mode_1").removeClass("active");
	   $(".photo_mode_2").addClass("active");
	   $.get( "/realty/new/step_5.php?REQUEST_ID="+$("input[name=REQUEST_ID]").val(),function( data ) {
		   console.log("123");
		   $(".webform_realty").html(data);
	   });
	   return false;
   });  
	$(".mail_client_buklet").click(function(){
		event.preventDefault();
		$("#reset_all").show();
		return false;
	});
	$(".close_w").click(function(){
		event.preventDefault();
		$("#reset_all").hide();
		return false;
	});
	$(".go_request.send").click(function(){
		event.preventDefault();
		$.post( "/realty/new/mail_buklet.php",{ mail: $("input[name=mail_client]").val()}).done(function( data ) {
		  var obj = JSON.parse(data);
		  console.log(obj);
		  if(obj.err=="")
		  {
			$("#reset_all h3").html("<span class='green'>Ваше письмо отправлено</span>"); 
			setTimeout(function(){
				$("#reset_all").hide();
			}, 2000);
		  }
		});
		return false;
	});
	
});