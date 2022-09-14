<?php

namespace D4rk0snet\CoralOrder\Model;

use D4rk0snet\Coralguardian\Enums\Language;

abstract class CustomerModel implements \JsonSerializable
{
    /**
     * @required
     */
    private string $address;

    /**
     * @required
     */
    private string $postalCode;

    /**
     * @required
     */
    private string $city;

    /**
     * @required
     */
    private string $country;

    /**
     * @required
     */
    private string $email;

    /**
     * @required
     */
    private bool $wantsNewsletter;

    /**
     * @required
     */
    private Language $language;

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function wantsNewsletter(): bool
    {
        return $this->wantsNewsletter;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        try {
            $this->language = Language::from($language);
            return $this;
        } catch (\ValueError $exception) {
            throw new \Exception("Code de langue invalide",400);
        }
    }

    public function setWantsNewsletter(bool $wantsNewsletter): self
    {
        $this->wantsNewsletter = $wantsNewsletter;
        return $this;
    }
}
