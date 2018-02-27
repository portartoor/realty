<?php

namespace Bitrix\ImOpenLines;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

use Bitrix\ImOpenlines\Security\Permissions;
use Bitrix\ImOpenlines\Security\Helper;

Loc::loadMessages(__FILE__);

class Operator
{
	private $chatId = 0;
	private $userId = 0;
	private $error = null;
	private $moduleLoad = false;

	public function __construct($chatId, $userId)
	{
		$imLoad = \Bitrix\Main\Loader::includeModule('im');
		$pullLoad = \Bitrix\Main\Loader::includeModule('pull');
		if ($imLoad && $pullLoad)
		{
			$this->error = new Error(null, '', '');
			$this->moduleLoad = true;
		}
		else
		{
			if (!$imLoad)
			{
				$this->error = new Error(__METHOD__, 'IM_LOAD_ERROR', Loc::getMessage('IMOL_OPERATOR_ERROR_IM_LOAD'));
			}
			elseif (!$pullLoad)
			{
				$this->error = new Error(__METHOD__, 'IM_LOAD_ERROR', Loc::getMessage('IMOL_OPERATOR_ERROR_PULL_LOAD'));
			}
		}

		$this->chatId = intval($chatId);
		$this->userId = intval($userId);
	}

	private function checkAccess()
	{
		if (!$this->moduleLoad)
		{
			return Array(
				'RESULT' => false
			);
		}

		if ($this->chatId <= 0)
		{
			$this->error = new Error(__METHOD__, 'CHAT_ID', Loc::getMessage('IMOL_OPERATOR_ERROR_CHAT_ID'));

			return Array(
				'RESULT' => false
			);
		}
		if ($this->userId <= 0)
		{
			$this->error = new Error(__METHOD__, 'USER_ID', Loc::getMessage('IMOL_OPERATOR_ERROR_USER_ID'));

			return Array(
				'RESULT' => false
			);
		}

		$orm = \Bitrix\Im\Model\RelationTable::getList(array(
			"select" => array("ID", "ENTITY_TYPE" => "CHAT.ENTITY_TYPE"),
			"filter" => array(
				"=CHAT_ID" => $this->chatId,
				"=USER_ID" => $this->userId,
			),
		));
		if ($relation = $orm->fetch())
		{
			if ($relation["ENTITY_TYPE"] != "LINES")
			{
				$this->error = new Error(__METHOD__, 'CHAT_TYPE', Loc::getMessage('IMOL_OPERATOR_ERROR_CHAT_TYPE'));

				return Array(
					'RESULT' => false
				);
			}
		}
		else
		{
			$this->error = new Error(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_OPERATOR_ERROR_ACCESS_DENIED'));

			return Array(
				'RESULT' => false
			);
		}

		return Array(
			'RESULT' => true
		);
	}

	public function answer()
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}
		
		$chat = new \CIMChat();
		$chat->SetReadMessage($this->chatId);

		$chat = new Chat($this->chatId);
		$chat->answer($this->userId);

		return true;
	}

	public function skip()
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->skip($this->userId);

		return true;
	}

	public function transfer(array $params)
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'] || empty($params['TRANSFER_ID']))
		{
			return false;
		}
		if ($this->userId == $params['TRANSFER_ID'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->transfer(Array(
			'FROM' => $this->userId,
			'TO' => $params['TRANSFER_ID']
		));

		return true;
	}

	public function setSilentMode($active = true)
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->setSilentMode($active);

		return true;
	}

	public function setPinMode($active = true)
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->setPauseFlag(Array(
			'ACTIVE' => $active
		));

		return true;
	}

	public function closeDialog()
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->finish();

		return true;
	}
	
	public function markSpam()
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->markSpamAndFinish($this->userId);

		return true;
	}

	public function createLead()
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$result = $chat->createLead();
		if ($result)
		{
			$this->error = new Error(__METHOD__, 'CREATE_ERROR', 'CREATE_ERROR');
		}
		
		return $result;
	}
	
	public function cancelCrmExtend($messageId)
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Tracker();
		return $chat->cancel($messageId);
	}
	
	public function changeCrmEntity($messageId, $entityType, $entityId)
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Tracker();
		return $chat->change($messageId, $entityType, $entityId);
	}

	public function joinChat($userCode)
	{
		if (\Bitrix\Im\User::getInstance($this->userId)->isExtranet())
			return false;

		$chat = new Chat();
		$result = $chat->load(Array(
			'USER_CODE' => $userCode,
			'ONLY_LOAD' => 'Y',
		));
		if ($result)
		{
			$configManager = new \Bitrix\ImOpenLines\Config();
			list($connectorId, $lineId) = explode('|', $userCode);
			if ($chat->getData('AUTHOR_ID') == 0)
			{
				$sessionField = $chat->getFieldData(Chat::FIELD_SESSION);
				if ($configManager->canJoin($lineId) || Crm::hasAccessToEntity($sessionField['CRM_ENTITY_TYPE'], $sessionField['CRM_ENTITY_ID']))
				{
					$chat->join($this->userId, false);
				}
				else
				{
					$result = false;
				}
			}
			else if ($chat->getData('AUTHOR_ID') != $this->userId)
			{
				$sessionField = $chat->getFieldData(Chat::FIELD_SESSION);
				if ($configManager->canJoin($lineId) || Crm::hasAccessToEntity($sessionField['CRM_ENTITY_TYPE'], $sessionField['CRM_ENTITY_ID']))
				{
					$chat->join($this->userId, false);
				}
				else
				{
					$result = false;
				}
			}
		}
		
		if ($result)
		{
			return $chat->getData();
		}
		else
		{
			$this->error = new Error(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_OPERATOR_ERROR_ACCESS_DENIED'));
			return false;
		}
	}
	
	public function voteAsHead($sessionId, $rating)
	{
		Session::voteAsHead($sessionId, $rating);

		return true;
	}
	
	public function startSessionByMessage($messageId)
	{
		$chat = new Chat($this->chatId);
		$chat->startSessionByMessage($this->userId, $messageId);

		return true;
	}
	
	public function getSessionHistory($sessionId)
	{
		
		$sessionId = intval($sessionId);
		if ($sessionId <= 0)
		{
			$this->error = new Error(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_OPERATOR_ERROR_ACCESS_DENIED'));
			return false;
		}
		
		$orm = Model\SessionTable::getByIdPerformance($sessionId);
		$session = $orm->fetch();
		if (!$session)
		{
			$this->error = new Error(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_OPERATOR_ERROR_ACCESS_DENIED'));
			return false;
		}
		
		if ($session['OPERATOR_ID'] != $this->userId && !isset($session[$this->userId]))
		{
			$permission = Permissions::createWithCurrentUser();
			$allowedUserIds = Helper::getAllowedUserIds(
				Helper::getCurrentUserId(),
				$permission->getPermission(Permissions::ENTITY_HISTORY, Permissions::ACTION_VIEW)
			);
			if (is_array($allowedUserIds) && !in_array($session['OPERATOR_ID'], $allowedUserIds) && 
				!Crm::hasAccessToEntity($session['CRM_ENTITY_TYPE'], $session['CRM_ENTITY_ID'])
			)
			{
				$this->error = new Error(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_OPERATOR_ERROR_ACCESS_DENIED'));
				return false;
			}
		}

		$chatId = $session['CHAT_ID'];

		$CIMChat = new \CIMChat();
		$result = $CIMChat->GetLastMessageLimit($chatId, $session['START_ID'], $session['END_ID'], true, false);
		if ($result && isset($result['message']))
		{
			foreach ($result['message'] as $id => $ar)
				$result['message'][$id]['recipientId'] = 'chat'.$ar['recipientId'];

			$result['usersMessage']['chat'.$chatId] = $result['usersMessage'][$chatId];
			unset($result['usersMessage'][$chatId]);
		}
		else
		{
			$this->error = new Error(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_OPERATOR_ERROR_ACCESS_DENIED'));
			return false;
		}
		
		$configManager = new \Bitrix\ImOpenLines\Config();
		$result['sessionId'] = $sessionId;
		$result['canJoin'] = $configManager->canJoin($session['CONFIG_ID'])? 'Y':'N';
		$result['canVoteAsHead'] = $configManager->canVoteAsHead($session['CONFIG_ID'])? 'Y':'N';
		$result['sessionVoteHead'] = intval($session['VOTE_HEAD']);

		return $result;
	}

	public function getError()
	{
		return $this->error;
	}
}