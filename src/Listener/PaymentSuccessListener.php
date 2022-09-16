<?php

namespace D4rk0snet\CoralOrder\Listener;

use D4rk0snet\Coralguardian\Enums\CustomerType;
use D4rk0snet\Coralguardian\Model\CompanyCustomerModel;
use D4rk0snet\Coralguardian\Model\IndividualCustomerModel;
use D4rk0snet\Coralguardian\Service\CustomerService;
use D4rk0snet\CoralOrder\Model\CustomerModel;
use D4rk0snet\CoralOrder\Model\DonationOrderModel;
use D4rk0snet\CoralOrder\Model\OrderModel;
use D4rk0snet\CoralOrder\Service\CustomerStripeService;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use Exception;
use Hyperion\RestAPI\APIManagement;
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
            $orderModel = $mapper->map(json_decode($stripePaymentIntent->metadata['model'], false, 512, JSON_THROW_ON_ERROR), new OrderModel());
        } catch(Exception $exception)
        {}

        //self::doBackofficeStuff($orderModel->getCustomer());
        self::doSendInBlueStuff();
    }

    private static function doBackofficeStuff(CustomerModel $customer)
    {
        // Création du customer en BO
        $model = $customer->getCustomerType() === CustomerType::INDIVIDUAL ? new IndividualCustomerModel() : new CompanyCustomerModel();

        try {
            $mapper = new JsonMapper();
            $mapper->bExceptionOnMissingData = true;
            $customerModel = $mapper->map($payload, $model);
        } catch (\Exception $exception) {
            return APIManagement::APIError($exception->getMessage(), 400);
        }

        switch ($customerType) {
            case CustomerType::INDIVIDUAL:
                $uuid = CustomerService::createIndividualCustomer($customerModel)->getUuid();
                break;
            case CustomerType::COMPANY:
                $uuid = CustomerService::createCompanyCustomer($customerModel)->getUuid();
                break;
        }

        return APIManagement::APIOk([
            "uuid" => $uuid,
        ]);

    }

    private static function doSendInBlueStuff()
    {

    }
}