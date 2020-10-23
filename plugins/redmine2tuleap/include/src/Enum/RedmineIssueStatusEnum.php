<?php

namespace Maximaster\Redmine2TuleapPlugin\Enum;

use MyCLabs\Enum\Enum;

class RedmineIssueStatusEnum extends Enum
{
    public const NEW = 1;               // Новая
    public const WORKING = 2;           // В работе
    public const SOLVED = 3;            // Решена
    public const DISCUSSION = 4;        // В обсуждении
    public const CLOSED = 5;            // Закрыта
    public const REJECTED = 6;          // Отклонена
    public const DEPLOYING = 8;         // К переносу
    public const REVIEW = 9;            // Ревью
    public const TESTING = 10;          // Тестирование
}
