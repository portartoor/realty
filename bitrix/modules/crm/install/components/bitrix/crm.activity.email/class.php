<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CrmActivityEmailComponent extends CBitrixComponent
{

	public function executeComponent()
	{
		global $APPLICATION;

		$activity = $this->arParams['ACTIVITY'];
		if (empty($activity))
			return;

		$pageSize = (int) $this->arParams['PAGE_SIZE'];
		if ($pageSize < 1 || $pageSize > 100)
			$this->arParams['PAGE_SIZE'] = ($pageSize = 5);

		$actIds  = array();
		$authIds = array($activity['AUTHOR_ID'], $activity['RESPONSIBLE_ID']);

		$res = \CCrmActivity::getList(
			array(
				'START_TIME' => 'DESC',
				'ID'         => 'DESC',
			),
			array(
				'!ID'          => $activity['ID'],
				'THREAD_ID'    => $activity['THREAD_ID'],
				'<=START_TIME' => $activity['START_TIME'],
			),
			false, false,
			array('ID', 'SUBJECT', 'START_TIME', 'DIRECTION', 'COMPLETED', 'AUTHOR_ID', 'RESPONSIBLE_ID'),
			array('QUERY_OPTIONS' => array('OFFSET' => 0, 'LIMIT' => $pageSize))
		);

		$this->arResult['LOG']['B'] = array();
		while ($item = $res->fetch())
		{
			$this->arResult['LOG']['B'][] = $item;

			if ($item['DIRECTION'] == \CCrmActivityDirection::Incoming)
			{
				$actIds[] = $item['ID'];
			}
			else
			{
				$authIds[] = $item['AUTHOR_ID'];
				$authIds[] = $item['RESPONSIBLE_ID'];
			}
		}

		$res = \CCrmActivity::getList(
			array(
				'START_TIME' => 'ASC',
				'ID'         => 'ASC',
			),
			array(
				'!ID'         => $activity['ID'],
				'THREAD_ID'   => $activity['THREAD_ID'],
				'>START_TIME' => $activity['START_TIME'],
			),
			false, false,
			array('ID', 'SUBJECT', 'START_TIME', 'DIRECTION', 'COMPLETED', 'AUTHOR_ID', 'RESPONSIBLE_ID'),
			array('QUERY_OPTIONS' => array('OFFSET' => 0, 'LIMIT' => $pageSize))
		);

		$this->arResult['LOG']['A'] = array();
		while ($item = $res->fetch())
		{
			$this->arResult['LOG']['A'][] = $item;

			if ($item['DIRECTION'] == \CCrmActivityDirection::Incoming)
			{
				$actIds[] = $item['ID'];
			}
			else
			{
				$authIds[] = $item['AUTHOR_ID'];
				$authIds[] = $item['RESPONSIBLE_ID'];
			}
		}

		$this->arResult['LOG']['A'] = array_reverse($this->arResult['LOG']['A']);

		$clients = array();

		if (!empty($actIds))
		{
			$res = \CCrmActivity::getCommunicationList(
				array('ID' => 'ASC'),
				array('ACTIVITY_ID' => $actIds),
				false, false,
				array()
			);

			while ($item = $res->fetch())
			{
				if (array_key_exists($item['ACTIVITY_ID'], $clients))
					continue; 

				\CCrmActivity::prepareCommunicationInfo($item);

				$entityTypes = array(
					'\CCrmContact' => \CCrmOwnerType::Contact,
					'\CCrmCompany' => \CCrmOwnerType::Company,
				);
				if ($entityClass = array_search($item['ENTITY_TYPE_ID'], $entityTypes))
				{
					$entity = $entityClass::getListEx(
						array(),
						array('ID' => $item['ENTITY_ID']),
						false, false,
						array('PHOTO', 'LOGO')
					)->fetch();

					if (!empty($entity) and $entity['PHOTO'] > 0 || $entity['LOGO'] > 0)
					{
						$fileInfo = \CFile::resizeImageGet(
							$entity['PHOTO'] ?: $entity['LOGO'],
							array('width' => 38, 'height' => 38),
							BX_RESIZE_IMAGE_EXACT, false
						);
						$item['IMAGE_URL'] = !empty($fileInfo['src']) ? $fileInfo['src'] : '';
					}
				}

				$clients[$item['ACTIVITY_ID']] = $item;
			}
		}

		$res = \Bitrix\Main\UserTable::getList(array(
			'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO'),
			'filter' => array('=ID' => array_unique($authIds)),
		));

		$authors = array();
		$nameFormat = \CSite::getNameFormat(null);
		while ($item = $res->fetch())
		{
			$item['NAME_FORMATTED'] = \CUser::formatName($nameFormat, $item, true);

			$authors[$item['ID']] = $item;
		}

		foreach ($this->arResult['LOG'] as $k => $log)
		{
			foreach ($log as $i => $item)
			{
				if ($item['DIRECTION'] == \CCrmActivityDirection::Incoming)
				{
					$item['LOG_TITLE'] = $clients[$item['ID']]['TITLE'];
					$item['LOG_IMAGE'] = $clients[$item['ID']]['IMAGE_URL'];
				}
				else
				{
					$authorId = !empty($authors[$item['AUTHOR_ID']]) ? $item['AUTHOR_ID'] : $item['RESPONSIBLE_ID'];

					if (!array_key_exists('IMAGE_URL', $authors[$authorId]))
					{
						$preview = \CFile::resizeImageGet(
							$authors[$authorId]['PERSONAL_PHOTO'], array('width' => 38, 'height' => 38),
							BX_RESIZE_IMAGE_EXACT, false
						);

						$authors[$authorId]['IMAGE_URL'] = $preview['src'];
					}

					$item['LOG_TITLE'] = $authors[$authorId]['NAME_FORMATTED'];
					$item['LOG_IMAGE'] = $authors[$authorId]['IMAGE_URL'];
				}

				$this->arResult['LOG'][$k][$i] = $item;
			}
		}

		$author = !empty($authors[$activity['AUTHOR_ID']])
			? $authors[$activity['AUTHOR_ID']]
			: $authors[$activity['RESPONSIBLE_ID']];

		if (!array_key_exists('IMAGE_URL', $author))
		{
			$preview = \CFile::resizeImageGet(
				$author['PERSONAL_PHOTO'], array('width' => 38, 'height' => 38),
				BX_RESIZE_IMAGE_EXACT, false
			);

			$author['IMAGE_URL'] = $preview['src'];
		}

		if (\CCrmActivityDirection::Incoming == $activity['DIRECTION'])
		{
			$item = reset($activity['COMMUNICATIONS']);

			$activity['ITEM_IMAGE'] = $item['IMAGE_URL'];

			$activity['ITEM_FROM_TITLE'] = $item['TITLE'];
			$activity['ITEM_FROM_EMAIL'] = $item['VALUE'];

			$activity['ITEM_TO'] = array(array(
				'IMAGE' => $author['IMAGE_URL'],
				'TITLE' => $author['NAME_FORMATTED'],
			));
		}
		else
		{
			$activity['ITEM_IMAGE'] = $author['IMAGE_URL'];

			$activity['ITEM_FROM_TITLE'] = $author['NAME_FORMATTED'];
			$activity['ITEM_FROM_EMAIL'] = null;

			$activity['ITEM_TO'] = array();
			foreach ($activity['COMMUNICATIONS'] as $item)
			{
				$activity['ITEM_TO'][] = array(
					'IMAGE' => $item['IMAGE_URL'],
					'TITLE' => $item['VALUE'],
				);
			}
		}

		$this->arParams['ACTIVITY'] = $activity;

		$this->includeComponentTemplate();
	}

}
