<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPCrmCreateRequestActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"Subject" => null,
			"StartTime" => null,
			"EndTime" => null,
			"IsImportant" => null,
			"Description" => null,
			"Location" => null,
			"NotifyType" => null,
			"NotifyValue" => null,
			"Responsible" => null,
			"AutoComplete" => null,
			//return
			"Id" => null
		);

		$this->SetPropertiesTypes(array(
			'Id' => array(
				'Type' => 'int'
			)
		));
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("crm"))
			return CBPActivityExecutionStatus::Closed;

		$start = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL');

		$activityFields = array(
			'START_TIME' => $start,
			'END_TIME' => $start,
			'TYPE_ID' =>  \CCrmActivityType::Provider,
			'SUBJECT' => (string)$this->Subject,
			'PRIORITY' => ($this->IsImportant == 'Y') ? \CCrmActivityPriority::High : CCrmActivityPriority::Medium,
			'DESCRIPTION' => (string)$this->Description,
			'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
			'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\Request::getId(),
			'PROVIDER_TYPE_ID' => \Bitrix\Crm\Activity\Provider\Request::getTypeId(array()),
			'RESPONSIBLE_ID' => $this->getResponsibleId(),
			'AUTOCOMPLETE_RULE' => (
			$this->AutoComplete == 'Y' ?
				\Bitrix\Crm\Activity\AutocompleteRule::AUTOMATION_ON_STATUS_CHANGED
				: \Bitrix\Crm\Activity\AutocompleteRule::NONE
			)
		);

		$activityFields['BINDINGS'] = $this->getBindings();
		$communications = $this->getCommunications();

		if(!($id = CCrmActivity::Add($activityFields, false, true, array('REGISTER_SONET_EVENT' => true))))
		{
			$this->WriteToTrackingService(CCrmActivity::GetLastErrorMessage(), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		if ($id > 0)
		{
			$this->Id = $id;
			if ($communications)
				CCrmActivity::SaveCommunications($id, $communications, $activityFields, false, false);
			$this->WriteToTrackingService($id, 0, CBPTrackingType::AttachedEntity);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function getResponsibleId()
	{
		$id = $this->Responsible;
		if (!$id)
		{
			$runtime = CBPRuntime::GetRuntime();
			$runtime->StartRuntime();
			/** @var CBPDocumentService $documentService */
			$documentService = $runtime->GetService('DocumentService');
			$document = $documentService->GetDocument($this->GetDocumentId());

			$id = isset($document['ASSIGNED_BY_ID']) ? $document['ASSIGNED_BY_ID'] : null;

		}

		return CBPHelper::ExtractUsers($id, $this->GetDocumentId(), true);
	}

	private function getBindings()
	{
		$documentId = $this->GetDocumentId();
		list($typeName, $id) = explode('_', $documentId[2]);

		return array(
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($typeName),
				'OWNER_ID' => $id
			)
		);
	}

	private function getCommunications()
	{
		$documentId = $this->GetDocumentId();
		list($typeName, $id) = explode('_', $documentId[2]);
		$communications = array();

		if ($typeName !== CCrmOwnerType::DealName)
		{
				$communications[] = array(
					'ENTITY_ID' => $id,
					'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($typeName),
					'ENTITY_TYPE' => $typeName,
					'TYPE' => ''
				);
		}

		return $communications;
	}

	public static function ValidateProperties($testProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = array();
		$fieldsMap = static::getPropertiesDialogMap();

		foreach ($fieldsMap as $propertyKey => $fieldProperties)
		{
			if (
				CBPHelper::getBool($fieldProperties['Required'])
				&& CBPHelper::isEmptyValue($testProperties[$propertyKey])
			)
				$errors[] = array(
					"code" => "NotExist",
					"parameter" => $propertyKey,
					"message" => GetMessage("CRM_CREATE_REQUEST_EMPTY_PROP", array('#PROPERTY#' => $fieldProperties['Name']))
				);
		}

		return array_merge($errors, parent::ValidateProperties($testProperties, $user));
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

		$dialog->setMap(static::getPropertiesDialogMap());

		return $dialog;
	}

	private static function getPropertiesDialogMap()
	{
		return array(
			'Subject' => array(
				'Name' => GetMessage('CRM_CREATE_REQUEST_SUBJECT'),
				'FieldName' => 'subject',
				'Type' => 'string',
				'Required' => true
			),
			'Description' => array(
				'Name' => GetMessage('CRM_CREATE_REQUEST_DESCRIPTION'),
				'FieldName' => 'description',
				'Type' => 'text',
				'Required' => true
			),
			'Responsible' => array(
				'Name' => GetMessage('CRM_CREATE_REQUEST_RESPONSIBLE_ID'),
				'FieldName' => 'responsible',
				'Type' => 'user',
				'Default' => 'author'
			),
			'IsImportant' => array(
				'Name' => GetMessage('CRM_CREATE_REQUEST_IS_IMPORTANT'),
				'FieldName' => 'is_important',
				'Type' => 'bool'
			),
			'AutoComplete' => array(
				'Name' => GetMessage('CRM_CREATE_REQUEST_AUTO_COMPLETE'),
				'FieldName' => 'auto_completed',
				'Type' => 'bool',
				'Default' => 'N'
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $currentValues, &$errors)
	{
		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$errors = $properties = array();
		/** @var CBPDocumentService $documentService */
		$documentService = $runtime->GetService('DocumentService');

		$fieldsMap = static::getPropertiesDialogMap();
		foreach ($fieldsMap as $propertyKey => $fieldProperties)
		{
			$field = $documentService->getFieldTypeObject($documentType, $fieldProperties);
			if (!$field)
				continue;

			$properties[$propertyKey] = $field->extractValue(
				array('Field' => $fieldProperties['FieldName']),
				$currentValues,
				$errors
			);
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
			return false;

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}
}