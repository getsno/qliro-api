<?php

namespace Gets\QliroApi\Builders;

use Gets\QliroApi\Dtos\Order\MarkItemsAsShippedDto;
use Gets\QliroApi\Dtos\Order\OrderItemDto;
use Gets\QliroApi\Dtos\Order\ShipmentDto;
use Gets\QliroApi\Dtos\Order\UpdateDto;
use Gets\QliroApi\Dtos\Order\UpdateItemsDto;
use Gets\QliroApi\Enums\OrderChangeType;
use Gets\QliroApi\Exceptions\QliroException;
use Gets\QliroApi\Models\Order;
use Gets\QliroApi\Models\OrderCaptures;
use Gets\QliroApi\Models\OrderChanges;

class OrderCaptureDtoBuilder
{
    private Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build(OrderCaptures $captures): MarkItemsAsShippedDto
    {
        $currentItems = $this->order->itemsCurrent();

        // Create a map of current items by merchant reference and price
        $currentItemsMap = [];
        foreach ($currentItems as $item) {
            $key = $item->MerchantReference . '_' . $item->PricePerItemIncVat;
            if (!isset($currentItemsMap[$key])) {
                $currentItemsMap[$key] = [];
            }
            $currentItemsMap[$key][] = $item;
        }

        // Group captures by PaymentTransactionId
        $shipmentsByTransaction = [];

        foreach ($captures->getCaptures() as $capture) {
            $key = $capture->MerchantReference . '_' . $capture->PricePerItemIncVat;

            // Check if the item exists in current items
            if (!isset($currentItemsMap[$key])) {
                throw new QliroException(
                    "Item with MerchantReference '{$capture->MerchantReference}' and price {$capture->PricePerItemIncVat} not found in current items"
                );
            }

            // Calculate total current quantity for this item
            $totalCurrentQty = 0;
            foreach ($currentItemsMap[$key] as $currentItem) {
                $totalCurrentQty += $currentItem->Quantity;
            }

            // Check if capture quantity is valid
            if ($capture->Quantity > $totalCurrentQty) {
                throw new QliroException(
                    "Capture quantity {$capture->Quantity} exceeds total current quantity {$totalCurrentQty} for item with MerchantReference '{$capture->MerchantReference}' and price {$capture->PricePerItemIncVat}"
                );
            }

            // Distribute capture quantity across current items
            $remainingQty = $capture->Quantity;
            foreach ($currentItemsMap[$key] as $currentItem) {
                if ($remainingQty <= 0) {
                    break;
                }

                // Determine quantity to capture from this current item
                $qtyToCapture = min($remainingQty, $currentItem->Quantity);
                $remainingQty -= $qtyToCapture;

                // Add to shipments by transaction
                $transactionId = $currentItem->PaymentTransactionId;
                if (!isset($shipmentsByTransaction[$transactionId])) {
                    $shipmentsByTransaction[$transactionId] = [];
                }

                // Create a new OrderItemDto for the capture
                $shipmentsByTransaction[$transactionId][] = new OrderItemDto(
                    Description: $currentItem->Description,
                    MerchantReference: $currentItem->MerchantReference,
                    PaymentTransactionId: $currentItem->PaymentTransactionId,
                    PricePerItemExVat: $currentItem->PricePerItemExVat,
                    PricePerItemIncVat: $currentItem->PricePerItemIncVat,
                    Quantity: $qtyToCapture,
                    Type: $currentItem->Type,
                    VatRate: $currentItem->VatRate
                );
            }
        }

        // Create ShipmentDto objects for each transaction
        $shipments = [];
        foreach ($shipmentsByTransaction as $transactionId => $items) {
            $shipments[] = new ShipmentDto(
                PaymentTransactionId: $transactionId,
                OrderItems: $items
            );
        }

        // Create MarkItemsAsShippedDto
        return new MarkItemsAsShippedDto(
            OrderId: $this->orderId() ?? 0,
            Currency: $this->currency() ?? 'NOK',
            Shipments: $shipments
        );
    }
}
