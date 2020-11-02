<?php

namespace Maximaster\Redmine2TuleapPlugin\Config;

use BaseLanguage;

class TransferConfig
{
    /** @var string */
    private $redmineDirectory;

    /**
     * Some object could refer deleted users, but we should link them to someone
     * @var int
     */
    private $defaultRedmineUserId;

    /**
     * Custom field ids which shouldn't be transfered into trackers
     *
     * @var int[]
     */
    private $excludedCustomFields;

    /**
     * Default locale for users
     *
     * @var string
     */
    private $language;

    public function __construct(
        string $redmineDirectory,
        int $defaultRedmineUserId,
        string $language = BaseLanguage::DEFAULT_LANG,
        array $excludedCustomFields = []
    ) {
        $this->redmineDirectory = $redmineDirectory;
        $this->defaultRedmineUserId = $defaultRedmineUserId;
        $this->language = $language;
        $this->excludedCustomFields = $excludedCustomFields;
    }

    public function redmineDirectory(): string
    {
        return $this->redmineDirectory;
    }

    public function defaultRedmineUserId(): int
    {
        return $this->defaultRedmineUserId;
    }

    /**
     * @return int[]
     */
    public function excludedCustomFields(): array
    {
        return $this->excludedCustomFields;
    }

    public function language(): string
    {
        return $this->language;
    }
}
