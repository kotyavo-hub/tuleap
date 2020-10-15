<?php

namespace Maximaster\RedmineTuleapImporter\Enum;

use MyCLabs\Enum\Enum;

class TuleapUserColumnEnum extends Enum
{
    public const USER_ID = 'user_id';
    public const USER_NAME = 'user_name';
    public const EMAIL = 'email';
    public const USER_PW = 'user_pw';
    public const PASSWORD = 'password';
    public const REALNAME = 'realname';
    public const REGISTER_PURPOSE = 'register_purpose';
    public const STATUS = 'status';
    public const SHELL = 'shell';
    public const UNIX_PW = 'unix_pw';
    public const UNIX_STATUS = 'unix_status';
    public const UNIX_UID = 'unix_uid';
    public const UNIX_BOX = 'unix_box';
    public const LDAP_ID = 'ldap_id';
    public const ADD_DATE = 'add_date';
    public const APPROVED_BY = 'approved_by';
    public const CONFIRM_HASH = 'confirm_hash';
    public const MAIL_SITEUPDATES = 'mail_siteupdates';
    public const MAIL_VA = 'mail_va';
    public const STICKY_LOGIN = 'sticky_login';
    public const AUTHORIZED_KEYS = 'authorized_keys';
    public const EMAIL_NEW = 'email_new';
    public const TIMEZONE = 'timezone';
    public const LANGUAGE_ID = 'language_id';
    public const LAST_PWD_UPDATE = 'last_pwd_update';
    public const EXPIRY_DATE = 'expiry_date';
    public const HAS_CUSTOM_AVATAR = 'has_custom_avatar';

    // custom
    public const REDMINE_ID = 'redmine_id';
}
