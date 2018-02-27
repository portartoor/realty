<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPCrmSendEmailActivity extends CBPActivity
{
	const TEXT_TYPE_BBCODE = 'bbcode';
	const TEXT_TYPE_HTML = 'html';
	const ATTACHMENT_TYPE_FILE = 'file';
	const ATTACHMENT_TYPE_DISK = 'disk';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"Subject" => "",
			"MessageText" => '',
			"MessageTextType" => '',
			'AttachmentType' => static::ATTACHMENT_TYPE_FILE,
			'Attachment' => array()
		);
	}

	public function Execute()
	{
		if (!$this->MessageText || !CModule::IncludeModule("crm") || !CModule::IncludeModule('subscribe'))
			return CBPActivityExecutionStatus::Closed;

		$ownerTypeID = $this->getEntityTypeId();
		$ownerID = $this->getEntityId();

		$userID = CCrmOwnerType::GetResponsibleID($ownerTypeID, $ownerID, false);
		if($userID <= 0)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		list($from, $userImap, $crmImap, $cc) = $this->getFromEmail($userID);
		$to = $this->getToEmail($ownerTypeID, $ownerID);

		if (empty($to))
		{
			$this->WriteToTrackingService(GetMessage('CRM_SEMA_NO_ADDRESSER'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$errors = array();

		// Bindings & Communications -->
		$arBindings = array(
			array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			)
		);
		$arComms = array(array(
			'TYPE' => 'EMAIL',
			'VALUE' => $to,
			'ENTITY_ID' => $ownerID,
			'ENTITY_TYPE_ID' => $ownerTypeID
		));
		// <-- Bindings & Communications

		$subject = (string)$this->Subject;
		$message = $this->MessageText;
		$messageType = $this->MessageTextType;

		if($message !== '')
		{
			CCrmActivity::AddEmailSignature($message,
				$messageType === self::TEXT_TYPE_HTML ? CCrmContentType::Html : CCrmContentType::BBCode
			);
		}

		if($message === '')
		{
			$messageHtml = '';
		}
		else
		{
			if ($messageType !== self::TEXT_TYPE_HTML)
			{
				//Convert BBCODE to HTML
				$parser = new CTextParser();
				$parser->allow['SMILES'] = 'N';
				$messageHtml = $parser->convertText($message);
			}
			else
			{
				$messageHtml = $message;
			}

			if (strpos($messageHtml, '</html>') === false)
			{
				$messageHtml = '<html><body>'.$messageHtml.'</body></html>';
			}
		}

		$now = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL');
		if($subject === '')
		{
			$subject = GetMessage(
				'CRM_SEMA_DEFAULT_SUBJECT',
				array('#DATE#'=> $now)
			);
		}

		$description = $message;

		$activityFields = array(
			'OWNER_ID' => $ownerID,
			'OWNER_TYPE_ID' => $ownerTypeID,
			'TYPE_ID' =>  CCrmActivityType::Email,
			'SUBJECT' => $subject,
			'START_TIME' => $now,
			'END_TIME' => $now,
			'COMPLETED' => 'Y',
			'RESPONSIBLE_ID' => $userID,
			'PRIORITY' => CCrmActivityPriority::Medium,
			'DESCRIPTION' => $description,
			'DESCRIPTION_TYPE' => $messageType === self::TEXT_TYPE_HTML ? CCrmContentType::Html : CCrmContentType::BBCode,
			'DIRECTION' => CCrmActivityDirection::Outgoing,
			'BINDINGS' => array_values($arBindings),
		);

		if ($this->AttachmentType === static::ATTACHMENT_TYPE_DISK)
		{
			$attachmentStorageType = Bitrix\Crm\Integration\StorageType::Disk;
			$attachment = (array)$this->Attachment;
		}
		else
		{
			$attachmentStorageType = Bitrix\Crm\Integration\StorageType::File;
			$attachment = array();
			$attachmentFiles = (array)$this->ParseValue($this->getRawProperty('Attachment'), 'file');

			if($attachmentFiles)
			{
				foreach ($attachmentFiles as $fileID)
				{
					$arRawFile = CFile::MakeFileArray($fileID);
					if (is_array($arRawFile))
					{
						$fileID = intval(CFile::SaveFile($arRawFile, 'crm'));
						if ($fileID > 0)
						{
							$attachment[] = $fileID;
						}
					}
				}
			}
		}

		if ($attachment)
		{
			$activityFields['STORAGE_TYPE_ID'] = $attachmentStorageType;
			$activityFields['STORAGE_ELEMENT_IDS'] = $attachment;
		}

		if(!($ID = CCrmActivity::Add($activityFields, false, false, array('REGISTER_SONET_EVENT' => true))))
		{
			$this->WriteToTrackingService(CCrmActivity::GetLastErrorMessage(), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$urn = CCrmActivity::PrepareUrn($activityFields);
		if($urn !== '')
		{
			CCrmActivity::Update($ID, array('URN'=> $urn), false, false, array('REGISTER_SONET_EVENT' => true));
		}

		$messageId = sprintf(
			'<crm.activity.%s@%s>', $urn,
			defined('BX24_HOST_NAME') ? BX24_HOST_NAME : (
			defined('SITE_SERVER_NAME') && SITE_SERVER_NAME
				? SITE_SERVER_NAME : \COption::getOptionString('main', 'server_name', '')
			)
		);

		CCrmActivity::SaveCommunications($ID, $arComms, $activityFields, false, false);

		// Creating Email -->

		// Try to resolve posting charset -->
		$postingCharset = '';
		$siteCharset = defined('LANG_CHARSET') ? LANG_CHARSET : (defined('SITE_CHARSET') ? SITE_CHARSET : 'windows-1251');
		$arSupportedCharset = explode(',', COption::GetOptionString('subscribe', 'posting_charset'));
		if(count($arSupportedCharset) === 0)
		{
			$postingCharset = $siteCharset;
		}
		else
		{
			foreach($arSupportedCharset as $curCharset)
			{
				if(strcasecmp($curCharset, $siteCharset) === 0)
				{
					$postingCharset = $curCharset;
					break;
				}
			}

			if($postingCharset === '')
			{
				$postingCharset = $arSupportedCharset[0];
			}
		}
		//<-- Try to resolve posting charset

		$postingData = array(
			'STATUS' => 'D',
			'FROM_FIELD' => $from,
			'TO_FIELD' => $cc,
			'BCC_FIELD' => $to,
			'SUBJECT' => $subject,
			'BODY_TYPE' => 'html',
			'BODY' => $messageHtml !== '' ? $messageHtml : GetMessage('CRM_EMAIL_ACTION_DEFAULT_DESCRIPTION'),
			'DIRECT_SEND' => 'Y',
			'SUBSCR_FORMAT' => 'html',
			'CHARSET' => $postingCharset
		);

		CCrmActivity::InjectUrnInMessage(
			$postingData,
			$urn,
			CCrmEMailCodeAllocation::GetCurrent()
		);

		$posting = new CPosting();
		$postingID = $posting->Add($postingData);
		if($postingID === false)
		{
			$errors[] = $posting->LAST_ERROR;
		}
		else
		{
			// Attaching files -->
			$arRawFiles = isset($activityFields['STORAGE_ELEMENT_IDS']) && !empty($activityFields['STORAGE_ELEMENT_IDS'])
				? \Bitrix\Crm\Integration\StorageManager::makeFileArray(
					$activityFields['STORAGE_ELEMENT_IDS'], $activityFields['STORAGE_TYPE_ID']
				)
				: array();

			foreach($arRawFiles as &$arRawFile)
			{
				if(isset($arRawFile['ORIGINAL_NAME']))
				{
					$arRawFile['name'] = $arRawFile['ORIGINAL_NAME'];
				}
				if(!$posting->SaveFile($postingID, $arRawFile))
				{
					$arErrors[] = $posting->LAST_ERROR;
					break;
				}
			}
			unset($arRawFile);
			// <-- Attaching files

			if(empty($errors))
			{
				$arUpdateFields = array(
					'ASSOCIATED_ENTITY_ID' => $postingID,
					'SETTINGS' => array('MESSAGE_HEADERS' => array('Message-Id' => $messageId))
				);
				CCrmActivity::Update($ID, $arUpdateFields, false, false);
			}
		}
		// <-- Creating Email

		if(!empty($errors))
		{
			CCrmActivity::Delete($ID);
			return CBPActivityExecutionStatus::Closed;
		}

		if (!empty($userImap['need_sync']) || !empty($crmImap['need_sync']))
		{
			$attachments = array();
			foreach ($arRawFiles as $item)
			{
				$attachments[] = array(
					'ID'           => $item['external_id'],
					'NAME'         => $item['ORIGINAL_NAME'] ?: $item['name'],
					'PATH'         => $item['tmp_name'],
					'CONTENT_TYPE' => $item['type'],
				);
			}

			class_exists('Bitrix\Mail\Helper');

			$rcpt = '';
			foreach ((array)$to as $item)
				$rcpt[] = \Bitrix\Mail\DummyMail::encodeHeaderFrom($item, SITE_CHARSET);
			$rcpt = join(', ', $rcpt);

			$outgoing = new \Bitrix\Mail\DummyMail(array(
				'CONTENT_TYPE' => 'html',
				'CHARSET'      => SITE_CHARSET,
				'HEADER'       => array(
					'From'       => $from,
					'To'         => $rcpt,
					'Subject'    => $subject,
					'Message-Id' => $messageId,
				),
				'BODY'         => $messageHtml ?: getMessage('CRM_EMAIL_ACTION_DEFAULT_DESCRIPTION'),
				'ATTACHMENT'   => $attachments
			));

			if (!empty($userImap['need_sync']))
				\Bitrix\Mail\Helper::addImapMessage($userImap, (string) $outgoing, $err);
			if (!empty($crmImap['need_sync']))
				\Bitrix\Mail\Helper::addImapMessage($crmImap, (string) $outgoing, $err);
		}

		// Sending Email -->
		if($posting->ChangeStatus($postingID, 'P'))
		{
			$rsAgents = CAgent::GetList(
				array('ID'=>'DESC'),
				array(
					'MODULE_ID' => 'subscribe',
					'NAME' => 'CPosting::AutoSend('.$postingID.',%',
				)
			);

			if(!$rsAgents->Fetch())
			{
				CAgent::AddAgent('CPosting::AutoSend('.$postingID.',true);', 'subscribe', 'N', 0);
			}
		}

		// Try add event to entity
		$CCrmEvent = new CCrmEvent();

		$eventText  = '';
		$eventText .= GetMessage('CRM_SEMA_EMAIL_SUBJECT').': '.$subject."\n\r";
		$eventText .= GetMessage('CRM_SEMA_EMAIL_FROM').': '.$from."\n\r";
		$eventText .= GetMessage('CRM_SEMA_EMAIL_TO').': '.implode(',', (array)$to)."\n\r\n\r";
		$eventText .= $messageHtml;
		// Register event only for owner
		$CCrmEvent->Add(
			array(
				'ENTITY' => array(
					$ownerID => array(
						'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($ownerTypeID),
						'ENTITY_ID' => $ownerID
					)
				),
				'EVENT_ID' => 'MESSAGE',
				'EVENT_TEXT_1' => $eventText,
				'FILES' => $arRawFiles
			)
		);
		// <-- Sending Email

		return CBPActivityExecutionStatus::Closed;
	}

	private function getFromEmail($userId)
	{
		$userImap = $crmImap = $defaultFrom = null;

		if (CModule::includeModule('mail'))
		{
			$res = \Bitrix\Mail\MailboxTable::getList(array(
				'select' => array('*', 'LANG_CHARSET' => 'SITE.CULTURE.CHARSET'),
				'filter' => array(
					'=LID'    => SITE_ID,
					'=ACTIVE' => 'Y',
					array(
						'LOGIC' => 'OR',
						'=USER_ID' => $userId,
						array(
							'USER_ID'      => 0,
							'=SERVER_TYPE' => 'imap',
						),
					),
				),
				'order' => array('TIMESTAMP_X' => 'ASC'), // @TODO: order by ID
			));

			while ($mailbox = $res->fetch())
			{
				if (!empty($mailbox['OPTIONS']['flags']) && in_array('crm_connect', (array) $mailbox['OPTIONS']['flags']))
				{
					$mailbox['EMAIL_FROM'] = null;
					if (check_email($mailbox['NAME'], true))
						$mailbox['EMAIL_FROM'] = strtolower($mailbox['NAME']);
					elseif(check_email($mailbox['LOGIN'], true))
						$mailbox['EMAIL_FROM'] = strtolower($mailbox['LOGIN']);

					if ($mailbox['USER_ID'] > 0)
						$userImap = $mailbox;
					else
						$crmImap = $mailbox;
				}
			}

			$defaultFrom = \Bitrix\Mail\User::getDefaultEmailFrom();
		}

		$crmEmail = \CCrmMailHelper::extractEmail(\COption::getOptionString('crm', 'mail', ''));

		$cc = '';

		if (!empty($userImap))
		{
			$from = $userImap['EMAIL_FROM'] ?: $defaultFrom;
			$userImap['need_sync'] = true;
		}
		elseif (!empty($crmImap))
		{
			$from = $crmImap['EMAIL_FROM'] ?: $defaultFrom;
			$crmImap['need_sync'] = true;
		}
		else
		{
			$from = $crmEmail;
			$cc   = $crmEmail;
		}

		if ($from == '')
			$from = CUserOptions::GetOption('crm', 'activity_email_addresser', '', $userId);

		if ($from == '')
			$from = $defaultFrom;

		return array($from, $userImap, $crmImap, $cc);
	}

	private function getToEmail($entityTypeId, $entityId)
	{
		$to = '';
		if ($entityTypeId == \CCrmOwnerType::Lead)
		{
			$to = $this->getEntityEmail($entityTypeId, $entityId);
		}
		elseif ($entityTypeId == \CCrmOwnerType::Deal)
		{
			$entity = \CCrmDeal::GetByID($entityId, false);
			$entityContactID = isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
			$entityCompanyID = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;

			if($entityContactID > 0)
			{
				$to = $this->getEntityEmail(\CCrmOwnerType::Contact, $entityContactID);
			}
			if (empty($to) && $entityCompanyID > 0)
			{
				$to = $this->getEntityEmail(\CCrmOwnerType::Company, $entityCompanyID);
			}
		}

		return $to;
	}

	private function getEntityEmail($entityTypeId, $entityId)
	{
		$result = '';
		$dbResFields = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => \CCrmOwnerType::ResolveName($entityTypeId),
				'ELEMENT_ID' => $entityId,
				'TYPE_ID' => \CCrmFieldMulti::EMAIL
			)
		);

		while($arField = $dbResFields->Fetch())
		{
			if(empty($arField['VALUE']))
			{
				continue;
			}

			$result = $arField['VALUE'];
			break;
		}

		return $result;
	}

	private function getEntityTypeId()
	{
		$id = $this->GetDocumentId();
		$typeId = \CCrmOwnerType::Undefined;
		if ($id[1] == 'CCrmDocumentDeal')
			$typeId = \CCrmOwnerType::Deal;
		elseif ($id[1] == 'CCrmDocumentLead')
			$typeId = \CCrmOwnerType::Lead;
		return $typeId;
	}

	private function getEntityId()
	{
		//extract real entity id from string like LEAD_123 or DEAL_345
		$id = $this->GetDocumentId();
		$pairs = explode('_', $id[2]);

		return count($pairs) > 1 ? $pairs[1] : $pairs[0];
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (empty($arTestProperties["MessageText"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageText", "message" => GetMessage("CRM_SEMA_EMPTY_PROP"));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("crm"))
			return '';

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId
		));

		$map = array(
			'Subject' => array(
				'Name' => GetMessage('CRM_SEMA_EMAIL_SUBJECT'),
				'FieldName' => 'subject',
				'Type' => 'string',
				'Required' => true
			),
			'MessageText' => array(
				'Name' => GetMessage('CRM_SEMA_MESSAGE_TEXT'),
				'FieldName' => 'message_text',
				'Type' => 'text',
				'Required' => true
			),
			'MessageTextType' => array(
				'Name' => GetMessage('CRM_SEMA_MESSAGE_TEXT'),
				'FieldName' => 'message_text_type',
				'Type' => 'select',
				'Options' => array(
					self::TEXT_TYPE_BBCODE => 'BBCODE',
					self::TEXT_TYPE_HTML => 'HTML'
				),
				'Default' => self::TEXT_TYPE_BBCODE
			),
			'AttachmentType' => array(
				'Name' => GetMessage('CRM_SEMA_ATTACHMENT_TYPE'),
				'FieldName' => 'attachment_type',
				'Type' => 'select',
				'Options' => array(
					static::ATTACHMENT_TYPE_FILE => GetMessage('CRM_SEMA_ATTACHMENT_FILE'),
					static::ATTACHMENT_TYPE_DISK => GetMessage('CRM_SEMA_ATTACHMENT_DISK')
				)
			),
			'Attachment' => array(
				'Name' => GetMessage('CRM_SEMA_ATTACHMENT'),
				'FieldName' => 'attachment',
				'Type' => 'file',
				'Multiple' => true
			)
		);
		$dialog->setMap($map);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = array();

		$properties = array(
			'Subject' => (string)$arCurrentValues["subject"],
			'MessageText' => (string)$arCurrentValues["message_text"],
			'MessageTextType' => (string)$arCurrentValues["message_text_type"],
			'AttachmentType' => (string)$arCurrentValues["attachment_type"]
		);

		$properties['Attachment'] = array();

		if ($properties['AttachmentType'] === static::ATTACHMENT_TYPE_DISK)
		{
			foreach ((array)$arCurrentValues["attachment"] as $attachmentId)
			{
				$attachmentId = (int)$attachmentId;
				if ($attachmentId > 0)
				{
					$properties['Attachment'][] = $attachmentId;
				}
			}
		}
		else
		{
			$properties['Attachment'] = isset($arCurrentValues["attachment"])
				? $arCurrentValues["attachment"] : $arCurrentValues["attachment_text"];
		}

		if (
			$properties['MessageTextType'] !== self::TEXT_TYPE_BBCODE
			&& $properties['MessageTextType'] !== self::TEXT_TYPE_HTML
		)
		{
			$properties['MessageTextType'] = self::TEXT_TYPE_BBCODE;
		}

		if (count($errors) > 0)
			return false;

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}
}