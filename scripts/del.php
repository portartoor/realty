<?
function glob_recursive($dir){
  foreach(glob($dir."2017-01*", GLOB_NOSORT) as $filename){
    //echo $filename."#<br>";
    if((is_dir($filename)))
	{ 
		echo "dir <br>";
		glob_recursive($filename."/*");
	}
	else
	{
		echo "del ".$filename."<br>";
		unlink($filename);
	}
  }
}
/*$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/1c/old/";
glob_recursive($dir);
$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/1c/domofey/d_old/";
glob_recursive($dir);
$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/1c/B24_Catalogs/old/";
glob_recursive($dir);*/
/*$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/1c/domofey/d_B24_Catalogs/old/";
glob_recursive($dir);*/
?>