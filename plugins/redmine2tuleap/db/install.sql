CREATE TABLE IF NOT EXISTS `plugin_redmine2tuleap_entity_external_id` (
    `id` INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`),
    `type` CHAR(16) NOT NULL,
    `redmine_id` INT NOT NULL,
    `tuleap_id` INT NOT NULL
);
