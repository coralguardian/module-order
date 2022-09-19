<?php

namespace D4rk0snet\CoralOrder\API;

use D4rk0snet\CoralOrder\Enums\CoralOrderEvents;
use D4rk0snet\CoralOrder\Enums\PaymentMethod;
use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\OrderModel;
use D4rk0snet\CoralOrder\Model\ProductOrderModel;
use D4rk0snet\CoralOrder\Service\CustomerStripeService;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Hyperion\RestAPI\APIEnpointAbstract;
use Hyperion\RestAPI\APIManagement;
use Hyperion\Stripe\Model\ProductSearchModel;
use Hyperion\Stripe\Service\StripeService;
use JsonMapper;
use Stripe\Product;
use WP_REST_Request;
use WP_REST_Response;

class CreateOrder extends APIEnpointAbstract
{
    public static function callback(WP_REST_Request $request): WP_REST_Response
    {
        $payload = json_decode($request->get_body(), false, 512, JSON_THROW_ON_ERROR);
        if ($payload === null) {
            return APIManagement::APIError("Invalid body content", 400);
        }

        try {
            $mapper = new JsonMapper();
            $mapper->bExceptionOnMissingData = true;
            $mapper->postMappingMethod = 'afterMapping';
            /** @var OrderModel $orderModel */
            $orderModel = $mapper->map($payload, new OrderModel());

            // On vérifie si le prix est cohérent
            if($orderModel->getProductsOrdered() !== null && !self::checkPriceConsistency($orderModel)) {
                throw new \Exception("totalPrice is below required for the total order amount");
            }

            $stripeCustomer = CustomerStripeService::getOrCreateCustomer($orderModel);

            if($orderModel->getPaymentMethod() === PaymentMethod::BANK_TRANSFER) {
                do_action(CoralOrderEvents::BANK_TRANSFER_ORDER->value, $orderModel);
                return APIManagement::APIOk();
            }

            // Création de la facture et récupération du paymentIntent
            $invoice = CustomerStripeService::CreateCustomerInvoice($orderModel, $stripeCustomer);
            $needFutureUsage = count(array_filter($orderModel->getDonationOrdered(), static function(DonationOrderModel $donation) {
                    return $donation->getDonationRecurrency() === DonationRecurrencyEnum::MONTHLY;
                })) >= 1;

            $paymentIntentParams = [
                'metadata' => [
                    "model" => json_encode($orderModel, JSON_THROW_ON_ERROR)
                ],
            ];

            if ($needFutureUsage) {
                $paymentIntentParams['setup_future_usage'] = 'off_session';
            }

            // Mise à jour du paymentIntent
            $paymentIntent = StripeService::getStripeClient()->paymentIntents->update($invoice->payment_intent,$paymentIntentParams);

            return APIManagement::APIOk([
                "clientSecret" => $paymentIntent->client_secret
            ]);
        } catch (\Exception $exception) {
            return APIManagement::APIError($exception->getMessage(), 400);
        }
    }

    /**
     * If there is products in the order, then check that the amount wanted to be paid is enough
     *
     * @param OrderModel $orderModel
     * @throws \Stripe\Exception\ApiErrorException
     * @return bool
     */
    private static function checkPriceConsistency(OrderModel $orderModel) : bool
    {
        $totalProductPriceToPay = 0;

        // Produits
        array_map(
            static function(ProductOrderModel $productOrderModel) use (&$totalProductPriceToPay) {
                $totalProductPriceToPay += self::getDefaultProductPrice($productOrderModel->getKey(), $productOrderModel->getProject());
            },
            $orderModel->getProductsOrdered(),
        );

        return $orderModel->getTotalAmount() >= $totalProductPriceToPay;
    }

    /**
     * Compute the total order price for the paymentIntent
     *
     * @param OrderModel $orderModel
     * @throws \Stripe\Exception\ApiErrorException
     * @return float
     */
    private static function computeTotalPrice(OrderModel $orderModel) : float
    {
        $totalToPay = 0;

        // Produits
        array_map(
            static function(ProductOrderModel $productOrderModel) use (&$totalToPay) {
                $totalToPay += self::getDefaultProductPrice($productOrderModel->getKey(), $productOrderModel->getProject()) * $productOrderModel->getQuantity();
            },
            $orderModel->getProductsOrdered(),
        );

        // Dons
        array_map(
            static function(DonationOrderModel $donationOrderModel) use (&$totalToPay) {
                $totalToPay += $donationOrderModel->getAmount();
            },
            $orderModel->getDonationOrdered()
        );

        return $totalToPay;
    }

    /**
     * Retrieve the default product price
     *
     * @param string $productKey
     * @param string $project
     * @throws \Stripe\Exception\ApiErrorException
     * @return float|null
     */
    private static function getDefaultProductPrice(string $productKey, string $project) : ?float
    {
        $stripeProductSearchModel = new ProductSearchModel(
            active: true,
            metadata: [
                'key' => $productKey,
                'project' => $project
            ]
        );

        $searchResult = StripeService::getStripeClient()
            ->products
            ->search(['query' => (string) $stripeProductSearchModel]);

        if($searchResult->count() === 0) {
            return null;
        }

        if($searchResult->count() > 1) {
            throw new \Exception("Multiple products match the research !");
        }

        /** @var Product $stripeProduct */
        $stripeProduct = current($searchResult->data);

        if($stripeProduct->default_price === null) {
            throw new \Exception("Product ".$stripeProduct->id." doesn't have a default price");
        }

        $stripeProductPrice = StripeService::getStripeClient()->prices->retrieve($stripeProduct->default_price);

        return $stripeProductPrice->unit_amount / 100;
    }

    public static function getMethods(): array
    {
        return ["POST"];
    }

    public static function getPermissions(): string
    {
        return "__return_true";
    }

    public static function getEndpoint(): string
    {
        return "createOrder";
    }
}