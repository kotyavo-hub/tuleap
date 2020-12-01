<?php
/**
 *  Copyright (c) Maximaster, 2020. All rights reserved
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker\Permission\FollowUp\PrivateComments;

use PFUser;
use Tracker;
use Tuleap\Tracker\Permission\FollowUp\PrivateComments\TrackerPrivateCommentsDao;

class PermissionsOnPrivateCommentChecker
{
    public static function checkPermission(PFUser $user, Tracker $tracker): bool
    {
        if ($user->isAdmin($tracker->getProject()->getID())) {
            return true;
        }
        $dao = new TrackerPrivateCommentsDao();
        $user_ugroups = $user->getUgroups($tracker->getProject()->getID(), []);
        $private_comments_groups = array_column($dao->getAccessUgroupsByTrackerId($tracker->getId()), 'ugroup_id');
        foreach ($user_ugroups as $user_ugroup) {
            if (in_array($user_ugroup, $private_comments_groups)) {
                return true;
            }
        }
        return false;
    }
}
