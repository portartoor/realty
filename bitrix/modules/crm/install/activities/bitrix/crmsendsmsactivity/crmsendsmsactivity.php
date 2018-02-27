<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Rest\Sqs;

class CBPCrmSendSmsActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"ProviderId" => '',
			"MessageText" => '',
		);
	}

	public function Execute()
	{
		if (!$this->MessageText || !CModule::IncludeModule("crm"))
			return CBPActivityExecutionStatus::Closed;

		if (!CModule::includeModule('rest') || !\Bitrix\Rest\OAuthService::getEngine()->isRegistered())
			return CBPActivityExecutionStatus::Closed;

		$provider = null;
		list($appId, $providerCode) = explode('|', (string)$this->ProviderId);
		if ($appId && $providerCode)
		{
			$provider = \Bitrix\Bizproc\RestProviderTable::getList(
				array('filter' => array('APP_ID' => $appId, 'CODE' => $providerCode))
			)->fetch();
		}

		if (!$provider)
		{
			$this->WriteToTrackingService(GetMessage("CRM_SSMSA_NO_PROVIDER"), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$session = \Bitrix\Rest\Event\Session::get();
		if(!$session)
		{
			$this->WriteToTrackingService(GetMessage("CRM_SSMSA_REST_SESSION_ERROR"), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$dbRes = \Bitrix\Rest\AppTable::getList(array(
			'filter' => array(
				'=CLIENT_ID' => $provider['APP_ID'],
			)
		));
		$application = $dbRes->fetch();

		if (!$application)
		{
			$this->WriteToTrackingService(GetMessage("CRM_SSMSA_NO_PROVIDER"), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$appStatus = \Bitrix\Rest\AppTable::getAppStatusInfo($application, '');
		if($appStatus['PAYMENT_ALLOW'] === 'N')
		{
			$this->WriteToTrackingService(GetMessage("CRM_SSMSA_PAYMENT_REQUIRED"), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->GetDocumentId();
		list($typeName, $id) = explode('_', $documentId[2]);

		$auth = array(
			'CODE' => $provider['CODE'],
			\Bitrix\Rest\Event\Session::PARAM_SESSION => $session,
			\Bitrix\Rest\OAuth\Auth::PARAM_LOCAL_USER => \CCrmOwnerType::GetResponsibleID(
				\CCrmOwnerType::ResolveID($typeName), $id
			),
			"application_token" => \CRestUtil::getApplicationToken($application),
		);

		$phoneNumber = $this->getPhoneNumber();

		if (!$phoneNumber)
		{
			$this->WriteToTrackingService(GetMessage("CRM_SSMSA_EMPTY_PHONE_NUMBER"), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$queryItems = array(
			Sqs::queryItem(
				$provider['APP_ID'],
				$provider['HANDLER'],
				array(
					'workflow_id' => $this->getWorkflowInstanceId(),
					'type' => $provider['TYPE'],
					'code' => $provider['CODE'],
					'document_id' => $this->GetDocumentId(),
					'document_type' => $this->GetDocumentType(),
					'properties' => array(
						'phone_number' => $phoneNumber,
						'message_text' => $this->MessageText,
					),
					'ts' => time(),
				),
				$auth,
				array(
					"sendAuth" => true,
					"sendRefreshToken" => false,
					"category" => Sqs::CATEGORY_BIZPROC,
				)
			),
		);

		\Bitrix\Rest\OAuthService::getEngine()->getClient()->sendEvent($queryItems);

		return CBPActivityExecutionStatus::Closed;
	}

	private function getPhoneNumber()
	{
		$documentId = $this->GetDocumentId();
		$communications = array();

		switch ($documentId[1])
		{
			case 'CCrmDocumentDeal':
				$communications = $this->getDealCommunications((int)str_replace('DEAL_', '', $documentId[2]));
				break;
			case 'CCrmDocumentLead':
				$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Lead, (int)str_replace('LEAD_', '', $documentId[2]));
				break;
		}

		$communications = array_slice($communications, 0, 1);
		return $communications? $communications[0]['VALUE'] : null;
	}

	private function getDealCommunications($id)
	{
		$communications = array();

		$entity = CCrmDeal::GetByID($id);
		if(!$entity)
		{
			return array();
		}

		$entityContactID = isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
		$entityCompanyID = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;

		if ($entityContactID > 0)
		{
			$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Contact, $entityContactID);
		}

		if (empty($communications) && $entityCompanyID > 0)
		{
			$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Company, $entityCompanyID);
		}

		return $communications;
	}

	private function getCommunicationsFromFM($entityTypeId, $entityId)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
		$communications = array();

		$iterator = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => $entityTypeName,
				'ELEMENT_ID' => $entityId,
				'TYPE_ID' => 'PHONE'
			)
		);

		while ($row = $iterator->fetch())
		{
			if (empty($row['VALUE']))
				continue;

			$communications[] = array(
				'ENTITY_ID' => $entityId,
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_TYPE' => $entityTypeName,
				'TYPE' => 'PHONE',
				'VALUE' => $row['VALUE'],
				'VALUE_TYPE' => $row['VALUE_TYPE']
			);
		}

		return $communications;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (empty($arTestProperties["ProviderId"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "ProviderId", "message" => GetMessage("CRM_SSMSA_EMPTY_PROVIDER"));
		}

		if (empty($arTestProperties["MessageText"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "MessageText", "message" => GetMessage("CRM_SSMSA_EMPTY_TEXT"));
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

		$dialog->setMap(array(
			'MessageText' => array(
				'Name' => GetMessage('CRM_SSMSA_MESSAGE_TEXT'),
				'FieldName' => 'message_text',
				'Type' => 'text',
				'Required' => true
			),
			'ProviderId' => array(
				'Name' => GetMessage('CRM_SSMSA_PROVIDER'),
				'FieldName' => 'provider_id',
				'Type' => 'select',
				'Required' => true,
				'Options' => self::getProvidersList()
			)
		));

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = Array();

		$arProperties = array(
			'MessageText' => (string)$arCurrentValues["message_text"],
			'ProviderId' => (string)$arCurrentValues["provider_id"]
		);

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	private static function getProvidersList()
	{
		$list = array();

		$result = \Bitrix\Bizproc\RestProviderTable::getList(array(
			'select' => array('APP_ID', 'CODE', 'NAME', 'APP_NAME'),
			'order' => array('APP_NAME' => 'ASC', 'NAME' => 'ASC')
		));

		while ($row = $result->fetch())
		{
			$id = $row['APP_ID'].'|'.$row['CODE'];
			$list[$id] = sprintf('[%s] %s',
				\Bitrix\Bizproc\RestProviderTable::getLocalization($row['APP_NAME'], LANGUAGE_ID),
				\Bitrix\Bizproc\RestProviderTable::getLocalization($row['NAME'], LANGUAGE_ID)
			);
		}

		return $list;
	}
}