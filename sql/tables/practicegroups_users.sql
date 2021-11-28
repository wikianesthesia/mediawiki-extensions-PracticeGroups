CREATE TABLE /*_*/practicegroups_users (
  `practicegroupsuser_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `practicegroup_id` INT(10) UNSIGNED NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `admin` TINYINT(1) NOT NULL DEFAULT 0,
  `active_since` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `invited_since` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `requested_since` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `request_reason` VARCHAR(255) NULL,
  `awaiting_email_verification_since` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `affiliated_email` VARCHAR(255) NULL,
  `email_verification_code` VARCHAR(16) NULL,
  `approved_by_user_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `display_order` INT(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`practicegroupsuser_id`)
) /*$wgDBTableOptions*/;