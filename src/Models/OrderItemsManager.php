<?php

namespace Gets\QliroApi\Models;

use Gets\QliroApi\Dtos\Order\OrderItemActionDto;
use Gets\QliroApi\Dtos\Order\OrderItemDto;
use Gets\QliroApi\Dtos\Order\PaymentTransactionDto;
use Gets\QliroApi\Enums\OrderItemActionType;

class OrderItemsManager
{
    private ?array $orderItemActions;
    private OrderPaymentTransactions $transactions;

    public function __construct(?array $orderItemActions, OrderPaymentTransactions $transactions)
    {
        $this->orderItemActions = $orderItemActions;
        $this->transactions = $transactions;
    }

    /**
     * @return OrderItemActionDto[]|null
     */
    public function successfulActions(): ?array
    {
        // Get transactions after the last preauthorization
        $successfulTransactions = $this->transactions->successfullOnlyAfterLastPreauthorization();
        // Also include successful Capture, Reversal, Refund that were before last preauthorization
        $beforePreauthorization = $this->transactions->successfulCaptureReversalRefundBeforeLastPreauthorization();
        // Merge both sets of transactions

        if (!empty($beforePreauthorization)) {
            if ($successfulTransactions !== null) {
                $successfulTransactions = array_merge($successfulTransactions, $beforePreauthorization);
            } else {
                $successfulTransactions = $beforePreauthorization;
            }
        }

        if ($successfulTransactions === null) {
            return null;
        }

        $successfulTransIds = array_map(static function (PaymentTransactionDto $transaction) {
            return $transaction->PaymentTransactionId;
        }, $successfulTransactions);

        if ($this->orderItemActions === null) {
            return null;
        }

        return array_filter($this->orderItemActions, static function ($action) use ($successfulTransIds) {
            return in_array($action->PaymentTransactionId, $successfulTransIds, true);
        });
    }

    /**
     * Used to properly calculate current items, need to ignore previous captures and returns
     */
    public function successfulActionsAfterLastPreauthorization(): ?array
    {
        // Get transactions after the last preauthorization
        $successfulTransactions = $this->transactions->successfullOnlyAfterLastPreauthorization();

        if ($successfulTransactions === null) {
            return null;
        }

        $successfulTransIds = array_map(static function (PaymentTransactionDto $transaction) {
            return $transaction->PaymentTransactionId;
        }, $successfulTransactions);

        if ($this->orderItemActions === null) {
            return null;
        }

        return array_filter($this->orderItemActions, static function ($action) use ($successfulTransIds) {
            return in_array($action->PaymentTransactionId, $successfulTransIds, true);
        });
    }

    /**
     * @return OrderItemActionDto[]
     */
    public function successfullActionsByType(OrderItemActionType $actionType): array
    {
        $orderItemActions = $this->successfulActions();
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

    /**
     * @return OrderItemDto[]
     */
    public function current(): array
    {
        $orderItemActions = $this->successfulActionsAfterLastPreauthorization();
        if (!$orderItemActions) {
            return [];
        }

        // Group items by their identity (MerchantReference and PricePerItemExVat)
        $groupedItems = [];
        foreach ($orderItemActions as $action) {
            // Skip actions without required fields
            if (!$action->MerchantReference || $action->PricePerItemIncVat === null || $action->Quantity === null) {
                continue;
            }

            // Create a unique key for each item based on MerchantReference and PricePerItemIncVat
            $itemKey = $action->MerchantReference . '_' . $action->PricePerItemExVat;

            if (!isset($groupedItems[$itemKey])) {
                $groupedItems[$itemKey] = [
                    'reserved'      => 0,
                    'shipped'       => 0,
                    'released'      => 0,
                    'reserveAction' => null, // Store the Reserve action to use its properties later
                ];
            }

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

    /**
     * @return OrderItemDto[]
     */
    public function byActionType(OrderItemActionType $actionType): array
    {
        $orderItemActions = $this->successfullActionsByType($actionType);
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

    /**
     * @return OrderItemDto[]
     */
    public function byTransactionId(int $transactionId): array
    {
        $orderItemActions = $this->orderItemActions;
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
     * @return OrderItemDto[]
     */
    public function cancelled(): array
    {
        return $this->byActionType(OrderItemActionType::Release);
    }

    /**
     * @return OrderItemDto[]
     */
    public function refunded(): array
    {
        return $this->byActionType(OrderItemActionType::Return);
    }

    /**
     * @return OrderItemDto[]
     */
    public function captured(): array
    {
        return $this->byActionType(OrderItemActionType::Ship);
    }

    /**
     * @return OrderItemDto[]
     */
    public function reserved(): array
    {
        return $this->byActionType(OrderItemActionType::Reserve);
    }

    /**
     * @return OrderItemDto[]
     */
    public function eligibleForRefund(): array
    {
        $orderItemsCaptured = $this->successfullActionsByType(OrderItemActionType::Ship);
        $orderItemsRefunded = $this->successfullActionsByType(OrderItemActionType::Return);
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
            $transaction = $this->transactions->findById($capturedItem->PaymentTransactionId);
            if (!$transaction) {
                continue;
            }

            $capturedItemsMap[$key][] = [
                'item'        => $capturedItem,
                'transaction' => $transaction,
                'timestamp'   => $transaction->Timestamp,
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
                        'Description'          => $capturedItem->Description,
                        'MerchantReference'    => $capturedItem->MerchantReference,
                        'PaymentTransactionId' => $capturedItem->PaymentTransactionId,
                        'PricePerItemExVat'    => $capturedItem->PricePerItemExVat,
                        'PricePerItemIncVat'   => $capturedItem->PricePerItemIncVat,
                        'Quantity'             => $remainingQty,
                        'Type'                 => $capturedItem->Type,
                        'VatRate'              => $capturedItem->VatRate,
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

    // Add other item-related methods...
}
