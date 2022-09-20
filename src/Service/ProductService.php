<?php

namespace D4rk0snet\CoralOrder\Service;

use Hyperion\Stripe\Model\PriceSearchModel;
use Hyperion\Stripe\Model\ProductSearchModel;
use Hyperion\Stripe\Service\StripeService;
use Stripe\Price;
use Stripe\Product;

class ProductService
{
    /**
     * Récupère un produit dans stripe
     *
     * @param string $key
     * @param string $project
     * @param string|null $variant
     * @throws \Stripe\Exception\ApiErrorException
     * @return Product
     */
    public static function getProduct(
        string $key,
        string $project,
        ?string $variant = null
    ) : Product
    {
        $productSearchMetadata = [
            'key' => $key,
            'project' => $project
        ];

        if($variant !== null) {
            $productSearchMetadata['variant'] = $variant;
        }

        $stripeProductSearchModel = new ProductSearchModel(
            active: true,
            metadata: $productSearchMetadata
        );

        $searchResult = StripeService::getStripeClient()
            ->products
            ->search(['query' => (string) $stripeProductSearchModel]);

        if(count($searchResult->data) === 0 ) {
            throw new \Exception("Invoice creation aborted. Product not found");
        } elseif(count($searchResult->data) > 1) {
            throw new \Exception("Invoice creation aborted. More than one product found.");
        }

        /** @var Product $stripeProduct */
        return $searchResult->first();
    }

    /**
     * Crée ou récupère un prix existant dans stripe.
     *
     * @param Product $product
     * @param int $amount
     * @throws \Stripe\Exception\ApiErrorException
     * @return Price
     */
    public static function getOrCreatePrice(
        Product $product,
        int $amount
    ) : Price
    {
        // On recherche tous les prix pour éviter de créer des doublons
        $stripePriceSearchModel = new PriceSearchModel(
            active: true,
            product: $product->id
        );

        $searchResult = StripeService::getStripeClient()
            ->prices
            ->search(['query' => (string) $stripePriceSearchModel]);

        $stripePrices = array_filter($searchResult->data, static function(Price $price) use ($amount) {
            return $price->unit_amount === $amount * 100;
        });

        if(count($stripePrices) > 0) {
            return current($stripePrices);
        }

        // Création d'un nouveau prix
        $data = [
            'unit_amount' => $amount * 100,
            'currency' => 'eur',
            'product' => $product->id
        ];

        return StripeService::getStripeClient()->prices->create($data);
    }
}