<?php

declare( strict_types=1 );

namespace SWP\MailchimpConsent\Services;

use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal subscription helper:
 * - Sets MC4WC meta to '1'
 * - Queues MC4WC's User_Submit job
 * - No extra meta or options are written
 */
final class Subscription {
	private const NONCE_FIELD = '_swp_mc_subscribe_nonce';
	private const NONCE_ACTION = 'swp_mc_subscribe_save';

	public function nonce_field(): string {
		return self::NONCE_FIELD;
	}

	public function nonce_action(): string {
		return self::NONCE_ACTION;
	}

	public function register_save_hooks(): void {
		add_action( 'user_register', [ $this, 'on_user_register' ], 10, 1 );
		add_action( 'profile_update', [ $this, 'on_profile_update' ], 10, 2 );
	}

	public function on_user_register( int $user_id ): void {
		if ( ! is_admin() || ! current_user_can( 'create_users' ) ) {
			return;
		}

		// Verify nonce when present (fail-soft).
		if ( isset( $_POST[ self::NONCE_FIELD ] ) && ! wp_verify_nonce( (string) $_POST[ self::NONCE_FIELD ], self::NONCE_ACTION ) ) {
			return;
		}
		if ( empty( $_POST['swp_mc_marketing_subscribe'] ) ) {
			return; // box not checked
		}
		$this->subscribe_and_queue( $user_id );
	}

	public function on_profile_update( int $user_id, WP_User $old_user ): void {
		if ( ! is_admin() || ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( isset( $_POST[ self::NONCE_FIELD ] ) && ! wp_verify_nonce( (string) $_POST[ self::NONCE_FIELD ], self::NONCE_ACTION ) ) {
			return;
		}
		if ( empty( $_POST['swp_mc_marketing_subscribe'] ) ) {
			return;
		}
		$this->subscribe_and_queue( $user_id );
	}

	/**
	 * Minimal idempotent subscribe + queue via MC4WC.
	 */
	public function subscribe_and_queue( int $user_id ): void {
		// MC4WC presence/config checks.
		if ( ! function_exists( 'mailchimp_is_configured' ) || ! mailchimp_is_configured() ) {
			return;
		}
		if ( ! class_exists( '\MailChimp_WooCommerce_User_Submit' ) || ! function_exists( 'mailchimp_handle_or_queue' ) ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user || empty( $user->user_email ) ) {
			return;
		}

		$val = get_user_meta( $user_id, 'mailchimp_woocommerce_is_subscribed', true );

		// Skip unsubscribed and already subscribed.
		if ( $val === 'unsubscribed' || $val === '1' ) {
			return;
		}

		// Flip local flag to subscribed.
		update_user_meta( $user_id, 'mailchimp_woocommerce_is_subscribed', '1' );

		// Hand off to MC4WC queue.
		try {
			$job = new \MailChimp_WooCommerce_User_Submit( $user_id, true );
			mailchimp_handle_or_queue( $job );
		} catch ( \Throwable $e ) {
			// Ignore; MC4WC/Action Scheduler handles errors/logging.
		}
	}
}
