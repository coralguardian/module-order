<?php

namespace D4rk0snet\CoralOrder\Model;

class ProductOrderModel implements \JsonSerializable
{
    /** @required */
    private string $key;

    /** @required */
    private int $quantity;

    /** @required */
    private string $project;

    private ?string $variant = null;

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

    public function getProject(): string
    {
        return $this->project;
    }

    public function setProject(string $project): ProductOrderModel
    {
        $this->project = $project;
        return $this;
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function setVariant(?string $variant): ProductOrderModel
    {
        $this->variant = $variant;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'key' => $this->getKey(),
            'quantity' => $this->getQuantity(),
            'project' => $this->getProject(),
            'variant' => $this->getVariant()
        ];
    }
}