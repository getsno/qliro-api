<?php

namespace Gets\QliroApi\Dtos\Order;

readonly class OrderItemDto
{
    public function __construct(
        public ?string $Description = null,
        public ?string $MerchantReference = null,
        public ?int    $PaymentTransactionId = null,
        public float   $PricePerItemExVat = 0.0,
        public float   $PricePerItemIncVat = 0.0,
        public int     $Quantity = 1,
        public string  $Type = 'Product',
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
            Description: $data->Description ?? null,
            MerchantReference: $data->MerchantReference ?? null,
            PaymentTransactionId: $data->PaymentTransactionId ?? null,
            PricePerItemExVat: $data->PricePerItemExVat ?? 0.0,
            PricePerItemIncVat: $data->PricePerItemIncVat ?? 0.0,
            Quantity: $data->Quantity ?? 1,
            Type: $data->Type ?? 'Product',
            VatRate: $data->VatRate ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'MerchantReference'  => $this->MerchantReference,
            'Description'        => $this->Description,
            'Type'               => $this->Type,
            'Quantity'           => $this->Quantity,
            'PricePerItemIncVat' => $this->PricePerItemIncVat,
            'PricePerItemExVat'  => $this->PricePerItemExVat,
            'VatRate'            => $this->VatRate,
        ];

        // Remove null values
        return array_filter($data, function ($value) {
            return $value !== null;
        });
    }
}
