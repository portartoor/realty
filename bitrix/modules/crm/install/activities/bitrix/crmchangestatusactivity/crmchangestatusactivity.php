<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPCrmChangeStatusActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"TargetStatus" => null,
		);
	}

	public function Execute()
	{
		if ($this->TargetStatus == null || !CModule::IncludeModule("crm"))
			return CBPActivityExecutionStatus::Closed;

		$documentId = $this->GetDocumentId();
		$fields = null;
		$targetStatus = (string)$this->TargetStatus;
		$automationTarget = null;
		$id = null;

		switch ($documentId[1])
		{
			case 'CCrmDocumentDeal':
				$fields = array('STAGE_ID' => $targetStatus);
				$automationTarget = \Bitrix\Crm\Automation\Factory::createTarget(\CCrmOwnerType::Deal);
				$id = (int)str_replace('DEAL_', '', $documentId[2]);
				break;

			case 'CCrmDocumentLead':
				$fields = array('STATUS_ID' => $targetStatus);
				$automationTarget = \Bitrix\Crm\Automation\Factory::createTarget(\CCrmOwnerType::Lead);
				$id = (int)str_replace('LEAD_', '', $documentId[2]);
				break;
		}

		if ($fields && $automationTarget && $id > 0)
		{
			$automationTarget->setEntityById($id);
			$currentStatus = $automationTarget->getEntityStatus();
			$allStatuses = $automationTarget->getEntityStatuses();
			$currentKey = array_search($currentStatus, $allStatuses);
			$targetKey = array_search($targetStatus, $allStatuses);

			if ($currentKey !== false && $targetKey !== false && $targetKey > $currentKey)
			{
				$runtime = CBPRuntime::GetRuntime();
				/** @var CBPDocumentService $ds */
				$ds = $runtime->GetService('DocumentService');

				$ds->UpdateDocument($documentId, $fields);
			}
		}

		CBPDocument::TerminateWorkflow(
			$this->GetWorkflowInstanceId(),
			$documentId,
			$arErrorsTmp,
			GetMessage('CRM_CHANGE_STATUS_TERMINATED')
		);

		//Stop running queue
		throw new Exception("TerminateWorkflow");

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (empty($arTestProperties["TargetStatus"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "TargetStatus", "message" => GetMessage("CRM_CHANGE_STATUS_EMPTY_PROP"));
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

		$dialog->setMapCallback(array(__CLASS__, 'getPropertiesDialogMap'));

		return $dialog;
	}

	/**
	 * @param \Bitrix\Bizproc\Activity\PropertiesDialog $dialog
	 * @return array Map.
	 */
	public static function getPropertiesDialogMap($dialog)
	{
		if (!CModule::IncludeModule('crm'))
			return array();

		$documentStatuses = array();
		$documentType = $dialog->getDocumentType();
		$context = $dialog->getContext();
		$categoryId = isset($context['ENTITY_CATEGORY_ID']) ? (int)$context['ENTITY_CATEGORY_ID'] : null;
		$fieldName = '';

		switch ($documentType[1])
		{
			case 'CCrmDocumentDeal':
				if ($categoryId !== null)
					$documentStatuses = \Bitrix\Crm\Category\DealCategory::getStageList($categoryId);
				else
					$documentStatuses = \Bitrix\Crm\Category\DealCategory::getFullStageList();
				$fieldName = GetMessage('CRM_CHANGE_STATUS_STAGE');
				break;

			case 'CCrmDocumentLead':
				$documentStatuses = CCrmStatus::GetStatusList('STATUS');
				$fieldName = GetMessage('CRM_CHANGE_STATUS_STATUS');
				break;
		}

		return array(
			'TargetStatus' => array(
				'Name' => $fieldName,
				'FieldName' => 'target_status',
				'Type' => 'select',
				'Required' => true,
				'Options' => $documentStatuses
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = Array();

		$arProperties = array(
			'TargetStatus' => $arCurrentValues['target_status']
		);

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}
}