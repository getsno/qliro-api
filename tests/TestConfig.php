<?php

namespace Gets\QliroApi\Tests;

use Gets\QliroApi\Api\Config;

class TestConfig
{
    public static function getConfig(string $mode = 'dev'): Config
    {
        // First try to get credentials from environment variables
        $apiKey = getenv('QLIRO_API_KEY');
        $apiSecret = getenv('QLIRO_API_SECRET');

        // If environment variables are not set, try to load from config file
        if (!$apiKey || !$apiSecret) {
            $configFile = self::getConfigFilePath();
            if (file_exists($configFile)) {
                $config = require $configFile;
                $apiKey = $config['api_key'] ?? '';
                $apiSecret = $config['api_secret'] ?? '';
            }
        }

        // If credentials are still not set, use default test values
        if (!$apiKey || !$apiSecret) {
            $apiKey = 'test_api_key';
            $apiSecret = 'test_api_secret';
        }

        return new Config($apiKey, $apiSecret, $mode);
    }
    private static function getConfigFilePath(): string
    {
        return __DIR__ . '/config.php';
    }
}
