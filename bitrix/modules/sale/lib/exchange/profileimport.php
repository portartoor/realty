<?php
namespace Bitrix\Sale\Exchange;

use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Sale\Exchange;

class ProfileImport extends ImportBase
{
    protected $entity = array();

    /**
     * ProfileImport constructor.
     */
    public function __construct()
    {
        $this->fields = new Sale\Internals\Fields();
    }

    /**
     * @return int
     */
    public function getOwnerTypeId()
    {
        return EntityType::PROFILE;
    }

    /**
     * @return array
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param $profile
     */
    public function setEntity($profile)
    {
        $this->entity = $profile;
    }

	/**
	 * @param array $fields
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 */
    public function load(array $fields)
    {
        $result = new Sale\Result();

    	$r = $this->checkFields($fields);
        if(!$r->isSuccess())
        {
            throw new Main\ArgumentException('XML_ID is not defined');
        }

        $profileFields = $this->initFieldsProfile($fields, $arErrors);

        if(count($arErrors)>0)
		{
			foreach($arErrors as $error)
			{
				$result->addError(new  Main\Error(str_replace('<br>','', $error['TEXT'])));
			}
		}

		if(count($profileFields)>0)
		{
			$profile = new static();

			$profile->setFields($profileFields);

			$this->setEntity($profile);
		}

        return $result;
    }

    /**
     * @return null|string
     */
    public function getPersonalTypeId()
    {
        return $this->getField('PERSON_TYPE_ID');
    }

    /**
     * @return null|string
     */
    public function getUserId()
    {
        return $this->getField('USER_ID');
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        /** @var static $profile */
        $profile = $this->getEntity();
        return $profile->getField('USER_PROFILE_ID');
    }

    /**
     * @return int|null
     */
    public function profileGetId()
    {
        return $this->getField('USER_PROFILE_ID');
    }

    /**
     * @return bool
     */
	public function isImportable()
    {
        return $this->settings->isImportableFor($this->getOwnerTypeId());
    }

    /**
     * @param array $params
     * @return Sale\Result
     */
    public function add(array $params)
    {
        $result = new Sale\Result();

        /** @var static $profile */
        $profile = $this->getEntity();

        $fields = $params["TRAITS"];
        $property = $params["ORDER_PROP"];

        $propertyOrders = static::getPropertyOrdersByPersonalTypeId($profile->getPersonalTypeId());

        if(is_array($propertyOrders))
        {
            foreach($propertyOrders as $filedsProperty)
            {
                $propertyId = $filedsProperty["ID"];
                if(array_key_exists($propertyId, $property))
                {
                    $propertyByConfigValue = $property[$propertyId];
                    if($profile->profileGetId()<=0)
                    {
                        if(!empty($propertyByConfigValue))
                        {
                            $profileId = \CSaleOrderUserProps::Add(array(
                                "NAME" => $fields["AGENT_NAME"],
                                "USER_ID" => $profile->getUserId(),
                                "PERSON_TYPE_ID" => $profile->getPersonalTypeId(),
                                "XML_ID" => $fields["XML_ID"],
                                "VERSION_1C" => $fields["VERSION_1C"]
                            ));

                            $profile->setField("USER_PROFILE_ID", $profileId);
                        }
                    }

                    \CSaleOrderUserPropsValue::Add(array(
                        "USER_PROPS_ID" => $profile->profileGetId(),
                        "ORDER_PROPS_ID" => $propertyId,
                        "NAME" => $filedsProperty["NAME"],
                        "VALUE" => $propertyByConfigValue
                    ));
                }
            }
        }

        return $result;
    }

    /**
     * @param array $params
     * @return Sale\Result
     */
    public function update(array $params)
    {
        $result = new Sale\Result();

        /** @var static $profile */
        $profile = $this->getEntity();

        $criterion = $this->getCurrentCriterion($profile);

        $fields = $params["TRAITS"];
        $property = $params["ORDER_PROP"];

        if($criterion->equals($fields))
        {
            $fieldsProfileUpdate = array(
                "VERSION_1C" => $fields["VERSION_1C"],
                "NAME" => $fields["AGENT_NAME"],
                "USER_ID" => $profile->getUserId()
            );

            \CSaleOrderUserProps::Update($profile->profileGetId(), $fieldsProfileUpdate);


            $profileFields = static::getFieldsUserProfile($profile->profileGetId());
            foreach($profileFields as $fieldsProfile)
            {
                $fieldsProfileByProperty[$fieldsProfile["ORDER_PROPS_ID"]] = array("ID" => $fieldsProfile["ID"], "VALUE" => $fieldsProfile["VALUE"]);
            }

            $propertyOrders = static::getPropertyOrdersByPersonalTypeId($profile->getPersonalTypeId());
            foreach($propertyOrders as $filedsProperty)
            {
                $propertyId = $filedsProperty["ID"];
                if(array_key_exists($propertyId, $property))
                {
                    $propertyByConfigValue = $property[$propertyId];
                    if(!empty($propertyByConfigValue))
                    {
                        $fields = array(
                            "USER_PROPS_ID" => $profile->getField("USER_PROFILE_ID"),
                            "ORDER_PROPS_ID" => $propertyId,
                            "NAME" => $filedsProperty["NAME"],
                            "VALUE" => $propertyByConfigValue
                        );

                        if(empty($fieldsProfileByProperty[$propertyId]))
                        {
                            \CSaleOrderUserPropsValue::Add($fields);
                        }
                        elseif($fieldsProfileByProperty[$propertyId]["VALUE"] != $propertyByConfigValue)
                        {
                            \CSaleOrderUserPropsValue::Update($fieldsProfileByProperty[$propertyId]["ID"], $fields);
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Deletes row in entity table by primary key
     * @param array|null $params
     * @return Sale\Result
     */
    public function delete(array $params = null)
    {
        return new Sale\Result();
    }

    /**
     * @param array $fields
     * @return Sale\Result
     */
    protected function checkFields(array $fields)
    {
        $result = new Sale\Result();

        if(empty($fields['XML_ID']))
        {
            $result->addError(new Main\Error('XML_ID is not defined',''));
        }

        return $result;
    }

    /**
     * @param $personalTypeId
     * @param $profile
     * @param $property
     * @return array
     */
    public function getPropertyOrdersByConfig($personalTypeId, $profile, $property)
    {
        $result = array();

        if($fieldsConfig = $this->getFieldsConfig($personalTypeId, $profile))
        {
            if(is_array($fieldsConfig))
            {
                foreach($fieldsConfig as $k => $v)
                {
					if(!isset($v['VALUE']))
						continue;

                	if(!empty($property[$k]))
                    {
                        $result[$v["VALUE"]] = $property[$k];
                    }

                    if(empty($result[$v["VALUE"]]) && !empty($profile[$v["VALUE"]]))
                    {
                        $result[$v["VALUE"]] = $profile[$v["VALUE"]];
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param $personTypeId
     * @return mixed
     */
    public static function getPropertyOrdersByPersonalTypeId($personTypeId)
    {
        static $result = null;

        if($result[$personTypeId] === null)
        {
            $dbOrderProperties = \CSaleOrderProps::GetList(
                array("SORT" => "ASC"),
                array(
                    "PERSON_TYPE_ID" => $personTypeId,
                    "ACTIVE" => "Y",
                    "UTIL" => "N",
                    "USER_PROPS" => "Y",
                ),
                false,
                false,
                array("ID", "TYPE", "NAME", "CODE", "USER_PROPS", "SORT", "MULTIPLE")
            );
            while ($arOrderProperties = $dbOrderProperties->Fetch())
                $result[$personTypeId][] = $arOrderProperties;
        }

        return $result[$personTypeId];
    }

    /**
     * @param $fields
     * @return int|null|string
     */
    public function resolvePersonTypeId($fields)
    {
        foreach($this->getConfig() as $id => $value)
        {
            if((($value["IS_FIZ"] == "Y" && $fields["TYPE"] == "FIZ") ||
                ($value["IS_FIZ"] == "N" && $fields["TYPE"] != "FIZ")))
            {
                return $id;
            }
        }
        return null;
    }

    /**
     * @return null
     */
    public function getConfig()
    {
        static $config = null;

        if($config === null)
        {
            $r = \CSaleExport::GetList(array(), array("PERSON_TYPE_ID" => $this->getListPersonType($this->settings->getSiteId())));
            while($ar = $r->Fetch())
            {
                $config[$ar["PERSON_TYPE_ID"]] = unserialize($ar["VARS"]);
            }
        }
        return $config;
    }

    /**
     * @param $orgFormId
     * @param array $userProps
     * @return bool
     */
    public function getFieldsConfig($orgFormId, $userProps=array())
    {
        if(intval($orgFormId)<=0)
            return false;

        $config = $this->getConfig();

        if(empty($config[$orgFormId]))
            return false;

        $fields = $config[$orgFormId];
        foreach($fields as $k => $v)
        {
            if(empty($v) ||
                ((empty($v["VALUE"]) || $v["TYPE"] != "PROPERTY") &&
                    (empty($userProps) || (is_array($v) && is_string($v["VALUE"]) && empty($userProps[$v["VALUE"]])))
                )
            )
            {
                unset($fields[$k]);
            }

        }
        return $fields;
    }

    /**
     * @param $siteId
     * @return array
     */
    public function getListPersonType($siteId)
    {
        static $personType = null;

        if($personType === null)
        {
            $r = \CSalePersonType::GetList(array(), array("ACTIVE" => "Y", "LIDS" => $siteId));
            while($ar = $r->Fetch())
            {
                $personType[] = $ar["ID"];
            }
        }
        return $personType;
    }

    /**
     * @param array $fields
     * @return array|bool|false|mixed|null
     */
    public static function getUserProfile(array $fields)
    {
        $result = array();

        $r = \CSaleOrderUserProps::GetList(array(),
            array("XML_ID" => $fields["XML_ID"]),
            false,
            false,
            array("ID", "NAME", "USER_ID", "PERSON_TYPE_ID", "XML_ID", "VERSION_1C")
        );
        if ($ar = $r->Fetch())
            $result = $ar;

        return $result;
    }

    /**
     * @param $profileId
     * @return array|bool
     */
    public static function getFieldsUserProfile($profileId)
    {
        $result = array();

        if(intval($profileId) <= 0)
            return false;

        $r = \CSaleOrderUserPropsValue::GetList(array(), array("USER_PROPS_ID" => $profileId));
        while ($ar = $r->Fetch())
        {
            //$result[$ar["ORDER_PROPS_ID"]] = $ar["VALUE"];
            $result[] = $ar;
        }

        return $result;
    }

    /**
     * @param array $fields
     * @return mixed
     */
    public function initFieldsProfile(array $fields, &$arErrors)
    {
        static $profiles = null;

        if($profiles[$fields['XML_ID']] === null)
        {
			$result = array();
        	$profile = static::getUserProfile(array('XML_ID'=>$fields['XML_ID']));

            if(!empty($profile))
            {
                $result['USER_ID'] = $profile['USER_ID'];
                $result['PERSON_TYPE_ID'] = $profile['PERSON_TYPE_ID'];

                $result['USER_PROFILE_ID'] = $profile['ID'];
                $result['USER_PROFILE_VERSION'] = $profile['VERSION_1C'];

                $profileFields = static::getFieldsUserProfile($profile['ID']);
                if(!empty($profileFields))
                {
                    foreach($profileFields as $profileField)
                    {
                        $result['USER_PROPS'][$profileField["ORDER_PROPS_ID"]] = $profileField["VALUE"];
                    }
                }
            }
            elseif($this->isImportable())
            {
                $result['PERSON_TYPE_ID'] = $this->resolvePersonTypeId($fields);

                $user = static::getUserByCode($fields['XML_ID']);
                if(!empty($user))
                    $result['USER_ID'] = $user['ID'];
                else
                    $result['USER_ID'] = $this->registerUser($fields, $arErrors);

                $result['USER_PROFILE_ID'] = null;
                $result['USER_PROFILE_VERSION'] = null;
                $result['USER_PROPS'] = null;
            }

            if(count($arErrors)>0)
            	return $result;
            else
            	$profiles[$fields['XML_ID']] = $result;
        }

        return $profiles[$fields['XML_ID']];
    }

    /**
     * @param $code
     * @return array
     */
    public static function getUserByCode($code)
    {
        $result = array();
		$code = rtrim($code);

        $userCode = explode("#", $code);
        if(intval($userCode[0]) > 0)
        {
            $r = \CUser::GetByID($userCode[0]);
            if ($arUser = $r->Fetch())
            {
                if(rtrim(htmlspecialcharsback(substr(htmlspecialcharsbx($arUser["ID"] . "#" . $arUser["LOGIN"] . "#" . $arUser["LAST_NAME"] . " " . $arUser["NAME"] . " " . $arUser["SECOND_NAME"]), 0, 80))) == $code)
                    $result = $arUser;
            }
        }
        return $result;
    }

	/**
	 * @param $fields
	 * @param $arErrors
	 * @return bool|int|string
	 */
	public function registerUser($fields, &$arErrors)
    {
        $userFields = array(
            "NAME" => $fields["ITEM_NAME"],
            "EMAIL" => $fields["CONTACT"]["MAIL_NEW"],
        );

        if (strlen($userFields["NAME"]) <= 0)
            $userFields["NAME"] = $fields["CONTACT"]["CONTACT_PERSON"];

        $emServer = $_SERVER["SERVER_NAME"];
        if(strpos($_SERVER["SERVER_NAME"], ".") === false)
            $emServer .= ".bx";

        if (strlen($userFields["EMAIL"]) <= 0)
            $userFields["EMAIL"] = "buyer" . time() . GetRandomCode(2) . "@" . $emServer;

        $id = \CSaleUser::DoAutoRegisterUser($userFields["EMAIL"], $userFields["NAME"], $this->settings->getSiteId(), $arErrors, array("XML_ID"=>$fields["XML_ID"]));

        $obUser = new \CUser;
        if(strlen($fields["CONTACT"]["PHONE"])>0)
            $obUser->Update($id, array('WORK_PHONE'=>$fields["CONTACT"]["PHONE"]), true);

        return $id;
    }

    /**
     * @param ICriterionOrder|ICriterionPayment|ICriterionProfile|ICriterionShipment $criterion
     * @throws Main\ArgumentException
     */
    public function loadCriterion($criterion)
    {
        if(!($criterion instanceof Exchange\ICriterionProfile))
            throw new Main\ArgumentException("Criterion must be instanceof ICriterionProfile");

        $this->loadCriterion = $criterion;
    }

    /**
     * @param Exchange\ICollisionProfile $collision
     * @throws Main\ArgumentException
     */
    public function loadCollision($collision)
    {
        if(!($collision instanceof Exchange\ICollisionProfile))
            throw new Main\ArgumentException("Collision must be instanceof ICollisionProfile");

        $this->loadCollision = $collision;
    }

    /**
     * @return string
     */
    public static function getFieldExternalId()
    {
        return 'XML_ID';
    }

    /**
     * @param array $fields
     */
    public function refreshData(array $fields)
    {
    }
}