<?php

namespace Bitrix\Sale\Cashbox;

use Bitrix\Main;
use Bitrix\Catalog;
use Bitrix\Main\Localization;
use Bitrix\Sale\Cashbox\Internals\KkmModelTable;
use Bitrix\Sale\Result;

Localization\Loc::loadMessages(__FILE__);

class CashboxBitrix extends Cashbox
{
	const TYPE_Z_REPORT = 1;
	const UUID_TYPE_CHECK = 'check';
	const UUID_TYPE_REPORT = 'report';
	const UUID_DELIMITER = '|';

	/**
	 * @param Check $check
	 * @return array
	 */
	public function buildCheckQuery(Check $check)
	{
		$result = array();

		$data = $check->getDataForCheck();
		foreach ($data['payments'] as $payment)
		{
			$result['payments'][] = array(
				'type' => $payment['is_cash'] === 'Y' ? 0 : 1,
				'value' => $payment['sum']
			);
		}

		$typeMap = $this->getCheckTypeMap();
		if (isset($typeMap[$data['type']]))
			$result['type'] = $typeMap[$data['type']];
		else
			return array();

		$result['uuid'] = static::buildUuid(static::UUID_TYPE_CHECK, $data['unique_id']);
		$result['zn'] = $this->getField('NUMBER_KKM');
		$result['items'] = array();
		foreach ($data['items'] as $item)
		{
			$vat = $this->getValueFromSettings('VAT', $item['vat']);

			$value = array(
				'name' => $item['name'],
				'price' => $item['base_price'],
				'quantity' => $item['quantity'],
				'VAT' => ($vat !== null) ? (int)$vat : 4
			);

			if (isset($item['discount']) && is_array($item['discount']))
			{
				$value['discount'] = $item['discount']['discount']*$item['quantity'];

				$discountType = $item['discount']['discount_type'] === 'P' ? 1 : 0;
				$value['discount_type'] = $discountType;
			}

			$result['items'][] = $value;
		}
		$result['client'] = $data['client_email'];

		/** @var Main\Type\DateTime $dateTime */
		$dateTime = $data['date_create'];
		$result['timestamp'] = (string)$dateTime->getTimestamp();

		return $result;
	}

	/**
	 * @param $id
	 * @return array
	 */
	public function buildZReportQuery($id)
	{
		$dateTime = new Main\Type\DateTime();

		return array(
			'type' => static::TYPE_Z_REPORT,
			'uuid' => static::buildUuid(static::UUID_TYPE_REPORT, $id),
			'timestamp' => (string)$dateTime->getTimestamp(),
			'zn' => $this->getField('NUMBER_KKM')
		);
	}

	/**
	 * @param $type
	 * @param $id
	 * @return string
	 */
	private static function buildUuid($type, $id)
	{
		$context = Main\Application::getInstance()->getContext();
		$server = $context->getServer();
		$domain = $server->getServerName();

		return $type.static::UUID_DELIMITER.$domain.static::UUID_DELIMITER.$id;
	}

	/**
	 * @param $uuid
	 * @return array
	 */
	private static function parseUuid($uuid)
	{
		$info = explode(static::UUID_DELIMITER, $uuid);

		return array('type' => $info[0], 'id' => $info[2]);
	}

	/**
	 * @return string
	 */
	public static function getName()
	{
		return Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_TITLE');
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public static function getCashboxList(array $data)
	{
		$result = array();

		if (isset($data['kkm']) && is_array($data['kkm']))
		{
			$factoryNum = array();
			foreach ($data['kkm'] as $kkm)
				$factoryNum[] = $kkm['zn'];

			$cashboxList = Manager::getListFromCache();
			foreach ($cashboxList as $item)
			{
				if (in_array($item['NUMBER_KKM'], $factoryNum))
					$result[$item['NUMBER_KKM']] = $item;
			}

			foreach ($data['kkm'] as $kkm)
			{
				if (!isset($result[$kkm['zn']]))
				{
					$result[$kkm['zn']] = array(
						'NUMBER_KKM' => $kkm['zn'],
						'NUMBER_FN' => $kkm['fn'],
						'HANDLER' => '\\'.get_called_class(),
						'CACHE' => $kkm['cache'],
						'INCOME' => $kkm['reg_income'],
						'NZ_SUM' => $kkm['nz_sum']
					);
				}

				$result[$kkm['zn']]['PRESENTLY_ENABLED'] = ($kkm['status'] === 'ok') ? 'Y' : 'N';
			}
		}

		return $result;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public static function applyPrintResult(array $data)
	{
		$processedIds = array();

		foreach ($data['kkm'] as $kkm)
		{
			if (isset($kkm['printed']) && is_array($kkm['printed']))
			{
				foreach ($kkm['printed'] as $item)
				{
					$uuid = static::parseUuid($item['uuid']);

					$result = null;
					if ($uuid['type'] === static::UUID_TYPE_CHECK)
					{
						$result = static::applyCheckResult($item);
					}
					elseif ($uuid['type'] === static::UUID_TYPE_REPORT)
					{
						$result = static::applyZReportResult($item);
					}

					if ($result !== null)
					{
						if ($result->isSuccess())
						{
							$processedIds[] = $item['uuid'];
						}
						else
						{
							$errors = $result->getErrors();
							foreach ($errors as $error)
							{
								if ($error instanceof Errors\Error)
								{
									$processedIds[] = $item['uuid'];
									break;
								}
							}
						}
					}
				}
			}
		}

		return $processedIds;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected static function extractCheckData(array $data)
	{
		$uuid = self::parseUuid($data['uuid']);
		$result = array(
			'ID' => $uuid['id'],
			'TYPE' => $uuid['type'],
		);

		if ($data['code'] !== 0 && isset($data['message']))
		{
			$errorMsg = Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_ERR'.$data['code']);
			if (!$errorMsg)
				$errorMsg = $data['message'];

			$errorType = static::getErrorType($data['code']);

			$result['ERROR'] = array(
				'MESSAGE' => $errorMsg,
				'TYPE' => ($errorType === Errors\Error::TYPE) ? Errors\Error::TYPE : Errors\Warning::TYPE
			);
		}

		$result['LINK_PARAMS'] = array('qr' => $data['qr']);

		return $result;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected static function extractZReportData(array $data)
	{
		$uuid = self::parseUuid($data['uuid']);
		$result = array(
			'ID' => $uuid['id'],
			'TYPE' => $uuid['type'],
		);

		if ($data['code'] !== 0 && isset($data['message']))
		{
			$errorMsg = Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_ERR'.$data['code']);
			if (!$errorMsg)
				$errorMsg = $data['message'];

			$errorType = static::getErrorType($data['code']);
			if ($errorType == null)
				$errorType = Errors\Warning::TYPE;

			$result['ERROR'] = array('MESSAGE' => $errorMsg, 'CODE' => $data['code'], 'TYPE' => $errorType);
		}

		$result['CASH_SUM'] = $data['payments_cache'];
		$result['CASHLESS_SUM'] = $data['reg_income'] - $data['payments_cache'];
		$result['CUMULATIVE_SUM'] = $data['nz_sum'];
		$result['RETURNED_SUM'] = $data['reg_return'];

		$result['LINK_PARAMS'] = array('qr' => $data['qr']);

		return $result;
	}

	/**
	 * @return array
	 */
	private function getCheckTypeMap()
	{
		return array(
			SellOrderCheck::getType() => 1,
			SellCheck::getType() => 1,
			SellReturnCashCheck::getType() => 2,
			SellReturnCheck::getType() => 2
		);
	}

	/**
	 * @param array $linkParams
	 * @return string
	 */
	public function getCheckLink(array $linkParams)
	{
		if (isset($linkParams['qr']) && !empty($linkParams['qr']))
		{
			/** @var Ofd $ofd */
			$ofd = $this->getOfd();
			if ($ofd !== null)
				return $ofd->generateCheckLink($linkParams['qr']);
		}

		return '';
	}

	/**
	 * @param $errorCode
	 * @throws Main\NotImplementedException
	 * @return int
	 */
	protected static function getErrorType($errorCode)
	{
		$errors = array(-3800, -3803, -3804, -3805, -3816, -3807, -3896, -3897);
		if (in_array($errorCode, $errors))
			return Errors\Error::TYPE;

		$warnings = array();
		if (in_array($errorCode, $warnings))
			return Errors\Warning::TYPE;

		return null;
	}

	/**
	 * @param int $modelId
	 * @return array
	 */
	public static function getSettings($modelId = 0)
	{
		$settings = array();

		if ($modelId > 0)
		{
			$data = KkmModelTable::getRowById($modelId);
			if (isset($data['SETTINGS']['PAYMENT_TYPE']))
			{
				$settings['PAYMENT_TYPE'] = array(
					'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_SETTINGS_P_TYPE'),
					'ITEMS' => array()
				);

				$systemPaymentType = array('Y', 'N', 'A');
				foreach ($systemPaymentType as $type)
				{
					$settings['PAYMENT_TYPE']['ITEMS'][$type] = array(
						'TYPE' => 'STRING',
						'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_SETTINGS_P_TYPE_LABEL_'.$type),
						'VALUE' => $data['SETTINGS']['PAYMENT_TYPE'][$type]
					);
				}
			}

			if (Main\Loader::includeModule('catalog'))
			{
				$dbRes = Catalog\VatTable::getList(array('filter' => array('ACTIVE' => 'Y')));
				$vatList = $dbRes->fetchAll();
				if ($vatList)
				{
					$settings['VAT'] = array(
						'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_SETTINGS_VAT'),
						'ITEMS' => array()
					);

					$settings['VAT']['ITEMS']['NOT_VAT'] = array(
						'TYPE' => 'STRING',
						'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_SETTINGS_VAT_LABEL_NOT_VAT'),
						'VALUE' => 4
					);

					foreach ($vatList as $vat)
					{
						$value = '';
						if (isset($data['SETTINGS']['VAT'][(int)$vat['RATE']]))
							$value = $data['SETTINGS']['VAT'][(int)$vat['RATE']];

						$settings['VAT']['ITEMS'][(int)$vat['ID']] = array(
							'TYPE' => 'STRING',
							'LABEL' => $vat['NAME'].' ['.(int)$vat['RATE'].'%]',
							'VALUE' => $value
						);
					}
				}
			}
		}

		$hours = array();
		for ($i = 0; $i < 24; $i++)
		{
			$value = ($i < 10) ? '0'.$i : $i;
			$hours[$i] = $value;
		}

		$minutes = array();
		for ($i = 0; $i < 60; $i+=5)
		{
			$value = ($i < 10) ? '0'.$i : $i;
			$minutes[$i] = $value;
		}

		$settings['Z_REPORT'] = array(
			'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_SETTINGS_Z_REPORT'),
			'ITEMS' => array(
				'TIME' => array(
					'TYPE' => 'DELIVERY_MULTI_CONTROL_STRING',
					'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_SETTINGS_Z_REPORT_LABEL'),
					'ITEMS' => array(
						'H' => array(
							'TYPE' => 'ENUM',
							'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_SETTINGS_Z_REPORT_LABEL_H'),
							'VALUE' => 23,
							'OPTIONS' => $hours
						),
						'M' => array(
							'TYPE' => 'ENUM',
							'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_SETTINGS_Z_REPORT_LABEL_M'),
							'VALUE' => 30,
							'OPTIONS' => $minutes
						),
					)
				)
			)
		);

		return $settings;
	}

	/**
	 * @param $data
	 * @return Result
	 */
	public static function validateSettings($data)
	{
		$result = new Result();

		if (empty($data['KKM_ID']))
		{
			$result->addError(new Main\Error(Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_VALIDATE_E_KKM_ID')));
		}

		if (empty($data['OFD']))
		{
			$result->addError(new Main\Error(Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_VALIDATE_E_OFD')));
		}

		if (empty($data['NUMBER_KKM']))
		{
			$result->addError(new Main\Error(Localization\Loc::getMessage('SALE_CASHBOX_BITRIX_VALIDATE_E_NUMBER_KKM')));
		}

		return $result;
	}

}
