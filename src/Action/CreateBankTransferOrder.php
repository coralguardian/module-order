<?php

namespace D4rk0snet\CoralOrder\Action;

use D4rk0snet\Adoption\Enums\AdoptedProduct;
use D4rk0snet\Adoption\Enums\CoralAdoptionActions;
use D4rk0snet\Adoption\Models\AdoptionModel;
use D4rk0snet\Adoption\Models\GiftAdoptionModel;
use D4rk0snet\CoralOrder\Enums\PaymentMethod;
use D4rk0snet\CoralOrder\Enums\Project;
use D4rk0snet\CoralOrder\Model\OrderModel;
use D4rk0snet\Donation\Enums\CoralDonationActions;
use D4rk0snet\Donation\Enums\DonationRecurrencyEnum;
use D4rk0snet\Donation\Models\DonationModel;
use Hyperion\RestAPI\APIManagement;

class CreateBankTransferOrder
{
    public static function doAction(OrderModel $orderModel)
    {
        if (count($orderModel->getDonationOrdered()) > 0 && !is_null($orderModel->getProductsOrdered())) {
            return APIManagement::APIError("You can't pay for adoption and recurrentDonation as the same time by Bank Transfer.", 500);
        }

        if ($orderModel->getProductsOrdered()) {
            $product = $orderModel->getProductsOrdered();

            if (!is_null($product->getGiftModel())) {
                // GiftAdoption
                $giftAdoptionModel = new GiftAdoptionModel();
                $giftAdoptionModel
                    ->setCustomerModel($orderModel->getCustomer())
                    ->setLang($orderModel->getLang())
                    ->setPaymentMethod(PaymentMethod::BANK_TRANSFER)
                    ->setAmount($orderModel->getTotalAmount())
                    ->setStripePaymentIntent(null)
                    ->setAdoptedProduct(AdoptedProduct::from($product->getFullKey()))
                    ->setQuantity($product->getQuantity())
                    ->setProject(Project::from($product->getProject()))
                    ->setSendToFriend($product->getGiftModel()->isSendToFriend());

                do_action(CoralAdoptionActions::PENDING_GIFT_ADOPTION->value, $giftAdoptionModel);
            } else {
                $adoptionModel = new AdoptionModel();
                $adoptionModel
                    ->setCustomerModel($orderModel->getCustomer())
                    ->setLang($orderModel->getLang())
                    ->setPaymentMethod(PaymentMethod::BANK_TRANSFER)
                    ->setAmount($orderModel->getTotalAmount())
                    ->setStripePaymentIntent(null)
                    ->setAdoptedProduct(AdoptedProduct::from($product->getFullKey()))
                    ->setQuantity($product->getQuantity())
                    ->setProject(Project::from($product->getProject()));

                do_action(CoralAdoptionActions::PENDING_ADOPTION->value, $adoptionModel);
            }
        } else {
            $donation = current($orderModel->getDonationOrdered());

            $donationModel = new DonationModel();
            $donationModel
                ->setDonationRecurrency(DonationRecurrencyEnum::ONESHOT) // forcèment oneshot par virement
                ->setAmount($donation->getAmount())
                ->setStripePaymentIntentId(null)
                ->setIsPaid(false)
                ->setDate(new \DateTime())
                ->setPaymentMethod(PaymentMethod::BANK_TRANSFER)
                ->setProject(Project::from($donation->getProject()))
                ->setLang($orderModel->getLang())
                ->setCustomerModel($orderModel->getCustomer());

            do_action(CoralDonationActions::PENDING_DONATION->value, $donationModel);
        }
    }
}