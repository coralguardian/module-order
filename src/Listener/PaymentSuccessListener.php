<?php

namespace D4rk0snet\CoralOrder\Listener;

use D4rk0snet\CoralOrder\Enums\CoralOrderEvents;
use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\OrderModel;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Hyperion\Stripe\Service\StripeService;
use JsonMapper;
use Stripe\PaymentIntent;

class PaymentSuccessListener
{
    public static function doAction(PaymentIntent $stripePaymentIntent)
    {
        $mapper = new JsonMapper();
        $mapper->bExceptionOnMissingData = true;
        $mapper->postMappingMethod = 'afterMapping';

        // Evite le déclenchement lors de la création de la subscription
        if(json_decode($stripePaymentIntent->metadata['model']) === null) {
            return;
        }

        // Force mise en defaut du moyen de paiement utilisé.
        StripeService::getStripeClient()->customers->update($stripePaymentIntent->customer,
            ['invoice_settings' =>
                ['default_payment_method' => $stripePaymentIntent->payment_method]
            ]
        );

        /** @var OrderModel $orderModel */
        $orderModel = $mapper->map(json_decode($stripePaymentIntent->metadata['model'], false, 512, JSON_THROW_ON_ERROR), new OrderModel());

        // Est ce que l'on a un abonnement mensuel à gérer ?
        array_map(static function(DonationOrderModel $donationOrderModel) use ($orderModel, $stripePaymentIntent) {
            if($donationOrderModel->getDonationRecurrency() === DonationRecurrencyEnum::MONTHLY) {
                do_action(CoralOrderEvents::NEW_MONTHLY_SUBSCRIPTION->value, $donationOrderModel, $orderModel->getCustomer()->getEmail());
            }
        },$orderModel->getDonationOrdered());

        do_action(CoralOrderEvents::NEW_ORDER->value, $orderModel, $stripePaymentIntent);
    }

   /* private static function rebuildOrderModelFromPaymentIntent(PaymentIntent $paymentIntent)
    {
        $stripeClient = StripeService::getStripeClient();
        $invoice = $stripeClient->invoices->retrieve($paymentIntent->invoice, [
            'expand' => ['customer']
        ]);

        $orderModel = new OrderModel();
        $customerModel = new

    }*/
}