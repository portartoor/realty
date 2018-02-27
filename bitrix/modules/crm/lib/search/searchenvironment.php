<?php
namespace Bitrix\Crm\Search;

class SearchEnvironment
{
	public static function prepareToken($str)
	{
		return str_rot13($str);
	}

	public static function prepareEntityFilter($entityTypeID, array $params)
	{
		$builder = SearchContentBuilderFactory::create($entityTypeID);
		return $builder->prepareEntityFilter($params);
	}

	public static function isFullTextSearchEnabled($entityTypeID)
	{
		$builder = SearchContentBuilderFactory::create($entityTypeID);
		return $builder->isFullTextSearchEnabled();
	}
}