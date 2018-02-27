<?php

namespace Bitrix\Sale\Cashbox;

use Bitrix\Main;
use Bitrix\Main\Localization;
use Bitrix\Sale\Result;
use Bitrix\Catalog;

Localization\Loc::loadMessages(__FILE__);

class CashboxAtolFarm extends Cashbox implements IPrintImmediately
{
	/**
	 * @param Check $check
	 * @return array
	 */
	public function buildCheckQuery(Check $check)
	{

		$data = $check->getDataForCheck();

		/** @var Main\Type\DateTime $dateTime */
		$dateTime = $data['date_create'];

		$result = array(
			'timestamp' => $dateTime->format('d.m.Y H:i:s'),
			'service' => array(
				'inn' => $this->getValueFromSettings('SERVICE', 'INN'),
				'callback_url' => $this->getCallbackUrl(),
				'payment_address' => $this->getValueFromSettings('SERVICE', 'P_ADDRESS'),
			),
			'receipt' => array(
				'attributes' => array(
					'email' => $data['client_email'],
					'phone' => $data['client_phone'],
					'sno' => $this->getValueFromSettings('TAX', 'SNO'),
				),
				'payments' => array(),
				'items' => array(),
				'total' => (float)$data['total_sum']
			)
		);

		foreach ($data['payments'] as $payment)
		{
			$result['receipt']['payments'][] = array(
				'type' => (int)$this->getValueFromSettings('PAYMENT_TYPE', $payment['is_cash']),
				'sum' => (float)$payment['sum']
			);
		}

		foreach ($data['items'] as $i => $item)
		{
			$vat = $this->getValueFromSettings('VAT', $item['vat']);

			$result['receipt']['items'][] = array(
				'name' => $item['name'],
				'price' => (float)$item['price'],
				'sum' => (float)$item['sum'],
				'quantity' => $item['quantity'],
				'tax' => ($vat !== null) ? $vat : 'none'
			);
		}

		return $result;
	}

	/**
	 * @return string
	 */
	private function getCallbackUrl()
	{
		$context = Main\Application::getInstance()->getContext();
		$scheme = $context->getRequest()->isHttps() ? 'https' : 'http';
		$server = $context->getServer();
		$domain = $server->getServerName();

		if (preg_match('/^(?<domain>.+):(?<port>\d+)$/', $domain, $matches))
		{
			$domain = $matches['domain'];
			$port   = $matches['port'];
		}
		else
		{
			$port = $server->getServerPort();
		}
		$port = in_array($port, array(80, 443)) ? '' : ':'.$port;

		return sprintf('%s://%s%s/bitrix/tools/sale_farm_check_print.php', $scheme, $domain, $port);
	}

	/**
	 * @return string
	 */
	public static function getName()
	{
		return Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_TITLE');
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected static function extractCheckData(array $data)
	{
		$result = array();

		if (!$data['uuid'])
			return $result;

		$data = CheckManager::getCheckInfoByExternalUuid($data['uuid']);

		if ($data['error'])
			$result['ERROR'] = array('MESSAGE' => $data['error']);

		$result['ID'] = $data['ID'];
		$result['CHECK_TYPE'] = $data['TYPE'];
		$result['LINK_PARAMS'] = array();

		return $result;
	}

	/**
	 * @param $id
	 * @return array
	 */
	public function buildZReportQuery($id)
	{
		return array();
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected static function extractZReportData(array $data)
	{
		return array();
	}

	/**
	 * @return array
	 */
	private function getCheckTypeMap()
	{
		return array(
			SellOrderCheck::getType() => 'sell',
			SellCheck::getType() => 'sell',
			SellReturnCashCheck::getType() => 'sell_refund',
			SellReturnCheck::getType() => 'sell_refund'
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
	 * @param $operation
	 * @return string
	 */
	private function createUrl($operation)
	{
		$groupCode = $this->getField('NUMBER_KKM');
		$ip = $this->getValueFromSettings('CONNECTION', 'IP');
		$port = $this->getValueFromSettings('CONNECTION', 'PORT');

		return 'http://'.$ip.':'.$port.'/v2/'.$groupCode.'/'.$operation;
	}

	/**
	 * @param Check $check
	 * @return Result
	 */
	public function printImmediately(Check $check)
	{
		$result = new Result();

		$checkTypes = $this->getCheckTypeMap();
		$url = $this->createUrl($checkTypes[$check::getType()]);

		$data = static::buildCheckQuery($check);
		$json = Main\Web\Json::encode($data);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_USERAGENT, "1C-Bitrix");
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		$content = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlError = curl_error($ch);
		curl_close($ch);

		if ($content !== false)
		{
			try
			{
				$decodeData = Main\Web\Json::decode($content);
				$result->setData(array('UUID' => $decodeData['uuid']));
			}
			catch (Main\ArgumentException $e)
			{
				$result->addError(new Main\Error($e->getMessage()));
			}
		}
		else
		{
			$result->addError(new Main\Error($curlError, $httpCode));
		}

		return $result;
	}

	/**
	 * @param int $modelId
	 * @return array
	 */
	public static function getSettings($modelId = 0)
	{
		$settings = array(
			'CONNECTION' => array(
				'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_CONNECTION'),
				'ITEMS' => array(
					'IP' => array(
						'TYPE' => 'STRING',
						'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_CONNECTION_IP_LABEL')
					),
					'PORT' => array(
						'TYPE' => 'STRING',
						'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_CONNECTION_PORT_LABEL')
					),
				)
			),
			'SERVICE' => array(
				'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_SERVICE'),
				'ITEMS' => array(
					'INN' => array(
						'TYPE' => 'STRING',
						'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_SERVICE_INN_LABEL')
					),
					'P_ADDRESS' => array(
						'TYPE' => 'STRING',
						'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_SERVICE_P_ADDRESS_LABEL')
					),
				)
			)
		);

		$settings['PAYMENT_TYPE'] = array(
			'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_P_TYPE'),
			'ITEMS' => array()
		);

		$systemPaymentType = array('Y' => 0, 'N' => 1, 'A' => 1);
		foreach ($systemPaymentType as $type => $value)
		{
			$settings['PAYMENT_TYPE']['ITEMS'][$type] = array(
				'TYPE' => 'STRING',
				'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_P_TYPE_LABEL_'.$type),
				'VALUE' => $value
			);
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
					'VALUE' => 'none'
				);

				$defaultVat = array(0 => 'vat0', 10 => 'vat10', 18 => 'vat18');
				foreach ($vatList as $vat)
				{
					$value = '';
					if (isset($defaultVat[(int)$vat['RATE']]))
						$value = $defaultVat[(int)$vat['RATE']];

					$settings['VAT']['ITEMS'][(int)$vat['ID']] = array(
						'TYPE' => 'STRING',
						'LABEL' => $vat['NAME'].' ['.(int)$vat['RATE'].'%]',
						'VALUE' => $value
					);
				}
			}
		}

		$settings['TAX'] = array(
			'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_SNO'),
			'ITEMS' => array(
				'SNO' => array(
					'TYPE' => 'ENUM',
					'LABEL' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SETTINGS_SNO_LABEL'),
					'VALUE' => 'osn',
					'OPTIONS' => array(
						'osn' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SNO_OSN'),
						'usn_income' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SNO_UI'),
						'usn_income_outcome' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SNO_UIO'),
						'envd' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SNO_ENVD'),
						'esn' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SNO_ESN'),
						'patent' => Localization\Loc::getMessage('SALE_CASHBOX_ATOL_FARM_SNO_PATENT')
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

		if (empty($data['NUMBER_KKM']))
		{
			$result->addError(new Main\Error(Localization\Loc::getMessage('SALE_CASHBOX_ATOL_VALIDATE_E_NUMBER_KKM')));
		}

		if (empty($data['SETTINGS']['SERVICE']['INN']))
		{
			$result->addError(new Main\Error(Localization\Loc::getMessage('SALE_CASHBOX_ATOL_VALIDATE_E_INN')));
		}

		if (empty($data['SETTINGS']['SERVICE']['P_ADDRESS']))
		{
			$result->addError(new Main\Error(Localization\Loc::getMessage('SALE_CASHBOX_ATOL_VALIDATE_E_ADDRESS')));
		}

		if (empty($data['SETTINGS']['CONNECTION']['IP']))
		{
			$result->addError(new Main\Error(Localization\Loc::getMessage('SALE_CASHBOX_ATOL_VALIDATE_E_IP')));
		}

		if (empty($data['SETTINGS']['CONNECTION']['PORT']))
		{
			$result->addError(new Main\Error(Localization\Loc::getMessage('SALE_CASHBOX_ATOL_VALIDATE_E_PORT')));
		}

		return $result;
	}

}
