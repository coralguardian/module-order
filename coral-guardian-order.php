<?php
/**
 * Plugin Name: Adopte un corail / recif = Commande =
 * Plugin URI:
 * Description: Gestion des commandes
 * Version: 0.1
 * Requires PHP: 8.1
 * Author: Benoit DELBOE & Grégory COLLIN
 * Author URI:
 * Licence: GPLv2
 */

use D4rk0snet\CoralOrder\Action\CreateBankTransferOrder;
use D4rk0snet\CoralOrder\Action\ProductsBilling;
use D4rk0snet\CoralOrder\Action\SubscriptionBilling;
use D4rk0snet\CoralOrder\Enums\CoralOrderEvents;
use D4rk0snet\CoralOrder\Plugin;
use Hyperion\Stripe\Enum\StripeEventEnum;

add_action('plugins_loaded', [Plugin::class,'launchActions']);
add_action(StripeEventEnum::SETUPINTENT_SUCCESS, [ProductsBilling::class, 'doAction'], 10,1);
add_action(StripeEventEnum::SETUPINTENT_SUCCESS, [SubscriptionBilling::class, 'doAction'], 10,1);
add_action(CoralOrderEvents::BANK_TRANSFER_ORDER->value, [CreateBankTransferOrder::class,'doAction'], 10, 2);
