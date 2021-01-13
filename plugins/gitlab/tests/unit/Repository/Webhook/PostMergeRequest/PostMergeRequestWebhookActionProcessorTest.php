<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery;
use Project;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Psr\Log\LoggerInterface;
use Reference;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;

class PostMergeRequestWebhookActionProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PostMergeRequestWebhookActionProcessor
     */
    private $processor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ReferenceManager
     */
    private $reference_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryProjectRetriever
     */
    private $gitlab_repository_project_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|MergeRequestTuleapReferenceDao
     */
    private $merge_request_reference_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tuleap_reference_retriever               = Mockery::mock(TuleapReferenceRetriever::class);
        $this->logger                                   = Mockery::mock(LoggerInterface::class);
        $this->reference_manager                        = Mockery::mock(\ReferenceManager::class);
        $this->merge_request_reference_dao              = Mockery::mock(MergeRequestTuleapReferenceDao::class);
        $this->gitlab_repository_project_retriever      = Mockery::mock(GitlabRepositoryProjectRetriever::class);

        $this->processor = new PostMergeRequestWebhookActionProcessor(
            new WebhookTuleapReferencesParser(),
            $this->tuleap_reference_retriever,
            $this->reference_manager,
            $this->merge_request_reference_dao,
            $this->gitlab_repository_project_retriever,
            $this->logger
        );
    }

    public function testItProcessesActionsForPostMergeRequestWebhook(): void
    {
        $gitlab_repository = new GitlabRepository(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable()
        );

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title TULEAP-58",
            'TULEAP-666 TULEAP-45',
        );

        $this->gitlab_repository_project_retriever
            ->shouldReceive('getProjectsGitlabRepositoryIsIntegratedIn')
            ->with($gitlab_repository)
            ->andReturn([
                Project::buildForTest()
            ])
            ->once();

        $this->merge_request_reference_dao
            ->shouldReceive('saveGitlabMergeRequestInfo')
            ->with(1, 2, 'My Title TULEAP-58')
            ->once();

        $this->reference_manager
            ->shouldReceive('insertCrossReference')
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('3 Tuleap references found in merge request 2')
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('|_ Reference to Tuleap artifact #58 found, cross-reference will be added for each project the GitLab repository is integrated in.')
            ->once();


        $this->logger
            ->shouldReceive('info')
            ->with('|_ Reference to Tuleap artifact #666 found, cross-reference will be added for each project the GitLab repository is integrated in.')
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('|_ Reference to Tuleap artifact #45 found, cross-reference will be added for each project the GitLab repository is integrated in.')
            ->once();

        $this->tuleap_reference_retriever
            ->shouldReceive('retrieveTuleapReference')
            ->with(58)
            ->andThrow(new TuleapReferencedArtifactNotFoundException(58))
            ->once();

        $this->tuleap_reference_retriever
            ->shouldReceive('retrieveTuleapReference')
            ->with(666)
            ->andThrow(new TuleapReferenceNotFoundException())
            ->once();

        $this->tuleap_reference_retriever
            ->shouldReceive('retrieveTuleapReference')
            ->with(45)
            ->andReturn(
                new Reference(
                    43,
                    'key',
                    'desc',
                    'link',
                    'P',
                    'service_short_name',
                    'nature',
                    1,
                    100
                )
            )
            ->once();

        $this->logger
            ->shouldReceive("error")
            ->with('Tuleap artifact #58 not found, no cross-reference will be added.')
            ->once();

        $this->logger
            ->shouldReceive("error")
            ->with('No reference found with the keyword \'art\', and this must not happen. If you read this, this is really bad.')
            ->once();

        $this->logger
            ->shouldReceive("info")
            ->with('|  |_ Tuleap artifact #45 found')
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('Merge request data for 2 saved in database')
            ->once();

        $this->processor->process($gitlab_repository, $merge_request_webhook_data);
    }

    public function testItProcessesActionsForPostMergeRequestWebhookAlreadyIntegrated(): void
    {
        $gitlab_repository = new GitlabRepository(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable()
        );

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title TULEAP-58",
            '',
        );

        $this->gitlab_repository_project_retriever
            ->shouldReceive('getProjectsGitlabRepositoryIsIntegratedIn')
            ->with($gitlab_repository)
            ->andReturn([
                Project::buildForTest()
            ])
            ->once();

        $this->merge_request_reference_dao
            ->shouldReceive('saveGitlabMergeRequestInfo')
            ->with(1, 2, 'My Title TULEAP-58')
            ->once();

        $this->reference_manager
            ->shouldReceive('insertCrossReference')
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('1 Tuleap references found in merge request 2')
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('|_ Reference to Tuleap artifact #58 found, cross-reference will be added for each project the GitLab repository is integrated in.')
            ->once();

        $this->tuleap_reference_retriever
            ->shouldReceive('retrieveTuleapReference')
            ->with(58)
            ->andReturn(
                new Reference(
                    58,
                    'key',
                    'desc',
                    'link',
                    'P',
                    'service_short_name',
                    'nature',
                    1,
                    100
                )
            )
            ->once();

        $this->logger
            ->shouldReceive("info")
            ->with('|  |_ Tuleap artifact #58 found')
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('Merge request data for 2 saved in database')
            ->once();

        $this->processor->process($gitlab_repository, $merge_request_webhook_data);
    }
}
