<?php

namespace Gets\QliroApi\Api;

use Gets\QliroApi\Api\Config;
use Gets\QliroApi\Api\Services\Admin\AdminServiceManager;
use Gets\QliroApi\Api\Services\Merchant\MerchantServiceManager;
use Saloon\Http\Faking\MockClient;

class QliroApi
{
    private QliroConnector $connector;
    private ?MerchantServiceManager $merchantServiceManager = null;
    private ?AdminServiceManager $adminServiceManager = null;

    public function __construct(Config $config)
    {
        $this->connector = new QliroConnector($config);
    }

    public function merchant(): MerchantServiceManager
    {
        if ($this->merchantServiceManager === null) {
            $this->merchantServiceManager = new MerchantServiceManager($this->connector);
        }

        return $this->merchantServiceManager;
    }

    public function admin(): AdminServiceManager
    {
        if ($this->adminServiceManager === null) {
            $this->adminServiceManager = new AdminServiceManager($this->connector);
        }

        return $this->adminServiceManager;
    }

    public function withMockClient(MockClient $mockClient):void
    {
        $this->connector->withMockClient($mockClient);
    }
}
