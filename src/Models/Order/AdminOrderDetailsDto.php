<?php

namespace Gets\QliroApi\Models\Order;

readonly class AdminOrderDetailsDto
{
    public function __construct(
        public ?int         $OrderId = null,
        public ?string      $MerchantReference = null,
        public ?string      $Country = null,
        public ?string      $Currency = null,
        public ?AddressDto  $BillingAddress = null,
        public ?AddressDto  $ShippingAddress = null,
        public ?CustomerDto $Customer = null,
        public ?array       $PaymentTransactions = null,
        /** @var OrderItemActionDto[]|null */
        public ?array       $OrderItemActions = null,
        /** @var MerchantProvidedMetadataDto[]|null */
        public ?array       $MerchantProvidedMetadata = null,
        public ?array       $IdentityVerification = null,
        public ?array       $Upsell = null,
    )
    {
    }

    public static function fromStdClass(null|object|array $data): ?self
    {
        if (!$data) {
            return null;
        }

        if (is_array($data)) {
            $data = (object)$data;
        }

        // Convert MerchantProvidedMetadata array to MerchantProvidedMetadataDto objects
        $merchantProvidedMetadata = null;
        if (isset($data->MerchantProvidedMetadata) && is_array($data->MerchantProvidedMetadata)) {
            $merchantProvidedMetadata = array_map(
                static fn($item) => MerchantProvidedMetadataDto::fromStdClass($item),
                $data->MerchantProvidedMetadata
            );
        }

        // Convert OrderItemActions array to OrderItemActionDto objects
        $orderItemActions = null;
        if (isset($data->OrderItemActions) && is_array($data->OrderItemActions)) {
            $orderItemActions = array_map(
                static fn($item) => OrderItemActionDto::fromStdClass($item),
                $data->OrderItemActions
            );
        }

        $normalizeData = static function ($field) use ($data) {
            if (!isset($data->$field)) {
                return null;
            }
            return is_array($data->$field) ? (object)$data->$field : $data->$field;
        };

        return new self(
            OrderId: $data->OrderId ?? null,
            MerchantReference: $data->MerchantReference ?? null,
            Country: $data->Country ?? null,
            Currency: $data->Currency ?? null,
            BillingAddress: AddressDto::fromStdClass($normalizeData('BillingAddress')),
            ShippingAddress: AddressDto::fromStdClass($normalizeData('ShippingAddress')),
            Customer: CustomerDto::fromStdClass($normalizeData('Customer')),
            PaymentTransactions: $data->PaymentTransactions ?? null,
            OrderItemActions: $orderItemActions,
            MerchantProvidedMetadata: $merchantProvidedMetadata,
            IdentityVerification: $data->IdentityVerification ?? null,
            Upsell: $data->Upsell ?? null,
        );
    }

    public function toArray(): array
    {
        // Convert MerchantProvidedMetadataDto objects back to arrays
        $merchantProvidedMetadata = null;
        if ($this->MerchantProvidedMetadata) {
            $merchantProvidedMetadata = array_map(
                static fn(MerchantProvidedMetadataDto $item) => $item->toArray(),
                $this->MerchantProvidedMetadata
            );
        }

        // Convert OrderItemActionDto objects back to arrays
        $orderItemActions = null;
        if ($this->OrderItemActions) {
            $orderItemActions = array_map(
                static fn(OrderItemActionDto $item) => $item->toArray(),
                $this->OrderItemActions
            );
        }

        return [
            'OrderId'                  => $this->OrderId,
            'MerchantReference'        => $this->MerchantReference,
            'Country'                  => $this->Country,
            'Currency'                 => $this->Currency,
            'BillingAddress'           => $this->BillingAddress?->toArray(),
            'ShippingAddress'          => $this->ShippingAddress?->toArray(),
            'Customer'                 => $this->Customer?->toArray(),
            'PaymentTransactions'      => $this->PaymentTransactions,
            'OrderItemActions'         => $orderItemActions,
            'MerchantProvidedMetadata' => $merchantProvidedMetadata,
            'IdentityVerification'      => $this->IdentityVerification,
            'Upsell'                   => $this->Upsell,
        ];
    }
}
