<?php

namespace D4rk0snet\CoralOrder\Model;

class IndividualCustomerModel extends CustomerModel
{
    /**
     * @required
     */
    private string $firstname;

    /**
     * @required
     */
    private string $lastname;

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): IndividualCustomerModel
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): IndividualCustomerModel
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            "firstName" => $this->getFirstname(),
            "lastName" => $this->getLastname(),
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
