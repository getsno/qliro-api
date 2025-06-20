<?php

namespace Gets\QliroApi\Api\Requests\Merchant\Orders;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetOrderRequest extends Request
{
    protected Method $method = Method::GET;

    private string $orderId;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function resolveEndpoint(): string
    {
        return "/checkout/merchantapi/Orders/{$this->orderId}";
    }
}
