<?php
namespace Bitrix\Crm\Requisite;
use Bitrix\Main;
use Bitrix\Main\Entity;

class LinkTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_requisite_link';
	}
	public static function getMap()
	{
		return array(
			'ENTITY_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'REQUISITE_ID' => array('data_type' => 'integer'),
			'BANK_DETAIL_ID' => array('data_type' => 'integer'),
			'MC_REQUISITE_ID' => array('data_type' => 'integer'),
			'MC_BANK_DETAIL_ID' => array('data_type' => 'integer')
		);
	}
	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();

		$entityTypeId = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : 0;
		$entityId = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		$requisiteId = isset($data['REQUISITE_ID']) ? (int)$data['REQUISITE_ID'] : 0;
		$bankDetailId = isset($data['BANK_DETAIL_ID']) ? (int)$data['BANK_DETAIL_ID'] : 0;
		$mcRequisiteId = isset($data['MC_REQUISITE_ID']) ? (int)$data['MC_REQUISITE_ID'] : 0;
		$mcBankDetailId = isset($data['MC_BANK_DETAIL_ID']) ? (int)$data['MC_BANK_DETAIL_ID'] : 0;

		if($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$connection->queryExecute( /** @lang MySQL */
				"INSERT INTO b_crm_requisite_link (ENTITY_TYPE_ID, ENTITY_ID, REQUISITE_ID, BANK_DETAIL_ID, ".
				"MC_REQUISITE_ID, MC_BANK_DETAIL_ID)".PHP_EOL.
				"VALUES ({$entityTypeId}, {$entityId}, {$requisiteId}, {$bankDetailId}, {$mcRequisiteId}, ".
				"{$mcBankDetailId})".PHP_EOL.
				"ON DUPLICATE KEY UPDATE ".
				"REQUISITE_ID = {$requisiteId}, BANK_DETAIL_ID = {$bankDetailId}, ".
				"MC_REQUISITE_ID = {$mcRequisiteId}, MC_BANK_DETAIL_ID = {$mcBankDetailId}".PHP_EOL
			);
		}
		elseif($connection instanceof Main\DB\MssqlConnection)
		{
			$dbResult = $connection->query( /** @lang TSQL */
				"SELECT 'X'".PHP_EOL.
				"FROM B_CRM_REQUISITE_LINK".PHP_EOL.
				"WHERE ENTITY_TYPE_ID = {$entityTypeId} AND ENTITY_ID = {$entityId}".PHP_EOL
			);

			if(is_array($dbResult->fetch()))
			{
				$connection->queryExecute( /** @lang TSQL */
					"UPDATE B_CRM_REQUISITE_LINK".PHP_EOL.
					"  SET REQUISITE_ID = {$requisiteId}, BANK_DETAIL_ID = {$bankDetailId}, ".
					"MC_REQUISITE_ID = {$mcRequisiteId}, MC_BANK_DETAIL_ID = {$mcBankDetailId}".PHP_EOL.
					"WHERE ENTITY_TYPE_ID = {$entityTypeId} AND ENTITY_ID = {$entityId}".PHP_EOL
				);
			}
			else
			{
				$connection->queryExecute( /** @lang TSQL */
					"INSERT INTO B_CRM_REQUISITE_LINK (ENTITY_TYPE_ID, ENTITY_ID, REQUISITE_ID, BANK_DETAIL_ID, ".
					"MC_REQUISITE_ID, MC_BANK_DETAIL_ID)".PHP_EOL.
					"VALUES ({$entityTypeId}, {$entityId}, {$requisiteId}, {$bankDetailId}, {$mcRequisiteId}, ".
					"{$mcBankDetailId})".PHP_EOL
				);
			}
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$connection->queryExecute( /** @lang Oracle */
				"MERGE INTO B_CRM_REQUISITE_LINK".PHP_EOL.
				"USING (SELECT {$entityTypeId} ENTITY_TYPE_ID, {$entityId} ENTITY_ID, {$requisiteId} REQUISITE_ID, ".
				"{$bankDetailId} BANK_DETAIL_ID, {$mcRequisiteId} MC_REQUISITE_ID, ".
				"{$mcBankDetailId} MC_BANK_DETAIL_ID FROM dual) source".PHP_EOL.
				"ON (".PHP_EOL.
				"	source.ENTITY_TYPE_ID = B_CRM_REQUISITE_LINK.ENTITY_TYPE_ID".PHP_EOL.
				"	AND source.ENTITY_ID = B_CRM_REQUISITE_LINK.ENTITY_ID".PHP_EOL.
				")".PHP_EOL.
				"WHEN MATCHED THEN".PHP_EOL.
				"  UPDATE SET B_CRM_REQUISITE_LINK.REQUISITE_ID = {$requisiteId}, ".
				"B_CRM_REQUISITE_LINK.BANK_DETAIL_ID = {$bankDetailId}, ".
				"B_CRM_REQUISITE_LINK.MC_REQUISITE_ID = {$mcRequisiteId}, ".
				"B_CRM_REQUISITE_LINK.MC_BANK_DETAIL_ID = {$mcBankDetailId}".PHP_EOL.
				"WHEN NOT MATCHED THEN".PHP_EOL.
				"  INSERT (ENTITY_TYPE_ID, ENTITY_ID, REQUISITE_ID, BANK_DETAIL_ID, MC_REQUISITE_ID, ".
				"MC_BANK_DETAIL_ID)".PHP_EOL.
				"  VALUES ({$entityTypeId}, {$entityId}, {$requisiteId}, {$bankDetailId}, {$mcRequisiteId}, ".
				"{$mcBankDetailId})".PHP_EOL
			);
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}
	}
}