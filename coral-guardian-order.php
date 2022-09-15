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

use Hyperion\Stripe\Enum\StripeEventEnum;

add_action('plugins_loaded', [\D4rk0snet\CoralOrder\Plugin::class,'launchActions']);
add_action(StripeEventEnum::PAYMENT_SUCCESS->value, [\D4rk0snet\CoralOrder\Listener\PaymentSuccessListener::class,'doAction'], 10, 1);
