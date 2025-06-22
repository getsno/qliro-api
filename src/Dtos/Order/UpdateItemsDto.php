<?php

namespace Gets\QliroApi\Dtos\Order;

readonly class UpdateItemsDto
{
    public function __construct(
        public int    $OrderId,
        public string $Currency,
        /** @var UpdateDto[] */
        public array  $Updates,
    )
    {
    }

    public static function fromStdClass(object|array $data): self
    {
        if (is_array($data)) {
            $data = (object)$data;
        }

        $updates = [];
        if (isset($data->Updates) && is_array($data->Updates)) {
            $updates = array_map(
                static fn($shipment) => ShipmentDto::fromStdClass($shipment),
                $data->Updates
            );
        }

        return new self(
            OrderId: $data->OrderId,
            Currency: $data->Currency,
            Updates: $updates,
        );
    }

    public function toArray(): array
    {
        // Convert ShipmentDto objects back to arrays
        $updates = array_map(
            static fn(ShipmentDto $shipment) => $shipment->toArray(),
            $this->Updates
        );

        return [
            'OrderId'  => $this->OrderId,
            'Currency' => $this->Currency,
            'Updates'  => $updates,
        ];
    }
}
