<?php /** @noinspection PhpParamsInspection */

namespace Gets\QliroApi\Tests\Integration;

use Gets\QliroApi\Api\QliroApi;
use Gets\QliroApi\Api\Requests\Admin\Orders\AddOrderItemsRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\CancelOrderRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\MarkItemsAsShippedRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\ReturnItemsRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\UpdateItemsRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\UpdateMerchantReferenceRequest;
use Gets\QliroApi\Enums\PaymentTransactionStatus;
use Gets\QliroApi\Exceptions\InvalidInputException;
use Gets\QliroApi\Exceptions\InvalidItemException;
use Gets\QliroApi\Exceptions\InvalidPaymentTypeException;
use Gets\QliroApi\Exceptions\InvalidRequestTotalAmountException;
use Gets\QliroApi\Exceptions\OperationNotSupportedException;
use Gets\QliroApi\Exceptions\OrderHasBeenCancelledException;
use Gets\QliroApi\Exceptions\PaymentReferenceIsIncorrectException;
use Gets\QliroApi\Models\Order\MarkItemsAsShippedDto;
use Gets\QliroApi\Tests\Factories\CreateOrderDtoFactory;
use Gets\QliroApi\Tests\QliroApiTestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;

class AdminApiTest extends QliroApiTestCase
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
        $this->assertTrue(array_key_exists('OrderId', $adminResponse->json()));
        $this->assertEquals($response->json()['OrderId'], $adminResponse->json()['OrderId']);
        $this->assertTrue(array_key_exists('PaymentTransactions', $adminResponse->json()));
    }

    public function testGetOrderByReferenceWorks(): void
    {
        $orderDto = CreateOrderDtoFactory::create();
        $response = $this->client->merchant()->orders()->createOrder($orderDto->toArray());
        $this->assertTrue(array_key_exists('OrderId', $response->json()));

        $adminResponse = $this->client->admin()->orders()->getOrderByReference($orderDto->MerchantReference);
        $this->assertTrue(array_key_exists('OrderId', $adminResponse->json()));
        $this->assertEquals($response->json()['OrderId'], $adminResponse->json()['OrderId']);
        $this->assertEquals($orderDto->MerchantReference, $adminResponse->json()['MerchantReference']);
        $this->assertTrue(array_key_exists('PaymentTransactions', $adminResponse->json()));
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
                'PaymentTransactionId' => 1234,
                'Status'               => 'Created',
            ], status: 200),
        ]));

        $cancelResponse = $this->client->admin()->orders()->cancelOrder($testOrderId);
        $this->assertTrue(array_key_exists('PaymentTransactionId', $cancelResponse->json()));
        $this->assertTrue(array_key_exists('Status', $cancelResponse->json()));
        $this->assertEquals(PaymentTransactionStatus::Created->value, $cancelResponse->json()['Status']);
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


    public function testAddOrderItemsWorks(): void
    {
        $this->markTestSkipped();
        $testOrderId = 4948230;
        $orderDetails = $this->client->admin()->orders()->getOrder($testOrderId)->json();
        $paymentTransactionId = end($orderDetails['PaymentTransactions'])['PaymentTransactionId'];
        $data = [
            'OrderId'   => $testOrderId,
            'Currency'  => 'NOK',
            'Additions' => [
                [
                    'PaymentTransactionId' => $paymentTransactionId,
                    'OrderItems'           => [
                        [
                            'MerchantReference'  => 'test-addition-1',
                            'Type'               => 'Product',
                            'Quantity'           => 1,
                            'PricePerItemIncVat' => 100,
                            'Description'        => 'Test Addition product',
                            'PricePerItemExVat'  => 80,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->client->admin()->orders()->addOrderItems($data);
        $test = 1;
    }

    public function testAddOrderWithWrongTransactionWorks(): void
    {
        $this->client->withMockClient(new MockClient([
            AddOrderItemsRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'OPERATION_NOT_SUPPORTED',
                'ErrorMessage'   => 'Evaluation, Operation is not supported for this order PaymentTransactions belong to different Orders.',
                'ErrorReference' => '592238f6-c4b2-488d-94e5-07966df0c304',
            ], status: 400),
        ]));
        $data = [];
        $this->expectException(OperationNotSupportedException::class);
        $this->client->admin()->orders()->addOrderItems($data);
    }

    public function testAddOrderWithInvalidPaymentType(): void
    {
        $this->client->withMockClient(new MockClient([
            AddOrderItemsRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'INVALID_PAYMENT_TYPE',
                'ErrorMessage'   => "Evaluation, Type of transaction '3292652' of order '4927354' is invalid. Expected type 'Capture' but actual type 'Preauthorization'",
                'ErrorReference' => '60a31db1-3506-493a-9634-8eb3fb12a537',
            ], status: 400),
        ]));
        $data = [];
        $this->expectException(InvalidPaymentTypeException::class);
        $this->client->admin()->orders()->addOrderItems($data);
    }

    public function testAddOrderWithUnsupportedPaymentMethod(): void
    {
        $this->client->withMockClient(new MockClient([
            AddOrderItemsRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'OPERATION_NOT_SUPPORTED',
                'ErrorMessage'   => "Evaluation, Operation is not supported for the order '4948217' Adding items that increase the original reserved amount are not allowed for this payment method.",
                'ErrorReference' => '9cb2faa5-59b2-4c06-9fb7-07677a39907a',
            ], status: 400),
        ]));
        $data = [];
        $this->expectException(OperationNotSupportedException::class);
        $this->client->admin()->orders()->addOrderItems($data);
    }

    public function testMarkItemsAsShippedWorks(): void
    {
        $this->markTestSkipped();
        $testOrderId = 4948264;
        $orderDetails = $this->client->admin()->orders()->getOrder($testOrderId)->json();
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


        $response = $this->client->admin()->orders()->markItemsAsShipped($dto);
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
            'Shipments' => []
        ];
        $this->expectException(OperationNotSupportedException::class);
        $dto=MarkItemsAsShippedDto::fromStdClass($data);
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
            'Shipments' => []
        ];
        $this->expectException(InvalidItemException::class);
        $dto=MarkItemsAsShippedDto::fromStdClass($data);
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
            'Shipments' => []
        ];
        $this->expectException(PaymentReferenceIsIncorrectException::class);
        $dto=MarkItemsAsShippedDto::fromStdClass($data);
        $this->client->admin()->orders()->markItemsAsShipped($dto);
    }

    public function testMarkItemsAsShippedSuccess(): void
    {
        $this->client->withMockClient(new MockClient([
            MarkItemsAsShippedRequest::class => MockResponse::make(body: [
                'PaymentTransactionId' => 3293033,
                'Status'               => 'Created',
            ], status: 200),
        ]));
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Shipments' => []
        ];
        $dto=MarkItemsAsShippedDto::fromStdClass($data);
        $response = $this->client->admin()->orders()->markItemsAsShipped($dto);
        $this->assertTrue(array_key_exists('PaymentTransactionId', $response->json()));
        $this->assertTrue(array_key_exists('Status', $response->json()));
        $this->assertEquals(PaymentTransactionStatus::Created->value, $response->json()['Status']);
    }

    public function testUpdateItemsWorks(): void
    {
        $this->markTestSkipped();
        $testOrderId = 4948837;
        $orderDetails = $this->client->admin()->orders()->getOrder($testOrderId)->json();
        $paymentTransactionId = end($orderDetails['PaymentTransactions'])['PaymentTransactionId'];
        $orderItems = $orderDetails['OrderItemActions'];

        $data = [
            'OrderId'   => $testOrderId,
            'Currency'  => 'NOK',
            'Additions' => [
                [
                    'PaymentTransactionId' => $paymentTransactionId,
                    'OrderItems'           => $addItems,
                ],
            ],
        ];
        $response = $this->client->admin()->orders()->addOrderItems($data);
        $test = 1;

        $updatedItems = [

            [
                'MerchantReference'  => '7057320926803',
                'Type'               => 'Shipping',
                'Quantity'           => 1,
                'PricePerItemIncVat' => 99,
                'Description'        => 'Fraktkostnad' . ' (Updated)',
                'PricePerItemExVat'  => 90,
            ],
            [
                'MerchantReference'  => '7057320926803',
                'Type'               => 'Shipping',
                'Quantity'           => 1,
                'PricePerItemIncVat' => 400,
                'Description'        => 'Fraktkostnad' . ' (Updated)',
                'PricePerItemExVat'  => 300,
            ],
        ];
        $updatedItems = [end($updatedItems)];
        $data = [
            'OrderId'  => $testOrderId,
            'Currency' => 'NOK',
            'Updates'  => [
                [
                    'PaymentTransactionId' => $paymentTransactionId,
                    'OrderItems'           => $updatedItems,
                ],
            ],
        ];

        $response = $this->client->admin()->orders()->updateItems($data);
        $test = 1;
    }

    public function testUpdateItemsWithError(): void
    {
        $this->client->withMockClient(new MockClient([
            UpdateItemsRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'OPERATION_NOT_SUPPORTED',
                'ErrorMessage'   => "Evaluation, Operation is not supported for this order. Items cannot be updated.",
                'ErrorReference' => 'a1b2c3d4-e5f6-7890-1234-567890abcdef',
            ], status: 400),
        ]));
        $data = [];
        $this->expectException(OperationNotSupportedException::class);
        $this->client->admin()->orders()->updateItems($data);
    }

    public function testUpdateItemsFailsWithInvalidItem(): void
    {
        $this->client->withMockClient(new MockClient([
            UpdateItemsRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'INVALID_ITEM',
                'ErrorMessage'   => "Evaluation, Could not find a matching item in the original order with MerchantReference 'test-update-1' and PricePerItemIncludingVat 100.00 in the order 4948230",
                'ErrorReference' => '40347ec5-4ae8-4ec7-8d26-14b0cc59dcb1',
            ], status: 400),
        ]));
        $data = [];
        $this->expectException(InvalidItemException::class);
        $this->client->admin()->orders()->updateItems($data);
    }

    public function testUpdateItemsFailsForProcessedTransaction(): void
    {
        $this->client->withMockClient(new MockClient([
            UpdateItemsRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'PAYMENT_REFERENCE_IS_INCORRECT',
                'ErrorMessage'   => 'Payment references 3293033 does not connect to a success PreAuthrization or Debit',
                'ErrorReference' => 'a86357c2-d3a5-4120-823b-deee79b733ae',
            ], status: 400),
        ]));
        $data = [];
        $this->expectException(PaymentReferenceIsIncorrectException::class);
        $this->client->admin()->orders()->updateItems($data);
    }

    public function testUpdateItemsSuccess(): void
    {
        $this->client->withMockClient(new MockClient([
            UpdateItemsRequest::class => MockResponse::make(body: [
                'PaymentTransactionId' => 3293033,
                'Status'               => 'Created',
            ], status: 200),
        ]));
        $data = [];
        $response = $this->client->admin()->orders()->updateItems($data);
        $this->assertTrue(array_key_exists('PaymentTransactionId', $response->json()));
        $this->assertTrue(array_key_exists('Status', $response->json()));
        $this->assertEquals(PaymentTransactionStatus::Created->value, $response->json()['Status']);
    }

    public function testReturnItemsWithError(): void
    {
        $this->client->withMockClient(new MockClient([
            ReturnItemsRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'INVALID_INPUT',
                'ErrorMessage'   => "Property: Returns[0].OrderItems.2.OrderItems, Message: Cannot deserialize the current JSON object",
                'ErrorReference' => 'a1b2c3d4-e5f6-7890-1234-567890abcdef',
            ], status: 400),
        ]));
        $data = [];
        $this->expectException(InvalidInputException::class);
        $this->client->admin()->orders()->returnItems($data);
    }

    public function testReturnItemsFailsWithInvalidItem(): void
    {
        $this->client->withMockClient(new MockClient([
            ReturnItemsRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'INVALID_ITEM',
                'ErrorMessage'   => "Evaluation, Could not find a matching item in the original shipment with MerchantReference 'random' and PricePerItemIncludingVat 4995.00 in the shipment 3293375",
                'ErrorReference' => '40347ec5-4ae8-4ec7-8d26-14b0cc59dcb1',
            ], status: 400),
        ]));
        $data = [];
        $this->expectException(InvalidItemException::class);
        $this->client->admin()->orders()->returnItems($data);
    }

    public function testReturnItemsFailsForProcessedTransaction(): void
    {
        $this->client->withMockClient(new MockClient([
            ReturnItemsRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'INVALID_PAYMENT_TYPE',
                'ErrorMessage'   => "Evaluation, Type of transaction '3293369' of order '4948945' is invalid. Expected type 'Capture' but actual type 'Refund'",
                'ErrorReference' => '29b5f185-b3a3-44fa-a20b-1783f21cec9a',
            ], status: 400),
        ]));
        $data = [];
        $this->expectException(InvalidPaymentTypeException::class);
        $this->client->admin()->orders()->returnItems($data);
    }

    public function testReturnItemsFailsForAmount(): void
    {
        $this->client->withMockClient(new MockClient([
            ReturnItemsRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'INVALID_REQUEST_TOTAL_AMOUNT',
                'ErrorMessage'   => "Item prices in the request should add up to a positive total amount in order to proceed. Actual Request Total '-99999989511'",
                'ErrorReference' => '51c22044-2025-4947-92ce-4eebd7bda5ee',
            ], status: 400),
        ]));
        $data = [];
        $this->expectException(InvalidRequestTotalAmountException::class);
        $this->client->admin()->orders()->returnItems($data);
    }

    public function testReturnItemsSuccess(): void
    {
        $testOrderId = 4949115;
        $orderDetails = $this->client->admin()->orders()->getOrder($testOrderId)->json();
        $paymentTransactionId = end($orderDetails['PaymentTransactions'])['PaymentTransactionId'];
        $orderItems = array_values(
            array_filter($orderDetails['OrderItemActions'], function ($item) use ($paymentTransactionId) {
                return isset($item['PaymentTransactionId']) && $item['PaymentTransactionId'] === $paymentTransactionId;
            }));
        $orderItems = array_map(function ($orderItem) {
            return [
                'MerchantReference'  => $orderItem['MerchantReference'],
                'Type'               => $orderItem['Type'],
                'Quantity'           => $orderItem['Quantity'],
                'PricePerItemIncVat' => $orderItem['PricePerItemIncVat']-100,
            ];
        }, $orderItems);
        $data = [
            'OrderId'  => $testOrderId,
            'Currency' => 'NOK',
            'Returns'  => [
                [
                    'PaymentTransactionId' => $paymentTransactionId,
                    'OrderItems'           => $orderItems,
//                    'Fees'                 => [
//                        [
//                            'MerchantReference'  => 'fee100',
//                            'Description'        => 'test fee',
//                            'PricePerItemIncVat' => 100,
//                            'PricePerItemExVat'  => 80,
//                        ],
//                    ],
                    'Discounts'                 => [
                        [
                            'MerchantReference'  => 'discount200',
                            'Description'        => 'test discount',
                            'PricePerItemIncVat' => -200,
                            'PricePerItemExVat'  => -160,
                        ],
                    ],
                ],
            ],
        ];

        $this->client->withMockClient(new MockClient([
            ReturnItemsRequest::class => MockResponse::make(body: [
                'PaymentTransactionId' => 3293033,
                'Status' => 'Created',
            ], status: 200),
        ]));
        $response = $this->client->admin()->orders()->returnItems($data);
        $this->assertTrue(array_key_exists('PaymentTransactionId', $response->json()));
        $this->assertTrue(array_key_exists('Status', $response->json()));
        $this->assertEquals(PaymentTransactionStatus::Created->value, $response->json()['Status']);

        $test = 1;
    }

    public function testUpdateMerchantReferenceFailsWithInvalidItem(): void
    {
        $this->client->withMockClient(new MockClient([
            UpdateMerchantReferenceRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'INVALID_INPUT',
                'ErrorMessage'   => "Property: NewMerchantReference, Message: The NewMerchantReference field is required.",
                'ErrorReference' => '0b8e1d5a-ef73-47db-96e7-397507063d72',
            ], status: 400),
        ]));
        $data = [];
        $this->expectException(InvalidInputException::class);
        $this->client->admin()->orders()->updateMerchantReference(1234, '');

    }

    public function testUpdateMerchantReferenceSuccess(): void
    {
        $testOrderId = 4948837;
        $newReference = '12344';
        $this->client->withMockClient(new MockClient([
            UpdateMerchantReferenceRequest::class => MockResponse::make(body: [
                'discriminator' => 'UpdateMerchantReference',
            ], status: 200),
        ]));
        $response = $this->client->admin()->orders()->updateMerchantReference($testOrderId, $newReference);
        $this->assertEquals(200, $response->status());
    }
}
