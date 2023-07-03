<?php
/**
 * Core class
 *
 * @package UM_WPCLI\Commands
 */

namespace UM_WPCLI\Commands;

/**
 * Class Core to handle all plugin initialization.
 *
 * @since 1.0.0
 */
class Core {

	/**
	 * Init
	 */
	public function __construct() {

		// Command: wp um security test user=<user_id(int)>.
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'um security test', array( $this, 'test_security_settings' ) );
		}

	}

	/**
	 * Test Security Settings.
	 */
	public function test_security_settings( $args, $assoc_args ) {
		$args = wp_parse_args( $args[0] );
		\WP_CLI::success( esc_html__( 'Account with user ID ' . $args['user'] . ' has been validated. ', 'champ' ) );
		
		add_filter( 'um_secure_blocked_user_redirect_immediately', '__return_false' );

		$is_secured = UM()->secure()->secure_user_capabilities( $args['user'] );
		if ( $is_secured ) {
			\WP_CLI::success( \WP_CLI::colorize( esc_html__( 'Account with user ID ' . $args['user'] . ' has been %Gsecured and %Rflagged as suspicious account. ', 'champ' ) ) );
		} else {
			\WP_CLI::success( \WP_CLI::colorize( esc_html__( 'Account with user ID ' . $args['user'] . ' is %Yalready %Ysecured. ', 'champ' ) ) );
		}

	}
}