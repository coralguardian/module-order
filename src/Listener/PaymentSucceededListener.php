<?php

namespace D4rk0snet\CoralOrder\Listener;

use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\ProductOrderModel;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Hyperion\Stripe\Model\ProductSearchModel;
use Hyperion\Stripe\Service\StripeService;
use JsonMapper;
use Stripe\PaymentIntent;
use Stripe\Price;

class PaymentSucceededListener
{
    public static function doAction(PaymentIntent $paymentIntent) : void
    {
        // On check si on est sur un achat croisé (abonnement + achat de produit)
        if($paymentIntent->metadata['donationOrdered'] === null) {
            return;
        }

        $mapper = new JsonMapper();
        $mapper->bExceptionOnMissingData = true;
        $mapper->postMappingMethod = 'afterMapping';

        /** @var DonationOrderModel[] $donationsOrdered */
        $donationsOrdered = $mapper->map(json_decode($paymentIntent->metadata['donationOrdered'], false, 512, JSON_THROW_ON_ERROR), new ProductOrderModel());

        $filterResults = array_filter($donationsOrdered, function(DonationOrderModel $donationOrderModel) {
            return $donationOrderModel->getDonationRecurrency() === DonationRecurrencyEnum::MONTHLY;
        });

        if(count($filterResults) === 0) {
            return;
        }

        $monthlyDonation = current($filterResults);
        $stripeClient = StripeService::getStripeClient();

        // recherche du produit dans stripe
        $searchModel = new ProductSearchModel(
            active: 'true',
            metadata: [
                'key' => $monthlyDonation->getDonationRecurrency()->value,
                'project' => $monthlyDonation->getProject()
            ]
        );

        $products = $stripeClient->products->search(['query' => (string) $searchModel]);
        if($products->count() === 0) {
            throw new \Exception("Unable to find the monthly donation product !. Subscription aborted.");
        }

        $stripeMonthlySubscriptionProduct = $products->first();

        // On recherche les prix déjà disponibles pour ce produit pour éviter d'en créer d'autres similaires
        // et donc inutiles.
        $stripeMonthlySubscriptionPrices = $stripeClient->prices->all([
            'product' => $stripeMonthlySubscriptionProduct->id,
            'active' => true,
        ]);
        $matchingStripePrices = array_filter($stripeMonthlySubscriptionPrices->data, static function(Price $price) use ($monthlyDonation) {
            return $price->unit_amount === (int) $monthlyDonation->getAmount() * 100;
        });

        if(count($matchingStripePrices) === 0) {
            // On crée un nouveau prix puisqu'on ne l'a pas trouvé.
            $price = $stripeClient->prices->create([
                'unit_amount' => $monthlyDonation->getAmount() * 100,
                'currency' => 'eur',
                'recurring' => ['interval' => 'month'],
                'product' => $stripeMonthlySubscriptionProduct->id
            ]);
        } else {
            $price = current($matchingStripePrices);
        }

        // Création de l'abonnement
        $metadata = [
            'customer' => $paymentIntent->metadata['customer'],
            'donationOrdered' => $paymentIntent->metadata['donationOrdered'],
            'language' => $paymentIntent->metadata['language']
        ];

        $stripeClient->subscriptions->create(
            [
                'customer' => $paymentIntent->customer,
                'items' => [[
                    'price' => $price->id
                ]],
                'payment_behavior' => 'default_incomplete',
                'metadata' => $metadata
            ]
        );


    }
}