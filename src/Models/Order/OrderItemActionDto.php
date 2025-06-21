<?php

namespace Gets\QliroApi\Models\Order;

readonly class OrderItemActionDto
{
    public function __construct(
        public ?string $ActionType = null,
        public ?string $Description = null,
        public ?int    $Id = null,
        public ?string $MerchantReference = null,
        public ?int    $PaymentTransactionId = null,
        public ?float  $PricePerItemExVat = null,
        public ?float  $PricePerItemIncVat = null,
        public ?int    $Quantity = null,
        public ?string $Type = null,
        public ?float  $VatRate = null,
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
            ActionType: $data->ActionType ?? null,
            Description: $data->Description ?? null,
            Id: $data->Id ?? null,
            MerchantReference: $data->MerchantReference ?? null,
            PaymentTransactionId: $data->PaymentTransactionId ?? null,
            PricePerItemExVat: $data->PricePerItemExVat ?? null,
            PricePerItemIncVat: $data->PricePerItemIncVat ?? null,
            Quantity: $data->Quantity ?? null,
            Type: $data->Type ?? null,
            VatRate: $data->VatRate ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'ActionType'           => $this->ActionType,
            'Description'          => $this->Description,
            'Id'                   => $this->Id,
            'MerchantReference'    => $this->MerchantReference,
            'PaymentTransactionId' => $this->PaymentTransactionId,
            'PricePerItemExVat'    => $this->PricePerItemExVat,
            'PricePerItemIncVat'   => $this->PricePerItemIncVat,
            'Quantity'             => $this->Quantity,
            'Type'                 => $this->Type?->value,
            'VatRate'              => $this->VatRate,
        ];

        // Remove null values
        return array_filter($data, function ($value) {
            return $value !== null;
        });
    }
}
