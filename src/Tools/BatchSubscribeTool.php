<?php

declare( strict_types=1 );

namespace SWP\MailchimpConsent\Tools;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce → Status → Tools:
 * "Subscribe all non-subscribed users (MC4WC)"
 *
 * - Targets users where `mailchimp_woocommerce_is_subscribed` is MISSING / '' / '0'
 * - Skips '1' (already subscribed) and 'unsubscribed'
 * - Sets local MC4WC meta to '1'
 * - Queues MailChimp_WooCommerce_User_Submit via mailchimp_handle_or_queue()
 *
 * Lean: no custom meta, no run stats options.
 */
final class BatchSubscribeTool {

	private const DISPATCH = 'mc4wc_ob_dispatch';
	private const WORKER = 'mc4wc_ob_worker';
	private const GROUP = 'mc4wc-override-batch';

	// Tune for your site size.
	private const CHUNK = 200;

	public static function register(): void {
		add_filter( 'woocommerce_debug_tools', [ self::class, 'add_tool' ] );
		add_action( self::DISPATCH, [ self::class, 'dispatch' ], 10, 1 );
		add_action( self::WORKER, [ self::class, 'worker' ], 10, 1 );
	}

	public static function add_tool( array $tools ): array {
		$tools['mc4wc_ob_batch_subscribe'] = [
			'name'     => __( 'Subscribe all non-subscribed users (MC4WC)', 'mc4wc-override-batch' ),
			'button'   => __( 'Queue subscription batch', 'mc4wc-override-batch' ),
			'desc'     => __( 'Finds users whose Mailchimp flag is missing/0 and queues Mailchimp for WooCommerce to subscribe & sync. Skips unsubscribed and already subscribed.', 'mc4wc-override-batch' ),
			'callback' => [ self::class, 'start' ],
		];

		return $tools;
	}

	public static function start(): string {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return esc_html__( 'Insufficient permissions.', 'mc4wc-override-batch' );
		}
		if ( ! function_exists( 'mailchimp_is_configured' ) || ! mailchimp_is_configured()
		     || ! class_exists( '\MailChimp_WooCommerce_User_Submit' ) || ! function_exists( 'mailchimp_handle_or_queue' ) ) {
			return esc_html__( 'Mailchimp for WooCommerce is not installed/configured.', 'mc4wc-override-batch' );
		}
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return esc_html__( 'Action Scheduler is unavailable.', 'mc4wc-override-batch' );
		}

		$payload = [
			'last_id' => 0,
			'chunk'   => self::CHUNK,
		];
		as_enqueue_async_action( self::DISPATCH, [ $payload ], self::GROUP );

		return esc_html__( 'Batch queued. Track progress in WooCommerce → Status → Scheduled Actions.', 'mc4wc-override-batch' );
	}

	/**
	 * Dispatcher: pages through eligible users and spawns worker jobs.
	 */
	public static function dispatch( array $payload ): void {
		global $wpdb;

		$last_id = (int) ( $payload['last_id'] ?? 0 );
		$chunk   = (int) ( $payload['chunk'] ?? self::CHUNK );

		$users = $wpdb->users;
		$meta  = $wpdb->usermeta;

		// Eligible: meta missing/''/'0', require email, ID > last_id.
		$sql = "
			SELECT u.ID
			FROM {$users} u
			LEFT JOIN {$meta} m
			  ON m.user_id = u.ID
			 AND m.meta_key = 'mailchimp_woocommerce_is_subscribed'
			WHERE u.user_email <> ''
			  AND u.ID > %d
			  AND (
					m.umeta_id IS NULL
			 	 OR m.meta_value IN ('0','')
			  )
			ORDER BY u.ID ASC
			LIMIT %d
		";

		$ids = $wpdb->get_col( $wpdb->prepare( $sql, $last_id, $chunk ) ); // phpcs:ignore

		if ( empty( $ids ) ) {
			return; // done
		}

		as_enqueue_async_action( self::WORKER, [ [ 'user_ids' => array_map( 'intval', $ids ) ] ], self::GROUP );

		// Queue next page.
		as_enqueue_async_action( self::DISPATCH, [
			[
				'last_id' => (int) end( $ids ),
				'chunk'   => $chunk,
			]
		], self::GROUP );
	}

	/**
	 * Worker: sets local flag and queues MC4WC for each user.
	 */
	public static function worker( array $payload ): void {
		$user_ids = isset( $payload['user_ids'] ) && is_array( $payload['user_ids'] ) ? $payload['user_ids'] : [];
		if ( empty( $user_ids ) ) {
			return;
		}

		if ( ! class_exists( '\MailChimp_WooCommerce_User_Submit' ) || ! function_exists( 'mailchimp_handle_or_queue' ) ) {
			return;
		}

		foreach ( $user_ids as $uid ) {
			$uid  = (int) $uid;
			$user = get_userdata( $uid );
			if ( ! $user || empty( $user->user_email ) ) {
				continue;
			}

			$val = get_user_meta( $uid, 'mailchimp_woocommerce_is_subscribed', true );

			// Skip unsubscribed and already subscribed.
			if ( $val === 'unsubscribed' || $val === '1' ) {
				continue;
			}

			// Mark subscribed locally and queue MC4WC.
			update_user_meta( $uid, 'mailchimp_woocommerce_is_subscribed', '1' );

			try {
				$job = new \MailChimp_WooCommerce_User_Submit( $uid, true );
				mailchimp_handle_or_queue( $job );
			} catch ( \Throwable $e ) {
				// Ignore; MC4WC/Action Scheduler handles logging/retries.
			}
		}
	}
}
