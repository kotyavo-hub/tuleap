<?php

namespace Tuleap\Timetracking\Command;

use DataAccessResult;
use ProjectDao;
use ProjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TrackerFactory;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use UGroupDao;

class UpdateTimetrackingPermissionsCommand extends Command
{
    const ARGUMENT_PROJECTS_ALL = 'all';
    const ARGUMENT_PROJECTS = 'projects';
    const OPTION_GRANT_WRITER = 'grant-writer';
    const OPTION_GRANT_READER = 'grant-reader';
    const OPTION_REFUSE_WRITER = 'refuse-writer';
    const OPTION_REFUSE_READER = 'refuse-reader';

    /** @var TimetrackingUgroupDao */
    private $timetrackingUgroupDao;

    /** @var ProjectDao */
    private $projectDao;

    /** @var ProjectManager */
    private $projectManager;

    /** @var TrackerFactory */
    private $trackerFactory;

    /** @var UGroupDao */
    private $ugroupDao;

    public static function getDefaultName()
    {
        return 'timetracking:permissions:update';
    }

    public function __construct(
        ProjectManager $projectManager,
        TrackerFactory $trackerFactory,
        TimetrackingUgroupDao $timetrackingUgroupDao,
        ProjectDao $projectDao,
        UGroupDao $ugroupDao
    ) {
        parent::__construct();

        $this->projectManager = $projectManager;
        $this->trackerFactory = $trackerFactory;
        $this->timetrackingUgroupDao = $timetrackingUgroupDao;
        $this->projectDao = $projectDao;
        $this->ugroupDao = $ugroupDao;
    }

    protected function configure()
    {
        $def = $this->getDefinition();

        $def->setArguments([
            new InputArgument(
                self::ARGUMENT_PROJECTS,
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Affected project codes. Put "' . self::ARGUMENT_PROJECTS_ALL . '" to affect every project'
            ),
        ]);

        $def->setOptions([
            new InputOption(
                self::OPTION_GRANT_WRITER,
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Grant write permission to an user group with specified name'
            ),
            new InputOption(
                self::OPTION_GRANT_READER,
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Grant read permission to an user group with specified name'
            ),
            new InputOption(
                self::OPTION_REFUSE_WRITER,
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Refuse write permission to an user group with specified name'
            ),
            new InputOption(
                self::OPTION_REFUSE_READER,
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Refuse read permission to an user group with specified name'
            ),

        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ss = new SymfonyStyle($input, $output);

        $projectUnixNames = $input->getArgument(self::ARGUMENT_PROJECTS);
        if (array_search(self::ARGUMENT_PROJECTS_ALL, $projectUnixNames) !== false) {
            /** @var DataAccessResult $projectsResult */
            $projectUnixNames = array_column(
                iterator_to_array($this->projectDao->returnAllProjects(null, null)['projects']),
                'unix_group_name'
            );
        }

        if (!$projectUnixNames) {
            $ss->warning('Failed to find any projects suitable for the request');
            return 0;
        }

        $projectsCnt = count($projectUnixNames);
        $ss->section(sprintf('Processing %d project%s', $projectsCnt, $projectsCnt > 1 ? 's' : ''));

        $progress = $ss->createProgressBar($projectsCnt);

        foreach ($projectUnixNames as $projectUnixName) {
            $project = $this->projectManager->getProjectByUnixName($projectUnixName);
            if (!$project) {
                $ss->error(sprintf('Failed to find project with unix name "%s"', $projectUnixName));
                return -1;
            }

            $grantWriters = $this->convertUGroupNamesToIds($project->group_id, $input->getOption(self::OPTION_GRANT_WRITER));
            $refuseWriters = $this->convertUGroupNamesToIds($project->group_id, $input->getOption(self::OPTION_REFUSE_WRITER));
            $grantReaders = $this->convertUGroupNamesToIds($project->group_id, $input->getOption(self::OPTION_GRANT_READER));
            $refuseReaders = $this->convertUGroupNamesToIds($project->group_id, $input->getOption(self::OPTION_REFUSE_READER));

            foreach ($this->trackerFactory->getTrackersByGroupId($project->getID()) as $tracker) {
                if ($grantWriters || $refuseWriters) {
                    $currentWriters = array_column($this->timetrackingUgroupDao->getWriters($tracker->id), 'ugroup_id');
                    $updatedWriters = $this->getUpdatedList($currentWriters, $grantWriters, $refuseWriters);
                    if ($updatedWriters !== $currentWriters) {
                        $this->timetrackingUgroupDao->saveWriters($tracker->id, $updatedWriters);
                    }
                }

                if ($grantReaders || $refuseReaders) {
                    $currentReaders = array_column($this->timetrackingUgroupDao->getReaders($tracker->id), 'ugroup_id');
                    $updatedReaders = $this->getUpdatedList($currentReaders, $grantReaders, $refuseReaders);
                    if ($updatedReaders !== $currentReaders) {
                        $this->timetrackingUgroupDao->saveReaders($tracker->id, $updatedReaders);
                    }
                }
            }

            $progress->advance();
        }

        return 0;
    }

    private function getUpdatedList(array $list, array $grant, array $refuse): array
    {
        $updated = array_merge($list, $grant);
        $updated = array_diff($updated, $refuse);
        return array_unique($updated);
    }

    private function convertUGroupNamesToIds(int $projectId, array $names): array
    {
        static $projectsUgroups = [];
        if (empty($projectsUgroups[$projectId])) {
            $projectsUgroups[$projectId] = [];
            foreach ($this->ugroupDao->searchByGroupId($projectId)->getResult() as $ugroup) {
                $projectsUgroups[$projectId][ $ugroup['ugroup_id'] ] = $ugroup['name'];
            }
        }

        $ids = [];
        foreach ($names as $name) {
            $ids[] = array_search($name, $projectsUgroups[$projectId]);
        }

        return array_filter($ids);
    }
}
