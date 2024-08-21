<?php
/*
Plugin Name: WooCommerce Deduct Salary Payment Gateway
Description: A custom payment gateway that deducts the order amount from the customer's salary.
Version: 1.0
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Make sure WooCommerce is active.
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    function deduct_salary_payment_gateway_init() {

        class WC_Gateway_Deduct_Salary extends WC_Payment_Gateway {

            public function __construct() {
                $this->id = 'deduct_salary_gateway';
                $this->icon = ''; // You can add a URL to an icon if needed.
                $this->has_fields = false;
                $this->method_title = __( 'Deduct from Salary', 'woocommerce' );
                $this->method_description = __( 'Deduct the order amount from the customer\'s salary.', 'woocommerce' );

                // Load the settings.
                $this->init_form_fields();
                $this->init_settings();

                $this->title = $this->get_option( 'title' );
                $this->description = $this->get_option( 'description' );

                // Save settings.
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            public function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title'       => __( 'Enable/Disable', 'woocommerce' ),
                        'label'       => __( 'Enable Deduct from Salary Payment', 'woocommerce' ),
                        'type'        => 'checkbox',
                        'description' => '',
                        'default'     => 'no',
                    ),
                    'title' => array(
                        'title'       => __( 'Title', 'woocommerce' ),
                        'type'        => 'text',
                        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                        'default'     => __( 'Deduct from Salary', 'woocommerce' ),
                        'desc_tip'    => true,
                    ),
                    'description' => array(
                        'title'       => __( 'Description', 'woocommerce' ),
                        'type'        => 'textarea',
                        'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
                        'default'     => __( 'The order amount will be deducted from your salary.', 'woocommerce' ),
                    ),
                );
            }

            public function process_payment( $order_id ) {
                $order = wc_get_order( $order_id );

                // Mark the order as processing (since payment is handled separately).
                $order->update_status( 'processing', __( 'Amount to be deducted from salary.', 'woocommerce' ) );

                // Reduce stock levels.
                wc_reduce_stock_levels( $order_id );

                // Empty the cart.
                WC()->cart->empty_cart();

                // Return success and redirect to the thank you page.
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order ),
                );
            }
        }
    }

    add_filter( 'woocommerce_payment_gateways', 'add_deduct_salary_payment_gateway' );

    function add_deduct_salary_payment_gateway( $gateways ) {
        $gateways[] = 'WC_Gateway_Deduct_Salary';
        return $gateways;
    }

    add_action( 'plugins_loaded', 'deduct_salary_payment_gateway_init', 11 );


    add_filter( 'woocommerce_available_payment_gateways', 'auto_select_deduct_salary_gateway' );
    function auto_select_deduct_salary_gateway( $available_gateways ) {
        if ( is_checkout() && ! is_wc_endpoint_url( 'order-pay' ) ) {
            if ( isset( $available_gateways['deduct_salary_gateway'] ) ) {
                // Set the "Deduct from Salary" gateway as the only available method.
                $available_gateways = array( 'deduct_salary_gateway' => $available_gateways['deduct_salary_gateway'] );
            }
        }
        return $available_gateways;
    }
}