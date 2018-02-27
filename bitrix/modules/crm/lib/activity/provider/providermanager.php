<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ProviderManager
{
	private static $providers = null;
	/**
	 * @return Base[] - List of providers.
	 */
	public static function getProviders()
	{
		if(self::$providers === null)
		{
			self::$providers = array(
				Meeting::getId() => Meeting::className(),
				Livefeed::getId() => Livefeed::className(),
				ExternalChannel::getId() => ExternalChannel::className(),
				WebForm::getId() => WebForm::className(),
				Call::getId() => Call::className(),
				OpenLine::getId() => OpenLine::className(),
				Email::getId() => Email::className(),
				Visit::getId() => Visit::className(),
				Request::getId() => Request::className(),
				CallList::getId() => CallList::className()
			);

			foreach(GetModuleEvents('crm', 'OnGetActivityProviders', true) as $event)
			{
				$result = (array)ExecuteModuleEventEx($event);
				foreach ($result as $provider)
				{
					/** @var \Bitrix\Crm\Activity\Provider\Base  $provider */
					$provider = (string)$provider;
					if ($provider
						&& class_exists($provider)
						&& (is_subclass_of($provider, Base::className()) || in_array(Base::className(), class_implements($provider)))
					)
					{
						self::$providers[$provider::getId()] = $provider;
					}
				}
			}
		}
		return self::$providers;
	}

	/**
	 * @return int
	 */
	public static function prepareToolbarButtons(array &$buttons, array $params = null)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$ownerTypeID = isset($params['OWNER_TYPE_ID']) ? (int)$params['OWNER_TYPE_ID'] : \CCrmOwnerType::Undefined;
		$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;
		$count = 0;
		$providerParams = array('OWNER_TYPE_ID' => $ownerTypeID, 'OWNER_ID' => $ownerID);
		foreach(self::getProviders() as $provider)
		{
			foreach($provider::getPlannerActions($providerParams) as $action)
			{
				$name = isset($action['NAME']) ? $action['NAME'] : '';
				if($name === '')
				{
					continue;
				}

				$action = array_merge($action, array('OWNER_TYPE_ID' => $ownerTypeID, 'OWNER_ID' => $ownerID));
				$actionParams = htmlspecialcharsbx(\CUtil::PhpToJSObject($action));
				$buttons[] = array(
					'TEXT' => $name,
					'TITLE' => $name,
					'ONCLICK' => "(new BX.Crm.Activity.Planner()).showEdit({$actionParams})",
					'ICON' => 'btn-new'
				);
				$count++;
			}

			$count += $provider::prepareToolbarButtons($buttons, $params);
		}

		return $count;
	}
}