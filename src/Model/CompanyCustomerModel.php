<?php

namespace D4rk0snet\CoralOrder\Model;

class CompanyCustomerModel extends CustomerModel
{
    /**
     * @required
     */
    private string $companyName;

    /**
     * @required
     */
    private string $firstname;

    /**
     * @required
     */
    private string $lastname;

    private ?string $alternateNewsletterEmail = null;

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): CompanyCustomerModel
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getAlternateNewsletterEmail(): ?string
    {
        return $this->alternateNewsletterEmail;
    }

    public function setAlternateNewsletterEmail(?string $alternateNewsletterEmail): CompanyCustomerModel
    {
        $this->alternateNewsletterEmail = $alternateNewsletterEmail;
        return $this;
    }

    public function setWantsNewsletter(bool $wantsNewsletter): CompanyCustomerModel
    {
        $this->wantsNewsletter = $wantsNewsletter;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            "firstName" => $this->getFirstname(),
            "companyName" => $this->getCompanyName(),
            "address" => $this->getAddress(),
            "postalCode" => $this->getPostalCode(),
            "city" => $this->getCity(),
            "country" => $this->getCountry(),
            "email" => $this->getEmail(),
            "wantsNewsletter" => $this->wantsNewsletter(),
            "language" => $this->getLanguage()->value
        ];
    }
}
