<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$folder = $this->GetFolder();

$arResult['TEMPLATE_DATA'] = array(
	'EXTENSION_ID' => 'tasks_task_list_component_ext_'.md5($folder)
);

CJSCore::RegisterExt(
	$arResult['TEMPLATE_DATA']['EXTENSION_ID'],
	array(
		'js'  => $folder.'/logic.js',
		'rel' =>  array('tasks_util_query')
	)
);