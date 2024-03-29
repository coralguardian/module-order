<?php

namespace D4rk0snet\CoralOrder\Action;

use D4rk0snet\CoralCustomer\Model\CustomerModel;
use D4rk0snet\CoralOrder\Enums\CoralOrderEvents;
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
        /**
         * Cette classe va gérer l'adoption de coraux mais AUSSI les dons ponctuels
         */
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
                'quantity' => $productOrderModel->getQuantity(),
                'price' => $stripeProduct->default_price,
                'invoice' => $invoice->id
            ]);
        }

        // On récupère les dons oneshot
        if($setupIntent->metadata['donationOrdered'] !== null) {
            $donations = json_decode($setupIntent->metadata['donationOrdered'], false, 512, JSON_THROW_ON_ERROR);

            // On isole un éventuel don oneshot
            $filterResults = array_filter($donations, static function ($donationOrderData) {
                return $donationOrderData->donationRecurrency === DonationRecurrencyEnum::ONESHOT->value;
            });

            /**
             * On peut avoir plusieurs dons oneshot
             * - Un qui proviendrait d'une réponse à la demande dans le formulaire
             * - Un autre qui proviendrait que la personne a modifié le prix global du corail/récif.
             */
            foreach($filterResults as $donationOrderData) {
                /** @var DonationOrderModel $oneshotDonation */
                $oneshotDonation = $mapper->map(
                    $donationOrderData,
                    new DonationOrderModel()
                );

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
        StripeService::getStripeClient()->invoices->pay($invoice->id, ['payment_method' => $setupIntent->payment_method]);

        if($setupIntent->metadata['productOrdered'] !== null) {
            do_action(CoralOrderEvents::NEW_ORDER->value, $setupIntent);
        }

        // On traite chaque don
        if($setupIntent->metadata['donationOrdered'] !== null && count($filterResults) > 0)
        {
            foreach($filterResults as $donationOrderData) {
                $oneshotDonation = $mapper->map(
                    $donationOrderData,
                    new DonationOrderModel()
                );

                $customer = $mapper->map(
                    json_decode($setupIntent->metadata['customer'], false, 512, JSON_THROW_ON_ERROR),
                    new CustomerModel()
                );

                do_action(CoralOrderEvents::NEW_DONATION->value, $oneshotDonation, $customer, $setupIntent->id);
            }
        }
    }
}