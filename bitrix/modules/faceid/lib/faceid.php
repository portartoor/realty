<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2016 Bitrix
 */

namespace Bitrix\FaceId;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * @package    bitrix
 * @subpackage faceid
 */
class FaceId
{
	public static function insertIntoCrmMenu(&$items)
	{
		$newItems = array();

		foreach ($items as $item)
		{
			if ($item['ID'] == 'EVENT')
			{
				$newItems[] = array(
					'ID' => 'FACETRACKER',
					'MENU_ID' => 'menu_crm_facetracker',
					'NAME' => Loc::getMessage('FACEID_TRACKER'),
					'TITLE' => Loc::getMessage('FACEID_TRACKER'),
					'URL' => '/crm/face-tracker/',
					'ICON' => 'settings'
				);
			}

			$newItems[] = $item;
		}

		$items = $newItems;
	}

	/**
	 * @param string $binaryImageContent in JPEG format
	 *
	 * @return array [success => bool, result => [found => bool, items => array(face_id, confidence)]]
	 */
	public static function identify($binaryImageContent)
	{
		$handler = new Http;

		$response = $handler->query("identify", array(
			'image' => base64_encode($binaryImageContent)
		));

		$result = array('found' => false, 'msg' => '');
		if ($response['success'])
		{
			$result = $response['result'];
		}

		// update balance
		if (isset($response['status']['balance']))
		{
			$currentBalance = (int) $response['status']['balance'];
			\Bitrix\Main\Config\Option::set('faceid', 'balance', $currentBalance);
		}

		// continue with faces
		if ($result['found'])
		{
			$newItems = array();

			foreach ($result['items'] as $item)
			{
				$newItem = array();

				// face id
				$meta = explode(':', $item['meta']);
				$newItem['face_id'] = intval($meta[1]);

				// confidence
				$newItem['confidence'] = $item['confidence'];

				$newItems[] = $newItem;
			}

			$response['result']['items'] = $newItems;
		}

		return $response;
	}

	/**
	 * @param string $binaryImageContent in JPEG format
	 *
	 * @return array [success => bool, result => [added => bool, item => array(face_id, file_id)]]
	 */
	public static function add($binaryImageContent)
	{
		// save face locally
		$response = array('success' => false, 'msg' => 'unknown');

		$addResult = FaceTable::add(array('ID' => null));

		if ($addResult->isSuccess())
		{
			$faceId = $addResult->getId();
			$handler = new Http;

			$response = $handler->query("add", array(
				'image' => base64_encode($binaryImageContent),
				'meta' => 'faceid:'.$faceId
			));

			$result = array('added' => false, 'msg' => '');
			if ($response['success'])
			{
				$result = $response['result'];
			}

			// update balance
			if (isset($response['status']['balance']))
			{
				$currentBalance = (int) $response['status']['balance'];
				\Bitrix\Main\Config\Option::set('faceid', 'balance', $currentBalance);
			}

			if ($result['added'])
			{
				// save photo locally
				$fileId = \CFile::SaveFile(array(
					'MODULE_ID' => 'faceid',
					'name' => 'face_'.$faceId.'.jpg',
					'type' => 'image/jpeg',
					'content' => $binaryImageContent
				), 'faceid');

				// update face with fileID and cloudID
				FaceTable::update($faceId, array(
					'FILE_ID' => $fileId,
					'CLOUD_FACE_ID' => $result['face_id']
				))->getId();

				$response['result']['item'] = array(
					'face_id' => $faceId,
					'file_id' => $fileId
				);

				// not to confuse with local face id
				unset($response['result']['face_id']);
			}
			else
			{
				var_dump($response);
			}
		}
		else
		{
			var_dump($addResult->getErrorMessages());
		}

		return $response;
	}

	public static function identifyVk($binaryImageContent)
	{
		$handler = new \Bitrix\FaceId\Http;

		$response = $handler->query("identify_vk", array(
			'image' => base64_encode($binaryImageContent)
		));

		// update balance
		if (isset($response['status']['balance']))
		{
			$currentBalance = (int) $response['status']['balance'];
			\Bitrix\Main\Config\Option::set('faceid', 'balance', $currentBalance);
		}

		return $response;
	}

	public static function getBalance()
	{
		$handler = new \Bitrix\FaceId\Http;

		$response = $handler->query("balance");

		// update balance
		if (isset($response['status']['balance']))
		{
			$currentBalance = (int) $response['status']['balance'];
			\Bitrix\Main\Config\Option::set('faceid', 'balance', $currentBalance);
		}

		return $response;
	}
}
