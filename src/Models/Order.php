<?php

namespace Gets\QliroApi\Models;

use Gets\QliroApi\Dtos\Order\AddressDto;
use Gets\QliroApi\Dtos\Order\AdminOrderDetailsDto;
use Gets\QliroApi\Dtos\Order\CustomerDto;
use Gets\QliroApi\Dtos\Order\MarkItemsAsShippedDto;
use Gets\QliroApi\Dtos\Order\MerchantProvidedMetadataDto;
use Gets\QliroApi\Dtos\Order\OrderItemActionDto;
use Gets\QliroApi\Dtos\Order\OrderItemDto;
use Gets\QliroApi\Dtos\Order\PaymentTransactionDto;
use Gets\QliroApi\Dtos\Order\ReturnDto;
use Gets\QliroApi\Dtos\Order\ReturnItemsDto;
use Gets\QliroApi\Dtos\Order\ShipmentDto;
use Gets\QliroApi\Dtos\Order\UpdateDto;
use Gets\QliroApi\Dtos\Order\UpdateItemsDto;
use Gets\QliroApi\Enums\OrderChangeType;
use Gets\QliroApi\Enums\OrderItemActionType;
use Gets\QliroApi\Enums\OrderStatus;
use Gets\QliroApi\Enums\PaymentTransactionStatus;
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

    public function paymentTransactionsSuccessful(): ?array
    {
        if ($this->dto->PaymentTransactions === null) {
            return null;
        }
        return array_filter($this->dto->PaymentTransactions, static function (PaymentTransactionDto $transaction) {
            return $transaction->Status === PaymentTransactionStatus::Success->value;
        });
    }

    public function orderItemActions(): ?array
    {
        return $this->dto->OrderItemActions;
    }

    public function orderItemActionsSuccessful(): ?array
    {
        $successfulTransactions = $this->paymentTransactionsSuccessful();
        if ($successfulTransactions === null) {
            return null;
        }

        $successfullTransIds = array_map(static function (PaymentTransactionDto $transaction) {
            return $transaction->PaymentTransactionId;
        }, $successfulTransactions);

        $orderItemActions = $this->orderItemActions();
        if ($orderItemActions === null) {
            return null;
        }

        return array_filter($orderItemActions, static function (OrderItemActionDto $action) use ($successfullTransIds) {
            return in_array($action->PaymentTransactionId, $successfullTransIds, true);
        });
    }

    public function getTransactionStatus(int $paymentTransactionId): ?string
    {
        return $this->getPaymentTransactionById($paymentTransactionId)?->Status;
    }

    public function getPaymentTransactionById(int $paymentTransactionId): ?PaymentTransactionDto
    {
        $transactions = $this->paymentTransactions();
        if (!$transactions) {
            return null;
        }
        foreach ($transactions as $transaction) {
            if ($transaction->PaymentTransactionId === $paymentTransactionId) {
                return $transaction;
            }
        }
        return null;
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
     * Get current order items based on reserved, shipped, and released quantities.
     *
     * @return OrderItemDto[] Array of current order items with adjusted quantities
     */
    public function itemsCurrent(): array
    {
        $orderItemActions = $this->orderItemActionsSuccessful();
        if (!$orderItemActions) {
            return [];
        }

        // Group items by their identity (MerchantReference and PricePerItemExVat)
        $groupedItems = [];
        foreach ($orderItemActions as $action) {
            // Skip actions without required fields
            if (!$action->MerchantReference || $action->PricePerItemExVat === null || $action->Quantity === null) {
                continue;
            }

            // Create a unique key for each item based on MerchantReference and PricePerItemExVat
            $itemKey = $action->MerchantReference . '_' . $action->PricePerItemExVat;

            if (!isset($groupedItems[$itemKey])) {
                $groupedItems[$itemKey] = [
                    'reserved'      => 0,
                    'shipped'       => 0,
                    'released'      => 0,
                    'reserveAction' => null, // Store the Reserve action to use its properties later
                ];
            }

            // Update quantities based on action type
            if ($action->ActionType === OrderItemActionType::Reserve->value) {
                $groupedItems[$itemKey]['reserved'] += $action->Quantity;
                // Store the Reserve action for this item if not already set
                if ($groupedItems[$itemKey]['reserveAction'] === null) {
                    $groupedItems[$itemKey]['reserveAction'] = $action;
                }
            } elseif ($action->ActionType === OrderItemActionType::Ship->value) {
                $groupedItems[$itemKey]['shipped'] += $action->Quantity;
            } elseif ($action->ActionType === OrderItemActionType::Release->value) {
                $groupedItems[$itemKey]['released'] += $action->Quantity;
            }
        }

        // Create OrderItemDto objects for items with remaining quantities
        $currentItems = [];
        foreach ($groupedItems as $itemData) {
            // Calculate the remaining quantity (reserved - shipped - released)
            $remainingQty = $itemData['reserved'] - $itemData['shipped'] - $itemData['released'];

            // Only include items with remaining quantity > 0
            if ($remainingQty > 0) {
                // Use the Reserve action as the source of information
                $reserveAction = $itemData['reserveAction'];
                if ($reserveAction === null) {
                    continue; // Skip if no Reserve action is found
                }

                $currentItems[] = new OrderItemDto(
                    Description: $reserveAction->Description,
                    MerchantReference: $reserveAction->MerchantReference,
                    PaymentTransactionId: $reserveAction->PaymentTransactionId,
                    PricePerItemExVat: $reserveAction->PricePerItemExVat,
                    PricePerItemIncVat: $reserveAction->PricePerItemIncVat ?? 0.0,
                    Quantity: $remainingQty,
                    Type: $reserveAction->Type ?? 'Product',
                    VatRate: $reserveAction->VatRate
                );
            }
        }

        return $currentItems;
    }

    public function itemsCancelled(): array
    {
        return $this->getOrderItemsByActionType(OrderItemActionType::Release);
    }

    public function itemsRefunded(): array
    {
        return $this->getOrderItemsByActionType(OrderItemActionType::Return);
    }

    public function itemsCaptured(): array
    {
        return $this->getOrderItemsByActionType(OrderItemActionType::Ship);
    }

    public function itemsEligableForRefund(): array
    {
        $orderItemsCaptured = $this->getOrderItemActionsByActionType(OrderItemActionType::Ship);
        $orderItemsRefunded = $this->getOrderItemActionsByActionType(OrderItemActionType::Return);
        $orderItemsEligibleForRefund = [];

        // Group captured items by MerchantReference and PricePerItemIncVat
        $capturedItemsMap = [];
        foreach ($orderItemsCaptured as $capturedItem) {
            // Skip items without required fields
            if (!$capturedItem->MerchantReference ||
                $capturedItem->PricePerItemIncVat === null ||
                $capturedItem->Quantity === null ||
                $capturedItem->PaymentTransactionId === null) {
                continue;
            }

            $key = $capturedItem->MerchantReference . '_' . $capturedItem->PricePerItemIncVat;

            if (!isset($capturedItemsMap[$key])) {
                $capturedItemsMap[$key] = [];
            }

            // Get the transaction to check its timestamp
            $transaction = $this->getPaymentTransactionById($capturedItem->PaymentTransactionId);
            if (!$transaction) {
                continue;
            }

            $capturedItemsMap[$key][] = [
                'item' => $capturedItem,
                'transaction' => $transaction,
                'timestamp' => $transaction->Timestamp
            ];
        }

        // Group refunded items by MerchantReference and PricePerItemIncVat
        $refundedItemsMap = [];
        foreach ($orderItemsRefunded as $refundedItem) {
            // Skip items without required fields
            if (!$refundedItem->MerchantReference ||
                $refundedItem->PricePerItemIncVat === null ||
                $refundedItem->Quantity === null) {
                continue;
            }

            $key = $refundedItem->MerchantReference . '_' . $refundedItem->PricePerItemIncVat;

            if (!isset($refundedItemsMap[$key])) {
                $refundedItemsMap[$key] = 0;
            }

            $refundedItemsMap[$key] += $refundedItem->Quantity;
        }

        // Calculate remaining quantities for each captured item
        foreach ($capturedItemsMap as $key => $capturedItems) {
            $refundedQty = $refundedItemsMap[$key] ?? 0;

            // Sort captured items by timestamp (oldest first)
            usort($capturedItems, function ($a, $b) {
                return strcmp($a['timestamp'] ?? '', $b['timestamp'] ?? '');
            });

            // Process each captured item
            foreach ($capturedItems as $capturedItemData) {
                $capturedItem = $capturedItemData['item'];
                $capturedQty = $capturedItem->Quantity;

                // If there are refunded items, subtract them from the captured quantity
                if ($refundedQty > 0) {
                    if ($refundedQty >= $capturedQty) {
                        // This item is fully refunded
                        $refundedQty -= $capturedQty;
                        continue;
                    } else {
                        // This item is partially refunded
                        $remainingQty = $capturedQty - $refundedQty;
                        $refundedQty = 0;
                    }
                } else {
                    // No refunds for this item
                    $remainingQty = $capturedQty;
                }

                // Add to eligible items if there's a remaining quantity
                if ($remainingQty > 0) {
                    $orderItemsEligibleForRefund[] = [
                        'Description' => $capturedItem->Description,
                        'MerchantReference' => $capturedItem->MerchantReference,
                        'PaymentTransactionId' => $capturedItem->PaymentTransactionId,
                        'PricePerItemExVat' => $capturedItem->PricePerItemExVat,
                        'PricePerItemIncVat' => $capturedItem->PricePerItemIncVat,
                        'Quantity' => $remainingQty,
                        'Type' => $capturedItem->Type,
                        'VatRate' => $capturedItem->VatRate
                    ];
                }
            }
        }

        // Convert to OrderItemDto objects
        $filteredItems = [];
        foreach ($orderItemsEligibleForRefund as $action) {
            $filteredItems[] = new OrderItemDto(
                Description: $action['Description'],
                MerchantReference: $action['MerchantReference'],
                PaymentTransactionId: $action['PaymentTransactionId'],
                PricePerItemExVat: $action['PricePerItemExVat'],
                PricePerItemIncVat: $action['PricePerItemIncVat'] ?? 0.0,
                Quantity: $action['Quantity'],
                Type: $action['Type'] ?? 'Product',
                VatRate: $action['VatRate']
            );
        }

        return $filteredItems;
    }

    public function itemsReserved(): array
    {
        return $this->getOrderItemsByActionType(OrderItemActionType::Reserve);
    }

    public function itemsNotCancelled(): array
    {
        // Get reserved items
        $reservedItems = $this->itemsReserved();

        // Get cancelled items
        $cancelledItems = $this->itemsCancelled();

        // Create a map of cancelled items by merchant reference and price
        $cancelledItemsMap = [];
        foreach ($cancelledItems as $item) {
            $key = $item->MerchantReference . '_' . $item->PricePerItemExVat;
            if (!isset($cancelledItemsMap[$key])) {
                $cancelledItemsMap[$key] = 0;
            }
            $cancelledItemsMap[$key] += $item->Quantity;
        }

        // Create a result array of items that are reserved but not cancelled
        // (or where the cancelled quantity is less than the reserved quantity)
        $notCancelledItems = [];
        foreach ($reservedItems as $item) {
            $key = $item->MerchantReference . '_' . $item->PricePerItemExVat;
            $cancelledQty = $cancelledItemsMap[$key] ?? 0;

            // Skip items that have been fully cancelled
            if ($cancelledQty >= $item->Quantity) {
                continue;
            }

            // Calculate the remaining quantity
            $remainingQty = $item->Quantity - $cancelledQty;

            // Create a new OrderItemDto with the remaining quantity
            $notCancelledItems[] = new OrderItemDto(
                Description: $item->Description,
                MerchantReference: $item->MerchantReference,
                PaymentTransactionId: $item->PaymentTransactionId,
                PricePerItemExVat: $item->PricePerItemExVat,
                PricePerItemIncVat: $item->PricePerItemIncVat,
                Quantity: $remainingQty,
                Type: $item->Type,
                VatRate: $item->VatRate
            );
        }

        return $notCancelledItems;
    }

    protected function getOrderItemActionsByActionType(OrderItemActionType $actionType): array
    {
        $orderItemActions = $this->orderItemActionsSuccessful();
        if (!$orderItemActions) {
            return [];
        }
        $filteredItems = [];
        foreach ($orderItemActions as $action) {
            // Skip actions without required fields
            if (!$action->MerchantReference || $action->PricePerItemExVat === null || $action->Quantity === null) {
                continue;
            }
            if ($action->ActionType === $actionType->value) {
                $filteredItems[] = $action;
            }
        }
        return $filteredItems;
    }

    protected function getOrderItemsByActionType(OrderItemActionType $actionType): array
    {
        $orderItemActions = $this->getOrderItemActionsByActionType($actionType);
        $filteredItems = [];
        foreach ($orderItemActions as $action) {
            $filteredItems[] = new OrderItemDto(
                Description: $action->Description,
                MerchantReference: $action->MerchantReference,
                PaymentTransactionId: $action->PaymentTransactionId,
                PricePerItemExVat: $action->PricePerItemExVat,
                PricePerItemIncVat: $action->PricePerItemIncVat ?? 0.0,
                Quantity: $action->Quantity,
                Type: $action->Type ?? 'Product',
                VatRate: $action->VatRate
            );
        }

        return $filteredItems;
    }

    public function getUpdateDto(OrderChanges $changes): UpdateItemsDto
    {
        // Get reserved items
        $currentItems = $this->itemsNotCancelled();

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

    public function getReturnDto(OrderReturns $changes): ReturnItemsDto
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

    public function getCaptureDto(OrderCaptures $captures): MarkItemsAsShippedDto
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

    public function getOrderItemsForTransaction(int $transactionId): array
    {
        $orderItemActions = $this->orderItemActions();
        if (!$orderItemActions) {
            return [];
        }

        $transactionItems = [];
        foreach ($orderItemActions as $action) {
            // Skip actions without required fields or not matching the transaction ID
            if (!$action->MerchantReference ||
                $action->PricePerItemExVat === null ||
                $action->Quantity === null ||
                $action->PaymentTransactionId !== $transactionId) {
                continue;
            }

            $transactionItems[] = new OrderItemDto(
                Description: $action->Description,
                MerchantReference: $action->MerchantReference,
                PaymentTransactionId: $action->PaymentTransactionId,
                PricePerItemExVat: $action->PricePerItemExVat,
                PricePerItemIncVat: $action->PricePerItemIncVat ?? 0.0,
                Quantity: $action->Quantity,
                Type: $action->Type ?? 'Product',
                VatRate: $action->VatRate
            );
        }

        return $transactionItems;
    }

    /**
     * @throws QliroException
     */
    public function getChangesBasedOnTransaction(int $transactionId): OrderReturns|OrderCaptures
    {
        $transaction = $this->getPaymentTransactionById($transactionId);
        $orderItems = $this->getOrderItemsForTransaction($transactionId);
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
