<?php

namespace Gets\QliroApi\Api\Resources\Admin;

use Gets\QliroApi\Api\Requests\Admin\Transactions\RetryReversalPaymentRequest;

use Saloon\Http\BaseResource;
use Saloon\Http\Response;

class TransactionsResource extends BaseResource
{
    public function retryReversalPayment(int $paymentReference): Response
    {
        return $this->connector->send(new RetryReversalPaymentRequest($paymentReference));
    }
}
