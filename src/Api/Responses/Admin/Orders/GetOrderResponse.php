<?php

namespace Gets\QliroApi\Api\Responses\Admin\Orders;

use Gets\QliroApi\Dtos\Order\AdminOrderDetailsDto;
use Gets\QliroApi\Models\Order;
use Saloon\Http\Response;

class GetOrderResponse
{
    public Order $order;
    public function __construct(
        public AdminOrderDetailsDto $dto,
        public Response $response
    ) {
        $this->order = new Order($this->dto);
    }
    public static function fromResponse(Response $response): self
    {
        $orderDto = AdminOrderDetailsDto::fromStdClass($response->json());

        return new self($orderDto, $response);
    }

    public function toArray(): array
    {
        return $this->dto->toArray();
    }
}
