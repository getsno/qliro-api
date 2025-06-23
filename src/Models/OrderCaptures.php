<?php

namespace Gets\QliroApi\Models;

class OrderCaptures
{
    /** @var OrderCapture[] */
    public array $captures = [];

    /**
     * Get all captures
     *
     * @return OrderCapture[]
     */
    public function getCaptures(): array
    {
        return $this->captures;
    }

    /**
     * Check if there are any captures
     *
     * @return bool
     */
    public function hasCaptures(): bool
    {
        return !empty($this->captures);
    }
    public function add(string $merchantReference, float $PricePerItemIncVat, int $quantity): self
    {
        $this->captures[] = OrderReturn::make($merchantReference, $PricePerItemIncVat, $quantity);
        return $this;
    }
}
