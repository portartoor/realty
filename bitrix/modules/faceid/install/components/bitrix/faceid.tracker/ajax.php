<?php

define("PUBLIC_AJAX_MODE", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$VISIT_DURATION = 3600*2;

\Bitrix\Main\Loader::includeModule('faceid');

if (!\Bitrix\Faceid\AgreementTable::checkUser($USER->getId()))
{
	die;
}

$imageContent = str_replace('data:image/jpeg', 'data://image/jpeg', $_POST['image']);

$fileContent = base64_decode(str_replace('data://image/jpeg;base64,', '', $imageContent));

if (!empty($_POST['action']))
{
	// current balance cache
	$currentBalance = \Bitrix\Main\Config\Option::get('faceid', 'balance', '1000');

	if ($_POST['action'] == 'identify')
	{
		$visitor = null;
		$confidence = 0;

		$response = \Bitrix\FaceId\FaceId::identify($fileContent);

		// get actual balance
		if (isset($response['status']['balance']))
		{
			$currentBalance = (int) $response['status']['balance'];
		}

		if (!empty($response['success']) && !empty($response['result']['found']))
		{
			$result = $response['result'];

			$faceId = $result['items'][0]['face_id'];
			$confidence = round($result['items'][0]['confidence']);

			$visitor = \Bitrix\Faceid\TrackingVisitorsTable::getRow(array(
				'filter' => array('=FACE_ID' => $faceId)
			));

			if (!empty($visitor))
			{
				$visitorId = $visitor['ID'];

				// check if it is new visit
				$currentTime = new \Bitrix\Main\Type\DateTime;
				$diff = $currentTime->getTimestamp() - $visitor['LAST_VISIT']->getTimestamp();

				if ($diff > $VISIT_DURATION)
				{
					// register new hit
					\Bitrix\Faceid\TrackingVisitsTable::registerVisit($visitorId);
					$visitor = \Bitrix\Faceid\TrackingVisitorsTable::getById($visitorId)->fetch();
				}
				else
				{
					// update last visit
					\Bitrix\Faceid\TrackingVisitorsTable::update($visitorId, array(
						'LAST_VISIT' => $currentTime
					));

					$visitor['LAST_VISIT'] = $currentTime;
				}
			}
			else
			{
				// photo has been added by another module
				// create visitor
				$face = \Bitrix\Faceid\FaceTable::getRowById($faceId);
				if ($face)
				{
					$visitorId = \Bitrix\Faceid\TrackingVisitorsTable::add(array(
						'FILE_ID' => $face['FILE_ID'],
						'FACE_ID' => $face['ID'],
						'FIRST_VISIT' => new \Bitrix\Main\Type\DateTime
					))->getId();

					if ($visitorId)
					{
						\Bitrix\Faceid\TrackingVisitsTable::registerVisit($visitorId);
						$visitor = \Bitrix\Faceid\TrackingVisitorsTable::getById($visitorId)->fetch();
					}
					else
					{
						var_dump('error while creating visitor');
					}
				}
				else
				{
					var_dump('local face not found');
				}
			}
		}
		elseif (!$response['result']['found'] && ($response['result']['msg'] == 'Unknown person' || $response['result']['msg'] == 'there is no photos for this portal'))
		{
			$currentTime = new \Bitrix\Main\Type\DateTime;

			$response = \Bitrix\FaceId\FaceId::add($fileContent);

			// get actual balance
			if (isset($response['status']['balance']))
			{
				$currentBalance = (int) $response['status']['balance'];
			}

			// save face locally
			$addResult = \Bitrix\Faceid\FaceTable::add(array('ID' => null));

			if (!empty($response['success']) && !empty($response['result']['added']))
			{
				$faceId = $response['result']['item']['face_id'];
				$fileId = $response['result']['item']['file_id'];

				// add visitor
				$visitorId = \Bitrix\Faceid\TrackingVisitorsTable::add(array(
					'FILE_ID' => $fileId,
					'FACE_ID' => $faceId,
					'FIRST_VISIT' => $currentTime
				))->getId();

				if ($visitorId)
				{
					\Bitrix\Faceid\TrackingVisitsTable::registerVisit($visitorId);
					$visitor = \Bitrix\Faceid\TrackingVisitorsTable::getById($visitorId)->fetch();
				}
				else
				{
					// visitor has not been added
				}
			}
			else
			{
				var_dump($addResult->getErrorMessages());
			}
		}
		else
		{
			// some error
			var_dump($response);
		}

		if (!empty($visitor))
		{
			$outputVisitor = \Bitrix\Faceid\TrackingVisitorsTable::toJson($visitor, $confidence, true);

			echo \Bitrix\Main\Web\Json::encode(array(
				'visitor' => $outputVisitor,
				'status' => array('balance' => $currentBalance)
			));

		}
	}
}

CMain::FinalActions();
