<?php
/**
 * Plugin Name: Mailchimp for WooCommerce – Override & Batch Subscribe
 * Description: Admin-only subscribe tools for Mailchimp for WooCommerce: Add New User checkbox + batch subscribe via MC4WC queue. No extra meta, no API fallback.
 * Version:     1.0.0
 * Author:      Smart WP Plugins
 * Author URI:  https://smartwpplugins.com/
 * Text Domain: mc4wc-override-batch
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires Plugins: mailchimp-for-woocommerce
 * License:     GPL-2.0+
 */

defined( 'ABSPATH' ) || exit;

/**
 * Autoload (Composer if present; otherwise a tiny fallback).
 */
$autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
} else {
	spl_autoload_register( static function ( $class ) {
		$prefix = 'SWP\\MailchimpConsent\\';
		if ( strpos( $class, $prefix ) !== 0 ) {
			return;
		}
		$rel  = substr( $class, strlen( $prefix ) );
		$path = __DIR__ . '/src/' . str_replace( '\\', DIRECTORY_SEPARATOR, $rel ) . '.php';
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	} );
}

/**
 * Activation guard for WordPress < 6.5 (or if dependency header is ignored).
 */
register_activation_hook( __FILE__, static function () {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$mc4wc = 'mailchimp-for-woocommerce/mailchimp-woocommerce.php';
	if ( ! is_plugin_active( $mc4wc ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'Mailchimp for WooCommerce – Override & Batch Subscribe requires the plugin “Mailchimp for WooCommerce” to be active.', 'mc4wc-override-batch' ),
			esc_html__( 'Dependency missing', 'mc4wc-override-batch' ),
			[ 'back_link' => true ]
		);
	}
} );

add_action( 'plugins_loaded', static function () {
	if ( ! class_exists( 'SWP\\MailchimpConsent\\Plugin' ) ) {
		return;
	}
	SWP\MailchimpConsent\Plugin::init();
} );
