<?php

namespace Gets\QliroApi\Models\Order;

readonly class MarkItemsAsShippedDto
{
    public function __construct(
        public int      $OrderId,
        public string   $Currency,
        /** @var ShipmentDto[] */
        public array    $Shipments,
    )
    {
    }

    public static function fromStdClass(object|array $data): self
    {
        if (is_array($data)) {
            $data = (object)$data;
        }

        $shipments = [];
        if (isset($data->Shipments) && is_array($data->Shipments)) {
            $shipments = array_map(
                static fn($shipment) => ShipmentDto::fromStdClass($shipment),
                $data->Shipments
            );
        }

        return new self(
            OrderId: $data->OrderId,
            Currency: $data->Currency,
            Shipments: $shipments,
        );
    }

    public function toArray(): array
    {
        // Convert ShipmentDto objects back to arrays
        $shipments = array_map(
            static fn(ShipmentDto $shipment) => $shipment->toArray(),
            $this->Shipments
        );

        return [
            'OrderId'   => $this->OrderId,
            'Currency'  => $this->Currency,
            'Shipments' => $shipments,
        ];
    }
}
