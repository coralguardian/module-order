<?php

namespace D4rk0snet\CoralOrder\Listener;

use D4rk0snet\CoralOrder\Model\ProductOrderModel;
use D4rk0snet\CoralOrder\Service\ProductService;
use Hyperion\Stripe\Service\StripeService;
use JsonMapper;
use Stripe\Subscription;

class NewSubscriptionListener
{
    public static function do_action(Subscription $subscription)
    {
        // On check si on est sur un achat croisé (abonnement + achat de produit)
        if($subscription->metadata['productOrdered'] === null) {
            return;
        }

        $mapper = new JsonMapper();
        $mapper->bExceptionOnMissingData = true;
        $mapper->postMappingMethod = 'afterMapping';

        $productOrder = $mapper->map(json_decode($subscription->metadata['productOrdered'], false, 512, JSON_THROW_ON_ERROR), new ProductOrderModel());

        $invoice = StripeService::getStripeClient()->invoices->create(['customer' => $subscription->customer]);

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

        $invoice = StripeService::getStripeClient()->invoices->finalizeInvoice($invoice->id);
        StripeService::getStripeClient()->invoices->pay($invoice->id, ['default_payment_method' => $subscription->default_payment_method]);
    }
}