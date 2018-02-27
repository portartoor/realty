<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
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
					"items" => array(
						array(
							"name" => "deal_success",
							"nomineeId" => "1",
							"positions" => array(
								array("id" => "2", "value" => "1", "legend" => "34000"),
								array("id" => "1", "value" => "2", "legend" => "50000"),
								array("id" => "3", "value" => "3", "legend" => "24000")
							)
						),
					)
				)
			)
		)
	)
);
