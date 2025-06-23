<?php

namespace Gets\QliroApi\Models;

use Gets\QliroApi\Enums\OrderStatus;

class OrderStatusCalculator
{
    private OrderAmountCalculator $amountCalculator;

    public function __construct(OrderAmountCalculator $amountCalculator)
    {
        $this->amountCalculator = $amountCalculator;
    }

    public function calculate(): OrderStatus
    {
        if ($this->amountCalculator->original() === $this->amountCalculator->cancelled()) {
            return OrderStatus::Cancelled;
        }

        if ($this->amountCalculator->refunded() === $this->amountCalculator->original()) {
            return OrderStatus::Refunded;
        }

        if ($this->amountCalculator->total() > 0.0 && $this->amountCalculator->refunded() !== 0.0) {
            return OrderStatus::PartiallyRefunded;
        }

        if ($this->amountCalculator->captured() !== 0.0 && $this->amountCalculator->remaining() !== 0.0) {
            return OrderStatus::PartiallyCompleted;
        }

        if ($this->amountCalculator->remaining() === 0.0) {
            return OrderStatus::Completed;
        }

        return OrderStatus::New;
    }
}