<?php

define("PUBLIC_AJAX_MODE", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('faceid');

if (!\Bitrix\Faceid\AgreementTable::checkUser($USER->getId()))
{
	die;
}

if (substr($_POST['image'], 0, 5) == 'data:')
{
	$imageContent = str_replace('data:image/jpeg', 'data://image/jpeg', $_POST['image']);
	$fileContent = base64_decode(str_replace('data://image/jpeg;base64,', '', $imageContent));
}
else
{
	$http = new \Bitrix\Main\Web\HttpClient;

	if (substr($_POST['image'], 0, 4) != 'http')
	{
		$httpRequest = \Bitrix\Main\Context::getCurrent()->getRequest();
		$_POST['image'] = 'http'.($httpRequest->isHttps()?'s':'').'://'.$httpRequest->getHttpHost().$_POST['image'];
	}

	$fileContent = $http->get($_POST['image']);

	if (empty($fileContent))
	{
		echo '{}';
		die;
	}
}

if (!empty($_POST['action']))
{
	if ($_POST['action'] == 'identify' && !empty($_POST['visitor_id']))
	{
		$response = \Bitrix\FaceId\FaceId::identifyVk($fileContent);

		// get actual balance
		$currentBalance = \Bitrix\Main\Config\Option::get('faceid', 'balance', '1000');

		if (isset($response['status']['balance']))
		{
			$currentBalance = (int) $response['status']['balance'];
		}

		if (!empty($response['success']) && !empty($response['result']['found']))
		{
			$result = $response['result'];

			foreach ($result['items'] as &$vk)
			{
				$personal = array();
				if (substr_count($vk['bdate'], '.') > 1)
					$personal[] = $vk['bdate'];
				if (!empty($vk['city']))
					$personal[] = $vk['city'];
				$vk['personal'] = join(', ', $personal);
			}

			echo \Bitrix\Main\Web\Json::encode(array(
				'items' => $result['items'],
				'status' => array('balance' => $currentBalance)
			));
		}
		else
		{
			// some error
			echo '{}';
		}
	}
	elseif ($_POST['action'] == 'save' && !empty($_POST['visitor_id']))
	{
		$r = \Bitrix\Faceid\TrackingVisitorsTable::update($_POST['visitor_id'], array(
			'VK_ID' => $_POST['vk_id']
		));

		echo '{}';
	}
}

CMain::FinalActions();
