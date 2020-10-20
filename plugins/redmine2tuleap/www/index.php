<?php

use Maximaster\Redmine2TuleapPlugin\Controller\PluginAdminPageController;
use Tuleap\Admin\AdminPageRenderer;

require_once __DIR__ . '/../../../src/www/include/pre.php';

/** @global Response $Response */
global $Response;

$pluginManager = PluginManager::instance();

/** @var Plugin $plugin */
$plugin = $pluginManager->getPluginByName(basename(realpath(__DIR__ . '/../')));

if (!$plugin || !$pluginManager->isPluginAvailable($plugin)) {
    $Response->permanentRedirect('/');
    return;
}

if (!UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $Response->permanentRedirect('/');
    return;
}

$admin_page_renderer = new AdminPageRenderer();
$admin_page_renderer->renderANoFramedPresenter(
    $plugin->getName(),
    ForgeConfig::get('codendi_dir') . '/plugins/redmine2tuleap/templates',
    'index',
    new stdClass()
);
