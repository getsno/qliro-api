<?php

namespace Gets\QliroApi\Tests\Integration;

use Gets\QliroApi\Api\QliroApi;
use Gets\QliroApi\Models\OrderCaptures;
use Gets\QliroApi\Services\TransactionRetryService;
use Gets\QliroApi\Tests\QliroApiTestCase;

class ExamplesTest extends QliroApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNoActualApiCalls();
        $config = $this->getApiConfig();
        $this->client = new QliroApi($config);
    }
    public function testCapture(): void
    {
        $orderRef='F97MMXTT';
        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
        $captures = new OrderCaptures();
        $captures->add('7057320717166',75,5)
            ->add('7057320926803',99,1);
        $dto = $order->buildCaptureDto($captures);
        $result = $this->client->admin()->orders()->markItemsAsShipped($dto)->dto->toArray();
        $retryTransactions = new TransactionRetryService($this->client);
        $retryResults = $retryTransactions->processFailedTransactions($orderRef, $result);

    }
}
