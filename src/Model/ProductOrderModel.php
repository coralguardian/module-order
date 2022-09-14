<?php

namespace D4rk0snet\CoralOrder\Model;

class ProductOrderModel implements \JsonSerializable
{
    /** @required */
    private string $key;

    /** @required */
    private int $quantity;

    public function afterMapping()
    {
        if($this->getQuantity() < 1) {
            throw new \Exception("the minimal quantity is 1 for this product (".$this->getKey().")");
        }
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): ProductOrderModel
    {
        $this->key = $key;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): ProductOrderModel
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'key' => $this->getKey(),
            'quantity' => $this->getQuantity()
        ];
    }
}