<?php

namespace D4rk0snet\CoralOrder\API;

use D4rk0snet\CoralOrder\Model\ProductOrderModel;
use D4rk0snet\CoralOrder\Enums\CoralOrderEvents;
use D4rk0snet\CoralOrder\Enums\PaymentMethod;
use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\OrderModel;
use D4rk0snet\CoralOrder\Service\CustomerStripeService;
use D4rk0snet\CoralOrder\Service\ProductService;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Hyperion\RestAPI\APIEnpointAbstract;
use Hyperion\RestAPI\APIManagement;
use Hyperion\Stripe\Service\StripeService;
use JsonMapper;
use WP_REST_Request;
use WP_REST_Response;

class CreateOrder extends APIEnpointAbstract
{
    /**
     * @param WP_REST_Request $request
     * @throws \JsonException
     * @return WP_REST_Response
     */
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

            if(!is_null($orderModel->getProductsOrdered())) {
                self::manageProductOrdered($orderModel->getProductsOrdered(), $orderModel);
            }

            if($orderModel->getPaymentMethod() === PaymentMethod::BANK_TRANSFER) {
                do_action(CoralOrderEvents::BANK_TRANSFER_ORDER->value, $orderModel);
                return APIManagement::APIOk();
            }

            $stripeCustomer = CustomerStripeService::getOrCreateCustomer($orderModel);

            // Metadatas
            $metadata = [
                'customer' => json_encode($orderModel->getCustomer(), JSON_THROW_ON_ERROR),
                'language' => $orderModel->getLang()->value
            ];

            if(!is_null($orderModel->getProductsOrdered())) {
                $metadata = array_merge($metadata,
                    [
                        'productOrdered' => json_encode($orderModel->getProductsOrdered(), JSON_THROW_ON_ERROR),
                    ]);
            }

            if(!is_null($orderModel->getDonationOrdered()) && count($orderModel->getDonationOrdered()) > 0) {
                $metadata['donationOrdered'] = json_encode($orderModel->getDonationOrdered(), JSON_THROW_ON_ERROR);
            }

            // On prépare une empreinte de carte sans montant auquel on va rattacher la commande de l'utilisateur.
            // Par la suite ce sont ces metas qui seront utilisé pour faire les différentes actions.
            $setupIntent = StripeService::getStripeClient()->setupIntents->create([
                'customer' => $stripeCustomer->id,
                'usage' => 'off_session',
                'metadata' => $metadata
            ]);

            return APIManagement::APIOk([
                "clientSecret" => $setupIntent->client_secret
            ]);
        } catch (\Exception $exception) {
            return APIManagement::APIError($exception->getMessage(), 400);
        }
    }

    public static function manageProductOrdered(ProductOrderModel $productOrderModel, OrderModel $orderModel)
    {
        // On vérifie si le prix est cohérent
        if(!self::checkPriceConsistency($orderModel)) {
            throw new \Exception("totalPrice is below required for the total order amount");
        }

        // On ajoute un don unique exceptionnel si le prix souhaité est supérieurs au prix "normaux" des produits.
        $stripeProduct = ProductService::getProduct(
            key: $productOrderModel->getKey(),
            project: $productOrderModel->getProject(),
            variant: $productOrderModel->getVariant()
        );

        $stripeDefaultPrice = StripeService::getStripeClient()->prices->retrieve($stripeProduct->default_price);
        $productPriceAmount = $orderModel->getTotalAmount();

        if(!is_null($orderModel->getDonationOrdered()) && count($orderModel->getDonationOrdered()))
        {
            $monthlyDonation = array_filter($orderModel->getDonationOrdered(), function(DonationOrderModel $donationOrderModel) {
                return $donationOrderModel->getDonationRecurrency() === DonationRecurrencyEnum::MONTHLY;
            });
            if(count($monthlyDonation) > 0) {
                $productPriceAmount -= current($monthlyDonation)->getAmount();
            }
        }

        if($productPriceAmount > $stripeDefaultPrice->unit_amount / 100 * $productOrderModel->getQuantity()) {
            // On rajoute un don unique dans le modèle
            $oneShotDonationPrice = $productPriceAmount - $stripeDefaultPrice->unit_amount / 100 * $productOrderModel->getQuantity();
            $oneShotDonation = new DonationOrderModel();
            $oneShotDonation
                ->setAmount($oneShotDonationPrice)
                ->setProject($productOrderModel->getProject())
                ->setDonationRecurrency(DonationRecurrencyEnum::ONESHOT->value)
                ->setIsExtra(true);
            $orderModel->setDonationOrdered(array_merge($orderModel->getDonationOrdered(),[$oneShotDonation]));
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
        if(is_null($orderModel->getProductsOrdered())) {
            throw new \Exception("Not able to check price consistency : no products");
        }

        $productOrderModel = $orderModel->getProductsOrdered();
        $stripeProduct = ProductService::getProduct(
            key: $productOrderModel->getKey(),
            project: $productOrderModel->getProject(),
            variant: $productOrderModel->getVariant()
        );

        $stripeDefaultPrice = StripeService::getStripeClient()->prices->retrieve($stripeProduct->default_price);

        return $orderModel->getTotalAmount() >= $stripeDefaultPrice->unit_amount / 100 * $productOrderModel->getQuantity();
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

    public static function isAuthentificationRequired(): bool
    {
        return true;
    }
}