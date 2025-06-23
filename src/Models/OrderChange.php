<?php

namespace Gets\QliroApi\Models;

use Gets\QliroApi\Enums\OrderChangeType;

class OrderChange
{
    public function __construct(
        public string $MerchantReference,
        public float $PricePerItemIncVat,
        public OrderChangeType $Type,
        public ?int $Quantity = null
    )
    {
    }

    public static function delete(string $MerchantReference, float $PricePerItemIncVat): self
    {
        return new self($MerchantReference, $PricePerItemIncVat, OrderChangeType::Delete);
    }

    public static function decrease(string $MerchantReference, float $PricePerItemIncVat, int $Quantity): self
    {
        return new self($MerchantReference, $PricePerItemIncVat, OrderChangeType::Decrease, $Quantity);
    }

    public static function replace(string $MerchantReference, float $PricePerItemIncVat, int $Quantity): self
    {
        return new self($MerchantReference, $PricePerItemIncVat, OrderChangeType::Replace, $Quantity);
    }
}
