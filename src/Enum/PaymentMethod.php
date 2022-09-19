<?php

namespace D4rk0snet\CoralOrder\Enums;

enum PaymentMethod : string
{
    case CREDIT_CARD = 'credit_card';
    case BANK_TRANSFER = 'bank_transfert';

    public function getMethodName()
    {
        return match($this) {
            self::CREDIT_CARD => __("CARTE BANCAIRE", "fiscalreceipt"),
            self::BANK_TRANSFER => __("VIREMENT BANCAIRE", "fiscalreceipt")
        };
    }
}