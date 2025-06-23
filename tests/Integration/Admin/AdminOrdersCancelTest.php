<?php /** @noinspection PhpParamsInspection */

namespace Gets\QliroApi\Tests\Integration\Admin;

use Gets\QliroApi\Api\QliroApi;
use Gets\QliroApi\Api\Requests\Admin\Orders\CancelOrderRequest;
use Gets\QliroApi\Enums\PaymentTransactionStatus;
use Gets\QliroApi\Exceptions\OperationNotSupportedException;
use Gets\QliroApi\Exceptions\OrderHasBeenCancelledException;
use Gets\QliroApi\Tests\Factories\CreateOrderDtoFactory;
use Gets\QliroApi\Tests\QliroApiTestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class AdminOrdersCancelTest extends QliroApiTestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNoActualApiCalls();
        $config = $this->getApiConfig();
        $this->client = new QliroApi($config);
    }

    public function testCancelOrderNotSupportedForNewOrder(): void
    {
        $orderDto = CreateOrderDtoFactory::create();
        $response = $this->client->merchant()->orders()->createOrder($orderDto->toArray());
        $this->assertTrue(array_key_exists('OrderId', $response->json()));
        $this->expectException(OperationNotSupportedException::class);
        $this->client->admin()->orders()->cancelOrder($response->json()['OrderId']);
    }

    public function testCancelOrderWorks(): void
    {
        $testOrderId = 4944662;
        $this->client->withMockClient(new MockClient([
            CancelOrderRequest::class => MockResponse::make(body: [
                'PaymentTransactions' => [
                    [
                        'PaymentTransactionId' => 1234,
                        'Status'               => 'Created',
                    ],

                ],
            ], status: 200),
        ]));

        $cancelResponse = $this->client->admin()->orders()->cancelOrder($testOrderId)->response;
        $this->assertTrue(array_key_exists('PaymentTransactionId', $cancelResponse->json()['PaymentTransactions'][0]));
        $this->assertTrue(array_key_exists('Status', $cancelResponse->json()['PaymentTransactions'][0]));
        $this->assertEquals(PaymentTransactionStatus::Created->value, $cancelResponse->json()['PaymentTransactions'][0]['Status']);
    }

    public function testCancelOrderFailsOnCancelledOrderWorks(): void
    {
        $testOrderId = 4944662;
        $this->client->withMockClient(new MockClient([
            CancelOrderRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'ORDER_HAS_BEEN_CANCELLED',
                'ErrorMessage'   => 'Evaluation, Order 4944662 has been cancelled',
                'ErrorReference' => 'b54213e5-8316-41d8-b524-c9c7646326c6',
            ], status: 400),
        ]));
        $this->expectException(OrderHasBeenCancelledException::class);
        $this->client->admin()->orders()->cancelOrder($testOrderId);
    }
}
