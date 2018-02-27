<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
abstract class EntityConversionWizard
{
	/** @var EntityConverter|null  */
	protected $converter = null;
	/** @var string  */
	protected $originUrl = '';
	/** @var string  */
	protected $redirectUrl = '';
	/** @var string  */
	protected $errorText = '';
	/** @var EntityConversionException|null  */
	protected $exception = null;
	/** @var bool */
	protected $enableRedirectToShow = true;
	/** @var bool */
	protected $isMobileContext = false;

	public function __construct(EntityConverter $converter)
	{
		$this->converter = $converter;

		if (
			is_callable(array('\Bitrix\MobileApp\Mobile', 'getApiVersion'))
			&& defined("BX_MOBILE") && BX_MOBILE === true
		)
			$this->isMobileContext = true;
	}
	abstract public function execute(array $contextData = null);
	public function hasOriginUrl()
	{
		return $this->originUrl !== '';
	}
	public function getOriginUrl()
	{
		return $this->originUrl;
	}
	public function setOriginUrl($url)
	{
		$this->originUrl = $url;
	}
	public function getErrorText()
	{
		return $this->exception !== null ? $this->exception->getLocalizedMessage() : $this->errorText;
	}
	public function getEntityTypeID()
	{
		return $this->converter->getEntityTypeID();
	}
	public function getEntityID()
	{
		return $this->converter->getEntityID();
	}
	/**
	 * Check if redirect to entity show page is enabled.
	 * @return bool
	 */
	public function isRedirectToShowEnabled()
	{
		return $this->enableRedirectToShow;
	}
	/**
	 * Enable or disable redirect to entity show page.
	 * @param boolean $enabled
	 */
	public function setRedirectToShowEnabled($enabled)
	{
		$this->enableRedirectToShow = (bool)$enabled;
	}
	/**
	 * Get converter result data.
	 * @return array
	 */
	public function getResultData()
	{
		return $this->converter->getResultData();
	}
	public function getRedirectUrl()
	{
		return $this->redirectUrl;
	}
	public function prepareDataForEdit($entityTypeID, array &$fields, $encode = true)
	{
	}
	public function prepareDataForSave($entityTypeID, array &$fields)
	{
	}
	protected function prepareFileUserFieldForSave($fieldName, array $fildInfo, array &$fields)
	{
		if(isset($fildInfo['MULTIPLE']) && $fildInfo['MULTIPLE'] === 'Y')
		{
			$results = array();
			if(is_array($fields[$fieldName]))
			{
				foreach($fields[$fieldName] as $fileInfo)
				{
					//HACK: Deletion flag may contain fileID or boolean value.
					$isDeleted = isset($fileInfo['del']) && ($fileInfo['del'] === true || $fileInfo['del'] === $fileInfo['old_id']);
					if($isDeleted)
					{
						continue;
					}

					if($fileInfo['tmp_name'] !== '')
					{
						$results[] = $fileInfo;
					}
					elseif($fileInfo['old_id'] !== '')
					{
						$isResolved = \CCrmFileProxy::TryResolveFile($fileInfo['old_id'], $file, array('ENABLE_ID' => true));
						if($isResolved)
						{
							$results[] = $file;
						}
					}
				}
			}
			$fields[$fieldName] = $results;
		}
		else
		{
			$fileInfo = $fields[$fieldName];
			//HACK: Deletion flag may contain fileID or boolean value.
			$isDeleted = isset($fileInfo['del']) && ($fileInfo['del'] === true || $fileInfo['del'] === $fileInfo['old_id']);
			if(!$isDeleted  && $fileInfo['tmp_name'] === '' && $fileInfo['old_id'] !== '')
			{
				$isResolved = \CCrmFileProxy::TryResolveFile($fields[$fieldName]['old_id'], $file, array('ENABLE_ID' => true));
				if($isResolved)
				{
					$fields[$fieldName] = $file;
				}
			}
		}
	}
	public function getEditFormLegend()
	{
		Main\Localization\Loc::loadMessages(__FILE__);

		$exceptionCode = $this->exception !== null ? (int)$this->exception->getCode() : 0;
		if($exceptionCode === EntityConversionException::AUTOCREATION_DISABLED
			|| $exceptionCode === EntityConversionException::HAS_WORKFLOWS)
		{
			return GetMessage(
				"CRM_ENTITY_CONV_WIZ_CUSTOM_FORM_LEGEND",
				array('#TEXT#' => $this->exception->getLocalizedMessage())
			);
		}

		return GetMessage("CRM_ENTITY_CONV_WIZ_FORM_LEGEND");
	}
	public function externalize()
	{
		$result = array(
			'originUrl' => $this->originUrl,
			'redirectUrl' => $this->redirectUrl,
			'converter' => $this->converter->externalize()
		);

		if($this->exception !== null)
		{
			$result['exception'] = $this->exception->externalize();
		}

		return $result;
	}
	public function internalize(array $params)
	{
		if(isset($params['originUrl']))
		{
			$this->originUrl = $params['originUrl'];
		}

		if(isset($params['redirectUrl']))
		{
			$this->redirectUrl = $params['redirectUrl'];
		}

		if(isset($params['converter']) && is_array($params['converter']))
		{
			$this->converter->internalize($params['converter']);
		}

		if(isset($params['exception']) && is_array($params['exception']))
		{
			$this->exception = new EntityConversionException();
			$this->exception->internalize($params['exception']);
		}
	}
}