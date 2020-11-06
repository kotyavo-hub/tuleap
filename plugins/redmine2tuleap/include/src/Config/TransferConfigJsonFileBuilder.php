<?php

namespace Maximaster\Redmine2TuleapPlugin\Config;

use BaseLanguage;
use Exception;
use function FunctionalPHP\NamedParameters\construct;

class TransferConfigJsonFileBuilder
{
    public function buildFromFile(string $configFile): TransferConfig
    {
        if (!file_exists($configFile)) {
            throw new Exception(sprintf('Failed to load file "%s"', $configFile));
        }

        return construct(TransferConfig::class, json_decode(file_get_contents($configFile), true));
    }
}
