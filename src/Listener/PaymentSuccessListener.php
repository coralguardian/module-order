<?php

namespace D4rk0snet\CoralOrder\Listener;

use D4rk0snet\Adoption\Models\AdoptionModel;
use D4rk0snet\CoralOrder\Model\OrderModel;
use Exception;
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
            $orderModel = $mapper->map($stripePaymentIntent->metadata['model'], new OrderModel());
        } catch(Exception $exception)
        {}

        // On crée le customer


        if($orderModel->getProductsOrdered()) {
            foreach($orderModel->getProductsOrdered() as $product) {
                $adoptionModel = new AdoptionModel();
                $adoptionModel
                    ->setAmount($product->getQuantity())
                    ->setLang($orderModel->getCustomer()->getLanguage()->value)

            }
        }
    }
}