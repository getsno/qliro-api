<?php

namespace Gets\QliroApi\Tests;

use Gets\QliroApi\Api\QliroApi;
use PHPUnit\Framework\TestCase;

class QliroApiTestCase extends TestCase
{
    protected function skipIfNoActualApiCalls(string $message = 'Skipping test that makes actual API calls.'): void
    {
        if (getenv('SKIP_ACTUAL_API_CALLS') === 'true') {
            $this->markTestSkipped($message);
        }
    }

    protected function getApiConfig(string $mode = 'dev'): \Gets\QliroApi\Api\Config
    {
        return TestConfig::getConfig($mode);
    }

}
