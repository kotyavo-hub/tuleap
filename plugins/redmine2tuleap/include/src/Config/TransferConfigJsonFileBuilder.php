<?php

namespace Maximaster\Redmine2TuleapPlugin\Config;

use BaseLanguage;
use Exception;

class TransferConfigJsonFileBuilder
{
    public function buildFromFile(string $configFile): TransferConfig
    {
        if (!file_exists($configFile)) {
            throw new Exception(sprintf('Failed to load file "%s"', $configFile));
        }

        $config = json_decode(file_get_contents($configFile), true);
        return new TransferConfig(
            $config['redmineDirectory'],
            $config['defaultRedmineUserId'],
            $config['language'] ?: BaseLanguage::DEFAULT_LANG,
            $config['excludedCustomFields'] ?? []
        );
    }
}
