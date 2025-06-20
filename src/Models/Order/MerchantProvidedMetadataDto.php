<?php

namespace Gets\QliroApi\Models\Order;

readonly class MerchantProvidedMetadataDto
{
    public function __construct(
        public string $Key,
        public ?string $Value = null,
    )
    {
    }

    public static function fromStdClass(null|object|array $data): ?self
    {
        if (!$data) {
            return null;
        }

        if (is_array($data)) {
            $data = (object)$data;
        }

        return new self(
            Key: $data->Key,
            Value: $data->Value ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'Key' => $this->Key,
            'Value' => $this->Value,
        ];

        // Remove null values
        return array_filter($data, function ($value) {
            return $value !== null;
        });
    }
}
