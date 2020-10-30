<?php

include __DIR__ . '/../../../src/www/include/pre.php';

$pluginManager = PluginManager::instance();

/** @var timetrackingPlugin $timetrackingPlugin */
$timetrackingPlugin = $pluginManager->getPluginByName('timetracking');
$timetrackingPlugin->routePluginLegacyController()->process(
    HttpRequest::instance(),
    new FlamingParrot_Theme('/themes/FlamingParrot_Theme'),
    []
);
