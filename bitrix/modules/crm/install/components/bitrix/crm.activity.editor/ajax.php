<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}

IncludeModuleLangFile(__FILE__);

/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'SAVE_ACTIVITY' - save activity (CALL, MEETING)
 * 'SAVE_EMAIL'
 * 'SET_NOTIFY' - change notification settings
 * 'SET_PRIORITY'
 * 'COMPLETE' - mark activity as completed
 * 'DELETE' - delete activity
 * 'GET_ENTITY_COMMUNICATIONS' - get entity communications
 * 'GET_ACTIVITY_COMMUNICATIONS_PAGE'
 * 'GET_TASK'
 * 'SEARCH_COMMUNICATIONS'
 * 'GET_ACTIVITIES'
 * 'GET_WEBDAV_ELEMENT_INFO'
 * 'PREPARE_MAIL_TEMPLATE'
 */

global $DB, $APPLICATION;

$curUser = CCrmSecurityHelper::GetCurrentUser();
if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
CUtil::JSPostUnescape();
if(!function_exists('__CrmActivityEditorEndResonse'))
{
	function __CrmActivityEditorEndResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}


$GLOBALS['APPLICATION']->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if(strlen($action) == 0)
{
	__CrmActivityEditorEndResonse(array('ERROR' => 'Invalid data!'));
}

function GetCrmActivityCommunications($ID)
{
	$communications = CCrmActivity::GetCommunications($ID);
	$communicationData = array();
	if(is_array($communications))
	{
		foreach($communications as &$comm)
		{
			CCrmActivity::PrepareCommunicationInfo($comm);
			$datum = array(
				'id' => $comm['ID'],
				'type' => $comm['TYPE'],
				'value' => $comm['VALUE'],
				'entityId' => $comm['ENTITY_ID'],
				'entityType' => CCrmOwnerType::ResolveName($comm['ENTITY_TYPE_ID']),
				'entityTitle' => $comm['TITLE'],
				'entityUrl' => CCrmOwnerType::GetShowUrl($comm['ENTITY_TYPE_ID'], $comm['ENTITY_ID'])
			);

			if($datum['type'] === 'PHONE' && CCrmSipHelper::checkPhoneNumber($datum['value']))
			{
				$datum['enableSip'] = true;
			}

			$communicationData[] = &$datum;
			unset($datum);
		}
		unset($comm);
	}

	return array('DATA' => array(
		'ID' => $ID,
		'COMMUNICATIONS' => $communicationData
		)
	);
}
function GetCrmActivityCommunicationsPage($ID, $pageSize, $pageNumber)
{
	$dbRes = CCrmActivity::GetCommunicationList(
		array('ID' => 'ASC'),
		array('ACTIVITY_ID' => $ID),
		false,
		array('bShowAll' => false, 'nPageSize' => $pageSize, 'iNumPage' => $pageNumber)
	);

	$communicationData = array();
	while($result = $dbRes->Fetch())
	{
		$result['ENTITY_SETTINGS'] = isset($result['ENTITY_SETTINGS']) && $result['ENTITY_SETTINGS'] !== '' ? unserialize($result['ENTITY_SETTINGS']) : array();
		CCrmActivity::PrepareCommunicationInfo($result);
		$communicationData[] = array(
			'id' => $result['ID'],
			'type' => $result['TYPE'],
			'value' => $result['VALUE'],
			'entityId' => $result['ENTITY_ID'],
			'entityType' => CCrmOwnerType::ResolveName($result['ENTITY_TYPE_ID']),
			'entityTitle' => $result['TITLE'],
			'entityUrl' => CCrmOwnerType::GetShowUrl($result['ENTITY_TYPE_ID'], $result['ENTITY_ID'])
		);
	}

	return array(
		'DATA' => array(
			'ID' => $ID,
			'PAGE_SIZE'=> $dbRes->NavPageSize,
			'PAGE_NUMBER'=> $dbRes->NavPageNomer,
			'PAGE_COUNT'=> $dbRes->NavPageCount,
			'COMMUNICATIONS' => $communicationData
		)
	);
}
function GetCrmEntityCommunications($entityType, $entityID, $communicationType)
{
	if($entityType === 'LEAD')
	{
		$data = array(
			'ownerEntityType' => 'LEAD',
			'ownerEntityId' => $entityID,
			'entityType' => 'LEAD',
			'entityId' => $entityID,
			'entityTitle' => "{$entityType}_{$entityID}",
			'entityDescription' => '',
			'tabId' => 'main',
			'communications' => array()
		);

		$entity = CCrmLead::GetByID($entityID);
		if(!$entity)
		{
			return array('ERROR' => 'Invalid data');
		}

		// Prepare title
		$title = isset($entity['TITLE']) ? $entity['TITLE'] : '';
		$honorific = isset($entity['HONORIFIC']) ? $entity['HONORIFIC'] : '';
		$name = isset($entity['NAME']) ? $entity['NAME'] : '';
		$secondName = isset($entity['SECOND_NAME']) ? $entity['SECOND_NAME'] : '';
		$lastName = isset($entity['LAST_NAME']) ? $entity['LAST_NAME'] : '';

		if($title !== '')
		{
			$data['entityTitle'] = $title;
			$data['entityDescription'] = CCrmLead::PrepareFormattedName(
				array(
					'HONORIFIC' => $honorific,
					'NAME' => $name,
					'SECOND_NAME' => $secondName,
					'LAST_NAME' => $lastName
				)
			);
		}
		else
		{
			$data['entityTitle'] = CCrmLead::PrepareFormattedName(
				array(
					'HONORIFIC' => $honorific,
					'NAME' => $name,
					'SECOND_NAME' => $secondName,
					'LAST_NAME' => $lastName
				)
			);
			$data['entityDescription'] = '';
		}

		// Try to load entity communications
		if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::ResolveID($entityType), $entityID))
		{
			return array('ERROR' => GetMessage('CRM_PERMISSION_DENIED'));
		}

		if($communicationType !== '')
		{
			$dbResFields = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityID, 'TYPE_ID' =>  $communicationType)
			);

			while($arField = $dbResFields->Fetch())
			{
				if(empty($arField['VALUE']))
				{
					continue;
				}

				$comm = array('type' => $communicationType, 'value' => $arField['VALUE']);
				$data['communications'][] = $comm;
			}
		}

		return array(
			'DATA' => array(
				'TABS' => array(
					array(
						'id' => 'lead',
						'title' => GetMessage('CRM_COMMUNICATION_TAB_LEAD'), 'active' => true, 'items' => array($data))
				)
			)
		);
	}
	elseif($entityType === 'DEAL')
	{
		$entity = CCrmDeal::GetByID($entityID);
		if(!$entity)
		{
			return array('ERROR' => 'Invalid data');
		}

		$dealData = array();

		// Prepare company data
		$entityCompanyData = null;
		$entityCompanyID =  isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;
		$entityCompany = $entityCompanyID > 0 ? CCrmCompany::GetByID($entityCompanyID) : null;

		if(is_array($entityCompany))
		{
			$entityCompanyData = array(
				'ownerEntityType' => 'DEAL',
				'ownerEntityId' => $entityID,
				'entityType' => 'COMPANY',
				'entityId' => $entityCompanyID,
				'entityTitle' => isset($entityCompany['TITLE']) ? $entityCompany['TITLE'] : '',
				'entityDescription' => '',
				'communications' => array()
			);

			if($communicationType !== '')
			{
				$entityCompanyComms = CCrmActivity::PrepareCommunications('COMPANY', $entityCompanyID, $communicationType);

				foreach($entityCompanyComms as &$entityCompanyComm)
				{
					$comm = array(
						'type' => $entityCompanyComm['TYPE'],
						'value' => $entityCompanyComm['VALUE']
					);

					$entityCompanyData['communications'][] = $comm;
				}
				unset($entityCompanyComm);
			}
		}

		// Try to get contact of deal
		$entityContactID =  isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
		if($entityContactID > 0)
		{
			$entityContact = CCrmContact::GetByID($entityContactID);
			if(is_array($entityContact))
			{
				$item = array(
					'ownerEntityType' => 'DEAL',
					'ownerEntityId' => $entityID,
					'entityType' => 'CONTACT',
					'entityId' => $entityContactID,
					'entityTitle' => CCrmContact::PrepareFormattedName(
						array(
							'HONORIFIC' => isset($entityContact['HONORIFIC']) ? $entityContact['HONORIFIC'] : '',
							'NAME' => isset($entityContact['NAME']) ? $entityContact['NAME'] : '',
							'LAST_NAME' => isset($entityContact['LAST_NAME']) ? $entityContact['LAST_NAME'] : '',
							'SECOND_NAME' => isset($entityContact['SECOND_NAME']) ? $entityContact['SECOND_NAME'] : ''
						)
					),
					'tabId' => 'deal',
					'communications' => array()
				);

				$entityCompany = isset($entityContact['COMPANY_ID']) ? CCrmCompany::GetByID($entityContact['COMPANY_ID']) : null;
				if($entityCompany && isset($entityCompany['TITLE']))
				{
					$item['entityDescription'] = $entityCompany['TITLE'];
				}

				if($communicationType !== '')
				{
					$entityContactComms = CCrmActivity::PrepareCommunications('CONTACT', $entityContactID, $communicationType);
					foreach($entityContactComms as &$entityContactComm)
					{
						$comm = array(
							'type' => $entityContactComm['TYPE'],
							'value' => $entityContactComm['VALUE']
						);

						$item['communications'][] = $comm;
					}
					unset($entityContactComm);
				}

				if($communicationType === '' || !empty($item['communications']))
				{
					$dealData["CONTACT_{$entityContactID}"] = $item;
				}
			}
		}

		if($entityCompanyData && !empty($entityCompanyData['communications']))
		{
			$dealData['COMPANY_'.$entityCompanyID] = $entityCompanyData;
			$dealData['COMPANY_'.$entityCompanyID]['tabId'] = 'deal';
		}

		// Try to get previous communications
		$entityComms = CCrmActivity::GetCommunicationsByOwner('DEAL', $entityID, $communicationType);
		foreach($entityComms as &$entityComm)
		{
			CCrmActivity::PrepareCommunicationInfo($entityComm);
			$key = "{$entityComm['ENTITY_TYPE']}_{$entityComm['ENTITY_ID']}";
			if(!isset($dealData[$key]))
			{
				$dealData[$key] = array(
					'ownerEntityType' => 'DEAL',
					'ownerEntityId' => $entityID,
					'entityType' => CCrmOwnerType::ResolveName($entityComm['ENTITY_TYPE_ID']),
					'entityId' => $entityComm['ENTITY_ID'],
					'entityTitle' => isset($entityComm['TITLE']) ? $entityComm['TITLE'] : '',
					'entityDescription' => isset($entityComm['DESCRIPTION']) ? $entityComm['DESCRIPTION'] : '',
					'tabId' => 'deal',
					'communications' => array()
				);
			}

			if($communicationType !== '')
			{
				$commFound = false;
				foreach($dealData[$key]['communications'] as &$comm)
				{
					if($comm['value'] === $entityComm['VALUE'])
					{
						$commFound = true;
						break;
					}
				}
				unset($comm);

				if($commFound)
				{
					continue;
				}

				$comm = array(
					'type' => $entityComm['TYPE'],
					'value' => $entityComm['VALUE']
				);

				$dealData[$key]['communications'][] = $comm;
			}
		}
		unset($entityComm);

		$companyData = array();
		// Try to get contacts of company
		if($entityCompany > 0)
		{
			$entityComms = CCrmActivity::GetCompanyCommunications($entityCompanyID, $communicationType);
			foreach($entityComms as &$entityComm)
			{
				CCrmActivity::PrepareCommunicationInfo($entityComm);
				$key = "{$entityComm['ENTITY_TYPE']}_{$entityComm['ENTITY_ID']}";
				if(!isset($companyData[$key]))
				{
					$companyData[$key] = array(
						'ownerEntityType' => 'DEAL',
						'ownerEntityId' => $entityID,
						'entityType' => CCrmOwnerType::ResolveName($entityComm['ENTITY_TYPE_ID']),
						'entityId' => $entityComm['ENTITY_ID'],
						'entityTitle' => isset($entityComm['TITLE']) ? $entityComm['TITLE'] : '',
						'entityDescription' => isset($entityComm['DESCRIPTION']) ? $entityComm['DESCRIPTION'] : '',
						'tabId' => 'company',
						'communications' => array()
					);
				}

				if($communicationType !== '')
				{
					$comm = array(
						'type' => $entityComm['TYPE'],
						'value' => $entityComm['VALUE']
					);

					$companyData[$key]['communications'][] = $comm;
				}
			}
			unset($entityComm);
		}

		if($entityCompanyData && !empty($entityCompanyData['communications']))
		{
			$companyData['COMPANY_'.$entityCompanyID] = $entityCompanyData;
			$companyData['COMPANY_'.$entityCompanyID]['tabId'] = 'company';
		}

		return array(
			'DATA' => array(
				'TABS' => array(
					array(
						'id' => 'deal',
						'title' => GetMessage('CRM_COMMUNICATION_TAB_DEAL'),
						'active' => true,
						'items' => array_values($dealData)
					),
					array(
						'id' => 'company',
						'title' => GetMessage('CRM_COMMUNICATION_TAB_COMPANY'),
						'items' => array_values($companyData)
					)
				)
			)
		);
	}
	elseif($entityType === 'COMPANY')
	{
		$companyData = array();

		$entity = CCrmCompany::GetByID($entityID);
		if(!$entity)
		{
			return array('ERROR' => 'Invalid data');
		}

		$companyItem = array(
			'ownerEntityType' => 'COMPANY',
			'ownerEntityId' => $entityID,
			'entityType' => 'COMPANY',
			'entityId' => $entityID,
			'entityTitle' => isset($entity['TITLE']) ? $entity['TITLE'] : "{$entityType}_{$entityID}",
			'entityDescription' => '',
			'tabId' => 'company',
			'communications' => array()
		);

		// Try to load entity communications
		if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::ResolveID($entityType), $entityID))
		{
			return array('ERROR' => GetMessage('CRM_PERMISSION_DENIED'));
		}

		if($communicationType !== '')
		{
			$dbResFields = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityID, 'TYPE_ID' =>  $communicationType)
			);

			while($arField = $dbResFields->Fetch())
			{
				if(empty($arField['VALUE']))
				{
					continue;
				}

				$comm = array(
					'type' => $communicationType,
					'value' => $arField['VALUE']
				);

				$companyItem['communications'][] = $comm;
			}
		}

		$companyData["{$entityType}_{$entityID}"] = $companyItem;

		//if($communicationType !== '')
		{
			$entityComms = CCrmActivity::GetCompanyCommunications($entityID, $communicationType);
			foreach($entityComms as &$entityComm)
			{
				CCrmActivity::PrepareCommunicationInfo($entityComm);
				$key = "{$entityComm['ENTITY_TYPE']}_{$entityComm['ENTITY_ID']}";
				if(!isset($companyData[$key]))
				{
					$companyData[$key] = array(
						'ownerEntityType' => 'COMPANY',
						'ownerEntityId' => $entityID,
						'entityType' => $entityComm['ENTITY_TYPE'],
						'entityId' => $entityComm['ENTITY_ID'],
						'entityTitle' => isset($entityComm['TITLE']) ? $entityComm['TITLE'] : '',
						'entityDescription' => isset($entityComm['DESCRIPTION']) ? $entityComm['DESCRIPTION'] : '',
						'tabId' => 'company',
						'communications' => array()
					);
				}

				$comm = array(
					'type' => $entityComm['TYPE'],
					'value' => $entityComm['VALUE']
				);

				$companyData[$key]['communications'][] = $comm;
			}
			unset($entityComm);
		}

		return array(
			'DATA' => array(
				'TABS' => array(
					array(
						'id' => 'company',
						'title' => GetMessage('CRM_COMMUNICATION_TAB_COMPANY'),
						'active' => true,
						'items' => array_values($companyData)
					)
				)
			)
		);
	}
	elseif($entityType === 'CONTACT')
	{
		$contactData = array();

		$entity = CCrmContact::GetByID($entityID);
		if(!$entity)
		{
			return array('ERROR' => 'Invalid data');
		}

		$entityCompany = isset($entity['COMPANY_ID']) ? CCrmCompany::GetByID($entity['COMPANY_ID']) : null;

		$contactItem = array(
			'ownerEntityType' => 'CONTACT',
			'ownerEntityId' => $entityID,
			'entityType' => 'CONTACT',
			'entityId' => $entityID,
			'entityTitle' => CCrmContact::PrepareFormattedName(
				array(
					'HONORIFIC' => isset($entity['HONORIFIC']) ? $entity['HONORIFIC'] : '',
					'NAME' => isset($entity['NAME']) ? $entity['NAME'] : '',
					'LAST_NAME' => isset($entity['LAST_NAME']) ? $entity['LAST_NAME'] : '',
					'SECOND_NAME' => isset($entity['SECOND_NAME']) ? $entity['SECOND_NAME'] : ''
				)
			),
			'entityDescription' => ($entityCompany && isset($entityCompany['TITLE'])) ? $entityCompany['TITLE'] : '',
			'tabId' => 'contact',
			'communications' => array()
		);

		// Try to load entity communications
		if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::ResolveID($entityType), $entityID))
		{
			return array('ERROR' => GetMessage('CRM_PERMISSION_DENIED'));
		}

		if($communicationType !== '')
		{
			$dbResFields = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityID, 'TYPE_ID' =>  $communicationType)
			);

			while($arField = $dbResFields->Fetch())
			{
				if(empty($arField['VALUE']))
				{
					continue;
				}

				$comm = array(
					'type' => $communicationType,
					'value' => $arField['VALUE']
				);

				$contactItem['communications'][] = $comm;
			}
		}

		$contactData["{$entityType}_{$entityID}"] = $contactItem;

		return array(
			'DATA' => array(
				'TABS' => array(
					array(
						'id' => 'contact',
						'title' => GetMessage('CRM_COMMUNICATION_TAB_CONTACT'),
						'active' => true,
						'items' => array_values($contactData)
					)
				)
			)
		);
	}

	return array('ERROR' => 'Invalid data');
}

if($action == 'DELETE')
{
	$ID = isset($_POST['ITEM_ID']) ? intval($_POST['ITEM_ID']) : 0;

	if($ID <= 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Invalid parameters!'));
	}

	$arActivity = CCrmActivity::GetByID($ID);
	if(!$arActivity)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Activity not found!'));
	}

	$ownerTypeName = isset($_POST['OWNER_TYPE']) ? strtoupper(strval($_POST['OWNER_TYPE'])) : '';
	if(!isset($ownerTypeName[0]))
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;
	if($ownerID <= 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	if(!CCrmActivity::CheckUpdatePermission(CCrmOwnerType::ResolveID($ownerTypeName), $ownerID))
	{
		__CrmActivityEditorEndResonse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	if(CCrmActivity::Delete($ID))
	{
		__CrmActivityEditorEndResonse(array('DELETED_ITEM_ID'=> $ID));
	}
	else
	{
		__CrmActivityEditorEndResonse(array('ERROR'=> "Could not delete activity ('$ID')!"));
	}
}
elseif($action == 'COMPLETE')
{
	$ID = isset($_POST['ITEM_ID']) ? intval($_POST['ITEM_ID']) : 0;

	if($ID <= 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Invalid data!'));
	}

	$arActivity = CCrmActivity::GetByID($ID);
	if(!$arActivity)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Activity not found!'));
	}

	$provider = CCrmActivity::GetActivityProvider($arActivity);
	if(!$provider)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Provider not found!'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID(isset($_POST['OWNER_TYPE']) ? strtoupper(strval($_POST['OWNER_TYPE'])) : '');
	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;

	if(!CCrmOwnerType::IsDefined($ownerTypeID) || $ownerID > 0)
	{
		$ownerTypeID = isset($arActivity['OWNER_TYPE_ID']) ? intval($arActivity['OWNER_TYPE_ID']) : CCrmOwnerType::Undefined;
		$ownerID = isset($arActivity['OWNER_ID']) ? intval($arActivity['OWNER_ID']) : 0;
	}

	if($provider::checkOwner() && !CCrmOwnerType::IsDefined($ownerTypeID))
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	if($provider::checkOwner() && $ownerID <= 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if($provider::checkOwner() && !CCrmActivity::CheckCompletePermission($ownerTypeID, $ownerID, $userPermissions, array('FIELDS' => $arActivity)))
	{
		__CrmActivityEditorEndResonse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}


	$completed = (isset($_POST['COMPLETED']) ? intval($_POST['COMPLETED']) : 0) > 0;

	if(CCrmActivity::Complete($ID, $completed, array('REGISTER_SONET_EVENT' => true)))
	{
		__CrmActivityEditorEndResonse(array('ITEM_ID'=> $ID, 'COMPLETED'=> $completed));
	}
	else
	{
		$errorMsg = CCrmActivity::GetLastErrorMessage();
		if(!isset($errorMsg[0]))
		{
			$errorMsg = "Could not complete activity ('$ID')!";
		}

		__CrmActivityEditorEndResonse(array('ERROR' => $errorMsg));
	}
}
elseif($action == 'SET_PRIORITY')
{
	$ID = isset($_POST['ITEM_ID']) ? intval($_POST['ITEM_ID']) : 0;

	if($ID <= 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Invalid data!'));
	}

	$arActivity = CCrmActivity::GetByID($ID);
	if(!$arActivity)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Activity not found!'));
	}

	$ownerTypeName = isset($_POST['OWNER_TYPE']) ? strtoupper(strval($_POST['OWNER_TYPE'])) : '';
	if(!isset($ownerTypeName[0]))
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;
	if($ownerID <= 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'OWNER ID IS NOT DEFINED!'));
	}

	if(!CCrmActivity::CheckUpdatePermission(CCrmOwnerType::ResolveID($ownerTypeName), $ownerID))
	{
		__CrmActivityEditorEndResonse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$priority = isset($_POST['PRIORITY']) ? intval($_POST['PRIORITY']) : CCrmActivityPriority::Medium;

	if(CCrmActivity::SetPriority($ID, $priority, array('REGISTER_SONET_EVENT' => true)))
	{
		__CrmActivityEditorEndResonse(array('ITEM_ID'=> $ID, 'PRIORITY'=> $priority));
	}
	else
	{
		$errorMsg = CCrmActivity::GetLastErrorMessage();
		if(!isset($errorMsg[0]))
		{
			$errorMsg = "Could not change priority!";
		}

		__CrmActivityEditorEndResonse(array('ERROR' => $errorMsg));
	}
}
elseif($action == 'SAVE_ACTIVITY')
{
	$siteID = !empty($_REQUEST['siteID']) ? $_REQUEST['siteID'] : SITE_ID;

	if (!CModule::IncludeModule('calendar'))
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Could not load module "calendar"!'));
	}

	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'SOURCE DATA ARE NOT FOUND!'));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	$typeID = isset($data['type']) ? intval($data['type']) : CCrmActivityType::Activity;

	$arActivity = null;
	if($ID > 0)
	{
		$arActivity = CCrmActivity::GetByID($ID, false);
		if(!$arActivity)
		{
			__CrmActivityEditorEndResonse(array('ERROR'=>'IS NOT EXISTS!'));
		}
	}

	$ownerTypeName = isset($data['ownerType']) ? strtoupper(strval($data['ownerType'])) : '';
	if($ownerTypeName === '')
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if(!CCrmOwnerType::IsDefined($ownerTypeID))
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT SUPPORTED!'));
	}

	$ownerID = isset($data['ownerID']) ? intval($data['ownerID']) : 0;
	if($ownerID <= 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'OWNER ID IS NOT DEFINED!'));
	}

	if(!CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID))
	{
		$entityTitle = CCrmOwnerType::GetCaption($ownerTypeID, $ownerID, false);
		if($ownerTypeID === CCrmOwnerType::Contact)
		{
			$errorMsg = GetMessage('CRM_CONTACT_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
		}
		elseif($ownerTypeID === CCrmOwnerType::Company)
		{
			$errorMsg = GetMessage('CRM_COMPANY_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
		}
		elseif($ownerTypeID === CCrmOwnerType::Lead)
		{
			$errorMsg = GetMessage('CRM_LEAD_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
		}
		elseif($ownerTypeID === CCrmOwnerType::Deal)
		{
			$errorMsg = GetMessage('CRM_DEAL_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
		}
		else
		{
			$errorMsg = GetMessage('CRM_PERMISSION_DENIED');
		}
		__CrmActivityEditorEndResonse(array('ERROR' => $errorMsg));
	}

	$responsibleID = isset($data['responsibleID']) ? intval($data['responsibleID']) : 0;

	$userID = $curUser->GetID();
	if($userID <= 0)
	{
		$userID = CCrmOwnerType::GetResponsibleID($ownerTypeID, $ownerID, false);
		if($userID <= 0)
		{
			__CrmActivityEditorEndResonse(array('ERROR'=>GetMessage('CRM_ACTIVITY_RESPONSIBLE_NOT_FOUND')));
		}
	}

	$now = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID);
	$start = isset($data['start']) ? strval($data['start']) : '';
	if($start === '')
	{
		$start =  $now;
	}

	$descr = isset($data['description']) ? strval($data['description']) : '';
	$priority = isset($data['priority']) ? intval($data['priority']) : CCrmActivityPriority::Medium;
	$location = isset($data['location']) ? strval($data['location']) : '';

	$direction = $typeID === CCrmActivityType::Call
			? (isset($data['direction']) ? intval($data['direction']) : CCrmActivityDirection::Outgoing)
			: CCrmActivityDirection::Undefined;

	// Communications
	$commData = isset($data['communication']) ? $data['communication'] : array();
	$commID = isset($commData['id']) ? intval($commData['id']) : 0;
	$commEntityType = isset($commData['entityType']) ? strtoupper(strval($commData['entityType'])) : '';
	$commEntityID = isset($commData['entityId']) ? intval($commData['entityId']) : 0;
	$commType = isset($commData['type']) ? strtoupper(strval($commData['type'])) : '';
	$commValue = isset($commData['value']) ? strval($commData['value']) : '';

	$subject = isset($data['subject']) ? strval($data['subject']) : '';
	if($subject === '')
	{
		$msgID = 'CRM_ACTION_DEFAULT_SUBJECT';
		if($typeID === CCrmActivityType::Call)
		{
			if($direction === CCrmActivityDirection::Incoming)
			{
				$msgID = 'CRM_INCOMING_CALL_ACTION_DEFAULT_SUBJECT_EXT';
			}
			elseif($direction === CCrmActivityDirection::Outgoing)
			{
				$msgID = 'CRM_OUTGOING_CALL_ACTION_DEFAULT_SUBJECT_EXT';
			}
		}
		elseif($typeID === CCrmActivityType::Meeting)
		{
			$msgID = 'CRM_MEETING_ACTION_DEFAULT_SUBJECT_EXT';
		}

		$arCommInfo = array(
			'ENTITY_ID' => $commEntityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType)
		);
		CCrmActivity::PrepareCommunicationInfo($arCommInfo);

		$subject = GetMessage(
			$msgID,
			array(
				'#DATE#'=> $now,
				'#TITLE#' => isset($arCommInfo['TITLE']) ? $arCommInfo['TITLE'] : $commValue,
				'#COMMUNICATION#' => $commValue
			)
		);
	}

	$arFields = array(
		'TYPE_ID' =>  $typeID,
		'SUBJECT' => $subject,
		'COMPLETED' => isset($data['completed']) ? (intval($data['completed']) > 0 ? 'Y' : 'N') : 'N',
		'PRIORITY' => $priority,
		'DESCRIPTION' => $descr,
		'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
		'LOCATION' => $location,
		'DIRECTION' => $direction,
		'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
		'SETTINGS' => array()
	);

	$arBindings = array(
		"{$ownerTypeName}_{$ownerID}" => array(
			'OWNER_TYPE_ID' => $ownerTypeID,
			'OWNER_ID' => $ownerID
		)
	);

	$notify = isset($data['notify']) ? $data['notify'] : null;
	if(is_array($notify))
	{
		$arFields['NOTIFY_TYPE'] = isset($notify['type']) ? intval($notify['type']) : CCrmActivityNotifyType::Min;
		$arFields['NOTIFY_VALUE'] = isset($notify['value']) ? intval($notify['value']) : 15;
	}

	// Communications
	$arComms = array();
	if($commEntityID <= 0 && $commType === 'PHONE' && $ownerTypeName !== 'DEAL')
	{
		// Communication entity ID is 0 (processing of new communications)
		// Communication type must present it determines TYPE_ID (is only 'PHONE' in current context)
		// Deal does not have multi fields.

		$fieldMulti = new CCrmFieldMulti();
		$arFieldMulti = array(
			'ENTITY_ID' => $ownerTypeName,
			'ELEMENT_ID' => $ownerID,
			'TYPE_ID' => 'PHONE',
			'VALUE_TYPE' => 'WORK',
			'VALUE' => $commValue
		);

		$fieldMultiID = $fieldMulti->Add($arFieldMulti);
		if($fieldMultiID > 0)
		{
			$commEntityType = $ownerTypeName;
			$commEntityID = $ownerID;
		}
	}

	if($commEntityType !== '')
	{
		$arComms[] = array(
			'ID' => $commID,
			'TYPE' => $commType,
			'VALUE' => $commValue,
			'ENTITY_ID' => $commEntityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType)
		);

		$bindingKey = $commEntityID > 0 ? "{$commEntityType}_{$commEntityID}" : uniqid("{$commEntityType}_");
		if(!isset($arBindings[$bindingKey]))
		{
			$arBindings[$bindingKey] = array(
				'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType),
				'OWNER_ID' => $commEntityID
			);
		}
	}

	$isNew = $ID <= 0;
	$arPreviousFields = $ID > 0 ? CCrmActivity::GetByID($ID) : array();

	$storageTypeID = isset($data['storageTypeID']) ? intval($data['storageTypeID']) : CCrmActivityStorageType::Undefined;
	if($storageTypeID === CCrmActivityStorageType::Undefined
		|| !CCrmActivityStorageType::IsDefined($storageTypeID))
	{
		if($isNew)
		{
			$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
		}
		else
		{
			$storageTypeID = CCrmActivity::GetStorageTypeID($ID);
			if($storageTypeID === CCrmActivityStorageType::Undefined)
			{
				$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
			}
		}
	}

	$arFields['STORAGE_TYPE_ID'] = $storageTypeID;
	$disableStorageEdit = isset($data['disableStorageEdit']) && strtoupper($data['disableStorageEdit']) === 'Y';
	if(!$disableStorageEdit)
	{
		if($storageTypeID === CCrmActivityStorageType::File)
		{
			$arPermittedFiles = array();
			$arUserFiles = isset($data['files']) && is_array($data['files']) ? $data['files'] : array();
			if(!empty($arUserFiles) || !$isNew)
			{
				$arPreviousFiles = array();
				if(!$isNew)
				{
					CCrmActivity::PrepareStorageElementIDs($arPreviousFields);
					$arPreviousFiles = $arPreviousFields['STORAGE_ELEMENT_IDS'];
					if(is_array($arPreviousFiles) && !empty($arPreviousFiles))
					{
						$arPermittedFiles = array_intersect($arUserFiles, $arPreviousFiles);
					}
				}

				$uploadControlCID = isset($data['uploadControlCID']) ? strval($data['uploadControlCID']) : '';
				if($uploadControlCID !== '' && isset($_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"]))
				{
					$uploadedFiles = $_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"];
					if(!empty($uploadedFiles))
					{
						$arPermittedFiles = array_merge(
							array_intersect($arUserFiles, $uploadedFiles),
							$arPermittedFiles
						);
					}
				}

				$arFields['STORAGE_ELEMENT_IDS'] = $arPermittedFiles;
			}
		}
		elseif($storageTypeID === CCrmActivityStorageType::WebDav || $storageTypeID === CCrmActivityStorageType::Disk)
		{
			$fileKey = $storageTypeID === CCrmActivityStorageType::Disk ? 'diskfiles' : 'webdavelements';
			$arFileIDs = isset($data[$fileKey]) && is_array($data[$fileKey]) ? $data[$fileKey] : array();
			if(!empty($arFileIDs) || !$isNew)
			{
				$arFields['STORAGE_ELEMENT_IDS'] = Bitrix\Crm\Integration\StorageManager::filterFiles($arFileIDs, $storageTypeID, $userID);
			}
		}
	}

	//TIME FIELDS
	$arFields['START_TIME'] = $arFields['END_TIME'] = $start;

	if($isNew)
	{
		$arFields['OWNER_ID'] = $ownerID;
		$arFields['OWNER_TYPE_ID'] = $ownerTypeID;
		$arFields['RESPONSIBLE_ID'] = $responsibleID > 0 ? $responsibleID : $userID;

		$arFields['BINDINGS'] = array_values($arBindings);

		if(!($ID = CCrmActivity::Add($arFields, false, true, array('REGISTER_SONET_EVENT' => true))))
		{
			__CrmActivityEditorEndResonse(array('ERROR' => CCrmActivity::GetLastErrorMessage()));
		}
	}
	else
	{
		$dbResult = CCrmActivity::GetList(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('OWNER_ID', 'OWNER_TYPE_ID', 'START_TIME', 'END_TIME')
		);
		$presentFields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($presentFields))
		{
			__CrmActivityEditorEndResonse(array('ERROR' => 'COULD NOT FIND ACTIVITY'));
		}

		$presentOwnerTypeID = intval($presentFields['OWNER_TYPE_ID']);
		$presentOwnerID = intval($presentFields['OWNER_ID']);
		$ownerChanged =  ($presentOwnerTypeID !== $ownerTypeID || $presentOwnerID !== $ownerID);

		$arFields['OWNER_ID'] = $ownerID;
		$arFields['OWNER_TYPE_ID'] = $ownerTypeID;

		if($responsibleID > 0)
		{
			$arFields['RESPONSIBLE_ID'] = $responsibleID;
		}

		//Merge new bindings with old bindings
		$presetCommunicationKeys = array();
		$presetCommunications = CCrmActivity::GetCommunications($ID);
		foreach($presetCommunications as $arComm)
		{
			$commEntityTypeName = CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']);
			$commEntityID = $arComm['ENTITY_ID'];
			$presetCommunicationKeys["{$commEntityTypeName}_{$commEntityID}"] = true;
		}

		$presentBindings = CCrmActivity::GetBindings($ID);
		foreach($presentBindings as &$binding)
		{
			$bindingOwnerID = (int)$binding['OWNER_ID'];
			$bindingOwnerTypeID = (int)$binding['OWNER_TYPE_ID'];
			$bindingOwnerTypeName = CCrmOwnerType::ResolveName($bindingOwnerTypeID);
			$bindingKey = "{$bindingOwnerTypeName}_{$bindingOwnerID}";

			//Skip present present owner if it is changed
			if($ownerChanged && $presentOwnerTypeID === $bindingOwnerTypeID && $presentOwnerID === $bindingOwnerID)
			{
				continue;
			}

			//Skip present communications - new communications already are in bindings
			if(isset($presetCommunicationKeys[$bindingKey]))
			{
				continue;
			}

			$arBindings[$bindingKey] = array(
				'OWNER_TYPE_ID' => $bindingOwnerTypeID,
				'OWNER_ID' => $bindingOwnerID
			);
		}
		unset($binding);
		$arFields['BINDINGS'] = array_values($arBindings);
		if(!CCrmActivity::Update($ID, $arFields, false, true, array('REGISTER_SONET_EVENT' => true)))
		{
			__CrmActivityEditorEndResonse(array('ERROR' => CCrmActivity::GetLastErrorMessage()));
		}
	}

	CCrmActivity::SaveCommunications($ID, $arComms, $arFields, !$isNew, false);

	$commData = array();
	$communications = CCrmActivity::GetCommunications($ID);
	foreach($communications as &$arComm)
	{
		CCrmActivity::PrepareCommunicationInfo($arComm);
		$commData[] = array(
			'type' => $arComm['TYPE'],
			'value' => $arComm['VALUE'],
			'entityId' => $arComm['ENTITY_ID'],
			'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
			'entityTitle' => $arComm['TITLE'],
			'entityUrl' => CCrmOwnerType::GetShowUrl($arComm['ENTITY_TYPE_ID'], $arComm['ENTITY_ID'])
		);
	}
	unset($arComm);

	$arFields = CCrmActivity::GetByID($ID);

	$responsibleID = isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0;
	$responsibleName = $responsibleID > 0 ? CCrmViewHelper::GetFormattedUserName($responsibleID) : '';

	$descrRaw = isset($arFields['DESCRIPTION']) ? $arFields['DESCRIPTION'] : '';
	$descrHtml = preg_replace("/[\r\n]+/".BX_UTF_PCRE_MODIFIER, "<br/>", htmlspecialcharsbx($descrRaw));

	CCrmActivity::PrepareStorageElementIDs($arFields);
	CCrmActivity::PrepareStorageElementInfo($arFields);

	$jsonFields = array(
		'ID' => $ID,
		'typeID' => $arFields['TYPE_ID'],
		'ownerID' => $arFields['OWNER_ID'],
		'ownerType' => CCrmOwnerType::ResolveName($arFields['OWNER_TYPE_ID']),
		'ownerTitle' => CCrmOwnerType::GetCaption($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
		'ownerUrl' => CCrmOwnerType::GetShowUrl($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
		'subject' => $arFields['SUBJECT'],
		'direction' => isset($arFields['DIRECTION']) ? intval($arFields['DIRECTION']) : CCrmActivityDirection::Undefined,
		'description' => $descrRaw,
		'descriptionHtml' => $descrHtml,
		'location' => isset($arFields['LOCATION']) ? $arFields['LOCATION'] : '',
		'start' => isset($arFields['START_TIME']) ? ConvertTimeStamp(MakeTimeStamp($arFields['START_TIME']), 'FULL', $siteID) : '',
		'end' => isset($arFields['END_TIME']) ? ConvertTimeStamp(MakeTimeStamp($arFields['END_TIME']), 'FULL', $siteID) : '',
		'deadline' => isset($arFields['DEADLINE']) ? ConvertTimeStamp(MakeTimeStamp($arFields['DEADLINE']), 'FULL', $siteID) : '',
		'completed' => isset($arFields['COMPLETED']) && $arFields['COMPLETED'] == 'Y',
		'notifyType' => isset($arFields['NOTIFY_TYPE']) ? intval($arFields['NOTIFY_TYPE']) : CCrmActivityNotifyType::None,
		'notifyValue' => isset($arFields['NOTIFY_VALUE']) ? intval($arFields['NOTIFY_VALUE']) : 0,
		'priority' => intval($arFields['PRIORITY']),
		'responsibleID' => $responsibleID,
		'responsibleName' => $responsibleName,
		'responsibleUrl' =>
			CComponentEngine::MakePathFromTemplate(
				'/company/personal/user/#user_id#/',
				array('user_id' => $responsibleID)
			),
		'storageTypeID' => $storageTypeID,
		'files' => isset($arFields['FILES']) ? $arFields['FILES'] : array(),
		'webdavelements' => isset($arFields['WEBDAV_ELEMENTS']) ? $arFields['WEBDAV_ELEMENTS'] : array(),
		'diskfiles' => isset($arFields['DISK_FILES']) ? $arFields['DISK_FILES'] : array(),
		'communications' => $commData
	);

	__CrmActivityEditorEndResonse(array('ACTIVITY' => $jsonFields));
}
elseif($action == 'SAVE_EMAIL')
{
	if (!CModule::IncludeModule('subscribe'))
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Could not load module "subscribe"!'));
	}

	$siteID = !empty($_REQUEST['siteID']) ? $_REQUEST['siteID'] : SITE_ID;

	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'SOURCE DATA ARE NOT FOUND!'));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	$isNew = $ID <= 0;

	$ownerTypeName = isset($data['ownerType']) ? strtoupper(strval($data['ownerType'])) : '';
	if($ownerTypeName === '')
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if(!CCrmOwnerType::IsDefined($ownerTypeID))
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'OWNER TYPE IS NOT SUPPORTED!'));
	}

	$ownerID = isset($data['ownerID']) ? intval($data['ownerID']) : 0;
	if($ownerID <= 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR'=>'OWNER ID IS NOT DEFINED!'));
	}

	if(!CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID))
	{
		$entityTitle = CCrmOwnerType::GetCaption($ownerTypeID, $ownerID, false);
		if($ownerTypeID === CCrmOwnerType::Contact)
		{
			$errorMsg = GetMessage('CRM_CONTACT_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
		}
		elseif($ownerTypeID === CCrmOwnerType::Company)
		{
			$errorMsg = GetMessage('CRM_COMPANY_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
		}
		elseif($ownerTypeID === CCrmOwnerType::Lead)
		{
			$errorMsg = GetMessage('CRM_LEAD_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
		}
		elseif($ownerTypeID === CCrmOwnerType::Deal)
		{
			$errorMsg = GetMessage('CRM_DEAL_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
		}
		else
		{
			$errorMsg = GetMessage('CRM_PERMISSION_DENIED');
		}
		__CrmActivityEditorEndResonse(array('ERROR' => $errorMsg));
	}

	$userID = $curUser->GetID();
	if($userID <= 0)
	{
		$userID = CCrmOwnerType::GetResponsibleID($ownerTypeID, $ownerID, false);
		if($userID <= 0)
		{
			__CrmActivityEditorEndResonse(array('ERROR' => GetMessage('CRM_ACTIVITY_RESPONSIBLE_NOT_FOUND')));
		}
	}

	$arErrors = array();

	if (CModule::includeModule('mail'))
	{
		$res = \Bitrix\Mail\MailboxTable::getList(array(
			'select' => array('*', 'LANG_CHARSET' => 'SITE.CULTURE.CHARSET'),
			'filter' => array(
				'=LID'    => SITE_ID,
				'=ACTIVE' => 'Y',
				array(
					'LOGIC' => 'OR',
					'=USER_ID' => $userID,
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

	$from  = '';
	$reply = '';
	$to    = array();
	$cc    = '';

	if (isset($data['from']))
		$from = trim(strval($data['from']));

	if ($from == '')
	{
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
			$arErrors[] = GetMessage('CRM_ACTIVITY_EMAIL_EMPTY_FROM_FIELD');
	}
	else
	{
		$fromAddresses = explode(',', $from);
		foreach ($fromAddresses as $fromAddress)
		{
			if (!check_email($fromAddress))
			{
				$arErrors[] = GetMessage('CRM_ACTIVITY_INVALID_EMAIL', array('#VALUE#' => $fromAddress));
				continue;
			}

			// copied from check_email
			if (preg_match('/.*?[<\[\(](.+?)[>\]\)].*/i', $fromAddress, $matches))
				$fromAddress = $matches[1];
			$fromList[] = strtolower(trim($fromAddress));
		}

		if (!empty($userImap['EMAIL_FROM']) && in_array($userImap['EMAIL_FROM'], $fromList))
			$userImap['need_sync'] = true;
		if (!empty($crmImap['EMAIL_FROM']) && in_array($crmImap['EMAIL_FROM'], $fromList))
			$crmImap['need_sync'] = true;

		if (empty($userImap['need_sync']) && empty($crmImap['need_sync']))
		{
			$cc = join(', ', $fromList);

			if ($crmEmail != '' && !in_array($crmEmail, $fromList))
				$reply = $cc . ', ' . $crmEmail;
		}
		else
		{
			$cc = join(', ', array_diff(
				$fromList,
				array(
					!empty($userImap['EMAIL_FROM']) ? $userImap['EMAIL_FROM'] : '',
					!empty($crmImap['EMAIL_FROM']) ? $crmImap['EMAIL_FROM'] : '',
				)
			));
		}
	}

	// Bindings & Communications -->
	$arBindings = array(
		"{$ownerTypeName}_{$ownerID}" => array(
			'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($ownerTypeName),
			'OWNER_ID' => $ownerID
		)
	);
	$arComms = array();
	$commData = isset($data['communications']) ? $data['communications'] : array();
	foreach($commData as &$commDatum)
	{
		$commID = isset($commData['id']) ? intval($commData['id']) : 0;
		$commEntityType = isset($commDatum['entityType']) ? strtoupper(strval($commDatum['entityType'])) : '';
		$commEntityID = isset($commDatum['entityId']) ? intval($commDatum['entityId']) : 0;

		$commType = isset($commDatum['type']) ? strtoupper(strval($commDatum['type'])) : '';
		if($commType === '')
		{
			$commType = 'EMAIL';
		}
		$commValue = isset($commDatum['value']) ? strval($commDatum['value']) : '';

		if($commType === 'EMAIL' && $commValue !== '')
		{
			if(!check_email($commValue))
			{
				$arErrors[] = GetMessage('CRM_ACTIVITY_INVALID_EMAIL', array('#VALUE#' => $commValue));
				continue;
			}

			$to[] = strtolower(trim($commValue));
		}

		$arComms[] = array(
			'ID' => $commID,
			'TYPE' => $commType,
			'VALUE' => $commValue,
			'ENTITY_ID' => $commEntityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType)
		);

		if($commEntityType !== '')
		{
			$bindingKey = $commEntityID > 0 ? "{$commEntityType}_{$commEntityID}" : uniqid("{$commEntityType}_");
			if(!isset($arBindings[$bindingKey]))
			{
				$arBindings[$bindingKey] = array(
					'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType),
					'OWNER_ID' => $commEntityID
				);
			}
		}
	}
	unset($commDatum);
	// <-- Bindings & Communications

	if(empty($to))
	{
		__CrmActivityEditorEndResonse(array('ERROR' => GetMessage('CRM_ACTIVITY_EMAIL_EMPTY_TO_FIELD')));
	}
	elseif(!empty($arErrors))
	{
		__CrmActivityEditorEndResonse(array('ERROR' => $arErrors));
	}

	$subject = isset($data['subject']) ? strval($data['subject']) : '';
	$message = isset($data['message']) ? strval($data['message']) : '';

	if($message !== '')
	{
		CCrmActivity::AddEmailSignature($message, CCrmContentType::BBCode);
	}

	if($message === '')
	{
		$messageHtml = '';
	}
	else
	{
		//Convert BBCODE to HTML
		$parser = new CTextParser();
		$parser->allow['SMILES'] = 'N';
		$messageHtml = '<html><body>'.$parser->convertText($message).'</body></html>';
	}

	$now = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID);
	if($subject === '')
	{
		$subject = GetMessage(
			'CRM_EMAIL_ACTION_DEFAULT_SUBJECT',
			array('#DATE#'=> $now)
		);
	}

	$parentId = 0;
	if (isset($data['FORWARDED_ID']))
		$parentId = intval($data['FORWARDED_ID']);
	elseif (isset($data['REPLIED_ID']))
		$parentId = intval($data['REPLIED_ID']);

	$description = $message;

	$arFields = array(
		'OWNER_ID' => $ownerID,
		'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($ownerTypeName),
		'TYPE_ID' =>  CCrmActivityType::Email,
		'SUBJECT' => $subject,
		'START_TIME' => $now,
		'END_TIME' => $now,
		'COMPLETED' => 'Y',
		'RESPONSIBLE_ID' => $userID,
		'PRIORITY' => CCrmActivityPriority::Medium,
		'DESCRIPTION' => $description,
		'DESCRIPTION_TYPE' => CCrmContentType::BBCode,
		'DIRECTION' => CCrmActivityDirection::Outgoing,
		'LOCATION' => '',
		'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
		'BINDINGS' => array_values($arBindings),
		'PARENT_ID' => $parentId,
	);

	if(count($arFields['BINDINGS']) === 1)
	{
		// In single bindind mode override owner data
		$arBinding = $arFields['BINDINGS'][0];
		$arFields['OWNER_TYPE_ID'] = $arBinding['OWNER_TYPE_ID'];
		$arFields['OWNER_ID'] = $arBinding['OWNER_ID'];
	}

	$storageTypeID = isset($data['storageTypeID']) ? intval($data['storageTypeID']) : CCrmActivityStorageType::Undefined;
	if($storageTypeID === CCrmActivityStorageType::Undefined
		|| !CCrmActivityStorageType::IsDefined($storageTypeID))
	{
		if($isNew)
		{
			$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
		}
		else
		{
			$storageTypeID = CCrmActivity::GetStorageTypeID($ID);
			if($storageTypeID === CCrmActivityStorageType::Undefined)
			{
				$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
			}
		}
	}

	$arFields['STORAGE_TYPE_ID'] = $storageTypeID;
	if($storageTypeID === CCrmActivityStorageType::File)
	{
		$arUserFiles = isset($data['files']) && is_array($data['files']) ? $data['files'] : array();
		if(!empty($arUserFiles) || !$isNew)
		{
			$arPermittedFiles = array();
			$arPreviousFiles = array();
			if(!$isNew)
			{
				$arPreviousFields = $ID > 0 ? CCrmActivity::GetByID($ID) : array();
				CCrmActivity::PrepareStorageElementIDs($arPreviousFields);
				$arPreviousFiles = $arPreviousFiles['STORAGE_ELEMENT_IDS'];
				if(is_array($arPreviousFiles) && !empty($arPreviousFiles))
				{
					$arPermittedFiles = array_intersect($arUserFiles, $arPreviousFiles);
				}
			}

			$forwardedID = isset($data['FORWARDED_ID']) ? intval($data['FORWARDED_ID']) : 0;
			if($forwardedID > 0)
			{
				$arForwardedFields = CCrmActivity::GetByID($forwardedID);
				if($arForwardedFields)
				{
					CCrmActivity::PrepareStorageElementIDs($arForwardedFields);
					$arForwardedFiles = $arForwardedFields['STORAGE_ELEMENT_IDS'];
					if(!empty($arForwardedFiles))
					{
						$arForwardedFiles = array_intersect($arUserFiles, $arForwardedFiles);
					}


					if(!empty($arForwardedFiles))
					{
						foreach($arForwardedFiles as $fileID)
						$arRawFile = CFile::MakeFileArray($fileID);
						if(is_array($arRawFile))
						{
							$fileID = intval(CFile::SaveFile($arRawFile, 'crm'));
							if($fileID > 0)
							{
								$arPermittedFiles[] = $fileID;
							}
						}
					}
				}
			}

			$uploadControlCID = isset($data['uploadControlCID']) ? strval($data['uploadControlCID']) : '';
			if($uploadControlCID !== '' && isset($_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"]))
			{
				$uploadedFiles = $_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"];
				if(!empty($uploadedFiles))
				{
					$arPermittedFiles = array_merge(
						array_intersect($arUserFiles, $uploadedFiles),
						$arPermittedFiles
					);
				}
			}

			$arFields['STORAGE_ELEMENT_IDS'] = $arPermittedFiles;
		}
	}
	elseif($storageTypeID === CCrmActivityStorageType::WebDav || $storageTypeID === CCrmActivityStorageType::Disk)
	{
		$fileKey = $storageTypeID === CCrmActivityStorageType::Disk ? 'diskfiles' : 'webdavelements';
		$arFileIDs = isset($data[$fileKey]) && is_array($data[$fileKey]) ? $data[$fileKey] : array();
		if(!empty($arFileIDs) || !$isNew)
		{
			$arFields['STORAGE_ELEMENT_IDS'] = Bitrix\Crm\Integration\StorageManager::filterFiles($arFileIDs, $storageTypeID, $userID);
		}
	}
	if($isNew)
	{
		if(!($ID = CCrmActivity::Add($arFields, false, false, array('REGISTER_SONET_EVENT' => true))))
		{
			__CrmActivityEditorEndResonse(array('ERROR' => CCrmActivity::GetLastErrorMessage()));
		}
	}
	else
	{
		if(!CCrmActivity::Update($ID, $arFields, false, false))
		{
			__CrmActivityEditorEndResonse(array('ERROR' => CCrmActivity::GetLastErrorMessage()));
		}
	}

	$urn = CCrmActivity::PrepareUrn($arFields);
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

	CCrmActivity::SaveCommunications($ID, $arComms, $arFields, false, false);

	// Creating Email -->
	//Save user email in settings -->
	if($from !== CUserOptions::GetOption('crm', 'activity_email_addresser', ''))
	{
		CUserOptions::SetOption('crm', 'activity_email_addresser', $from);
	}

	/*CCrmMailTemplate::SetLastUsedTemplateID(
		isset($data['templateID']) ? intval($data['templateID']) : 0,
		CCrmOwnerType::ResolveID($ownerTypeName),
		CCrmSecurityHelper::GetCurrentUserID()
	);*/
	//<-- Save user email in settings
	if(!empty($arErrors))
	{
		__CrmActivityEditorEndResonse(array('ERROR' => $arErrors));
	}

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
		'BCC_FIELD' => implode(',', $to),
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
		$arErrors[] = GetMessage('CRM_ACTIVITY_COULD_NOT_CREATE_POSTING');
		$arErrors[] = $posting->LAST_ERROR;
	}
	else
	{
		// Attaching files -->
		$arRawFiles = isset($arFields['STORAGE_ELEMENT_IDS']) && !empty($arFields['STORAGE_ELEMENT_IDS'])
			? \Bitrix\Crm\Integration\StorageManager::makeFileArray($arFields['STORAGE_ELEMENT_IDS'], $storageTypeID)
			: array();


		foreach($arRawFiles as &$arRawFile)
		{
			if(isset($arRawFile['ORIGINAL_NAME']))
			{
				$arRawFile['name'] = $arRawFile['ORIGINAL_NAME'];
			}
			if(!$posting->SaveFile($postingID, $arRawFile))
			{
				$arErrors[] = GetMessage('CRM_ACTIVITY_COULD_NOT_SAVE_POSTING_FILE', array('#FILE_NAME#' => $arRawFile['ORIGINAL_NAME']));
				$arErrors[] = $posting->LAST_ERROR;
				break;
			}
		}
		unset($arRawFile);
		// <-- Attaching files

		if(empty($arErrors))
		{
			$arUpdateFields = array(
				'ASSOCIATED_ENTITY_ID' => $postingID,
				'SETTINGS' => array('MESSAGE_HEADERS' => array('Message-Id' => $messageId))
			);

			if ($reply != '')
				$arUpdateFields['SETTINGS']['MESSAGE_HEADERS']['Reply-To'] = $reply;

			CCrmActivity::Update($ID, $arUpdateFields, false, false);
		}
	}
	// <-- Creating Email

	if(!empty($arErrors))
	{
		if($isNew)
		{
			$arErrors[] = GetMessage('CRM_ACTIVITY_EMAIL_CREATION_CANCELED');
			CCrmActivity::Delete($ID);
		}
		__CrmActivityEditorEndResonse(array('ERROR' => $arErrors));
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
		foreach ($to as $item)
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
	$eventText .= GetMessage('CRM_TITLE_EMAIL_SUBJECT').': '.$subject."\n\r";
	$eventText .= GetMessage('CRM_TITLE_EMAIL_FROM').': '.$from."\n\r";
	$eventText .= GetMessage('CRM_TITLE_EMAIL_TO').': '.implode(',', $to)."\n\r\n\r";
	$eventText .= $messageHtml;
	// Register event only for owner
	$CCrmEvent->Add(
		array(
			'ENTITY' => array(
				$ownerID => array(
					'ENTITY_TYPE' => $ownerTypeName,
					'ENTITY_ID' => $ownerID
				)
			),
			'EVENT_ID' => 'MESSAGE',
			'EVENT_TEXT_1' => $eventText,
			'FILES' => $arRawFiles
		)
	);
	// <-- Sending Email

	$commData = array();
	$communications = CCrmActivity::GetCommunications($ID);
	foreach($communications as &$arComm)
	{
		CCrmActivity::PrepareCommunicationInfo($arComm);
		$commData[] = array(
			'type' => $arComm['TYPE'],
			'value' => $arComm['VALUE'],
			'entityId' => $arComm['ENTITY_ID'],
			'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
			'entityTitle' => $arComm['TITLE'],
			'entityUrl' => CCrmOwnerType::GetShowUrl($arComm['ENTITY_TYPE_ID'], $arComm['ENTITY_ID'])
		);
	}
	unset($arComm);

	$userName = '';
	if($userID > 0)
	{
		$dbResUser = CUser::GetByID($userID);
		$userName = is_array(($user = $dbResUser->Fetch()))
			? CUser::FormatName(CSite::GetNameFormat(false), $user, true, false) : '';
	}

	$nowStr = ConvertTimeStamp(MakeTimeStamp($now), 'FULL', $siteID);

	CCrmActivity::PrepareStorageElementIDs($arFields);
	CCrmActivity::PrepareStorageElementInfo($arFields);

	$jsonFields = array(
		'ID' => $ID,
		'typeID' => CCrmActivityType::Email,
		'ownerID' => $arFields['OWNER_ID'],
		'ownerType' => CCrmOwnerType::ResolveName($arFields['OWNER_TYPE_ID']),
		'ownerTitle' => CCrmOwnerType::GetCaption($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
		'ownerUrl' => CCrmOwnerType::GetShowUrl($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
		'subject' => $subject,
		'description' => $description,
		'descriptionHtml' => $messageHtml,
		'location' => '',
		'start' => $nowStr,
		'end' => $nowStr,
		'deadline' => $nowStr,
		'completed' => true,
		'notifyType' => CCrmActivityNotifyType::None,
		'notifyValue' => 0,
		'priority' => CCrmActivityPriority::Medium,
		'responsibleName' => $userName,
		'responsibleUrl' =>
			CComponentEngine::MakePathFromTemplate(
				'/company/personal/user/#user_id#/',
				array('user_id' => $userID)
			),
		'storageTypeID' => $storageTypeID,
		'files' => isset($arFields['FILES']) ? $arFields['FILES'] : array(),
		'webdavelements' => isset($arFields['WEBDAV_ELEMENTS']) ? $arFields['WEBDAV_ELEMENTS'] : array(),
		'diskfiles' => isset($arFields['DISK_FILES']) ? $arFields['DISK_FILES'] : array(),
		'communications' => $commData
	);

	__CrmActivityEditorEndResonse(array('ACTIVITY' => $jsonFields));
}
elseif($action == 'GET_ACTIVITY')
{
	$ID = isset($_POST['ID']) ? intval($_POST['ID']) : 0;

	$arFields = CCrmActivity::GetByID($ID);
	if(!is_array($arFields))
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'NOT FOUND'));
	}

	$commData = array();
	$communications = CCrmActivity::GetCommunications($ID);
	foreach($communications as &$arComm)
	{
		CCrmActivity::PrepareCommunicationInfo($arComm);
		$commData[] = array(
			'type' => $arComm['TYPE'],
			'value' => $arComm['VALUE'],
			'entityId' => $arComm['ENTITY_ID'],
			'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
			'entityTitle' => $arComm['TITLE'],
		);
	}
	unset($arComm);

	$storageTypeID = isset($arFields['STORAGE_TYPE_ID'])
		? intval($arFields['STORAGE_TYPE_ID']) : CCrmActivityStorageType::Undefined;

	CCrmActivity::PrepareStorageElementIDs($arFields);
	CCrmActivity::PrepareStorageElementInfo($arFields);

	__CrmActivityEditorEndResonse(
		array(
			'ACTIVITY' => array(
				'ID' => $ID,
				'typeID' => $arFields['TYPE_ID'],
				'associatedEntityID' => isset($arFields['ASSOCIATED_ENTITY_ID']) ? $arFields['ASSOCIATED_ENTITY_ID'] : '0',
				'ownerID' => $arFields['OWNER_ID'],
				'ownerType' => CCrmOwnerType::ResolveName($arFields['OWNER_TYPE_ID']),
				'ownerTitle' => CCrmOwnerType::GetCaption($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
				'ownerUrl' => CCrmOwnerType::GetShowUrl($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
				'subject' => $arFields['SUBJECT'],
				'description' => $arFields['DESCRIPTION'],
				'location' => $arFields['LOCATION'],
				'direction' => intval($arFields['DIRECTION']),
				'start' => $arFields['START_TIME'],
				'end' => $arFields['END_TIME'],
				'completed' => isset($arFields['COMPLETED']) && $arFields['COMPLETED'] === 'Y',
				'notifyType' => intval($arFields['NOTIFY_TYPE']),
				'notifyValue' => intval($arFields['NOTIFY_VALUE']),
				'priority' => intval($arFields['PRIORITY']),
				'responsibleName' => CCrmViewHelper::GetFormattedUserName(
					isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0
				),
				'storageTypeID' => $storageTypeID,
				'files' => isset($arFields['FILES']) ? $arFields['FILES'] : array(),
				'webdavelements' => isset($arFields['WEBDAV_ELEMENTS']) ? $arFields['WEBDAV_ELEMENTS'] : array(),
				'diskfiles' => isset($arFields['DISK_FILES']) ? $arFields['DISK_FILES'] : array(),
				'communications' => $commData
			)
		)
	);
}
elseif($action == 'GET_ENTITY_COMMUNICATIONS')
{
	$entityType = isset($_POST['ENTITY_TYPE']) ? strtoupper(strval($_POST['ENTITY_TYPE'])) : '';
	$entityID = isset($_POST['ENTITY_ID']) ? intval($_POST['ENTITY_ID']) : 0;
	$communicationType = isset($_POST['COMMUNICATION_TYPE']) ? strval($_POST['COMMUNICATION_TYPE']) : '';

	if($entityType === '' || $entityID <= 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Invalid data'));
	}

	__CrmActivityEditorEndResonse(GetCrmEntityCommunications($entityType, $entityID, $communicationType));
}
elseif($action == 'SEARCH_COMMUNICATIONS')
{
	$entityType = isset($_POST['ENTITY_TYPE']) ? strtoupper(strval($_POST['ENTITY_TYPE'])) : '';
	$entityID = isset($_POST['ENTITY_ID']) ? intval($_POST['ENTITY_ID']) : 0;
	$communicationType = isset($_POST['COMMUNICATION_TYPE']) ? strval($_POST['COMMUNICATION_TYPE']) : '';
	$needle = isset($_POST['NEEDLE']) ? strval($_POST['NEEDLE']) : '';

	$results = CCrmActivity::FindContactCommunications($needle, $communicationType, 10);

	if($communicationType !== '')
	{
		//If communication type defined add companies communications
		$results = array_merge(
			$results,
			CCrmActivity::FindCompanyCommunications($needle, $communicationType, 10)
		);
	}

	$results = array_merge(
		$results,
		CCrmActivity::FindLeadCommunications($needle, $communicationType, 10)
	);

	$data = array();
	foreach($results as &$result)
	{
		$key = "{$result['ENTITY_TYPE_ID']}_{$result['ENTITY_ID']}";
		if(!isset($data[$key]))
		{
			$data[$key] = array(
				'ownerEntityType' => $entityType !== '' ? $entityType : CCrmOwnerType::ResolveName($result['ENTITY_TYPE_ID']),
				'ownerEntityId' => $entityID > 0 ? $entityID : intval($result['ENTITY_ID']),
				'entityType' => CCrmOwnerType::ResolveName($result['ENTITY_TYPE_ID']),
				'entityId' => $result['ENTITY_ID'],
				'entityTitle' => $result['TITLE'],
				'entityDescription' => $result['DESCRIPTION'],
				'tabId' => 'search',
				'communications' => array()
			);
		}

		if($result['TYPE'] !== '' && $result['VALUE'] !== '')
		{
			$comm = array(
				'type' => $result['TYPE'],
				'value' => $result['VALUE']
			);

			$data[$key]['communications'][] = $comm;
		}
	}
	unset($result);

	__CrmActivityEditorEndResonse(array('DATA' => array('ITEMS' => array_values($data))));
}
elseif($action == 'GET_TASK')
{
	$ID = isset($_POST['ITEM_ID']) ? intval($_POST['ITEM_ID']) : 0;
	$ownerTypeName = isset($_POST['OWNER_TYPE']) ? strtoupper(strval($_POST['OWNER_TYPE'])) : '';
	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;
	$taskID = isset($_POST['TASK_ID']) ? intval($_POST['TASK_ID']) : 0;

	$arFilter = array();

	if($ID > 0)
	{
		$arFilter['=ID'] = $ID;
	}
	else
	{
		if($taskID <= 0)
		{
			__CrmActivityEditorEndResonse(array('ERROR' => 'Invalid data'));
		}

		$arFilter['=TYPE_ID'] = CCrmActivityType::Task;
		$arFilter['=ASSOCIATED_ENTITY_ID'] = $taskID;

		if($ownerTypeName !== '')
		{
			$arFilter['=OWNER_TYPE_ID'] = CCrmOwnerType::ResolveID($ownerTypeName);
		}

		if($ownerID > 0)
		{
			$arFilter['=OWNER_ID'] = $ownerID;
		}
	}

	$dbActivities = CCrmActivity::GetList(array(), $arFilter);
	$arActivity = $dbActivities->Fetch();
	if(!$arActivity)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Not found'));
	}

	$userName = '';
	if($arActivity['RESPONSIBLE_ID'] > 0)
	{
		$dbResUser = CUser::GetByID($arActivity['RESPONSIBLE_ID']);
		$userName = is_array(($user = $dbResUser->Fetch()))
			? CUser::FormatName(CSite::GetNameFormat(false), $user, true, false) : '';
	}

	__CrmActivityEditorEndResonse(
		array(
			'ACTIVITY' => array(
				'ID' => $arActivity['ID'],
				'typeID' => CCrmActivityType::Task,
				'associatedEntityID' => $taskID,
				'subject' => $arActivity['SUBJECT'],
				'description' => $arActivity['DESCRIPTION'],
				'start' => !empty($arActivity['START_TIME']) ? $arActivity['START_TIME'] : '',
				'end' => !empty($arActivity['END_TIME']) ? $arActivity['END_TIME'] : '',
				'completed' => $arActivity['COMPLETED'] === 'Y',
				'notifyType' => CCrmActivityNotifyType::None,
				'notifyValue' => 0,
				'priority' => $arActivity['PRIORITY'],
				'responsibleName' => $userName
			)
		)
	);
}
elseif($action == 'GET_ACTIVITIES')
{
	$ownerTypeName = isset($_POST['OWNER_TYPE']) ? strtoupper(strval($_POST['OWNER_TYPE'])) : '';
	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;

	if($ownerTypeName === '' || $ownerID <= 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Invalid data'));
	}

	$completed = isset($_POST['COMPLETED']) ? intval($_POST['COMPLETED']) : 0;

	if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::ResolveID($ownerTypeName), $ownerID))
	{
		__CrmActivityEditorEndResonse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$dbRes = CCrmActivity::GetList(
		array('deadline' => 'asc'),
		array(
			'OWNER_ID' => $ownerID,
			'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($ownerTypeName),
			'COMPLETED' => $completed > 0 ? 'Y' : 'N'
		)
	);

	$arItems = array();
	while($arRes = $dbRes->GetNext())
	{
		$responsibleID = isset($arRes['~RESPONSIBLE_ID'])
			? intval($arRes['~RESPONSIBLE_ID']) : 0;
		if($responsibleID > 0)
		{
			$dbResUser = CUser::GetByID($responsibleID);
			$arRes['RESPONSIBLE'] = $dbResUser->Fetch();
			$arRes['RESPONSIBLE_FULL_NAME'] = is_array($arRes['RESPONSIBLE'])
				? CUser::FormatName(CSite::GetNameFormat(false), $arRes['RESPONSIBLE'], true, false) : '';
		}
		else
		{
			$arRes['RESPONSIBLE'] = false;
			$arRes['RESPONSIBLE_FULL_NAME'] = '';
			$arRes['PATH_TO_RESPONSIBLE'] = '';
		}

		$arRes['FILES'] = array();
		CCrmActivity::PrepareStorageElementIDs($arRes);
		$arFileID = $arRes['STORAGE_ELEMENT_IDS'];
		if(is_array($arFileID))
		{
			$fileCount = count($arFileID);
			for($i = 0; $i < $fileCount; $i++)
			{
				if(is_array($arData = CFile::GetFileArray($arFileID[$i])))
				{
					$arRes['FILES'][] = array(
						'fileID' => $arData['ID'],
						'fileName' => $arData['FILE_NAME'],
						'fileURL' =>  CCrmUrlUtil::UrnEncode($arData['SRC']),
						'fileSize' => $arData['FILE_SIZE']
					);
				}
			}
		}

		$arRes['SETTINGS'] = isset($arRes['~SETTINGS']) ? unserialize($arRes['~SETTINGS']) : array();
		$arRes['COMMUNICATIONS'] = CCrmActivity::GetCommunications($arRes['~ID']);

		$commData = array();
		if(is_array($arRes['COMMUNICATIONS']))
		{
			foreach($arRes['COMMUNICATIONS'] as &$arComm)
			{
				CCrmActivity::PrepareCommunicationInfo($arComm);
				$commData[] = array(
					'id' => $arComm['ID'],
					'type' => $arComm['TYPE'],
					'value' => $arComm['VALUE'],
					'entityId' => $arComm['ENTITY_ID'],
					'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
					'entityTitle' => $arComm['TITLE'],
				);
			}
			unset($arComm);
		}

		$item = array(
			'ID' => $arRes['~ID'],
			'typeID' => $arRes['~TYPE_ID'],
			'subject' => strval($arRes['~SUBJECT']),
			'description' => strval($arRes['~DESCRIPTION']),
			'direction' => intval($arRes['~DIRECTION']),
			'location' => strval($arRes['~LOCATION']),
			'start' => isset($arRes['~START_TIME']) ? ConvertTimeStamp(MakeTimeStamp($arRes['~START_TIME']), 'FULL', SITE_ID) : '',
			'end' => isset($arRes['~START_TIME']) ? ConvertTimeStamp(MakeTimeStamp($arRes['~END_TIME']), 'FULL', SITE_ID) : '',
			'deadline' => isset($arRes['~DEADLINE']) ? ConvertTimeStamp(MakeTimeStamp($arRes['~DEADLINE']), 'FULL', SITE_ID) : '',
			'completed' => strval($arRes['~COMPLETED']) == 'Y',
			'notifyType' => intval($arRes['~NOTIFY_TYPE']),
			'notifyValue' => intval($arRes['~NOTIFY_VALUE']),
			'priority' => intval($arRes['~PRIORITY']),
			'responsibleName' => isset($arRes['RESPONSIBLE_FULL_NAME'][0]) ? $arRes['RESPONSIBLE_FULL_NAME'] : GetMessage('CRM_UNDEFINED_VALUE'),
			'files' => $arRes['FILES'],
			'associatedEntityID' => isset($arRes['~ASSOCIATED_ENTITY_ID']) ? intval($arRes['~ASSOCIATED_ENTITY_ID']) : 0,
			'communications' => $commData
		);

		$arItems[] = $item;
	}

	__CrmActivityEditorEndResonse(array('DATA' => array('ITEMS' => $arItems)));
}
elseif($action == 'GET_ENTITIES_DEFAULT_COMMUNICATIONS')
{
	$communicationType = isset($_POST['COMMUNICATION_TYPE']) ? strval($_POST['COMMUNICATION_TYPE']) : '';
	$entityType = isset($_POST['ENTITY_TYPE']) ? strtoupper(strval($_POST['ENTITY_TYPE'])) : '';
	$arEntityID = isset($_POST['ENTITY_IDS']) ? $_POST['ENTITY_IDS'] : array();
	$gridID = isset($_POST['GRID_ID']) ? $_POST['GRID_ID'] : array();

	if($entityType === '' || $communicationType === '')
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Invalid data'));
	}

	// PERMISSIONS CHECK -->
	$isPermitted = true;
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if(empty($arEntityID))
	{
		$isPermitted = CCrmActivity::CheckReadPermission(CCrmOwnerType::ResolveID($entityType), 0, $userPermissions);
	}
	else
	{
		foreach($arEntityID as $entityID)
		{
			$isPermitted = CCrmActivity::CheckReadPermission(CCrmOwnerType::ResolveID($entityType), $entityID, $userPermissions);
			if(!$isPermitted)
			{
				break;
			}
		}
	}

	if(!$isPermitted)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}
	// <--PERMISSIONS CHECK

	if(empty($arEntityID) && $gridID !== '')
	{
		//Apply grid filter if ids is not defined
		$gridOptions = new CCrmGridOptions($gridID);
		$gridFilter = $gridOptions->GetFilter(array());

		//Clear service fields
		if(isset($gridFilter['GRID_FILTER_APPLIED']))
		{
			unset($gridFilter['GRID_FILTER_APPLIED']);
		}

		if(isset($gridFilter['GRID_FILTER_ID']))
		{
			unset($gridFilter['GRID_FILTER_ID']);
		}

		if(is_array($gridFilter) && !empty($gridFilter))
		{
			$dbEntities = null;
			if($entityType === 'LEAD')
			{
				CCrmLead::PrepareFilter($gridFilter);
				$dbEntities = CCrmLead::GetListEx(array(), $gridFilter, false, false, array('ID'));
			}
			elseif($entityType === 'DEAL')
			{
				CCrmDeal::PrepareFilter($gridFilter);
				$dbEntities = CCrmDeal::GetListEx(array(), $gridFilter, false, false, array('ID'));
			}
			elseif($entityType === 'COMPANY')
			{
				CCrmCompany::PrepareFilter($gridFilter);
				$dbEntities = CCrmCompany::GetListEx(array(), $gridFilter, false, false, array('ID'));
			}
			elseif($entityType === 'CONTACT')
			{
				CCrmContact::PrepareFilter($gridFilter);
				$dbEntities = CCrmContact::GetListEx(array(), $gridFilter, false, false, array('ID'));
			}

			if($dbEntities)
			{
				while($arEntity = $dbEntities->Fetch())
				{
					$arEntityID[] = $arEntity['ID'];
				}
			}
		}
	}

	$arFilter = array(
		'ENTITY_ID' => $entityType,
		'TYPE_ID' =>  $communicationType,
		'@VALUE_TYPE' => array('WORK', 'HOME', 'OTHER')
	);

	if(!empty($arEntityID))
	{
		$arFilter['@ELEMENT_ID'] = $arEntityID;
	}
	
	$dbResFields = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		$arFilter
	);

	$data = array();
	while($arField = $dbResFields->Fetch())
	{
		$value = isset($arField['VALUE']) ? $arField['VALUE'] : '';
		if($value === '')
		{
			continue;
		}

		$entityID = isset($arField['ELEMENT_ID']) ? intval($arField['ELEMENT_ID']) : 0;
		$valueType = isset($arField['VALUE_TYPE']) ? $arField['VALUE_TYPE'] : '';
		if($entityID <= 0
			|| $valueType === ''
			|| (isset($data[$entityID]) && isset($data[$entityID][$valueType])))
		{
			continue;
		}

		$data[$entityID][$valueType] = $value;
	}

	$result = array();
	foreach($data as $entityID => &$values)
	{
		if(isset($values['WORK']))
		{
			$result[] = array(
				'entityId' => $entityID,
				'value' => $values['WORK']
			);
		}
		elseif(isset($values['HOME']))
		{
			$result[] = array(
				'entityId' => $entityID,
				'value' => $values['HOME']
			);
		}		
		elseif(isset($values['OTHER']))
		{
			$result[] = array(
				'entityId' => $entityID,
				'value' => $values['OTHER']
			);
		}
	}
	unset($values);

	__CrmActivityEditorEndResonse(array('DATA' => array('ENTITY_TYPE' => $entityType, 'ITEMS' => $result)));
}
elseif($action == 'GET_WEBDAV_ELEMENT_INFO')
{
	$elementID = isset($_POST['ELEMENT_ID']) ? intval($_POST['ELEMENT_ID']) : 0;

	if($elementID <= 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Invalid data'));
	}

	__CrmActivityEditorEndResonse(
		array(
			'DATA' => array(
				'ELEMENT_ID' => $elementID,
				'INFO' => \Bitrix\Crm\Integration\StorageManager::getFileInfo(
					$elementID,
					\Bitrix\Crm\Integration\StorageType::WebDav
				)
			)
		)
	);

}
elseif($action == 'GET_COMMUNICATION_HTML')
{
	$typeName = isset($_POST['TYPE_NAME']) ? strval($_POST['TYPE_NAME']) : '';
	$value = isset($_POST['VALUE']) ? strval($_POST['VALUE']) : '';

	__CrmActivityEditorEndResonse(
		array(
			'DATA' => array(
				'HTML' => CCrmViewHelper::PrepareMultiFieldHtml(
					$typeName,
					array(
						'VALUE_TYPE_ID' => 'WORK',
						'VALUE' => $value
					)
				)
			)
		)
	);
}
elseif($action == 'PREPARE_MAIL_TEMPLATE')
{
	$templateID = isset($_POST['TEMPLATE_ID']) ? intval($_POST['TEMPLATE_ID']) : 0;
	$ownerTypeName = isset($_POST['OWNER_TYPE']) ? strtoupper(strval($_POST['OWNER_TYPE'])) : '';
	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;

	if($templateID <= 0)
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Invalid data'));
	}

	$dbResult = CCrmMailTemplate::GetList(
		array(),
		array('=ID' => $templateID),
		false,
		false,
		array('OWNER_ID', 'ENTITY_TYPE_ID', 'SCOPE', 'EMAIL_FROM', 'SUBJECT', 'BODY')
	);
	$fields = $dbResult->Fetch();
	if(!is_array($fields))
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Invalid data'));
	}

	$templateOwnerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;
	$templateScope = isset($fields['SCOPE']) ? intval($fields['SCOPE']) : CCrmMailTemplateScope::Undefined;

	if($templateScope !== CCrmMailTemplateScope::Common
		&& $templateOwnerID !== intval($curUser->GetID()))
	{
		__CrmActivityEditorEndResonse(array('ERROR' => 'Invalid data'));
	}

	$body = isset($fields['BODY']) ? $fields['BODY'] : '';
	if($body !== '')
	{
		$contentTypeID = isset($_POST['CONTENT_TYPE']) ? CCrmContentType::ResolveTypeID($_POST['CONTENT_TYPE']) : CCrmContentType::Undefined;
		if(!CCrmContentType::IsDefined($contentTypeID))
		{
			$contentTypeID = CCrmContentType::PlainText;
		}
		$body = CCrmTemplateManager::PrepareTemplate($body, CCrmOwnerType::ResolveID($ownerTypeName), $ownerID, $contentTypeID);
	}

	__CrmActivityEditorEndResonse(
		array(
			'DATA' => array(
				'ID' => $templateID,
				'OWNER_TYPE'=> $ownerTypeName,
				'OWNER_ID' => $ownerID,
				'FROM' => isset($fields['EMAIL_FROM']) ? $fields['EMAIL_FROM'] : '',
				'SUBJECT' => isset($fields['SUBJECT']) ? $fields['SUBJECT'] : '',
				'BODY' => $body
			)
		)
	);
}
elseif($action == 'GET_ACTIVITY_COMMUNICATIONS')
{
	$ID = isset($_POST['ID']) ? intval($_POST['ID']) : 0;
	__CrmActivityEditorEndResonse(array('ACTIVITY_COMMUNICATIONS' => GetCrmActivityCommunications($ID)));
}
elseif($action == 'GET_ACTIVITY_COMMUNICATIONS_PAGE')
{
	$ID = isset($_POST['ID']) ? intval($_POST['ID']) : 0;
	$pageSize = isset($_POST['PAGE_SIZE']) ? intval($_POST['PAGE_SIZE']) : 20;
	$pageNumber = isset($_POST['PAGE_NUMBER']) ? intval($_POST['PAGE_NUMBER']) : 1;

	__CrmActivityEditorEndResonse(array('ACTIVITY_COMMUNICATIONS_PAGE' => GetCrmActivityCommunicationsPage($ID, $pageSize, $pageNumber)));
}
elseif($action == 'GET_ACTIVITY_VIEW_DATA')
{
	$result = array();
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();

	$comm = isset($params['ACTIVITY_COMMUNICATIONS']) ? $params['ACTIVITY_COMMUNICATIONS'] : null;
	if(is_array($comm))
	{
		$ID = isset($comm['ID']) ? (int)$comm['ID'] : 0;
		$result['ACTIVITY_COMMUNICATIONS'] = GetCrmActivityCommunications($ID);
	}

	$commPage = isset($params['ACTIVITY_COMMUNICATIONS_PAGE']) ? $params['ACTIVITY_COMMUNICATIONS_PAGE'] : null;
	if(is_array($commPage))
	{
		$ID = isset($commPage['ID']) ? (int)$commPage['ID'] : 0;
		$pageSize = isset($commPage['PAGE_SIZE']) ? (int)$commPage['PAGE_SIZE'] : 20;
		$pageNumber = isset($commPage['PAGE_NUMBER']) ? (int)$commPage['PAGE_NUMBER'] : 1;
		$result['ACTIVITY_COMMUNICATIONS_PAGE'] = GetCrmActivityCommunicationsPage($ID, $pageSize, $pageNumber);
	}

	$entityComm = isset($params['ENTITY_COMMUNICATIONS']) ? $params['ENTITY_COMMUNICATIONS'] : null;
	if(is_array($entityComm))
	{
		$entityType = isset($entityComm['ENTITY_TYPE']) ? strtoupper($entityComm['ENTITY_TYPE']) : '';
		$entityID = isset($entityComm['ENTITY_ID']) ? (int)$entityComm['ENTITY_ID'] : 0;
		$communicationType = isset($entityComm['COMMUNICATION_TYPE']) ? $entityComm['COMMUNICATION_TYPE'] : '';

		if($entityType === '' || $entityID <= 0)
		{
			$result['ENTITY_COMMUNICATIONS'] = array('ERROR' => 'Invalid data');
		}
		else
		{
			$result['ENTITY_COMMUNICATIONS'] = GetCrmEntityCommunications($entityType, $entityID, $communicationType);
		}
	}

	__CrmActivityEditorEndResonse($result);
}
else
{
	__CrmActivityEditorEndResonse(array('ERROR' => 'Unknown action'));
}
?>
