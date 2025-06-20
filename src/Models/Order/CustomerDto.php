<?php

namespace Gets\QliroApi\Models\Order;

readonly class CustomerDto
{
    public function __construct(
        public ?string $Email = null,
        public ?string $FirstName = null,
        public ?string $JuridicalType = null,
        public ?string $LastName = null,
        public ?string $MobileNumber = null,
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
            Email: $data->Email ?? null,
            FirstName: $data->FirstName ?? null,
            JuridicalType: $data->JuridicalType ?? null,
            LastName: $data->LastName ?? null,
            MobileNumber: $data->MobileNumber ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'FirstName'     => $this->FirstName,
            'LastName'      => $this->LastName,
            'Email'         => $this->Email,
            'MobileNumber'  => $this->MobileNumber,
            'JuridicalType' => $this->JuridicalType,
        ];
    }
}
