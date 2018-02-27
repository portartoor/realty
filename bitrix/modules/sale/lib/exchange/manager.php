<?php
namespace Bitrix\Sale\Exchange;


use Bitrix\Main\ArgumentOutOfRangeException;

final class Manager
{
    private static $instance = null;
    /** @var ISettings $settings */
    protected $settings = null;
    /** @var ICollisionOrder|ICollisionPayment|ICollisionShipment|ICollisionProfile $collision */
    protected $collision = null;
    /** @var ICriterionOrder|ICriterionPayment|ICriterionShipment|ICollisionProfile $criterion */
    protected $criterion = null;

    /**
     * @return static
     */
    private static function getInstance()
    {
        if(self::$instance === null)
        {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * @param $typeId
     * @return Entity\OrderImport|Entity\PaymentCardImport|Entity\PaymentCashImport|Entity\PaymentCashLessImport|Entity\ShipmentImport|ProfileImport
     */
    public static function createImport($typeId)
    {
        $config = static::getImportByType($typeId);

        $import = Entity\EntityImportFactory::create($typeId);

        $import->loadSettings($config->settings);
        $import->loadCollision($config->collision);
        $import->loadCriterion($config->criterion);

        return $import;
    }

    /**
     * Add instance of this manager to collection
     * @param $typeId
     * @param ISettings $settings
     * @param ICollision $collision
     * @param ICriterion $criterion
     * @return mixed
     * @throws ArgumentOutOfRangeException
     */
    public static function registerInstance($typeId, ISettings $settings, ICollision $collision, ICriterion $criterion)
    {
        if(!is_int($typeId))
        {
            $typeId = (int)$typeId;
        }

        if(!EntityType::IsDefined($typeId))
        {
            throw new ArgumentOutOfRangeException('Is not defined', EntityType::FIRST, EntityType::LAST);
        }

        if(self::$instance[$typeId] === null)
        {
            $manager = new static();
            $manager->settings = $settings;
            $manager->collision = $collision;
            $manager->criterion = $criterion;

            self::$instance[$typeId] = $manager;
        }
        return self::$instance[$typeId];
    }

    /**
     * Get import by Type ID.
     * @param $typeId
     * @return null|static
     * @throws ArgumentOutOfRangeException
     */
    private static function getImportByType($typeId)
    {
        if(!is_int($typeId))
        {
            $typeId = (int)$typeId;
        }

        if(!EntityType::IsDefined($typeId))
        {
            throw new ArgumentOutOfRangeException('Is not defined', EntityType::FIRST, EntityType::LAST);
        }

        $import = static::getInstance();
        return isset($import[$typeId]) ? $import[$typeId] : null;
    }

	/**
	 * @param $typeId
	 * @return ISettings
	 * @throws ArgumentOutOfRangeException
	 */
	public static function getSettingsByType($typeId)
	{
		if(!is_int($typeId))
		{
			$typeId = (int)$typeId;
		}

		if(!EntityType::IsDefined($typeId))
		{
			throw new ArgumentOutOfRangeException('Is not defined', EntityType::FIRST, EntityType::LAST);
		}

		$config = static::getImportByType($typeId);

		return $config->settings;
	}
}