<?php

namespace Gets\QliroApi\Tests\Factories;

use Gets\QliroApi\Dtos\Order\CreateOrderDto;
use Gets\QliroApi\Dtos\Order\OrderItemDto;

class CreateOrderDtoFactory
{
    public static function create(array $overrides = [], array $orderItems = []): CreateOrderDto
    {
        $dto = new CreateOrderDto();

        // Generate a merchantReference that matches the regex pattern ^[A-Za-z0-9_-]{1,25}$
        $dto->MerchantReference = 'test-' . substr(str_replace(['.', '/'], ['', ''], uniqid('', false)), 0, 20);
        $dto->Currency = 'NOK';
        $dto->Country = 'NO';
        $dto->Language = 'nb-no';
        $dto->MerchantTermsUrl = 'https://example.com/terms';
        $dto->MerchantConfirmationUrl = 'https://example.com/confirmation';
        $dto->MerchantOrderManagementStatusPushUrl = 'https://example.com/pushstatus';
        $dto->MerchantCheckoutStatusPushUrl = 'https://example.com/push';

        // Apply overrides
        foreach ($overrides as $property => $value) {
            if (property_exists($dto, $property)) {
                $dto->$property = $value;
            }
        }

        // Add order items
        if (empty($orderItems)) {
            // Add a default order item if none provided
            $dto->addOrderItem(OrderItemDtoFactory::create());
        } else {
            foreach ($orderItems as $orderItem) {
                if ($orderItem instanceof OrderItemDto) {
                    $dto->addOrderItem($orderItem);
                } elseif (is_array($orderItem)) {
                    $dto->addOrderItem(OrderItemDtoFactory::create($orderItem));
                }
            }
        }

        return $dto;
    }
}
