<?php

namespace D4rk0snet\CoralOrder\Action;

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

        // On isole un éventuel don mensuel
        $filterResults = array_filter($donations, static function ($donationOrderData) {
            return $donationOrderData->donationRecurrency === DonationRecurrencyEnum::MONTHLY->value;
        });

        if (count($filterResults) > 0) {
            /** @var DonationOrderModel $oneshotDonation */
            $monthlyDonation = $mapper->map(
                current($filterResults),
                new DonationOrderModel()
            );

            $stripeClient = StripeService::getStripeClient();
            $stripeMonthlySubscriptionProduct = ProductService::getProduct(
                key: $monthlyDonation->getDonationRecurrency()->value,
                project: $monthlyDonation->getProject()
            );

            $price = ProductService::getOrCreatePrice($stripeMonthlySubscriptionProduct, $monthlyDonation->getAmount());

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
                    'metadata' => $metadata
                ]
            );

            // @todo: Mettre en place les events
        }

    }
}