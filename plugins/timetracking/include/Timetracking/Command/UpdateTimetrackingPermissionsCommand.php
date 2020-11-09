<?php

namespace Tuleap\Timetracking\Command;

use Codendi_Request;
use DataAccessResult;
use Exception;
use ProjectDao;
use ProjectManager;
use ProjectUGroup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TrackerFactory;
use TrackerManager;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use UGroupDao;
use UGroupManager;
use UserDao;

class UpdateTimetrackingPermissionsCommand extends Command
{
    const ARGUMENT_PROJECTS_ALL = 'all';
    const ARGUMENT_PROJECTS = 'projects';
    const OPTION_GRANT_WRITER = 'grant-writer';
    const OPTION_GRANT_READER = 'grant-writer';

    /** @var TimetrackingUgroupDao */
    private $timetrackingUgroupDao;

    /** @var ProjectDao */
    private $projectDao;

    /** @var ProjectManager */
    private $projectManager;

    /** @var TrackerManager */
    private $trackerManager;

    /** @var TrackerFactory */
    private $trackerFactory;

    /** @var UGroupDao */
    private $ugroupDao;

    /** @var UGroupManager */
    private $ugroupManager;

    /** @var UserDao */
    private $userDao;

    public static function getDefaultName()
    {
        return 'timetracking:permissions:update';
    }

    public function __construct(TimetrackingUgroupDao $timetrackingUgroupDao)
    {
        parent::__construct();

        $this->timetrackingUgroupDao = $timetrackingUgroupDao;
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
                'w',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Grant write permission to group with specified name'
            ),
            new InputOption(
                self::OPTION_GRANT_READER,
                'r',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Grant read permission to group with specified name'
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

        $newWriters = $this->convertUserNamesToIds($input->getOption(self::OPTION_GRANT_WRITER));
        $newReaders = $this->convertUserNamesToIds($input->getOption(self::OPTION_GRANT_READER));

        foreach ($projectUnixNames as $projectUnixName) {
            $project = $this->projectManager->getProjectByUnixName($projectUnixName);
            if (!$project) {
                $ss->error(sprintf('Failed to find project with unix name "%s"', $projectUnixName));
                return -1;
            }

            /** @var ProjectUGroup[] $projectUgroups */
            $projectUGroups = $this->ugroupManager->getUgroupsById($project);
            $ugroupIdByName = array_combine(
                array_map(
                    function (ProjectUGroup $projectUGroup) {
                        return $projectUGroup->getName();
                    },
                    $projectUGroups
                ),
                array_map(
                    function (ProjectUGroup $projectUGroup) {
                        return $projectUGroup->getId();
                    },
                    $projectUGroups
                )
            );

            foreach ($this->trackerFactory->getTrackersByGroupId($project->getID()) as $tracker) {
                // $this->ugroupDao->searchByGroupId()->getResult();

                // $this->timetrackingUgroupDao
            }
        }



        $this->adminController->editTimetrackingAdminSettings(null, new Codendi_Request([
            'enable_timetracking' => true,
            'write_ugroups' => [],
            'read_ugroups' => [],
        ]));

        global $Response;
        if ($Response->feedbackHasErrors()) {
            $ss->error($Response->_feedback->fetchAsPlainText());
            return -1;
        }

        return 0;
    }

    private function convertUserNamesToIds(array $names): array
    {
        static $allUserNames;
        if ($allUserNames === null) {
            foreach ($this->userDao->listAllUsers(null, null, null, null, 'user_Id', 'asc', null)['users'] as $user) {
                $allUserNames[ $user['user_id'] ] = $user['user_name'];
            }
        }

        $ids = [];
        foreach ($names as $name) {
            $id = array_search($name, $allUserNames);
            if (!$id) {
                throw new Exception('Failed to find user "%s"', $name);
            }

            $ids[] = $id;
        }

        return $ids;
    }
}
