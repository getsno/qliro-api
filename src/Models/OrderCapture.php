<?php

namespace Gets\QliroApi\Models;

class OrderCapture
{
    public function __construct(
        public string $MerchantReference,
        public float  $PricePerItemIncVat,
        public int    $Quantity
    )
    {
    }

    public static function make(string $MerchantReference, float $PricePerItemIncVat, int $Quantity): self
    {
        return new self($MerchantReference, $PricePerItemIncVat, $Quantity);
    }
}
