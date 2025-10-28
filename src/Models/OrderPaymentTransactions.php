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

    public function successfullOnlyAfterLastPreauthorization(): ?array
    {
        $successfullTrans = $this->successful();
        if ($successfullTrans === null) {
            return null;
        }

        // Find the last successful preauthorization transaction by timestamp
        $lastPreauthTimestamp = null;
        foreach ($successfullTrans as $transaction) {
            if ($transaction->Type === \Gets\QliroApi\Enums\PaymentTransactionType::Preauthorization->value
                && $transaction->Timestamp !== null) {
                if ($lastPreauthTimestamp === null || $transaction->Timestamp > $lastPreauthTimestamp) {
                    $lastPreauthTimestamp = $transaction->Timestamp;
                }
            }
        }

        // If no successful preauthorization found, return all transactions
        if ($lastPreauthTimestamp === null) {
            return $successfullTrans;
        }

        // Return transactions based on timestamp comparison
        $result = [];

        foreach ($successfullTrans as $transaction) {
            if ($transaction->Timestamp >= $lastPreauthTimestamp) {
                $result[] = $transaction;
            }
        }

        return $result;
    }

    public function successfulCaptureReversalRefundBeforeLastPreauthorization(): ?array
    {
        $successfullTrans = $this->successful();
        if ($successfullTrans === null) {
            return null;
        }

        // Find the last successful preauthorization transaction by timestamp
        $lastPreauthTimestamp = null;
        foreach ($successfullTrans as $transaction) {
            if ($transaction->Type === \Gets\QliroApi\Enums\PaymentTransactionType::Preauthorization->value
                && $transaction->Timestamp !== null) {
                if ($lastPreauthTimestamp === null || $transaction->Timestamp > $lastPreauthTimestamp) {
                    $lastPreauthTimestamp = $transaction->Timestamp;
                }
            }
        }

        // If no successful preauthorization found, return empty array
        if ($lastPreauthTimestamp === null) {
            return [];
        }

        // Return Capture, Reversal, and Refund transactions that occurred before the last preauthorization
        $result = [];
        $allowedTypes = [
            \Gets\QliroApi\Enums\PaymentTransactionType::Capture->value,
            \Gets\QliroApi\Enums\PaymentTransactionType::Reversal->value,
            \Gets\QliroApi\Enums\PaymentTransactionType::Refund->value,
        ];

        foreach ($successfullTrans as $transaction) {
            if ($transaction->Timestamp < $lastPreauthTimestamp
                && in_array($transaction->Type, $allowedTypes, true)) {
                $result[] = $transaction;
            }
        }

        return $result;
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
