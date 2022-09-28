<?php

namespace D4rk0snet\CoralOrder\Service;

use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\OrderModel;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Hyperion\Stripe\Service\StripeService;
use Stripe\Customer;
use Stripe\Invoice;

class InvoiceService
{
    public static function createCustomerInvoice(
        OrderModel $orderModel,
        Customer $stripeCustomer,
        array $metadata
    ) : Invoice
    {
        $invoice = StripeService::getStripeClient()->invoices->create(
            [
                'customer' => $stripeCustomer->id,
                'metadata' => $metadata
            ]
        );

        // On ajoute le produit souhaité
        if(count($orderModel->getProductsOrdered()) > 0) {
            $productOrderModel = current($orderModel->getProductsOrdered());

            $stripeProduct = ProductService::getProduct(
                key: $productOrderModel->getKey(),
                project: $productOrderModel->getProject(),
                variant: $productOrderModel->getVariant()
            );

            StripeService::getStripeClient()->invoiceItems->create(
                [
                    'customer' => $stripeCustomer->id,
                    'price' => $stripeProduct->default_price,
                    'quantity' => $productOrderModel->getQuantity(),
                    'invoice' => $invoice->id
                ]
            );
        }

        if(count($orderModel->getDonationOrdered()) > 0) {
            // On ne traite que les donation oneshot ici, les monthly seront fait après payement.
            if(count($orderModel->getDonationOrdered()))
            {
                $filterResults = array_filter($orderModel->getDonationOrdered(), function(DonationOrderModel $donationOrderModel) {
                    return $donationOrderModel->getDonationRecurrency() === DonationRecurrencyEnum::ONESHOT;
                });

                if(count($filterResults) > 0) {
                    $oneShotDonation = current($filterResults);

                    $stripeProduct = ProductService::getProduct(
                        key: $oneShotDonation->getDonationRecurrency()->value,
                        project: $oneShotDonation->getProject()
                    );

                    $stripePrice = ProductService::getOrCreatePrice(
                        product: $stripeProduct,
                        amount: $oneShotDonation->getAmount()
                    );

                    StripeService::getStripeClient()->invoiceItems->create(
                        [
                            'customer' => $stripeCustomer->id,
                            'price' => $stripePrice->id,
                            'quantity' => 1,
                            'invoice' => $invoice->id
                        ]
                    );
                }
            }
        }

        return StripeService::getStripeClient()->invoices->finalizeInvoice($invoice->id);
    }
}