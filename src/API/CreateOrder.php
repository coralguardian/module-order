<?php

namespace D4rk0snet\CoralOrder\API;

use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\OrderModel;
use D4rk0snet\CoralOrder\Model\ProductOrderModel;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Hyperion\RestAPI\APIEnpointAbstract;
use Hyperion\RestAPI\APIManagement;
use Hyperion\Stripe\Model\ProductSearchModel;
use Hyperion\Stripe\Service\CustomerService;
use Hyperion\Stripe\Service\SearchService;
use Hyperion\Stripe\Service\StripeService;
use JsonMapper;
use WP_REST_Request;
use WP_REST_Response;

class CreateOrder extends APIEnpointAbstract
{
    public const GIFT_ADOPTION_UUID_PARAM = "adoption_uuid";

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

        $total = self::computeTotalPrice($orderModel);
        if($orderModel->getIndividualCustomerModel()) {
            $customer = $orderModel->getIndividualCustomerModel();
            $stripeCustomer = CustomerService::getOrCreateIndividualCustomer(
                email: $customer->getEmail(),
                firstName: $customer->getFirstname(),
                lastName: $customer->getLastname(),
                metadata: $customer->jsonSerialize()
            );
        } else {
            $customer = $orderModel->getCompanyCustomerModel();
            $stripeCustomer = CustomerService::getOrCreateCompanyCustomer(
                email: $customer->getEmail(),
                companyName: $customer->getCompanyName(),
                mainContactName: $customer->getFirstname(),
                metadata: $customer->jsonSerialize()
            );
        }

        $needFutureUsage = false;
        if($orderModel->getDonationOrdered() !== null) {
            $needFutureUsage = count(array_filter($orderModel->getDonationOrdered(), function(DonationOrderModel $donation) {
                    return $donation->getDonationRecurrency() === DonationRecurrencyEnum::MONTHLY;
                })) >= 1;
        }

        $paymentIntent = StripeService::createPaymentIntent($total,$stripeCustomer->id, $orderModel->jsonSerialize(), $needFutureUsage);

        } catch (\Exception $exception) {
            return APIManagement::APIError($exception->getMessage(), 400);
        }

        return APIManagement::APIOk([
            "clientSecret" => $paymentIntent->client_secret
        ]);
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

    private static function checkPriceConsistency(OrderModel $orderModel) : bool
    {
        $totalProductPriceToPay = 0;

        if($orderModel->getProductsOrdered()) {
            /** @var ProductOrderModel $product */
            $totalProductPriceToPay += self::getProductsTotalToPay($orderModel);
        } else {
            throw new \Exception("No products ordered for checkPriceConsistency");
        }

        return $orderModel->getTotalAmount() >= $totalProductPriceToPay;
    }

    private static function computeTotalPrice(OrderModel $orderModel) : float
    {
        $totalToPay = 0;

        if($orderModel->getProductsOrdered()) {
            /** @var ProductOrderModel $product */
            $totalToPay += self::getProductsTotalToPay($orderModel);
        }

        if($orderModel->getDonationOrdered()) {
            /** @var DonationOrderModel $donation */
            foreach($orderModel->getDonationOrdered() as $donation) {
                $totalToPay += $donation->getAmount();
            }
        }

        return $totalToPay;
    }

    private static function getProductsTotalToPay(OrderModel $orderModel): float
    {
        $productPriceAmount = 0;

        foreach ($orderModel->getProductsOrdered() as $product) {
            $productSearch = (new ProductSearchModel)
                ->setActive(true)
                ->addMetadata(['key' => $product->getKey()]);
            $searchResults = SearchService::searchProduct($productSearch);
            if (count($searchResults->data) === 0) {
                throw new \Exception("Product with " . $product->getKey() . " key is not found");
            }

            $stripeProduct = current($searchResults->data);
            $stripePrice = SearchService::getPrice($stripeProduct->default_price);

            $productPriceAmount += $stripePrice->unit_amount / 100;
        }

        return $productPriceAmount;
    }
}