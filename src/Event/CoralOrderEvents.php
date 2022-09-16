<?php

namespace D4rk0snet\CoralOrder\Event;

enum CoralOrderEvents : string
{
    case ORDER_PAID = "coralorder_order_paid";
    case BANK_TRANSFER_ORDER = "coralorder_bank_transfer_order";
}