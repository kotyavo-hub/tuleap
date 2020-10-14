<?php

use Maximaster\RedmineTuleapImporter\Command\TransferCommand;
use Maximaster\RedmineTuleapImporter\Command\TransferProjectsCommand;
use Maximaster\RedmineTuleapImporter\Command\TransferUsersCommand;
use Maximaster\RedmineTuleapImporter\Database\Connection;
use Symfony\Component\Console\Application;

$composer = require_once __DIR__ . '/../vendor/autoload.php';

$connection = new Connection(new mysqli(getenv('DHOST'), 'root', getenv('MYSQL_ROOT_PASSWORD')));

$application = new Application();

$application->addCommands([
    new TransferCommand(realpath(__DIR__ . '/../'), $connection),
    new TransferUsersCommand($connection),
    new TransferProjectsCommand($connection),
]);

$application->setDefaultCommand(TransferCommand::getDefaultName());

$application->run();
