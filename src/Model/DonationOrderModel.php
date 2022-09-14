<?php

namespace D4rk0snet\CoralOrder\Model;

use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;

class DonationOrderModel implements \JsonSerializable
{
    /**
     * @required
     */
    private float $amount;

    /**
     * @required
     */
    private DonationRecurrencyEnum $donationRecurrency;

    public function afterMapping()
    {
        if($this->getAmount() <= 0) {
            throw new \Exception("the donation must have a positive amount");
        }
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): DonationOrderModel
    {
        $this->amount = $amount;
        return $this;
    }

    public function getDonationRecurrency(): DonationRecurrencyEnum
    {
        return $this->donationRecurrency;
    }

    public function setDonationRecurrency(DonationRecurrencyEnum $donationRecurrency): DonationOrderModel
    {
        $this->donationRecurrency = $donationRecurrency;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'amount' => $this->getAmount(),
            'recurrency' => $this->getDonationRecurrency()->value
        ];
    }
}