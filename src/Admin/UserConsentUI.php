<?php

declare( strict_types=1 );

namespace SWP\MailchimpConsent\Admin;

use SWP\MailchimpConsent\Services\Subscription;
use WP_User;

defined( 'ABSPATH' ) || exit;

final class UserConsentUI {
	private Subscription $subscription;
	private bool $showOnEdit;

	public function __construct( Subscription $subscription, bool $showOnEdit ) {
		$this->subscription = $subscription;
		$this->showOnEdit   = $showOnEdit;
	}

	public function register(): void {
		// WP passes a STRING (form type) to user_new_form — do NOT type-hint WP_User here.
		add_action( 'user_new_form', [ $this, 'render_on_add_new' ] ); // Add New User

		if ( $this->showOnEdit ) {
			add_action( 'show_user_profile', [ $this, 'render_on_edit' ] ); // Your Profile
			add_action( 'edit_user_profile', [ $this, 'render_on_edit' ] ); // Edit User
		}
	}

	/**
	 * Renders on Add New User screen.
	 * $type is a string like 'add-new-user' — we don't need it, so ignore it.
	 *
	 * @param mixed $type
	 */
	public function render_on_add_new( $type = '' ): void {
		if ( ! current_user_can( 'create_users' ) ) {
			return;
		}
		// There's no WP_User on this screen yet; pass null.
		$this->render_checkbox( 'wp-admin user-new.php', null );
	}

	/**
	 * Renders on Edit Profile/User screen (WP passes WP_User here).
	 */
	public function render_on_edit( WP_User $user ): void {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}
		$this->render_checkbox( 'wp-admin user-edit.php', $user );
	}

	private function render_checkbox( string $source, ?WP_User $user ): void {
		wp_nonce_field( $this->subscription->nonce_action(), $this->subscription->nonce_field() );

		$checked = isset( $_POST['swp_mc_marketing_subscribe'] ) ? (bool) $_POST['swp_mc_marketing_subscribe'] : false;

		echo '<h2>' . esc_html__( 'Newsletter Subscription (Mailchimp)', 'mc4wc-override-batch' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';

		echo '<tr>';
		echo '<th><label for="swp_mc_marketing_subscribe">' . esc_html__( 'Subscribe to our newsletter', 'mc4wc-override-batch' ) . '</label></th>';
		echo '<td>';
		printf(
			'<label><input type="checkbox" id="swp_mc_marketing_subscribe" name="swp_mc_marketing_subscribe" value="1" %s /> %s</label>',
			checked( true, $checked, false ),
			esc_html__( 'If checked, the user will be subscribed via Mailchimp for WooCommerce.', 'mc4wc-override-batch' )
		);
		echo '</td>';
		echo '</tr>';

		echo '</tbody></table>';
	}
}
