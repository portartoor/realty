<?php

namespace Bitrix\Disk\Rest\Service;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\RestException;
use Bitrix\Disk\Rest\Entity;
use Bitrix\Disk;

final class File extends BaseObject
{
	/**
	 * Returns field descriptions (type, possibility to usage in filter, in render).
	 * @return array
	 */
	protected function getFields()
	{
		$storage = new Entity\File;
		return $storage->getFields();
	}

	/**
	 * Returns file by id.
	 * @param int $id Id of file.
	 * @return Disk\File
	 * @throws RestException
	 */
	protected function getWorkObjectById($id)
	{
		return $this->getFileById($id);
	}

	/**
	 * Deletes file by id.
	 * @param int $id Id of file.
	 * @return bool
	 * @throws RestException
	 */
	protected function delete($id)
	{
		$file = $this->getFileById($id);
		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if(!$file->canDelete($securityContext))
		{
			throw new AccessException;
		}
		if(!$file->delete($this->userId))
		{
			$this->errorCollection->add($file->getErrors());
			return false;
		}

		return true;
	}

	/**
	 * Creates new version of file.
	 * @param int $id Id of file.
	 * @param string|array $fileContent File content. General format in REST.
	 * @return Disk\Version|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function uploadVersion($id, $fileContent)
	{
		$file = $this->getFileById($id);
		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if(!$file->canUpdate($securityContext))
		{
			throw new AccessException;
		}
		$fileData = \CRestUtil::saveFile($fileContent);
		if(!$fileData)
		{
			throw new RestException('Could not save file.');
		}
		$newFile = $file->uploadVersion($fileData, $this->userId);
		if(!$newFile)
		{
			$this->errorCollection->add($file->getErrors());
			return null;
		}

		return $file;
	}

	/**
	 * Returns new or existent external link for current user on the file.
	 * @param int $id Id of file.
	 * @return null|string
	 * @throws AccessException
	 */
	protected function getExternalLink($id)
	{
		/** @var Disk\File $file */
		$file = $this->get($id);

		$extLinks = $file->getExternalLinks(array(
			'filter' => array(
				'OBJECT_ID' => $file->getId(),
				'CREATED_BY' => $this->userId,
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
				'IS_EXPIRED' => false,
			),
			'limit' => 1,
		));
		$extModel = array_pop($extLinks);
		if(!$extModel)
		{
			$extModel = $file->addExternalLink(array(
				'CREATED_BY' => $this->userId,
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
			));
		}
		if(!$extModel)
		{
			$this->errorCollection->add($file->getErrors());

			return null;
		}

		return Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
			'hash' => $extModel->getHash(),
			'action' => 'default',
		), true);
	}

	/**
	 * Sends file content.
	 *
	 * The method is invoked by \CRestUtil::METHOD_DOWNLOAD.
	 *
	 * @param int $id Id of file.
	 * @throws AccessException
	 * @throws RestException
	 * @return void
	 */
	protected function download($id)
	{
		/** @var Disk\File $file */
		$file = $this->get($id);

		$fileData = $file->getFile();
		if(!$fileData)
		{
			throw new RestException('Could not get content of file');
		}
		\CFile::viewByUser($fileData, array('force_download' => true, 'cache_time' => 0, 'attachment_name' => $file->getName()));
	}
}