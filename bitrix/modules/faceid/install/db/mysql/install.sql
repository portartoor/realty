CREATE TABLE IF NOT EXISTS `b_faceid_tracking_visitors` (
  `ID` int(10) unsigned NOT NULL,
  `FILE_ID` int(10) unsigned NOT NULL,
  `FACE_ID` int(10) unsigned NOT NULL,
  `CRM_ID` int(10) unsigned NOT NULL,
  `VK_ID` varchar(50) NOT NULL,
  `FIRST_VISIT` datetime NOT NULL,
  `PRELAST_VISIT` datetime NOT NULL,
  `LAST_VISIT` datetime NOT NULL,
  `LAST_VISIT_ID` int(10) unsigned NOT NULL,
  `VISITS_COUNT` int(10) unsigned NOT NULL
);

ALTER TABLE `b_faceid_tracking_visitors`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `b_faceid_tracking_visitors` ADD UNIQUE(`FACE_ID`);

ALTER TABLE `b_faceid_tracking_visitors`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT;


CREATE TABLE IF NOT EXISTS `b_faceid_tracking_visits` (
  `ID` int(10) unsigned NOT NULL,
  `VISITOR_ID` int(10) unsigned NOT NULL,
  `DATE` datetime NOT NULL
);

ALTER TABLE `b_faceid_tracking_visits`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `VISITOR_ID` (`VISITOR_ID`);

ALTER TABLE `b_faceid_tracking_visits`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT;


CREATE TABLE IF NOT EXISTS `b_faceid_agreement` (
  `ID` int(10) unsigned NOT NULL,
  `USER_ID` int(10) unsigned NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `EMAIL` varchar(255) NOT NULL,
  `DATE` datetime NOT NULL,
  `IP_ADDRESS` varchar(39) NOT NULL COMMENT 'ipv4 or ipv6'
);

ALTER TABLE `b_faceid_agreement`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `USER_ID` (`USER_ID`);

ALTER TABLE `b_faceid_agreement`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `b_faceid_face` (
  `ID` int(10) unsigned NOT NULL,
  `FILE_ID` int(10) unsigned NOT NULL,
  `CLOUD_FACE_ID` int(10) unsigned NOT NULL
);

ALTER TABLE `b_faceid_face`
  ADD PRIMARY KEY (`ID`);
ALTER TABLE `b_faceid_face`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT;
