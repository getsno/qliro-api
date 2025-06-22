<?php

namespace Gets\QliroApi\Models;

use Gets\QliroApi\Enums\OrderChangeType;

class OrderChange
{
    public string $MerchantReference;
    public float $PricePerItemExVat;

    public OrderChangeType $Type;

    public ?int $Quantity = null;

    public function __construct(string $MerchantReference, float $PricePerItemExVat, OrderChangeType $Type, int $Quantity = null)
    {
        $this->MerchantReference = $MerchantReference;
        $this->Type = $Type;
        $this->PricePerItemExVat = $PricePerItemExVat;
        $this->Quantity = $Quantity;
    }

    public static function delete(string $MerchantReference, float $PricePerItemExVat): self
    {
        return new self($MerchantReference, $PricePerItemExVat, OrderChangeType::Delete);
    }

    public static function decrease(string $MerchantReference, float $PricePerItemExVat, int $Quantity): self
    {
        return new self($MerchantReference, $PricePerItemExVat, OrderChangeType::Decrease, $Quantity);
    }

    public static function replace(string $MerchantReference, float $PricePerItemExVat, int $Quantity): self
    {
        return new self($MerchantReference, $PricePerItemExVat, OrderChangeType::Replace, $Quantity);
    }
}
