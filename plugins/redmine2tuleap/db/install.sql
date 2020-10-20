CREATE DATABASE `redmine` DEFAULT CHARACTER SET utf8;

CREATE TABLE IF NOT EXISTS `plugin_redmine2tuleap_entity_external_id` (
    `id` TINYINT NOT NULL,
    PRIMARY KEY ( `id` ),
    `type` CHAR(16) NOT NULL,
    `redmine_id` INT NOT NULL,
    `tuleap_id` INT NOT NULL
);
