<?php

namespace Gets\QliroApi\Models;

class OrderAmountCalculator
{
    private OrderPaymentTransactions $transactions;

    public function __construct(OrderPaymentTransactions $transactions)
    {
        $this->transactions = $transactions;
    }

    public function original(): float
    {
        return $this->calculateAmountByType('Preauthorization');
    }

    public function captured(): float
    {
        return $this->calculateAmountByType('Capture');
    }

    public function refunded(): float
    {
        return $this->calculateAmountByType('Refund');
    }

    public function cancelled(): float
    {
        return $this->calculateAmountByType('Reversal');
    }

    public function remaining(): float
    {
        return $this->original() - $this->captured() - $this->cancelled();
    }

    public function total(): float
    {
        return $this->captured() - $this->refunded() + $this->remaining();
    }

    private function calculateAmountByType(string $type): float
    {
        $transactions = $this->transactions->successful();
        if (!$transactions) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($transactions as $transaction) {
            if ($transaction->Type === $type && $transaction->Amount !== null) {
                $total += $transaction->Amount;
            }
        }

        return $total;
    }
}
