<?php

namespace Gets\QliroApi\Dtos\Transaction;

readonly class TransactionsCreatedDto
{
    public function __construct(
        /** @var NewlyCreatedTransactionDto[] */
        public array $PaymentTransactions,
    )
    {

    }

    public static function fromStdClass(object|array $data): self
    {
        if (is_array($data)) {
            $data = (object)$data;
        }

        $paymentTransactions = [];
        if (isset($data->PaymentTransactions) && is_array($data->PaymentTransactions)) {
            $paymentTransactions = array_map(
                static fn($transaction) => NewlyCreatedTransactionDto::fromStdClass($transaction),
                $data->PaymentTransactions
            );
        }

        return new self(
            PaymentTransactions: $paymentTransactions,
        );
    }

    public function toArray(): array
    {
        // Convert NewlyCreatedTransactionDto objects back to arrays
        $paymentTransactions = array_map(
            static fn(NewlyCreatedTransactionDto $transaction) => $transaction->toArray(),
            $this->PaymentTransactions
        );

        return [
            'PaymentTransactions' => $paymentTransactions,
        ];
    }
}
