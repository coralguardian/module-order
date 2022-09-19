<?php

namespace D4rk0snet\CoralOrder\Service;

use D4rk0snet\CoralCustomer\Enum\CustomerType;
use D4rk0snet\CoralOrder\Model\OrderModel;
use Hyperion\Stripe\Model\CustomerSearchModel;
use Hyperion\Stripe\Model\PriceSearchModel;
use Hyperion\Stripe\Model\ProductSearchModel;
use Hyperion\Stripe\Service\StripeService;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\Price;
use Stripe\Product;

class CustomerStripeService
{
    /**
     * Get or create a stripe customer.
     * If the customer already exists, update his address (could have changed).
     *
     * @param OrderModel $orderModel
     * @throws \Stripe\Exception\ApiErrorException
     * @return Customer
     */
    public static function getOrCreateCustomer(OrderModel $orderModel) : Customer
    {
        // On recherche le customer par son email.
        $stripeCustomerSearchModel = new CustomerSearchModel(
            email: $orderModel->getCustomer()->getEmail()
        );
        $searchResult = StripeService::getStripeClient()->customers->search(['query' => (string) $stripeCustomerSearchModel]);
        $customerModel = $orderModel->getCustomer();

        // Si on ne le trouve pas il faut le créer, sinon on reprend celui ci.
        if($searchResult->count() === 0) {
            $stripeCustomerData = [
                'name' => $customerModel->getFirstname()." ".$customerModel->getLastname(),
                'address' => [
                    'line1' => $customerModel->getAddress(),
                    'postal_code' => $customerModel->getPostalCode(),
                    'city' => $customerModel->getCity(),
                    'country' => $customerModel->getCountry()
                ],
                'email' => $orderModel->getCustomer()->getEmail()
            ];

            if($customerModel->getCustomerType() === CustomerType::COMPANY) {
                $stripeCustomerData['metadata'] = [
                    'company_name' => $customerModel->getCompanyName(),
                ];
            }

            return StripeService::getStripeClient()->customers->create($stripeCustomerData);
        }

        return StripeService::getStripeClient()->customers->update($searchResult->first()->id, [
            'address' => [
                'line1' => $customerModel->getAddress(),
                'postal_code' => $customerModel->getPostalCode(),
                'city' => $customerModel->getCity(),
                'country' => $customerModel->getCountry()
            ]
        ]);
    }

    public static function createCustomerInvoice(
        OrderModel $orderModel,
        Customer $stripeCustomer
    ) : Invoice
    {
        $invoice = StripeService::getStripeClient()->invoices->create(['customer' => $stripeCustomer->id]);

        foreach($orderModel->getDonationOrdered() as $donationOrderModel)
        {
            $stripeProductSearchModel = new ProductSearchModel(
                active: true,
                metadata: [
                    'key' => $donationOrderModel->getDonationRecurrency()->value,
                    'project' => $donationOrderModel->getProject()
                ]
            );

            $searchResult = StripeService::getStripeClient()
                ->products
                ->search(['query' => (string) $stripeProductSearchModel]);

            /** @var Product $stripeProduct */
            $stripeProduct = $searchResult->first();

            // On recherche tous les prix pour éviter de créer des doublons
            $stripePriceSearchModel = new PriceSearchModel(
                active: true,
                product: $stripeProduct->id
            );

            $searchResult = StripeService::getStripeClient()
                ->prices
                ->search(['query' => (string) $stripePriceSearchModel]);

            $stripePrices = array_filter($searchResult->toArray(), function(Price $price) use ($donationOrderModel) {
                return $price->unit_amount === $donationOrderModel->getAmount() * 100;
            });

            if(count($stripePrices) > 0) {
                $stripePrice = current($stripePrices);
            } else {
                // Création d'un nouveau prix
                $stripePrice = StripeService::getStripeClient()->prices->create([
                    'unit_amount' => $donationOrderModel->getAmount() * 100,
                    'currency' => 'eur',
                    'product' => $stripeProduct->id
                ]);
            }

            StripeService::getStripeClient()->invoiceItems->create(
                [
                    'customer' => $stripeCustomer->id,
                    'price' => $stripePrice->id,
                    'quantity' => 1,
                    'invoice' => $invoice->id
                ]
            );
        }


        foreach($orderModel->getProductsOrdered() as $product)
        {
            $stripeProductSearchModel = new ProductSearchModel(
                active: true,
                metadata: [
                    'key' => $product->getKey(),
                    'project' => $product->getProject()
                ]
            );

            $searchResult = StripeService::getStripeClient()
                ->products
                ->search(['query' => (string) $stripeProductSearchModel]);

            /** @var Product $stripeProduct */
            $stripeProduct = $searchResult->first();

            StripeService::getStripeClient()->invoiceItems->create(
                [
                    'customer' => $stripeCustomer->id,
                    'price' => $stripeProduct->default_price,
                    'quantity' => $product->getQuantity(),
                    'invoice' => $invoice->id
                ]
            );
        }

        return StripeService::getStripeClient()->invoices->finalizeInvoice($invoice->id);
    }
}