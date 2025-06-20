<?php

namespace Gets\QliroApi\Models\Order;

readonly class MerchantOrderDetailsDto
{
    public function __construct(
        public ?AddressDto       $BillingAddress = null,
        public ?string           $Country = null,
        public ?string           $Currency = null,
        public ?CustomerDto      $Customer = null,
        public ?string           $CustomerCheckoutStatus = null,
        public ?array            $IdentityVerification = null,
        public ?string           $Language = null,
        /** @var MerchantProvidedMetadataDto[] */
        public ?array            $MerchantProvidedMetadata = null,
        public ?bool             $MerchantProvidedQuestionAnswer = null,
        public ?string           $MerchantReference = null,
        public ?string           $OrderHtmlSnippet = null,
        public ?int              $OrderId = null,
        /** @var OrderItemDto[] */
        public ?array            $OrderItems = null,
        public ?string           $PaymentLink = null,
        public ?PaymentMethodDto $PaymentMethod = null,
        public ?AddressDto       $ShippingAddress = null,
        public ?int              $TotalPrice = null,
        public ?bool             $SignupForNewsletter = null,
    )
    {
    }

    public static function fromStdClass(object|array $data): self
    {
        if (is_array($data)) {
            $data = (object)$data;
        }

        // Convert OrderItems array to OrderItemDto objects
        $orderItems = null;
        if (isset($data->OrderItems) && is_array($data->OrderItems)) {
            $orderItems = array_map(
                fn($item) => OrderItemDto::fromStdClass($item),
                $data->OrderItems
            );
        }

        // Convert MerchantProvidedMetadata array to MerchantProvidedMetadataDto objects
        $merchantProvidedMetadata = null;
        if (isset($data->MerchantProvidedMetadata) && is_array($data->MerchantProvidedMetadata)) {
            $merchantProvidedMetadata = array_map(
                static fn($item) => MerchantProvidedMetadataDto::fromStdClass($item),
                $data->MerchantProvidedMetadata
            );
        }

        // Helper method to normalize data for DTO creation
        $normalizeData = static function ($field) use ($data) {
            if (!isset($data->$field)) {
                return null;
            }
            return is_array($data->$field) ? (object)$data->$field : $data->$field;
        };

        return new self(
            BillingAddress: AddressDto::fromStdClass($normalizeData('BillingAddress')),
            Country: $data->Country ?? null,
            Currency: $data->Currency ?? null,
            Customer: CustomerDto::fromStdClass($normalizeData('Customer')),
            CustomerCheckoutStatus: $data->CustomerCheckoutStatus ?? null,
            IdentityVerification: $data->IdentityVerification ?? null,
            Language: $data->Language ?? null,
            MerchantProvidedMetadata: $merchantProvidedMetadata,
            MerchantProvidedQuestionAnswer: $data->MerchantProvidedQuestionAnswer ?? null,
            MerchantReference: $data->MerchantReference ?? null,
            OrderHtmlSnippet: $data->OrderHtmlSnippet ?? null,
            OrderId: $data->OrderId ?? null,
            OrderItems: $orderItems,
            PaymentLink: $data->PaymentLink ?? null,
            PaymentMethod: PaymentMethodDto::fromStdClass($normalizeData('PaymentMethod')),
            ShippingAddress: AddressDto::fromStdClass($normalizeData('ShippingAddress')),
            TotalPrice: $data->TotalPrice ?? null,
            SignupForNewsletter: $data->SignupForNewsletter ?? null,
        );
    }

    public function toArray(): array
    {
        // Convert OrderItemDto objects back to arrays
        $orderItems = null;
        if ($this->OrderItems) {
            $orderItems = array_map(
                fn(OrderItemDto $item) => $item->toArray(),
                $this->OrderItems
            );
        }

        // Convert MerchantProvidedMetadataDto objects back to arrays
        $merchantProvidedMetadata = null;
        if ($this->MerchantProvidedMetadata) {
            $merchantProvidedMetadata = array_map(
                static fn(MerchantProvidedMetadataDto $item) => $item->toArray(),
                $this->MerchantProvidedMetadata
            );
        }

        return [
            'BillingAddress'           => $this->BillingAddress?->toArray(),
            'Country'                  => $this->Country,
            'Currency'                 => $this->Currency,
            'Customer'                 => $this->Customer?->toArray(),
            'CustomerCheckoutStatus'   => $this->CustomerCheckoutStatus,
            'IdentityVerification'     => $this->IdentityVerification,
            'Language'                 => $this->Language,
            'MerchantProvidedMetadata' => $merchantProvidedMetadata,
            'MerchantReference'        => $this->MerchantReference,
            'OrderHtmlSnippet'         => $this->OrderHtmlSnippet,
            'OrderId'                  => $this->OrderId,
            'OrderItems'               => $orderItems,
            'PaymentLink'              => $this->PaymentLink,
            'PaymentMethod'            => $this->PaymentMethod?->toArray(),
            'ShippingAddress'          => $this->ShippingAddress?->toArray(),
            'TotalPrice'               => $this->TotalPrice,
        ];
    }
}
