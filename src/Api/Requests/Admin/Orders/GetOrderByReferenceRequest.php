<?php

namespace Gets\QliroApi\Api\Requests\Admin\Orders;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetOrderByReferenceRequest extends Request
{
    protected Method $method = Method::GET;
    private string $merchantReference;

    public function __construct(string $merchantReference)
    {
        $this->merchantReference = $merchantReference;
    }

    public function resolveEndpoint(): string
    {
        return "/checkout/adminapi/v2/orders/{$this->merchantReference}";
    }
}
