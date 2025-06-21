<?php

namespace Gets\QliroApi\Dtos\Order;

readonly class AddressDto
{
    public function __construct(
        public ?string $City = null,
        public ?string $CountryCode = null,
        public ?string $FirstName = null,
        public ?string $LastName = null,
        public ?string $PostalCode = null,
        public ?string $Street = null,
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
            City: $data->City ?? null,
            CountryCode: $data->CountryCode ?? null,
            FirstName: $data->FirstName ?? null,
            LastName: $data->LastName ?? null,
            PostalCode: $data->PostalCode ?? null,
            Street: $data->Street ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'City'        => $this->City,
            'CountryCode' => $this->CountryCode,
            'FirstName'   => $this->FirstName,
            'LastName'    => $this->LastName,
            'Street'      => $this->Street,
            'PostalCode'  => $this->PostalCode,
        ];
    }
}
