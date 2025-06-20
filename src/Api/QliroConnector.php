<?php

namespace Gets\QliroApi\Api;

use Gets\QliroApi\Api\Config;
use Gets\QliroApi\Exceptions\QliroException;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Http\Response;
use Throwable;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
class QliroConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;

    public function resolveBaseUrl(): string
    {
        return $this->qliroConfig->baseUrl;
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    protected Config $qliroConfig;

    public function __construct(Config $config)
    {
        $this->qliroConfig = $config;
    }

    /**
     * @throws \JsonException
     */
    public function boot(PendingRequest $pendingRequest): void
    {
        $requestData = [];
        if ($pendingRequest->body() && method_exists($pendingRequest->body(), 'all')) {
            $requestData = $pendingRequest->body()->all();
            if (!isset($requestData['MerchantApiKey'])) {
                $requestData['MerchantApiKey'] = $this->getMerchantApiKey();
                $pendingRequest->body()->merge(['MerchantApiKey' => $this->getMerchantApiKey()]);
                $requestData = $pendingRequest->body()->all();
            }

        }

        $authToken = $this->generateAuthToken($requestData);
        $pendingRequest->headers()->add('Authorization', 'Qliro ' . $authToken);
    }

    public function getRequestException(Response $response, ?Throwable $senderException): QliroException
    {
        return QliroException::fromResponse($response, $senderException);
    }

    /**
     * @throws \JsonException
     */
    private function generateAuthToken(array $data = []): string
    {
        $payload = '';
        if (!empty($data)) {
            $payload = json_encode($data, JSON_THROW_ON_ERROR);
        }
        return base64_encode(hex2bin(hash('sha256', $payload . $this->qliroConfig->apiSecret)));
    }

    public function getMerchantApiKey(): string
    {
        return $this->qliroConfig->apiKey;
    }
}
