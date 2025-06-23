<?php

namespace Gets\QliroApi\Models;

use Gets\QliroApi\Dtos\Order\OrderItemDto;
use Gets\QliroApi\Enums\OrderItemActionType;

class OrderItemsManager
{
    private ?array $orderItemActions;
    private OrderPaymentTransactions $transactions;

    public function __construct(?array $orderItemActions, OrderPaymentTransactions $transactions)
    {
        $this->orderItemActions = $orderItemActions;
        $this->transactions = $transactions;
    }

    public function successful(): ?array
    {
        $successfulTransactions = $this->transactions->successful();
        if ($successfulTransactions === null) {
            return null;
        }

        $successfulTransIds = array_map(static function (PaymentTransactionDto $transaction) {
            return $transaction->PaymentTransactionId;
        }, $successfulTransactions);

        if ($this->orderItemActions === null) {
            return null;
        }

        return array_filter($this->orderItemActions, function ($action) use ($successfulTransIds) {
            return in_array($action->PaymentTransactionId, $successfulTransIds, true);
        });
    }

    public function current(): array
    {
        // Move the itemsCurrent() logic here
        // ... (existing itemsCurrent implementation)
    }

    public function byActionType(OrderItemActionType $actionType): array
    {
        // Move the getOrderItemsByActionType() logic here
        // ... (existing implementation)
    }

    public function eligibleForRefund(): array
    {
        // Move the itemsEligableForRefund() logic here
        // ... (existing implementation)
    }

    // Add other item-related methods...
}