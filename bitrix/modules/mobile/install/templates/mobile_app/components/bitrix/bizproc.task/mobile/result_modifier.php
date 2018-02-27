<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arResult["SKIP_BP"] = 'N';

if (!empty($arResult['TASK']['PARAMETERS']['REQUEST']))
{
	//primitive checking
	if (empty($arResult['TypesMap']))
	{
		$whiteList = array(
			'bool', 'double', 'int', 'select', 'string', 'text', 'internalselect',
			//CBPVirtualDocument
			'B', 'N', 'L', 'S', 'T'
		);
		foreach ($arResult['TASK']['PARAMETERS']['REQUEST'] as $request)
		{
			if (!in_array($request['Type'], $whiteList))
			{
				$arResult["SKIP_BP"] = 'Y';
				break;
			}
		}
	}
	//smart checking
	else
	{
		$checkedTypes = array();
		foreach ($arResult['TASK']['PARAMETERS']['REQUEST'] as $request)
		{
			$type = strtolower($request['Type']);
			if (!in_array($type, $checkedTypes))
			{
				if (isset($arResult['TypesMap'][$type]))
				{
					/** @var Bitrix\Bizproc\BaseType\Base $typeClass */
					$typeClass = $arResult['TypesMap'][$type];
					if (!$typeClass::canRenderControl(\Bitrix\Bizproc\FieldType::RENDER_MODE_MOBILE))
					{
						$arResult["SKIP_BP"] = 'Y';
						break;
					}
					$checkedTypes[] = $type;
				}
				else
				{
					$arResult["SKIP_BP"] = 'Y';
					break;
				}
			}
		}
	}
}

if (!empty($arResult["TASK"]["DESCRIPTION"]))
{
	$arResult["TASK"]["DESCRIPTION"] = preg_replace_callback(
		'|<a href="/bitrix/tools/bizproc_show_file.php\?([^"]+)"\starget=\'_blank\'>|',
		function($matches)
		{
			parse_str($matches[1], $query);
			$filename = '';
			if (isset($query['f']))
			{
				$query['hash'] = md5($query['f']);
				$filename = $query['f'];
				unset($query['f']);
			}
			$query['mobile_action'] = 'bp_show_file';

			return '<a href="#" data-url="'.SITE_DIR.'mobile/ajax.php?'.http_build_query($query)
				.'" data-name="'.htmlspecialcharsbx($filename).'" onclick="BXMobileApp.UI.Document.open({url: this.getAttribute(\'data-url\'), filename: this.getAttribute(\'data-name\')}); return false;">';
		},
		$arResult["TASK"]["DESCRIPTION"]
	);
}