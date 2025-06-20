<?php

namespace Gets\QliroApi\Api\Requests\Merchant\Orders;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetOrderByMerchantReferenceRequest extends Request
{
    protected Method $method = Method::GET;

    private string $merchantReference;

    public function __construct(string $merchantReference)
    {
        $this->merchantReference = $merchantReference;
    }

    public function resolveEndpoint(): string
    {
        return "/checkout/merchantapi/Orders?merchantReference={$this->merchantReference}";
    }
}
