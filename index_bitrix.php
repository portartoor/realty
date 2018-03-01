<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("NOT_SHOW_NAV_CHAIN", "Y");
$APPLICATION->SetPageProperty("title", htmlspecialcharsbx(COption::GetOptionString("main", "site_name", "Bitrix24")));
?>
<?
if(in_array(32, CUser::GetUserGroup($USER->GetID())))
{
	LocalRedirect("/workgroups/group/34/");
}
else
{
$APPLICATION->IncludeComponent("bitrix:socialnetwork_user", ".default", Array(
	"ITEM_DETAIL_COUNT"	=>	"32",
	"ITEM_MAIN_COUNT"	=>	"6",
	"DATE_TIME_FORMAT"	=>	"d.m.Y H:i:s",
	"NAME_TEMPLATE" => "",
	"PATH_TO_GROUP" => "/workgroups/group/#group_id#/",
	"PATH_TO_GROUP_SUBSCRIBE" => "/workgroups/group/#group_id#/subscribe/",
	"PATH_TO_GROUP_SEARCH" => "/workgroups/group/search/",
	"PATH_TO_SEARCH_EXTERNAL" => "/company/index.php",
	"PATH_TO_CONPANY_DEPARTMENT" => "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#",
	"PATH_TO_GROUP_TASKS" => "/workgroups/group/#group_id#/tasks/",
	"PATH_TO_GROUP_TASKS_TASK" => "/workgroups/group/#group_id#/tasks/task/#action#/#task_id#/",
	"PATH_TO_GROUP_TASKS_VIEW" => "/workgroups/group/#group_id#/tasks/view/#action#/#view_id#/",
	"PATH_TO_GROUP_POST" => "/workgroups/group/#group_id#/blog/#post_id#/",
	"PATH_TO_GROUP_PHOTO" => "/workgroups/group/#group_id#/photo/",
	"PATH_TO_GROUP_PHOTO_SECTION" => "/workgroups/group/#group_id#/photo/album/#section_id#/",
	"PATH_TO_GROUP_PHOTO_ELEMENT" => "/workgroups/group/#group_id#/photo/#section_id#/#element_id#/",
	"SEF_MODE" => "Y",
	"LOG_AUTH" => "Y",
	"SEF_FOLDER" => "/company/personal/",
	"AJAX_MODE" => "N",
	"AJAX_OPTION_SHADOW" => "Y",
	"AJAX_OPTION_JUMP" => "N",
	"AJAX_OPTION_STYLE" => "Y",
	"AJAX_OPTION_HISTORY" => "Y",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "3600",
	"CACHE_TIME_LONG" => "604800",
	"PATH_TO_SMILE" => "/bitrix/images/socialnetwork/smile/",
	"PATH_TO_BLOG_SMILE" => "/bitrix/images/blog/smile/",
	"PATH_TO_FORUM_SMILE" => "/bitrix/images/forum/smile/",
	"PATH_TO_FORUM_ICON" => "/bitrix/images/forum/icon/",
	"SET_TITLE" => "Y",
	"SET_NAV_CHAIN" => "Y",
	"SHOW_RATING" => "",
	"RATING_TYPE" => "",
	"USER_FIELDS_MAIN" => array(
		0 => "LAST_LOGIN",
		1 => "PERSONAL_PROFESSION",
		2 => "WORK_POSITION",
	),
	"USER_PROPERTY_MAIN" => array(
		0 => "UF_DEPARTMENT",
	),
	"USER_FIELDS_CONTACT" => array(
		0 => "EMAIL",
		1 => "PERSONAL_WWW",
		2 => "PERSONAL_ICQ",
		3 => "PERSONAL_PHONE",
		4 => "PERSONAL_FAX",
		5 => "PERSONAL_MOBILE",
		6 => "WORK_WWW",
		7 => "WORK_PHONE",
		8 => "WORK_FAX",
	),
	"USER_PROPERTY_CONTACT" => array(
		0 => "UF_PHONE_INNER",	
		1 => "UF_SKYPE",	
	),
	"USER_FIELDS_PERSONAL" => array(
		0 => "PERSONAL_BIRTHDAY",
		1 => "PERSONAL_GENDER",
	),
	"USER_PROPERTY_PERSONAL" => array(
	),
	"AJAX_LONG_TIMEOUT" => "60",
	"EDITABLE_FIELDS" => array(
		0 => "LOGIN",
		1 => "NAME",
		2 => "SECOND_NAME",
		3 => "LAST_NAME",
		4 => "EMAIL",
		5 => "PASSWORD",
		6 => "PERSONAL_BIRTHDAY",
		7 => "PERSONAL_WWW",
		8 => "PERSONAL_ICQ",
		9 => "PERSONAL_GENDER",
		10 => "PERSONAL_PHOTO",
		11 => "PERSONAL_PHONE",
		12 => "PERSONAL_FAX",
		13 => "PERSONAL_MOBILE",
		14 => "PERSONAL_COUNTRY",
		15 => "PERSONAL_STATE",
		16 => "PERSONAL_CITY",
		17 => "PERSONAL_ZIP",
		18 => "PERSONAL_STREET",
		19 => "PERSONAL_MAILBOX",
		20 => "WORK_PHONE",
		21 => "FORUM_SHOW_NAME",
		22 => "FORUM_DESCRIPTION",
		23 => "FORUM_INTERESTS",
		24 => "FORUM_SIGNATURE",
		25 => "FORUM_AVATAR",
		26 => "FORUM_HIDE_FROM_ONLINE",
		27 => "FORUM_SUBSC_GET_MY_MESSAGE",
		28 => "BLOG_ALIAS",
		29 => "BLOG_DESCRIPTION",
		30 => "BLOG_INTERESTS",
		31 => "BLOG_AVATAR",
		32 => "UF_PHONE_INNER",
		33 => "UF_SKYPE",
		34 => "TIME_ZONE",
	),
	"SHOW_YEAR" => "M",
	"USER_FIELDS_SEARCH_SIMPLE" => array(
		0 => "PERSONAL_GENDER",
		1 => "PERSONAL_CITY",
	),
	"USER_PROPERTIES_SEARCH_SIMPLE" => array(
	),
	"USER_FIELDS_SEARCH_ADV" => array(
		0 => "PERSONAL_GENDER",
		1 => "PERSONAL_COUNTRY",
		2 => "PERSONAL_CITY",
	),
	"USER_PROPERTIES_SEARCH_ADV" => array(
	),
	"SONET_USER_FIELDS_LIST" => array(
		0 => "PERSONAL_BIRTHDAY",
		1 => "PERSONAL_GENDER",
		2 => "PERSONAL_CITY",
	),
	"SONET_USER_PROPERTY_LIST" => array(
	),
	"SONET_USER_FIELDS_SEARCHABLE" => array(
	),
	"SONET_USER_PROPERTY_SEARCHABLE" => array(
	),
	"BLOG_GROUP_ID" => "1",
	"BLOG_COMMENT_AJAX_POST" => "Y",
	"FORUM_ID" => "3",
	"CALENDAR_IBLOCK_TYPE"	=>	"events",
	"CALENDAR_USER_IBLOCK_ID"	=>	"0",
	"CALENDAR_WEEK_HOLIDAYS"	=>	array(
		0	=>	"5",
		1	=>	"6",
	),
	"CALENDAR_YEAR_HOLIDAYS"	=>	"1.01, 2.01, 7.01, 23.02, 8.03, 1.05, 9.05, 12.06, 4.11, 12.12",
	"CALENDAR_WORK_TIME_START" => "9",
	"CALENDAR_WORK_TIME_END" => "19",
	"CALENDAR_ALLOW_SUPERPOSE" => "Y",
	"CALENDAR_SUPERPOSE_CAL_IDS" => array(
		0 => "#CALENDAR_COMPANY_IBLOCK_ID#",
	),
	"CALENDAR_SUPERPOSE_CUR_USER_CALS" => "Y",
	"CALENDAR_SUPERPOSE_USERS_CALS" => "Y",
	"CALENDAR_SUPERPOSE_GROUPS_CALS" => "Y",
	"CALENDAR_SUPERPOSE_GROUPS_IBLOCK_ID" => "#CALENDAR_GROUPS_IBLOCK_ID#",
	"CALENDAR_ALLOW_RES_MEETING" => "Y",
	"CALENDAR_RES_MEETING_IBLOCK_ID" => "#CALENDAR_RES_IBLOCK_ID#",
	"CALENDAR_PATH_TO_RES_MEETING" => "/services/?page=meeting&meeting_id=#id#",
	"CALENDAR_RES_MEETING_USERGROUPS" => array("1"),
	"CALENDAR_ALLOW_VIDEO_MEETING" => "Y",
	"CALENDAR_VIDEO_MEETING_IBLOCK_ID" => "25",
	"CALENDAR_PATH_TO_VIDEO_MEETING_DETAIL" => "/services/video/detail.php?ID=#ID#",
	"CALENDAR_PATH_TO_VIDEO_MEETING" => "/services/video/",
	"CALENDAR_VIDEO_MEETING_USERGROUPS" => array("1"),
	"TASK_IBLOCK_TYPE" => "services",
	"TASK_IBLOCK_ID" => "24",
	"TASKS_FIELDS_SHOW" => array(
		0 => "ID",
		1 => "NAME",
		2 => "MODIFIED_BY",
		3 => "DATE_CREATE",
		4 => "CREATED_BY",
		5 => "DATE_ACTIVE_FROM",
		6 => "DATE_ACTIVE_TO",
		7 => "IBLOCK_SECTION",
		8 => "DETAIL_TEXT",
		9 => "TASKPRIORITY",
		10 => "TASKSTATUS",
		11 => "TASKCOMPLETE",
		12 => "TASKASSIGNEDTO",
		13 => "TASKALERT",
		14 => "TASKSIZE",
		15 => "TASKSIZEREAL",
		16 => "TASKFINISH",
		17 => "TASKFILES",
		18 => "TASKREPORT",
	),
	"TASK_FORUM_ID" => "8",
	"FILES_USER_IBLOCK_TYPE"	=>	"library",
	"FILES_USER_IBLOCK_ID"	=>	"16",
	"FILES_USE_AUTH"	=>	"Y",
	"NAME_FILE_PROPERTY"	=>	"FILE",
	"FILES_UPLOAD_MAX_FILESIZE"	=>	"1024",
	"FILES_UPLOAD_MAX_FILE"	=>	"4",
	"FILES_USE_COMMENTS" => "Y",
	"FILES_FORUM_ID" => "7",
	"PHOTO_USER_IBLOCK_TYPE"	=>	"photos",
	"PHOTO_USER_IBLOCK_ID"	=>	"21",
	"PHOTO_UPLOAD_MAX_FILESIZE"	=>	"64",
	"PHOTO_UPLOAD_MAX_FILE"	=>	"4",
	"PHOTO_ORIGINAL_SIZE" => "1280",
	"PHOTO_UPLOADER_TYPE" => "flash",
	"PHOTO_USE_RATING"	=>	"Y",
	"PHOTO_DISPLAY_AS_RATING" => "vote_avg",
	"PHOTO_USE_COMMENTS" => "Y",
	"PHOTO_FORUM_ID" => "2",
	"PHOTO_USE_CAPTCHA" => "N",
	"PHOTO_GALLERY_AVATAR_SIZE" => "50",
	"PHOTO_ALBUM_PHOTO_THUMBS_SIZE" => "150",
	"PHOTO_ALBUM_PHOTO_SIZE" => "150",
	"PHOTO_THUMBS_SIZE" => "250",
	"PHOTO_PREVIEW_SIZE" => "700",
	"PHOTO_JPEG_QUALITY1" => "95",
	"PHOTO_JPEG_QUALITY2" => "95",
	"PHOTO_JPEG_QUALITY" => "90",
	"SEF_URL_TEMPLATES"	=>	array(
		"index"	=>	"index.php",
		"user"	=>	"user/#user_id#/",
		"user_friends"	=>	"user/#user_id#/friends/",
		"user_friends_add"	=>	"user/#user_id#/friends/add/",
		"user_friends_delete"	=>	"user/#user_id#/friends/delete/",
		"user_groups"	=>	"user/#user_id#/groups/",
		"user_groups_add"	=>	"user/#user_id#/groups/add/",
		"group_create"	=>	"user/#user_id#/groups/create/",
		"user_profile_edit"	=>	"user/#user_id#/edit/",
		"user_settings_edit"	=>	"user/#user_id#/settings/",
		"user_features"	=>	"user/#user_id#/features/",
		"group_request_group_search"	=>	"group/#user_id#/group_search/",
		"group_request_user"	=>	"group/#group_id#/user/#user_id#/request/",
		"search"	=>	"search.php",
		"message_form"	=>	"messages/form/#user_id#/",
		"message_form_mess"	=>	"messages/form/#user_id#/#message_id#/",
		"user_ban"	=>	"messages/ban/",
		"messages_chat"	=>	"messages/chat/#user_id#/",
		"messages_input"	=>	"messages/input/",
		"messages_input_user"	=>	"messages/input/#user_id#/",
		"messages_output"	=>	"messages/output/",
		"messages_output_user"	=>	"messages/output/#user_id#/",
		"messages_users"	=>	"messages/",
		"messages_users_messages"	=>	"messages/#user_id#/",
		"user_photo"	=>	"user/#user_id#/photo/",
		"user_calendar"	=>	"user/#user_id#/calendar/",
		"user_files"	=>	"user/#user_id#/files/lib/#path#",
		"user_blog"	=>	"user/#user_id#/blog/",
		"user_blog_post_edit"	=>	"user/#user_id#/blog/edit/#post_id#/",
		"user_blog_rss"	=>	"user/#user_id#/blog/rss/#type#/",
		"user_blog_draft"	=>	"user/#user_id#/blog/draft/",
		"user_blog_post"	=>	"user/#user_id#/blog/#post_id#/",
		"user_forum"	=>	"user/#user_id#/forum/",
		"user_forum_topic_edit"	=>	"user/#user_id#/forum/edit/#topic_id#/",
		"user_forum_topic"	=>	"user/#user_id#/forum/#topic_id#/",
		"user_tasks" => "user/#user_id#/tasks/",
		"user_tasks_task" => "user/#user_id#/tasks/task/#action#/#task_id#/",
		"user_tasks_view" => "user/#user_id#/tasks/view/#action#/#view_id#/",
	),
	"LOG_NEW_TEMPLATE" => "Y"
	)
);?>

<?
$APPLICATION->IncludeComponent(
	"bitrix:intranet.bitrix24.banner",
	"",
	array(),
	null,
	array("HIDE_ICONS" => "N")
);

if(CModule::IncludeModule('intranet')):
	$APPLICATION->IncludeComponent("bitrix:intranet.ustat.status", "", array(),	false);
endif;

if(CModule::IncludeModule('calendar')):
	$APPLICATION->IncludeComponent("bitrix:calendar.events.list", "widget", array(
		"CALENDAR_TYPE" => "user",
		"B_CUR_USER_LIST" => "Y",
		"INIT_DATE" => "",
		"FUTURE_MONTH_COUNT" => "1",
		"DETAIL_URL" => "/company/personal/user/#user_id#/calendar/",
		"EVENTS_COUNT" => "10",
		"CACHE_TYPE" => "N",
		"CACHE_TIME" => "3600"
		),
		false
	);
endif;?>


<?
if(CModule::IncludeModule('tasks')):
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.filter.v2",
		"widget",
		array(
			"VIEW_TYPE" => 0,
			"COMMON_FILTER" => array("ONLY_ROOT_TASKS" => "Y"),
			"USER_ID" => $USER->GetID(),
			"ROLE_FILTER_SUFFIX" => "",
			"PATH_TO_TASKS" => "/company/personal/user/".$USER->GetID()."/tasks/",
			"CHECK_TASK_IN" => "R"
		),
		null,
		array("HIDE_ICONS" => "N")
	);
endif;?>

<?if ($GLOBALS["USER"]->IsAuthorized()){
	$APPLICATION->IncludeComponent("bitrix:socialnetwork.blog.blog", "important",
		Array(
			"BLOG_URL" => "",
			"FILTER" => array(">UF_BLOG_POST_IMPRTNT" => 0, "!POST_PARAM_BLOG_POST_IMPRTNT" => array("USER_ID" => $GLOBALS["USER"]->GetId(), "VALUE" => "Y")),
			"FILTER_NAME" => "",
			"YEAR" => "",
			"MONTH" => "",
			"DAY" => "",
			"CATEGORY_ID" => "",
			"GROUP_ID" => array(),
			"USER_ID" => $GLOBALS["USER"]->GetId(),
			"SOCNET_GROUP_ID" => 0,
			"SORT" => array(),
			"SORT_BY1" => "",
			"SORT_ORDER1" => "",
			"SORT_BY2" => "",
			"SORT_ORDER2" => "",
			//************** Page settings **************************************
			"MESSAGE_COUNT" => 0,
			"NAV_TEMPLATE" => "",
			"PAGE_SETTINGS" => array("bDescPageNumbering" => false, "nPageSize" => 10),
			//************** URL ************************************************
			"BLOG_VAR" => "",
			"POST_VAR" => "",
			"USER_VAR" => "",
			"PAGE_VAR" => "",
			"PATH_TO_BLOG" => "/company/personal/user/#user_id#/blog/",
			"PATH_TO_BLOG_CATEGORY" => "",
			"PATH_TO_BLOG_POSTS" => "/company/personal/user/#user_id#/blog/important/",
			"PATH_TO_POST" => "/company/personal/user/#user_id#/blog/#post_id#/",
			"PATH_TO_POST_EDIT" => "/company/personal/user/#user_id#/blog/edit/#post_id#/",
			"PATH_TO_USER" => "/company/personal/user/#user_id#/",
			"PATH_TO_SMILE" => "/bitrix/images/socialnetwork/smile/",
			//************** ADDITIONAL *****************************************
			"DATE_TIME_FORMAT" => "d.m.Y H:i:s",
			"NAME_TEMPLATE" => "",
			"SHOW_LOGIN" => "Y",
			"AVATAR_SIZE" => 42,
			"SET_TITLE" => "N",
			"SHOW_RATING" => "N",
			"RATING_TYPE" => "",
			//************** CACHE **********************************************
			"CACHE_TYPE" => "A",
			"CACHE_TIME" => 3600,
			"CACHE_TAGS" => array("IMPORTANT", "IMPORTANT".$GLOBALS["USER"]->GetId()),
			//************** Template Settings **********************************
			"OPTIONS" => array(array("name" => "BLOG_POST_IMPRTNT", "value" => "Y")),
		),
		null
	);
}?>

<?$APPLICATION->IncludeComponent("bitrix:blog.popular_posts", "widget", array(
	"GROUP_ID" => 1,
	"SORT_BY1" => "RATING_TOTAL_VALUE",
	"MESSAGE_COUNT" => "5",
	"PERIOD_DAYS" => "8",
	"MESSAGE_LENGTH" => "100",
	"DATE_TIME_FORMAT" => "d.m.Y H:i:s",
	"PATH_TO_BLOG" => "/company/personal/user/#user_id#/blog/",
	"PATH_TO_GROUP_BLOG_POST" => "/workgroups/group/#group_id#/blog/#post_id#/",
	"PATH_TO_POST" => "/company/personal/user/#user_id#/blog/#post_id#/",
	"PATH_TO_USER" => "/company/personal/user/#user_id#/",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "3600",
	"SEO_USER" => "Y",
	"USE_SOCNET" => "Y",
	),
    false
);?>

<?$APPLICATION->IncludeComponent(
	"bitrix:intranet.structure.birthday.nearest",
	"widget",
	Array(
		"NUM_USERS" => "4",
		"NAME_TEMPLATE" => "",
		"SHOW_LOGIN" => "Y",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600",
		"DATE_FORMAT" => "j F",
	"DATE_FORMAT_NO_YEAR" => "j F",
		"DETAIL_URL" => "/company/personal/user/#USER_ID#/",
		"DEPARTMENT" => "0",
		"AJAX_OPTION_ADDITIONAL" => ""
	)
);?>

<?}?>

<? if(CModule::IncludeModule('bizproc')):
	$APPLICATION->IncludeComponent(
		'bitrix:bizproc.task.list',
		'widget',
		array(
			'COUNTERS_ONLY' => 'Y',
			'USER_ID' => $USER->GetID(),
			'PATH_TO_BP_TASKS' => '/company/personal/bizproc/',
			'PATH_TO_MY_PROCESSES' => '/company/personal/processes/',
		),
		null,
		array('HIDE_ICONS' => 'N')
	);
endif;?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>