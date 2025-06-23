<?php

namespace Gets\QliroApi\Api\Responses\Admin\Orders;

use Gets\QliroApi\Dtos\Order\AdminOrderDetailsDto;
use Gets\QliroApi\Dtos\Transaction\TransactionsCreatedDto;
use Gets\QliroApi\Models\Order;
use Saloon\Http\Response;

class TransactionsCreatedResponse
{
    public Order $order;
    public function __construct(
        public TransactionsCreatedDto $dto,
        public Response $response
    ) {
    }
    public static function fromResponse(Response $response): self
    {
        $dto = TransactionsCreatedDto::fromStdClass($response->json());

        return new self($dto, $response);
    }

    public function toArray(): array
    {
        return $this->dto->toArray();
    }
}
