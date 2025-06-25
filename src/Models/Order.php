<?php

namespace Gets\QliroApi\Models;

use Gets\QliroApi\Builders\OrderCaptureDtoBuilder;
use Gets\QliroApi\Builders\OrderReturnDtoBuilder;
use Gets\QliroApi\Builders\OrderUpdateDtoBuilder;
use Gets\QliroApi\Dtos\Order\AddressDto;
use Gets\QliroApi\Dtos\Order\AdminOrderDetailsDto;
use Gets\QliroApi\Dtos\Order\CustomerDto;
use Gets\QliroApi\Dtos\Order\MarkItemsAsShippedDto;
use Gets\QliroApi\Dtos\Order\OrderItemDto;
use Gets\QliroApi\Dtos\Order\ReturnItemsDto;
use Gets\QliroApi\Dtos\Order\UpdateItemsDto;
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

    public function getTransactionType(int $paymentTransactionId): ?string
    {
        return $this->paymentTransactions->getType($paymentTransactionId);
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
        return (new OrderUpdateDtoBuilder($this))->build($changes);
    }

    /**
     * @throws QliroException
     */
    public function buildReturnDto(OrderReturns $changes): ReturnItemsDto
    {
        return (new OrderReturnDtoBuilder($this))->build($changes);
    }

    public function buildCaptureDto(OrderCaptures $captures): MarkItemsAsShippedDto
    {
        return (new OrderCaptureDtoBuilder($this))->build($captures);
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
