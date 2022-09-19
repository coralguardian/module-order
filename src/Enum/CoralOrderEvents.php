<?php

namespace D4rk0snet\CoralOrder\Enums;

enum CoralOrderEvents : string
{
    case NEW_ORDER = "coralorder_new_order";
    case NEW_MONTHLY_SUBSCRIPTION = "coralorder_new_monthly_subscription";
    case BANK_TRANSFER_ORDER = "coralorder_bank_transfer_order";
}