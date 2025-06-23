<?php

namespace Gets\QliroApi\Dtos\Transaction;

use Gets\QliroApi\Enums\PaymentTransactionStatus;

readonly class NewlyCreatedTransactionDto
{
    public function __construct(
        public int $PaymentTransactionId,
        public PaymentTransactionStatus $Status,
    )
    {

    }

    public static function fromStdClass(object|array $data): self
    {
        if (is_array($data)) {
            $data = (object)$data;
        }

        return new self(
            PaymentTransactionId: $data->PaymentTransactionId,
            Status: PaymentTransactionStatus::from($data->Status),
        );
    }

    public function toArray(): array
    {
        return [
            'PaymentTransactionId' => $this->PaymentTransactionId,
            'Status' => $this->Status->value,
        ];
    }
}
