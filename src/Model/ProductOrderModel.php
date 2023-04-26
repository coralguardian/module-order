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

    private ?SelfAdoptionModel $selfAdoptionModel = null;
    private ?GiftModel $giftModel = null;

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

    public function getFullKey() : string
    {
        if($this->getVariant()) {
            return $this->getKey().".".$this->getVariant();
        }

        return $this->getKey();
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

    public function getSelfAdoptionModel(): ?SelfAdoptionModel
    {
        return $this->selfAdoptionModel;
    }

    public function setSelfAdoptionModel(?SelfAdoptionModel $selfAdoptionModel): void
    {
        $this->selfAdoptionModel = $selfAdoptionModel;
    }

    public function getGiftModel(): ?GiftModel
    {
        return $this->giftModel;
    }

    public function setGiftModel(?GiftModel $giftModel): void
    {
        $this->giftModel = $giftModel;
    }

    public function jsonSerialize()
    {
        $results = [
            'key' => $this->getKey(),
            'quantity' => $this->getQuantity(),
            'project' => $this->getProject(),
            'variant' => $this->getVariant()
        ];

        if(!is_null($this->getGiftModel())) {
            $results['giftModel'] = $this->getGiftModel()->jsonSerialize();
        }

        if(!is_null($this->getSelfAdoptionModel())) {
            $results['selfAdoptionModel'] = $this->getSelfAdoptionModel()->jsonSerialize();
        }

        return $results;
    }
}