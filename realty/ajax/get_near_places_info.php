<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?key=AIzaSyC-4XkC7UTdEYyJtDhQ8FKazoizfIhMkvI&location='.$_GET["location"]."&rankby=distance&types=hospital|bank|school|doctor|bus_station|gas_station|university|restaurant&language=ru";
$data = array('key1' => 'value1', 'key2' => 'value2');

// use key 'http' even if you send the request to https://...
$options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
if ($result === FALSE) { /* Handle error */ }
echo($result);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>