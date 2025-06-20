<?php

namespace Gets\QliroApi\Api\Requests\Admin\Orders;

use Gets\QliroApi\Traits\HasRequestId;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class ReturnItemsRequest extends Request implements HasBody
{
    use HasJsonBody, HasRequestId;

    protected Method $method = Method::POST;
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function resolveEndpoint(): string
    {
        return "/checkout/adminapi/v2/ReturnItems";
    }

    protected function defaultBody(): array
    {
        return $this->addRequestIdToBody($this->data);
    }
}
