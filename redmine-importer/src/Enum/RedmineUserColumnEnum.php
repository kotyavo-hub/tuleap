<?php

namespace Maximaster\RedmineTuleapImporter\Enum;

use MyCLabs\Enum\Enum;

class RedmineUserColumnEnum extends Enum
{
    public const ID = 'id';
    public const LOGIN = 'login';
    public const HASHED_PASSWORD = 'hashed_password';
    public const FIRSTNAME = 'firstname';
    public const LASTNAME = 'lastname';
    public const ADMIN = 'admin';
    public const STATUS = 'status';
    public const LAST_LOGIN_ON = 'last_login_on';
    public const LANGUAGE = 'language';
    public const AUTH_SOURCE_ID = 'auth_source_id';
    public const CREATED_ON = 'created_on';
    public const UPDATED_ON = 'updated_on';
    public const TYPE = 'type';
    public const IDENTITY_URL = 'identity_url';
    public const MAIL_NOTIFICATION = 'mail_notification';
    public const SALT = 'salt';
    public const MUST_CHANGE_PASSWD = 'must_change_passwd';
    public const PASSWD_CHANGED_ON = 'passwd_changed_on';
}
