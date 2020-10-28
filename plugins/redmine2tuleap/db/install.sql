CREATE TABLE IF NOT EXISTS `plugin_redmine2tuleap_entity_external_id` (
    `id` INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`),
    `type` CHAR(16) NOT NULL,
    `redmine_id` INT NOT NULL,
    `tuleap_id` INT NOT NULL
);

CREATE TABLE IF NOT EXISTS `plugin_redmine2tuleap_tracker_field_list_bind_users_backup` (
    `field_id` INT NOT NULL,
    `value_function` TEXT
);
