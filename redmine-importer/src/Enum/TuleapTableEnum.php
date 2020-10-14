<?php

namespace Maximaster\RedmineTuleapImporter\Enum;

class TuleapTableEnum
{
    public const CROSS_REFERENCES = 'cross_references';
    public const CVS_BRANCHES = 'cvs_branches';
    public const CVS_CHECKINS = 'cvs_checkins';
    public const CVS_COMMITS = 'cvs_commits';
    public const CVS_DESCS = 'cvs_descs';
    public const CVS_DIRS = 'cvs_dirs';
    public const CVS_FILES = 'cvs_files';
    public const CVS_REPOSITORIES = 'cvs_repositories';
    public const CVS_TAGS = 'cvs_tags';
    public const DASHBOARDS_LINES = 'dashboards_lines';
    public const DASHBOARDS_LINES_COLUMNS = 'dashboards_lines_columns';
    public const DASHBOARDS_LINES_COLUMNS_WIDGETS = 'dashboards_lines_columns_widgets';
    public const EMAIL_GATEWAY_SALT = 'email_gateway_salt';
    public const FEEDBACK = 'feedback';
    public const FILEDOWNLOAD_LOG = 'filedownload_log';
    public const FILEMODULE = 'filemodule';
    public const FILEMODULE_MONITOR = 'filemodule_monitor';
    public const FILERELEASE = 'filerelease';
    public const FORGE_UPGRADE_BUCKET = 'forge_upgrade_bucket';
    public const FORGE_UPGRADE_LOG = 'forge_upgrade_log';
    public const FORGECONFIG = 'forgeconfig';
    public const FORUM = 'forum';
    public const FORUM_AGG_MSG_COUNT = 'forum_agg_msg_count';
    public const FORUM_GROUP_LIST = 'forum_group_list';
    public const FORUM_MONITORED_FORUMS = 'forum_monitored_forums';
    public const FORUM_MONITORED_THREADS = 'forum_monitored_threads';
    public const FORUM_SAVED_PLACE = 'forum_saved_place';
    public const FORUM_THREAD_ID = 'forum_thread_id';
    public const FRS_DLSTATS_FILE_AGG = 'frs_dlstats_file_agg';
    public const FRS_DLSTATS_FILETOTAL_AGG = 'frs_dlstats_filetotal_agg';
    public const FRS_DLSTATS_GROUP_AGG = 'frs_dlstats_group_agg';
    public const FRS_DLSTATS_GROUPTOTAL_AGG = 'frs_dlstats_grouptotal_agg';
    public const FRS_DOWNLOAD_AGREEMENT = 'frs_download_agreement';
    public const FRS_DOWNLOAD_AGREEMENT_DEFAULT = 'frs_download_agreement_default';
    public const FRS_FILE = 'frs_file';
    public const FRS_FILE_DELETED = 'frs_file_deleted';
    public const FRS_FILETYPE = 'frs_filetype';
    public const FRS_GLOBAL_PERMISSIONS = 'frs_global_permissions';
    public const FRS_LOG = 'frs_log';
    public const FRS_PACKAGE = 'frs_package';
    public const FRS_PACKAGE_DOWNLOAD_AGREEMENT = 'frs_package_download_agreement';
    public const FRS_PROCESSOR = 'frs_processor';
    public const FRS_RELEASE = 'frs_release';
    public const FRS_UPLOADED_LINKS = 'frs_uploaded_links';
    public const GENERIC_USER = 'generic_user';
    public const GROUP_CVS_FULL_HISTORY = 'group_cvs_full_history';
    public const GROUP_CVS_HISTORY = 'group_cvs_history';
    public const GROUP_DESC = 'group_desc';
    public const GROUP_DESC_VALUE = 'group_desc_value';
    public const GROUP_HISTORY = 'group_history';
    public const GROUP_SVN_FULL_HISTORY = 'group_svn_full_history';
    public const GROUP_TYPE = 'group_type';
    public const GROUPS = 'groups';
    public const GROUPS_NOTIF_DELEGATION = 'groups_notif_delegation';
    public const GROUPS_NOTIF_DELEGATION_MESSAGE = 'groups_notif_delegation_message';
    public const HOMEPAGE_HEADLINE = 'homepage_headline';
    public const INVITATIONS = 'invitations';
    public const MAIL_GROUP_LIST = 'mail_group_list';
    public const NEWS_BYTES = 'news_bytes';
    public const NOTIFICATIONS = 'notifications';
    public const PASSWORD_CONFIGURATION = 'password_configuration';
    public const PERMISSIONS = 'permissions';
    public const PERMISSIONS_VALUES = 'permissions_values';
    public const PLUGIN = 'plugin';
    public const PLUGIN_LDAP_PROJECT_GROUP = 'plugin_ldap_project_group';
    public const PLUGIN_LDAP_SUSPENDED_USER = 'plugin_ldap_suspended_user';
    public const PLUGIN_LDAP_SVN_REPOSITORY = 'plugin_ldap_svn_repository';
    public const PLUGIN_LDAP_UGROUP = 'plugin_ldap_ugroup';
    public const PLUGIN_LDAP_USER = 'plugin_ldap_user';
    public const PLUGIN_TRACKER_ARTIFACT_PENDING_REMOVAL = 'plugin_tracker_artifact_pending_removal';
    public const PLUGIN_TRACKER_ARTIFACTLINK_NATURES = 'plugin_tracker_artifactlink_natures';
    public const PLUGIN_TRACKER_CHANGESET_FROM_XML = 'plugin_tracker_changeset_from_xml';
    public const PLUGIN_TRACKER_CONFIG = 'plugin_tracker_config';
    public const PLUGIN_TRACKER_DELETED_ARTIFACTS = 'plugin_tracker_deleted_artifacts';
    public const PLUGIN_TRACKER_FILE_UPLOAD = 'plugin_tracker_file_upload';
    public const PLUGIN_TRACKER_IN_NEW_DROPDOWN = 'plugin_tracker_in_new_dropdown';
    public const PLUGIN_TRACKER_INVOLVED_NOTIFICATION_SUBSCRIBERS = 'plugin_tracker_involved_notification_subscribers';
    public const PLUGIN_TRACKER_NOTIFICATION_ASSIGNED_TO = 'plugin_tracker_notification_assigned_to';
    public const PLUGIN_TRACKER_NOTIFICATION_EMAIL_CUSTOM_SENDER_FORMAT = 'plugin_tracker_notification_email_custom_sender_format';
    public const PLUGIN_TRACKER_PENDING_JIRA_IMPORT = 'plugin_tracker_pending_jira_import';
    public const PLUGIN_TRACKER_PROJECTS_UNUSED_ARTIFACTLINK_TYPES = 'plugin_tracker_projects_unused_artifactlink_types';
    public const PLUGIN_TRACKER_PROJECTS_USE_ARTIFACTLINK_TYPES = 'plugin_tracker_projects_use_artifactlink_types';
    public const PLUGIN_TRACKER_RECENTLY_VISITED = 'plugin_tracker_recently_visited';
    public const PLUGIN_TRACKER_SOURCE_ARTIFACT_ID = 'plugin_tracker_source_artifact_id';
    public const PLUGIN_TRACKER_WEBHOOK_LOG = 'plugin_tracker_webhook_log';
    public const PLUGIN_TRACKER_WEBHOOK_URL = 'plugin_tracker_webhook_url';
    public const PLUGIN_TRACKER_WORKFLOW_POSTACTIONS_FROZEN_FIELDS = 'plugin_tracker_workflow_postactions_frozen_fields';
    public const PLUGIN_TRACKER_WORKFLOW_POSTACTIONS_FROZEN_FIELDS_VALUE = 'plugin_tracker_workflow_postactions_frozen_fields_value';
    public const PLUGIN_TRACKER_WORKFLOW_POSTACTIONS_HIDDEN_FIELDSETS = 'plugin_tracker_workflow_postactions_hidden_fieldsets';
    public const PLUGIN_TRACKER_WORKFLOW_POSTACTIONS_HIDDEN_FIELDSETS_VALUE = 'plugin_tracker_workflow_postactions_hidden_fieldsets_value';
    public const PROJECT_BACKGROUND = 'project_background';
    public const PROJECT_BANNER = 'project_banner';
    public const PROJECT_COUNTS_TMP = 'project_counts_tmp';
    public const PROJECT_COUNTS_WEEKLY_TMP = 'project_counts_weekly_tmp';
    public const PROJECT_DASHBOARDS = 'project_dashboards';
    public const PROJECT_DASHBOARDS_DISABLED_WIDGETS = 'project_dashboards_disabled_widgets';
    public const PROJECT_LABEL = 'project_label';
    public const PROJECT_MEMBERSHIP_DELEGATION = 'project_membership_delegation';
    public const PROJECT_METRIC = 'project_metric';
    public const PROJECT_METRIC_TMP1 = 'project_metric_tmp1';
    public const PROJECT_METRIC_WEEKLY_TMP1 = 'project_metric_weekly_tmp1';
    public const PROJECT_PARENT = 'project_parent';
    public const PROJECT_PLUGIN = 'project_plugin';
    public const PROJECT_TEMPLATE_XML = 'project_template_xml';
    public const PROJECT_UGROUP_SYNCHRONIZED_MEMBERSHIP = 'project_ugroup_synchronized_membership';
    public const PROJECT_WEBHOOK_LOG = 'project_webhook_log';
    public const PROJECT_WEBHOOK_URL = 'project_webhook_url';
    public const PROJECT_WEEKLY_METRIC = 'project_weekly_metric';
    public const REFERENCE = 'reference';
    public const REFERENCE_GROUP = 'reference_group';
    public const RELEASE_NOTE_LINK = 'release_note_link';
    public const REST_AUTHENTICATION_TOKEN = 'rest_authentication_token';
    public const SERVICE = 'service';
    public const SESSION = 'session';
    public const SOAP_CALL_COUNTER = 'soap_call_counter';
    public const STATS_PROJECT = 'stats_project';
    public const STATS_PROJECT_TMP = 'stats_project_tmp';
    public const SVN_ACCESSFILE_HISTORY = 'svn_accessfile_history';
    public const SVN_CACHE_PARAMETER = 'svn_cache_parameter';
    public const SVN_CHECKINS = 'svn_checkins';
    public const SVN_COMMITS = 'svn_commits';
    public const SVN_DIRS = 'svn_dirs';
    public const SVN_FILES = 'svn_files';
    public const SVN_IMMUTABLE_TAGS = 'svn_immutable_tags';
    public const SVN_NOTIFICATION = 'svn_notification';
    public const SVN_REPOSITORIES = 'svn_repositories';
    public const SVN_TOKEN = 'svn_token';
    public const SYSTEM_EVENT = 'system_event';
    public const SYSTEM_EVENTS_FOLLOWERS = 'system_events_followers';
    public const TOP_GROUP = 'top_group';
    public const TRACKER = 'tracker';
    public const TRACKER_ARTIFACT = 'tracker_artifact';
    public const TRACKER_ARTIFACT_PRIORITY_HISTORY = 'tracker_artifact_priority_history';
    public const TRACKER_ARTIFACT_PRIORITY_RANK = 'tracker_artifact_priority_rank';
    public const TRACKER_ARTIFACT_UNSUBSCRIBE = 'tracker_artifact_unsubscribe';
    public const TRACKER_CANNED_RESPONSE = 'tracker_canned_response';
    public const TRACKER_CHANGESET = 'tracker_changeset';
    public const TRACKER_CHANGESET_COMMENT = 'tracker_changeset_comment';
    public const TRACKER_CHANGESET_COMMENT_FULLTEXT = 'tracker_changeset_comment_fulltext';
    public const TRACKER_CHANGESET_INCOMINGMAIL = 'tracker_changeset_incomingmail';
    public const TRACKER_CHANGESET_VALUE = 'tracker_changeset_value';
    public const TRACKER_CHANGESET_VALUE_ARTIFACTLINK = 'tracker_changeset_value_artifactlink';
    public const TRACKER_CHANGESET_VALUE_COMPUTEDFIELD_MANUAL_VALUE = 'tracker_changeset_value_computedfield_manual_value';
    public const TRACKER_CHANGESET_VALUE_DATE = 'tracker_changeset_value_date';
    public const TRACKER_CHANGESET_VALUE_FILE = 'tracker_changeset_value_file';
    public const TRACKER_CHANGESET_VALUE_FLOAT = 'tracker_changeset_value_float';
    public const TRACKER_CHANGESET_VALUE_INT = 'tracker_changeset_value_int';
    public const TRACKER_CHANGESET_VALUE_LIST = 'tracker_changeset_value_list';
    public const TRACKER_CHANGESET_VALUE_OPENLIST = 'tracker_changeset_value_openlist';
    public const TRACKER_CHANGESET_VALUE_PERMISSIONSONARTIFACT = 'tracker_changeset_value_permissionsonartifact';
    public const TRACKER_CHANGESET_VALUE_TEXT = 'tracker_changeset_value_text';
    public const TRACKER_FIELD = 'tracker_field';
    public const TRACKER_FIELD_BURNDOWN = 'tracker_field_burndown';
    public const TRACKER_FIELD_COMPUTED = 'tracker_field_computed';
    public const TRACKER_FIELD_COMPUTED_CACHE = 'tracker_field_computed_cache';
    public const TRACKER_FIELD_DATE = 'tracker_field_date';
    public const TRACKER_FIELD_FLOAT = 'tracker_field_float';
    public const TRACKER_FIELD_INT = 'tracker_field_int';
    public const TRACKER_FIELD_LIST = 'tracker_field_list';
    public const TRACKER_FIELD_LIST_BIND_DECORATOR = 'tracker_field_list_bind_decorator';
    public const TRACKER_FIELD_LIST_BIND_DEFAULTVALUE = 'tracker_field_list_bind_defaultvalue';
    public const TRACKER_FIELD_LIST_BIND_STATIC = 'tracker_field_list_bind_static';
    public const TRACKER_FIELD_LIST_BIND_STATIC_VALUE = 'tracker_field_list_bind_static_value';
    public const TRACKER_FIELD_LIST_BIND_UGROUPS_VALUE = 'tracker_field_list_bind_ugroups_value';
    public const TRACKER_FIELD_LIST_BIND_USERS = 'tracker_field_list_bind_users';
    public const TRACKER_FIELD_MSB = 'tracker_field_msb';
    public const TRACKER_FIELD_OPENLIST = 'tracker_field_openlist';
    public const TRACKER_FIELD_OPENLIST_VALUE = 'tracker_field_openlist_value';
    public const TRACKER_FIELD_STRING = 'tracker_field_string';
    public const TRACKER_FIELD_TEXT = 'tracker_field_text';
    public const TRACKER_FILEINFO = 'tracker_fileinfo';
    public const TRACKER_FILEINFO_TEMPORARY = 'tracker_fileinfo_temporary';
    public const TRACKER_GLOBAL_NOTIFICATION = 'tracker_global_notification';
    public const TRACKER_GLOBAL_NOTIFICATION_UGROUPS = 'tracker_global_notification_ugroups';
    public const TRACKER_GLOBAL_NOTIFICATION_UNSUBSCRIBERS = 'tracker_global_notification_unsubscribers';
    public const TRACKER_GLOBAL_NOTIFICATION_USERS = 'tracker_global_notification_users';
    public const TRACKER_HIERARCHY = 'tracker_hierarchy';
    public const TRACKER_IDSHARING_ARTIFACT = 'tracker_idsharing_artifact';
    public const TRACKER_IDSHARING_TRACKER = 'tracker_idsharing_tracker';
    public const TRACKER_NOTIFICATION = 'tracker_notification';
    public const TRACKER_NOTIFICATION_EVENT = 'tracker_notification_event';
    public const TRACKER_NOTIFICATION_EVENT_DEFAULT = 'tracker_notification_event_default';
    public const TRACKER_NOTIFICATION_ROLE = 'tracker_notification_role';
    public const TRACKER_NOTIFICATION_ROLE_DEFAULT = 'tracker_notification_role_default';
    public const TRACKER_ONLY_STATUS_CHANGE_NOTIFICATION_SUBSCRIBERS = 'tracker_only_status_change_notification_subscribers';
    public const TRACKER_POST_CREATION_EVENT_LOG = 'tracker_post_creation_event_log';
    public const TRACKER_REMINDER = 'tracker_reminder';
    public const TRACKER_REMINDER_NOTIFIED_ROLES = 'tracker_reminder_notified_roles';
    public const TRACKER_REPORT = 'tracker_report';
    public const TRACKER_REPORT_CONFIG = 'tracker_report_config';
    public const TRACKER_REPORT_CRITERIA = 'tracker_report_criteria';
    public const TRACKER_REPORT_CRITERIA_ALPHANUM_VALUE = 'tracker_report_criteria_alphanum_value';
    public const TRACKER_REPORT_CRITERIA_COMMENT_VALUE = 'tracker_report_criteria_comment_value';
    public const TRACKER_REPORT_CRITERIA_DATE_VALUE = 'tracker_report_criteria_date_value';
    public const TRACKER_REPORT_CRITERIA_FILE_VALUE = 'tracker_report_criteria_file_value';
    public const TRACKER_REPORT_CRITERIA_LIST_VALUE = 'tracker_report_criteria_list_value';
    public const TRACKER_REPORT_CRITERIA_OPENLIST_VALUE = 'tracker_report_criteria_openlist_value';
    public const TRACKER_REPORT_CRITERIA_PERMISSIONSONARTIFACT_VALUE = 'tracker_report_criteria_permissionsonartifact_value';
    public const TRACKER_REPORT_RENDERER = 'tracker_report_renderer';
    public const TRACKER_REPORT_RENDERER_TABLE = 'tracker_report_renderer_table';
    public const TRACKER_REPORT_RENDERER_TABLE_COLUMNS = 'tracker_report_renderer_table_columns';
    public const TRACKER_REPORT_RENDERER_TABLE_FUNCTIONS_AGGREGATES = 'tracker_report_renderer_table_functions_aggregates';
    public const TRACKER_REPORT_RENDERER_TABLE_SORT = 'tracker_report_renderer_table_sort';
    public const TRACKER_RULE = 'tracker_rule';
    public const TRACKER_RULE_DATE = 'tracker_rule_date';
    public const TRACKER_RULE_LIST = 'tracker_rule_list';
    public const TRACKER_SEMANTIC_CONTRIBUTOR = 'tracker_semantic_contributor';
    public const TRACKER_SEMANTIC_DESCRIPTION = 'tracker_semantic_description';
    public const TRACKER_SEMANTIC_STATUS = 'tracker_semantic_status';
    public const TRACKER_SEMANTIC_TIMEFRAME = 'tracker_semantic_timeframe';
    public const TRACKER_SEMANTIC_TITLE = 'tracker_semantic_title';
    public const TRACKER_STATICFIELD_RICHTEXT = 'tracker_staticfield_richtext';
    public const TRACKER_TOOLTIP = 'tracker_tooltip';
    public const TRACKER_WATCHER = 'tracker_watcher';
    public const TRACKER_WIDGET_RENDERER = 'tracker_widget_renderer';
    public const TRACKER_WORKFLOW = 'tracker_workflow';
    public const TRACKER_WORKFLOW_TRANSITION = 'tracker_workflow_transition';
    public const TRACKER_WORKFLOW_TRANSITION_CONDITION_COMMENT_NOTEMPTY = 'tracker_workflow_transition_condition_comment_notempty';
    public const TRACKER_WORKFLOW_TRANSITION_CONDITION_FIELD_NOTEMPTY = 'tracker_workflow_transition_condition_field_notempty';
    public const TRACKER_WORKFLOW_TRANSITION_POSTACTIONS_CIBUILD = 'tracker_workflow_transition_postactions_cibuild';
    public const TRACKER_WORKFLOW_TRANSITION_POSTACTIONS_FIELD_DATE = 'tracker_workflow_transition_postactions_field_date';
    public const TRACKER_WORKFLOW_TRANSITION_POSTACTIONS_FIELD_FLOAT = 'tracker_workflow_transition_postactions_field_float';
    public const TRACKER_WORKFLOW_TRANSITION_POSTACTIONS_FIELD_INT = 'tracker_workflow_transition_postactions_field_int';
    public const TRACKER_WORKFLOW_TRIGGER_RULE_STATIC_VALUE = 'tracker_workflow_trigger_rule_static_value';
    public const TRACKER_WORKFLOW_TRIGGER_RULE_TRG_FIELD_STATIC_VALUE = 'tracker_workflow_trigger_rule_trg_field_static_value';
    public const TROVE_CAT = 'trove_cat';
    public const TROVE_GROUP_LINK = 'trove_group_link';
    public const TULEAP_INSTALLED_VERSION = 'tuleap_installed_version';
    public const UGROUP = 'ugroup';
    public const UGROUP_FORGE_PERMISSION = 'ugroup_forge_permission';
    public const UGROUP_MAPPING = 'ugroup_mapping';
    public const UGROUP_USER = 'ugroup_user';
    public const USER = 'user';
    public const USER_ACCESS = 'user_access';
    public const USER_ACCESS_KEY = 'user_access_key';
    public const USER_ACCESS_KEY_SCOPE = 'user_access_key_scope';
    public const USER_BOOKMARKS = 'user_bookmarks';
    public const USER_DASHBOARDS = 'user_dashboards';
    public const USER_GROUP = 'user_group';
    public const USER_LOST_PASSWORD = 'user_lost_password';
    public const USER_PREFERENCES = 'user_preferences';
    public const WIDGET_IMAGE = 'widget_image';
    public const WIDGET_NOTE = 'widget_note';
    public const WIDGET_RSS = 'widget_rss';
    public const WIKI_ATTACHMENT = 'wiki_attachment';
    public const WIKI_ATTACHMENT_DELETED = 'wiki_attachment_deleted';
    public const WIKI_ATTACHMENT_LOG = 'wiki_attachment_log';
    public const WIKI_ATTACHMENT_REVISION = 'wiki_attachment_revision';
    public const WIKI_GROUP_LIST = 'wiki_group_list';
    public const WIKI_LINK = 'wiki_link';
    public const WIKI_LOG = 'wiki_log';
    public const WIKI_NONEMPTY = 'wiki_nonempty';
    public const WIKI_PAGE = 'wiki_page';
    public const WIKI_RECENT = 'wiki_recent';
    public const WIKI_VERSION = 'wiki_version';
}
