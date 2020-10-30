<?php

use Maximaster\Redmine2TuleapPlugin\Command\TransferCommand;
use Maximaster\Redmine2TuleapPlugin\Command\TransferIssuesCommand;
use Maximaster\Redmine2TuleapPlugin\Command\TransferProjectsCommand;
use Maximaster\Redmine2TuleapPlugin\Command\TransferUsersCommand;
use Maximaster\Redmine2TuleapPlugin\Enum\DatabaseEnum;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapReferenceRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\PluginRedmine2TuleapTrackerFieldListBindUsersBackupRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineCustomFieldRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineIssueStatusRepository;
use Maximaster\Redmine2TuleapPlugin\Repository\RedmineEnumerationRepository;
use Symfony\Component\Console\Command\Command;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\DB\DBFactory;
use Netcarver\Textile;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithoutStatusCheckAndNotifications;
use Tuleap\Project\UserPermissionsDao;

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

    public function collectCLICommands(CLICommandsCollector $commandCollector)
    {
        $tuleapDb = DBFactory::getMainTuleapDBConnection()->getDB();
        $redmineDb = DBFactory::getDBConnection(DatabaseEnum::REDMINE)->getDB();

        $cfRepo = new RedmineCustomFieldRepository($redmineDb);
        $redmineIssueStatusRepo = new RedmineIssueStatusRepository($redmineDb);
        $redmineEnumerationRepository = new RedmineEnumerationRepository($redmineDb);
        $redmine2TuleapReferenceRepo = new PluginRedmine2TuleapReferenceRepository($tuleapDb);
        $redmine2TuleapTrackerFieldListBindUsersBackupRepo = new PluginRedmine2TuleapTrackerFieldListBindUsersBackupRepository($tuleapDb);
        $pluginDirectory = $this->getFilesystemPath();

        $trackerArtifactFactory = Tracker_ArtifactFactory::instance();
        $userPermDao = new UserPermissionsDao();
        $projectMemberAdder = ProjectMemberAdderWithoutStatusCheckAndNotifications::build();
        $userManager = UserManager::instance();

        $textileParser = new Textile\Parser();

        $subTransferCommands = [
            TransferUsersCommand::class => function () use (
                $pluginDirectory,
                $redmineDb,
                $tuleapDb,
                $redmine2TuleapReferenceRepo,
                $cfRepo,
                $userManager,
                $userPermDao
            ) {
                return new TransferUsersCommand(
                    $pluginDirectory,
                    $redmineDb,
                    $tuleapDb,
                    $redmine2TuleapReferenceRepo,
                    $cfRepo,
                    $userManager,
                    $userPermDao
                );
            },
            TransferProjectsCommand::class => function ()
                use (
                    $pluginDirectory,
                    $redmineDb,
                    $tuleapDb,
                    $redmine2TuleapReferenceRepo,
                    $cfRepo,
                    $redmineIssueStatusRepo,
                    $redmineEnumerationRepository,
                    $projectMemberAdder,
                    $userManager
                ) {
                return new TransferProjectsCommand(
                    $pluginDirectory,
                    $redmineDb,
                    $tuleapDb,
                    $redmine2TuleapReferenceRepo,
                    $cfRepo,
                    $redmineIssueStatusRepo,
                    $redmineEnumerationRepository,
                    ProjectManager::instance(),
                    TrackerFactory::instance(),
                    Tracker_FormElementFactory::instance(),
                    $projectMemberAdder,
                    $userManager
                );
            },
            TransferIssuesCommand::class => function ()
                use (
                    $pluginDirectory,
                    $redmineDb,
                    $tuleapDb,
                    $redmine2TuleapReferenceRepo,
                    $trackerArtifactFactory,
                    $cfRepo,
                    $redmineIssueStatusRepo,
                    $redmineEnumerationRepository,
                    $textileParser,
                    $redmine2TuleapTrackerFieldListBindUsersBackupRepo,
                    $userPermDao,
                    $userManager
                ) {
                return new TransferIssuesCommand(
                    $pluginDirectory,
                    $redmineDb,
                    $tuleapDb,
                    $redmine2TuleapReferenceRepo,
                    $trackerArtifactFactory,
                    TrackerFactory::instance(),
                    $cfRepo,
                    Tracker_FormElementFactory::instance(),
                    $redmineIssueStatusRepo,
                    $redmineEnumerationRepository,
                    $textileParser,
                    $redmine2TuleapTrackerFieldListBindUsersBackupRepo,
                    $userPermDao,
                    $userManager
                );
            },
        ];

        $subTransferCommandNames = [];
        foreach ($subTransferCommands as $command => $subTransferCommand) {
            /** @var Command $command */
            $commandName = $command::getDefaultName();
            $subTransferCommandNames[] = $commandName;

            $commandCollector->addCommand($commandName, $subTransferCommand);
        }

        $commandCollector->addCommand(TransferCommand::getDefaultName(), function () use ($pluginDirectory, $redmineDb, $tuleapDb, $redmine2TuleapReferenceRepo, $subTransferCommandNames) {
            return new TransferCommand(
                $pluginDirectory,
                $redmineDb,
                $tuleapDb,
                $redmine2TuleapReferenceRepo,
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
