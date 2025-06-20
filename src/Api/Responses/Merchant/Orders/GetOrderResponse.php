<?php
namespace Gets\QliroApi\Api\Responses\Merchant\Orders;

use Gets\QliroApi\Models\Order\MerchantOrderDetailsDto;
use Saloon\Http\Response;

class GetOrderResponse
{
    public function __construct(
        public MerchantOrderDetailsDto $order,
        public Response $response
    ) {}

    public static function fromResponse(Response $response): self
    {
        $orderDto = MerchantOrderDetailsDto::fromStdClass($response->json());

        return new self($orderDto, $response);
    }

    public function toArray(): array
    {
        return $this->order->toArray();
    }

}
