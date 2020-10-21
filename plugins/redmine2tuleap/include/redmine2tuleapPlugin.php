<?php

use Maximaster\Redmine2TuleapPlugin\Command\TransferCommand;
use Maximaster\Redmine2TuleapPlugin\Command\TransferProjectsCommand;
use Maximaster\Redmine2TuleapPlugin\Command\TransferUsersCommand;
use Maximaster\Redmine2TuleapPlugin\Enum\DatabaseEnum;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineCustomFieldRepository;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Exception\ConstructorFailed;
use Symfony\Component\Console\Command\Command;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\DB\DBFactory;

require_once __DIR__ . '/../vendor/autoload.php';

class redmine2tuleapPlugin extends Plugin
{
    public const NAME = 'redmine2tuleap';

    public const PREFIXED_NAME = 'plugin_redmine2tuleap';

    /** @var bool */
    private $hooksRegistered = false;

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
    }

    public function getServiceShortname()
    {
        return self::PREFIXED_NAME;
    }

    public function getHooksAndCallbacks()
    {
        if (!$this->hooksRegistered) {
            $this->addHook(CLICommandsCollector::NAME);
        }

        return parent::getHooksAndCallbacks();
    }

    private function getRedmineDb(EasyDB $tuleapDb, bool $tryToCreate = true): EasyDB
    {
        try {
            return DBFactory::getDBConnection(DatabaseEnum::REDMINE)->getDB();
        } catch (ConstructorFailed $e) {
            if ($tryToCreate) {
                $tuleapDb->exec('CREATE DATABASE `redmine` DEFAULT CHARACTER SET utf8');
                return $this->getRedmineDb($tuleapDb, false);
            }

            throw $e;
        }
    }

    public function collectCLICommands(CLICommandsCollector $commandCollector)
    {
        $tuleapDb = DBFactory::getMainTuleapDBConnection()->getDB();
        $redmineDb = DBFactory::getDBConnection(DatabaseEnum::REDMINE)->getDB();

        $cfRepo = new RedmineCustomFieldRepository($redmineDb);

        $subTransferCommands = [
            TransferUsersCommand::class => function () use ($redmineDb, $tuleapDb, $cfRepo) {
                return new TransferUsersCommand($redmineDb, $tuleapDb, $cfRepo);
            },
            TransferProjectsCommand::class => function () use ($redmineDb, $tuleapDb, $cfRepo) {
                return new TransferProjectsCommand($redmineDb, $tuleapDb, $cfRepo);
            },
        ];

        $subTransferCommandNames = [];
        foreach ($subTransferCommands as $command => $subTransferCommand) {
            /** @var Command $command */
            $commandName = $command::getDefaultName();
            $subTransferCommandNames[] = $commandName;

            $commandCollector->addCommand($commandName, $subTransferCommand);
        }

        $commandCollector->addCommand(TransferCommand::getDefaultName(), function () use ($redmineDb, $tuleapDb, $subTransferCommandNames) {
            return new TransferCommand(
                $redmineDb,
                $tuleapDb,
                realpath(__DIR__ . '/../../../'),
                $subTransferCommandNames
            );
        });


    }

    public function getPluginInfo()
    {
        if ($this->pluginInfo) {
            return $this->pluginInfo;
        }

        /** @var BaseLanguage $Language */
        global $Language;

        $shortName = $this->getServiceShortname();

        $this->pluginInfo = new PluginFileInfo($this, $shortName);
        $this->pluginInfo->setPluginDescriptor(new PluginDescriptor(
            $Language->getText($shortName, 'descriptor_name'),
            false,
            $Language->getText($shortName, 'descriptor_description')
        ));

        return $this->pluginInfo;
    }
}
