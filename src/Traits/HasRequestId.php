<?php

namespace Gets\QliroApi\Traits;

use Random\RandomException;

trait HasRequestId
{
    protected ?string $requestId = null;

    public function setRequestId(string $requestId): self
    {
        $this->requestId = $requestId;
        return $this;
    }

    protected function addRequestIdToBody(array $body): array
    {
        if (!isset($body['RequestId'])) {
            $body['RequestId'] = $this->requestId ?? $this->generateRequestId();
        }

        return $body;
    }


    protected function generateRequestId(): string
    {
        return $this->generateGuid();
    }

    private function generateGuid(): string
    {
        try {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

            return sprintf(
                '%08s-%04s-%04s-%04s-%12s',
                bin2hex(substr($data, 0, 4)),
                bin2hex(substr($data, 4, 2)),
                bin2hex(substr($data, 6, 2)),
                bin2hex(substr($data, 8, 2)),
                bin2hex(substr($data, 10, 6))
            );
        } catch (RandomException) {
            // Fallback to alternative UUID generation method
            return $this->generateFallbackGuid();
        }

    }

    private function generateFallbackGuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

}

