CREATE TABLE /*_*/practicegroups_page_settings (
  `practicegroups_page_setting_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `practicegroup_id` INT(10) UNSIGNED NOT NULL,
  `page_id` INT(10) UNSIGNED NOT NULL,
  `timestamp` INT(10) UNSIGNED NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `privacy` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`practicegroups_page_setting_id`)
) /*$wgDBTableOptions*/;