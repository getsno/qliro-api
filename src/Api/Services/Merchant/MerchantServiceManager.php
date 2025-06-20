<?php

namespace Gets\QliroApi\Api\Services\Merchant;

use Gets\QliroApi\Api\QliroConnector;
use Gets\QliroApi\Api\Resources\Merchant\OrdersResource;

class MerchantServiceManager
{
    private QliroConnector $connector;
    private ?OrdersResource $ordersResource = null;

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
}
