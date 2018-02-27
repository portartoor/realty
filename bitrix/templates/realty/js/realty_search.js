	//Создаем объект класса Map
	var Map = (function() {

		var myMap,
			placemarks = [],
			points = [];

		function init() 
		{
			myMap = new ymaps.Map("map", {
				center: [54.709945099931694,20.509721533901942],
				zoom: 12,
				controls: []
			});

			addPolygon();
		}

		//Функция поиска меток для карты
		//Параметры:
		//pages - номер страницы для постраничной навигации
		//IDs - идентификаторы объектов маркеры которых неоходимо вывести
		//arBounds - массив с кординатами ограничивающими зону попадания для меток.
		//  формат массива array(array(latitude, longtitude) - левый нижний угол, array(latitude, longtitude) - правый верхний)
		//callback - функция которая будет вызвана по завершению размещения меток
		//
		function getPoints(page, IDs, arBounds, callback)
		{
			//Значения по умолчанию
			page = typeof page !== 'undefined' ?  page : 1;
			if (typeof callback === 'undefined') 
				callback = function() {};
			var parametrs = "";
			//Получаем значения из формы для фильтрации объектов
			parametrs = $("form[name=search_object]").serialize() + "&PAGEN_2=" + page;
			//Если указаны ID, то включаем их в фильтр
			if (typeof IDs !== 'undefined') 
			{
				for (var i = 0; i <= IDs.length; i++)
				{
					parametrs += "&ID[]=" + IDs[i];
				}
			}
			//Если указаны границы то включаем их в фильтр
			else if (typeof arBounds !== 'undefined')
			{
				parametrs += "&UF_LATITUDE_FROM=" + arBounds[0][0];
				parametrs += "&UF_LONGITUDE_FROM=" + arBounds[0][1];
				parametrs += "&UF_LATITUDE_TO=" + arBounds[1][0];
				parametrs += "&UF_LONGITUDE_TO=" + arBounds[1][1];
			}
			//Посылаем ajax POST-запрос в формате json
			$.ajax({
				dataType: "json",
				type: "POST",
				url: "/realty/search/points.php",
				data: parametrs,
			})
				.done(function(data) {
					$.each(data, function( i, item ) {
						//Если встречаем в ответе ключ "END_SEARCH", то проверяем остались ли еще данные
						if (i == "END_SEARCH")
						{
							//Делаем вызов для дополнительных данных
							if (item === "false")
							{
								getPoints (++page, IDs, arBounds, function() {callback();});
							}
							else
								//Заканчиваем поиск и передаем массив меток функции отрисовки
								//также передаем функцию для вызова по завершению работы
								addPoints(placemarks, function() {
									callback();
								})
						}
						else
						{
							//Заполняем массив меток
							placemarks.push({
								ID: item[0], 
								coords: item[1], 
								header: item[2], 
								body: item[3], 
								footer: item[4]
							});
						}
					});
				});
		}

		//Функция добавления меток на карту
		//Параметры:
		//pm - массив меток
		//  формат массива меток:
		//    {ID: int, coords: array(latitude, longtitude), header: string, body: string, footer: string}
		//callback - функция которая будет вызвана по завершению размещения меток
		//
		function addPoints(pm, callback) 
		{
			var nexti = 0;
			if (pm.length)
			{
				//Разбираем массив меток
				$.each(pm, function( i, item ) {
					nexti = i + 1;
					//Добавляем метки на карту, заполняем их информацию, редактируем вид
					myMap.geoObjects.add(new ymaps.Placemark(item.coords, {
						balloonContentHeader: item.header,
						balloonContentBody: item.body,
						balloonContentFooter: item.footer,
						objectID: item.ID
					},{
						//Вид метки
	           			iconLayout: 'default#image',
						iconImageHref: '/bitrix/templates/realty/images/house-marker.png',
	           			iconImageSize: [37, 37],
	           			iconImageOffset: [-18, -18]
	       			}));
					//Вызов функции callback, если массив меток кончился
					if (typeof pm[nexti] === 'undefined' && typeof callback !== 'undefined') {
						callback();
					}
				});
			}
			else
			{
				$("#result_arr").html("<div class=\"not_found\">По вашему запросу ничего не найдено</div>");
			}
			//Изменение масштаба и центра карты, чтобы было видно все метки
			myMap.setBounds(myMap.geoObjects.getBounds(), {
				checkZoomRange: true
			});
		}

		//Функция проверки, есть ли объекты на карте (метки, полигоны)
		//Возвращает кол-во объектов на карте
		function checkMarkers()
		{
			var Objects = ymaps.geoQuery(myMap.geoObjects);
			return Objects._objects.length;
		}

		//Функция переноса полигона с канваса и кординат страницы, на карту и кординаты карты
		//Параметры:
		//coords - кординаты полигона на странице
		//Возвращает: кординаты для карты
		function convert(coords) 
		{
			var projection = myMap.options.get('projection');

			return coords.map(function(el) {
			var c = projection.fromGlobalPixels(myMap.converter.pageToGlobal([el.x, el.y]), myMap.getZoom());
			return c;
			});
		}

		//Функция добавления полигона на карту
		//Параметры:
		//coord - кординаты вершин полигона
		function addPolygon(coord) 
		{
			//Добавления списка объектов в формате geoQuery в переменую
			var storage = ymaps.geoQuery(myMap.geoObjects);
			//Создание полигона
			var myGeoObject = new ymaps.GeoObject({
				geometry: {
					type: "Polygon",
					coordinates: [coord],
				},
				}, {
					//Вид полигона
					fillColor: '#00FF00',
					strokeColor: '#0000FF',
					opacity: 0.5,
					strokeWidth: 3
			});

			//Очистка карты от объектов
			storage.each(function(object) {
				myMap.geoObjects.remove(object);
			});
			//Очистка массива меток
			placemarks = [];

			//Добавление полигона на карту
			myMap.geoObjects.add(myGeoObject);

			//Вызов поиска меток с ограничением по границам полигона
			var arBounds = myGeoObject.geometry.getBounds();
			getPoints(1, undefined, arBounds, function() {removeMarkersOutsidePoligon(myGeoObject)});
		}

		//Функция по удалению меток не попавших в полигон
		//Параметры:
		//poligon - объект geoObject описывающий полигон
		function removeMarkersOutsidePoligon (poligon) 
		{
			//Очищаем массив маркеров
			points.length = 0;
			//Получаем список объектов на карте
			var storage = ymaps.geoQuery(myMap.geoObjects),
				//Список объектов в полигоне, включая сам полигон
				storageInsideWithPoligon = storage.searchInside(poligon),
				//Почему приходится удалять полигон два раза я не знаю
				//Список объектов полигоне, без полигона
			    storageInsidePoligon = storageInsideWithPoligon.remove(poligon),
				//Список объектов вне полигона
				objectsOutsidePoligon = storage.remove(storageInsideWithPoligon).remove(poligon);
			//Удаляем объекты вне полигона
			objectsOutsidePoligon.each(function(pm) {
				myMap.geoObjects.remove(pm);
			});
			//Заполняем массив маркеров ID объектов внутри полигона
			storageInsidePoligon.each(function(pm) {
				points.push(pm.properties.get('objectID'));
			});
			//Вывод результаты поиска на страницу
			//showResults(storageInsidePoligon);
		}

		//Функция вывода результата поиска на страницу
		//Параметры:
		//storage - объект geoQuery со списком объектов geoObjects для вывода
		/*function showResults (storage) 
		{
			//Обнуляем список результатов
			$("#result_arr").empty();
			null_page(0);
			//Получаем поля формы поиска
			var data = $("form[name=search_object]").serialize();
			//Добавляем в фильтр ID меток попавших в полигон
			storage.each(function(pm) {
				data += "&ID[]=" + pm.properties.get('objectID');
			});
			//Ищем объекты по сформировнному фильтру
			send_search_filter(data);
		}*/

		//Публикуем методы и переменные
		return {
			addPolygon: addPolygon,
			addPoints: addPoints,
			getPoints: getPoints,
			convert: convert,
			checkMarkers: checkMarkers,
			points: points,
			init: init
		}
	})();
	
	//----------------------

	var canv = {},
		map = {},
		canvJq = {},
		ctx = "";

	line = [];

	var startX = 0,
	startY = 0;
	
	function showCanvas() 
	{
		canv = document.getElementById('canv');
		map = document.getElementById('map');
		canv.width = map.offsetWidth;
		canv.height = map.offsetHeight;
		canvJq = $("#canv");
		ctx = canv.getContext('2d');
		canv.addEventListener('touchstart', mouseDown, false);
	}
	
	function hideCanvas()
	{
		$(".draw-on-map").removeClass("active");
		canv.removeEventListener('touchstart', mouseDown); 
		canv.width = 0;
		canv.height = 0;
	}
	
	function mouseDown(e) 
	{
		e.preventDefault();
		ctx.clearRect(0, 0, canv.width, canv.height);

		startX = e.targetTouches[0].pageX - canvJq.offset().left;
		startY = e.targetTouches[0].pageY - canvJq.offset().top;
		
		lineX = e.targetTouches[0].pageX;
		lineY = e.targetTouches[0].pageY;
		
		canv.addEventListener('touchend', mouseUp, false);
		canv.addEventListener('touchmove', mouseMove, false);
		
		
		line = [];
		line.push({
			x: lineX,
			y: lineY
		});
	}

	function mouseMove(e) 
	{
		e.preventDefault();
			
		var x = e.targetTouches[0].pageX - canvJq.offset().left,
		y = e.targetTouches[0].pageY - canvJq.offset().top,
		lineX = e.targetTouches[0].pageX,
		lineY = e.targetTouches[0].pageY;
		
		ctx.beginPath();
		ctx.moveTo(startX, startY);
		ctx.lineTo(x, y);
		ctx.stroke();
		
		startX = x;
		startY = y;
		line.push({
			x: lineX,
			y: lineY
		});
	}

	function mouseUp() 
	{
		canv.removeEventListener('touchend', mouseUp);
		canv.removeEventListener('touchmove', mouseMove); 

		aproximate();
	}

	function aproximate() 
	{
		ctx.clearRect(0, 0, canv.width, canv.height);
		var res = simplify(line, 5);
		res = Map.convert(res);
		Map.addPolygon(res);
		hideCanvas();
	}

	function stickyShowMap(showmap_start_pos)
	{
		var showmap        = $('#showMap'),
			showmap_height = showmap.height() + "px";
		showmap_start_pos += showmap.height();
		if ($(window).scrollTop() >= showmap_start_pos && $(window).scrollTop() < showmap_start_pos + $(".map-block").height() && $(".map-block").css("display") != "none") {
			showmap.css({
				maxWidth: "1120px",
				position: "fixed",
				top: 0,
				zIndex: 2
			});
			$(".map-block").css("marginTop", showmap_height);
		}
		else {
			showmap.css({
				position: "relative",
				top: 0,
				zIndex: 1
			});
			$(".map-block").css("marginTop", 0);
		}
	}
	
	$(document).ready(function() 
	{
		/*$("form[name=search_object]").submit(function(event) 
		{
			console.log(Map.points);
			if($("#result_arr").data( "page" )==0||$("#result_arr").data( "page" )==1)$("#result_arr").empty();
			var data = $(this).serialize();
			var dataForMap = "";
			$("#search_object :input[value!=''][type!=hidden][type!=submit]").each(function(i, item) {
				if ($(this).val() != "") 
				{
					if (dataForMap.length) dataForMap += "&";
					dataForMap += $(this).prop("name") + "=" + $(this).val();
				}
			});
			var url = "/realty/ajax/results_with_map.php?PAGEN_2="+$("#result_arr").data( "page" );
			if (Map.points.length)
			{
				$.each(Map.points, function(i, item) {
					data += "&ID[]=" + item;
				});
			}
			if ($("#map").html() != "" && dataForMap.length)
			{
				Map.getPoints();
			}
			$.post(url, data, function(data) {
				$("#result_arr").append(data);
				if(data==""||$("#result_arr .not_found").length)$("#result_arr").data( "page" ,"n")
				inProgress=false;
			});
			event.preventDefault();
		});*/
		var showmap_start_pos = $('#showMap').offset().top;
		
		stickyShowMap(showmap_start_pos);
		
		$(window).scroll(function() {
			stickyShowMap(showmap_start_pos);
		});			
		
		$("#showMap").click(function(e) {
			$(".map-block").toggle(0, function() {
				if ($(this).css("display") == "block")
					$("#showMap").text("Скрыть карту");
				else
					$("#showMap").text("Показать на карте");
				if ($("#map").html() == "")
					ymaps.ready(Map.init);
			});
		});
		$(".draw-on-map").click(function(e) {
			if ($(this).hasClass("active"))
			{
				$(this).removeClass("active");
				hideCanvas();
			}
			else
			{
				$(this).addClass("active");
				showCanvas();
			}
		});
	});