<?php

namespace D4rk0snet\CoralOrder\Model;

class OrderModel implements \JsonSerializable
{
    private ?CompanyCustomerModel $companyCustomerModel = null;
    private ?IndividualCustomerModel $individualCustomerModel = null;
    private ?array $productsOrdered = null;
    private ?array $donationOrdered = null;
    /** @required */
    private ?float $totalAmount = null;

    public function afterMapping()
    {
        if($this->getCompanyCustomerModel() === null && $this->getIndividualCustomerModel() === null) {
            throw new \Exception("A customer is required");
        }

        if($this->getProductsOrdered() === null && $this->getDonationOrdered() === null) {
            throw new \Exception("products or donations are required");
        }

        if($this->getProductsOrdered() && $this->getTotalAmount() === null) {
            throw new \Exception("Totalamount is required when products are ordered");
        }
    }

    public function getCompanyCustomerModel(): ?CompanyCustomerModel
    {
        return $this->companyCustomerModel;
    }

    public function setCompanyCustomerModel(?CompanyCustomerModel $companyCustomerModel): OrderModel
    {
        $this->companyCustomerModel = $companyCustomerModel;
        return $this;
    }

    public function getIndividualCustomerModel(): ?IndividualCustomerModel
    {
        return $this->individualCustomerModel;
    }

    public function setIndividualCustomerModel(?IndividualCustomerModel $individualCustomerModel): OrderModel
    {
        $this->individualCustomerModel = $individualCustomerModel;
        return $this;
    }

    public function getProductsOrdered(): array
    {
        return $this->productsOrdered;
    }

    public function setProductsOrdered(array $productsOrdered): OrderModel
    {
        $this->productsOrdered = $productsOrdered;
        return $this;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): OrderModel
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getDonationOrdered(): array
    {
        return $this->donationOrdered;
    }

    public function setDonationOrdered(array $donationOrdered): OrderModel
    {
        $this->donationOrdered = $donationOrdered;
        return $this;
    }

    public function jsonSerialize()
    {
        $result = [];

        if($this->getProductsOrdered()) {
            $result['products'] = [];
            /** @var ProductOrderModel $product */
            foreach($this->getProductsOrdered() as $product) {
                $result['products'][] = $product->jsonSerialize();
            }
        }

        if($this->getDonationOrdered()) {
            $result['donations'] = [];
            /** @var DonationOrderModel $donation */
            foreach($this->getDonationOrdered() as $donation) {
                $result['donations'][] = $donation->jsonSerialize();
            }
        }

        return $result;
    }
}