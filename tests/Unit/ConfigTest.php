<?php

namespace Gets\QliroApi\Tests\Unit;

use Gets\QliroApi\Api\Config;
use Gets\QliroApi\Tests\QliroApiTestCase;
use InvalidArgumentException;

class ConfigTest extends QliroApiTestCase
{
    public function testConstructorWithDefaultMode(): void
    {
        $config = new Config('test_api_key', 'test_api_secret');

        $this->assertEquals('test_api_key', $config->apiKey);
        $this->assertEquals('test_api_secret', $config->apiSecret);
        $this->assertEquals('https://pago.qit.nu', $config->baseUrl);
    }

    public function testConstructorWithDevMode(): void
    {
        $config = new Config('test_api_key', 'test_api_secret', 'dev');

        $this->assertEquals('test_api_key', $config->apiKey);
        $this->assertEquals('test_api_secret', $config->apiSecret);
        $this->assertEquals('https://pago.qit.nu', $config->baseUrl);
    }

    public function testConstructorWithProdMode(): void
    {
        $config = new Config('test_api_key', 'test_api_secret', 'prod');

        $this->assertEquals('test_api_key', $config->apiKey);
        $this->assertEquals('test_api_secret', $config->apiSecret);
        $this->assertEquals('https://payments.qit.nu', $config->baseUrl);
    }

    public function testConstructorWithInvalidMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Config('test_api_key', 'test_api_secret', 'invalid_mode');
    }
}
