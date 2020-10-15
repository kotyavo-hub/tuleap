<?php

use Maximaster\RedmineTuleapImporter\Command\TransferCommand;
use Maximaster\RedmineTuleapImporter\Command\TransferProjectsCommand;
use Maximaster\RedmineTuleapImporter\Command\TransferUsersCommand;
use Maximaster\RedmineTuleapImporter\Enum\DatabaseEnum;
use Maximaster\RedmineTuleapImporter\Repository\RedmineCustomFieldRepository;
use Symfony\Component\Console\Application;

$composer = require_once __DIR__ . '/../vendor/autoload.php';

$connectionParams = [
    'host' => getenv('DHOST'),
    'username' => 'root',
    'password' => getenv('MYSQL_ROOT_PASSWORD'),
    'charset' => 'utf8'
];

$db = new MysqliDb($connectionParams['host'], $connectionParams['username'], $connectionParams['password'], DatabaseEnum::TULEAP);
$db->addConnection('redmine', $connectionParams + ['db' => DatabaseEnum::REDMINE]);
$redmineConnection = $db->connection(DatabaseEnum::REDMINE);

$cfRepo = new RedmineCustomFieldRepository($redmineConnection);

$application = new Application();

$application->addCommands([
    new TransferCommand(realpath(__DIR__ . '/../'), $db),
    new TransferUsersCommand($db, $cfRepo),
    new TransferProjectsCommand($db),
]);

$application->setDefaultCommand(TransferCommand::getDefaultName());

$application->run();
