<?php

namespace Gets\QliroApi\Api\Requests\Merchant\Orders;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreateOrderRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    private array $orderData;

    public function __construct(array $orderData)
    {
        $this->orderData = $orderData;
    }

    public function resolveEndpoint(): string
    {
        return "/checkout/merchantapi/Orders";
    }

    protected function defaultBody(): array
    {
        return $this->orderData;
    }
}
