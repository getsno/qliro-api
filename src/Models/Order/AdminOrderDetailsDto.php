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
        public ?array       $OrderItemActions = null,
        /** @var MerchantProvidedMetadataDto[] */
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
            OrderItemActions: $data->OrderItemActions ?? null,
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

        return [
            'OrderId'                  => $this->OrderId,
            'MerchantReference'        => $this->MerchantReference,
            'Country'                  => $this->Country,
            'Currency'                 => $this->Currency,
            'BillingAddress'           => $this->BillingAddress?->toArray(),
            'ShippingAddress'          => $this->ShippingAddress?->toArray(),
            'Customer'                 => $this->Customer?->toArray(),
            'PaymentTransactions'      => $this->PaymentTransactions,
            'OrderItemActions'         => $this->OrderItemActions,
            'MerchantProvidedMetadata' => $merchantProvidedMetadata,
            'IdentityVerification'      => $this->IdentityVerification,
            'Upsell'                   => $this->Upsell,
        ];
    }
}
