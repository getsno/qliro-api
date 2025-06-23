<?php

namespace Gets\QliroApi\Models;

class OrderReturns
{
    /** @var OrderReturn[] */
    public array $returns = [];

    /**
     * Get all returns
     *
     * @return OrderReturn[]
     */
    public function getReturns(): array
    {
        return $this->returns;
    }

    /**
     * Check if there are any returns
     *
     * @return bool
     */
    public function hasReturns(): bool
    {
        return !empty($this->returns);
    }

    public function add(string $merchantReference, float $PricePerItemIncVat, int $quantity): self
    {
        $this->returns[] = OrderReturn::make($merchantReference, $PricePerItemIncVat, $quantity);
        return $this;
    }
}
