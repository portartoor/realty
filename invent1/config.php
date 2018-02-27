<?
header("Content-Type: application/x-javascript");
$hash = "12345678";
$config = array("appmap" =>
	array("main" => "/invent1/",
		"left" => "/invent1/menu.php",
		"settings" => "/invent1/settings.php",
		"hash" => substr($hash, rand(1, strlen($hash)))
	)
);
echo json_encode($config);