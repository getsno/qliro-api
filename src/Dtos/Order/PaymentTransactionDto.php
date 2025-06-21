<?php

namespace Gets\QliroApi\Dtos\Order;

readonly class PaymentTransactionDto
{
    public function __construct(
        public ?float $Amount = null,
        public ?string $Currency = null,
        public ?int $OrderId = null,
        public ?string $PaymentMethodName = null,
        public ?string $PaymentMethodSubtypeCode = null,
        public ?int $PaymentTransactionId = null,
        public ?string $ProviderResultCode = null,
        public ?string $ProviderResultDescription = null,
        public ?string $ProviderTransactionId = null,
        public ?string $Status = null,
        public ?string $Timestamp = null,
        public ?string $Type = null,
    )
    {
    }

    public static function fromStdClass(null|object|array $data): ?self
    {
        if (!$data) {
            return null;
        }

        if (is_array($data)) {
            $data = (object)$data;
        }

        return new self(
            Amount: $data->Amount ?? null,
            Currency: $data->Currency ?? null,
            OrderId: $data->OrderId ?? null,
            PaymentMethodName: $data->PaymentMethodName ?? null,
            PaymentMethodSubtypeCode: $data->PaymentMethodSubtypeCode ?? null,
            PaymentTransactionId: $data->PaymentTransactionId ?? null,
            ProviderResultCode: $data->ProviderResultCode ?? null,
            ProviderResultDescription: $data->ProviderResultDescription ?? null,
            ProviderTransactionId: $data->ProviderTransactionId ?? null,
            Status: $data->Status ?? null,
            Timestamp: $data->Timestamp ?? null,
            Type: $data->Type ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'Amount' => $this->Amount,
            'Currency' => $this->Currency,
            'OrderId' => $this->OrderId,
            'PaymentMethodName' => $this->PaymentMethodName,
            'PaymentMethodSubtypeCode' => $this->PaymentMethodSubtypeCode,
            'PaymentTransactionId' => $this->PaymentTransactionId,
            'ProviderResultCode' => $this->ProviderResultCode,
            'ProviderResultDescription' => $this->ProviderResultDescription,
            'ProviderTransactionId' => $this->ProviderTransactionId,
            'Status' => $this->Status,
            'Timestamp' => $this->Timestamp,
            'Type' => $this->Type,
        ];

        // Remove null values
        return array_filter($data, function ($value) {
            return $value !== null;
        });
    }
}
