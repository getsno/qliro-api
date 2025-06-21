<?php

namespace Gets\QliroApi\Tests\Unit\Models;

use Gets\QliroApi\Dtos\Order\AdminOrderDetailsDto;
use Gets\QliroApi\Dtos\Order\OrderItemActionDto;
use Gets\QliroApi\Dtos\Order\PaymentTransactionDto;
use Gets\QliroApi\Enums\OrderItemActionType;
use Gets\QliroApi\Models\Order;
use Gets\QliroApi\Tests\QliroApiTestCase;

class OrderTest extends QliroApiTestCase
{
    /**
     * Test getTransactionStatus when a transaction with the given ID exists
     */
    public function testGetTransactionStatusWithExistingTransaction(): void
    {
        // Create payment transactions
        $transaction1 = new PaymentTransactionDto(
            PaymentTransactionId: 123,
            Status: 'Completed'
        );
        $transaction2 = new PaymentTransactionDto(
            PaymentTransactionId: 456,
            Status: 'Pending'
        );

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting status of existing transaction
        $status = $order->getTransactionStatus(123);
        $this->assertEquals('Completed', $status);

        // Test getting status of another existing transaction
        $status = $order->getTransactionStatus(456);
        $this->assertEquals('Pending', $status);
    }

    /**
     * Test getTransactionStatus when no transaction with the given ID exists
     */
    public function testGetTransactionStatusWithNonExistingTransaction(): void
    {
        // Create payment transaction
        $transaction = new PaymentTransactionDto(
            PaymentTransactionId: 123,
            Status: 'Completed'
        );

        // Create order DTO with transaction
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting status of non-existing transaction
        $status = $order->getTransactionStatus(999);
        $this->assertNull($status);
    }

    /**
     * Test getTransactionStatus when there are no transactions
     */
    public function testGetTransactionStatusWithNoTransactions(): void
    {
        // Create order DTO without transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: null
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting status when there are no transactions
        $status = $order->getTransactionStatus(123);
        $this->assertNull($status);
    }
    /**
     * Test getOriginalOrderAmount with various transaction types
     */
    public function testGetOriginalOrderAmount(): void
    {
        // Create payment transactions
        $transaction1 = new PaymentTransactionDto(
            Amount: 100.0,
            PaymentTransactionId: 123,
            Type: 'Preauthorization'
        );
        $transaction2 = new PaymentTransactionDto(
            Amount: 50.0,
            PaymentTransactionId: 456,
            Type: 'Preauthorization'
        );
        $transaction3 = new PaymentTransactionDto(
            Amount: 75.0,
            PaymentTransactionId: 789,
            Type: 'Capture'
        );

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2, $transaction3]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting original order amount (should sum only Preauthorization transactions)
        $amount = $order->getOriginalOrderAmount();
        $this->assertEquals(150.0, $amount);
    }

    /**
     * Test getCapturedAmount with various transaction types
     */
    public function testGetCapturedAmount(): void
    {
        // Create payment transactions
        $transaction1 = new PaymentTransactionDto(
            Amount: 100.0,
            PaymentTransactionId: 123,
            Type: 'Capture'
        );
        $transaction2 = new PaymentTransactionDto(
            Amount: 50.0,
            PaymentTransactionId: 456,
            Type: 'Capture'
        );
        $transaction3 = new PaymentTransactionDto(
            Amount: 200.0,
            PaymentTransactionId: 789,
            Type: 'Preauthorization'
        );

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2, $transaction3]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting captured amount (should sum only Capture transactions)
        $amount = $order->getCapturedAmount();
        $this->assertEquals(150.0, $amount);
    }

    /**
     * Test getRefundedAmount with various transaction types
     */
    public function testGetRefundedAmount(): void
    {
        // Create payment transactions
        $transaction1 = new PaymentTransactionDto(
            Amount: 30.0,
            PaymentTransactionId: 123,
            Type: 'Refund'
        );
        $transaction2 = new PaymentTransactionDto(
            Amount: 20.0,
            PaymentTransactionId: 456,
            Type: 'Refund'
        );
        $transaction3 = new PaymentTransactionDto(
            Amount: 100.0,
            PaymentTransactionId: 789,
            Type: 'Capture'
        );

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2, $transaction3]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting refunded amount (should sum only Refund transactions)
        $amount = $order->getRefundedAmount();
        $this->assertEquals(50.0, $amount);
    }

    /**
     * Test getCancelledAmount with various transaction types
     */
    public function testGetCancelledAmount(): void
    {
        // Create payment transactions
        $transaction1 = new PaymentTransactionDto(
            Amount: 40.0,
            PaymentTransactionId: 123,
            Type: 'Reversal'
        );
        $transaction2 = new PaymentTransactionDto(
            Amount: 10.0,
            PaymentTransactionId: 456,
            Type: 'Reversal'
        );
        $transaction3 = new PaymentTransactionDto(
            Amount: 100.0,
            PaymentTransactionId: 789,
            Type: 'Preauthorization'
        );

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2, $transaction3]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting cancelled amount (should sum only Reversal transactions)
        $amount = $order->getCancelledAmount();
        $this->assertEquals(50.0, $amount);
    }

    /**
     * Test getRemainingAmount calculation
     */
    public function testGetRemainingAmount(): void
    {
        // Create payment transactions
        $transaction1 = new PaymentTransactionDto(
            Amount: 200.0,
            PaymentTransactionId: 123,
            Type: 'Preauthorization'
        );
        $transaction2 = new PaymentTransactionDto(
            Amount: 150.0,
            PaymentTransactionId: 456,
            Type: 'Capture'
        );
        $transaction3 = new PaymentTransactionDto(
            Amount: 20.0,
            PaymentTransactionId: 789,
            Type: 'Reversal'
        );

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2, $transaction3]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting remaining amount (should be original - captured - cancelled)
        $amount = $order->getRemainingAmount();
        $this->assertEquals(30.0, $amount);
    }

    /**
     * Test getTotalAmount calculation
     */
    public function testGetTotalAmount(): void
    {
        // Create payment transactions
        $transaction1 = new PaymentTransactionDto(
            Amount: 200.0,
            PaymentTransactionId: 123,
            Type: 'Preauthorization'
        );
        $transaction2 = new PaymentTransactionDto(
            Amount: 150.0,
            PaymentTransactionId: 456,
            Type: 'Capture'
        );
        $transaction3 = new PaymentTransactionDto(
            Amount: 20.0,
            PaymentTransactionId: 789,
            Type: 'Reversal'
        );
        $transaction4 = new PaymentTransactionDto(
            Amount: 30.0,
            PaymentTransactionId: 101,
            Type: 'Refund'
        );

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2, $transaction3, $transaction4]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting total amount (should be captured - refunded + remaining)
        $amount = $order->getTotalAmount();
        $this->assertEquals(150.0, $amount);
    }

    /**
     * Test amount methods with no transactions
     */
    public function testAmountMethodsWithNoTransactions(): void
    {
        // Create order DTO without transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: null
        );

        // Create order model
        $order = new Order($orderDto);

        // Test all amount methods return 0.0 when there are no transactions
        $this->assertEquals(0.0, $order->getOriginalOrderAmount());
        $this->assertEquals(0.0, $order->getCapturedAmount());
        $this->assertEquals(0.0, $order->getRefundedAmount());
        $this->assertEquals(0.0, $order->getCancelledAmount());
        $this->assertEquals(0.0, $order->getRemainingAmount());
        $this->assertEquals(0.0, $order->getTotalAmount());
    }

    /**
     * Test currentOrderItems with no order item actions
     */
    public function testCurrentOrderItemsWithNoActions(): void
    {
        // Create order DTO without order item actions
        $orderDto = new AdminOrderDetailsDto(
            OrderItemActions: null
        );

        // Create order model
        $order = new Order($orderDto);

        // Test currentOrderItems returns empty array when there are no actions
        $this->assertEmpty($order->currentOrderItems());
    }

    /**
     * Test currentOrderItems with various order item actions
     */
    public function testCurrentOrderItems(): void
    {
        // Create order item actions
        $actions = [
            // Item 1: 10 reserved, 3 shipped, 2 released = 5 remaining
            new OrderItemActionDto(
                ActionType: OrderItemActionType::Reserve->value,
                Description: 'Product 1',
                MerchantReference: 'PROD-1',
                PaymentTransactionId: 1001, // Reserve action's PaymentTransactionId
                PricePerItemExVat: 100.0,
                PricePerItemIncVat: 125.0,
                Quantity: 10,
                Type: 'Product',
                VatRate: 25.0
            ),
            new OrderItemActionDto(
                ActionType: OrderItemActionType::Ship->value,
                MerchantReference: 'PROD-1',
                PaymentTransactionId: 2001, // Different PaymentTransactionId
                PricePerItemExVat: 100.0,
                Quantity: 3
            ),
            new OrderItemActionDto(
                ActionType: OrderItemActionType::Release->value,
                MerchantReference: 'PROD-1',
                PaymentTransactionId: 3001, // Different PaymentTransactionId
                PricePerItemExVat: 100.0,
                Quantity: 2
            ),

            // Item 2: 5 reserved, 5 shipped = 0 remaining (should not be included)
            new OrderItemActionDto(
                ActionType: OrderItemActionType::Reserve->value,
                Description: 'Product 2',
                MerchantReference: 'PROD-2',
                PaymentTransactionId: 1002, // Reserve action's PaymentTransactionId
                PricePerItemExVat: 200.0,
                PricePerItemIncVat: 240.0,
                Quantity: 5,
                Type: 'Product',
                VatRate: 20.0
            ),
            new OrderItemActionDto(
                ActionType: OrderItemActionType::Ship->value,
                MerchantReference: 'PROD-2',
                PaymentTransactionId: 2002, // Different PaymentTransactionId
                PricePerItemExVat: 200.0,
                Quantity: 5
            ),

            // Item 3: 3 reserved, 0 shipped, 0 released = 3 remaining
            new OrderItemActionDto(
                ActionType: OrderItemActionType::Reserve->value,
                Description: 'Product 3',
                MerchantReference: 'PROD-3',
                PaymentTransactionId: 1003, // Reserve action's PaymentTransactionId
                PricePerItemExVat: 50.0,
                PricePerItemIncVat: 60.0,
                Quantity: 3,
                Type: 'Product',
                VatRate: 20.0
            ),

            // Item 4: Different price for same product (should be treated as separate item)
            new OrderItemActionDto(
                ActionType: OrderItemActionType::Reserve->value,
                Description: 'Product 1 (discounted)',
                MerchantReference: 'PROD-1',
                PaymentTransactionId: 1004, // Reserve action's PaymentTransactionId
                PricePerItemExVat: 80.0,
                PricePerItemIncVat: 100.0,
                Quantity: 2,
                Type: 'Product',
                VatRate: 25.0
            )
        ];

        // Create order DTO with order item actions
        $orderDto = new AdminOrderDetailsDto(
            OrderItemActions: $actions
        );

        // Create order model
        $order = new Order($orderDto);

        // Get current order items
        $currentItems = $order->currentOrderItems();

        // Test number of items (should be 3: PROD-1 with price 100, PROD-3, and PROD-1 with price 80)
        $this->assertCount(3, $currentItems);

        // Find items by merchant reference and price
        $item1 = null;
        $item3 = null;
        $item4 = null;

        foreach ($currentItems as $item) {
            if ($item->MerchantReference === 'PROD-1' && $item->PricePerItemExVat === 100.0) {
                $item1 = $item;
            } elseif ($item->MerchantReference === 'PROD-3') {
                $item3 = $item;
            } elseif ($item->MerchantReference === 'PROD-1' && $item->PricePerItemExVat === 80.0) {
                $item4 = $item;
            }
        }

        // Test item 1 properties
        $this->assertNotNull($item1);
        $this->assertEquals('Product 1', $item1->Description);
        $this->assertEquals(5, $item1->Quantity); // 10 reserved - 3 shipped - 2 released
        $this->assertEquals(100.0, $item1->PricePerItemExVat);
        $this->assertEquals(125.0, $item1->PricePerItemIncVat);
        $this->assertEquals('Product', $item1->Type);
        $this->assertEquals(25.0, $item1->VatRate);
        $this->assertEquals(1001, $item1->PaymentTransactionId); // Should use Reserve action's PaymentTransactionId

        // Test item 3 properties
        $this->assertNotNull($item3);
        $this->assertEquals('Product 3', $item3->Description);
        $this->assertEquals(3, $item3->Quantity); // 3 reserved - 0 shipped - 0 released
        $this->assertEquals(50.0, $item3->PricePerItemExVat);
        $this->assertEquals(60.0, $item3->PricePerItemIncVat);
        $this->assertEquals('Product', $item3->Type);
        $this->assertEquals(20.0, $item3->VatRate);
        $this->assertEquals(1003, $item3->PaymentTransactionId); // Should use Reserve action's PaymentTransactionId

        // Test item 4 properties
        $this->assertNotNull($item4);
        $this->assertEquals('Product 1 (discounted)', $item4->Description);
        $this->assertEquals(2, $item4->Quantity); // 2 reserved - 0 shipped - 0 released
        $this->assertEquals(80.0, $item4->PricePerItemExVat);
        $this->assertEquals(100.0, $item4->PricePerItemIncVat);
        $this->assertEquals('Product', $item4->Type);
        $this->assertEquals(25.0, $item4->VatRate);
        $this->assertEquals(1004, $item4->PaymentTransactionId); // Should use Reserve action's PaymentTransactionId
    }
}
