<?php

namespace D4rk0snet\CoralOrder\Action;

use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\ProductOrderModel;
use D4rk0snet\CoralOrder\Service\ProductService;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Hyperion\Stripe\Service\StripeService;
use JsonMapper;
use Stripe\SetupIntent;

class ProductsBilling
{
    public static function doAction(SetupIntent $setupIntent) : void
    {
        if($setupIntent->metadata['productOrdered'] === null && $setupIntent->metadata['donationOrdered'] === null) {
            return;
        }

        $mapper = new JsonMapper();
        $mapper->bExceptionOnMissingData = true;
        $mapper->postMappingMethod = 'afterMapping';

        $invoice = StripeService::getStripeClient()->invoices->create([
            'customer' => $setupIntent->customer,
            'metadata' => [
                'language' => $setupIntent->metadata['language']
            ]
        ]);

        // On récupère les produits
        if($setupIntent->metadata['productOrdered'] !== null) {
            /** @var ProductOrderModel $productOrderModel */
            $productOrderModel = $mapper->map(
                json_decode($setupIntent->metadata['productOrdered'], false, 512, JSON_THROW_ON_ERROR),
                new ProductOrderModel()
            );

            $stripeProduct = ProductService::getProduct(
                key: $productOrderModel->getKey(),
                project: $productOrderModel->getProject(),
                variant: $productOrderModel->getVariant()
            );

            StripeService::getStripeClient()->invoiceItems->create([
                'customer' => $setupIntent->customer,
                'currency' => 'eur',
                'price' => $stripeProduct->default_price,
                'invoice' => $invoice->id
            ]);
        }

        // On récupère les dons oneshot
        if($setupIntent->metadata['donationOrdered'] !== null) {
            /** @var ProductOrderModel $productOrderModel */
            $donationOrderModel = $mapper->map(
                json_decode($setupIntent->metadata['donationOrdered'], false, 512, JSON_THROW_ON_ERROR),
                new DonationOrderModel()
            );

            // On isole un éventuel don oneshot
            $filterResults = array_filter($donationOrderModel, static function(DonationOrderModel $donationOrderModel) {
                return $donationOrderModel->getDonationRecurrency() === DonationRecurrencyEnum::ONESHOT;
            });

            if(count($filterResults) > 0) {
                /** @var DonationOrderModel $oneshotDonation */
                $oneshotDonation = current($filterResults);

                $stripeProduct = ProductService::getProduct(
                    key: $oneshotDonation->getDonationRecurrency()->value,
                    project: $oneshotDonation->getProject()
                );

                // Est ce que l'on a déjà un même prix dans stripe ?
                $price = ProductService::getOrCreatePrice($stripeProduct, $oneshotDonation->getAmount());

                StripeService::getStripeClient()->invoiceItems->create([
                    'customer' => $setupIntent->customer,
                    'currency' => 'eur',
                    'price' => $price->id,
                    'invoice' => $invoice->id
                ]);
            }
        }

        // On demande le paiement de la facture
        StripeService::getStripeClient()->invoices->pay($invoice->id);

        // @todo: rajouter les events
    }
}