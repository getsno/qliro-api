<?php

namespace Gets\QliroApi\Api\Requests\Admin\Transactions;

use Gets\QliroApi\Traits\HasRequestId;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class RetryReversalPaymentRequest extends Request implements HasBody
{
    use HasJsonBody, HasRequestId;

    protected Method $method = Method::POST;
    private array $data;

    public function __construct(int $paymentReference,)
    {
        $this->data = [
            'PaymentReference'              => $paymentReference,
        ];
    }

    public function resolveEndpoint(): string
    {
        return "/checkout/adminapi/v2/retryReversalPaymentTransaction";
    }

    protected function defaultBody(): array
    {
        return $this->addRequestIdToBody($this->data);
    }
}
