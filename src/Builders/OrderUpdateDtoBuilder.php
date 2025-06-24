<?php

namespace Gets\QliroApi\Builders;

use Gets\QliroApi\Dtos\Order\OrderItemDto;
use Gets\QliroApi\Dtos\Order\UpdateDto;
use Gets\QliroApi\Dtos\Order\UpdateItemsDto;
use Gets\QliroApi\Enums\OrderChangeType;
use Gets\QliroApi\Models\Order;
use Gets\QliroApi\Models\OrderChanges;

class OrderUpdateDtoBuilder
{
    private Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build(OrderChanges $changes): UpdateItemsDto
    {
        // Get reserved items
        $currentItems = $this->order->itemsEligableForCapture();

        // Create a map of current items by merchant reference and price
        $currentItemsMap = [];
        foreach ($currentItems as $item) {
            $key = $item->MerchantReference . '_' . $item->PricePerItemIncVat;
            $currentItemsMap[$key] = $item;
        }

        // Apply changes to current items
        foreach ($changes->getChanges() as $change) {
            $key = $change->MerchantReference . '_' . $change->PricePerItemIncVat;

            // Skip if item doesn't exist in current items
            if (!isset($currentItemsMap[$key])) {
                continue;
            }

            // Apply change based on type
            switch ($change->Type) {
                case OrderChangeType::Delete:
                    // Remove item from map
                    unset($currentItemsMap[$key]);
                    break;

                case OrderChangeType::Decrease:
                    // Decrease quantity
                    if ($change->Quantity !== null) {
                        $newQuantity = $currentItemsMap[$key]->Quantity - $change->Quantity;
                        if ($newQuantity <= 0) {
                            unset($currentItemsMap[$key]);
                        } else {
                            // Update quantity
                            $currentItemsMap[$key] = new OrderItemDto(
                                Description: $currentItemsMap[$key]->Description,
                                MerchantReference: $currentItemsMap[$key]->MerchantReference,
                                PaymentTransactionId: $currentItemsMap[$key]->PaymentTransactionId,
                                PricePerItemExVat: $currentItemsMap[$key]->PricePerItemExVat,
                                PricePerItemIncVat: $currentItemsMap[$key]->PricePerItemIncVat,
                                Quantity: $newQuantity,
                                Type: $currentItemsMap[$key]->Type,
                                VatRate: $currentItemsMap[$key]->VatRate
                            );
                        }
                    }
                    break;

                case OrderChangeType::Replace:
                    // Replace quantity
                    if ($change->Quantity !== null) {
                        if ($change->Quantity <= 0) {
                            // If quantity is zero or negative, remove item
                            unset($currentItemsMap[$key]);
                        } else {
                            // Update quantity
                            $currentItemsMap[$key] = new OrderItemDto(
                                Description: $currentItemsMap[$key]->Description,
                                MerchantReference: $currentItemsMap[$key]->MerchantReference,
                                PaymentTransactionId: $currentItemsMap[$key]->PaymentTransactionId,
                                PricePerItemExVat: $currentItemsMap[$key]->PricePerItemExVat,
                                PricePerItemIncVat: $currentItemsMap[$key]->PricePerItemIncVat,
                                Quantity: $change->Quantity,
                                Type: $currentItemsMap[$key]->Type,
                                VatRate: $currentItemsMap[$key]->VatRate
                            );
                        }
                    }
                    break;
            }
        }

        // Get items after applying changes
        $updatedItems = array_values($currentItemsMap);

        // Group items by PaymentTransactionId
        $groupedItems = [];
        foreach ($updatedItems as $item) {
            if (!isset($groupedItems[$item->PaymentTransactionId])) {
                $groupedItems[$item->PaymentTransactionId] = [];
            }
            $groupedItems[$item->PaymentTransactionId][] = $item;
        }

        // Create UpdateDto objects for each group
        $updates = [];
        foreach ($groupedItems as $paymentTransactionId => $items) {
            $updates[] = new UpdateDto(
                PaymentTransactionId: $paymentTransactionId,
                OrderItems: $items
            );
        }

        // Create UpdateItemsDto
        return new UpdateItemsDto(
            OrderId: $this->order->orderId() ?? 0,
            Currency: $this->order->currency() ?? 'NOK',
            Updates: $updates
        );
    }
}
