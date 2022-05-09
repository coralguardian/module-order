<?php

namespace D4rk0snet\CoralOrder\Entity;

use D4rk0snet\CoralAdoption\Enums\AdoptionType;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;

/**
 * This entity records only not company order
 *
 * @Entity
 */
class StandardOrderEntity
{
    /**
     * @ORM\Id()
     */
    private string $uuid;

    /**
     * @ORM\Column(type="string")
     */
    private string $firstname;

    /**
     * @ORM\Column(type="string")
     */
    private string $lastname;

    /**
     * @ORM\Column(type="string")
     */
    private string $address;

    /**
     * @ORM\Column(type="string")
     */
    private string $city;

    /**
     * @ORM\Column(type="string")
     */
    private string $country;

    /**
     * @ORM\Column(type="string")
     */
    private string $email;

    /**
     * @ORM\Column(type="string")
     */
    private AdoptionType $adoptionType;

    /**
     * @ORM\Column(type="integer")
     */
    private int $quantity;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $orderDate;

    /**
     * @ORM\Column(type="string")
     */
    private string $stripePaymentIntentId;

    /**
     * @ORM\Column(type="integer")
     */
    private int $amount;

    public function __construct(string $uuid,
                                string $firstname,
                                string $lastname,
                                string $address,
                                string $city,
                                string $country,
                                string $email,
                                AdoptionType $adoptionType,
                                int $quantity,
                                DateTime $orderDate,
                                string $stripePaymentIntentId,
                                int $amount)
    {
        $this->uuid = $uuid;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->address = $address;
        $this->city = $city;
        $this->country = $country;
        $this->email = $email;
        $this->adoptionType = $adoptionType;
        $this->quantity = $quantity;
        $this->orderDate = $orderDate;
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return AdoptionType
     */
    public function getAdoptionType(): AdoptionType
    {
        return $this->adoptionType;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return DateTime
     */
    public function getOrderDate(): DateTime
    {
        return $this->orderDate;
    }

    /**
     * @return string
     */
    public function getStripePaymentIntentId(): string
    {
        return $this->stripePaymentIntentId;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }
}