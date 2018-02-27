<?php
namespace Bitrix\Sale\Exchange;


use Bitrix\Sale\Exchange\Entity\OrderImport;
use Bitrix\Sale\Exchange\Entity\PaymentImport;
use Bitrix\Sale\Exchange\Entity\ShipmentImport;
use Bitrix\Sale\Internals\Entity;
use Bitrix\Sale\Result;

interface ICollision
{

    /**
     * @param $entityTypeId
     * @param $typeId
     * @param Entity $entity
     * @param null $message
     * @return mixed
     */
    public function addItem($entityTypeId, $typeId, Entity $entity, $message=null);

    /**
     * @param $entityTypeId
     * @return mixed
     */
    public static function getCurrent($entityTypeId);

    /**
     * @return mixed
     */
    public function getEntity();

    /**
     * @return int
     */
    public function getTypeId();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return string
     */
    public function getTypeName();

    /**
     * @param Entity $entity
     */
    public function setEntity(Entity $entity);
}

interface ICollisionOrder extends ICollision
{
    /**
     * @param OrderImport $entity
     * @return Result
     */
    public function resolve(OrderImport $entity);
}

interface ICollisionPayment extends ICollision
{
    /**
     * @param PaymentImport $entity
     * @return Result
     */
    public function resolve(PaymentImport $entity);
}

interface ICollisionShipment extends ICollision
{
    /**
     * @param ShipmentImport $entity
     * @return Result
     */
    public function resolve(ShipmentImport $entity);
}

interface ICollisionProfile extends ICollision
{
    /**
     * @param ProfileImport $entity
     * @return Result
     */
    public function resolve(ProfileImport $entity);
}