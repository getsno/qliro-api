<?php

namespace Gets\QliroApi\Builders;

use Gets\QliroApi\Dtos\Order\OrderItemDto;
use Gets\QliroApi\Dtos\Order\ReturnDto;
use Gets\QliroApi\Dtos\Order\ReturnItemsDto;
use Gets\QliroApi\Exceptions\QliroException;
use Gets\QliroApi\Models\Order;
use Gets\QliroApi\Models\OrderChanges;

class OrderReturnDtoBuilder
{
    private Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build(OrderChanges $changes): ReturnItemsDto
    {
        $refundAbleItems = $this->order->itemsEligableForRefund();

        // Create a map of captured items by merchant reference and price
        $refundableItemsMap = [];
        foreach ($refundAbleItems as $item) {
            $key = $item->MerchantReference . '_' . $item->PricePerItemIncVat;
            if (!isset($refundableItemsMap[$key])) {
                $refundableItemsMap[$key] = [];
            }
            $refundableItemsMap[$key][] = $item;
        }

        // Group returns by PaymentTransactionId
        $returnsByTransaction = [];

        foreach ($changes->getReturns() as $return) {
            $key = $return->MerchantReference . '_' . $return->PricePerItemIncVat;

            // Check if the item exists in captured items
            if (!isset($refundableItemsMap[$key])) {
                throw new QliroException(
                    "Item with MerchantReference '{$return->MerchantReference}' and price {$return->PricePerItemIncVat} not found in captured items"
                );
            }

            // Calculate total captured quantity for this item
            $totalCapturedQty = 0;
            foreach ($refundableItemsMap[$key] as $capturedItem) {
                $totalCapturedQty += $capturedItem->Quantity;
            }

            // Check if return quantity is valid
            if ($return->Quantity > $totalCapturedQty) {
                throw new QliroException(
                    "Return quantity {$return->Quantity} exceeds total captured quantity {$totalCapturedQty} for item with MerchantReference '{$return->MerchantReference}' and price {$return->PricePerItemIncVat}"
                );
            }

            // Distribute return quantity across captured items
            $remainingQty = $return->Quantity;
            foreach ($refundableItemsMap[$key] as $capturedItem) {
                if ($remainingQty <= 0) {
                    break;
                }

                // Determine quantity to return from this captured item
                $qtyToReturn = min($remainingQty, $capturedItem->Quantity);
                $remainingQty -= $qtyToReturn;

                // Add to returns by transaction
                $transactionId = $capturedItem->PaymentTransactionId;
                if (!isset($returnsByTransaction[$transactionId])) {
                    $returnsByTransaction[$transactionId] = [];
                }

                // Create a new OrderItemDto for the return
                $returnsByTransaction[$transactionId][] = new OrderItemDto(
                    Description: $capturedItem->Description,
                    MerchantReference: $capturedItem->MerchantReference,
                    PaymentTransactionId: $capturedItem->PaymentTransactionId,
                    PricePerItemExVat: $capturedItem->PricePerItemExVat,
                    PricePerItemIncVat: $capturedItem->PricePerItemIncVat,
                    Quantity: $qtyToReturn,
                    Type: $capturedItem->Type,
                    VatRate: $capturedItem->VatRate
                );
            }
        }

        // Create ReturnDto objects for each transaction
        $returns = [];
        foreach ($returnsByTransaction as $transactionId => $items) {
            $returns[] = new ReturnDto(
                PaymentTransactionId: $transactionId,
                OrderItems: $items
            );
        }

        // Create ReturnItemsDto
        return new ReturnItemsDto(
            OrderId: $this->orderId() ?? 0,
            Currency: $this->currency() ?? 'NOK',
            Returns: $returns
        );
    }
}
