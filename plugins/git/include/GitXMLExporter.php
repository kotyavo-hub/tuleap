<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Git;

use EventManager;
use Git;
use Git_LogDao;
use GitPermissionsManager;
use GitRepository;
use GitRepositoryFactory;
use Psr\Log\LoggerInterface;
use Project;
use ProjectUGroup;
use SimpleXMLElement;
use Tuleap\Git\Events\XMLExportExternalContentEvent;
use Tuleap\GitBundle;
use Tuleap\Project\UGroups\InvalidUGroupException;
use Tuleap\Project\XML\Export\ArchiveInterface;
use UGroupManager;
use UserManager;
use UserXMLExporter;

class GitXmlExporter
{
    public const EXPORT_FOLDER = "export";

    /**
     * @var Project
     */
    private $project;

    /**
     * @var GitPermissionsManager
     */
    private $permission_manager;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var GitBundle
     */
    private $git_bundle;
    /**
     * @var Git_LogDao
     */
    private $git_log_dao;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UserXMLExporter
     */
    private $user_exporter;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        Project $project,
        GitPermissionsManager $permission_manager,
        UGroupManager $ugroup_manager,
        GitRepositoryFactory $repository_factory,
        LoggerInterface $logger,
        GitBundle $git_bundle,
        Git_LogDao $git_log_dao,
        UserManager $user_manager,
        UserXMLExporter $user_exporter,
        EventManager $event_manager
    ) {
        $this->project            = $project;
        $this->permission_manager = $permission_manager;
        $this->ugroup_manager     = $ugroup_manager;
        $this->repository_factory = $repository_factory;
        $this->logger             = $logger;
        $this->git_bundle         = $git_bundle;
        $this->git_log_dao        = $git_log_dao;
        $this->user_manager       = $user_manager;
        $this->user_exporter      = $user_exporter;
        $this->event_manager      = $event_manager;
    }

    public function exportToXml(SimpleXMLElement $xml_content, ArchiveInterface $archive, $temporary_dump_path_on_filesystem)
    {
        $root_node = $xml_content->addChild("git");
        $this->exportGitAdministrators($root_node);
        $this->exportExternalGitAdministrationContent($root_node);

        $this->exportGitRepositories($root_node, $temporary_dump_path_on_filesystem, $archive);
    }

    private function exportGitAdministrators(SimpleXMLElement $xml_content)
    {
        $this->logger->info('Export git administrators');
        $root_node     = $xml_content->addChild("ugroups-admin");
        $admin_ugroups = $this->permission_manager->getCurrentGitAdminUgroups($this->project->getId());

        foreach ($admin_ugroups as $ugroup) {
            $root_node->addChild("ugroup", $this->getLabelForUgroup($ugroup));
        }
    }

    private function getLabelForUgroup($ugroup)
    {
        if ($ugroup === ProjectUGroup::PROJECT_MEMBERS) {
            return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_members_name_key');
        }

        if ($ugroup === ProjectUGroup::PROJECT_ADMIN) {
            return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_admins_name_key');
        }

        $ugroup_object = $this->ugroup_manager->getUGroup($this->project, $ugroup);
        if (! $ugroup_object) {
            throw new InvalidUGroupException($ugroup);
        }
        return $ugroup_object->getTranslatedName();
    }

    private function exportExternalGitAdministrationContent(SimpleXMLElement $xml_content): void
    {
        $this->event_manager->processEvent(
            new XMLExportExternalContentEvent(
                $this->project,
                $xml_content,
                $this->logger
            )
        );
    }

    private function exportGitRepositories(
        SimpleXMLElement $xml_content,
        $temporary_dump_path_on_filesystem,
        ArchiveInterface $archive
    ) {
        $this->logger->info('Export git repositories');
        $repositories = $this->repository_factory->getAllRepositories($this->project);

        $archive->addEmptyDir('export');

        foreach ($repositories as $repository) {
            if ($repository->getParent()) {
                continue;
            }

            $root_node = $xml_content->addChild("repository");
            $root_node->addAttribute("name", $repository->getName());
            $root_node->addAttribute("description", $repository->getDescription());

            $row = $this->git_log_dao->getLastPushForRepository($repository->getId());
            if (! empty($row) && $row['user_id'] !== 0) {
                $last_push_node    = $root_node->addChild("last-push-date");
                $user              = $this->user_manager->getUserById($row['user_id']);
                $this->user_exporter->exportUser($user, $last_push_node, 'user');
                $last_push_node->addAttribute("push_date", $row["push_date"]);
                $last_push_node->addAttribute("commits_number", $row["commits_number"]);
                $last_push_node->addAttribute("refname", $row["refname"]);
                $last_push_node->addAttribute("operation_type", $row["operation_type"]);
                $last_push_node->addAttribute("refname_type", $row["refname_type"]);
            }

            $bundle_path = "";
            if ($repository->isInitialized()) {
                $bundle_path = self::EXPORT_FOLDER . DIRECTORY_SEPARATOR . $repository->getName() . '.bundle';
            }

            $root_node->addAttribute(
                "bundle-path",
                $bundle_path
            );

            $this->bundleRepository($repository, $temporary_dump_path_on_filesystem, $archive);

            $this->exportGitRepositoryPermissions($repository, $root_node);
        }
    }

    private function bundleRepository(
        GitRepository $repository,
        $temporary_dump_path_on_filesystem,
        ArchiveInterface $archive
    ) {
        $this->logger->info('Create git bundle for repository ' . $repository->getName());

        $this->git_bundle->dumpRepository($repository, $archive, $temporary_dump_path_on_filesystem);
    }

    private function exportGitRepositoryPermissions(GitRepository $repository, SimpleXMLElement $xml_content)
    {
        $this->logger->info('Export repository permissions');
        $default_permissions = $this->permission_manager->getRepositoryGlobalPermissions($repository);

        $read_node = $xml_content->addChild("read");
        if (isset($default_permissions[Git::PERM_READ])) {
            $this->exportPermission($read_node, $default_permissions[Git::PERM_READ]);
        }

        if (isset($default_permissions[Git::PERM_WRITE])) {
            $write_node = $xml_content->addChild("write");
            $this->exportPermission($write_node, $default_permissions[Git::PERM_WRITE]);
        }

        if (isset($default_permissions[Git::PERM_WPLUS])) {
            $wplus_node = $xml_content->addChild("wplus");
            $this->exportPermission($wplus_node, $default_permissions[Git::PERM_WPLUS]);
        }
    }

    private function exportPermission(SimpleXMLElement $xml_content, $permissions)
    {
        foreach ($permissions as $permission) {
            $xml_content->addChild("ugroup", $this->getLabelForUgroup($permission));
        }
    }
}
