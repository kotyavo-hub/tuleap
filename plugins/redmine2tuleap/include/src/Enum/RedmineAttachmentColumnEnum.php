<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineAttachmentColumnEnum extends Enum
{
    public const ID = 'id';
    public const CONTAINER_ID = 'container_id';
    public const CONTAINER_TYPE = 'container_type';
    public const FILENAME = 'filename';
    public const DISK_FILENAME = 'disk_filename';
    public const FILESIZE = 'filesize';
    public const CONTENT_TYPE = 'content_type';
    public const DIGEST = 'digest';
    public const DOWNLOADS = 'downloads';
    public const AUTHOR_ID = 'author_id';
    public const CREATED_ON = 'created_on';
    public const DESCRIPTION = 'description';
    public const DISK_DIRECTORY = 'disk_directory';
}
