<?php

namespace Gets\QliroApi\Dtos\Order;

readonly class UpdateDto
{
    public function __construct(
        public int   $PaymentTransactionId,
        /** @var OrderItemDto[] */
        public array $OrderItems,
    )
    {
    }

    public static function fromStdClass(object|array $data): self
    {
        if (is_array($data)) {
            $data = (object)$data;
        }

        // Convert OrderItems array to OrderItemDto objects
        $orderItems = [];
        if (isset($data->OrderItems) && is_array($data->OrderItems)) {
            $orderItems = array_map(
                static fn($item) => OrderItemDto::fromStdClass($item),
                $data->OrderItems
            );
        }

        return new self(
            PaymentTransactionId: $data->PaymentTransactionId,
            OrderItems: $orderItems,
        );
    }

    public function toArray(): array
    {
        // Convert OrderItemDto objects back to arrays
        $orderItems = array_map(
            static fn(OrderItemDto $item) => $item->toArray(),
            $this->OrderItems
        );

        return [
            'PaymentTransactionId' => $this->PaymentTransactionId,
            'OrderItems'           => $orderItems,
        ];
    }
}
