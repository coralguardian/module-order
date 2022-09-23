<?php

namespace D4rk0snet\CoralOrder\Listener;

use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\ProductOrderModel;
use D4rk0snet\CoralOrder\Service\ProductService;
use Hyperion\Stripe\Service\StripeService;
use JsonMapper;
use Stripe\Subscription;

class NewSubscriptionListener
{
    public static function doAction(Subscription $subscription)
    {
        // On check si on est sur un achat croisé (abonnement + achat de produit)
        if($subscription->metadata['productOrdered'] === null) {
            return;
        }

        $mapper = new JsonMapper();
        $mapper->bExceptionOnMissingData = true;
        $mapper->postMappingMethod = 'afterMapping';

        $productOrder = $mapper->map(json_decode($subscription->metadata['productOrdered'], false, 512, JSON_THROW_ON_ERROR), new ProductOrderModel());

        $invoice = StripeService::getStripeClient()->invoices->create(
            [
                'customer' => $subscription->customer,
                'metadata' => $subscription->metadata
            ]
        );

        // On ajoute le produit souhaité
        $stripeProduct = ProductService::getProduct(
            key: $productOrder->getKey(),
            project: $productOrder->getProject(),
            variant: $productOrder->getVariant()
        );

        StripeService::getStripeClient()->invoiceItems->create(
            [
                'customer' => $subscription->customer,
                'price' => $stripeProduct->default_price,
                'quantity' => $productOrder->getQuantity(),
                'invoice' => $invoice->id
            ]
        );

        // On check si on a pas un don ponctuel
        if($subscription->metadata['oneshotDonation'] !== null) {
            /** @var DonationOrderModel $oneshotDonation */
            $oneshotDonation = $mapper->map(json_decode($subscription->metadata['oneshotDonation'], false, 512, JSON_THROW_ON_ERROR), new DonationOrderModel());
            $stripeProduct = ProductService::getProduct(
                key: $oneshotDonation->getDonationRecurrency()->value,
                project: $productOrder->getProject(),
            );

            $price = ProductService::getOrCreatePrice($stripeProduct, $oneshotDonation->getAmount());

            StripeService::getStripeClient()->invoiceItems->create(
                [
                    'customer' => $subscription->customer,
                    'price' => $price->id,
                    'quantity' => 1,
                    'invoice' => $invoice->id
                ]
            );
        }

        $invoice = StripeService::getStripeClient()->invoices->finalizeInvoice($invoice->id);
        StripeService::getStripeClient()->invoices->pay($invoice->id, ['payment_method' => $subscription->default_payment_method]);
    }
}