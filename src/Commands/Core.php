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

		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'um security test', array( $this, 'test_security_settings' ) );
			\WP_CLI::add_command( 'um security affected users', array( $this, 'test_security_settings_affected_users' ) );
			\WP_CLI::add_command( 'um security flagged users', array( $this, 'test_security_settings_total_flagged_accounts' ) );
		}

	}

	/**
	 * Test Security Settings.
	 * Command: wp um security test user=<user_id>
	 */
	public function test_security_settings( $args, $assoc_args ) {
		$args = wp_parse_args( $args[0], array(
			'user' => null,
		) );
		\WP_CLI::success( esc_html__( 'Account with user ID ' . $args['user'] . ' has been validated. ', 'ultimate-member' ) );
		
		add_filter( 'um_secure_blocked_user_redirect_immediately', '__return_false' );

		$is_secured = UM()->secure()->secure_user_capabilities( $args['user'] );
		if ( $is_secured ) {
			\WP_CLI::success( \WP_CLI::colorize( esc_html__( 'Account with user ID ' . $args['user'] . ' has been %Gsecured and %Rflagged as suspicious account. ', 'ultimate-member' ) ) );
		} else {
			\WP_CLI::success( \WP_CLI::colorize( esc_html__( 'Account with user ID ' . $args['user'] . ' is %Yalready %Ysecured. ', 'ultimate-member' ) ) );
		}

	}

	/**
	 * Test Affected Users by Banned Capabilities
	 * Command: wp um security affected users
	 */
	public function test_security_settings_affected_users() {

		$arr_banned_caps = array();
		if ( UM()->options()->get( 'banned_capabilities' ) ) {
			$arr_banned_caps = array_keys( UM()->options()->get( 'banned_capabilities' ) );
		} else {
			$arr_banned_caps = UM()->secure()->banned_admin_capabilities;
		}

		foreach( $arr_banned_caps as $k => $cap ) {
			$args = array (
				'capability' => $cap,
				'role__not_in' => array( 'administrator' ),
			);
			$wp_user_query = new \WP_User_Query( $args );
			$count_users = $wp_user_query->get_total();
			if ( $count_users <= 0 ) {
				\WP_CLI::success( \WP_CLI::colorize( esc_html__( '`' . $cap . '` is %Gsafe', 'ultimate-member' ) ) );
		    } else {
				\WP_CLI::success( \WP_CLI::colorize( esc_html__( '`%Y' . $cap . '`%N', 'ultimate-member' ) ) . ' has ' . \WP_CLI::colorize( esc_html__('%Raffected `' . $count_users . '`%N user accounts. ', 'ultimate-member' ) ) );
			}
		}
	}

	/**
	 * Total Flagged accounts
	 * Command: wp um security flagged users interval=<today|last_hour>
	 */
	public function test_security_settings_total_flagged_accounts( $args, $assoc_args ) {
		$args = wp_parse_args( isset( $args[0] ) ? $args[0]: '', array(
			'interval' => '',
		) );

		if ( 'today' === $args['interval'] ) {
			$query_args = array(
				'fields'     => 'ID',
				'relation'   => 'AND',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'um_user_blocked__datetime',
						'value'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
						'compare' => '>=',
						'type'    => 'DATE',
					),
					array(
						'key'     => 'um_user_blocked__datetime',
						'value'   => gmdate( 'Y-m-d H:i:s', strtotime( 'now' ) ),
						'compare' => '<=',
						'type'    => 'DATE',
					),
				),
			);
			$users = new \WP_User_Query( $query_args );

			\WP_CLI::success( \WP_CLI::colorize( sprintf( _n( 'There\s %d account that has been blocked today.', 'There are %d accounts that have been blocked today.', $users->get_total(), 'ultimate-member' ), $users->get_total() ) ) );
			
		} else if( 'last_hour' == $args['interval'] ) {
			$query_args = array(
				'fields'     => 'ID',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'um_user_blocked__datetime',
						'value'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
						'compare' => '>=',
						'type'    => 'DATETIME',
					),
				),
			);

			$users = new \WP_User_Query( $query_args );

			\WP_CLI::success( \WP_CLI::colorize( sprintf( _n( 'There\s %d account that has been blocked within the last hour.', 'There are %d accounts that have been blocked within the last hour.', $users->get_total(), 'ultimate-member' ), $users->get_total() ) ) );
			
		} else if ( empty( $args['interval'] ) ) {
			$query_args = array(
				'fields'     => 'ID',
				'relation'   => 'AND',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'um_user_blocked__datetime',
						'compare' => 'EXISTS',
					),
					
				),
			);
			$users = new \WP_User_Query( $query_args );
			\WP_CLI::success( \WP_CLI::colorize( sprintf( _n( 'There\s %d account that has been blocked on your site.', 'There are %d accounts that have been blocked on your site.', $users->get_total(), 'ultimate-member' ), $users->get_total() ) ) );
		
		}

		
	}
}