<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement;

use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Plan\BuildProgram;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\ScaledAgile\Program\ToBeCreatedProgram;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProgramIncrementBuilderTest extends TestCase
{
    public function testBuildsOpenProgramIncrements(): void
    {
        $build_program = new class implements BuildProgram {
            public function buildExistingProgramProject(int $id, \PFUser $user): Program
            {
                return new Program($id);
            }

            public function buildNewProgramProject(int $id, \PFUser $user): ToBeCreatedProgram
            {
                throw new \LogicException("Not implemented");
            }
        };

        $retrieve_program_increments = new class implements RetrieveProgramIncrements {
            public function retrieveOpenProgramIncrements(Program $program, \PFUser $user): array
            {
                return [];
            }
        };

        $program_increment_builder = new ProgramIncrementBuilder($build_program, $retrieve_program_increments);
        self::assertEquals([], $program_increment_builder->buildOpenProgramIncrements(12, UserTestBuilder::aUser()->build()));
    }
}
