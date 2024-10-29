<?php
/*
Plugin Name: Affiliates Manager WooCommerce Subscription Integration
Plugin URI: https://wpaffiliatemanager.com/affiliates-manager-woocommerce-subscription-integration/
Description: Process an affiliate commission via Affiliates Manager after a WooCommerce subscription payment.
Version: 1.1.5
Author: wp.insider, affmngr
Author URI: https://wpaffiliatemanager.com
 */

if (!defined('ABSPATH')){
    exit; //Exit if accessed directly
}

include_once('affiliatemgr-wc-subscription-settings.php');

add_action('woocommerce_order_status_completed', 'wpam_process_woocommerce_subscription');
add_action('woocommerce_order_status_processing', 'wpam_process_woocommerce_subscription');
        
function wpam_process_woocommerce_subscription($order_id) {
    WPAM_Logger::log_debug('WooCommerce Subscription Integration - Order processed. Order ID: '.$order_id);
    if (wpam_has_purchase_record($order_id)) {
        WPAM_Logger::log_debug('WooCommerce Subscription Integration - Affiliate commission for this transaction was awarded once. No need to process anything.');
        return;
    }
    $order = wc_get_order($order_id);
    if(wcs_order_contains_subscription($order, 'parent')){
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - This notification is for a new subscription payment");
    }
    else if(wcs_order_contains_subscription($order, 'renewal')){
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - This notification is for a recurring subscription payment");
    }
    else if(wcs_order_contains_subscription($order, 'resubscribe')){
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - This notification is for a resubscription payment");
    }
    else{
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - This notification cannot be processed");
        return;
    }
    $recurring_payment = false;
    if(wcs_order_contains_subscription($order, 'renewal')){
        $recurring_payment = true;
    }
    $subscription_renewal = $order->get_meta('_subscription_renewal'); //get_post_meta($order_id, '_subscription_renewal', true);
    if(isset($subscription_renewal) && !empty($subscription_renewal)) {  //just an additional check
        $recurring_payment = true;
    }
    $total = $order->get_total(); 
    $shipping = $order->get_total_shipping();
    $tax = $order->get_total_tax();
    $fees = wpam_get_total_woocommerce_order_fees($order);
    $buyer_email = $order->get_billing_email();
    WPAM_Logger::log_debug('WooCommerce Subscription Integration - Order ID: '.$order_id.', Total amount: ' . $total . ', Total shipping: ' . $shipping . ', Total tax: ' . $tax . ', Fees: '. $fees);
    $purchaseAmount = $total - $shipping - $tax - $fees;
    $subscriptions = wcs_get_subscriptions_for_order($order_id, array('order_type' => 'any'));
    $parent_id = $order_id;
    $parent_order = $order;
    foreach($subscriptions as $subscription) {
        $temp_parent_id = $subscription->get_parent_id();
        if(isset($temp_parent_id) && $temp_parent_id > 0){  //making sure a parent order ID exists to prevent fatal errors later
            $parent_id = $temp_parent_id;
        }
        else{
            WPAM_Logger::log_debug('WooCommerce Subscription Integration - Parent order ID could not be found', 4);
        }
        $temp_parent_order = $subscription->get_parent();
        if(isset($temp_parent_order) && is_a($temp_parent_order, 'WC_Order')){  //making sure a parent order exists to prevent fatal errors later
            $parent_order = $temp_parent_order;
        }
        else{
            WPAM_Logger::log_debug('WooCommerce Subscription Integration - Parent order could not be found', 4);
        }
    }
    $wpam_refkey = $parent_order->get_meta('_wpam_refkey'); //get_post_meta($parent_id, '_wpam_refkey', true);
    $wpam_id = $parent_order->get_meta('_wpam_id'); //get_post_meta($parent_id, '_wpam_id', true);
    if(!empty($wpam_id)){
        $wpam_refkey = $wpam_id;
    }
    $wpam_refkey = apply_filters( 'wpam_woo_override_refkey', $wpam_refkey, $parent_order);
    if (empty($wpam_refkey)) {
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - could not get wpam_id/wpam_refkey from cookie. This is not an affiliate sale", 4);
        return;
    }
    $order_status = $order->get_status();
    WPAM_Logger::log_debug("WooCommerce Subscription Integration - Order status: " . $order_status);
    if (strtolower($order_status) != "completed" && strtolower($order_status) != "processing") {
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - Order status for this transaction is not in a 'completed' or 'processing' state. Commission will not be awarded at this stage.");
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - Commission for this transaciton will be awarded when you set the order status to completed or processing.");
        return;
    }
    $options = get_option('wpam_wc_subscription_settings');
    if(isset($options['first_commission_only']) && $options['first_commission_only'] != '' && $recurring_payment == true){
        WPAM_Logger::log_debug('WooCommerce Subscription Integration - First Commission Only is enabled in the settings. A commission cannot be rewarded to the affiliate.', 2);
        return;
    }
    $args = array();
    $args['txn_id'] = $order_id;
    $args['amount'] = $purchaseAmount;
    $args['aff_id'] = $wpam_refkey;
    $args['email'] = $buyer_email;
    WPAM_Logger::log_debug('WooCommerce Subscription Integration - awarding commission for order ID: ' . $order_id . ', Purchase amount: ' . $purchaseAmount . ', Affiliate ID: ' . $wpam_refkey . ', Buyer Email: ' . $buyer_email);
    WPAM_Commission_Tracking::award_commission($args);
}

/* No longer working when a renewal occurs
add_action('woocommerce_subscription_payment_complete', 'wpam_woocommerce_subscription_payment_complete');  //Triggers when a subscription payment is made

function wpam_woocommerce_subscription_payment_complete($subscription) {
    WPAM_Logger::log_debug('WooCommerce Subscription Integration - woocommerce_subscription_payment_complete hook triggered');
    if (!is_object($subscription)) {
        $subscription = new WC_Subscription($subscription);
    }
    $order = $subscription->get_parent(); //Getting an instance of the related WC_Order object old method: $subscription->order; 
    if (!is_object($order)) {
        $order = new WC_Order($order);
    }
    $order_id = $order->get_id();  //an alternative is to use $subscription->get_parent_id(). old method: $order->id or $subscription->order->id;
    //WPAM_Logger::log_debug('WooCommerce Subscription Integration - Parent Order ID: '.$order_id.', Subscription Total: '.$subscription->get_total().', Total: '.$order->order_total);
    $total = $subscription->get_total();
    $relatedOrders = $subscription->get_related_orders();
    $recurring_payment = true;
    if (count($relatedOrders) == 1) {  //new subscription payment notification
        $total = $order->get_total();  //$order->get_total() is better for a new subscription payment since it contains the actual amount charged. It also works well when there is a free trial. 
        $recurring_payment = false;
    }
    if($recurring_payment == true){
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - This notification is for a recurring subscription payment");
    }
    else{
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - This notification is for a new subscription payment");
    }
    $shipping = $order->get_total_shipping();
    $tax = $order->get_total_tax();
    WPAM_Logger::log_debug('WooCommerce Subscription Integration - Parent Order ID: '.$order_id.', Total amount: ' . $total . ', Total shipping: ' . $shipping . ', Total tax: ' . $tax);
    $purchaseAmount = $total - $shipping - $tax;
    $wpam_refkey = get_post_meta($order_id, '_wpam_refkey', true);
    $wpam_id = get_post_meta($order_id, '_wpam_id', true);
    if(!empty($wpam_id)){
        $wpam_refkey = $wpam_id;
    }
    $wpam_refkey = apply_filters( 'wpam_woo_override_refkey', $wpam_refkey, $order);
    if (empty($wpam_refkey)) {
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - could not get wpam_id/wpam_refkey from cookie. This is not an affiliate sale", 4);
        return;
    }
    $order_status = $order->get_status();
    WPAM_Logger::log_debug("WooCommerce Subscription Integration - Order status: " . $order_status);
    if (strtolower($order_status) != "completed" && strtolower($order_status) != "processing") {
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - Order status for this transaction is not in a 'completed' or 'processing' state. Commission will not be awarded at this stage.");
        WPAM_Logger::log_debug("WooCommerce Subscription Integration - Commission for this transaciton will be awarded when you set the order status to completed or processing.");
        return;
    }
    $options = get_option('wpam_wc_subscription_settings');
    if(isset($options['first_commission_only']) && $options['first_commission_only'] != '' && $recurring_payment == true){
        WPAM_Logger::log_debug('WooCommerce Subscription Integration - First Commission Only is enabled in the settings. A commission cannot be rewarded to the affiliate.', 2);
        return;
    }
    $txn_id = $order_id . "_" . date("Y-m-d"); //Add the subscription charge date to make this unique
    $requestTracker = new WPAM_Tracking_RequestTracker();
    WPAM_Logger::log_debug('WooCommerce Subscription Integration - awarding commission for order ID: ' . $order_id . ', Purchase amount: ' . $purchaseAmount);
    $requestTracker->handleCheckoutWithRefKey($txn_id, $purchaseAmount, $wpam_refkey);
}
*/

//add_action('woocommerce_subscriptions_switch_completed', 'wpam_woocommerce_subscriptions_switch_completed', 20, 1); //Triggers when a subscription is upgraded/downgraded
        
function wpam_woocommerce_subscriptions_switch_completed($order) {
    WPAM_Logger::log_debug('WooCommerce Subscription Integration – woocommerce_subscriptions_switch_completed hook triggered');
    $subscriptions = wcs_get_subscriptions_for_order($order);
    $theSub = null;
    WPAM_Logger::log_debug('WooCommerce Subscription Integration – checking all the subscriptions for order ID: '.$order->get_id());
    foreach($subscriptions as $subscription) {
        $sub_status = $subscription->get_status();
        WPAM_Logger::log_debug('WooCommerce Subscription Integration – subscription:' . $subscription->get_id() . ' status: ' . $sub_status);
        // only one activate sub can be tied to an order.
        if (strtolower($sub_status) == 'active') {
            // this is our sub
            $theSub = $subscription;
            break;
        }
    }
    $parent_order_id = $theSub->get_parent_id();
    $parent_order = wc_get_order($parent_order_id);
    $order_id = $order->get_id(); //an alternative is to use $subscription->get_parent_id(). old method: $order->id or $subscription->order->id;
    //WPAM_Logger::log_debug('WooCommerce Subscription Integration – Parent Order ID: '.$order_id.', Subscription Total: '.$subscription->get_total().', Total: '.$order->order_total);
    $total = $order->get_total(); //$order->order_total is better for a new subscription payment since it contains the actual amount charged. It also works well when there is a free trial.
    $shipping = $order->get_total_shipping();
    $tax = $order->get_total_tax();
    $fees = wpam_get_total_woocommerce_order_fees($order);
    $buyer_email = $order->get_billing_email();
    WPAM_Logger::log_debug('WooCommerce Subscription Integration – Order ID: '.$order_id.', Total amount: ' . $total . ', Total shipping: ' . $shipping . ', Total tax: ' . $tax . ', Fees: '. $fees);
    WPAM_Logger::log_debug('WooCommerce Subscription Integration – Subscription ID: ' . $theSub->get_id());
    WPAM_Logger::log_debug('WooCommerce Subscription Integration – Parent Order ID: ' . $parent_order_id);
    $purchaseAmount = $total - $shipping - $tax - $fees;
    $wpam_refkey = $parent_order->get_meta('_wpam_refkey'); //get_post_meta($parent_order_id, '_wpam_refkey', true);
    $wpam_id = $parent_order->get_meta('_wpam_id'); //get_post_meta($parent_order_id, '_wpam_id', true);
    if(!empty($wpam_id)){
        $wpam_refkey = $wpam_id;
    }
    $wpam_refkey = apply_filters( 'wpam_woo_override_refkey', $wpam_refkey, $parent_order);
    if (empty($wpam_refkey)) {
        WPAM_Logger::log_debug("WooCommerce Subscription Integration – could not get wpam_id/wpam_refkey from cookie. This is not an affiliate sale", 4);
        return;
    }
    $order_status = $parent_order->get_status();
    WPAM_Logger::log_debug("WooCommerce Subscription Integration – Order status: " . $order_status);
    if (strtolower($order_status) != "completed" && strtolower($order_status) != "processing") {
        WPAM_Logger::log_debug("WooCommerce Subscription Integration – Order status for this transaction is not in a 'completed' or 'processing' state. Commission will not be awarded at this stage.");
        WPAM_Logger::log_debug("WooCommerce Subscription Integration – Commission for this transaciton will be awarded when you set the order status to completed or processing.");
        return;
    }
    $txn_id = $order_id . "_" . date("Y-m-d"); //Add the subscription charge date to make this unique
    $args = array();
    $args['txn_id'] = $txn_id;
    $args['amount'] = $purchaseAmount;
    $args['aff_id'] = $wpam_refkey;
    $args['email'] = $buyer_email;
    WPAM_Logger::log_debug('WooCommerce Subscription Integration – awarding commission for order ID: ' . $order_id . ', Purchase amount: ' . $purchaseAmount . ', Affiliate ID: ' . $wpam_refkey . ', Buyer Email: ' . $buyer_email);
    WPAM_Commission_Tracking::award_commission($args);
}