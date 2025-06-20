<?php

namespace Gets\QliroApi\Tests\Unit;

use Gets\QliroApi\Api\Config;
use Gets\QliroApi\Exceptions\QliroException;
use Gets\QliroApi\Api\QliroApi;
use Gets\QliroApi\Api\QliroConnector;
use Gets\QliroApi\Api\Requests\Merchant\Orders\CreateOrderRequest;
use Gets\QliroApi\Api\Requests\Merchant\Orders\GetOrderByMerchantReferenceRequest;
use Gets\QliroApi\Api\Requests\Merchant\Orders\GetOrderRequest;
use Gets\QliroApi\Api\Requests\Merchant\Orders\UpdateOrderRequest;
use Gets\QliroApi\Tests\QliroApiTestCase;
use ReflectionClass;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class QliroApiTest extends QliroApiTestCase
{
    private QliroApi $client;
    private Config $config;
    private MockClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock config
        $this->config = new Config('test_api_key', 'test_api_secret', 'dev');

        // Create a mock client
        $this->mockClient = new MockClient();

        // Create the QliroApi client
        $this->client = new QliroApi($this->config);

        // Get the connector from the client
        $reflection = new ReflectionClass($this->client);
        $connectorProperty = $reflection->getProperty('connector');
        $connectorProperty->setAccessible(true);
        $connector = $connectorProperty->getValue($this->client);

        // Set the mock client on the connector
        $connector->withMockClient($this->mockClient);
    }

    /**
     * Test the generateAuthToken method in QliroConnector
     */
    public function testGenerateAuthToken(): void
    {
        $connector = new QliroConnector($this->config);
        $reflection = new ReflectionClass($connector);
        $method = $reflection->getMethod('generateAuthToken');
        $method->setAccessible(true);

        // Test with empty data
        $token = $method->invoke($connector);
        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Test with data
        $data = ['key' => 'value'];
        $tokenWithData = $method->invoke($connector, $data);
        $this->assertIsString($tokenWithData);
        $this->assertNotEmpty($tokenWithData);

        // Tokens should be different
        $this->assertNotEquals($token, $tokenWithData);
    }

    /**
     * Test getting an order
     */
    public function testGetOrder(): void
    {
        // Mock a successful response for the GetOrderRequest
        $this->mockClient->addResponse(
            new MockResponse(['success' => true], 200),
            GetOrderRequest::class
        );

        // Call the orders resource to get an order
        $response = $this->client->merchant()->orders()->getOrder('test-order-id');

        // Assert the response
        $this->assertEquals(200, $response->response->status());
        $this->assertEquals(['success' => true], $response->response->json());

        // Assert that the request was sent
        $this->mockClient->assertSent(GetOrderRequest::class);
    }

    public function testGetOrderWithError(): void
    {
        $this->mockClient->addResponse(
            new MockResponse(['error' => 'Bad request'], 404),
            GetOrderRequest::class
        );

        $this->expectException(QliroException::class);
        $this->client->merchant()->orders()->getOrder('test-order-id');
    }

    public function testGetOrderByMerchantReference(): void
    {
        $this->mockClient->addResponse(
            new MockResponse(['success' => true], 200),
            GetOrderByMerchantReferenceRequest::class
        );

        $response = $this->client->merchant()->orders()->getOrderByMerchantReference('test-reference');

        $this->assertEquals(200, $response->response->status());
        $this->assertEquals(['success' => true], $response->response->json());

        $this->mockClient->assertSent(GetOrderByMerchantReferenceRequest::class);
    }

    public function testGetOrderByMerchantReferenceWithError(): void
    {
        $this->mockClient->addResponse(
            new MockResponse(['error' => 'Bad request'], 400),
            GetOrderByMerchantReferenceRequest::class
        );

        $this->expectException(QliroException::class);
        $this->client->merchant()->orders()->getOrderByMerchantReference('test-reference');
    }

    public function testCreateOrder(): void
    {
        $this->mockClient->addResponse(
            new MockResponse(['success' => true], 200),
            CreateOrderRequest::class
        );

        $response = $this->client->merchant()->orders()->createOrder(['data' => 'test']);

        $this->assertEquals(200, $response->status());
        $this->assertEquals(['success' => true], $response->json());

        $this->mockClient->assertSent(CreateOrderRequest::class);
    }

    public function testCreateOrderWithError(): void
    {
        $this->mockClient->addResponse(
            new MockResponse(['error' => 'Bad request'], 400),
            CreateOrderRequest::class
        );

        $this->expectException(QliroException::class);
        $this->client->merchant()->orders()->createOrder(['data' => 'test']);
    }

    public function testUpdateOrder(): void
    {
        $this->mockClient->addResponse(
            new MockResponse(['success' => true], 200),
            UpdateOrderRequest::class
        );

        $response = $this->client->merchant()->orders()->updateOrder('test-order-id', ['data' => 'test']);

        $this->assertEquals(200, $response->status());
        $this->assertEquals(['success' => true], $response->json());

        $this->mockClient->assertSent(UpdateOrderRequest::class);
    }

    public function testUpdateOrderWithError(): void
    {
        $this->mockClient->addResponse(
            new MockResponse(['error' => 'Bad request'], 400),
            UpdateOrderRequest::class
        );

        $this->expectException(QliroException::class);
        $this->client->merchant()->orders()->updateOrder('test-order-id', ['data' => 'test']);
    }
}
