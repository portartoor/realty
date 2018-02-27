<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Dummy component for compatibility with the old OAuth scheme.
 *
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 * @global CMain $APPLICATION
 */


use Bitrix\Main\Loader;

if(!Loader::includeModule('rest'))
{
	return;
}

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

$httpClient = new \Bitrix\Main\Web\HttpClient(array(
	'socketTimeout' => 10,
	'streamTimeout' => 10,
	'redirect' => false,
));

$requestArray = $request->toArray();
$requestedScope = '';
if(isset($requestArray['scope']))
{
	$requestedScope = $requestArray['scope'];
	unset($requestArray['scope']);
}

$authResult = $httpClient->get(\Bitrix\Rest\OAuthService::SERVICE_URL.'/oauth/token/'
	.'?bx_proxy_from='.urlencode($request->getHttpHost())
	.'&'.http_build_query($requestArray));

try
{
	$auth = \Bitrix\Main\Web\Json::decode($authResult);


	if(is_array($auth))
	{
		// TODO: remove this crap in about a month after release
		/* crap start */
		if($request['grant_type'] === 'refresh_token' && $auth['error'] == 'invalid_grant')
		{
			if($request['grant_type'] === 'refresh_token')
			{
				global $DB;

				if($DB->TableExists('b_oauth_refresh_token'))
				{
					$dbRes = $DB->Query("
SELECT ort.ID, oc.CLIENT_ID, oc.CLIENT_SECRET, ort.USER_ID
FROM b_oauth_refresh_token ort
LEFT JOIN b_oauth_client oc ON oc.ID=ort.CLIENT_ID
WHERE REFRESH_TOKEN='".$DB->ForSql($request['refresh_token'])."'
AND ort.EXPIRES > '".time()."'
LIMIT 1
");
					$refreshTokenInfo = $dbRes->Fetch();
					if($refreshTokenInfo)
					{
						$salt = substr($refreshTokenInfo["CLIENT_SECRET"], 0, 8);
						$clientSecret = $request['client_secret'];
						if($refreshTokenInfo["CLIENT_SECRET"] == $salt.md5($salt.$clientSecret))
						{
							$DB->Query("DELETE FROM b_oauth_refresh_token WHERE ID='".intval($refreshTokenInfo['ID'])."'");

							$newAuth = \Bitrix\Rest\OAuth\Auth::get(
								$refreshTokenInfo['CLIENT_ID'],
								$requestedScope,
								array(),
								$refreshTokenInfo['USER_ID']
							);

							if(is_array($newAuth))
							{
								$auth = $newAuth;
							}
						}
					}
				}
			}
		}
		/* crap end */

		$auth['domain'] = $request->getHttpHost();

		if($requestedScope != '')
		{
			$auth['scope'] = $requestedScope;
		}

		$authResult = \Bitrix\Main\Web\Json::encode($auth);
	}
}
catch (\Bitrix\Main\ArgumentException $e)
{
}

$responseHeaders = $httpClient->getHeaders();

\CHTTP::SetStatus($httpClient->getStatus());

Header('Content-Type: '.$responseHeaders->get('Content-Type'));

echo $authResult;

CMain::FinalActions();
die();

