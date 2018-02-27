<?php

namespace Bitrix\Sale\Cashbox;

use Bitrix\Main;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaymentCollection;
use Bitrix\Sale\Shipment;
use Bitrix\Sale\ShipmentCollection;
use Bitrix\Sale\PaySystem;

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class SellOrderCheck
 * @package Bitrix\Sale\Cashbox
 */
class SellOrderCheck extends Check
{
	/**
	 * @return string
	 */
	public static function getType()
	{
		return 'sellorder';
	}

	/**
	 * @return string
	 */
	public static function getName()
	{
		return Main\Localization\Loc::getMessage('SALE_CASHBOX_SELL_ORDER_NAME');
	}
	
	/**
	 * @return array
	 */
	public function getDataForCheck()
	{
		$result = array(
			'type' => static::getType(),
			'unique_id' => $this->getField('ID'),
			'items' => array(),
			'date_create' => new Main\Type\DateTime()
		);

		$order = null;
		$discountList = array();
		$entities = $this->getEntities();

		if ($entities)
		{
			foreach ($entities as $entity)
			{
				if ($order === null)
				{
					$order = CheckManager::getOrder($entity);
					$discount = $order->getDiscount();
					$discount->calculate();
					$discountList = $discount->getApplyResult();
				}

				if ($entity instanceof Payment)
				{
					$paySystem = PaySystem\Manager::getById($entity->getPaymentSystemId());

					$result['payments'][] = array(
						'is_cash' => $paySystem['IS_CASH'],
						'sum' => $entity->getSum()
					);
				}
				elseif ($entity instanceof Shipment)
				{
					$item = array(
						'name' => Main\Localization\Loc::getMessage('SALE_CASHBOX_PREPAYMENT_DELIVERY'),
						'price' => (float)$entity->getField('BASE_PRICE_DELIVERY'),
						'quantity' => 1,
						'vat' => 0
					);

					if ($discountList['PRICES']['DELIVERY']['DISCOUNT'] > 0)
					{
						$item['discount'] = array(
							'discount' => $discountList['PRICES']['DELIVERY']['DISCOUNT'],
							'discount_type' => 'C',
						);
					}

					$result['items'][] = $item;
				}
			}

			if ($order !== null)
			{
				$properties = $order->getPropertyCollection();
				$email = $properties->getUserEmail();
				$result['client_email'] = $email->getValue();

				$basketCollection = $order->getBasket();
				if ($basketCollection)
				{
					/** @var BasketItem $basketItem */
					foreach ($basketCollection as $basketItem)
					{
						$vatInfo = array();
						if (Main\Loader::includeModule('catalog'))
						{
							$dbRes = \CCatalogProduct::GetVATInfo($basketItem->getProductId());
							$vatInfo = $dbRes->Fetch();
						}

						$item = array(
							'name' => $basketItem->getField('NAME'),
							'price' => $basketItem->getBasePrice(),
							'quantity' => (float)$basketItem->getQuantity(),
							'vat' => $vatInfo ? $vatInfo['ID'] : 0
						);

						if ($discountList['PRICES']['BASKET'][$basketItem->getId()]['DISCOUNT'] > 0)
						{
							$item['discount'] = array(
								'discount' => $discountList['PRICES']['BASKET'][$basketItem->getId()]['DISCOUNT'],
								'discount_type' => 'C',
							);
						}

						$result['items'][] = $item;
					}
				}
			}
		}

		return $result;
	}
}