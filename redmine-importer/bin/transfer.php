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

$redmineDb = new MysqliDb($connectionParams['host'], $connectionParams['username'], $connectionParams['password'], DatabaseEnum::REDMINE);
$tuleapDb = new MysqliDb($connectionParams['host'], $connectionParams['username'], $connectionParams['password'], DatabaseEnum::TULEAP);

$cfRepo = new RedmineCustomFieldRepository($redmineDb);

$application = new Application();

$application->addCommands([
    new TransferCommand($redmineDb, $tuleapDb, realpath(__DIR__ . '/../')),
    new TransferUsersCommand($redmineDb, $tuleapDb, $cfRepo),
    new TransferProjectsCommand($redmineDb, $tuleapDb),
]);

$application->setDefaultCommand(TransferCommand::getDefaultName());

$application->run();
