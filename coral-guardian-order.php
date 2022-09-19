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

use D4rk0snet\CoralOrder\Action\CreateSubscription;
use D4rk0snet\CoralOrder\Enums\CoralOrderEvents;
use D4rk0snet\CoralOrder\Listener\PaymentSuccessListener;
use D4rk0snet\CoralOrder\Plugin;
use Hyperion\Stripe\Enum\StripeEventEnum;

add_action('plugins_loaded', [Plugin::class,'launchActions']);
add_action(StripeEventEnum::PAYMENT_SUCCESS->value, [PaymentSuccessListener::class,'doAction'], 10, 1);
add_action(CoralOrderEvents::NEW_MONTHLY_SUBSCRIPTION->value, [CreateSubscription::class, 'doAction'], 10,2);