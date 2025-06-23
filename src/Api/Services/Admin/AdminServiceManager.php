<?php

namespace Gets\QliroApi\Api\Services\Admin;

use Gets\QliroApi\Api\QliroConnector;
use Gets\QliroApi\Api\Resources\Admin\OrdersResource;
use Gets\QliroApi\Api\Resources\Admin\TransactionsResource;

class AdminServiceManager
{
    private QliroConnector $connector;
    private ?OrdersResource $ordersResource = null;
    private ?TransactionsResource $transactionsResource = null;

    public function __construct(QliroConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Get the Orders resource
     *
     * @return OrdersResource
     */
    public function orders(): OrdersResource
    {
        if ($this->ordersResource === null) {
            $this->ordersResource = new OrdersResource($this->connector);
        }
        return $this->ordersResource;
    }

    public function transactions(): TransactionsResource
    {
        if ($this->transactionsResource === null) {
            $this->transactionsResource = new TransactionsResource($this->connector);
        }
        return $this->transactionsResource;
    }
}
