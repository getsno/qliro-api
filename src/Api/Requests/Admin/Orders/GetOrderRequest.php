<?php

namespace Gets\QliroApi\Api\Requests\Admin\Orders;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetOrderRequest extends Request
{
    protected Method $method = Method::GET;
    private int $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function resolveEndpoint(): string
    {
        return "/checkout/adminapi/v2/orders/{$this->orderId}";
    }
}
