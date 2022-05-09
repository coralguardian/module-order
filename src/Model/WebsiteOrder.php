<?php

namespace D4rk0snet\CoralOrder\Model;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class WebsiteOrder
{
    private UuidInterface $uuid;
    private int $price;
    private string $type;
    private string $stripeRef;

    public function __construct(int $price,
                                string $type,
                                string $stripeRef)
    {
        $this->uuid = Uuid::uuid4();
        $this->price = $price;
        $this->type = $type;
        $this->stripeRef = $stripeRef;
    }

    public function getUuid(): \Ramsey\Uuid\UuidInterface|string
    {
        return $this->uuid->toString();
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStripeRef(): string
    {
        return $this->stripeRef;
    }
}