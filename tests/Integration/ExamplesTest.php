<?php

namespace Gets\QliroApi\Tests\Integration;

use Gets\QliroApi\Api\QliroApi;
use Gets\QliroApi\Exceptions\QliroException;
use Gets\QliroApi\Models\OrderCaptures;
use Gets\QliroApi\Models\OrderChanges;
use Gets\QliroApi\Models\OrderReturns;
use Gets\QliroApi\Services\TransactionRetryService;
use Gets\QliroApi\Tests\QliroApiTestCase;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

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
        $this->markTestSkipped();
        $orderRef = '87GW2Y8K';
        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
        $captures = new OrderCaptures();
        $captures->add('7057320926803', 99, 1);
        $captures->add('7321464252051', 200, 1);
        $dto = $order->buildCaptureDto($captures);
        $result = $this->client->admin()->orders()->markItemsAsShipped($dto)->dto;
        $retryTransactions = new TransactionRetryService($this->client);
        $retryResults = $retryTransactions->processFailedTransactions($orderRef, $result->PaymentTransactions);

        $test = 1;
    }

    /**
     * @throws QliroException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function testRefund(): void
    {
        $this->markTestSkipped();
        $orderRef = 'F97MMXTT';
        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
        $returns = new OrderReturns();
        $returns->add('7057321129791', 75, 1);
        $returns->add('7057320717166', 75, 1);
        $dto = $order->buildReturnDto($returns);
        $result = $this->client->admin()->orders()->returnItems($dto)->dto;
        $retryTransactions = new TransactionRetryService($this->client);
        $retryResults = $retryTransactions->processFailedTransactions($orderRef, $result->PaymentTransactions);
    }

    public function testUpdate(): void
    {
        $this->markTestSkipped();
        $orderRef = 'YNBA2T6G';
        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
        $updates = new OrderChanges();
        $updates->decrease('7057320926803', 99, 1);
        $updates->decrease('7057320108056', 25, 1);
        $dto = $order->buildUpdateDto($updates);
        if (empty($dto->Updates)) {
            $result = $this->client->admin()->orders()->cancelOrder($dto->OrderId)->dto;
        } else {
            $result = $this->client->admin()->orders()->updateItems($dto)->dto;
        }
        $order = $this->client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
    }
}
