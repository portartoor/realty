<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Security\ParameterSigner;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DownloadController extends Internals\Controller
{
	const ERROR_COULD_NOT_FIND_VERSION   = 'DISK_DC_22003';
	const ERROR_COULD_NOT_FIND_FILE      = 'DISK_DC_22004';
	const ERROR_BAD_RIGHTS               = 'DISK_DC_22005';
	const ERROR_COULD_NOT_FIND_REAL_FILE = 'DISK_DC_22006';

	protected $fileId;
	protected $versionId;
	/** @var File */
	protected $file;
	/** @var Version */
	protected $version;

	protected function listActions()
	{
		return array(
			'showFile' => array(
				'method' => array('GET'),
				'redirect_on_auth' => true,
				'close_session' => true,
			),
			'downloadFile',
			'downloadVersion',
			'downloadArchive' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'copyToMe' => array(
				'method' => array('POST', 'GET'),
				'check_csrf_token' => true,
			),
			'downloadByExternalLink' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
		);
	}

	protected function onBeforeActionShowFile()
	{
		return $this->onWorkWithOneFile();
	}

	protected function onBeforeActionDownloadFile()
	{
		return $this->onWorkWithOneFile();
	}

	protected function onBeforeActionDownloadVersion()
	{
		return $this->onWorkWithOneFile();
	}

	protected function onBeforeActionCopyToMe()
	{
		return $this->onWorkWithOneFile();
	}

	private function onWorkWithOneFile()
	{
		if(!$this->checkRequiredGetParams(array('fileId')))
		{
			return new EventResult(EventResult::ERROR);
		}

		$this->fileId = (int)$this->request->getQuery('fileId');
		if($this->request->getQuery('versionId'))
		{
			$this->versionId = (int)$this->request->getQuery('versionId');
		}

		$this->file = File::loadById($this->fileId, array('STORAGE'));
		if(!$this->file)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_DOWNLOAD_CONTROLLER_ERROR_COULD_NOT_FIND_FILE'), self::ERROR_COULD_NOT_FIND_FILE));

			return new EventResult(EventResult::ERROR);
		}

		if($this->file instanceof FileLink && !$this->file->getRealObject())
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_DOWNLOAD_CONTROLLER_ERROR_COULD_NOT_FIND_FILE'), self::ERROR_COULD_NOT_FIND_REAL_FILE));

			return new EventResult(EventResult::ERROR);
		}
		//todo refactor. The method send response. It's bad.
		$this->checkPermissions();

		return new EventResult(EventResult::SUCCESS);
	}

	protected function checkPermissions()
	{
		$securityContext = $this->file->getStorage()->getCurrentUserSecurityContext();
		if(!$this->file->canRead($securityContext))
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_DOWNLOAD_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS));

			if(Desktop::getDiskVersion())
			{
				$this->sendJsonErrorResponse();
			}
			//general for user we show simple message
			$this->sendResponse(Loc::getMessage('DISK_DOWNLOAD_CONTROLLER_ERROR_BAD_RIGHTS'));
		}
	}

	protected function processActionDownloadFile()
	{
		$fileData = $this->file->getFile();
		\CFile::viewByUser($fileData, array('force_download' => true, 'cache_time' => 0, 'attachment_name' => $this->file->getName()));
	}

	protected function processActionShowFile()
	{
		$fileName = $this->file->getName();
		$fileData = $this->file->getFile();

		if(!$fileData)
		{
			$this->end();
		}

		$isImage = TypeFile::isImage($fileData['ORIGINAL_NAME']);
		$cacheTime = $isImage? 86400 : 0;

		$width = $this->request->getQuery('width');
		$height = $this->request->getQuery('height');
		if ($isImage && ($width > 0 || $height > 0))
		{
			$signature = $this->request->getQuery('signature');
			if(!$signature)
			{
				$this->sendJsonInvalidSignResponse('Empty signature');
			}
			if(!ParameterSigner::validateImageSignature($signature, $this->file->getId(), $width, $height))
			{
				$this->sendJsonInvalidSignResponse('Invalid signature');
			}

			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$tmpFile = \CFile::resizeImageGet($fileData, array('width' => $width, 'height' => $height), ($this->request->getQuery('exact') === 'Y' ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL), true, false, true);
			$fileData['FILE_SIZE'] = $tmpFile['size'];
			$fileData['SRC'] = $tmpFile['src'];
		}

		\CFile::viewByUser($fileData, array('force_download' => false, 'cache_time' => $cacheTime, 'attachment_name' => $fileName));
	}

	protected function processActionDownloadVersion()
	{
		$this->version = $this->file->getVersion($this->versionId);
		if(!$this->version)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_DOWNLOAD_CONTROLLER_ERROR_COULD_NOT_FIND_VERSION'), self::ERROR_COULD_NOT_FIND_VERSION));
			$this->sendJsonErrorResponse();
		}

		$fileData = $this->version->getFile();
		\CFile::viewByUser($fileData, array('force_download' => false, 'cache_time' => 0, 'attachment_name' => $this->file->getName()));
	}

	protected function processActionCopyToMe()
	{
		$userStorage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		if(!$userStorage)
		{
			$this->errorCollection->addOne(new Error('Could not find storage for current user'));
			$this->sendJsonErrorResponse();
		}
		$folder = $userStorage->getFolderForSavedFiles();
		if(!$folder)
		{
			$this->errorCollection->addOne(new Error('Could not find folder for created files'));
			$this->sendJsonErrorResponse();
		}
		$newFile = $this->file->copyTo($folder, $this->getUser()->getId(), true);

		if(!$newFile)
		{
			$this->errorCollection->addOne(new Error('Could not copy file to storage for current user'));
			$this->sendJsonErrorResponse();
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		$viewUrl = $urlManager->encodeUrn(
			$urlManager->getUrlFocusController('showObjectInGrid', array(
				'objectId' => $newFile->getId(),
			))
		);
		$runViewerUrl = $urlManager->encodeUrn(
			$urlManager->getUrlFocusController('showObjectInGrid', array(
				'objectId' => $newFile->getId(),
				'cmd' => 'show',
			))
		);

		$this->sendJsonSuccessResponse(array(
			'newId' => $newFile->getId(),
			'viewUrl' => $viewUrl,
			'runViewUrl' => $runViewerUrl,
		));
	}

	protected function processActionDownloadArchive($signature, array $objectIds)
	{
		if(!ParameterSigner::validateArchiveSignature($signature, $objectIds))
		{
			$this->sendJsonInvalidSignResponse('Invalid signature');
		}

		if(!ZipNginx\Configuration::isEnabled())
		{
			$this->errorCollection[] = new Error('Work with mod_zip is disabled in module settings.');
			$this->sendJsonErrorResponse();
		}

		$zipArchive = new ZipNginx\Archive('archive' . date('y-m-d') . '.zip');

		foreach($objectIds as $id)
		{
			//now we can't allow to download whole folder.
			$file = File::loadById($id);
			if(!$file)
			{
				continue;
			}

			$storage = $file->getStorage();
			if(!$storage)
			{
				continue;
			}

			$securityContext = $storage->getCurrentUserSecurityContext();
			if(!$file->canRead($securityContext))
			{
				continue;
			}

			$zipArchive->addEntry(
				ZipNginx\ArchiveEntry::createFromFile($file)
			);
		}

		if($zipArchive->isEmpty())
		{
			$this->errorCollection[] = new Error('Archive is empty');
			$this->sendJsonErrorResponse();
		}

		$zipArchive->send();
		$this->end();
	}

	protected function processActionDownloadByExternalLink($externalLink)
	{
		if(!ExternalLink::isValidValueForField('HASH', $externalLink, $this->errorCollection))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var ExternalLink $externalLink */
		$externalLink = ExternalLink::load(
			array(
				'=HASH' => $externalLink,
			),
			array(
				'FILE'
			)
		);

		if (!$externalLink || $externalLink->isExpired() || $externalLink->isSpecificVersion() || !$externalLink->getFile())
		{
			$this->errorCollection[] = new Error('Could not find external link');
			$this->sendJsonErrorResponse();
		}

		if ($externalLink->hasPassword())
		{
			$this->errorCollection[] = new Error('Could not use external link with password');
			$this->sendJsonErrorResponse();
		}

		$file = $externalLink->getFile();
		$fileData = $file->getFile();

		\CFile::viewByUser($fileData, array('force_download' => false, 'attachment_name' => $file->getName()));
	}
}