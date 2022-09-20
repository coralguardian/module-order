<?php

namespace D4rk0snet\CoralOrder\Action;

use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use Hyperion\Stripe\Model\CustomerSearchModel;
use Hyperion\Stripe\Model\ProductSearchModel;
use Hyperion\Stripe\Service\StripeService;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Price;

class CreateSubscription
{
    public static function doAction(DonationOrderModel $monthlySubscription, string $email)
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
        $stripeMonthlySubscriptionPrices = $stripeClient->prices->all(['product' => $stripeMonthlySubscriptionProduct->id, 'active' => true]);
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

        // On récupère le customer
        $searchModel = new CustomerSearchModel(email: $email);
        $customers = $stripeClient->customers->search(['query' => (string) $searchModel]);

        if($customers->count() === 0) {
            throw new \Exception("Unable to find the stripe customer !. Subscription aborted");
        }

        /** @var Customer $stripeCustomer */
        $stripeCustomer = $customers->first();

        // Création de l'abonnement
        $stripeClient->subscriptions->create(
            [
                'customer' => $stripeCustomer->id,
                'items' => [[
                    'price' => $price->id
                ]],
                'default_payment_method' => $stripeCustomer->invoice_settings->default_payment_method
            ]
        );
    }
}