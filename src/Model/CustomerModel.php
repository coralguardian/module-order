<?php

namespace D4rk0snet\CoralOrder\Model;

use D4rk0snet\Coralguardian\Enums\CustomerType;
use D4rk0snet\Coralguardian\Enums\Language;

class CustomerModel implements \JsonSerializable
{
    /**
     * @required
     */
    private string $firstname;

    /**
     * @required
     */
    private string $lastname;

    private ?string $companyName = null;

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

    /**
     * @required
     */
    private CustomerType $customerType;

    private ?string $alternateNewsletterEmail = null;

    public function afterMapping()
    {
        if($this->getCustomerType() === CustomerType::COMPANY) {
            if($this->companyName === null) {
                throw new \Exception("Company name is mandatory when it's a company user creation");
            }
        }
    }

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

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): CustomerModel
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): CustomerModel
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): CustomerModel
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getCustomerType(): CustomerType
    {
        return $this->customerType;
    }

    public function setCustomerType(string $customerType): CustomerModel
    {
        try {
            $this->customerType = CustomerType::from($customerType);
            return $this;
        } catch (\ValueError $exception) {
            throw new \Exception("CustomerType invalide",400);
        }
    }

    public function getAlternateNewsletterEmail(): ?string
    {
        return $this->alternateNewsletterEmail;
    }

    public function setAlternateNewsletterEmail(?string $alternateNewsletterEmail): CustomerModel
    {
        $this->alternateNewsletterEmail = $alternateNewsletterEmail;
        return $this;
    }

    public function jsonSerialize()
    {
        $returnedValues = [
            "firstName" => $this->getFirstname(),
            "lastName" => $this->getLastname(),
            "address" => $this->getAddress(),
            "postalCode" => $this->getPostalCode(),
            "city" => $this->getCity(),
            "country" => $this->getCountry(),
            "email" => $this->getEmail(),
            "wantsNewsletter" => $this->wantsNewsletter(),
            "language" => $this->getLanguage()->value,
            "customer_type" => $this->getCustomerType()->value
        ];

        if($this->getCustomerType() === CustomerType::COMPANY) {
            $returnedValues["company_name"] = $this->getCompanyName();
            $returnedValues["alternate_newsletter_email"] = $this->getAlternateNewsletterEmail();
        }

        return $returnedValues;
    }
}
