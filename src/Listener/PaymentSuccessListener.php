<?php

namespace D4rk0snet\CoralOrder\Listener;

use D4rk0snet\CoralOrder\Event\CoralOrderEvents;
use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\OrderModel;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Hyperion\Stripe\Model\CustomerSearchModel;
use Hyperion\Stripe\Model\ProductSearchModel;
use Hyperion\Stripe\Service\StripeService;
use JsonMapper;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Price;
use Stripe\Subscription;

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
        $filteredResults = array_filter($orderModel->getDonationOrdered(), function(DonationOrderModel $donationOrderModel) {
            return $donationOrderModel->getDonationRecurrency() === DonationRecurrencyEnum::MONTHLY;
        });

        if(count($filteredResults) > 0) {
            self::createSubscription($filteredResults, $orderModel->getCustomer()->getEmail());
        }

        do_action(CoralOrderEvents::ORDER_PAID, $orderModel);
    }

    private static function createSubscription(array $filteredResults, string $email) : Subscription
    {
        $monthlySubscription = current($filteredResults);
        // recherche du produit dans stripe
        $searchModel = new ProductSearchModel(
            active: 'true',
            metadata: [
                'key' => $monthlySubscription->getDonationRecurrency()->value,
                'project' => $monthlySubscription->getProject()
            ]
        );

        $products = StripeService::getStripeClient()->products->search(['query' => (string) $searchModel]);
        if($products->count() === 0) {
            throw new \Exception("Unable to find the monthly donation product !. Subscription aborted.");
        }

        $stripeMonthlySubscriptionProduct = $products->first();

        // On recherche les prix déjà disponibles pour ce produit pour éviter d'en créer d'autres similaires
        // et donc inutiles.
        $stripeMonthlySubscriptionPrices = StripeService::getStripeClient()->prices->all(['product' => $stripeMonthlySubscriptionProduct->id]);
        $matchingStripePrices = array_filter($stripeMonthlySubscriptionPrices->toArray(), function(Price $price) use ($monthlySubscription) {
            return $price->unit_amount === $monthlySubscription->getAmount() * 100;
        });

        if(count($matchingStripePrices) === 0) {
            // On crée un nouveau prix puisqu'on ne l'a pas trouvé.
            $price = StripeService::getStripeClient()->prices->create([
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
        $customers = StripeService::getStripeClient()->customers->search(['query' => (string) $searchModel]);

        if($customers->count() === 0) {
            throw new \Exception("Unable to find the strip customer !. Subscription aborted");
        }

        /** @var Customer $stripeCustomer */
        $stripeCustomer = $customers->first();

        // Création de l'abonnement
        return StripeService::getStripeClient()->subscriptions->create(
            [
                'customer' => $stripeCustomer->id,
                'items' => [[
                    'price' => $price->id
                ]],
                'default_payment_method' => $stripeCustomer->default_source
            ]
        );
    }
}