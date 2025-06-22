<?php

namespace Gets\QliroApi\Dtos\Order;

readonly class ReturnDto
{
    public function __construct(
        public int   $PaymentTransactionId,
        /** @var OrderItemDto[] */
        public array $OrderItems,
        /** @var DiscountDto[] */
        public array $Discounts = [],
    )
    {
    }

    public static function fromStdClass(object|array $data): self
    {
        if (is_array($data)) {
            $data = (object)$data;
        }

        $orderItems = [];
        if (isset($data->OrderItems) && is_array($data->OrderItems)) {
            $orderItems = array_map(
                static fn($item) => OrderItemDto::fromStdClass($item),
                $data->OrderItems
            );
        }

        $discounts = [];
        if (isset($data->Discounts) && is_array($data->Discounts)) {
            $discounts = array_map(
                static fn($discount) => DiscountDto::fromStdClass($discount),
                $data->Discounts
            );
        }

        return new self(
            PaymentTransactionId: $data->PaymentTransactionId,
            OrderItems: $orderItems,
            Discounts: $discounts,
        );
    }

    public function toArray(): array
    {
        return [
            'PaymentTransactionId' => $this->PaymentTransactionId,
            'OrderItems'           => array_map(
                static fn(OrderItemDto $item) => $item->toArray(),
                $this->OrderItems
            ),
            'Discounts'            => array_map(
                static fn(DiscountDto $discount) => $discount->toArray(),
                $this->Discounts
            ),
        ];
    }
}
