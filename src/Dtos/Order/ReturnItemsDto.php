<?php

namespace Gets\QliroApi\Dtos\Order;

readonly class ReturnItemsDto
{
    public function __construct(
        public int    $OrderId,
        public string $Currency,
        /** @var ReturnDto[] */
        public array  $Returns,
    )
    {
    }

    public static function fromStdClass(object|array $data): self
    {
        if (is_array($data)) {
            $data = (object)$data;
        }

        $returns = [];
        if (isset($data->Returns) && is_array($data->Returns)) {
            $returns = array_map(
                static fn($return) => ReturnDto::fromStdClass($return),
                $data->Returns
            );
        }

        return new self(
            OrderId: $data->OrderId,
            Currency: $data->Currency,
            Returns: $returns,
        );
    }

    public function toArray(): array
    {
        // Convert ReturnDto objects back to arrays
        $returns = array_map(
            static fn(ReturnDto $return) => $return->toArray(),
            $this->Returns
        );

        return [
            'OrderId'  => $this->OrderId,
            'Currency' => $this->Currency,
            'Returns'  => $returns,
        ];
    }
}
