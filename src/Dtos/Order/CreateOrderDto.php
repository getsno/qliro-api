<?php

namespace Gets\QliroApi\Dtos\Order;

use Gets\QliroApi\Traits\Fillable;

class CreateOrderDto
{
    use Fillable;

    public ?string $MerchantApiKey = null;
    public ?string $MerchantReference = null;
    public string $Currency = 'NOK';
    public string $Country = 'NO';
    public string $Language = 'nb-no';
    public ?string $MerchantTermsUrl = null;
    public ?string $MerchantConfirmationUrl = null;
    public ?string $MerchantCheckoutStatusPushUrl = null;
    public ?string $MerchantOrderManagementStatusPushUrl = null;
    /** @var OrderItemDto[] */
    public array $OrderItems = [];

    public function __construct(array $orderData = [])
    {
        if (!empty($orderData)) {
            $this->fillFromArray($orderData);
        }
    }

    public function addOrderItem(OrderItemDto $orderItem): self
    {
        $this->OrderItems[] = $orderItem;
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'MerchantApiKey'                       => $this->MerchantApiKey,
            'MerchantReference'                    => $this->MerchantReference,
            'Currency'                             => $this->Currency,
            'Country'                              => $this->Country,
            'Language'                             => $this->Language,
            'MerchantTermsUrl'                     => $this->MerchantTermsUrl,
            'MerchantConfirmationUrl'               => $this->MerchantConfirmationUrl,
            'MerchantCheckoutStatusPushUrl'        => $this->MerchantCheckoutStatusPushUrl,
            'MerchantOrderManagementStatusPushUrl' => $this->MerchantOrderManagementStatusPushUrl,
            'OrderItems'                           => array_map(static fn($item) => $item->toArray(), $this->OrderItems),
        ];

        // Remove null values
        return array_filter($data, function ($value) {
            return $value !== null;
        });
    }
}
