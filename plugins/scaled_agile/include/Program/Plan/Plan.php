<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ScaledAgile\Program\Plan;

/**
 * @psalm-immutable
 */
final class Plan
{
    /**
     * @var ProgramIncrementTracker
     */
    private $program_increment_tracker;
    /**
     * @var ProgramPlannableTracker[]
     */
    private $plannable_trackers;
    /**
     * @var non-empty-list<ProgramUserGroup>
     */
    private $can_prioritize;

    /**
     * @param ProgramPlannableTracker[] $plannable_trackers
     * @param non-empty-list<ProgramUserGroup> $can_prioritize
     */
    public function __construct(
        ProgramIncrementTracker $program_increment_tracker,
        array $plannable_trackers,
        array $can_prioritize
    ) {
        $this->program_increment_tracker = $program_increment_tracker;
        $this->plannable_trackers        = $plannable_trackers;
        $this->can_prioritize            = $can_prioritize;
    }

    public function getProgramIncrementTracker(): ProgramIncrementTracker
    {
        return $this->program_increment_tracker;
    }

    /**
     * @return int[]
     */
    public function getPlannableTrackerIds(): array
    {
        return array_map(
            static function (ProgramPlannableTracker $tracker) {
                return $tracker->getId();
            },
            $this->plannable_trackers
        );
    }

    /**
     * @return non-empty-list<ProgramUserGroup>
     */
    public function getCanPrioritize(): array
    {
        return $this->can_prioritize;
    }
}
