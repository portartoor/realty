CREATE TABLE IF NOT EXISTS b_voximplant_phone
(
	ID int(11) NOT NULL auto_increment,
	USER_ID int(11) NOT NULL,
	PHONE_NUMBER varchar(20) NOT NULL,
	PHONE_MNEMONIC varchar(20) NULL,
	PRIMARY KEY (ID),
	KEY IX_VI_PH_1 (USER_ID, PHONE_NUMBER),
	KEY IX_VI_PH_2 (USER_ID, PHONE_MNEMONIC),
	KEY IX_VI_PH_3 (PHONE_NUMBER)
);

CREATE TABLE IF NOT EXISTS b_voximplant_statistic
(
	ID int(11) NOT NULL auto_increment,
	ACCOUNT_ID int(11) NULL,
	APPLICATION_ID int(11) NULL,
	APPLICATION_NAME varchar(80) NULL,
	PORTAL_USER_ID int(11) NULL,
	PORTAL_NUMBER varchar(20) NULL,
	PHONE_NUMBER varchar(20) NOT NULL,
	INCOMING varchar(50) not null default '1',
	CALL_ID varchar(255) NOT NULL,
	CALL_CATEGORY varchar(20) NULL default 'external',
	CALL_LOG varchar(255) NULL,
	CALL_DIRECTION varchar(255) NULL,
	CALL_DURATION int(11) NOT NULL default 0,
	CALL_START_DATE datetime not null,
	CALL_STATUS int(11) NULL default 0,
	CALL_FAILED_CODE varchar(255) NULL,
	CALL_FAILED_REASON varchar(255) NULL,
	CALL_RECORD_ID INT(11) NULL,
	CALL_WEBDAV_ID INT(11) NULL,
	CALL_VOTE smallint(1) DEFAULT 0,
	COST double(11, 4) NULL default 0,
	COST_CURRENCY varchar(50) NULL,
	CRM_ENTITY_TYPE varchar(50) NULL,
	CRM_ENTITY_ID int(11) NULL,
	CRM_ACTIVITY_ID int(11) NULL,
	REST_APP_ID int(11) NULL,
	REST_APP_NAME varchar(255) NULL,
	PRIMARY KEY (ID),
	KEY IX_VI_ST_1 (PORTAL_USER_ID),
	KEY IX_VI_ST_2 (CALL_START_DATE),
	KEY IX_VI_ST_3 (CALL_FAILED_CODE),
	KEY IX_VI_ST_4 (CALL_CATEGORY),
	KEY IX_VI_ST_5 (CALL_VOTE)
);

CREATE TABLE IF NOT EXISTS b_voximplant_call
(
	ID int(11) NOT NULL auto_increment,
	CONFIG_ID int(11) NULL,
	USER_ID int(11) NULL,
	TRANSFER_TYPE varchar(50) NULL,
	TRANSFER_USER_ID int(11) NULL,
	TRANSFER_PHONE varchar(255) NULL,
	PORTAL_USER_ID int(11) NULL,
	CALL_ID varchar(255) NOT NULL,
	INCOMING varchar(50) null,
	CALLER_ID varchar(255) NULL,
	STATUS varchar(50) NULL,
	CRM char(1) not null default 'Y',
	CRM_LEAD int(11) NULL,
	CRM_ENTITY_TYPE varchar(50) NULL,
	CRM_ENTITY_ID int(11) NULL,
	CRM_ACTIVITY_ID int(11) NULL,
	CRM_CALL_LIST int(11) NULL,
	ACCESS_URL varchar(255) NULL,
	DATE_CREATE datetime,
	REST_APP_ID int(11) NULL,
	QUEUE_ID int(11) NULL,
	QUEUE_HISTORY text null,

	PRIMARY KEY (ID),
	KEY IX_VI_I_1 (CALL_ID),
	KEY IX_VI_I_2 (DATE_CREATE)
);

CREATE TABLE IF NOT EXISTS b_voximplant_sip
(
	ID int(11) NOT NULL auto_increment,
	APP_ID varchar(128) NULL,
	CONFIG_ID int(11) NOT NULL,
	TYPE varchar(255) NULL DEFAULT 'office',
	REG_ID int(11) NULL DEFAULT 0,
	SERVER varchar(255) NULL,
	LOGIN varchar(255) NULL,
	PASSWORD varchar(255) NULL,
	INCOMING_SERVER varchar(255) NULL,
	INCOMING_LOGIN varchar(255) NULL,
	INCOMING_PASSWORD varchar(255) NULL,
	AUTH_USER varchar(255) NULL,
	OUTBOUND_PROXY varchar(255) NULL,
	PRIMARY KEY (ID),
	KEY IX_VI_SIP_1 (CONFIG_ID),
	KEY IX_VI_SIP_2 (APP_ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_config
(
	ID int(11) NOT NULL auto_increment,
	PORTAL_MODE varchar(50),
	SEARCH_ID varchar(255) NULL,
	PHONE_NAME varchar(255) NULL,
	PHONE_COUNTRY_CODE varchar(50) NULL,
	PHONE_VERIFIED char(1) null default 'Y',
	CRM char(1) not null default 'Y',
	CRM_RULE varchar(50),
	CRM_CREATE varchar(50) default 'none',
	CRM_SOURCE varchar(50) default 'CALL',
	CRM_FORWARD char(1) not null default 'Y',
	CRM_TRANSFER_CHANGE char(1) null default 'N',
	IVR char(1) not null default 'N',
	QUEUE_ID int(11) null,
	IVR_ID int(11) null,
	DIRECT_CODE char(1) not null default 'Y',
	DIRECT_CODE_RULE varchar(50),
	RECORDING char(1) not null default 'Y',
	RECORDING_NOTICE char(1) null default 'N',
	RECORDING_TIME smallint(1) DEFAULT 0,
	VOTE char(1) null default 'N',
	FORWARD_LINE varchar(255) default 'default',
	TIMEMAN char(1) not null default 'N',
	VOICEMAIL char(1) not null default 'Y',
	MELODY_LANG char(2) not null default 'EN',
	MELODY_WELCOME int(11) null,
	MELODY_WELCOME_ENABLE char(1) not null default 'Y',
	MELODY_VOICEMAIL int(11) null,
	MELODY_WAIT int(11) null,
	MELODY_HOLD int(11) null,
	MELODY_RECORDING int(11) null,
	MELODY_VOTE int(11) null,
	MELODY_VOTE_END int(11) null,
	TO_DELETE char(1) null default 'N',
	DATE_DELETE datetime null,
	WORKTIME_ENABLE char(1) null default 'N',
	WORKTIME_FROM varchar(5) null,
	WORKTIME_TO varchar(5) null,
	WORKTIME_TIMEZONE varchar(50) null,
	WORKTIME_HOLIDAYS varchar(2000) null,
	WORKTIME_DAYOFF varchar(20) null,
	WORKTIME_DAYOFF_RULE varchar(50) default 'voicemail',
	WORKTIME_DAYOFF_NUMBER varchar(20) null,
	WORKTIME_DAYOFF_MELODY int(11) null,
	WAIT_CRM int(11) not null default 30,
	WAIT_DIRECT int(11) not null default 30,
	USE_SIP_TO char(1) null default 'N',
	PRIMARY KEY (ID),
	KEY IX_VI_PC_1 (SEARCH_ID),
	KEY IX_VI_PC_2 (PORTAL_MODE),
	KEY IX_VI_PC_3 (TO_DELETE, DATE_DELETE)
);

CREATE TABLE IF NOT EXISTS b_voximplant_queue
(
	ID int(11) NOT NULL auto_increment,
	NAME varchar(255) NULL,
	TYPE varchar(50) DEFAULT 'evenly',
	WAIT_TIME smallint(3) DEFAULT 0,
	NO_ANSWER_RULE varchar(50) DEFAULT 'voicemail',
	NEXT_QUEUE_ID int(11) NULL,
	FORWARD_NUMBER varchar(20) NULL,
	CONFIG_ID int(11) NOT NULL,
	PRIMARY KEY(ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_queue_user
(
	ID int(11) NOT NULL auto_increment,
	QUEUE_ID int(11) NOT NULL,
	USER_ID int(11) NOT NULL,
	STATUS varchar(50) NULL,
	LAST_ACTIVITY_DATE datetime,
	PRIMARY KEY (ID),
	KEY IX_VI_PQ_1 (USER_ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_blacklist
(
	ID int(11) NOT NULL auto_increment,
	PHONE_NUMBER varchar(20) NULL,
	PRIMARY KEY (ID),
	KEY IX_VI_BL_1 (PHONE_NUMBER)
);

CREATE TABLE IF NOT EXISTS b_voximplant_role
(
	ID int(11) NOT NULL auto_increment,
	NAME varchar(255) NOT NULL,
	PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_role_permission
(
	ID int(11) NOT NULL auto_increment,
	ROLE_ID int(11) NOT NULL,
	ENTITY varchar(50) NOT NULL,
	ACTION varchar(50) NOT NULL,
	PERMISSION char(1) NULL,

	PRIMARY KEY (ID),
	KEY IX_VOX_PERM_ROLE_ID (ROLE_ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_role_access
(
	ID int(11) NOT NULL auto_increment,
	ROLE_ID int(11) NOT NULL,
	ACCESS_CODE varchar(100) NOT NULL,

	PRIMARY KEY(ID),
	KEY IX_VOX_ACCESS_ROLE_ID (ROLE_ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_ivr
(
	ID int(11) NOT NULL auto_increment,
	NAME varchar(255) NOT NULL,
	FIRST_ITEM_ID int(11) NULL,

	PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_ivr_item
(
	ID int(11) NOT NULL auto_increment,
	CODE varchar(50) NULL,
	IVR_ID int(11) NULL,
	NAME varchar(255) NULL,
	TYPE varchar(10) NOT NULL default 'message',
	URL varchar(2000),
	MESSAGE text,
	FILE_ID int(11) NULL,
	TIMEOUT int(11) NOT NULL default 15,
   	TIMEOUT_ACTION varchar(255) NOT NULL default 'exit',
	TTS_VOICE varchar(50),
	TTS_SPEED varchar(20),
	TTS_VOLUME varchar(20),

	PRIMARY KEY (ID),
	UNIQUE INDEX ux_voximplant_ivr_item (IVR_ID, CODE)
);

CREATE TABLE IF NOT EXISTS b_voximplant_ivr_action
(
	ID int(11) NOT NULL auto_increment,
	ITEM_ID int(11) NOT NULL,
	DIGIT char(1) NULL,
	ACTION varchar(255) NOT NULL,
	PARAMETERS text,
	LEAD_FIELDS text,

	PRIMARY KEY (ID)
);