<?php

namespace Bitrix\Faceid;

use Bitrix\Main\Entity;


/**
 * Class AgreementTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> NAME string(100) mandatory
 * <li> EMAIL string(255) mandatory
 * <li> DATE datetime mandatory
 * <li> IP_ADDRESS string(39) mandatory
 * </ul>
 *
 * @package Bitrix\Faceid
 **/

class AgreementTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_faceid_agreement';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new Entity\IntegerField('USER_ID', array(
				'required' => true
			)),
			new Entity\StringField('NAME', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateName')
			)),
			new Entity\StringField('EMAIL', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateEmail')
			)),
			new Entity\DatetimeField('DATE', array(
				'required' => true
			)),
			new Entity\StringField('IP_ADDRESS', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateIpAddress')
			)),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 100),
		);
	}
	/**
	 * Returns validators for EMAIL field.
	 *
	 * @return array
	 */
	public static function validateEmail()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for IP_ADDRESS field.
	 *
	 * @return array
	 */
	public static function validateIpAddress()
	{
		return array(
			new Entity\Validator\Length(null, 39),
		);
	}

	/**
	 * Checks if User have access to the faceid
	 *
	 * @param $userId
	 *
	 * @return bool
	 */
	public static function checkUser($userId)
	{
		$hasAgreement = \Bitrix\Faceid\AgreementTable::getList(array(
			'select' => array(new \Bitrix\Main\Entity\ExpressionField('X', '1')),
			'filter' => array(
				'=USER_ID' => $userId
			)
		))->fetch();

		return !empty($hasAgreement);
	}
}