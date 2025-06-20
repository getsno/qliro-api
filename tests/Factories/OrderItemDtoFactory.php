<?php

namespace Gets\QliroApi\Tests\Factories;

use Gets\QliroApi\Models\Order\OrderItemDto;

class OrderItemDtoFactory
{
    public static function create(array $overrides = []): OrderItemDto
    {
        // Default values
        $defaults = [
            'MerchantReference' => 'test-item-' . uniqid('', true),
            'Description' => 'Test Product',
            'Type' => 'Product',
            'Quantity' => 1,
            'PricePerItemIncVat' => 100.0,
            'PricePerItemExVat' => 80.0,
        ];

        // Apply overrides to defaults
        $values = array_merge($defaults, $overrides);

        // Create instance with all values
        return new OrderItemDto(
            MerchantReference: $values['MerchantReference'],
            Description: $values['Description'],
            Type: $values['Type'],
            Quantity: $values['Quantity'],
            PricePerItemIncVat: $values['PricePerItemIncVat'],
            PricePerItemExVat: $values['PricePerItemExVat']
        );
    }
}
