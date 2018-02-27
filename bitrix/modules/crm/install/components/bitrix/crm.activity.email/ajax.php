<?php

use Bitrix\Main\Localization\Loc;

define('PUBLIC_AJAX_MODE', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loc::loadMessages(__DIR__.'/class.php');

class CrmActivityEmailAjax
{

	static $crmAvailable   = false;
	static $limitedLicense = false;

	public static function execute()
	{
		global $USER;

		$result = array();
		$error  = false;

		if (!is_object($USER) || !$USER->isAuthorized())
			$error = getMessage('CRM_ACT_EMAIL_AUTH');

		if ($error === false)
		{
			if (!CModule::includeModule('crm'))
				$error = getMessage('CRM_ACT_EMAIL_NOCRM');
		}

		if ($error === false)
		{
			$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : null;

			switch ($act)
			{
				case 'log':
					$result = (array) self::executeLog($error);
					break;
				case 'logitem':
					$result = (array) self::executeLogItem($error);
					break;
				default:
					$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');
			}
		}

		self::returnJson(array_merge(array(
			'result' => $error === false ? 'ok' : 'error',
			'error'  => $error
		), $result));
	}

	private static function executeLog(&$error)
	{
		$error = false;

		$itemId = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : false;
		if (!$itemId)
			$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');

		if ($error === false)
		{
			$params = !empty($_REQUEST['log']) ? $_REQUEST['log'] : false;
			if (!empty($params) && preg_match('/([ab])(\d+)/i', $params, $matches))
			{
				$type = strtolower($matches[1]);
				$page = (int) $matches[2];

				if ($page < 2)
					$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');
			}
			else
			{
				$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');
			}
		}

		if ($error === false)
		{
			$activity = \CCrmActivity::getList(
				array(),
				array('=ID' => $itemId),
				false, false,
				array('ID', 'THREAD_ID', 'START_TIME')
			)->fetch();

			if (empty($activity))
				$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			$filter = array('!ID' => $activity['ID'], 'THREAD_ID' => $activity['THREAD_ID']);

			if ($type == 'a')
			{
				$filter['>START_TIME'] = $activity['START_TIME'];
				$order = array('START_TIME' => 'ASC', 'ID' => 'ASC');
			}
			else
			{
				$filter['<=START_TIME'] = $activity['START_TIME'];
				$order = array('START_TIME' => 'DESC', 'ID' => 'DESC');
			}

			$pageSize = !empty($_REQUEST['size']) ? (int) $_REQUEST['size'] : 5;
			$res = \CCrmActivity::getList(
				$order, $filter, false, false,
				array('ID', 'SUBJECT', 'START_TIME', 'DIRECTION', 'COMPLETED', 'AUTHOR_ID', 'RESPONSIBLE_ID'),
				array('QUERY_OPTIONS' => array('OFFSET' => ($page-1)*$pageSize, 'LIMIT' => $pageSize))
			);

			$actIds  = array();
			$authIds = array();

			$log = array();
			while ($item = $res->fetch())
			{
				$log[] = $item;

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
		}

		if (!empty($log))
		{
			if ($type == 'a')
				$log = array_reverse($log);

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

			$authors = array();

			if (!empty($authIds))
			{
				$res = \Bitrix\Main\UserTable::getList(array(
					'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO'),
					'filter' => array('=ID' => array_unique($authIds)),
				));

				$nameFormat = \CSite::getNameFormat(null, $_REQUEST['site_id'] ?: '');
				while ($item = $res->fetch())
				{
					$item['NAME_FORMATTED'] = \CUser::formatName($nameFormat, $item, true);
					$authors[$item['ID']] = $item;
				}
			}

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

				$log[$i] = $item;
			}

			ob_start();

			foreach ($log as $item)
			{
				if ($type == 'b'): ?>
				<div class="crm-task-list-mail-item-separator" style="display: none; "></div>
				<? endif ?>
				<div class="crm-task-list-mail-item" id="crm-activity-email-log-<?=intval($item['ID']) ?>" data-id="<?=intval($item['ID']) ?>">
					<span class="crm-task-list-mail-item-icon-reply-<?=($item['DIRECTION'] == \CCrmActivityDirection::Incoming ? 'incoming' : 'coming') ?>"></span>
					<span class="crm-task-list-mail-item-icon <? if ($item['COMPLETED'] != 'Y'): ?>active-mail<? endif ?>"></span>
					<span class="crm-task-list-mail-item-user"
						<? if (!empty($item['LOG_IMAGE'])): ?>style="background: url('<?=$item['LOG_IMAGE'] ?>'); background-size: 23px 23px; "<? endif ?>>
						</span>
					<span class="crm-task-list-mail-item-name"><?=$item['LOG_TITLE'] ?></span>
					<span class="crm-task-list-mail-item-description"><?=$item['SUBJECT'] ?></span>
					<span class="crm-task-list-mail-item-date"><?=formatDate('x', makeTimeStamp($item['START_TIME']), time()+\CTimeZone::getOffset()) ?></span>
				</div>
				<div class="crm-task-list-mail-item-inner crm-activity-email-details-<?=intval($item['ID']) ?>"
					style="display: none; text-align: center; " data-empty="1">
					<span class="crm-task-list-mail-item-loading"></span>
				</div>
				<? if ($type == 'a'): ?>
				<div class="crm-task-list-mail-item-separator" style="display: none; "></div>
				<? endif;
			}

			$html = ob_get_clean();

			return array('html' => $html, 'count' => count($log));
		}

		return array('html' => '', 'count' => 0);
	}

	private static function executeLogItem(&$error)
	{
		$error = false;

		$itemId = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : false;
		if (!$itemId)
			$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');

		if ($error === false)
		{
			$activity = \CCrmActivity::getByID($itemId);
			if (empty($activity))
				$error = getMessage('CRM_ACT_EMAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			switch ((int) $activity['DESCRIPTION_TYPE'])
			{
				case \CCrmContentType::BBCode:
					$parser = new CTextParser();
					$actDescr = $parser->convertText($activity['DESCRIPTION']);
					break;
				case \CCrmContentType::Html:
					$actDescr = $activity['DESCRIPTION'];
					break;
				default:
					$actDescr = preg_replace(
						'/[\r\n]+/'.BX_UTF_PCRE_MODIFIER, '<br>',
						htmlspecialcharsbx($activity['DESCRIPTION'])
					);
			}

			$res = \Bitrix\Main\UserTable::getList(array(
				'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO'),
				'filter' => array('=ID' => array($activity['AUTHOR_ID'], $activity['RESPONSIBLE_ID'])),
			));

			$nameFormat = \CSite::getNameFormat(null, $_REQUEST['site_id'] ?: '');
			while ($author = $res->fetch())
			{
				$author['NAME_FORMATTED'] = \CUser::formatName($nameFormat, $author, true);

				if ($author['ID'] == $activity['AUTHOR_ID'])
					break;
			}

			$preview = \CFile::resizeImageGet(
				$author['PERSONAL_PHOTO'], array('width' => 38, 'height' => 38),
				BX_RESIZE_IMAGE_EXACT, false
			);

			$author['IMAGE_URL'] = $preview['src'];

			$entityTypes = array(
				'\CCrmContact' => \CCrmOwnerType::Contact,
				'\CCrmCompany' => \CCrmOwnerType::Company,
			);
			$activity['COMMUNICATIONS'] = array();
			foreach (\CCrmActivity::getCommunications($activity['ID']) as $item)
			{
				\CCrmActivity::prepareCommunicationInfo($item);

				if (empty($activity['COMMUNICATIONS']) || \CCrmActivityDirection::Outgoing == $activity['DIRECTION'])
				{
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
							$preview = \CFile::resizeImageGet(
								$entity['PHOTO'] ?: $entity['LOGO'],
								array('width' => 38, 'height' => 38),
								BX_RESIZE_IMAGE_EXACT, false
							);
							$item['IMAGE_URL'] = !empty($preview['src']) ? $preview['src'] : '';
						}
					}
				}

				$activity['COMMUNICATIONS'][] = $item;
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

			ob_start();

			?>
			<div class="crm-task-list-mail-item-inner-header crm-task-list-mail-item-inner-header-clickable crm-task-list-mail-item-open">
				<span class="crm-task-list-mail-item-inner-user"
					<? if (!empty($activity['ITEM_IMAGE'])): ?>style="background: url('<?=$activity['ITEM_IMAGE'] ?>'); background-size: 40px 40px; "<? endif ?>>
					</span>
				<span class="crm-task-list-mail-item-inner-user-container">
					<span class="crm-task-list-mail-item-inner-user-info">
						<span class="crm-task-list-mail-item-inner-user-title"><?=$activity['ITEM_FROM_TITLE'] ?></span>
						<? if (!empty($activity['ITEM_FROM_EMAIL'])): ?>
							<span class="crm-task-list-mail-item-inner-user-mail"><?=$activity['ITEM_FROM_EMAIL'] ?></span>
						<? endif ?>
						<div class="crm-task-list-mail-item-inner-send">
							<span class="crm-task-list-mail-item-inner-send-item"><?=getMessage('CRM_ACT_EMAIL_RCPT') ?>:</span>
							<? foreach ($activity['ITEM_TO'] as $item): ?>
								<span class="crm-task-list-mail-item-inner-send-user"
									<? if (!empty($item['IMAGE'])): ?>style="background: url('<?=$item['IMAGE'] ?>'); background-size: 23px 23px; "<? endif ?>>
									</span>
								<span class="crm-task-list-mail-item-inner-send-mail"><?=$item['TITLE'] ?></span>
							<? endforeach ?>
						</div>
					</span>
				</span>
				<span class="crm-task-list-mail-item-date crm-activity-email-details-hide" style="margin-top: -8px; margin-right: -11px; "></span>
			</div>
			<div class="crm-task-list-mail-item-inner-body"><?=$actDescr ?></div>
			<?

			$html = ob_get_clean();

			return array('html' => $html);
		}
	}

	private static function returnJson($data)
	{
		global $APPLICATION;

		$APPLICATION->restartBuffer();

		header('Content-Type: application/x-javascript; charset=UTF-8');
		echo \Bitrix\Main\Web\Json::encode($data);
	}

}

CrmActivityEmailAjax::execute();

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php';
