<?php

namespace D4rk0snet\CoralOrder\Service;

use D4rk0snet\CoralCustomer\Model\CustomerModel;
use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use Hyperion\Stripe\Model\ProductSearchModel;
use Hyperion\Stripe\Service\StripeService;
use Stripe\Customer;
use Stripe\Price;

class SubscriptionService
{
    /**
     * Création d'une nouvelle subscription
     * Renvoie le secret pour le paiement
     *
     * @param DonationOrderModel $monthlySubscription
     * @param Customer $customer
     * @throws \Stripe\Exception\ApiErrorException
     * @return string
     */
    public static function create(
        DonationOrderModel $monthlySubscription,
        Customer $customer,
        CustomerModel $customerModel
    ) : string
    {
        $stripeClient = StripeService::getStripeClient();

        // recherche du produit dans stripe
        $searchModel = new ProductSearchModel(
            active: 'true',
            metadata: [
                'key' => $monthlySubscription->getDonationRecurrency()->value,
                'project' => $monthlySubscription->getProject()
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
            'project' => $monthlySubscription->getProject()
        ]);
        $matchingStripePrices = array_filter($stripeMonthlySubscriptionPrices->data, static function(Price $price) use ($monthlySubscription) {
            return $price->unit_amount === (int) $monthlySubscription->getAmount() * 100;
        });

        if(count($matchingStripePrices) === 0) {
            // On crée un nouveau prix puisqu'on ne l'a pas trouvé.
            $price = $stripeClient->prices->create([
                'unit_amount' => $monthlySubscription->getAmount() * 100,
                'currency' => 'eur',
                'recurring' => ['interval' => 'month'],
                'product' => $stripeMonthlySubscriptionProduct->id
            ]);
        } else {
            $price = current($matchingStripePrices);
        }

        // Création de l'abonnement
        $subscription = $stripeClient->subscriptions->create(
            [
                'customer' => $customer->id,
                'items' => [[
                    'price' => $price->id
                ]],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => [
                    'save_default_payment_method' => 'on_subscription'
                ],
                'expand' => ['latest_invoice.payment_intent'],
                'metadata' => [
                    'customerModel' => json_encode($customerModel, JSON_THROW_ON_ERROR)
                ]
            ]
        );

        return $subscription->latest_invoice->payment_intent->client_secret;
    }
}