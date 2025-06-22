<?php /** @noinspection PhpParamsInspection */

namespace Gets\QliroApi\Tests\Integration\Admin;

use Gets\QliroApi\Api\QliroApi;
use Gets\QliroApi\Api\Requests\Admin\Orders\UpdateMerchantReferenceRequest;
use Gets\QliroApi\Exceptions\InvalidInputException;
use Gets\QliroApi\Tests\QliroApiTestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class AdminOrdersReferenceTest extends QliroApiTestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNoActualApiCalls();
        $config = $this->getApiConfig();
        $this->client = new QliroApi($config);
    }

    public function testUpdateMerchantReferenceFailsWithInvalidItem(): void
    {
        $this->client->withMockClient(new MockClient([
            UpdateMerchantReferenceRequest::class => MockResponse::make(body: [
                'ErrorCode'      => 'INVALID_INPUT',
                'ErrorMessage'   => "Property: NewMerchantReference, Message: The NewMerchantReference field is required.",
                'ErrorReference' => '0b8e1d5a-ef73-47db-96e7-397507063d72',
            ], status: 400),
        ]));
        $data = [];
        $this->expectException(InvalidInputException::class);
        $this->client->admin()->orders()->updateMerchantReference(1234, '');
    }

    public function testUpdateMerchantReferenceSuccess(): void
    {
        $testOrderId = 4948837;
        $newReference = '12344';
        $this->client->withMockClient(new MockClient([
            UpdateMerchantReferenceRequest::class => MockResponse::make(body: [
                'discriminator' => 'UpdateMerchantReference',
            ], status: 200),
        ]));
        $response = $this->client->admin()->orders()->updateMerchantReference($testOrderId, $newReference);
        $this->assertEquals(200, $response->status());
    }
}
