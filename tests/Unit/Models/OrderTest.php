<?php

namespace Gets\QliroApi\Tests\Unit\Models;

use Gets\QliroApi\Dtos\Order\AdminOrderDetailsDto;
use Gets\QliroApi\Dtos\Order\PaymentTransactionDto;
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

        // Create order DTO with transactions
        $orderDto = new AdminOrderDetailsDto(
            PaymentTransactions: [$transaction1, $transaction2]
        );

        // Create order model
        $order = new Order($orderDto);

        // Test getting remaining amount (should be original - captured)
        $amount = $order->getRemainingAmount();
        $this->assertEquals(50.0, $amount);
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
    }
}
