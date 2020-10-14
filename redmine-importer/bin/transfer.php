<?php

use Maximaster\RedmineTuleapImporter\Command\TransferCommand;
use Maximaster\RedmineTuleapImporter\Command\TransferProjectsCommand;
use Maximaster\RedmineTuleapImporter\Command\TransferUsersCommand;
use Maximaster\RedmineTuleapImporter\Enum\DatabaseEnum;
use Symfony\Component\Console\Application;

$composer = require_once __DIR__ . '/../vendor/autoload.php';

$db = new MysqliDb(getenv('DHOST'), 'root', getenv('MYSQL_ROOT_PASSWORD'), DatabaseEnum::TULEAP);
$db->addConnection('redmine', [
    'host' => getenv('DHOST'),
    'username' => 'root',
    'password' => getenv('MYSQL_ROOT_PASSWORD'),
    'db' => 'redmine',
]);

$application = new Application();

$application->addCommands([
    new TransferCommand(realpath(__DIR__ . '/../'), $db),
    new TransferUsersCommand($db),
    new TransferProjectsCommand($db),
]);

$application->setDefaultCommand(TransferCommand::getDefaultName());

$application->run();
