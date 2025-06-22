<?php /** @noinspection PhpParamsInspection */

namespace Gets\QliroApi\Tests\Integration\Admin;

use Gets\QliroApi\Api\QliroApi;
use Gets\QliroApi\Tests\Factories\CreateOrderDtoFactory;
use Gets\QliroApi\Tests\QliroApiTestCase;

class AdminOrdersGetTest extends QliroApiTestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNoActualApiCalls();
        $config = $this->getApiConfig();
        $this->client = new QliroApi($config);
    }

    public function testGetOrderWorks(): void
    {
        $orderDto = CreateOrderDtoFactory::create();
        $response = $this->client->merchant()->orders()->createOrder($orderDto->toArray());
        $this->assertTrue(array_key_exists('OrderId', $response->json()));

        $adminResponse = $this->client->admin()->orders()->getOrder($response->json()['OrderId']);
        $this->assertTrue(array_key_exists('OrderId', $adminResponse->response->json()));
        $this->assertEquals($response->json()['OrderId'], $adminResponse->response->json()['OrderId']);
        $this->assertTrue(array_key_exists('PaymentTransactions', $adminResponse->response->json()));
    }

    public function testGetOrderByReferenceWorks(): void
    {
        $orderDto = CreateOrderDtoFactory::create();
        $response = $this->client->merchant()->orders()->createOrder($orderDto->toArray());
        $this->assertTrue(array_key_exists('OrderId', $response->json()));

        $adminResponse = $this->client->admin()->orders()->getOrderByMerchantReference($orderDto->MerchantReference);
        $this->assertTrue(array_key_exists('OrderId', $adminResponse->response->json()));
        $this->assertEquals($response->json()['OrderId'], $adminResponse->response->json()['OrderId']);
        $this->assertEquals($orderDto->MerchantReference, $adminResponse->response->json()['MerchantReference']);
        $this->assertTrue(array_key_exists('PaymentTransactions', $adminResponse->response->json()));
    }
}
