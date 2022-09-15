<?php

namespace D4rk0snet\CoralOrder\Service;

use D4rk0snet\Coralguardian\Enums\CustomerType;
use D4rk0snet\CoralOrder\Model\OrderModel;
use Hyperion\Stripe\Model\CustomerSearchModel;
use Hyperion\Stripe\Model\ProductSearchModel;
use Hyperion\Stripe\Service\StripeService;
use Stripe\Customer;
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
                ]
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
    )
    {
        // @todo: faire pour les dons également

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
                    'quantity' => $product->getQuantity()
                ]
            );
        }

        $invoice = StripeService::getStripeClient()->invoices->create(['customer' => $stripeCustomer->id]);
        $finalizedInvoice = StripeService::getStripeClient()->invoices->finalizeInvoice($invoice->id,['auto_advance' => false]);

        StripeService::getStripeClient()->invoices->sendInvoice($finalizedInvoice->id);

    }
}