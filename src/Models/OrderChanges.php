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
     * @param float $pricePerItemExVat The price per item excluding VAT
     * @return self
     */
    public function delete(string $merchantReference, float $pricePerItemExVat): self
    {
        $this->changes[] = OrderChange::delete($merchantReference, $pricePerItemExVat);
        return $this;
    }

    /**
     * Add a change to decrease the quantity of an item
     *
     * @param string $merchantReference The merchant reference of the item
     * @param float $pricePerItemExVat The price per item excluding VAT
     * @param int $quantity The quantity to decrease by
     * @return self
     */
    public function decrease(string $merchantReference, float $pricePerItemExVat, int $quantity): self
    {
        $this->changes[] = OrderChange::decrease($merchantReference, $pricePerItemExVat, $quantity);
        return $this;
    }

    /**
     * Add a change to replace the quantity of an item
     *
     * @param string $merchantReference The merchant reference of the item
     * @param float $pricePerItemExVat The price per item excluding VAT
     * @param int $quantity The new quantity
     * @return self
     */
    public function replace(string $merchantReference, float $pricePerItemExVat, int $quantity): self
    {
        $this->changes[] = OrderChange::replace($merchantReference, $pricePerItemExVat, $quantity);
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
