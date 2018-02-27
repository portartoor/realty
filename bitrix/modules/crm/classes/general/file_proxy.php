<?php
class CCrmFileProxy
{
	public static function WriteFileToResponse($ownerTypeID, $ownerID, $fieldName, $fileID, &$errors, $options = array())
	{
		$ownerTypeID = intval($ownerTypeID);
		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);
		$ownerID = intval($ownerID);
		$fieldName = strval($fieldName);
		$fileID = intval($fileID);
		$options = is_array($options) ? $options : array();

		if(!CCrmOwnerType::IsDefined($ownerTypeID) || $ownerID <= 0 || $fieldName === '' || $fileID <= 0)
		{
			$errors[] = 'File not found';
			return false;
		}

		$authToken = isset($options['oauth_token']) ? strval($options['oauth_token']) : '';
		if($authToken !== '')
		{
			$authData = array();
			if(!(CModule::IncludeModule('rest')
				&& CRestUtil::checkAuth($authToken, CCrmRestService::SCOPE_NAME, $authData)
				&& CRestUtil::makeAuth($authData)))
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}

		if(!CCrmPerms::IsAdmin())
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
			$attrs = $userPermissions->GetEntityAttr($ownerTypeName, $ownerID);
			if($userPermissions->HavePerm($ownerTypeName, BX_CRM_PERM_NONE, 'READ')
				|| !$userPermissions->CheckEnityAccess($ownerTypeName, 'READ', isset($attrs[$ownerID]) ? $attrs[$ownerID] : array()))
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}

		$isDynamic = isset($options['is_dynamic']) ? (bool)$options['is_dynamic'] : true;
		if($isDynamic)
		{
			$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(
				CCrmOwnerType::ResolveUserFieldEntityID($ownerTypeID),
				$ownerID,
				LANGUAGE_ID
			);

			$field = is_array($userFields) && isset($userFields[$fieldName]) ? $userFields[$fieldName] : null;

			if(!(is_array($field) && $field['USER_TYPE_ID'] === 'file'))
			{
				$errors[] = 'File not found';
				return false;
			}

			$fileIDs = isset($field['VALUE'])
				? (is_array($field['VALUE'])
					? $field['VALUE']
					: array($field['VALUE']))
				: array();

			//The 'strict' flag must be 'false'. In MULTIPLE mode value is an array of integers. In SIGLE mode value is a string.
			if(!in_array($fileID, $fileIDs, false))
			{
				$errors[] = 'File not found';
				return false;
			}

			return self::InnerWriteFileToResponse($fileID, $errors, $options);
		}
		else
		{
			$fieldsInfo = isset($options['fields_info']) ? $options['fields_info'] : null;
			if(!is_array($fieldsInfo))
			{
				$fieldsInfo = CCrmOwnerType::GetFieldsInfo($ownerTypeID);
			}

			$fieldInfo = is_array($fieldsInfo) && isset($fieldsInfo[$fieldName]) ? $fieldsInfo[$fieldName] : array();
			$fieldInfoType = isset($fieldInfo['TYPE']) ? $fieldInfo['TYPE'] : '';

			if($fieldInfoType !== 'file')
			{
				$errors[] = 'File not found';
				return false;
			}

			if($fileID !== CCrmOwnerType::GetFieldIntValue($ownerTypeID, $ownerID, $fieldName))
			{
				$errors[] = 'File not found';
				return false;
			}

			return self::InnerWriteFileToResponse($fileID, $errors, $options);
		}
	}

	public static function WriteEventFileToResponse($eventID, $fileID, &$errors, $options = array())
	{
		$eventID = intval($eventID);
		$fileID = intval($fileID);

		if($eventID <= 0 || $fileID <= 0)
		{
			$errors[] = 'File not found';
			return false;
		}

		//Get event file IDs and check permissions
		$dbResult = CCrmEvent::GetListEx(
			array(),
			array(
				'=ID' => $eventID
				//'CHECK_PERMISSIONS' => 'Y' //by default
			),
			false,
			false,
			array('ID', 'FILES'),
			array()
		);

		$event = $dbResult ? $dbResult->Fetch() : null;

		if(!$event)
		{
			$errors[] = 'File not found';
			return false;
		}

		if(is_array($event['FILES']))
		{
			$eventFiles = $event['FILES'];
		}
		elseif(is_string($event['FILES']) && $event['FILES'] !== '')
		{
			$eventFiles = unserialize($event['FILES']);
		}
		else
		{
			$eventFiles = array();
		}

		if(
			empty($eventFiles)
			|| !is_array($eventFiles)
			|| !in_array($fileID, $eventFiles, true)
		)
		{
			$errors[] = 'File not found';
			return false;
		}

		return self::InnerWriteFileToResponse($fileID, $errors, $options);
	}

	private static function InnerWriteFileToResponse($fileID, &$errors, $options = array())
	{
		$fileInfo = CFile::GetFileArray($fileID);
		if(!is_array($fileInfo))
		{
			$errors[] = 'File not found';
			return false;
		}

		$options = is_array($options) ? $options : array();
		// �rutch for CFile::ViewByUser. Waiting for main 14.5.2
		$options['force_download'] = true;
		set_time_limit(0);
		CFile::ViewByUser($fileInfo, $options);

		return true;
	}

	public static function TryResolveFile($var, &$file, $options = array())
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$result = null;
		if(is_array($var))
		{
			if(isset($options['ENABLE_UPLOAD']) && $options['ENABLE_UPLOAD'] && self::IsUploadedFile($var))
			{
				$result = $var;
			}
		}
		elseif(is_numeric($var))
		{
			if(isset($options['ENABLE_ID']) && $options['ENABLE_ID'])
			{
				$result = CFile::MakeFileArray($var);
			}
		}
		elseif(is_string($var))
		{
			$path = CCrmUrlUtil::ToAbsoluteUrl($var);
			//Parent directories and not secure URLs are not allowed.
			if($path !== '' && !CHTTP::isPathTraversalUri($path) && CCrmUrlUtil::IsSecureUrl($path))
			{
				$result = CFile::MakeFileArray($path);
			}
		}

		if(is_array($result))
		{
			$result['MODULE_ID'] = 'crm';
			$file = $result;
			return true;
		}

		return false;
	}

	public static function IsUploadedFile($var)
	{
		return is_array($var) && isset($var['tmp_name']) && is_uploaded_file($var['tmp_name']) && file_exists($var['tmp_name']);
	}

	public static function WriteDiskFileToResponse($ownerTypeID, $ownerID, $fileID, &$errors, $options = array())
	{
		$ownerTypeID = (int)$ownerTypeID;
		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);
		$ownerID = (int)$ownerID;
		$fileID = (int)$fileID;
		$options = is_array($options) ? $options : array();

		if(!CCrmOwnerType::IsDefined($ownerTypeID) || $ownerID <= 0 || $fileID <= 0)
		{
			$errors[] = 'Invalid data ownerTypeID = '.$ownerTypeID.', ownerID = '.$ownerID.', fileID = '.$fileID;
			return false;
		}

		if($ownerTypeID !== CCrmOwnerType::Activity)
		{
			$errors[] = "The owner type '{$ownerTypeName}' is not supported in current context";
			return false;
		}

		$authToken = isset($options['oauth_token']) ? $options['oauth_token'] : '';
		if($authToken !== '')
		{
			$authData = array();
			if(!(CModule::IncludeModule('rest')
				&& CRestUtil::checkAuth($authToken, CCrmRestService::SCOPE_NAME, $authData)
				&& CRestUtil::makeAuth($authData)))
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}

		if(!CCrmActivity::CheckStorageElementExists($ownerID, CCrmActivityStorageType::Disk, $fileID))
		{
			$errors[] = 'File not found';
			return false;
		}

		$isPermitted = false;
		if(CCrmPerms::IsAdmin())
		{
			$isPermitted = true;
		}
		else
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
			$bindings = CCrmActivity::GetBindings($ownerID);
			foreach($bindings as $binding)
			{
				if(CCrmAuthorizationHelper::CheckReadPermission(
					$binding['OWNER_TYPE_ID'],
					$binding['OWNER_ID'],
					$userPermissions))
				{
					$isPermitted = true;
					break;
				}
			}
		}

		if(!$isPermitted)
		{
			$errors[] = 'Access denied.';
			return false;
		}

		Bitrix\Crm\Integration\DiskManager::writeFileToResponse($fileID);
		return true;
	}
}
?>
