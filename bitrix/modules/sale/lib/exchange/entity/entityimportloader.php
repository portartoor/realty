<?php
namespace Bitrix\Sale\Exchange\Entity;

use Bitrix\Main;
use Bitrix\Sale\Exchange\EntityType;
use Bitrix\Sale\Exchange\ImportSettings;
use Bitrix\Sale\Exchange\ISettings;
use Bitrix\Sale\Internals\OrderTable;
use Bitrix\Sale\Internals\PaymentTable;
use Bitrix\Sale\Internals\ShipmentTable;
use Bitrix\Sale\Internals\UserPropsTable;

/**
 * Class EntityImportLoader
 * @package Bitrix\Sale\Exchange\Entity
 * @internal
 */
class EntityImportLoader
{
    /** @var ISettings */
    protected $settings = null;

    /**
     * @return array
     * @throws Main\ArgumentException
     */
    protected static function getFields()
    {
        throw new Main\ArgumentException('The method is not implemented.');
    }

    /**
     * @return string
     */
    protected static function getExternalField()
    {
        return 'ID_1C';
    }

    /**
     * @param $number
     * @return null
     * @throws Main\ArgumentException
     */
    public function getByNumber($number)
    {
        $entity = static::getEntityTable();
        if($r = $entity::getById($number)->fetch())
        {
            return $r;
        }
        return null;
    }

    /**
     * @param $xmlId
     * @return null
     * @throws Main\ArgumentException
     */
    public function getByExternalId($xmlId)
    {
        if($xmlId === "")
        {
            throw new Main\ArgumentException('Is not defined', 'XML_1C_DOCUMENT_ID');
        }

        $entity = static::getEntityTable();

        if($r = $entity::getList(array(
            'select' => static::getFields(),
            'filter' => array(static::getExternalField() => $xmlId),
            'order' => array('ID' => 'DESC')))->fetch()
        )
        {
            return $r;
        }

        return null;
    }

    /**
     * @return object
     * @throws Main\ArgumentException
     */
    protected static function getEntityTable()
    {
        throw new Main\ArgumentException('The method is not implemented.');
    }

    /**
     * @param ISettings $settings
     */
    public function loadSettings(ISettings $settings)
    {
        $this->settings = $settings;
    }
}

class OrderImportLoader extends EntityImportLoader
{
    protected static function getFields()
    {
        return array(
            'ID',
            'ID_1C'
        );
    }

    public function getByNumber($number)
    {
        if($number === "")
        {
            throw new Main\ArgumentException('Is not defined', 'ID');
        }

        $accountNumberPrefix = $this->settings->prefixFor(EntityType::ORDER);

        if(is_numeric($number))
        {
            if($r = OrderTable::getById($number)->fetch())
                return $r;

            if($r = OrderTable::getList(array(
                'select' => array('ID'),
                'filter' => array('ID_1C' => $number),
                'order' => array('ID' => 'DESC')))->fetch()
            )
                return $r;

            if($r = OrderTable::getList(array(
                'select' => array('ID'),
                'filter' => array('ACCOUNT_NUMBER' => $number),
                'order' => array('ID' => 'DESC')))->fetch()
            )
                return $r;

            if ($accountNumberPrefix !== "")
            {
                if(strpos($number, $accountNumberPrefix) === 0)
                {
                    $number = substr($number, strlen($accountNumberPrefix));
                    if ($r = OrderTable::getById($number)->fetch())
                        return $r;
                }
            }
        }
        else
        {
            if ($r = OrderTable::getList(array(
                'select' => array('ID'),
                'filter' => array('ID_1C' => $number),
                'order' => array('ID' => 'DESC')))->fetch()
            )
                return $r;

            if ($r = OrderTable::getList(array(
                'select' => array('ID'),
                'filter' => array('ACCOUNT_NUMBER' => $number),
                'order' => array('ID' => 'DESC')))->fetch()
            )
                return $r;

            if($accountNumberPrefix != "")
            {
                if(strpos($number, $accountNumberPrefix) === 0)
                {
                    $number = substr($number, strlen($accountNumberPrefix));
                    if($r = OrderTable::getById($number)->fetch())
                        return $r;

                    if($r = OrderTable::getList(array(
                        'select' => array('ID'),
                        'filter' => array('ACCOUNT_NUMBER' => $number),
                        'order' => array('ID' => 'DESC')))->fetch()
                    )
                        return $r;
                }
            }
        }
        return null;
    }

    protected static function getEntityTable()
    {
        return new OrderTable();
    }
}

class PaymentImportLoader extends EntityImportLoader
{
    protected static function getFields()
    {
        return array(
            'ID',
            'ID_1C',
            'ORDER_ID'
        );
    }

    protected static function getEntityTable()
    {
        return new PaymentTable();
    }
}

class ShipmentImportLoader extends EntityImportLoader
{
    protected static function getFields()
    {
        return array(
            'ID',
            'ID_1C',
            'ORDER_ID'
        );
    }

    protected static function getEntityTable()
    {
        return new ShipmentTable();
    }
}

class ProfileImportLoader extends EntityImportLoader
{
    /**
     * @return string
     */
    protected static function getExternalField()
    {
        return 'XML_ID';
    }

    protected static function getFields()
    {
        return array(
            'ID'
        );
    }

    protected static function getEntityTable()
    {
        return new UserPropsTable();
    }
}