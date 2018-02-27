<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage rest
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Rest\Dictionary;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\Dictionary;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

class RemoteDictionary extends Dictionary
{
	const ID = 'generic';
	const BASE_URL = 'https://www.bitrix24.ru/util/';

	const CACHE_TTL = 86400;
	const CACHE_PREFIX = 'rest_dictionary';

	protected $language = null;

	public function __construct()
	{
		$this->language = LANGUAGE_ID;

		$values = $this->init();

		parent::__construct($values);
	}

	public function setLanguage($language)
	{
		if($language !== $this->language)
		{
			$this->language = $language;
			$this->set($this->init());
		}
	}

	protected function init()
	{
		$managedCache = Application::getInstance()->getManagedCache();
		if($managedCache->read(static::CACHE_TTL, $this->getCacheId()))
		{
			$dictionary = $managedCache->get($this->getCacheId());
		}
		else
		{
			$dictionary = $this->load();
			$managedCache->set($this->getCacheId(), $dictionary);
		}

		return $dictionary;
	}

	protected function load()
	{
		$httpClient = new HttpClient();

		$uri = $this->getDictionaryUri();

		$httpResult = $httpClient->get($uri->getLocator());

		try
		{
			$result = Json::decode($httpResult);
		}
		catch(ArgumentException $e)
		{
			$result = null;
		}

		return $result;
	}

	protected function getCacheId()
	{
		return static::CACHE_PREFIX.'/'.static::ID.'/'.$this->language;
	}

	/**
	 * @return Uri
	 */
	protected function getDictionaryUri()
	{
		$uri = new Uri(static::BASE_URL);
		$uri->addParams(array(
			'type' => static::ID,
			'lng' => $this->language,
		));

		return $uri;
	}
}
