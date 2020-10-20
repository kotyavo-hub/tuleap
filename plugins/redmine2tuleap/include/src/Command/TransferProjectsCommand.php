<?php

namespace Maximaster\Redmine2TuleapPlugin\Command;

use Maximaster\Redmine2TuleapPlugin\Enum\RedmineProjectColumnEnum;
use Maximaster\Redmine2TuleapPlugin\Enum\RedmineTableEnum;
use Maximaster\Redmine2TuleapPlugin\Framework\GenericTransferCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TransferProjectsCommand extends GenericTransferCommand
{
    public static function getDefaultName()
    {
        return 'redmine2tuleap:projects:transfer';
    }

    protected function transfer(InputInterface $input, SymfonyStyle $output): int
    {
        $ss = new SymfonyStyle($input, $output);
        $ss->note('Импорт проектов');

        $redmineDb = $this->redmine();

        $projects = $redmineDb->run(
            'SELECT ' . implode(', ', [
                RedmineProjectColumnEnum::ID,
                RedmineProjectColumnEnum::NAME,
                RedmineProjectColumnEnum::DESCRIPTION,
                RedmineProjectColumnEnum::HOMEPAGE,
                RedmineProjectColumnEnum::IS_PUBLIC,
                RedmineProjectColumnEnum::PARENT_ID,
                RedmineProjectColumnEnum::CREATED_ON,
                RedmineProjectColumnEnum::UPDATED_ON,
                RedmineProjectColumnEnum::IDENTIFIER,
                RedmineProjectColumnEnum::STATUS,
                RedmineProjectColumnEnum::LFT,
                RedmineProjectColumnEnum::RGT,
                RedmineProjectColumnEnum::INHERIT_MEMBERS,
                RedmineProjectColumnEnum::DEFAULT_ASSIGNEE_ID,
            ]) .
            ' FROM ' . RedmineTableEnum::PROJECTS .
            ' ORDER BY ' . RedmineProjectColumnEnum::ID . ' ASC'
        );

        $output->note(count($projects));

        return 0;
    }
}
