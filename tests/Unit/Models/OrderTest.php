<?php

namespace Gets\QliroApi\Tests\Unit\Models;

use Gets\QliroApi\Dtos\Order\AdminOrderDetailsDto;
use Gets\QliroApi\Dtos\Order\OrderItemActionDto;
use Gets\QliroApi\Dtos\Order\PaymentTransactionDto;
use Gets\QliroApi\Enums\OrderItemActionType;
use Gets\QliroApi\Enums\PaymentTransactionStatus;
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
            Status: PaymentTransactionStatus::Cancelled->value,
            Type: 'Preauthorization'
        );
        $transaction2 = new PaymentTransactionDto(
            Amount: 50.0,
            PaymentTransactionId: 456,
            Status: PaymentTransactionStatus::Success->value,
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
        $amount = $order->amountOriginal();
        $this->assertEquals(50, $amount);
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
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Capture'
        );
        $transaction2 = new PaymentTransactionDto(
            Amount: 50.0,
            PaymentTransactionId: 456,
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Capture'
        );
        $transaction3 = new PaymentTransactionDto(
            Amount: 200.0,
            PaymentTransactionId: 789,
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Preauthorization'
        );

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2, $transaction3]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting captured amount (should sum only Capture transactions)
        $amount = $order->amountCaptured();
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
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Refund'
        );
        $transaction2 = new PaymentTransactionDto(
            Amount: 20.0,
            PaymentTransactionId: 456,
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Refund'
        );
        $transaction3 = new PaymentTransactionDto(
            Amount: 100.0,
            PaymentTransactionId: 789,
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Capture'
        );

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2, $transaction3]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting refunded amount (should sum only Refund transactions)
        $amount = $order->amountRefunded();
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
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Reversal'
        );
        $transaction2 = new PaymentTransactionDto(
            Amount: 10.0,
            PaymentTransactionId: 456,
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Reversal'
        );
        $transaction3 = new PaymentTransactionDto(
            Amount: 100.0,
            PaymentTransactionId: 789,
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Preauthorization'
        );

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2, $transaction3]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting cancelled amount (should sum only Reversal transactions)
        $amount = $order->amountCancelled();
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
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Preauthorization'
        );
        $transaction2 = new PaymentTransactionDto(
            Amount: 150.0,
            PaymentTransactionId: 456,
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Capture'
        );
        $transaction3 = new PaymentTransactionDto(
            Amount: 20.0,
            PaymentTransactionId: 789,
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Reversal'
        );

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2, $transaction3]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting remaining amount (should be original - captured - cancelled)
        $amount = $order->amountRemaining();
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
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Preauthorization'
        );
        $transaction2 = new PaymentTransactionDto(
            Amount: 150.0,
            PaymentTransactionId: 456,
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Capture'
        );
        $transaction3 = new PaymentTransactionDto(
            Amount: 20.0,
            PaymentTransactionId: 789,
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Reversal'
        );
        $transaction4 = new PaymentTransactionDto(
            Amount: 30.0,
            PaymentTransactionId: 101,
            Status: PaymentTransactionStatus::Success->value,
            Type: 'Refund'
        );

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2, $transaction3, $transaction4]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting total amount (should be captured - refunded + remaining)
        $amount = $order->amountTotal();
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
        $this->assertEquals(0.0, $order->amountOriginal());
        $this->assertEquals(0.0, $order->amountCaptured());
        $this->assertEquals(0.0, $order->amountRefunded());
        $this->assertEquals(0.0, $order->amountCancelled());
        $this->assertEquals(0.0, $order->amountRemaining());
        $this->assertEquals(0.0, $order->amountTotal());
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
        $this->assertEmpty($order->itemsCurrent());
    }

    /**
     * Test currentOrderItems with various order item actions
     */
    public function testCurrentOrderItems(): void
    {
        $paymentTransactions = [
            new PaymentTransactionDto(
                PaymentTransactionId: 1001,
                Status: PaymentTransactionStatus::Success->value
            ),
            new PaymentTransactionDto(
                PaymentTransactionId: 1002,
                Status: PaymentTransactionStatus::Success->value
            ),
            new PaymentTransactionDto(
                PaymentTransactionId: 1003,
                Status: PaymentTransactionStatus::Success->value
            ),
            new PaymentTransactionDto(
                PaymentTransactionId: 1004,
                Status: PaymentTransactionStatus::Success->value
            ),
            new PaymentTransactionDto(
                PaymentTransactionId: 2001,
                Status: PaymentTransactionStatus::Success->value
            ),
            new PaymentTransactionDto(
                PaymentTransactionId: 2002,
                Status: PaymentTransactionStatus::Success->value
            ),
            new PaymentTransactionDto(
                PaymentTransactionId: 3001,
                Status: PaymentTransactionStatus::Success->value
            ),
        ];

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
            PaymentTransactions:$paymentTransactions,
            OrderItemActions: $actions
        );

        // Create order model
        $order = new Order($orderDto);

        // Get current order items
        $currentItems = $order->itemsCurrent();

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
    /**
     * Test getUpdateDto with various order changes
     */

    public function testGetUpdateDto(): void
    {
        // Create order item actions
        $actions = [
            // Item 1: 10 reserved, 3 shipped, 2 released = 5 remaining
            new OrderItemActionDto(
                ActionType: OrderItemActionType::Reserve->value,
                Description: 'Product 1',
                MerchantReference: 'PROD-1',
                PaymentTransactionId: 1001,
                PricePerItemExVat: 100.0,
                PricePerItemIncVat: 125.0,
                Quantity: 10,
                Type: 'Product',
                VatRate: 25.0
            ),
            new OrderItemActionDto(
                ActionType: OrderItemActionType::Ship->value,
                MerchantReference: 'PROD-1',
                PaymentTransactionId: 2001,
                PricePerItemIncVat: 125.0,
                Quantity: 3
            ),
            new OrderItemActionDto(
                ActionType: OrderItemActionType::Release->value,
                MerchantReference: 'PROD-1',
                PaymentTransactionId: 3001,
                PricePerItemIncVat: 125.0,
                Quantity: 2
            ),

            // Item 2: 5 reserved, 0 shipped, 0 released = 5 remaining
            new OrderItemActionDto(
                ActionType: OrderItemActionType::Reserve->value,
                Description: 'Product 2',
                MerchantReference: 'PROD-2',
                PaymentTransactionId: 1002,
                PricePerItemExVat: 200.0,
                PricePerItemIncVat: 240.0,
                Quantity: 5,
                Type: 'Product',
                VatRate: 20.0
            )
        ];

        // Create payment transactions with Success status for all referenced IDs
        $transactions = [
            new PaymentTransactionDto(
                Amount: 1000.0,
                PaymentTransactionId: 1001,
                Status: PaymentTransactionStatus::Success->value,
                Type: 'Preauthorization'
            ),
            new PaymentTransactionDto(
                Amount: 1000.0,
                PaymentTransactionId: 1002,
                Status: PaymentTransactionStatus::Success->value,
                Type: 'Preauthorization'
            ),
            new PaymentTransactionDto(
                Amount: 300.0,
                PaymentTransactionId: 2001,
                Status: PaymentTransactionStatus::Success->value,
                Type: 'Capture'
            ),
            new PaymentTransactionDto(
                Amount: 200.0,
                PaymentTransactionId: 3001,
                Status: PaymentTransactionStatus::Success->value,
                Type: 'Release'
            )
        ];

        // Create order DTO with order item actions and payment transactions
        $orderDto = new AdminOrderDetailsDto(
            OrderId: 12345,
            Currency: 'EUR',
            PaymentTransactions: $transactions,
            OrderItemActions: $actions
        );

        // Create order model
        $order = new Order($orderDto);

        // Create order changes
        $changes = new \Gets\QliroApi\Models\OrderChanges();

        // Test 1: Delete an item
        $changes->delete('PROD-2', 240.0);

        $updateDto = $order->buildUpdateDto($changes);

        // Verify the UpdateItemsDto
        $this->assertEquals(12345, $updateDto->OrderId);
        $this->assertEquals('EUR', $updateDto->Currency);
        $this->assertCount(1, $updateDto->Updates);

        // Verify the UpdateDto
        $update = $updateDto->Updates[0];
        $this->assertEquals(1001, $update->PaymentTransactionId);
        $this->assertCount(1, $update->OrderItems);

        // Verify the remaining item (PROD-1)
        $item = $update->OrderItems[0];
        $this->assertEquals('PROD-1', $item->MerchantReference);
        $this->assertEquals(10, $item->Quantity); // Full quantity from Reserve action
        $this->assertEquals(100.0, $item->PricePerItemExVat);

        // Test 2: Decrease quantity of an item
        $changes = new \Gets\QliroApi\Models\OrderChanges();
        $changes->decrease('PROD-1', 125.0, 2);

        $updateDto = $order->buildUpdateDto($changes);

        // Verify there are two UpdateDto objects (one for each PaymentTransactionId)
        $this->assertCount(2, $updateDto->Updates);

        // Find the UpdateDto for PaymentTransactionId 1001 (PROD-1)
        $updateProd1 = null;
        foreach ($updateDto->Updates as $update) {
            if ($update->PaymentTransactionId === 1001) {
                $updateProd1 = $update;
                break;
            }
        }

        // Verify the UpdateDto for PROD-1
        $this->assertNotNull($updateProd1);
        $this->assertEquals(1001, $updateProd1->PaymentTransactionId);
        $this->assertCount(1, $updateProd1->OrderItems);

        // Verify the decreased item
        $decreasedItem = $updateProd1->OrderItems[0];
        $this->assertEquals('PROD-1', $decreasedItem->MerchantReference);
        $this->assertEquals(8, $decreasedItem->Quantity); // 10 - 2 = 8

        // Find the UpdateDto for PaymentTransactionId 1002 (PROD-2)
        $updateProd2 = null;
        foreach ($updateDto->Updates as $update) {
            if ($update->PaymentTransactionId === 1002) {
                $updateProd2 = $update;
                break;
            }
        }

        // Verify the UpdateDto for PROD-2
        $this->assertNotNull($updateProd2);
        $this->assertEquals(1002, $updateProd2->PaymentTransactionId);
        $this->assertCount(1, $updateProd2->OrderItems);

        // Verify the PROD-2 item
        $prod2Item = $updateProd2->OrderItems[0];
        $this->assertEquals('PROD-2', $prod2Item->MerchantReference);
        $this->assertEquals(5, $prod2Item->Quantity);

        // Test 3: Replace quantity of an item
        $changes = new \Gets\QliroApi\Models\OrderChanges();
        $changes->replace('PROD-1', 125.0, 2);

        $updateDto = $order->buildUpdateDto($changes);

        // Verify there are two UpdateDto objects (one for each PaymentTransactionId)
        $this->assertCount(2, $updateDto->Updates);

        // Find the UpdateDto for PaymentTransactionId 1001 (PROD-1)
        $updateProd1 = null;
        foreach ($updateDto->Updates as $update) {
            if ($update->PaymentTransactionId === 1001) {
                $updateProd1 = $update;
                break;
            }
        }

        // Verify the UpdateDto for PROD-1
        $this->assertNotNull($updateProd1);
        $this->assertEquals(1001, $updateProd1->PaymentTransactionId);
        $this->assertCount(1, $updateProd1->OrderItems);

        // Verify the replaced item
        $replacedItem = $updateProd1->OrderItems[0];
        $this->assertEquals('PROD-1', $replacedItem->MerchantReference);
        $this->assertEquals(2, $replacedItem->Quantity); // Replaced with 2

        // Find the UpdateDto for PaymentTransactionId 1002 (PROD-2)
        $updateProd2 = null;
        foreach ($updateDto->Updates as $update) {
            if ($update->PaymentTransactionId === 1002) {
                $updateProd2 = $update;
                break;
            }
        }

        // Verify the UpdateDto for PROD-2
        $this->assertNotNull($updateProd2);
        $this->assertEquals(1002, $updateProd2->PaymentTransactionId);
        $this->assertCount(1, $updateProd2->OrderItems);

        // Verify the PROD-2 item
        $prod2Item = $updateProd2->OrderItems[0];
        $this->assertEquals('PROD-2', $prod2Item->MerchantReference);
        $this->assertEquals(5, $prod2Item->Quantity);
    }
}
