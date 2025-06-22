<?php

namespace Gets\QliroApi\Dtos\Order;

readonly class DiscountDto
{
    public function __construct(
        public string $MerchantReference,
        public string $Description,
        public int    $PricePerItemIncVat,
        public int    $PricePerItemExVat,
    )
    {
    }

    public static function fromStdClass(object|array $data): self
    {
        if (is_array($data)) {
            $data = (object)$data;
        }

        return new self(
            MerchantReference: $data->MerchantReference,
            Description: $data->Description,
            PricePerItemIncVat: $data->PricePerItemIncVat,
            PricePerItemExVat: $data->PricePerItemExVat,
        );
    }

    public function toArray(): array
    {
        return [
            'MerchantReference'  => $this->MerchantReference,
            'Description'        => $this->Description,
            'PricePerItemIncVat' => $this->PricePerItemIncVat,
            'PricePerItemExVat'  => $this->PricePerItemExVat,
        ];
    }
}