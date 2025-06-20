<?php

namespace Gets\QliroApi\Models\Order;

readonly class PaymentMethodDto
{
    public function __construct(
        public ?string $PaymentMethodName = null,
        public ?string $PaymentTypeCode = null,
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
            PaymentMethodName: $data->PaymentMethodName ?? null,
            PaymentTypeCode: $data->PaymentTypeCode ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'PaymentMethodName' => $this->PaymentMethodName,
            'PaymentTypeCode'   => $this->PaymentTypeCode,
        ];
    }
}
