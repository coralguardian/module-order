<?php

namespace D4rk0snet\CoralOrder\Action;

use D4rk0snet\CoralCustomer\Model\CustomerModel;
use D4rk0snet\CoralOrder\Enums\CoralOrderEvents;
use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Service\ProductService;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Hyperion\Stripe\Service\StripeService;
use JsonMapper;
use Stripe\SetupIntent;

class SubscriptionBilling
{
    public static function doAction(SetupIntent $setupIntent)
    {
        if($setupIntent->metadata['donationOrdered'] === null) {
            return;
        }

        $mapper = new JsonMapper();
        $mapper->bExceptionOnMissingData = true;
        $mapper->postMappingMethod = 'afterMapping';

        $donations = json_decode($setupIntent->metadata['donationOrdered'], false, 512, JSON_THROW_ON_ERROR);

        /**
         * On ne traite ici que les dons mensuels.
         * Les dons ponctuels sont gérés par la classe ProductBilling.
         * Cependant une personne peut à la fois faire un don ponctuel et dans la foulée faire un don mensuel
         * du coup on est obligé de filtrer.
         */
        $filterResults = array_filter($donations, static function ($donationOrderData) {
            return $donationOrderData->donationRecurrency === DonationRecurrencyEnum::MONTHLY->value;
        });

        if(count($filterResults) === 0) {
            return;
        }

        /**
         * Nous avons bien un don mensuel à traiter
         */
        $monthlyDonation = $mapper->map(
            current($filterResults),
            new DonationOrderModel()
        );

        $customer = $mapper->map(
            json_decode($setupIntent->metadata['customer'], false, 512, JSON_THROW_ON_ERROR),
            new CustomerModel()
        );

        $stripeClient = StripeService::getStripeClient();
        $stripeMonthlySubscriptionProduct = ProductService::getProduct(
            key: $monthlyDonation->getDonationRecurrency()->value,
            project: $monthlyDonation->getProject()
        );

        $price = ProductService::getOrCreatePrice($stripeMonthlySubscriptionProduct, $monthlyDonation->getAmount(), true);

        // Création de l'abonnement
        $metadata = [
            'language' => $setupIntent->metadata['language']
        ];

        $stripeClient->subscriptions->create(
            [
                'customer' => $setupIntent->customer,
                'items' => [[
                    'price' => $price->id
                ]],
                'default_payment_method' => $setupIntent->payment_method,
                'metadata' => $metadata
            ]
        );

        do_action(CoralOrderEvents::NEW_DONATION->value, $monthlyDonation, $customer, $setupIntent->id);
    }
}