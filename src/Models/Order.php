<?php

namespace Gets\QliroApi\Models;

use Gets\QliroApi\Dtos\Order\AddressDto;
use Gets\QliroApi\Dtos\Order\AdminOrderDetailsDto;
use Gets\QliroApi\Dtos\Order\CustomerDto;
use Gets\QliroApi\Dtos\Order\MerchantProvidedMetadataDto;
use Gets\QliroApi\Dtos\Order\OrderItemActionDto;
use Gets\QliroApi\Dtos\Order\OrderItemDto;
use Gets\QliroApi\Dtos\Order\PaymentTransactionDto;
use Gets\QliroApi\Enums\OrderItemActionType;

class Order
{
    public AdminOrderDetailsDto $dto;

    public function __construct(AdminOrderDetailsDto $dto)
    {
        $this->dto = $dto;
    }

    /**
     * Retrieves the order ID.
     *
     * @return int|null The order ID or null if not available.
     */
    public function orderId(): ?int
    {
        return $this->dto->OrderId;
    }

    /**
     * Retrieves the merchant reference.
     *
     * @return string|null The merchant reference or null if not available.
     */
    public function merchantReference(): ?string
    {
        return $this->dto->MerchantReference;
    }

    /**
     * Retrieves the country.
     *
     * @return string|null The country or null if not available.
     */
    public function country(): ?string
    {
        return $this->dto->Country;
    }

    /**
     * Retrieves the currency.
     *
     * @return string|null The currency or null if not available.
     */
    public function currency(): ?string
    {
        return $this->dto->Currency;
    }

    /**
     * Retrieves the billing address.
     *
     * @return AddressDto|null The billing address or null if not available.
     */
    public function billingAddress(): ?AddressDto
    {
        return $this->dto->BillingAddress;
    }

    /**
     * Retrieves the shipping address.
     *
     * @return AddressDto|null The shipping address or null if not available.
     */
    public function shippingAddress(): ?AddressDto
    {
        return $this->dto->ShippingAddress;
    }

    /**
     * Retrieves the customer information.
     *
     * @return CustomerDto|null The customer information or null if not available.
     */
    public function customer(): ?CustomerDto
    {
        return $this->dto->Customer;
    }

    /**
     * Retrieves the payment transactions.
     *
     * @return PaymentTransactionDto[]|null An array of payment transactions or null if no transactions are available.
     */
    public function paymentTransactions(): ?array
    {
        return $this->dto->PaymentTransactions;
    }

    /**
     * Retrieves the order item actions.
     *
     * @return OrderItemActionDto[]|null An array of order item actions or null if no actions are available.
     */
    public function orderItemActions(): ?array
    {
        return $this->dto->OrderItemActions;
    }

    /**
     * Retrieves the merchant provided metadata.
     *
     * @return MerchantProvidedMetadataDto[]|null An array of merchant provided metadata or null if no metadata is available.
     */
    public function merchantProvidedMetadata(): ?array
    {
        return $this->dto->MerchantProvidedMetadata;
    }

    /**
     * Retrieves the identity verification information.
     *
     * @return array|null The identity verification information or null if not available.
     */
    public function identityVerification(): ?array
    {
        return $this->dto->IdentityVerification;
    }

    /**
     * Retrieves the upsell information.
     *
     * @return array|null The upsell information or null if not available.
     */
    public function upsell(): ?array
    {
        return $this->dto->Upsell;
    }

    public function getTransactionStatus(int $paymentTransactionId): ?string
    {
        $transactions = $this->paymentTransactions();

        if (!$transactions) {
            return null;
        }

        foreach ($transactions as $transaction) {
            if ($transaction->PaymentTransactionId === $paymentTransactionId) {
                return $transaction->Status;
            }
        }

        return null;
    }

    public function getOriginalOrderAmount() : float
    {
        $transactions = $this->paymentTransactions();
        if (!$transactions) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($transactions as $transaction) {
            if ($transaction->Type === 'Preauthorization' && $transaction->Amount !== null) {
                $total += $transaction->Amount;
            }
        }

        return $total;
    }

    public function getCapturedAmount(): float
    {
        $transactions = $this->paymentTransactions();
        if (!$transactions) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($transactions as $transaction) {
            if ($transaction->Type === 'Capture' && $transaction->Amount !== null) {
                $total += $transaction->Amount;
            }
        }

        return $total;
    }

    public function getRefundedAmount(): float
    {
        $transactions = $this->paymentTransactions();
        if (!$transactions) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($transactions as $transaction) {
            if ($transaction->Type === 'Refund' && $transaction->Amount !== null) {
                $total += $transaction->Amount;
            }
        }

        return $total;
    }

    public function getCancelledAmount(): float
    {
        $transactions = $this->paymentTransactions();
        if (!$transactions) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($transactions as $transaction) {
            if ($transaction->Type === 'Reversal' && $transaction->Amount !== null) {
                $total += $transaction->Amount;
            }
        }

        return $total;
    }

    public function getRemainingAmount(): float
    {
        return $this->getOriginalOrderAmount() - $this->getCapturedAmount() - $this->getCancelledAmount();
    }

    public function getTotalAmount(): float
    {
        return $this->getCapturedAmount() - $this->getRefundedAmount() + $this->getRemainingAmount();
    }

    /**
     * Get current order items based on reserved, shipped, and released quantities.
     *
     * @return OrderItemDto[] Array of current order items with adjusted quantities
     */
    public function currentOrderItems(): array
    {
        $orderItemActions = $this->orderItemActions();
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
                    'reserved' => 0,
                    'shipped' => 0,
                    'released' => 0,
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

    public function cancelledOrderItems(): array
    {
        //based on OrderItemActions from dto need to find Items with type "Reversal" and provide an array of OrderItemDtos
        //as identity, we should use MerchantReference and PricePerItemExVat and PaymentTransactionId
        //because cancelation may be used in different transactions
    }

    public function refundedOrderItems(): array
    {
        //based on OrderItemActions from dto need to find Items with type "Refund" and provide an array of OrderItemDtos
        //as identity, we should use MerchantReference and PricePerItemExVat and PaymentTransactionId
        //because cancelation may be used in different transactions
    }

    public function capturedOrderItems(): array
    {
        //based on OrderItemActions from dto need to find Items with type "Capture" and provide an array of OrderItemDtos
        //as identity, we should use MerchantReference and PricePerItemExVat and PaymentTransactionId
        //because cancelation may be used in different transactions
    }

    public function reservedOrderItems(): array
    {
        //based on OrderItemActions from dto need to find Items with type "Reserve" and provide array of OrderItemDtos
        //as identity, we should use MerchantReference and PricePerItemExVat and PaymentTransactionId
        //because cancelation may be used in different transactions
    }

}
