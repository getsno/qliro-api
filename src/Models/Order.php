<?php

namespace Gets\QliroApi\Models;

use Gets\QliroApi\Dtos\Order\AddressDto;
use Gets\QliroApi\Dtos\Order\AdminOrderDetailsDto;
use Gets\QliroApi\Dtos\Order\CustomerDto;
use Gets\QliroApi\Dtos\Order\MerchantProvidedMetadataDto;
use Gets\QliroApi\Dtos\Order\OrderItemActionDto;
use Gets\QliroApi\Dtos\Order\PaymentTransactionDto;

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
        return $this->getOriginalOrderAmount() - $this->getCapturedAmount();
    }

    public function currentOrderItems()
    {
        //need to find all orderItmeActions with type = Reserve.
        //as identity of item we need to use MerchantReference and PricePerItemExVat
        // then need to filter out all orderItemAction with same identity, and OrderItemAction Type in (Ship,Release)
        // need to check Qty of shipped and Released items and keep only Reserved items with leftover qties.
        //based on leftover OrderItemActions we need to return array of OrderItemDto with proper info an qty
    }

}
