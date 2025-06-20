<?php

namespace Gets\QliroApi\Api\Requests\Admin\Orders;

use Gets\QliroApi\Traits\HasRequestId;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CancelOrderRequest extends Request implements HasBody
{
    use HasJsonBody;
    use HasRequestId;
    protected Method $method = Method::POST;

    private int $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function resolveEndpoint(): string
    {
        return "/checkout/adminapi/v2/cancelOrder";
    }

    protected function defaultBody(): array
    {
        return $this->addRequestIdToBody([
            'OrderId' => $this->orderId
        ]);
    }
}
