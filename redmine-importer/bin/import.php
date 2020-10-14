<?php

use Maximaster\RedmineTuleapImporter\Command\ImportCommand;
use Symfony\Component\Console\Application;

$composer = require_once __DIR__ . '/../vendor/autoload.php';

$application = new Application();
$application->setDefaultCommand(ImportCommand::getDefaultName());
$application->addCommands([
    new ImportCommand(realpath(__DIR__ . '/../'), new mysqli(getenv('DHOST'), 'root', getenv('MYSQL_ROOT_PASSWORD'))),
]);

$application->run();
