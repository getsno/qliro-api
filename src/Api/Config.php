<?php

namespace Gets\QliroApi\Api;

class Config
{
    public array $baseUrls = [
        'dev'  => 'https://pago.qit.nu',
        'prod' => 'https://payments.qit.nu'
    ];

    public string $baseUrl;
    public string $apiKey;
    public string $apiSecret;

    public function __construct(string $apiKey, string $apiSecret, string $mode = 'dev')
    {
        if(!in_array($mode, ['dev', 'prod'])) {
            throw new \InvalidArgumentException('Invalid mode, allowed modes are "dev", "prod"');
        }
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = $this->baseUrls[$mode];
    }
}
