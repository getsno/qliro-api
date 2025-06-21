<?php

namespace Gets\QliroApi\Tests\Integration;

use Gets\QliroApi\Api\Config;
use Gets\QliroApi\Api\QliroApi;
use Gets\QliroApi\Exceptions\AuthorizationException;
use Gets\QliroApi\Exceptions\OrderNotFoundExceprion;
use Gets\QliroApi\Dtos\Order\CreateOrderDto;
use Gets\QliroApi\Tests\Factories\CreateOrderDtoFactory;
use Gets\QliroApi\Tests\Factories\OrderItemDtoFactory;
use Gets\QliroApi\Tests\QliroApiTestCase;

class MerchantApiTest extends QliroApiTestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNoActualApiCalls();
        $config = $this->getApiConfig();
        $this->client = new QliroApi($config);
    }

    public function testAuthExceptionHandledCorrectly(): void
    {
        $config = new Config('broken', 'credentials', 'dev');
        $client = new QliroApi($config);
        $this->expectException(AuthorizationException::class);

        $orderDto = new CreateOrderDto();
        $client->merchant()->orders()->createOrder($orderDto->toArray());
    }

    public function testCreateOrderWorks(): void
    {
        // Create order DTO using factory
        $orderDto = CreateOrderDtoFactory::create();

        $response = $this->client->merchant()->orders()->createOrder($orderDto->toArray());
        $this->assertTrue(array_key_exists('OrderId', $response->json()));
    }

    public function testCreateOrderWithCustomItemsWorks(): void
    {
        $config = $this->getApiConfig();
        $client = new QliroApi($config);

        $orderItem1 = OrderItemDtoFactory::create([
            'Description'        => 'Custom Product 1',
            'PricePerItemIncVat' => 150.0,
            'PricePerItemExVat'  => 120.0,
        ]);

        $orderItem2 = OrderItemDtoFactory::create([
            'Description'        => 'Custom Product 2',
            'Quantity'           => 2,
            'PricePerItemIncVat' => 200.0,
            'PricePerItemExVat'  => 160.0,
        ]);

        // Create order DTO with custom items
        $orderDto = CreateOrderDtoFactory::create(
            ['Currency' => 'NOK', 'Country' => 'NO'],
            [$orderItem1, $orderItem2]
        );

        $response = $client->merchant()->orders()->createOrder($orderDto->toArray());
        $this->assertTrue(array_key_exists('OrderId', $response->json()));

    }

    public function testGetOrder(): void
    {

        $orderDto = CreateOrderDtoFactory::create();
        $response = $this->client->merchant()->orders()->createOrder($orderDto->toArray());
        $orderId = $response->json('OrderId');
        $order = $this->client->merchant()->orders()->getOrder($orderId)->toArray();
        $this->assertTrue(array_key_exists('OrderHtmlSnippet', $order));
    }

    public function testGetNotExistingOrder(): void
    {

        $orderId = 123124312;
        $this->expectException(OrderNotFoundExceprion::class);
        $this->client->merchant()->orders()->getOrder($orderId)->toArray();
    }

    public function testUpdateOrder(): void
    {
        // Create an order first
        $orderDto = CreateOrderDtoFactory::create();
        $response = $this->client->merchant()->orders()->createOrder($orderDto->toArray())->json();
        $this->assertTrue(array_key_exists('OrderId', $response));

        $orderId = $response['OrderId'];
        // Create update data
        $updateData = [
            'OrderItems' => [
                [
                    'MerchantReference'  => 'updated-item-123',
                    'Description'        => 'Updated Test Item',
                    'Quantity'           => 1,
                    'PricePerItemIncVat' => 150.0,
                    'PricePerItemExVat'  => 120.0,
                ],
            ],
        ];
        $this->client->merchant()->orders()->updateOrder($orderId, $updateData);
        $retrievedOrder = $this->client->merchant()->orders()->getOrder($orderId)->toArray();
        $this->assertTrue(array_key_exists('OrderItems', $retrievedOrder));

        $orderItems = $retrievedOrder['OrderItems'];

        $this->assertNotEmpty($orderItems);
        $this->assertEquals('updated-item-123', $orderItems[0]['MerchantReference']);
        $this->assertEquals('Updated Test Item', $orderItems[0]['Description']);
        $this->assertEquals(1, $orderItems[0]['Quantity']);
        $this->assertEquals(150.0, $orderItems[0]['PricePerItemIncVat']);
        $this->assertEquals(120.0, $orderItems[0]['PricePerItemExVat']);
        $this->assertEquals(150.0, $retrievedOrder['TotalPrice']);

    }
}
