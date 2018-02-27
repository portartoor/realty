<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo\Engine;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Context;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Seo\Engine;
use Bitrix\Seo\IEngine;
use Bitrix\Main\Text;
use Bitrix\Main\Text\Converter;

class Yandex extends Engine\YandexBase implements IEngine
{
	const ENGINE_ID = 'yandex';

	const SERVICE_URL = "https://webmaster.yandex.ru/api/v2";
	const API_BASE_URL = "https://api.webmaster.yandex.net/v3/user/";
	const API_HOSTS_URL = "hosts/";
	const API_SUMMARY_URL = "summary/";
	const API_VERIFICATION_URL = "verification/";
	const API_ORIGINAL_TEXTS_URL = "original-texts/";

	const HOSTS_SERVICE = "host-list";
	const HOST_VERIFY = "verify-host";
	const HOST_INFO = "host-information";
	const HOST_TOP_QUERIES = "top-queries";
	const HOST_ORIGINAL_TEXTS = "original-texts";
	const HOST_INDEXED = "indexed-urls";
	const HOST_EXCLUDED = "excluded-urls";

	const ORIGINAL_TEXT_MIN_LENGTH = 500;
	const ORIGINAL_TEXT_MAX_LENGTH = 32000;

	const QUERY_USER = 'https://login.yandex.ru/info';

	const VERIFIED_STATE_VERIFIED = "VERIFIED";
	const VERIFIED_STATE_WAITING = "WAITING";
	const VERIFIED_STATE_FAILED = "VERIFICATION_FAILED";
	const VERIFIED_STATE_NEVER_VERIFIED = "NEVER_VERIFIED";
	const VERIFIED_STATE_IN_PROGRESS = "IN_PROGRESS";
	
	private static $verificationTypes = array('DNS','HTML_FILE','META_TAG','WHOIS','TXT_FILE');

	protected $engineId = 'yandex';
	protected $arServiceList = array();
	private $userId = null;
	private $hostIds = array();
	
	public function __construct()
	{
		parent::__construct();
		
//		save user ID from auth
		if(isset($this->engineSettings['AUTH_USER']['id']))
			$this->userId = $this->engineSettings['AUTH_USER']['id'];
	}
	
	/**
	 * Construct URL of service for REST request.
	 * Glue base URL and params: user ID, host ID, service name and additional url-params
	 * @param null $userId
	 * @param null $hostId
	 * @param null $service
	 * @param null $params
	 * @return string
	 */
	private function getServiceUrlv3($userId = null, $hostId = null, $service = null, $params = null)
	{
		$url = self::API_BASE_URL;
		
		if($userId)
			$url .= $userId .'/';
		if($hostId)
			$url .= 'hosts/'.$hostId.'/';
		if($service)
			$url .= $service;
		if($params)
		{
			if(is_array($params))
				$params = '?'.http_build_query($params);
			else
				$params = '?' . str_replace('?','',$params);
			
			$url .= $params;
		}
		
		return $url;
	}
	
	// temporary hack
	public function getAuthSettings()
	{
		return $this->engineSettings['AUTH'];
	}
	
	private function getHostId($domain)
	{
//		get saved host ID
		if(isset($this->hostIds[$domain]) && !empty($this->hostIds[$domain]))
			return $this->hostIds[$domain];
		
//		else get host ID from API
		$hosts = $this->getFeedsV3();
		return $hosts[$domain];
	}

//	todo: deprecated this and other old methods
	public function getFeeds()
	{
		if(!isset($this->arServiceList[self::HOSTS_SERVICE]))
		{
			$this->getServiceDocument();
		}

		if(isset($this->arServiceList[self::HOSTS_SERVICE]))
		{
			$queryResult = $this->queryOld($this->arServiceList[self::HOSTS_SERVICE]);

			if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
			{
				return $this->processResult($queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
	}
	
	public function getFeedsV3()
	{
		$serviceUrl = $this->getServiceUrlv3($this->userId, null, self::API_HOSTS_URL);
		$queryResult = $this->queryV3($serviceUrl, 'GET');
		
		if($queryResult->getStatus() == self::HTTP_STATUS_OK && strlen($queryResult->getResult()) > 0)
		{
			$resultConverted = array();
			$result = Json::decode($queryResult->getResult());
			foreach($result['hosts'] as $host)
			{
				$hostUrl = str_replace(array('http://','https://','/'),'',$host['unicode_host_url']);
				$resultConverted[$hostUrl] = $host;
//				convert verified status in correct format
				if($host['verified'])
					$resultConverted[$hostUrl]['verification'] = self::VERIFIED_STATE_VERIFIED;
//				save hostId in local var
				$this->hostIds[$hostUrl] = $host['host_id'];
			}
			return $resultConverted;
		}
		else
		{
			throw new Engine\YandexException($queryResult);
		}
	}

	public function getSiteFeeds($domain)
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]))
		{
			$this->getFeeds();
		}

		if(isset($this->engineSettings['SITES'][$domain]))
		{
			$queryResult = $this->queryOld($this->engineSettings['SITES'][$domain]['href']);

			if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
			{
				return $this->processSiteResult($queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
		else
		{
			throw new \Exception('Site not binded! '.$domain);
		}
	}

	public function getSiteInfo($domain)
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$queryResult = $this->queryOld($this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_INFO]);
			if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
			{
				return $this->processResult($queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
		else
		{
			throw new \Exception('Site not binded! '.$domain);
		}
	}

	public function getQueriesFeed($domain)
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$queryResult = $this->queryOld($this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_TOP_QUERIES]);
			if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
			{
				$obXml = new \CDataXML();
				if($obXml->loadString($queryResult->result))
				{
					$root = $obXml->getTree()->elementsByName('top-queries');
					if(count($root) > 0)
					{
						$root = $root[0];

						$arQueriesData = array(
							'top-shows' => array(),
							'top-clicks' => array()
						);

						foreach ($root->children as $child)
						{
							switch($child->name())
							{
								case 'top-shows':
								case 'top-clicks':
									$arQueries = $child->elementsByName('top-info');
									foreach($arQueries as $query)
									{
										$arData = array();
										foreach($query->children() as $subChild)
										{
											$arData[$subChild->name()] = $subChild->textContent();
										}
										$arQueriesData[$child->name()][] = $arData;
									}

								break;
								default:
									$arQueriesData[$child->name()] = $child->textContent();
								break;
							}
						}

						return $arQueriesData;
					}

				}
				throw new \Exception('Unexpected query result! '.$queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
		else
		{
			throw new \Exception('Site not binded! '.$domain);
		}
	}

	public function getOriginalTexts($domain, $dir = "/")
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$queryResult = $this->queryOld($this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_ORIGINAL_TEXTS]);
			if($queryResult->status == self::HTTP_STATUS_OK)
			{
				$obXml = new \CDataXML();
				if($obXml->loadString($queryResult->result))
				{
					$arOriginalTexts = array();
					$arEntries = $obXml->getTree()->elementsByName('original-text');
					foreach($arEntries as $entry)
					{
						$arText = array();
						$arChildren = $entry->children();
						foreach($arChildren as $child)
						{
							$arText[$child->name] = $child->textContent();
						}
						$arOriginalTexts[] = $arText;
					}

					return array(
						"total" => $obXml->getTree()->root[0]->getAttribute("total"),
						"can-add" => $obXml->getTree()->root[0]->getAttribute("can-add"),
						"text" => $arOriginalTexts,
					);
				}

				throw new \Exception('Unexpected query result! '.$queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
	}

	public function addOriginalText($text, $domain, $dir = '/')
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
$str = <<<EOT
<original-text><content>%s</content></original-text>
EOT;

			$queryResult = $this->queryOld(
				$this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_ORIGINAL_TEXTS],
				'POST',
				urlencode(sprintf(
					$str,
					Converter::getXmlConverter()->encode(
						Text\Encoding::convertEncoding(
							$text,
							LANG_CHARSET,
							'utf-8'
						)
					)
				))
			);

			if($queryResult->status == self::HTTP_STATUS_OK || $queryResult->status == self::HTTP_STATUS_CREATED)
			{

				return true;
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
	}

	public function getIndexed($domain, $dir = "/")
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$queryResult = $this->queryOld($this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_INDEXED]);
			if($queryResult->status == self::HTTP_STATUS_OK)
			{
				$obXml = new \CDataXML();
				if($obXml->loadString($queryResult->result))
				{
					$arIndexed = array(
						'last-week-index-urls' => array(),
					);
					$root = $obXml->getTree()->root[0];
					foreach($root->children as $tag)
					{
						switch($tag->name())
						{
							case 'last-week-index-urls':
								if(count($tag->children()) > 0)
								{
									foreach($tag->children() as $child)
									{
										$arIndexed[$tag->name()][] = $child->textContent();
									}
								}
							break;
							default:
								$arIndexed[$tag->name()] = $tag->textContent();
							break;
						}
					}

					return $arIndexed;
				}

				throw new \Exception('Unexpected query result! '.$queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
	}

	public function getExcluded($domain, $dir = "/")
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$queryResult = $this->queryOld($this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_EXCLUDED]);
			if($queryResult->status == self::HTTP_STATUS_OK)
			{
				$obXml = new \CDataXML();
				if($obXml->loadString($queryResult->result))
				{
					$root = $obXml->getTree()->root[0]->children();
					$arExcluded = array(
						"count" => $root[0]->getAttribute('count'),
						"errors" => array(),
					);
					$arEntries = $obXml->getTree()->elementsByName('url-errors-with-code');
					foreach($arEntries as $entry)
					{
						$error = array(
							"code" => $entry->getAttribute('code')
						);
						foreach($entry->children() as $child)
						{
							$error[$child->name()] = $child->textContent();
						}
						$arExcluded['errors'][] = $error;
					}

					return $arExcluded;
				}

				throw new \Exception('Unexpected query result! '.$queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
	}
	
	public function addSite($domain, $dir = '/')
	{
		$domain = ToLower($domain);
		$queryDomain = Context::getCurrent()->getRequest()->isHttps() ? 'https://'.$domain : $domain;

		if(!isset($this->arServiceList[self::HOSTS_SERVICE]))
		{
			$this->getServiceDocument();
		}

		if(isset($this->arServiceList[self::HOSTS_SERVICE]))
		{
			$str = <<<EOT
<host><name>%s</name></host>
EOT;
			$queryResult = $this->queryOld(
				$this->arServiceList[self::HOSTS_SERVICE],
				"POST",
				sprintf($str, Converter::getXmlConverter()->encode($queryDomain))
			);

			if($queryResult->status == self::HTTP_STATUS_CREATED && strlen($queryResult->result) > 0)
			{
				return array($domain => true);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
	}
	
	public function addSiteV3($domain, $dir = '/')
	{
		$domain = ToLower($domain);
		$queryDomain = Context::getCurrent()->getRequest()->isHttps() ? 'https://'.$domain : $domain;
		
//		create JSON data in correct format
		$data = array("host_url" => $queryDomain);
		$data = Json::encode($data);
		$serviceUrl = $this->getServiceUrlv3($this->userId, null, self::API_HOSTS_URL);
		$queryResult = $this->queryV3($serviceUrl, 'POST', $data);
		
		if($queryResult->getStatus() == self::HTTP_STATUS_CREATED && strlen($queryResult->getResult()) > 0)
			return array($domain => true);
		else
			throw new Engine\YandexException($queryResult);
	}

	public function verifySite($domain, $bCheck)
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		$queryDomain = Context::getCurrent()->getRequest()->isHttps() ? 'https://'.$domain : $domain;
		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			if(!$bCheck)
			{
				$queryResult = $this->queryOld($this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_VERIFY]);
				if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
				{
					$obXml = new \CDataXML();
					if($obXml->loadString($queryResult->result))
					{
						$ver = $obXml->getTree()->elementsByName('verification');
						$ver = $ver[0];

						$state = $ver->getAttribute('state');
						if($state != 'VERIFIED')
						{
							return $ver->children[0]->textContent();
						}
						else
						{
							return false;
						}
					}
				}
				else
				{
					throw new Engine\YandexException($queryResult);
				}
			}
			else
			{
				$queryResult = $this->queryOld(
					$this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_VERIFY],
					"PUT",
					"<host><type>HTML_FILE</type></host>"
				);

				if($queryResult->status == self::HTTP_STATUS_OK || $queryResult->status == self::HTTP_STATUS_NO_CONTENT)
				{
					return array($domain => array('verification' => 'VERIFIED'));
				}
				else
				{
					throw new Engine\YandexException($queryResult);
				}
			}
		}

		return;
	}
	
	/**
	 * Just checking verify status of site and get UIN for verification
	 * @param $domain
	 * @return UIN if site not verified and FALSE if site already verify.
	 * @throws YandexException
	 */
	public function getVerifySiteUinV3($domain)
	{
		$domain = ToLower($domain);
		$hostId = $this->getHostId($domain);
		
		$serviceUrl = $this->getServiceUrlv3($this->userId, $hostId, self::API_VERIFICATION_URL);
		$queryResult = $this->queryV3($serviceUrl, 'GET');
		
		if($queryResult->getStatus() == self::HTTP_STATUS_OK && strlen($queryResult->getResult()) > 0)
		{
			$result = Json::decode($queryResult->getResult());
			if($result['verification_state'] != self::VERIFIED_STATE_VERIFIED)
				return $result['verification_uin'];
			else
				return false;	//already verify
		}
		else
		{
			throw new Engine\YandexException($queryResult);
		}
	}
	
//	todo: what is site not binded? It seems like first click by 'privazyat' will be bind site, and next - verify
	public function verifySiteV3($domain, $verType = 'HTML_FILE')
	{
		if(!in_array($verType, self::$verificationTypes))
			return array('error' => array('message' => 'incorrect verification type'));
			
		$domain = ToLower($domain);
		$hostId = $this->getHostId($domain);
		
		$serviceUrl = $this->getServiceUrlv3($this->userId, $hostId, self::API_VERIFICATION_URL, array('verification_type' => $verType));
		$queryResult = $this->queryV3($serviceUrl, 'POST');
		if($queryResult->getStatus() == self::HTTP_STATUS_OK && strlen($queryResult->getResult()) > 0)
		{
			$result = Json::decode($queryResult->getResult());
			return array($domain => array('verification' => $result['verification_state']));
		}
		else
		{
			throw new Engine\YandexException($queryResult);
		}
	}
	
	protected function getServiceDocument()
	{
		$queryResult = $this->queryOld(self::SERVICE_URL);
		if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
		{
			return $this->processServiceDocument($queryResult->result);
		}
		else
		{
			throw new Engine\YandexException($queryResult);
		}
	}

	protected function processServiceDocument($res)
	{
		$obXml = new \CDataXML();

		if($obXml->loadString($res))
		{
			$arEntries = $obXml->getTree()->elementsByName('link');
			foreach($arEntries as $entry)
			{
				$this->arServiceList[$entry->getAttribute('rel')] = $entry->getAttribute('href');
			}
		}
	}

	protected function processResult($res)
	{
		$obXml = new \CDataXML();

		if($obXml->loadString($res))
		{
			$arEntries = $obXml->getTree()->elementsByName('host');

			$arDomains = array();
			foreach($arEntries as $entry)
			{
				$entryChildren = $entry->children();
				$entryData = array();

				foreach ($entryChildren as $child)
				{
					$tag = $child->name();

					switch($tag)
					{
						case 'name':
							$value = \CBXPunycode::toASCII(ToLower($child->textContent()), $e = null);
							if(preg_match("/^https:\/\//", $value))
							{
								$value = substr($value, 8);
								$entryData['https'] = 1;
							}

							$entryData[$tag] = $value;

						break;

						case 'verification':
						case 'crawling':
							$entryData[$tag] = $child->getAttribute('state');
							$details = $child->children();
							if($details)
							{
								$entryData[$tag.'-details'] = $details[0]->textContent();
							}
						break;

						case 'virused':
							$entryData[$tag] = $child->textContent() == 'true';
						break;

						default: $entryData[$tag] = $child->textContent();
					}
				}

				// HOST_INFO query returns only host id instead of direct url so we should take it from the previous data
				$hostHref = $entry->getAttribute('href');
				if(!$hostHref)
				{
					$hostHref = $this->engineSettings['SITES'][$entryData['name']]['href'];
				}
				$entryData['href'] = $hostHref;

				$arDomains[$entryData['name']] = $entryData;
			}

			$arExistedDomains = \CSeoUtils::getDomainsList();
			foreach($arExistedDomains as $domain)
			{
				$domain['DOMAIN'] = ToLower($domain['DOMAIN']);

				if(isset($arDomains[$domain['DOMAIN']]))
				{
					if(!is_array($this->engineSettings['SITES']))
						$this->engineSettings['SITES'] = array();

					$this->engineSettings['SITES'][$domain['DOMAIN']] = $arDomains[$domain['DOMAIN']];
				}
			}

			$this->saveSettings();

			return $arDomains;
		}

		throw new \Exception('Unexpected query result! '.$res);
		return false;
	}

	protected function processSiteResult($res)
	{
		$obXml = new \CDataXML();

		if($obXml->loadString($res))
		{
			$hostName = $obXml->getTree()->elementsByName('name');
			$hostName = \CBXPunycode::toASCII(ToLower($hostName[0]->textContent()), $e = null);

			if(preg_match("/^https:\/\//", $hostName))
			{
				$hostName = substr($hostName, 8);
			}

			$this->engineSettings['SITES'][$hostName]['SERVICES'] = array();

			$arLinks = $obXml->getTree()->elementsByName('link');
			foreach ($arLinks as $link)
			{
				$this->engineSettings['SITES'][$hostName]['SERVICES'][$link->getAttribute('rel')] = $link->getAttribute('href');
			}

			$this->saveSettings();

			return true;
		}

		return false;
	}
	
	/**
	 * @deprecated by queryV3
	 * @param $scope
	 * @param string $method
	 * @param null $data
	 * @param bool $skipRefreshAuth
	 * @return \CHTTP
	 */
	protected function queryOld($scope, $method = "GET", $data = null, $skipRefreshAuth = false)
	{
		if($this->engineSettings['AUTH'])
		{
			$http = new \CHTTP();
			$http->setAdditionalHeaders(
				array(
					'Authorization' => 'OAuth '.$this->engineSettings['AUTH']['access_token']
				)
			);
			$http->setFollowRedirect(false);

			switch($method)
			{
				case 'GET':
					$result = $http->get($scope);
				break;
				case 'POST':
					$result = $http->post($scope, $data);
				break;
				case 'PUT':
					$result = $http->httpQuery($method, $scope, $http->prepareData($data));
				break;
				case 'DELETE':

				break;
			}

			if($http->status == 401 && !$skipRefreshAuth)
			{
				if($this->checkAuthExpired())
				{
					$this->queryOld($scope, $method, $data, true);
				}
			}

			$http->result = Text\Encoding::convertEncoding($http->result, 'utf-8', LANG_CHARSET);

			return $http;
		}
	}
	
	/**
	 * Create HTTP client, set necessary headers and set request
	 *
	 * @param $scope - URL of service with additional params, if needed
	 * @param string $method - may be POST, GET or DELETE
	 * @param null $data
	 * @param bool $skipRefreshAuth
	 * @return HttpClient
	 */
	private function queryV3($scope, $method = "GET", $data = null, $skipRefreshAuth = false)
	{
		if($this->engineSettings['AUTH'])
		{
			$http = new HttpClient();
			$http->setHeader('Authorization', 'OAuth ' . $this->engineSettings['AUTH']['access_token']);
			$http->setRedirect(false);
			switch ($method)
			{
				case 'GET':
					$http->get($scope);
					break;
				case 'POST':
					$http->setHeader('Content-type', 'application/json');
					$http->post($scope, $data);
					break;
				case 'DELETE':
					break;
			}

//			todo: what is it??
			if ($http->getStatus() == 401 && !$skipRefreshAuth)
			{
				if ($this->checkAuthExpired())
				{
					$this->queryV3($scope, $method, $data, true);
				}
			}

			return $http;
		}
	}
}
?>