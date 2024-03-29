<?php

namespace D4rk0snet\CoralOrder\Model;

use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Exception;

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

    /** @required */
    private string $project;

    private bool $isExtra = false;

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

    public function setDonationRecurrency(string $donationRecurrency): DonationOrderModel
    {
        try {
            $this->donationRecurrency = DonationRecurrencyEnum::from($donationRecurrency);

            return $this;
        } catch (\ValueError $exception) {
            throw new Exception("Invalid donation recurrency value");
        }
    }

    public function getProject(): string
    {
        return $this->project;
    }

    public function setProject(string $project): DonationOrderModel
    {
        $this->project = $project;
        return $this;
    }

    public function isExtra(): bool
    {
        return $this->isExtra;
    }

    public function setIsExtra(bool $isExtra): DonationOrderModel
    {
        $this->isExtra = $isExtra;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'amount' => $this->getAmount(),
            'donationRecurrency' => $this->getDonationRecurrency()->value,
            'project' => $this->getProject(),
            'isExtra' => $this->isExtra()
        ];
    }
}