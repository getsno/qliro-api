<?php

namespace Gets\QliroApi\Models;

class OrderChanges
{
    /** @var OrderChange[] */
    public array $changes = [];

    /**
     * Add a change to delete an item
     *
     * @param string $merchantReference The merchant reference of the item
     * @param float $PricePerItemIncVat The price per item including VAT
     * @return self
     */
    public function delete(string $merchantReference, float $PricePerItemIncVat): self
    {
        $this->changes[] = OrderChange::delete($merchantReference, $PricePerItemIncVat);
        return $this;
    }

    /**
     * Add a change to decrease the quantity of an item
     *
     * @param string $merchantReference The merchant reference of the item
     * @param float $PricePerItemIncVat The price per item including VAT
     * @param int $quantity The quantity to decrease by
     * @return self
     */
    public function decrease(string $merchantReference, float $PricePerItemIncVat, int $quantity): self
    {
        $this->changes[] = OrderChange::decrease($merchantReference, $PricePerItemIncVat, $quantity);
        return $this;
    }

    /**
     * Add a change to replace the quantity of an item
     *
     * @param string $merchantReference The merchant reference of the item
     * @param float $PricePerItemIncVat The price per item including VAT
     * @param int $quantity The new quantity
     * @return self
     */
    public function replace(string $merchantReference, float $PricePerItemIncVat, int $quantity): self
    {
        $this->changes[] = OrderChange::replace($merchantReference, $PricePerItemIncVat, $quantity);
        return $this;
    }

    /**
     * Get all changes
     *
     * @return OrderChange[]
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * Check if there are any changes
     *
     * @return bool
     */
    public function hasChanges(): bool
    {
        return !empty($this->changes);
    }
}
