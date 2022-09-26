<?php

namespace D4rk0snet\CoralOrder\Service;

use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\OrderModel;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
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
        OrderModel $orderModel,
        Customer $customer
    ) : string
    {
        $stripeClient = StripeService::getStripeClient();
        $monthlySubscription = current($orderModel->getDonationOrdered());

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
        $metadata = [
            'customer' => json_encode($orderModel->getCustomer(), JSON_THROW_ON_ERROR),
            'donationOrderedModel' => json_encode($monthlySubscription, JSON_THROW_ON_ERROR)
        ];

        // Mix entre abonnement mensuel et adoption
        if(count($orderModel->getProductsOrdered()) > 0) {
            $productOrderModel = current($orderModel->getProductsOrdered());

            // On vérifie également que l'on a pas un reliquat par rapport au prix du produit
            $stripeProduct = ProductService::getProduct(
                key: $productOrderModel->getKey(),
                project: $productOrderModel->getProject(),
                variant: $productOrderModel->getVariant()
            );

            $metadata['productOrdered'] = json_encode(current($orderModel->getProductsOrdered()), JSON_THROW_ON_ERROR);
            if($orderModel->isSendToFriend() !== null) {
                $metadata['sendToFriend'] = $orderModel->isSendToFriend();
            }

            $stripeDefaultPrice = StripeService::getStripeClient()->prices->retrieve($stripeProduct->default_price);
            if($orderModel->getTotalAmount() - $monthlySubscription->getAmount()  > $stripeDefaultPrice->unit_amount / 100 * $productOrderModel->getQuantity()) {
                // On rajoute un don unique dans le modèle
                $oneShotDonationPrice = ($orderModel->getTotalAmount() - $monthlySubscription->getAmount()) - $stripeDefaultPrice->unit_amount / 100 * $productOrderModel->getQuantity();
                $oneShotDonation = new DonationOrderModel();
                $oneShotDonation
                    ->setAmount($oneShotDonationPrice)
                    ->setProject($productOrderModel->getProject())
                    ->setDonationRecurrency(DonationRecurrencyEnum::ONESHOT->value);
                $orderModel->setDonationOrdered([$oneShotDonation]);

                $metadata['oneshotDonation'] = json_encode($oneShotDonation, JSON_THROW_ON_ERROR);
            }
        }

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
                'metadata' => $metadata
            ]
        );

        return $subscription->latest_invoice->payment_intent->client_secret;
    }
}