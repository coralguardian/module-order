<?php

namespace D4rk0snet\CoralOrder\Listener;

use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\OrderModel;
use D4rk0snet\CoralOrder\Service\CustomerStripeService;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Exception;
use Hyperion\Stripe\Service\StripeService;
use JsonMapper;
use Stripe\PaymentIntent;

class PaymentSuccessListener
{
    public static function doAction(PaymentIntent $stripePaymentIntent)
    {
        try {
            $mapper = new JsonMapper();
            $mapper->bExceptionOnMissingData = true;
            $mapper->postMappingMethod = 'afterMapping';
            /** @var OrderModel $orderModel */
            $orderModel = $mapper->mapArray($stripePaymentIntent->metadata['model'], new OrderModel());
        } catch(Exception $exception)
        {}

        // Le paiement a été validé, on crée sur stripe le customer et/ou on le met à jour
        $stripeCustomer = CustomerStripeService::getOrCreateCustomer($orderModel);

        // Est ce que l'on doit rattacher le moyen de paiement au customer ?
        $needFutureUsage = count(array_filter($orderModel->getDonationOrdered(), function(DonationOrderModel $donation) {
                return $donation->getDonationRecurrency() === DonationRecurrencyEnum::MONTHLY;
            })) >= 1;

        // @todo: Vérifier que l'on ne crée pas plusieurs cartes !
        if($needFutureUsage) {
            $stripeCustomer = StripeService::getStripeClient()->customers->update($stripeCustomer->id, ['default_source' => $stripePaymentIntent->payment_method]);
        }

        // Mise en place des achats
        CustomerStripeService::createCustomerInvoice($orderModel,$stripeCustomer);

        self::doBackofficeStuff();
        self::doSendInBlueStuff();
    }

    private static function doBackofficeStuff()
    {

    }

    private static function doSendInBlueStuff()
    {

    }
}