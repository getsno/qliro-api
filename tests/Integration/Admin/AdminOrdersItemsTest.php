<?php /** @noinspection PhpParamsInspection */

namespace Gets\QliroApi\Tests\Integration\Admin;

use Gets\QliroApi\Api\QliroApi;
use Gets\QliroApi\Api\Requests\Admin\Orders\AddOrderItemsRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\ReturnItemsRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\UpdateItemsRequest;
use Gets\QliroApi\Dtos\Order\ReturnItemsDto;
use Gets\QliroApi\Dtos\Order\UpdateItemsDto;
use Gets\QliroApi\Enums\PaymentTransactionStatus;
use Gets\QliroApi\Exceptions\InvalidInputException;
use Gets\QliroApi\Exceptions\InvalidItemException;
use Gets\QliroApi\Exceptions\InvalidPaymentTypeException;
use Gets\QliroApi\Exceptions\InvalidRequestTotalAmountException;
use Gets\QliroApi\Exceptions\OperationNotSupportedException;
use Gets\QliroApi\Exceptions\PaymentReferenceIsIncorrectException;
use Gets\QliroApi\Models\Order;
use Gets\QliroApi\Models\OrderCaptures;
use Gets\QliroApi\Models\OrderChanges;
use Gets\QliroApi\Models\OrderReturns;
use Gets\QliroApi\Tests\QliroApiTestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class AdminOrdersItemsTest extends QliroApiTestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNoActualApiCalls();
        $config = $this->getApiConfig();
        $this->client = new QliroApi($config);
    }

    // Add Order Items Tests
    public function testAddOrderItems(): void
    {
        $this->markTestSkipped();
        $testOrderId = 4948230;
        $orderDetails = $this->client->admin()->orders()->getOrder($testOrderId)->response->json();
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
    }

    public function testAddOrderWithWrongTransaction(): void
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

    // Update Items Tests

    public function testUpdateItemsProcess():void {
        $orderRef='XX3FT9KB';
        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
        $returns = new OrderReturns();
        $returns
            ->add('7057320717180',75,2)
            ->add('7057321129814',75,2);
        $returnDto = $order->getReturnDto($returns);
//        $res = $this->client->admin()->orders()->returnItems($returnDto)->json();
//
//        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
//        $retryTransactionIds=[];
//        foreach ($res['PaymentTransactions'] as $paymentTransaction) {
//            $paymentTransactionId = $paymentTransaction['PaymentTransactionId'];
//            if($order->getTransactionStatus($paymentTransactionId) !== PaymentTransactionStatus::Success->value) {
//                $retryDto = $order->getReturnDto($order->getChangesBasedOnTransaction($paymentTransactionId));
//                $resSecond = $this->client->admin()->orders()->returnItems($retryDto)->json();
//                $retryTransactionIds = array_values(array_merge($retryTransactionIds, $resSecond['PaymentTransactions']));
//            }
//        }
//        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
//        foreach ($retryTransactionIds as $paymentTransaction) {
//            $paymentTransactionId = $paymentTransaction['PaymentTransactionId'];
//            if($order->getTransactionStatus($paymentTransactionId) !== PaymentTransactionStatus::Success->value) {
//                $retryDto = $order->getReturnDto($order->getChangesBasedOnTransaction($paymentTransactionId));
//                $resSecond = $this->client->admin()->orders()->returnItems($retryDto)->json();
//                $retryTransactionIds = array_values(array_merge($retryTransactionIds, $resSecond['PaymentTransactions']));
//            }
//        }
//        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
//        $retryService = new TransactionRetryService($this->client);
//        $results = $retryService->processFailedTransactions($orderRef, $res['PaymentTransactions']);

        $test=1;
    }

    public function testCancelTransRetry() :void {
        $this->markTestSkipped();
        $paymentTransactionId = 3294678;
        $orderRef='XX3FT9KB';
        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
        $returns = $order->getChangesBasedOnTransaction($paymentTransactionId);
        $returnDto = $order->getReturnDto($returns);
        $res = $this->client->admin()->orders()->returnItems($returnDto)->json();
        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;

    }

    public function testUpdateItemsWorks(): void
    {
        $this->markTestSkipped();
        $testOrderId = 4948837;
        $orderDetails = $this->client->admin()->orders()->getOrder($testOrderId)->response->json();
        $paymentTransactionId = end($orderDetails['PaymentTransactions'])['PaymentTransactionId'];

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

        $response = $this->client->admin()->orders()->updateItems(UpdateItemsDto::fromStdClass($data));
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
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Updates' => []
        ];
        $this->expectException(OperationNotSupportedException::class);
        $this->client->admin()->orders()->updateItems(UpdateItemsDto::fromStdClass($data));
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
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Updates' => []
        ];
        $this->expectException(InvalidItemException::class);
        $this->client->admin()->orders()->updateItems(UpdateItemsDto::fromStdClass($data));
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
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Updates' => []
        ];
        $this->expectException(PaymentReferenceIsIncorrectException::class);
        $this->client->admin()->orders()->updateItems(UpdateItemsDto::fromStdClass($data));
    }

    public function testUpdateItemsSuccess(): void
    {
        $this->client->withMockClient(new MockClient([
            UpdateItemsRequest::class => MockResponse::make(body: [
                'PaymentTransactionId' => 3293033,
                'Status'               => 'Created',
            ], status: 200),
        ]));
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Updates' => []
        ];
        $response = $this->client->admin()->orders()->updateItems(UpdateItemsDto::fromStdClass($data));
        $this->assertTrue(array_key_exists('PaymentTransactionId', $response->json()));
        $this->assertTrue(array_key_exists('Status', $response->json()));
        $this->assertEquals(PaymentTransactionStatus::Created->value, $response->json()['Status']);
    }

    // Return Items Tests
    public function testReturnItemsWithError(): void
    {
        $this->client->withMockClient(new MockClient([
            ReturnItemsRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'INVALID_INPUT',
                'ErrorMessage'   => "Property: Returns[0].OrderItems.2.OrderItems, Message: Cannot deserialize the current JSON object",
                'ErrorReference' => 'a1b2c3d4-e5f6-7890-1234-567890abcdef',
            ], status: 400),
        ]));
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Returns' => []
        ];
        $this->expectException(InvalidInputException::class);
        $this->client->admin()->orders()->returnItems(ReturnItemsDto::fromStdClass($data));
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
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Returns' => []
        ];
        $this->expectException(InvalidItemException::class);
        $this->client->admin()->orders()->returnItems(ReturnItemsDto::fromStdClass($data));
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
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Returns' => []
        ];
        $this->expectException(InvalidPaymentTypeException::class);
        $this->client->admin()->orders()->returnItems(ReturnItemsDto::fromStdClass($data));
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
        $data = [
            'OrderId'   => 123,
            'Currency'  => 'NOK',
            'Returns' => []
        ];
        $this->expectException(InvalidRequestTotalAmountException::class);
        $this->client->admin()->orders()->returnItems(ReturnItemsDto::fromStdClass($data));
    }

    public function testReturnItemsSuccess(): void
    {
        $testOrderId = 4949115;
        $orderDetails = $this->client->admin()->orders()->getOrder($testOrderId)->response->json();
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
                    'Discounts'            => [
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
        $response = $this->client->admin()->orders()->returnItems(ReturnItemsDto::fromStdClass($data));
        $this->assertTrue(array_key_exists('PaymentTransactionId', $response->json()));
        $this->assertTrue(array_key_exists('Status', $response->json()));
        $this->assertEquals(PaymentTransactionStatus::Created->value, $response->json()['Status']);
    }
}
