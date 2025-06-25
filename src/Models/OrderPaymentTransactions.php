<?php

namespace Gets\QliroApi\Models;

use Gets\QliroApi\Dtos\Order\PaymentTransactionDto;
use Gets\QliroApi\Enums\PaymentTransactionStatus;

class OrderPaymentTransactions
{
    private ?array $transactions;

    public function __construct(?array $transactions)
    {
        $this->transactions = $transactions;
    }

    public function all(): ?array
    {
        return $this->transactions;
    }

    public function successful(): ?array
    {
        if ($this->transactions === null) {
            return null;
        }

        return array_filter($this->transactions, static function (PaymentTransactionDto $transaction) {
            return $transaction->Status === PaymentTransactionStatus::Success->value;
        });
    }

    public function findById(int $paymentTransactionId): ?PaymentTransactionDto
    {
        if (!$this->transactions) {
            return null;
        }

        foreach ($this->transactions as $transaction) {
            if ($transaction->PaymentTransactionId === $paymentTransactionId) {
                return $transaction;
            }
        }

        return null;
    }

    public function getStatus(int $paymentTransactionId): ?string
    {
        return $this->findById($paymentTransactionId)?->Status;
    }

    public function getType(int $paymentTransactionId): ?string
    {
        return $this->findById($paymentTransactionId)?->Type;
    }
}
