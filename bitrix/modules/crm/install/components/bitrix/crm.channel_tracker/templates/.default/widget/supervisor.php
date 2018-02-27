<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
return array(
	array(
		"cells" => array(
			array(
				"data" => array(
					"items" => array(
						array("CHANNEL_TYPE_ID" => "5", "COUNT" => "24"),
						array("CHANNEL_TYPE_ID" => "3", "COUNT" => "56"),
						array("CHANNEL_TYPE_ID" => "2", "COUNT" => "18"),
					),
					"valueField" => "COUNT",
					"titleField" => "CHANNEL",
					"identityField" => "CHANNEL_TYPE_ID"
				)
			),
			array(
				array(
					"data" => array(
						"items" => array(
							array("name" => "deal_success", "value" => "108000")
						)
					)
				),
				array(
					"data" => array(
						"items" => array(
							array("name" => "deal_process", "value" => "341000")
						)
					)
				)
			)
		)
	),
	array(
		"cells" => array(
			array(
				"data" => array(
					"dateFormat" => "YYYY-MM-DD",
					"items" => array(
						array(
							"groupField" => "DATE",
							"graphs" => array(
								array(
									"name" => "deal_success",
									"selectField" => "SUM_TOTAL"
								)
							),
							"values" => array(
								array("DATE" => "2016-11-01", "SUM_TOTAL" => "18000"),
								array("DATE" => "2016-11-02", "SUM_TOTAL" => "20000"),
								array("DATE" => "2016-11-03", "SUM_TOTAL" => "22000"),
								array("DATE" => "2016-11-04", "SUM_TOTAL" => "24000"),
								array("DATE" => "2016-11-05", "SUM_TOTAL" => "24000")
							)
						)
					)
				)
			)
		)
	),
	array(
		"cells" => array(
			array(
				"data" => array(
					"dateFormat" => "YYYY-MM-DD",
					"items" => array(
						array(
							"groupField" => "USER",
							"graphs" => array(
								array(
									"name" => "deal_process",
									"selectField" => "DEAL_PROCESS_SUM_TOTAL"
								),
								array(
									"name" => "deal_success",
									"selectField" => "DEAL_SUCCESS_SUM_TOTAL"
								)
							),
							"values" => array(
								array(
									"USER" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_1"),
									"DEAL_PROCESS_SUM_TOTAL" => 161000,
									"DEAL_SUCCESS_SUM_TOTAL" => 50000,

								),
								array(
									"USER" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_2"),
									"DEAL_PROCESS_SUM_TOTAL" => 98000,
									"DEAL_SUCCESS_SUM_TOTAL" => 34000,

								),
								array(
									"USER" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_3"),
									"DEAL_PROCESS_SUM_TOTAL" => 82000,
									"DEAL_SUCCESS_SUM_TOTAL" => 24000,
								)
							)
						)
					)
				)
			)
		)
	)
);
