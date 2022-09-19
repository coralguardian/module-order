<?php

namespace D4rk0snet\CoralOrder\Listener;

use D4rk0snet\CoralOrder\Enums\CoralOrderEvents;
use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\OrderModel;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use JsonMapper;
use Stripe\PaymentIntent;

class PaymentSuccessListener
{
    public static function doAction(PaymentIntent $stripePaymentIntent)
    {
        $mapper = new JsonMapper();
        $mapper->bExceptionOnMissingData = true;
        $mapper->postMappingMethod = 'afterMapping';
        /** @var OrderModel $orderModel */
        $orderModel = $mapper->map(json_decode($stripePaymentIntent->metadata['model'], false, 512, JSON_THROW_ON_ERROR), new OrderModel());

        // Est ce que l'on a un abonnement mensuel à gérer ?
        array_map(static function(DonationOrderModel $donationOrderModel) use ($orderModel) {
            if($donationOrderModel->getDonationRecurrency() === DonationRecurrencyEnum::MONTHLY) {
                do_action(CoralOrderEvents::NEW_MONTHLY_SUBSCRIPTION, $donationOrderModel->getDonationRecurrency(), $orderModel->getCustomer()->getEmail());
            }
        },$orderModel->getDonationOrdered());

        do_action(CoralOrderEvents::NEW_ORDER, $orderModel);
    }
}