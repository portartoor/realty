<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2017 Bitrix
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

class ImOpenlinesMailHistoryComponent extends CBitrixComponent
{
	private $configId = null;
	private $config = null;

	protected function checkModules()
	{
		if (!Loader::includeModule('im'))
		{
			\ShowError(Loc::getMessage('IMOL_COMPONENT_MODULE_IM_NOT_INSTALLED'));
			return false;
		}
		if (!Loader::includeModule('imopenlines'))
		{
			\ShowError(Loc::getMessage('IMOL_COMPONENT_MODULE_NOT_INSTALLED'));
			return false;
		}
		return true;
	}

	public function executeComponent()
	{
		$this->includeComponentLang('class.php');

		if (!$this->checkModules())
		{
			\Bitrix\Main\Mail\EventMessageThemeCompiler::stop();
			return false;
		}
		
		$this->arResult = $this->arParams;
		
		if ($this->arParams['TEMPLATE_TYPE'] == 'HISTORY')
		{
			$this->arResult['TEMPLATE_MESSAGES'] = \Bitrix\ImOpenLines\Mail::prepareSessionHistoryForTemplate($this->arParams['TEMPLATE_SESSION_ID']);
			if (!$this->arResult['TEMPLATE_MESSAGES'])
			{
				\Bitrix\Main\Mail\EventMessageThemeCompiler::stop();
				return false;
			}
		}
		else
		{
			$this->arResult['TEMPLATE_MESSAGES'] = \Bitrix\ImOpenLines\Mail::prepareOperatorAnswerForTemplate($this->arParams['TEMPLATE_SESSION_ID']);
			if (!$this->arResult['TEMPLATE_MESSAGES'])
			{
				\Bitrix\Main\Mail\EventMessageThemeCompiler::stop();
				return false;
			}
		}
		
		$parsedUrl = parse_url($this->arResult['TEMPLATE_WIDGET_URL']);
		if (isset($parsedUrl['query']))
		{
			$this->arResult['TEMPLATE_WIDGET_URL'] .= '&imolAction=answer';
		}
		else
		{
			$this->arResult['TEMPLATE_WIDGET_URL'] .= (substr($this->arResult['TEMPLATE_WIDGET_URL'], -1) != '?'? '?': '').'imolAction=answer';
		}
		
		$this->includeComponentTemplate();

		return true;
	}
};