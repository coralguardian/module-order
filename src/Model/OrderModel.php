<?php

namespace D4rk0snet\CoralOrder\Model;

use D4rk0snet\Donation\Enums\PaymentMethod;
use Exception;

class OrderModel implements \JsonSerializable
{
    /** @required  */
    private CustomerModel $customer;
    /** @var ProductOrderModel[] */
    private array $productsOrdered = [];
    /** @var DonationOrderModel[] */
    private array $donationOrdered = [];
    /** @required */
    private PaymentMethod $paymentMethod;
    private ?float $totalAmount = null;

    public function afterMapping()
    {
        if($this->getProductsOrdered() === null && $this->getDonationOrdered() === null) {
            throw new \Exception("products or donations are required");
        }

        if($this->getProductsOrdered() && $this->getTotalAmount() === null) {
            throw new \Exception("Totalamount is required when products are ordered");
        }
    }

    public function getCustomer(): CustomerModel
    {
        return $this->customer;
    }

    public function setCustomer(CustomerModel $customer): OrderModel
    {
        $this->customer = $customer;
        return $this;
    }

    /**
     * @return \D4rk0snet\CoralOrder\Model\ProductOrderModel[]
     */
    public function getProductsOrdered(): array
    {
        return $this->productsOrdered;
    }

    /**
     * @param \D4rk0snet\CoralOrder\Model\ProductOrderModel[]
     */
    public function setProductsOrdered(array $productsOrdered): OrderModel
    {
        $this->productsOrdered = $productsOrdered;
        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): OrderModel
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    /**
     * @return \D4rk0snet\CoralOrder\Model\DonationOrderModel[]
     */
    public function getDonationOrdered(): array
    {
        return $this->donationOrdered;
    }

    /**
     * @param \D4rk0snet\CoralOrder\Model\DonationOrderModel[]
     */
    public function setDonationOrdered(array $donationOrdered): OrderModel
    {
        $this->donationOrdered = $donationOrdered;
        return $this;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): OrderModel
    {
        try {
            $this->paymentMethod = PaymentMethod::from($paymentMethod);

            return $this;
        } catch (\ValueError $exception) {
            throw new Exception("Invalid payment method value");
        }
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