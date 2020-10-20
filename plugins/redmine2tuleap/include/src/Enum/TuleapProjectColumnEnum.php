<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class TuleapProjectColumnEnum extends Enum
{
    public const GROUP_ID = 'group_id';
    public const GROUP_NAME = 'group_name';
    public const ACCESS = 'access';
    public const STATUS = 'status';
    public const UNIX_GROUP_NAME = 'unix_group_name';
    public const UNIX_BOX = 'unix_box';
    public const HTTP_DOMAIN = 'http_domain';
    public const SHORT_DESCRIPTION = 'short_description';
    public const CVS_BOX = 'cvs_box';
    public const SVN_BOX = 'svn_box';
    public const REGISTER_TIME = 'register_time';
    public const RAND_HASH = 'rand_hash';
    public const TYPE = 'type';
    public const BUILT_FROM_TEMPLATE = 'built_from_template';
    public const CVS_TRACKER = 'cvs_tracker';
    public const CVS_WATCH_MODE = 'cvs_watch_mode';
    public const CVS_EVENTS_MAILING_LIST = 'cvs_events_mailing_list';
    public const CVS_EVENTS_MAILING_HEADER = 'cvs_events_mailing_header';
    public const CVS_PREAMBLE = 'cvs_preamble';
    public const CVS_IS_PRIVATE = 'cvs_is_private';
    public const SVN_TRACKER = 'svn_tracker';
    public const SVN_MANDATORY_REF = 'svn_mandatory_ref';
    public const SVN_CAN_CHANGE_LOG = 'svn_can_change_log';
    public const SVN_EVENTS_MAILING_HEADER = 'svn_events_mailing_header';
    public const SVN_PREAMBLE = 'svn_preamble';
    public const SVN_ACCESSFILE_VERSION_ID = 'svn_accessfile_version_id';
    public const SVN_COMMIT_TO_TAG_DENIED = 'svn_commit_to_tag_denied';
    public const TRUNCATED_EMAILS = 'truncated_emails';
}
