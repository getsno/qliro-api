<?php

namespace Gets\QliroApi\Models;

use Gets\QliroApi\Dtos\Order\AddressDto;
use Gets\QliroApi\Dtos\Order\AdminOrderDetailsDto;
use Gets\QliroApi\Dtos\Order\CustomerDto;
use Gets\QliroApi\Dtos\Order\MarkItemsAsShippedDto;
use Gets\QliroApi\Dtos\Order\OrderItemDto;
use Gets\QliroApi\Dtos\Order\PaymentTransactionDto;
use Gets\QliroApi\Dtos\Order\ReturnDto;
use Gets\QliroApi\Dtos\Order\ReturnItemsDto;
use Gets\QliroApi\Dtos\Order\ShipmentDto;
use Gets\QliroApi\Dtos\Order\UpdateDto;
use Gets\QliroApi\Dtos\Order\UpdateItemsDto;
use Gets\QliroApi\Enums\OrderChangeType;
use Gets\QliroApi\Enums\OrderStatus;
use Gets\QliroApi\Enums\PaymentTransactionType;
use Gets\QliroApi\Exceptions\QliroException;

class Order
{
    public AdminOrderDetailsDto $dto;
    private OrderPaymentTransactions $paymentTransactions;
    private OrderAmountCalculator $amountCalculator;
    private OrderItemsManager $itemsManager;
    private OrderStatusCalculator $statusCalculator;

    public function __construct(AdminOrderDetailsDto $dto)
    {
        $this->dto = $dto;
        $this->paymentTransactions = new OrderPaymentTransactions($dto->PaymentTransactions);
        $this->amountCalculator = new OrderAmountCalculator($this->paymentTransactions);
        $this->itemsManager = new OrderItemsManager($dto->OrderItemActions, $this->paymentTransactions);
        $this->statusCalculator = new OrderStatusCalculator($this->amountCalculator);
    }

    public function orderId(): ?int
    {
        return $this->dto->OrderId;
    }

    public function merchantReference(): ?string
    {
        return $this->dto->MerchantReference;
    }

    public function country(): ?string
    {
        return $this->dto->Country;
    }

    public function currency(): ?string
    {
        return $this->dto->Currency;
    }

    public function billingAddress(): ?AddressDto
    {
        return $this->dto->BillingAddress;
    }

    public function shippingAddress(): ?AddressDto
    {
        return $this->dto->ShippingAddress;
    }

    public function customer(): ?CustomerDto
    {
        return $this->dto->Customer;
    }

    public function merchantProvidedMetadata(): ?array
    {
        return $this->dto->MerchantProvidedMetadata;
    }

    public function identityVerification(): ?array
    {
        return $this->dto->IdentityVerification;
    }

    public function upsell(): ?array
    {
        return $this->dto->Upsell;
    }

    public function paymentTransactions(): OrderPaymentTransactions
    {
        return $this->paymentTransactions;
    }

    public function amounts(): OrderAmountCalculator
    {
        return $this->amountCalculator;
    }

    public function items(): OrderItemsManager
    {
        return $this->itemsManager;
    }

    public function status(): OrderStatus
    {
        return $this->statusCalculator->calculate();
    }

    public function orderItemActions(): ?array
    {
        return $this->dto->OrderItemActions;
    }

    public function getTransactionStatus(int $paymentTransactionId): ?string
    {
        return $this->paymentTransactions->getStatus($paymentTransactionId);
    }

    public function amountOriginal(): float
    {
        return $this->amountCalculator->original();
    }

    public function amountCaptured(): float
    {
        return $this->amountCalculator->captured();
    }

    public function amountRefunded(): float
    {
        return $this->amountCalculator->refunded();
    }

    public function amountCancelled(): float
    {
        return $this->amountCalculator->cancelled();
    }

    public function amountRemaining(): float
    {
        return $this->amountCalculator->remaining();
    }

    public function amountTotal(): float
    {
        return $this->amountCalculator->total();
    }

    /**
     * @return OrderItemDto[]
     */
    public function itemsCurrent(): array
    {
        return $this->itemsManager->current();
    }

    /**
     * @return OrderItemDto[]
     */
    public function itemsReserved(): array
    {
        return $this->itemsManager->reserved();
    }

    /**
     * @return OrderItemDto[]
     */
    public function itemsCancelled(): array
    {
        return $this->itemsManager->cancelled();
    }

    /**
     * @return OrderItemDto[]
     */
    public function itemsRefunded(): array
    {
        return $this->itemsManager->refunded();
    }

    /**
     * @return OrderItemDto[]
     */
    public function itemsCaptured(): array
    {
        return $this->itemsManager->captured();
    }

    /**
     * @return OrderItemDto[]
     */
    public function itemsEligableForRefund(): array
    {
        return $this->itemsManager->eligibleForRefund();
    }

    /**
     * @return OrderItemDto[]
     */
    public function itemsEligableForCapture(): array
    {
        return $this->itemsManager->eligibleForCapture();
    }

    public function buildUpdateDto(OrderChanges $changes): UpdateItemsDto
    {
        // Get reserved items
        $currentItems = $this->itemsEligableForCapture();

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
            OrderId: $this->orderId() ?? 0,
            Currency: $this->currency() ?? 'NOK',
            Updates: $updates
        );
    }

    public function buildReturnDto(OrderReturns $changes): ReturnItemsDto
    {
        $refundAbleItems = $this->itemsEligableForRefund();

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

    public function buildCaptureDto(OrderCaptures $captures): MarkItemsAsShippedDto
    {
        $currentItems = $this->itemsCurrent();

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

    /**
     * @throws QliroException
     */
    public function getChangesBasedOnTransaction(int $transactionId): OrderReturns|OrderCaptures
    {
        $transaction = $this->paymentTransactions->findById($transactionId);
        $orderItems = $this->itemsManager->byTransactionId($transactionId);
        $supportedTransactionTypes = [
            PaymentTransactionType::Refund->value,
            PaymentTransactionType::Capture->value,
        ];
        if (!in_array($transaction->Type, $supportedTransactionTypes, true)) {
            throw new QliroException('Unsupported transaction type');
        }
        $changes = match ($transaction->Type) {
            PaymentTransactionType::Refund->value => new OrderReturns(),
            PaymentTransactionType::Capture->value => new OrderCaptures(),
            default => throw new QliroException('Unsupported transaction type'),
        };
        foreach ($orderItems as $orderItem) {
            $changes->add($orderItem->MerchantReference, $orderItem->PricePerItemIncVat, $orderItem->Quantity);
        }
        return $changes;
    }

}
