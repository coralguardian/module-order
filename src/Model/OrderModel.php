<?php

namespace D4rk0snet\CoralOrder\Model;

use D4rk0snet\CoralCustomer\Model\CustomerModel;
use D4rk0snet\Coralguardian\Enums\Language;
use D4rk0snet\CoralOrder\Enums\PaymentMethod;
use Exception;

class OrderModel implements \JsonSerializable
{
    /** @required  */
    private CustomerModel $customer;
    /** @var ProductOrderModel|null */
    private ?ProductOrderModel $productsOrdered = null;
    /** @var DonationOrderModel[]|null */
    private ?array $donationOrdered = null;
    /** @required */
    private PaymentMethod $paymentMethod;
    /** @required */
    private Language $lang;
    /** @var float|null */
    private ?float $totalAmount = null;

    public function afterMapping()
    {
        if($this->getProductsOrdered() === null && $this->getDonationOrdered() === null) {
            throw new \Exception("products or donations are required");
        }

        if(is_null($this->getTotalAmount()) && !is_null($this->getProductsOrdered())) {
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

    public function getProductsOrdered(): ?ProductOrderModel
    {
        return $this->productsOrdered;
    }

    public function setProductsOrdered(?ProductOrderModel $productsOrdered): OrderModel
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
     * @return DonationOrderModel[] | null
     */
    public function getDonationOrdered(): ?array
    {
        return $this->donationOrdered;
    }

    /**
     * @param DonationOrderModel[]
     * @return OrderModel
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

    public function getLang(): Language
    {
        return $this->lang;
    }

    public function setLang(string $language): OrderModel
    {
        try {
            $this->lang = Language::from($language);
            return $this;
        } catch (\ValueError $exception) {
            throw new Exception("Invalid language value");
        }
    }

    public function jsonSerialize()
    {
        $result = [];

        $result['customer'] = $this->getCustomer()->jsonSerialize();
        $result['productsOrdered'] = $this->getProductsOrdered()?->jsonSerialize();

        if(is_array($this->getDonationOrdered())) {
            $result['donationOrdered'] = [];
            /** @var DonationOrderModel $donation */
            foreach($this->getDonationOrdered() as $donation) {
                $result['donationOrdered'][] = $donation->jsonSerialize();
            }
        }

        $result['paymentMethod'] = $this->getPaymentMethod()->value;
        $result['totalAmount'] = $this->getTotalAmount();
        $result['lang'] = $this->getLang()->value;

        return $result;
    }
}