<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\PostAction;

use Tuleap\Event\Dispatchable;

class ExternalWorkflowAssetsPathsRetrieverEvent implements Dispatchable
{

    public const NAME = 'externalWorkflowAssetsPathsRetrieverEvent';

    /** @var JSONPCallback[] */
    private $callbacks = [];
    /**
     * @var \Project
     */
    private $project;
    public function __construct(\Project $project)
    {
        $this->project = $project;
    }
    public function addCallback(JSONPCallback $callback): void
    {
        $this->callbacks[] = $callback;
    }
    /**
     * @return JSONPCallback[]
     */
    public function getCallbacks(): array
    {
        return $this->callbacks;
    }
    /**
     * @return \Project
     */
    public function getProject(): \Project
    {
        return $this->project;
    }
}