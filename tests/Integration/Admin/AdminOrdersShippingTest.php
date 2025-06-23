<?php /** @noinspection PhpParamsInspection */

namespace Gets\QliroApi\Tests\Integration\Admin;

use Gets\QliroApi\Api\QliroApi;
use Gets\QliroApi\Api\Requests\Admin\Orders\MarkItemsAsShippedRequest;
use Gets\QliroApi\Dtos\Order\MarkItemsAsShippedDto;
use Gets\QliroApi\Enums\PaymentTransactionStatus;
use Gets\QliroApi\Exceptions\InvalidItemException;
use Gets\QliroApi\Exceptions\OperationNotSupportedException;
use Gets\QliroApi\Exceptions\PaymentReferenceIsIncorrectException;
use Gets\QliroApi\Tests\QliroApiTestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class AdminOrdersShippingTest extends QliroApiTestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNoActualApiCalls();
        $config = $this->getApiConfig();
        $this->client = new QliroApi($config);
    }

    public function testMarkItemsAsShippedWorks(): void
    {
        $this->markTestSkipped();
        $testOrderId = 4948264;
        $orderDetails = $this->client->admin()->orders()->getOrder($testOrderId)->response->json();
        $paymentTransactionId = end($orderDetails['PaymentTransactions'])['PaymentTransactionId'];
        $orderItems = $orderDetails['OrderItemActions'];
        $shipments = array_map(function ($orderItem) {
            return [
                'MerchantReference'  => $orderItem['MerchantReference'],
                'Type'               => $orderItem['Type'],
                'Quantity'           => $orderItem['Quantity'],
                'PricePerItemIncVat' => $orderItem['PricePerItemIncVat'],
                'Description'        => $orderItem['Description'],
                'PricePerItemExVat'  => $orderItem['PricePerItemExVat'],
            ];
        }, $orderItems);
        $shipments = [end($shipments)];
        $data = [
            'OrderId'   => $testOrderId,
            'Currency'  => 'NOK',
            'Shipments' => [
                [
                    'PaymentTransactionId' => $paymentTransactionId,
                    'OrderItems'           => $shipments,
                ],
            ],
        ];
        $dto = MarkItemsAsShippedDto::fromStdClass($data);

        $this->client->admin()->orders()->markItemsAsShipped($dto);
    }

    public function testMarkItemsAsShippedWithError(): void
    {
        $this->client->withMockClient(new MockClient([
            MarkItemsAsShippedRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'OPERATION_NOT_SUPPORTED',
                'ErrorMessage'   => "Evaluation, Operation is not supported for this order. Items cannot be marked as shipped.",
                'ErrorReference' => 'a1b2c3d4-e5f6-7890-1234-567890abcdef',
            ], status: 400),
        ]));
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Shipments' => [],
        ];
        $this->expectException(OperationNotSupportedException::class);
        $dto = MarkItemsAsShippedDto::fromStdClass($data);
        $this->client->admin()->orders()->markItemsAsShipped($dto);
    }

    public function testMarkItemsAsShippedFailsWithInvalidItem(): void
    {
        $this->client->withMockClient(new MockClient([
            MarkItemsAsShippedRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'INVALID_ITEM',
                'ErrorMessage'   => "Evaluation, Could not find a matching item in the original order with MerchantReference 'test-addition-1' and PricePerItemIncludingVat 100.00 in the order 4948230",
                'ErrorReference' => '40347ec5-4ae8-4ec7-8d26-14b0cc59dcb1',
            ], status: 400),
        ]));
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Shipments' => [],
        ];
        $this->expectException(InvalidItemException::class);
        $dto = MarkItemsAsShippedDto::fromStdClass($data);
        $this->client->admin()->orders()->markItemsAsShipped($dto);
    }

    public function testMarkItemsAsShippedFailsForProcessedTransaction(): void
    {
        $this->client->withMockClient(new MockClient([
            MarkItemsAsShippedRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'PAYMENT_REFERENCE_IS_INCORRECT',
                'ErrorMessage'   => 'Payment references 3293033 does not connect to a success PreAuthrization or Debit',
                'ErrorReference' => 'a86357c2-d3a5-4120-823b-deee79b733ae',
            ], status: 400),
        ]));
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Shipments' => [],
        ];
        $this->expectException(PaymentReferenceIsIncorrectException::class);
        $dto = MarkItemsAsShippedDto::fromStdClass($data);
        $this->client->admin()->orders()->markItemsAsShipped($dto);
    }

    public function testMarkItemsAsShippedSuccess(): void
    {
        $this->client->withMockClient(new MockClient([
            MarkItemsAsShippedRequest::class => MockResponse::make(body: [
                'PaymentTransactions' => [
                    [
                        'PaymentTransactionId' => 3293033,
                        'Status'               => 'Created',
                    ],
                ],

            ], status: 200),
        ]));
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Shipments' => [],
        ];
        $dto = MarkItemsAsShippedDto::fromStdClass($data);
        $response = $this->client->admin()->orders()->markItemsAsShipped($dto)->response;
        $this->assertTrue(array_key_exists('PaymentTransactionId', $response->json()['PaymentTransactions'][0]));
        $this->assertTrue(array_key_exists('Status', $response->json()['PaymentTransactions'][0]));
        $this->assertEquals(PaymentTransactionStatus::Created->value, $response->json()['PaymentTransactions'][0]['Status']);
    }
}
