<?php

declare( strict_types=1 );

namespace SWP\MailchimpConsent;

use SWP\MailchimpConsent\Admin\UserConsentUI;
use SWP\MailchimpConsent\Services\Subscription;
use SWP\MailchimpConsent\Tools\BatchSubscribeTool;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public static function init(): void {
		// Soft notice if MC4WC is missing or not configured.
		add_action( 'admin_notices', [ self::class, 'maybe_show_dependency_notice' ] );

		$subscription = new Subscription();

		/**
		 * Filter: control whether the checkbox appears on Edit User/Profile screens.
		 * Default: false (MC4WC already provides controls there).
		 * Example to show: add_filter('swp_mc_show_on_edit', '__return_true');
		 */
		$show_on_edit = (bool) apply_filters( 'swp_mc_show_on_edit', false );

		$ui = new UserConsentUI( $subscription, $show_on_edit );
		$ui->register();

		// Save hooks: subscribe via checkbox.
		$subscription->register_save_hooks();

		// WooCommerce Status Tool (batch subscribe).
		BatchSubscribeTool::register();
	}

	public static function maybe_show_dependency_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$present    = function_exists( 'mailchimp_is_configured' ) || class_exists( '\MailChimp_WooCommerce' );
		$configured = function_exists( 'mailchimp_is_configured' ) ? (bool) mailchimp_is_configured() : false;

		if ( ! $present ) {
			echo '<div class="notice notice-error"><p>' .
			     esc_html__( 'Mailchimp for WooCommerce – Override & Batch Subscribe: Mailchimp for WooCommerce must be installed and active.', 'mc4wc-override-batch' ) .
			     '</p></div>';

			return;
		}
		if ( ! $configured ) {
			$url = admin_url( 'admin.php?page=mailchimp-woocommerce' );
			printf(
				'<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
				esc_html__( 'Mailchimp for WooCommerce – Override & Batch Subscribe: Mailchimp for WooCommerce is active but not configured.', 'mc4wc-override-batch' ),
				esc_url( $url ),
				esc_html__( 'Open settings', 'mc4wc-override-batch' )
			);
		}
	}
}
