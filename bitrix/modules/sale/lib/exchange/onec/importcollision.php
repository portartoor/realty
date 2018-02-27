<?php
namespace Bitrix\Sale\Exchange\OneC;


use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Error;
use Bitrix\Main\NotImplementedException;
use Bitrix\Sale\Exchange\Entity\OrderImport;
use Bitrix\Sale\Exchange\Entity\PaymentImport;
use Bitrix\Sale\Exchange\Entity\ShipmentImport;
use Bitrix\Sale\Exchange\EntityCollisionType;
use Bitrix\Sale\Exchange\EntityType;
use Bitrix\Sale\Exchange\ICollision;
use Bitrix\Sale\Exchange\ICollisionOrder;
use Bitrix\Sale\Exchange\ICollisionPayment;
use Bitrix\Sale\Exchange\ICollisionProfile;
use Bitrix\Sale\Exchange\ICollisionShipment;
use Bitrix\Sale\Exchange\ProfileImport;
use Bitrix\Sale\Internals\Entity;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\Result;
use Bitrix\Sale\Shipment;

class ImportCollision implements ICollision
{
    protected $entityTypeId = EntityType::UNDEFINED;
    protected $typeId = null;
    protected $entity = null;
    protected $message = null;

    /**
     * @param $entityTypeId
     * @param $typeId
     * @param Entity $entity
     * @param null $message
     * @throws ArgumentOutOfRangeException
     * @throws NotImplementedException
     */
    public function addItem($entityTypeId, $typeId, Entity $entity, $message=null)
    {
        if(!is_int($entityTypeId))
        {
            $entityTypeId = (int)$entityTypeId;
        }
        if(!EntityType::IsDefined($entityTypeId))
        {
            throw new ArgumentOutOfRangeException('Is not defined', EntityType::FIRST, EntityType::LAST);
        }

        if(!is_int($typeId))
        {
            $typeId = (int)$typeId;
        }

        if(!EntityCollisionType::isDefined($typeId))
        {
            throw new ArgumentOutOfRangeException('Is not defined', EntityCollisionType::First, EntityCollisionType::Last);
        }

        $this->setEntity($entity);

        $this->entityTypeId = $entityTypeId;
        $this->typeId = $typeId;
        $this->message = $message;
    }

    /**
     * @param $entityTypeId
     * @return self
     * @throws ArgumentOutOfRangeException
     */
    public static function getCurrent($entityTypeId)
    {
        if(!EntityType::IsDefined($entityTypeId))
        {
            throw new ArgumentOutOfRangeException('Is not defined', EntityType::FIRST, EntityType::LAST);
        }

        $criterion =  new static();

        return $criterion;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return int
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        return EntityCollisionType::resolveName($this->typeId);
    }

    /**
     * @param Entity $entity
     */
    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
    }
}
class CollisionOrder extends ImportCollision
    implements ICollisionOrder
{

    /**
     * Resolve import collisions
     * @param OrderImport $item
     * @return Result
     */
    public function resolve(OrderImport $item)
    {
        $result = new Result();

        /** @var ImportSettings $settings */
        $settings = $item->getSettings();

        /** @var Order $order */
        $order = $item->getEntity();
        if(!empty($order))
        {
            if($order->isPaid())
                $item->setCollisions(EntityCollisionType::OrderIsPayed, $order);

            if($order->isShipped())
                $item->setCollisions(EntityCollisionType::OrderIsShipped, $order);

            if($order->getField('STATUS_ID') == $settings->finalStatusIdFor($item->getOwnerTypeId()))
                $item->setCollisions(EntityCollisionType::OrderFinalStatus, $order);
        }

        return $result;
    }
}

class CollisionPayment extends ImportCollision
    implements ICollisionPayment
{

    /**
     * Resolve import collisions
     * @param PaymentImport $item
     * @return Result
     */
    public function resolve(PaymentImport $item)
    {
        $result = new Result();

        /** @var Payment $payment */
        $payment = $item->getEntity();
        if(!empty($payment))
        {
            if($payment->isPaid())
                $item->setCollisions(EntityCollisionType::PaymentIsPayed, $payment);
        }

        return $result;
    }
}

class CollisionShipment extends ImportCollision
    implements ICollisionShipment
{

    /**
     * Resolve import collisions
     * @param ShipmentImport $item
     * @return Result
     */
    public function resolve(ShipmentImport $item)
    {
        $result = new Result();

        /** @var Shipment $shipment */
        $shipment = $item->getEntity();
        if(!empty($shipment))
        {
            if($shipment->isShipped())
                $item->setCollisions(EntityCollisionType::ShipmentIsShipped, $shipment);
        }
        return $result;
    }
}

class CollisionProfile extends ImportCollision
    implements ICollisionProfile
{

    /**
     * Resolve import collisions
     * @param ProfileImport $profileImport
     * @return Result
     */
    public function resolve(ProfileImport $profileImport)
    {
        return new Result();
    }
}