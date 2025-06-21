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
}
