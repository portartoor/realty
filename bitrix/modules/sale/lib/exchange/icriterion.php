<?php
namespace Bitrix\Sale\Exchange;


use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\Shipment;

interface ICriterion
{
    /**
     * @return mixed
     */
    public function getEntity();
    /**
     * @param array $fields
     * @return mixed
     */
    public function equals(array $fields);

    /**
     * @param $entityTypeId
     * @param $entity
     * @return mixed
     */
    public static function getCurrent($entityTypeId, $entity);
}

interface ICriterionOrder extends ICriterion
{
    /**
     * @param Order $entity
     * @return mixed
     */
    public function setEntity(Order $entity=null);

    /**
     * @param BasketItem $basketItem
     * @param array $fields
     * @return mixed
     */
    public function equalsBasketItemTax(BasketItem $basketItem, array $fields);

    /**
     * @param BasketItem $basketItem
     * @param array $fields
     * @return mixed
     */
    public function equalsBasketItem(BasketItem $basketItem, array $fields);

    /**
     * @param BasketItem $basketItem
     * @param array $fields
     * @return mixed
     */
    public function equalsBasketItemDiscount(BasketItem $basketItem, array $fields);
}

interface ICriterionShipment extends ICriterion
{
    /**
     * @param array $fields
     * @param $withoutSystem
     * @return mixed
     */
    public function equalsForList(array $fields, $withoutSystem=null);

    /**
     * @param Shipment $entity
     * @return mixed
     */
    public function setEntity(Shipment $entity=null);

}

interface ICriterionPayment extends ICriterion
{
    /**
     * @param Payment $entity|null
     * @return mixed
     */
    public function setEntity(Payment $entity=null);
}

interface ICriterionProfile extends ICriterion
{
    /**
     * @param ProfileImport $entity
     * @return mixed
     */
    public function setEntity(ProfileImport $entity=null);
}