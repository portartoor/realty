<?php

namespace Bitrix\ImOpenLines;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Session
{
	private $error = null;
	private $config = Array();
	private $session = Array();
	private $connectorId = '';

	/* @var \Bitrix\ImOpenLines\Chat */
	public $chat = null;

	private $action = 'none';
	public $joinUserId = 0;
	public $joinUserList = Array();
	private $isCreated = false;

	const RULE_TEXT = 'text';
	const RULE_FORM = 'form';
	const RULE_QUEUE = 'queue';
	const RULE_NONE = 'none';

	const CRM_CREATE_LEAD = 'lead';
	const CRM_CREATE_NONE = 'none';

	const ACTION_WELCOME = 'welcome';
	const ACTION_WORKTIME = 'worktime';
	const ACTION_NO_ANSWER = 'no_answer';
	const ACTION_CLOSED = 'closed';
	const ACTION_NONE = 'none';

	const MODE_INPUT = 'input';
	const MODE_OUTPUT = 'output';

	const CACHE_QUEUE = 'queue';
	const CACHE_CLOSE = 'close';
	const CACHE_MAIL = 'mail';
	const CACHE_INIT = 'init';

	const ORM_SAVE = 'save';
	const ORM_GET = 'get';

	public function __construct($config = array())
	{
		$this->error = new Error(null, '', '');
		$this->config = $config;

		\Bitrix\Main\Loader::includeModule('im');
	}

	public function loadByArray($session, $config, $chat)
	{
		$this->session = $session;
		$this->config = $config;
		$this->chat = $chat;
		$this->connectorId = $session['SOURCE'];
	}

	public function load($params)
	{
		list($connectorId, $lineId, $chatId, $userId) = explode('|', $params['USER_CODE']);
		
		$params['CONFIG_ID'] = $lineId;
		$params['USER_ID'] = $userId;
		$params['SOURCE'] = $connectorId;
		$params['CHAT_ID'] = $chatId;
		
		if (\Bitrix\ImOpenLines\Connector::isEnableGroupByChat($connectorId))
		{
			$params['USER_CODE'] = $connectorId.'|'.$lineId.'|'.$chatId.'|0';
		}

		return $this->start($params);
	}

	public function start($params)
	{
		$this->connectorId = $params['SOURCE'];
		
		$fields['USER_CODE'] = $params['USER_CODE'];
		$fields['CONFIG_ID'] = intval($params['CONFIG_ID']);
		$fields['USER_ID'] = intval($params['USER_ID']);
		$fields['OPERATOR_ID'] = intval($params['OPERATOR_ID']);
		$fields['SOURCE'] = $params['SOURCE'];
		$fields['MODE'] =  $params['MODE'] == self::MODE_OUTPUT? self::MODE_OUTPUT: self::MODE_INPUT;
		$params['DEFERRED_JOIN'] =  $params['DEFERRED_JOIN'] == 'Y'? 'Y': 'N';
		$params['SKIP_CREATE'] =  $params['SKIP_CREATE'] == 'Y'? 'Y': 'N';
		
		$result = false;
		
		$select = \Bitrix\ImOpenLines\Model\SessionTable::getSelectFieldsPerformance();
		$select['CHECK_DATE_CLOSE'] = 'CHECK.DATE_CLOSE';
		$select['CHECK_DATE_QUEUE'] = 'CHECK.DATE_QUEUE';
		
		$orm = Model\SessionTable::getList(array(
			'select' => $select,
			'filter' => array(
				'=USER_CODE' => $fields['USER_CODE'],
				'=CLOSED' => 'N'
			),
			'order' => array('ID' => 'DESC')
		));
		while($row = $orm->fetch())
		{
			if (!$result)
			{
				$result = $row;
			}
			else
			{
				$chatDuplicate = new Chat($row['CHAT_ID']);
				$chatDuplicate->close();
				
				Model\SessionTable::update($row['ID'], Array('CLOSED' => 'Y'));
				Model\SessionCheckTable::delete($row['ID']);
				
				$updateDuplicate['CLOSED'] = new \Bitrix\Main\DB\SqlExpression("?# + 1", "CLOSED");
				$updateDuplicate['IN_WORK'] = new \Bitrix\Main\DB\SqlExpression("?# - 1", "IN_WORK");
				Model\ConfigStatisticTable::update($row['CONFIG_ID'], $updateDuplicate);
			}
		}
		
		if (!$result && $params['VOTE_SESSION'] == 'Y')
		{
			$select = \Bitrix\ImOpenLines\Model\SessionTable::getSelectFieldsPerformance();
			$select['CHECK_DATE_CLOSE'] = 'CHECK.DATE_CLOSE';
			$select['CHECK_DATE_QUEUE'] = 'CHECK.DATE_QUEUE';
			
			$orm = Model\SessionTable::getList(array(
				'select' => $select,
				'filter' => array(
					'=USER_CODE' => $fields['USER_CODE'],
					'=CLOSED' => 'Y',
				),
				'order' => array('ID' => 'DESC')
			));
			$result = $orm->fetch();
		
			if (!$result || $result['WAIT_VOTE'] != 'Y')
			{
				$result = false;
			}
		}
		
		if ($result)
		{
			$result['SESSION_ID'] = $result['ID'];
			$this->session = $result;
			
			if (!$this->config)
			{
				$configManager = new Config();
				$this->config = $configManager->get($this->session['CONFIG_ID']);
				if (!$this->config)
				{
					return false;
				}
			}
			
			$fields['CONFIG_ID'] = $this->session['CONFIG_ID'];
			
			$this->chat = new Chat($this->session['CHAT_ID']);
			
			if ($params['VOTE_SESSION'] == 'Y' && $result['CLOSED'] == 'Y')
			{
				$arMessageFields = array(
					"MESSAGE_TYPE" => "C",
					"SYSTEM" => "Y",
					"TO_CHAT_ID" => $this->session['CHAT_ID'],
					"MESSAGE" => Loc::getMessage('IMOL_SESSION_REOPEN_SESSION', Array("#LINK#" => "[URL=/online/?IM_HISTORY=imol|".$this->session['SESSION_ID']."]".$this->session['SESSION_ID']."[/URL]")),
					"PARAMS" => Array(
						"CLASS" => "bx-messenger-content-item-ol-start"
					),
					"RECENT_ADD" => 'N'
				);
				\CIMMessenger::Add($arMessageFields);
				
				$updateStatisticTable['CLOSED'] = new \Bitrix\Main\DB\SqlExpression("?# - 1", "CLOSED");
				$updateStatisticTable['IN_WORK'] = new \Bitrix\Main\DB\SqlExpression("?# + 1", "IN_WORK");
				Model\ConfigStatisticTable::update($this->session['CONFIG_ID'], $updateStatisticTable);
				
				$dateClose = new \Bitrix\Main\Type\DateTime();
				$dateClose->add('10 MINUTES');
				
				$this->session['END_ID'] = 0;
				$this->session['CLOSED'] = 'N';
				$this->session['WAIT_ANSWER'] = 'N';
				$this->session['PAUSE'] = 'N';
				
				Model\SessionTable::update($result['ID'], Array('CLOSED' => 'N', 'WAIT_ANSWER' => 'N', 'PAUSE' => 'N', 'END_ID' => 0));
				Model\SessionCheckTable::add(Array(
					'SESSION_ID' => $this->session['SESSION_ID'],
					'DATE_CLOSE' => $dateClose
				));
				
				$this->chat->join($this->session['OPERATOR_ID'], true, true);
				$this->chat->update(Array('AUTHOR_ID' => $this->session['OPERATOR_ID']));
				
				$this->chat->updateFieldData(Chat::FIELD_SESSION, Array(
					'ID' => $this->session['SESSION_ID'],
					'PAUSE' => 'N',
					'WAIT_ANSWER' => 'N'
				));
			}
			else if (!$this->chat->isNowCreated())
			{
				$this->chat->join($fields['USER_ID']);
			}
			
			return true;
		}
		
		if (!$this->config)
		{
			$configManager = new Config();
			$this->config = $configManager->get($fields['CONFIG_ID']);
			if (!$this->config)
			{
				return false;
			}
		}
		
		if ($params['SKIP_CREATE'] == 'Y')
		{
			return false;
		}

		$this->chat = new Chat();
		$result = $this->chat->load(Array(
			'USER_CODE' => $fields['USER_CODE'],
			'USER_ID' => $fields['USER_ID'],
			'LINE_NAME' => $this->config['LINE_NAME'],
			'CONNECTOR' => $params['CONNECTOR'],
		));
		if (!$result)
		{
			return false;
		}
		
		$fields['CHAT_ID'] = $this->chat->getData('ID');
		if (!$this->chat->isNowCreated())
		{
			$fields['START_ID'] = $this->chat->getData('LAST_MESSAGE_ID')+1;
			$this->chat->join($fields['USER_ID']);
		}
		
		$orm = Model\SessionTable::add($fields);
		if (!$orm->isSuccess())
		{
			return false;
		}
		$this->isCreated = true;
		$fields['SESSION_ID'] = $orm->getId();
		
		$arMessageFields = array(
			"MESSAGE_TYPE" => "C",
			"SYSTEM" => "Y",
			"TO_CHAT_ID" => $fields['CHAT_ID'],
			"MESSAGE" => Loc::getMessage('IMOL_SESSION_START_SESSION', Array("#LINK#" => "[URL=/online/?IM_HISTORY=imol|".$fields['SESSION_ID']."]".$fields['SESSION_ID']."[/URL]")),
			"PARAMS" => Array(
				"CLASS" => "bx-messenger-content-item-ol-start"
			)
		);
		\CIMMessenger::Add($arMessageFields);
		
		$this->chat->updateFieldData(Chat::FIELD_SESSION, Array(
			'ID' => $fields['SESSION_ID'],
		));
		
		if ($fields['MODE'] == self::MODE_INPUT)
		{
			$this->session['JOIN_BOT'] = false;
			if ($this->config['WELCOME_BOT_ENABLE'] == 'Y' && $this->config['WELCOME_BOT_ID'] > 0)
			{
				if ($this->config['WELCOME_BOT_JOIN'] == \Bitrix\ImOpenLines\Config::BOT_JOIN_ALWAYS)
				{
					//$this->chat->setUserIdForJoin($fields['USER_ID']);
					$this->session['JOIN_BOT'] = true;
				}
				else if ($this->chat->isNowCreated())
				{
					//$this->chat->setUserIdForJoin($fields['USER_ID']);
					$this->session['JOIN_BOT'] = true;
				}
			}
			else if ($this->chat->isNowCreated())
			{
				$this->action = self::ACTION_WELCOME;
			}

			/* QUEUE BLOCK */
			
			if ($this->config['QUEUE_TYPE'] == Config::QUEUE_TYPE_ALL)
			{
				$queue = $this->getQueue();
				$fields['QUEUE_HISTORY'] = Array(); 
				$params['USER_LIST'] = $queue['USER_LIST']; 
				
				$queue = $this->getNextInQueue();
				$fields['OPERATOR_ID'] = $queue['RESULT'] ? $queue['USER_ID'] : $queue['FIRST_IN_QUEUE'];
			}
			else
			{
				$queue = $this->getNextInQueue();
				$fields['OPERATOR_ID'] = $queue['RESULT'] ? $queue['USER_ID'] : $queue['FIRST_IN_QUEUE'];
				if (isset($this->session['QUEUE_HISTORY']))
				{
					$fields['QUEUE_HISTORY'] = $this->session['QUEUE_HISTORY'];
				}
			}

			/* CRM BLOCK */
			if (!Connector::isEnableGroupByChat($fields['SOURCE']) && $this->config['CRM'] == 'Y' && \IsModuleInstalled('crm'))
			{
				$crmFields = array_merge($fields, Array('CRM_TITLE' => $this->chat->getData('TITLE')));
				
				$result = $this->updateCrm($crmFields);
				$fields = array_merge($fields, $result);
				
				$this->chat->setCrmFlag(Array(
					'ACTIVE' => $fields['CRM'],
					'ENTITY_TYPE' => $fields['CRM_ENTITY_TYPE'],
					'ENTITY_ID' => $fields['CRM_ENTITY_ID'],
				));
			}
			else
			{
				$this->chat->setCrmFlag(Array(
					'ACTIVE' => 'N',
					'ENTITY_TYPE' => 'NONE',
					'ENTITY_ID' => 0,
				));
			}

			/* CLOSED LINE */
			if ($this->config['ACTIVE'] == 'N')
			{
				$this->session['JOIN_BOT'] = false;
				$this->action = self::ACTION_CLOSED;
				$fields['WORKTIME'] = 'N';
				$fields['WAIT_ACTION'] = 'Y';
				$this->chat->update(Array('AUTHOR_ID' => $fields['OPERATOR_ID']));
			}
			/* WORKTIME BLOCK */
			else if ($this->checkWorkTime())
			{
				/* NO ANSWER BLOCK */
				if (!$queue['RESULT'] && !$this->session['JOIN_BOT'])
				{
					if ($this->startNoAnswerRule())
					{
						$fields['WAIT_ACTION'] = 'Y';
					}
				}
			}
			else
			{
				$fields['WORKTIME'] = 'N';
				if ($this->session['JOIN_BOT'])
				{
					$this->action = self::ACTION_NONE;
				}
				else
				{
					$fields['WAIT_ACTION'] = 'Y';
				}
			}

			if ($this->session['JOIN_BOT'])
			{
				$queue['RESULT'] = true;
				$fields['OPERATOR_ID'] = $this->config['WELCOME_BOT_ID'];
			}
			else if ($fields['OPERATOR_ID'])
			{
				$fields['DATE_OPERATOR'] = new \Bitrix\Main\Type\DateTime();
				$fields['QUEUE_HISTORY'][$fields['OPERATOR_ID']] = true;
			}
			
			if (!empty($params['USER_LIST']))
			{
				$this->joinUserList = array_merge(Array($fields['OPERATOR_ID']), $params['USER_LIST']);
			}
			else if ($fields['OPERATOR_ID'])
			{
				$this->joinUserList = Array($fields['OPERATOR_ID']);
			}
		}
		elseif ($fields['MODE'] == self::MODE_OUTPUT)
		{
			if ($this->config['CRM'] == 'Y' && \IsModuleInstalled('crm'))
			{
				$crmFields = array_merge($fields, Array('CRM_TITLE' => $this->chat->getData('TITLE')));
				$crmFields['ANSWERED'] = 'Y';
				
				$result = $this->updateCrm($crmFields);
				
				$fields = array_merge($fields, $result);
				$this->chat->setCrmFlag(Array(
					'ACTIVE' => 'Y',
					'ENTITY_TYPE' => $fields['CRM_ENTITY_TYPE'],
					'ENTITY_ID' => $fields['CRM_ENTITY_ID'],
				));
			}
			if ($this->config['ACTIVE'] == 'N')
			{
				$this->action = self::ACTION_CLOSED;
				$fields['WORKTIME'] = 'N';
				$fields['WAIT_ACTION'] = 'Y';
			}
			if ($fields['OPERATOR_ID'])
			{
				if ($this->chat->getData('AUTHOR_ID') == 0)
				{
					$this->chat->answer($fields['OPERATOR_ID'], true);
					$this->chat->join($fields['OPERATOR_ID']);
				}
			}
			$fields['WAIT_ANSWER'] = 'N';
		}
		
		$sessionId = $fields['SESSION_ID'];
		unset($fields['SESSION_ID']);
		Model\SessionTable::update($sessionId, $fields);
		$fields['SESSION_ID'] = $sessionId;
		
		if (
			$fields['MODE'] == self::MODE_INPUT && 
			$fields['OPERATOR_ID'] && 
			$params['DEFERRED_JOIN'] == 'N'
		)
		{
			$this->chat->join($this->joinUserList);
			$this->joinUserList = Array();
		}

		$updateStats = Array(
			'IN_WORK' => new \Bitrix\Main\DB\SqlExpression("?# + 1", "IN_WORK"),
			'SESSION' => new \Bitrix\Main\DB\SqlExpression("?# + 1", "SESSION"),
		);
		if ($fields['CRM_CREATE'] == 'Y')
		{
			$updateStats['LEAD'] = new \Bitrix\Main\DB\SqlExpression("?# + 1", "LEAD");
		}
		Model\ConfigStatisticTable::update($fields['CONFIG_ID'], $updateStats);

		$dateClose = new \Bitrix\Main\Type\DateTime();
		$dateClose->add($this->config['AUTO_CLOSE_TIME'].' SECONDS');
		$dateQueue = null;

		if ($fields['MODE'] == self::MODE_INPUT)
		{
			$dateQueue = new \Bitrix\Main\Type\DateTime();
			if ($this->session['JOIN_BOT'])
			{
				if ($this->config['WELCOME_BOT_TIME'] > 0)
				{
					$dateQueue->add($this->config['WELCOME_BOT_TIME'].' SECONDS');
				}
				else
				{
					$dateQueue = null;
				}
			}
			else if ($fields['WAIT_ACTION'] != 'Y')
			{
				$dateQueue->add($this->config['QUEUE_TIME'].' SECONDS');
			}
		}
		
		Model\SessionCheckTable::add(Array(
			'SESSION_ID' => $fields['SESSION_ID'],
			'DATE_CLOSE' => $fields['MODE'] == self::MODE_OUTPUT? $dateClose: null,
			'DATE_QUEUE' => $dateQueue,
		));

		$orm = Model\SessionTable::getByIdPerformance($fields['SESSION_ID']);
		$this->session = $orm->fetch();
		
		$this->session['CHECK_DATE_CLOSE'] = $fields['MODE'] == self::MODE_OUTPUT? $dateClose: null;
		$this->session['CHECK_DATE_QUEUE'] = $dateQueue;

		self::deleteQueueFlagCache();

		$eventData['SESSION'] = $this->session;
		$eventData['CONFIG'] = $this->config;
		
		$event = new \Bitrix\Main\Event("imopenlines", "OnSessionStart", $eventData);
		$event->send();

		return true;
	}

	public function pause($active = true)
	{
		$this->update(Array(
			'PAUSE' => $active? 'Y': 'N',
			'DATE_MODIFY' => new \Bitrix\Main\Type\DateTime()
		));
		return true;
	}

	public function markSpam()
	{
		$this->update(Array(
			'SPAM' => 'Y',
			'WAIT_ANSWER' => 'N',
			'DATE_MODIFY' => new \Bitrix\Main\Type\DateTime(),
		));
		return true;
	}
	
	public function finish($auto = false, $force = false, $hideChat = true)
	{
		if (empty($this->session))
		{
			return false;
		}
		
		$update = Array();
		
		if ($force)
		{
			$this->session['CLOSED'] = 'Y';
			$update['FORCE_CLOSE'] = 'Y';
		}
		
		if (defined('IMOL_NETWORK_UPDATE') && $this->session['SOURCE'] == 'network')
		{
			$this->config['VOTE_MESSAGE'] = 'N';
		}
		
		$currentDate = new \Bitrix\Main\Type\DateTime();
		
		if ($this->session['CHAT_ID'])
		{
			$chatData = \Bitrix\Im\Model\ChatTable::getById($this->session['CHAT_ID'])->fetch();
			$lastMessageId = $chatData['LAST_MESSAGE_ID'];
		}
		else
		{
			$lastMessageId = 0;
		}
		
		if ($auto && $lastMessageId > 0)
		{
			$messageData = \Bitrix\Im\Model\MessageTable::getById($lastMessageId)->fetch();
			if ($messageData)
			{
				$currentDate = $messageData['DATE_CREATE'];
			}
		}
		
		if (
			$this->session['CLOSED'] == 'Y' 
			|| $this->session['SPAM'] == 'Y'
			|| $this->session['WAIT_ACTION'] == 'Y' && $this->session['WAIT_ANSWER'] == 'N' 
		)
		{
			$update['CLOSED'] = 'Y';
			
			$params = Array(
				"CLASS" => "bx-messenger-content-item-ol-end"
			);
			if ($this->config['VOTE_MESSAGE'] == 'Y')
			{
				$params["IMOL_VOTE_SID"] = $this->session['ID'];
				$params["IMOL_VOTE_USER"] = $this->session['VOTE'];
				$params["IMOL_VOTE_HEAD"] = $this->session['VOTE_HEAD'];
			}
			\CIMChat::AddMessage(Array(
				"TO_CHAT_ID" => $this->session['CHAT_ID'],
				"FROM_USER_ID" => $this->session['OPERATOR_ID'],
				"MESSAGE" => Loc::getMessage('IMOL_SESSION_CLOSE_FINAL'),
				"SYSTEM" => 'Y',
				"PARAMS" => $params
			));
		}
		else
		{
			$enableSystemMessage = Connector::isEnableSendSystemMessage($this->connectorId);
			if ($this->config['ACTIVE'] == 'N')
			{
				$update['WAIT_ACTION'] = 'N';
				$update['CLOSED'] = 'Y';
				
				$params = Array(
					"CLASS" => "bx-messenger-content-item-ol-end"
				);
				if ($this->config['VOTE_MESSAGE'] == 'Y')
				{
					$params["IMOL_VOTE_SID"] = $this->session['ID'];
					$params["IMOL_VOTE_USER"] = $this->session['VOTE'];
					$params["IMOL_VOTE_HEAD"] = $this->session['VOTE_HEAD'];
				}
				\CIMChat::AddMessage(Array(
					"TO_CHAT_ID" => $this->session['CHAT_ID'],
					"FROM_USER_ID" => $this->session['OPERATOR_ID'],
					"MESSAGE" => Loc::getMessage('IMOL_SESSION_CLOSE_FINAL'),
					"SYSTEM" => 'Y',
					"PARAMS" => $params
				));
			}
			else if ($auto)
			{
				$waitAction = false;
				if ($enableSystemMessage && $this->config['AUTO_CLOSE_RULE'] == self::RULE_TEXT)
				{
					$this->chat->update(Array(
						Chat::getFieldName(Chat::FIELD_SILENT_MODE) => 'N'
					));

					\CIMChat::AddMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"MESSAGE" => $this->config['AUTO_CLOSE_TEXT'],
						"SYSTEM" => 'Y',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"CLASS" => "bx-messenger-content-item-ol-output",
							"IMOL_FORM" => "history"
						)
					));
					$update['WAIT_ACTION'] = 'Y';
					$waitAction = true;
				}
				
				if ($enableSystemMessage && $this->config['VOTE_MESSAGE'] == 'Y')
				{
					\CIMChat::AddMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"MESSAGE" => $this->config['VOTE_MESSAGE_2_TEXT'],
						"SYSTEM" => 'Y',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"IMOL_VOTE" => $this->session['ID'],
							"IMOL_VOTE_TEXT" => $this->config['VOTE_MESSAGE_1_TEXT'],
							"IMOL_VOTE_LIKE" => $this->config['VOTE_MESSAGE_1_LIKE'],
							"IMOL_VOTE_DISLIKE" => $this->config['VOTE_MESSAGE_1_DISLIKE'],
							"CLASS" => "bx-messenger-content-item-ol-output bx-messenger-content-item-vote",
							"IMOL_FORM" => "history-delay"
						)
					));
					$update['WAIT_ACTION'] = 'Y';
					$update['WAIT_VOTE'] = 'Y';
					$waitAction = true;
				}
				
				if (!$waitAction)
				{
					$update['WAIT_ACTION'] = 'N';
					$update['CLOSED'] = 'Y';
					
					$params = Array(
						"CLASS" => "bx-messenger-content-item-ol-end"
					);
					if ($this->config['VOTE_MESSAGE'] == 'Y')
					{
						$params["IMOL_VOTE_SID"] = $this->session['ID'];
						$params["IMOL_VOTE_USER"] = $this->session['VOTE'];
						$params["IMOL_VOTE_HEAD"] = $this->session['VOTE_HEAD'];
					}
					\CIMChat::AddMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"MESSAGE" => Loc::getMessage('IMOL_SESSION_CLOSE_AUTO'),
						"SYSTEM" => 'Y',
						"PARAMS" => $params
					));
				}
			}
			else
			{
				$waitAction = false;
				if ($enableSystemMessage && $this->config['CLOSE_RULE'] == self::RULE_TEXT)
				{
					\CIMChat::AddMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"MESSAGE" => $this->config['CLOSE_TEXT'],
						"SYSTEM" => 'Y',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"CLASS" => "bx-messenger-content-item-ol-output",
							"IMOL_FORM" => "history"
						)
					));
					$update['WAIT_ACTION'] = 'Y';
					$waitAction = true;
				}
				
				if ($enableSystemMessage && $this->config['VOTE_MESSAGE'] == 'Y')
				{
					\CIMChat::AddMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"MESSAGE" => $this->config['VOTE_MESSAGE_2_TEXT'],
						"SYSTEM" => 'Y',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"IMOL_VOTE" => $this->session['ID'],
							"IMOL_VOTE_TEXT" => $this->config['VOTE_MESSAGE_1_TEXT'],
							"IMOL_VOTE_LIKE" => $this->config['VOTE_MESSAGE_1_LIKE'],
							"IMOL_VOTE_DISLIKE" => $this->config['VOTE_MESSAGE_1_DISLIKE'],
							"CLASS" => "bx-messenger-content-item-ol-output bx-messenger-content-item-vote",
							"IMOL_FORM" => "history-delay"
						)
					));
					$update['WAIT_ACTION'] = 'Y';
					$update['WAIT_VOTE'] = 'Y';
					$waitAction = true;
				}
				
				if (!$waitAction)
				{
					$userSkip = \Bitrix\Im\User::getInstance($this->chat->getData('OPERATOR_ID'));
					
					$params = Array(
						"CLASS" => "bx-messenger-content-item-ol-end"
					);
					if ($this->config['VOTE_MESSAGE'] == 'Y')
					{
						$params["IMOL_VOTE_SID"] = $this->session['ID'];
						$params["IMOL_VOTE_USER"] = $this->session['VOTE'];
						$params["IMOL_VOTE_HEAD"] = $this->session['VOTE_HEAD'];
					}
					\CIMChat::AddMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"MESSAGE" => Loc::getMessage('IMOL_SESSION_CLOSE_'.$userSkip->getGender(), Array('#USER#' => '[USER='.$userSkip->getId().']'.$userSkip->getFullName(false).'[/USER]')),
						"SYSTEM" => 'Y',
						"PARAMS" => $params
					));
					
					$update['WAIT_ACTION'] = 'N';
					$update['CLOSED'] = 'Y';
				}

				if (!\Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot())
				{
					$update['DATE_OPERATOR_CLOSE'] = $currentDate;
				}
				if ($this->session['DATE_CREATE'])
				{
					$update['TIME_CLOSE'] = $currentDate->getTimestamp()-$this->session['DATE_CREATE']->getTimestamp();
				}
			}
			
			$update['DATE_MODIFY'] = $currentDate;
		}

		if ($update['CLOSED'] == 'Y')
		{
			
			if ($this->session['CRM_ACTIVITY_ID'] > 0)
			{
				$crmManager = new Crm();
				$crmManager->updateActivity(Array(
					'ID' => $this->session['CRM_ACTIVITY_ID'],
					'UPDATE' => Array(
						'COMPLETED' => 'Y',
						'END_TIME' => $currentDate
					)
				));
			}
			
			$update['DATE_CLOSE'] = $currentDate;
			if ($this->session['TIME_CLOSE'] <= 0 && $this->session['DATE_CREATE'])
			{
				$update['TIME_CLOSE'] = $update['DATE_CLOSE']->getTimestamp()-$this->session['DATE_CREATE']->getTimestamp();
			}
			if (\Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot() && $this->session['TIME_BOT'] <= 0 && $this->session['DATE_CREATE'])
			{
				$update['TIME_BOT'] = $update['DATE_CLOSE']->getTimestamp()-$this->session['DATE_CREATE']->getTimestamp();
			}
			
			if ($this->session['CHAT_ID'])
			{
				$update['END_ID'] = $lastMessageId;
			}
		}
		
		if ($hideChat && \Bitrix\Main\Loader::includeModule("pull"))
		{
			\CIMChat::hide($this->session['CHAT_ID']);
		}
		
		$this->update($update);

		return true;
	}

	public function getData($field = '')
	{
		if ($field)
		{
			return isset($this->session[$field])? $this->session[$field]: null;
		}
		else
		{
			return $this->session;
		}
	}

	public function getConfig($field = '')
	{
		if ($field)
		{
			return isset($this->config[$field])? $this->config[$field]: null;
		}
		else
		{
			return $this->config;
		}
	}

	public function createLead($params)
	{
		$limitRemainder = Limit::getTrackerLimitRemainder();
		if ($limitRemainder <= 0)
		{
			$tracker = new Tracker;
			$tracker->sendLimitMessage(Array(
				'OPERATOR_ID' => $params['OPERATOR_ID'],
				'CHAT_ID' => $params['CHAT_ID'],
				'MESSAGE_TYPE' => Tracker::MESSAGE_ERROR_CREATE
			));
			
			return false;
		}
		
		$crmData = false;

		$crmManager = new Crm();
		$id = $crmManager->addLead(array(
			'CONFIG_ID' => $params['CONFIG_ID'],
			'USER_CODE' => $params['USER_CODE'],
			'USER_ID' => $params['USER_ID'],
			'TITLE' => $params['CRM_TITLE'],
			'OPERATOR_ID' => $params['OPERATOR_ID'],
		));
		if ($id)
		{
			\Bitrix\Imopenlines\Limit::increaseTracker();
			
			$crmData['ENTITY_TYPE'] = \CCrmOwnerType::LeadName;
			$crmData['ENTITY_ID'] = $id;
			$crmData['BINDINGS'][] = array(
				'OWNER_ID' => $id,
				'OWNER_TYPE_ID' => \CCrmOwnerType::Lead
			);
			
			$userViewChat = \CIMContactList::InRecent($params['OPERATOR_ID'], IM_MESSAGE_CHAT, $params['CHAT_ID']);
			\CIMChat::AddMessage(Array(
				"TO_CHAT_ID" => $params['CHAT_ID'],
				"MESSAGE" => '[b]'.Loc::getMessage('IMOL_SESSION_LEAD_ADD_NEW').'[/b]',
				"SYSTEM" => 'Y',
				"ATTACH" => $crmManager->getEntityCard($crmData['ENTITY_TYPE'], $crmData['ENTITY_ID']),
				"RECENT_ADD" => $userViewChat? 'Y': 'N'
			));

			if (!empty($crmData['BINDINGS']))
			{
				$crmData['ACTIVITY_ID'] = $crmManager->addActivity(Array(
					'TITLE' => $params['CRM_TITLE'],
					'MODE' => $params['MODE'],
					'USER_CODE' => $params['USER_CODE'],
					'SESSION_ID' => intval($params['SESSION_ID']),
					'COMPLETED' => 'N',
					'DATE_CREATE' => new \Bitrix\Main\Type\DateTime(),
					'AUTHOR_ID' => $params['OPERATOR_ID'],
					'RESPONSIBLE_ID' => $params['OPERATOR_ID'],
					'CRM_ENTITY_TYPE' => $crmData['ENTITY_TYPE'],
					'CRM_ENTITY_ID' => $crmData['ENTITY_ID'],
					'CRM_BINDINGS' => $crmData['BINDINGS'],
					'ANSWERED' => $params['ANSWERED'] == 'Y'? 'Y': 'N',
				));
				$crmManager->executeAutomationTrigger($crmData['BINDINGS'], array(
					'CONFIG_ID' => $params['CONFIG_ID']
				));
			}
		}

		return $crmData;
	}

	public function setOperatorId($id, $waitAnswer = false)
	{
		$this->update(Array(
			'WAIT_ANSWER' => $waitAnswer? 'Y': 'N',
			'OPERATOR_ID' => $id,
			'DATE_MODIFY' => new \Bitrix\Main\Type\DateTime(),
			'SKIP_DATE_CLOSE' => 'Y'
		));
		if ($this->config['CRM'] == 'Y' && $this->config['CRM_TRANSFER_CHANGE'] == 'Y' && \IsModuleInstalled('crm'))
		{
			$crmManager = new Crm();
			if ($this->session['CRM_CREATE'] == 'Y' && $this->session['CRM_ENTITY_TYPE'] == Crm::ENTITY_LEAD)
			{
				$crmManager->update($this->session['CRM_ENTITY_TYPE'], $this->session['CRM_ENTITY_ID'], Array(
					'ASSIGNED_BY_ID' => $id
				));
			}
			$crmManager->updateActivity(Array(
				'ID' => $this->session['CRM_ACTIVITY_ID'],
				'UPDATE' => Array(
					'RESPONSIBLE_ID' => $id
				)
			));
		}

		return true;
	}

	public function update($fields)
	{
		$updateCheckTable = Array();
		$updateChatSession = Array();
		
		if (isset($fields['CONFIG_ID']))
		{
			$configManager = new Config();
			$config = $configManager->get($fields['CONFIG_ID']);
			if ($config)
			{
				$this->config = $config;
			}
			else
			{
				unset($fields['CONFIG_ID']);
			}
		}
		if (array_key_exists('CHECK_DATE_CLOSE', $fields))
		{
			$updateCheckTable['DATE_CLOSE'] = $fields['CHECK_DATE_CLOSE'];
			unset($fields['CHECK_DATE_CLOSE']);
		}
		else if (isset($fields['DATE_MODIFY']) && ($this->session['PAUSE'] == 'N' || $fields['PAUSE'] == 'N') && $fields['CLOSED'] != 'Y')
		{
			$dateClose = clone $fields['DATE_MODIFY'];
			if ($this->session['WAIT_ACTION'] == 'N' && isset($fields['USER_ID']) && \Bitrix\Im\User::getInstance($fields['USER_ID'])->isConnector())
			{
				$dateClose = new \Bitrix\Main\Type\DateTime();
				$dateClose->add('1 MONTH');
				
				$updateCheckTable['DATE_CLOSE'] = $dateClose;
			}
			else
			{
				if (isset($fields['SKIP_DATE_CLOSE']))
				{
					$dateClose = null;
				}
				else if ($this->session['WAIT_ANSWER'] == 'Y' && $fields['WAIT_ANSWER'] != 'N' || $fields['WAIT_ANSWER'] == 'Y')
				{
					$dateClose->add('1 MONTH');
				}
				else if ($this->session['WAIT_ACTION'] == 'Y' && $fields['WAIT_ACTION'] != 'N' || $fields['WAIT_ACTION'] == 'Y')
				{
					$dateClose->add('10 MINUTES');
				}
				else
				{
					$dateClose->add($this->config['AUTO_CLOSE_TIME'].' SECONDS');
				}
				
				if ($dateClose)
				{
					$updateCheckTable['DATE_CLOSE'] = $dateClose;
				}
			}
		}

		if (isset($fields['DATE_LAST_MESSAGE']) && $this->session['DATE_CREATE'])
		{
			$fields['TIME_DIALOG'] = $fields['DATE_LAST_MESSAGE']->getTimestamp()-$this->session['DATE_CREATE']->getTimestamp();
		}

		if (isset($fields['CLOSED']) && $fields['CLOSED'] == 'Y')
		{
			if ($this->session['WAIT_ANSWER'] == 'N' || $this->session['SPAM'] == 'Y' || $fields['FORCE_CLOSE'] == 'Y')
			{
				$fields['PAUSE'] = 'N';
				$updateChatSession['PAUSE'] = 'N';
				
				$updateCheckTable = Array();

				$updateStatisticTable['CLOSED'] = new \Bitrix\Main\DB\SqlExpression("?# + 1", "CLOSED");
				$updateStatisticTable['IN_WORK'] = new \Bitrix\Main\DB\SqlExpression("?# - 1", "IN_WORK");

				if ($fields['FORCE_CLOSE'] != 'Y')
				{
					$this->chat->close();
				}
				
				if ($this->session['SOURCE'] == 'livechat' && $this->session['SPAM'] != 'Y')
				{
					if (\Bitrix\Main\Loader::includeModule('im') && \Bitrix\Im\User::getInstance($this->session['USER_ID'])->isOnline())
					{
						\CAgent::AddAgent('\Bitrix\ImOpenLines\Mail::sendOperatorAnswerAgent('.$this->session['ID'].');', "imopenlines", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));
					}
					else
					{
						\Bitrix\ImOpenLines\Mail::sendOperatorAnswer($this->session['ID']);
					}
				}
				
				Model\SessionCheckTable::delete($this->session['ID']);
			}
			else
			{
				unset($fields['CLOSED']);
			}
		}
		else if (isset($fields['PAUSE']))
		{
			if ($fields['PAUSE'] == 'Y')
			{
				$datePause = new \Bitrix\Main\Type\DateTime();
				$datePause->add('1 WEEK');

				$updateCheckTable['DATE_CLOSE'] = $datePause;
			}
		}
		else if (isset($fields['WAIT_ANSWER']))
		{
			if ($fields['WAIT_ANSWER'] == 'Y')
			{
				$fields['PAUSE'] = 'N';
				$updateChatSession['PAUSE'] = 'N';

				$dateQueue = new \Bitrix\Main\Type\DateTime();
				$dateQueue->add($this->config['QUEUE_TIME'].' SECONDS');
				$updateCheckTable['DATE_QUEUE'] = $dateQueue;
			}
			else
			{
				$fields['WAIT_ACTION'] = 'N';
				$fields['PAUSE'] = 'N';
				$updateChatSession['WAIT_ACTION'] = 'N';
				$updateChatSession['PAUSE'] = 'N';
				
				$updateCheckTable['DATE_QUEUE'] = null;
			}
		}

		if (!empty($updateChatSession))
		{
			$this->chat->updateFieldData(Chat::FIELD_SESSION, $updateChatSession);
		}

		if (isset($fields['MESSAGE_COUNT']))
		{
			$fields["MESSAGE_COUNT"] = new \Bitrix\Main\DB\SqlExpression("?# + 1", "MESSAGE_COUNT");
			$updateStatisticTable['MESSAGE'] = new \Bitrix\Main\DB\SqlExpression("?# + 1", "MESSAGE");
		}

		if (isset($fields['CRM_CREATE']) && $fields['CRM_CREATE'] == 'Y')
		{
			$updateStatisticTable['LEAD'] = new \Bitrix\Main\DB\SqlExpression("?# + 1", "LEAD");
		}

		if (!empty($updateCheckTable))
		{
			Model\SessionCheckTable::update($this->session['ID'], $updateCheckTable);
		}

		if (!empty($updateStatisticTable))
		{
			Model\ConfigStatisticTable::update($this->session['CONFIG_ID'], $updateStatisticTable);
		}
		
		unset($fields['USER_ID']);
		unset($fields['SKIP_DATE_CLOSE']);
		unset($fields['FORCE_CLOSE']);

		foreach ($fields as $key => $value)
		{
			$this->session[$key] = $value;
		}	
		foreach ($updateCheckTable as $key => $value)
		{
			$this->session['CHECK_'.$key] = $value;
		}
		if ($this->session['ID'] && !empty($fields))
		{
			Model\SessionTable::update($this->session['ID'], $fields);
		}

		return true;
	}

	public function updateCrm($params)
	{
		$result = array(
			'CRM' => 'N',
			'CRM_CREATE' => 'N',
			'CRM_ENTITY_TYPE' => 'NONE',
			'CRM_ENTITY_ID' => 0,
			'CRM_ACTIVITY_ID' => 0
		);
		if (!\IsModuleInstalled('crm'))
		{
			return $result;
		}
		
		$crmManager = new Crm();
		$crmData = $crmManager->find(Crm::FIND_BY_CODE, Array('CODE' => $params['USER_CODE']));
		if (!$crmData && $params['USER_ID'] > 0)
		{
			$limitRemainder = Limit::getTrackerLimitRemainder();
			if ($limitRemainder > 0)
			{
				$tracker = new \Bitrix\ImOpenLines\Tracker();
				$crmData = $tracker->user(Array(
					'CHAT_ID' => $params['CHAT_ID'],
					'USER_ID' => $params['USER_ID'],
					'USER_CODE' => $params['USER_CODE'],
					'SESSION_ID' => $params['SESSION_ID'],
					'OPERATOR_ID' => $params['OPERATOR_ID'],
				));
			}
		}
		
		if ($crmData)
		{
			$result['CRM'] = 'Y';
			$result['CRM_ENTITY_TYPE'] = $crmData['ENTITY_TYPE'];
			$result['CRM_ENTITY_ID'] = $crmData['ENTITY_ID'];
			
			if ($this->config['CRM_FORWARD'] == 'Y' && $result['CRM'] == 'Y' && $params['MODE'] == self::MODE_INPUT)
			{
				$entityData = $crmManager->getEntityData($result['CRM_ENTITY_TYPE'], $result['CRM_ENTITY_ID']);
				if ($entityData)
				{
					$params['OPERATOR_ID'] = $entityData['ASSIGNED_BY_ID'];
					$result['OPERATOR_ID'] = $params['OPERATOR_ID'];
				}
			}

			if (!empty($crmData['BINDINGS']))
			{
				$result['CRM_ACTIVITY_ID'] = $crmManager->addActivity(Array(
					'TITLE' => $params['CRM_TITLE'],
					'MODE' => $params['MODE'],
					'USER_CODE' => $params['USER_CODE'],
					'SESSION_ID' => intval($params['SESSION_ID']),
					'COMPLETED' => 'N',
					'DATE_CREATE' => new \Bitrix\Main\Type\DateTime(),
					'AUTHOR_ID' => $params['OPERATOR_ID'],
					'RESPONSIBLE_ID' => $params['OPERATOR_ID'],
					'CRM_ENTITY_TYPE' => $crmData['ENTITY_TYPE'],
					'CRM_ENTITY_ID' => $crmData['ENTITY_ID'],
					'CRM_BINDINGS' => $crmData['BINDINGS'],
					'ANSWERED' => $params['ANSWERED'] == 'Y'? 'Y': 'N',
				));
				$crmManager->executeAutomationTrigger($crmData['BINDINGS'], array(
					'CONFIG_ID' => $params['CONFIG_ID']
				));
			}
		}
		else if ($this->config['CRM_CREATE'] == self::CRM_CREATE_LEAD && $params['OPERATOR_ID'] && $params['SOURCE'] != 'livechat' && $params['USER_ID'] > 0)
		{
			$crmData = $this->createLead(array(
				'CONFIG_ID' => $params['CONFIG_ID'],
				'MODE' => $params['MODE'],
				'USER_CODE' => $params['USER_CODE'],
				'SESSION_ID' => intval($params['SESSION_ID']),
				'USER_ID' => $params['USER_ID'],
				'CRM_TITLE' => $params['CRM_TITLE'],
				'OPERATOR_ID' => $params['OPERATOR_ID'],
				'CHAT_ID' => $params['CHAT_ID'],
				'ANSWERED' => $params['ANSWERED'] == 'Y'? 'Y': 'N',
			));
			if ($crmData)
			{
				$result['CRM'] = 'Y';
				$result['CRM_CREATE'] = 'Y';
				$result['CRM_ENTITY_TYPE'] = \CCrmOwnerType::LeadName;
				$result['CRM_ENTITY_ID'] = $crmData['ENTITY_ID'];
				$result['CRM_ACTIVITY_ID'] = $crmData['ACTIVITY_ID'];
			}
		}

		return $result;
	}

	public function checkWorkTime()
	{
		$skipSession = false;
		if ($this->config['WORKTIME_ENABLE'] == 'N')
		{
			return true;
		}

		$timezone = !empty($this->config["WORKTIME_TIMEZONE"])? new \DateTimeZone($this->config["WORKTIME_TIMEZONE"]) : null;
		$numberDate = new \Bitrix\Main\Type\DateTime(null, null, $timezone);

		if (!empty($this->config['WORKTIME_DAYOFF']))
		{
			$allWeekDays = array('MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7);
			$currentWeekDay = $numberDate->format('N');
			foreach($this->config['WORKTIME_DAYOFF'] as $day)
			{
				if ($currentWeekDay == $allWeekDays[$day])
				{
					$skipSession = true;
					break;
				}
			}
		}

		if (!$skipSession && !empty($this->config['WORKTIME_HOLIDAYS']))
		{
			$currentDay = $numberDate->format('d.m');
			foreach($this->config['WORKTIME_HOLIDAYS'] as $holiday)
			{
				if ($currentDay == $holiday)
				{
					$skipSession = true;
					break;
				}
			}
		}

		if (!$skipSession && !empty($this->config['WORKTIME_FROM']) && !empty($this->config['WORKTIME_TO']))
		{
			$currentTime = $numberDate->format('G.i');

			if (!($currentTime >= $this->config['WORKTIME_FROM'] && $currentTime <= $this->config['WORKTIME_TO']))
			{
				$skipSession = true;
			}
		}

		if ($skipSession)
		{
			$this->action = self::ACTION_WORKTIME;
		}

		return $skipSession? false: true;
	}

	public function execAutoAction($params)
	{
		$update = Array();

		$enableSystemMessage = Connector::isEnableSendSystemMessage($this->connectorId);
		
		if ($this->action == self::ACTION_WELCOME)
		{
			if ($this->config['WELCOME_MESSAGE'] == 'Y' && $this->session['SOURCE'] != 'network' && $enableSystemMessage)
			{
				\CIMChat::AddMessage(Array(
					"TO_CHAT_ID" => $this->session['CHAT_ID'],
					"MESSAGE" => $this->config['WELCOME_MESSAGE_TEXT'],
					"SYSTEM" => 'Y',
					"IMPORTANT_CONNECTOR" => 'Y',
					"PARAMS" => Array(
						"CLASS" => "bx-messenger-content-item-ol-output",
					)
				));
			}
		}
		
		if ($this->action == self::ACTION_CLOSED && $this->config['ACTIVE'] == 'N')
		{
			\CIMChat::AddMessage(Array(
				"TO_CHAT_ID" => $this->session['CHAT_ID'],
				"MESSAGE" => Loc::getMessage('IMOL_SESSION_LINE_IS_CLOSED'),
				"SYSTEM" => 'Y',
			));
		}
		else if ($enableSystemMessage) 
		{
			if ($this->action == self::ACTION_WORKTIME)
			{
				if ($this->config['WORKTIME_DAYOFF_RULE'] == self::RULE_TEXT)
				{
					\CIMChat::AddMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"MESSAGE" => $this->config['WORKTIME_DAYOFF_TEXT'],
						"SYSTEM" => 'Y',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"CLASS" => "bx-messenger-content-item-ol-output",
							"IMOL_FORM" => "offline"
						)
					));
				}
			}
			else if ($this->action == self::ACTION_NO_ANSWER)
			{
				if ($this->config['NO_ANSWER_RULE'] == self::RULE_TEXT)
				{
					\CIMChat::AddMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"MESSAGE" => $this->config['NO_ANSWER_TEXT'],
						"SYSTEM" => 'Y',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"CLASS" => "bx-messenger-content-item-ol-output",
							"IMOL_FORM" => "offline"
						)
					));
				}
			}
		}

		$update['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
		$update['USER_ID'] = $GLOBALS['USER']? $GLOBALS['USER']->GetId(): 0;

		$this->update($update);
	}

	public function getQueue()
	{
		$queue = new Queue($this->session, $this->config, $this->chat);
		$result = $queue->getQueue();

		return $result;
	}
	
	public function getNextInQueue($manual = false)
	{
		$queue = new Queue($this->session, $this->config, $this->chat);
		$result = $queue->getNextUser($manual);

		return $result;
	}

	public function transferToNextInQueue($manual = true)
	{
		$queue = $this->getNextInQueue($manual);
		if (!$manual && $this->config['QUEUE_TYPE'] == Config::QUEUE_TYPE_ALL)
		{
			$queue['RESULT'] = false;
		}
		else
		{
			$transferToQueue = false;
			$dateQueue = new \Bitrix\Main\Type\DateTime();
			$dateQueue->add($this->config['QUEUE_TIME'].' SECONDS');
		}
		
		if ($queue['RESULT'])
		{
			if ($queue['USER_ID'] && $this->session['OPERATOR_ID'] != $queue['USER_ID'])
			{
				$this->chat->transfer(Array(
					'FROM' => $this->session['OPERATOR_ID'],
					'TO' => $queue['USER_ID'],
					'MODE' => Chat::TRANSFER_MODE_AUTO,
					'LEAVE' => $this->config['WELCOME_BOT_LEFT'] == Config::BOT_LEFT_CLOSE && \Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot()? 'N':'Y'
				));
			}
			$this->session['QUEUE_HISTORY'][$queue['USER_ID']] = true;
			$update['QUEUE_HISTORY'] = $this->session['QUEUE_HISTORY'];
		}
		else if ($this->session['WAIT_ACTION'] != 'Y' && $this->config['ACTIVE'] == 'Y')
		{
			if ($this->session['OPERATOR_ID'] <= 0 || \Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot())
			{
				$this->chat->transfer(Array(
					'FROM' => $this->session['OPERATOR_ID'],
					'TO' => $queue['FIRST_IN_QUEUE'],
					'MODE' => Chat::TRANSFER_MODE_AUTO,
					'LEAVE' => $this->config['WELCOME_BOT_LEFT'] == Config::BOT_LEFT_CLOSE && \Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot()? 'N':'Y'
				));
			}
			if ($this->startNoAnswerRule())
			{
				if ($this->config['NO_ANSWER_RULE'] != self::RULE_QUEUE)
				{
					$update['WAIT_ACTION'] = 'Y';
					$dateQueue = null;
				}
				if ($this->config['NO_ANSWER_RULE'] == self::RULE_TEXT && Connector::isEnableSendSystemMessage($this->connectorId))
				{
					\CIMChat::AddMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"MESSAGE" => $this->config['NO_ANSWER_TEXT'],
						"SYSTEM" => 'Y',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"CLASS" => "bx-messenger-content-item-ol-output",
							"IMOL_FORM" => "offline"
						)
					));
				}
			}
			else if ($this->config['NO_ANSWER_RULE'] == self::RULE_QUEUE && $manual)
			{
				\CIMChat::AddMessage(Array(
					"TO_CHAT_ID" => $this->session['CHAT_ID'],
					'MESSAGE' => Loc::getMessage('IMOL_SESSION_SKIP_ALONE'),
					'SYSTEM' => 'Y',
					'SKIP_COMMAND' => 'Y'
				));
			}
		}
		else
		{
			if ($this->session['OPERATOR_ID'] <= 0 && $queue['FIRST_IN_QUEUE'] > 0)
			{
				$this->chat->transfer(Array(
					'FROM' => $this->session['OPERATOR_ID'],
					'TO' => $queue['FIRST_IN_QUEUE'],
					'MODE' => Chat::TRANSFER_MODE_AUTO,
					'LEAVE' => $this->config['WELCOME_BOT_LEFT'] == Config::BOT_LEFT_CLOSE && \Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot()? 'N':'Y'
				));
			}
			else if ($manual)
			{
				if ($queue['FIRST_IN_QUEUE'] > 0 && $queue['FIRST_IN_QUEUE'] != $this->session['OPERATOR_ID'])
				{
					$transferToQueue = true;
				}
				else
				{
					\CIMChat::AddMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						'MESSAGE' => Loc::getMessage('IMOL_SESSION_SKIP_ALONE'),
						'SYSTEM' => 'Y',
						'SKIP_COMMAND' => 'Y'
					));
				}
			}
			$dateQueue = null;
			$update['QUEUE_HISTORY'] = Array();
		}

		Model\SessionCheckTable::update($this->session['ID'], Array(
			'DATE_QUEUE' => $dateQueue
		));

		$update['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
		$update['SKIP_DATE_CLOSE'] = 'Y';
		
		$this->update($update);
		
		if ($transferToQueue)
		{
			self::transferToNextInQueue(true);
		}
	}

	public function startNoAnswerRule()
	{
		$finalize = false;
		if ($this->config['NO_ANSWER_RULE'] != self::RULE_QUEUE)
		{
			$this->action = self::ACTION_NO_ANSWER;
			$finalize = true;
		}
		return $finalize;
	}
	
	public static function transferToNextInQueueAgent($nextExec)
	{
		if (self::getQueueFlagCache(self::CACHE_QUEUE) || self::getQueueFlagCache(self::CACHE_CLOSE))
			return '\Bitrix\ImOpenLines\Session::transferToNextInQueueAgent(0);';

		$configCount = Model\SessionCheckTable::getList(array(
			'select' => array('CNT'),
			'runtime' => array(new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')),
			'filter' => Array('!=DATE_QUEUE' => null),
		))->fetch();
		if ($configCount['CNT'] <= 0)
		{
			self::setQueueFlagCache(self::CACHE_QUEUE);
			return '\Bitrix\ImOpenLines\Session::transferToNextInQueueAgent(0);';
		}

		$configs = Array();
		$chats = Array();
		$configManager = new Config();

		$select = Model\SessionTable::getSelectFieldsPerformance('SESSION');
		$res = Model\SessionCheckTable::getList(Array(
			'select' => $select,
			'filter' => Array(
				'<=DATE_QUEUE' => new \Bitrix\Main\Type\DateTime()
			),
			'limit' => 100
		));
		while ($row = $res->fetch())
		{
			$fields = Array();
			foreach($row as $key=>$value)
			{
				$key = str_replace('IMOPENLINES_MODEL_SESSION_CHECK_SESSION_', '', $key);
				$fields[$key] = $value;
			}

			if (!isset($configs[$fields['CONFIG_ID']]))
			{
				$configs[$fields['CONFIG_ID']] = $configManager->get($fields['CONFIG_ID']);
			}
			if (!isset($chats[$fields['CHAT_ID']]))
			{
				$chats[$fields['CHAT_ID']] = new Chat($fields['CHAT_ID']);
			}

			$session = new Session();
			$session->loadByArray($fields, $configs[$fields['CONFIG_ID']], $chats[$fields['CHAT_ID']]);
			$session->transferToNextInQueue(false);
		}

		return '\Bitrix\ImOpenLines\Session::transferToNextInQueueAgent(1);';
	}

	public static function closeByTimeAgent($nextExec)
	{
		if (self::getQueueFlagCache(self::CACHE_CLOSE))
			return '\Bitrix\ImOpenLines\Session::closeByTimeAgent(0);';

		$configCount = Model\SessionCheckTable::getList(array(
			'select' => array('CNT'),
			'runtime' => array(new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')),
			'filter' => array('!=DATE_CLOSE' => null)
		))->fetch();
		if ($configCount['CNT'] <= 0)
		{
			self::setQueueFlagCache(self::CACHE_CLOSE);
			return '\Bitrix\ImOpenLines\Session::closeByTimeAgent(0);';
		}

		$configs = Array();
		$chats = Array();
		$configManager = new Config();

		$select = Model\SessionTable::getSelectFieldsPerformance('SESSION');
		$res = Model\SessionCheckTable::getList(Array(
			'select' => $select,
			'filter' => Array(
				'<=DATE_CLOSE' => new \Bitrix\Main\Type\DateTime()
			),
			'limit' => 100
		));
		while ($row = $res->fetch())
		{
			$fields = Array();
			foreach($row as $key=>$value)
			{
				$key = str_replace('IMOPENLINES_MODEL_SESSION_CHECK_SESSION_', '', $key);
				$fields[$key] = $value;
			}

			if (!isset($configs[$fields['CONFIG_ID']]))
			{
				$configs[$fields['CONFIG_ID']] = $configManager->get($fields['CONFIG_ID']);
			}

			if (!isset($chats[$fields['CHAT_ID']]))
			{
				$chats[$fields['CHAT_ID']] = new Chat($fields['CHAT_ID']);
			}

			$session = new Session();
			$session->loadByArray($fields, $configs[$fields['CONFIG_ID']], $chats[$fields['CHAT_ID']]);
			$session->finish(true);
		}

		return '\Bitrix\ImOpenLines\Session::closeByTimeAgent(1);';
	}
	
	public static function mailByTimeAgent($nextExec)
	{
		if (self::getQueueFlagCache(self::CACHE_MAIL))
			return '\Bitrix\ImOpenLines\Session::mailByTimeAgent(0);';

		$configCount = Model\SessionCheckTable::getList(array(
			'select' => array('CNT'),
			'runtime' => array(new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')),
			'filter' => array('!=DATE_MAIL' => null)
		))->fetch();
		if ($configCount['CNT'] <= 0)
		{
			self::setQueueFlagCache(self::CACHE_MAIL);
			return '\Bitrix\ImOpenLines\Session::mailByTimeAgent(0);';
		}
		
		$res = Model\SessionCheckTable::getList(Array(
			'select' => Array('SESSION_ID'),
			'filter' => Array(
				'<=DATE_MAIL' => new \Bitrix\Main\Type\DateTime()
			),
			'limit' => 100
		));
		while ($row = $res->fetch())
		{
			\Bitrix\ImOpenLines\Mail::sendOperatorAnswer($row['SESSION_ID']);
		}

		return '\Bitrix\ImOpenLines\Session::mailByTimeAgent(1);';
	}
	
	private static function prolongDueChatActivity($chatId)
	{
		$orm = Model\SessionTable::getList(array(
			'select' => Array(
				'ID',
				'CHECK_DATE_CLOSE' => 'CHECK.DATE_CLOSE'
			),
			'filter' => array(
				'=CHAT_ID' => $chatId,
				'=CLOSED' => 'N'
			)
		));
		
		if ($result = $orm->fetch())
		{
			$currentDate = new \Bitrix\Main\Type\DateTime();
			if ($result['CHECK_DATE_CLOSE'] && $currentDate->getTimestamp()+600 > $result['CHECK_DATE_CLOSE']->getTimestamp())
			{
				$dateClose = $result['CHECK_DATE_CLOSE']->add('10 MINUTES');
				Model\SessionCheckTable::update($result['ID'], Array(
					'DATE_CLOSE' => $dateClose
				));
			}
		}
	}
	
	public static function onSessionProlongLastMessage($chatId, $dialogId, $entityType = '', $entityId = '', $userId = '')
	{
		if ($entityType != 'LINES')
			return true;
		
		self::prolongDueChatActivity($chatId);
		
		return true;
	}
	
	public static function onSessionProlongWriting($params)
	{
		if ($params['CHAT']['ENTITY_TYPE'] != 'LINES')
			return true;
		
		self::prolongDueChatActivity($params['CHAT']['ID']);
		
		return true;
	}
	
	public static function onSessionProlongChatRename($chatId, $title, $entityType = '', $entityId = '', $userId = '')
	{
		if ($entityType != 'LINES')
			return true;
		
		self::prolongDueChatActivity($chatId);
		
		return true;
	}
	
	public static function setQueueFlagCache($type = "")
	{
		if (!$type)
			return false;

		$app = \Bitrix\Main\Application::getInstance();
		$managedCache = $app->getManagedCache();
		$managedCache->clean("imol_queue_flag_".$type);
		$managedCache->read(86400*30, "imol_queue_flag_".$type);
		$managedCache->set("imol_queue_flag_".$type, true);

		return true;
	}

	public static function deleteQueueFlagCache($type = "")
	{
		$app = \Bitrix\Main\Application::getInstance();
		$managedCache = $app->getManagedCache();
		if ($type)
		{
			$managedCache->clean("imol_queue_flag_".$type);
		}
		else
		{
			$managedCache->clean("imol_queue_flag_".self::CACHE_CLOSE);
			$managedCache->clean("imol_queue_flag_".self::CACHE_QUEUE);
			$managedCache->clean("imol_queue_flag_".self::CACHE_INIT);
			$managedCache->clean("imol_queue_flag_".self::CACHE_MAIL);
		}

		return true;
	}

	public static function getQueueFlagCache($type = "")
	{
		if (!$type)
			return false;

		$app = \Bitrix\Main\Application::getInstance();
		$managedCache = $app->getManagedCache();
		if ($result = $managedCache->read(86400*30, "imol_queue_flag_".$type))
		{
			$result = $managedCache->get("imol_queue_flag_".$type) === false? false: true;
		}
		return $result;
	}

	public function getChat()
	{
		return $this->chat;
	}

	public function getAction()
	{
		return $this->action;
	}

	public function joinUser()
	{
		if (!empty($this->joinUserList))
		{
			Log::write($this->joinUserList, 'DEFFERED JOIN');
			$this->chat->join($this->joinUserList);
		}

		return true;
	}
	
	public function isNowCreated()
	{
		return $this->isCreated;
	}

	public function getError()
	{
		return $this->error;
	}
	
	public static function voteAsUser($sessionId, $action, $userId = null)
	{
		$sessionData = Model\SessionTable::getByIdPerformance($sessionId)->fetch();
		if (!$sessionData)
			return false;
		
		$userId = intval($userId);
		if ($userId > 0 && $sessionData['USER_ID'] != $userId)
		{
			 return false;
		}
		
		$voteValue = $action == 'dislike'? 1: 5;
		Model\SessionTable::update($sessionId, Array('VOTE' => $voteValue));
		
		if ($sessionData['END_ID'] > 0)
		{
			\CIMMessageParam::Set($sessionData['END_ID'], Array('IMOL_VOTE_SID' => $sessionId, 'IMOL_VOTE_USER' => $voteValue));
			\CIMMessageParam::SendPull($sessionData['END_ID'], Array('IMOL_VOTE_SID', 'IMOL_VOTE_USER'));
		}
		
		\Bitrix\ImOpenLines\Chat::sendRatingNotify(\Bitrix\ImOpenLines\Chat::RATING_TYPE_CLIENT, $sessionData['ID'], $voteValue, $sessionData['OPERATOR_ID'], $sessionData['USER_ID']);
		
		return true;
	}
	
	public static function voteAsHead($sessionId, $voteValue, $userId = null)
	{
		$sessionData = Model\SessionTable::getByIdPerformance($sessionId)->fetch();
		if (!$sessionData)
			return false;
		
		$userId = intval($userId);
		if ($userId > 0)
		{
			$configManager = new \Bitrix\ImOpenLines\Config();
			if (!$configManager->canVoteAsHead($sessionData['CONFIG_ID']))
			{
				return false;
			}
		}
		
		$voteValue = $voteValue == 1 || $voteValue <= 5? $voteValue: 0;
		Model\SessionTable::update($sessionId, Array('VOTE_HEAD' => $voteValue));
		
		if ($voteValue > 0)
		{
			\Bitrix\ImOpenLines\Chat::sendRatingNotify(\Bitrix\ImOpenLines\Chat::RATING_TYPE_HEAD, $sessionData['ID'], $voteValue, $sessionData['OPERATOR_ID'], $userId);
		}
		
		if (\Bitrix\Main\Loader::includeModule("pull"))
		{
			$pullMessage = Array(
				'module_id' => 'imopenlines',
				'command' => 'voteHead',
				'expiry' => 60,
				'params' => Array(
					'sessionId' => $sessionId,
					'voteValue' => $voteValue,
				),
			);
			/*
			$relations = \CIMChat::GetRelationById($sessionData['CHAT_ID']);
			\CPullStack::AddByUsers(array_keys($relations), $pullMessage);
			
			$pullMessage['skip_users'] = array_keys($relations);
			*/
			\CPullWatch::AddToStack('IMOL_STATISTICS', $pullMessage);
			
			if ($sessionData['END_ID'] > 0)
			{
				\CIMMessageParam::Set($sessionData['END_ID'], Array('IMOL_VOTE_SID' => $sessionData['ID'], 'IMOL_VOTE_HEAD' => $voteValue));
				\CIMMessageParam::SendPull($sessionData['END_ID'], Array('IMOL_VOTE_SID', 'IMOL_VOTE_HEAD'));
			}
		}
		
		return true;
	}
}