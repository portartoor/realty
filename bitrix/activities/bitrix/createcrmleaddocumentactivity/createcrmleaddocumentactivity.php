<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CreateDocumentActivity');

class CBPCreateCrmLeadDocumentActivity
	extends CBPCreateDocumentActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
			CBPActivityExecutionStatus::Closed;

		$rootActivity = $this->GetRootActivity();

		$documentId = array();
		$documentId[0] = 'crm';
		$documentId[1] = 'CCrmDocumentLead';
		$documentId[2] = 'LEAD';

		$documentService = $this->workflow->GetService('DocumentService');
		$r = $documentService->CreateDocument($documentId, $this->Fields);
		return CBPActivityExecutionStatus::Closed;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null)
	{
		$documentType[0] = 'crm';
		$documentType[1] = 'CCrmDocumentLead';
		$documentType[2] = 'LEAD';
		return parent::GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $formName, $popupWindow);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$documentType[0] = 'crm';
		$documentType[1] = 'CCrmDocumentLead';
		$documentType[2] = 'LEAD';
		return parent::GetPropertiesDialogValues($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $arErrors);
	}
}
?>