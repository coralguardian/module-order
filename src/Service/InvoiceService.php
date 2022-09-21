<?php

namespace D4rk0snet\CoralOrder\Service;

use D4rk0snet\CoralOrder\Model\OrderModel;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Hyperion\Stripe\Service\StripeService;
use Stripe\Customer;
use Stripe\Invoice;

class InvoiceService
{
    public static function createCustomerInvoice(
        OrderModel $orderModel,
        Customer $stripeCustomer
    ) : Invoice
    {
        $invoice = StripeService::getStripeClient()->invoices->create(['customer' => $stripeCustomer->id]);

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

            return StripeService::getStripeClient()->invoices->finalizeInvoice($invoice->id);
        }

        if(count($orderModel->getDonationOrdered()) > 0) {
            $oneShotDonation = current($orderModel->getDonationOrdered());
            if ($oneShotDonation->getDonationRecurrency() !== DonationRecurrencyEnum::ONESHOT) {
                throw new \Exception("Invoice creation aborted. Should only be a oneshot donation here !");
            }

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

        return StripeService::getStripeClient()->invoices->finalizeInvoice($invoice->id);
    }
}