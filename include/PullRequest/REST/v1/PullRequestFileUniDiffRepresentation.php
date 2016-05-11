<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;
use Tuleap\PullRequest\FileUniDiff;

class PullRequestFileUniDiffRepresentation
{
    /**
     * @var PullRequestLineUniDiffRepresentation[] {@type PullRequestLineUniDiffRepresentation[]}
     */
    public $lines;

    /**
     * @var PullRequestInlineCommentRepresentation[] {@type PullRequestInlineCommentRepresentation[]}
     */
    public $inline_comments;

    public function __construct()
    {
        $lines = array();
    }

    public function addLine(PullRequestLineUniDiffRepresentation $line)
    {
        $this->lines[] = $line;
    }

    static public function build(FileUniDiff $diff, array $inline_comments)
    {
        $new_instance = new PullRequestFileUniDiffRepresentation();
        foreach ($diff->getLines() as $line) {
            $new_instance->addLine(
                new PullRequestLineUniDiffRepresentation($line)
            );
        }
        $new_instance->inline_comments = $inline_comments;

        return $new_instance;
    }
}
