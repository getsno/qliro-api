<?php

namespace Gets\QliroApi\Api\Requests\Merchant\Orders;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateOrderRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    private string $orderId;
    private array $orderData;

    public function __construct(string $orderId, array $orderData)
    {
        $this->orderId = $orderId;
        $this->orderData = $orderData;
    }

    public function resolveEndpoint(): string
    {
        return "/checkout/merchantapi/Orders/{$this->orderId}";
    }

    protected function defaultBody(): array
    {
        return $this->orderData;
    }
}
