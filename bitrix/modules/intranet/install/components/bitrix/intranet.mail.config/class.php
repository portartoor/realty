<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class CIntranetMailConfigComponent extends CBitrixComponent
{

	public function executeComponent()
	{
		$defaultUrlTemplates = array(
			'home'   => '',
			'domain' => 'domain/',
			'manage' => 'manage/',
		);

		$componentPage = '';

		if ($this->arParams['SEF_MODE'] == 'Y')
		{
			$urlTemplates  = \CComponentEngine::makeComponentUrlTemplates($defaultUrlTemplates, $this->arParams['SEF_URL_TEMPLATES']);
			$componentPage = \CComponentEngine::parseComponentPath($this->arParams['SEF_FOLDER'], $urlTemplates, $dummy);

			foreach ($urlTemplates as $page => $path)
			{
				$key = 'PATH_TO_MAIL_CFG_'.strtoupper($page);
				$this->arResult[$key] = $this->arParams[$key] ?: $this->arParams['SEF_FOLDER'].$path;
			}

			$this->arResult['PATH_TO_MAIL_CONFIG']  = $this->arParams['PATH_TO_MAIL_CONFIG'] ?: $this->arParams['SEF_FOLDER'].'?config';
			$this->arResult['PATH_TO_MAIL_SUCCESS'] = $this->arParams['PATH_TO_MAIL_SUCCESS'] ?: $this->arParams['SEF_FOLDER'].'?success';
		}
		else
		{
			if (!empty($_REQUEST['page']))
				$componentPage = $_REQUEST['page'];

			foreach ($defaultUrlTemplates as $page => $path)
			{
				$this->arResult['PATH_TO_MAIL_CFG_'.strtoupper($page)] = sprintf(
					'%s?page=%s',
					$APPLICATION->getCurPage(),
					strtolower($page)
				);
			}

			$this->arResult['PATH_TO_MAIL_CONFIG']  = sprintf('%s?page=home&config', $APPLICATION->getCurPage());
			$this->arResult['PATH_TO_MAIL_SUCCESS'] = sprintf('%s?page=home&success', $APPLICATION->getCurPage());
		}

		if (empty($componentPage) || !array_key_exists($componentPage, $defaultUrlTemplates))
			$componentPage = 'home';

		$this->includeComponentTemplate($componentPage);
	}

}
