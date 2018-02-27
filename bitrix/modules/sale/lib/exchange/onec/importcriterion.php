<?php
namespace Bitrix\Sale\Exchange\OneC;


use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Sale\Exchange;

class ImportCriterion
    implements Exchange\ICriterion
{
    protected $entity = null;

    /**
     * @param array $fields
     * @return bool
     */
    public function equals(array $fields)
    {
        /** @var Sale\Internals\CollectableEntity $entity */
        $entity = $this->getEntity();
        if(empty($entity))
        {
            return true;
        }

        if(($entity->getField('VERSION_1C') != $fields['VERSION_1C']) ||
            (strlen($entity->getField('VERSION_1C'))<=0 || strlen($fields['VERSION_1C'])<=0)
        )
        {
            return true;
        }

        return false;
    }

    /**
     * @return null|Sale\Internals\CollectableEntity $entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param $entityTypeId
     * @param $entity
     * @return static
     * @throws Main\ArgumentException
     * @throws Main\NotImplementedException
     */
    public static function getCurrent($entityTypeId, $entity)
    {
        if(!Exchange\EntityType::IsDefined($entityTypeId))
        {
            throw new Main\ArgumentOutOfRangeException('Is not defined', Exchange\EntityType::FIRST, Exchange\EntityType::LAST);
        }

        /** @var Exchange\ICriterionOrder|Exchange\ICriterionPayment|Exchange\ICriterionShipment|Exchange\ICriterionProfile $criterion */
        $criterion =  new static();
        $criterion->setEntity($entity);

        return $criterion;
    }
}

class CriterionOrder extends ImportCriterion
    implements Exchange\ICriterionOrder
{
    /**
     * @param Sale\BasketItem $basketItem
     * @param array $fields
     * @return bool
     */
    public function equalsBasketItemTax(Sale\BasketItem $basketItem, array $fields)
    {
        if($fields['TAX']['VAT_RATE'] != $basketItem->getVatRate() &&
            ($fields['TAX']['VAT_INCLUDED']<>'Y' && $fields['PRICE']<>$basketItem->getPrice())
        )
        {
            return true;
        }

        return false;
    }

    /**
     * @param Sale\BasketItem $basketItem
     * @param array $fields
     * @return bool
     */
    public function equalsBasketItem(Sale\BasketItem $basketItem, array $fields)
    {
        if($fields['QUANTITY'] != $basketItem->getQuantity() ||
            $fields['PRICE'] != $basketItem->getPrice() ||
            $fields['VAT_RATE'] != $basketItem->getVatRate() ||
            $fields['DISCOUNT_PRICE'] != $basketItem->getDiscountPrice())
        {
            return true;
        }

        return false;
    }

    /**
     * @param Sale\BasketItem $basketItem
     * @param array $fields
     * @return bool
     */
    public function equalsBasketItemDiscount(Sale\BasketItem $basketItem, array $fields)
    {
        if($fields['DISCOUNT']['PRICE'] != $basketItem->getDiscountPrice() && intval($fields['DISCOUNT']['PRICE'])>0)
        {
            return true;
        }

        return false;
    }

    /**
     * @param Sale\Order $entity
     * @return void
     */
    public function setEntity(Sale\Order $entity=null)
    {
        $this->entity = $entity;
    }
}

class CriterionShipment extends ImportCriterion
    implements Exchange\ICriterionShipment
{
    /**
     * @param array $fields
     * @param bool|true $withoutSystem
     * @return bool
     */
    public function equalsForList(array $fields, $withoutSystem = true)
    {
        /** @var Sale\Shipment $entity */
        $entity = $this->getEntity();

        if(!$entity->isShipped() && ($withoutSystem || !$entity->isSystem()))
        {
            if($this->equals($fields))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Sale\Shipment $entity
     * @return void
     */
    public function setEntity(Sale\Shipment $entity=null)
    {
        $this->entity = $entity;
    }
}

class CriterionPayment extends ImportCriterion
    implements Exchange\ICriterionPayment
{
    /**
     * @param Sale\Payment|null $entity
     * @return void
     */
    public function setEntity(Sale\Payment $entity=null)
    {
        $this->entity = $entity;
    }
}

class CriterionProfile extends ImportCriterion
    implements Exchange\ICriterionProfile
{
    /**
     * @param array $fields
     * @return bool
     */
    public function equals(array $fields)
    {
        $entity = $this->getEntity();
        if(empty($entity))
        {
            return true;
        }

        if(($entity->getField('USER_PROFILE_VERSION') != $fields['VERSION_1C']) ||
            (strlen($entity->getField('USER_PROFILE_VERSION'))<=0 || strlen($fields['VERSION_1C'])<=0)
        )
        {
            return true;
        }

        return false;
    }

    /**
     * @param Exchange\ProfileImport|null $entity
     * @return void
     */
    public function setEntity(Exchange\ProfileImport $entity=null)
    {
        $this->entity = $entity;
    }
}