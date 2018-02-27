<?
use Bitrix\Main\IO\Path;
use Bitrix\Main\IO\File;

$normPath = Path::normalize(__FILE__);
$filePath = Path::getDirectory(Path::getDirectory($normPath)).Path::DIRECTORY_SEPARATOR.'en'.Path::DIRECTORY_SEPARATOR.Path::getName($normPath);
if (File::isFileExists($filePath))
	include($filePath);
