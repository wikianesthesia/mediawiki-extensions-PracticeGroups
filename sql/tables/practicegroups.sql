CREATE TABLE /*_*/practicegroups (
  `practicegroup_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dbkey` VARCHAR(25) NOT NULL,
  `name_full` VARCHAR(100) NOT NULL,
  `name_short` VARCHAR(25) NOT NULL,
  `view_by_public` TINYINT(1) NOT NULL DEFAULT 0,
  `join_by_public` TINYINT(1) NOT NULL DEFAULT 0,
  `any_member_add_user` TINYINT(1) NOT NULL DEFAULT 0,
  `join_by_request` TINYINT(1) NOT NULL DEFAULT 0,
  `join_by_affiliated_email` TINYINT(1) NOT NULL DEFAULT 0,
  `affiliated_domains` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`practicegroup_id`)
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/practicegroups_dbkey ON /*_*/practicegroups (dbkey);
CREATE INDEX /*i*/practicegroups_name_full ON /*_*/practicegroups (name_full);
CREATE INDEX /*i*/practicegroups_name_short ON /*_*/practicegroups (name_short);