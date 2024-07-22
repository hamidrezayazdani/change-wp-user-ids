<?php
/*
Plugin Name: Change WP user IDs
Plugin URI: https://www.codemix.ir/
Description: ATTENTION: BACK UP YOUR DATABASE FIRST AND DO IT ON YOUR OWN RISK. Change 200 First users ids on activation
Version: 1.0.0
Author: Hamid Reza Yazdani
Author URI: https://www.codemix.ir/
License: GPL3
*/

defined( 'ABSPATH' ) || exit;

/**
 * Function to check if a table exists
 */
function table_exists( $table_name ): bool {
	global $wpdb;

	return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
}

/**
 * Update all occurrences
 *
 * @return void
 */
function update_user_ids() {
	global $wpdb;

	// Get the current auto-increment value
	$auto_increment = $wpdb->get_var( "
    									SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES
     									WHERE TABLE_SCHEMA = DATABASE()
      										AND TABLE_NAME = '{$wpdb->users}'
      									",
	);

	// Array of old user IDs
	$old_user_ids = range( 1, 200 );

	foreach ( $old_user_ids as $index => $old_id ) {
		$new_id = $auto_increment + $index;

		// Update wp_users table
		$wpdb->update(
			$wpdb->users,
			array( 'ID' => $new_id ),
			array( 'ID' => $old_id ),
		);

		// Update wp_usermeta table
		$wpdb->update(
			$wpdb->usermeta,
			array( 'user_id' => $new_id ),
			array( 'user_id' => $old_id ),
		);

		// Update wp_posts table (for orders)
		$wpdb->update(
			$wpdb->posts,
			array( 'post_author' => $new_id ),
			array( 'post_author' => $old_id ),
		);

		// Update wp_postmeta table
		$wpdb->update(
			$wpdb->postmeta,
			array( 'meta_value' => $new_id ),
			array(
				'meta_key'   => '_edit_last',
				'meta_value' => $old_id,
			),
		);

		// Update wp_postmeta table (for order metadata)
		$wpdb->update(
			$wpdb->postmeta,
			array( 'meta_value' => $new_id ),
			array(
				'meta_key'   => '_customer_user',
				'meta_value' => $old_id,
			),
		);

		// Update wp_comments table
		$wpdb->update(
			$wpdb->comments,
			array( 'user_id' => $new_id ),
			array( 'user_id' => $old_id ),
		);

		// Update wp_woocommerce_sessions table (for user session)
		if ( table_exists( $wpdb->prefix . 'woocommerce_sessions' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_sessions',
				array( 'userID' => $new_id ),
				array( 'userID' => $old_id ),
			);
		}

		// Update wp_woocommerce_downloadable_product_permissions table (for user download permissions)
		if ( table_exists( $wpdb->prefix . 'woocommerce_downloadable_product_permissions' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_downloadable_product_permissions',
				array( 'user_id' => $new_id ),
				array( 'user_id' => $old_id ),
			);
		}

		// Update wp_woocommerce_api_keys (for API keys)
		if ( table_exists( $wpdb->prefix . 'woocommerce_api_keys' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_api_keys',
				array( 'user_id' => $new_id ),
				array( 'user_id' => $old_id ),
			);
		}

		// Update wp_wc_download_log (for download logs)
		if ( table_exists( $wpdb->prefix . 'wc_download_log' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'wc_download_log',
				array( 'user_id' => $new_id ),
				array( 'user_id' => $old_id ),
			);
		}

		// Update wp_wc_customer_lookup (for customer lookup)
		if ( table_exists( $wpdb->prefix . 'wc_customer_lookup' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'wc_customer_lookup',
				array( 'user_id' => $new_id ),
				array( 'user_id' => $old_id ),
			);
		}

		// Update HPOS tables if HPOS is enabled
		if ( class_exists( 'Automattic\\WooCommerce\\Admin\\Features\\CustomOrdersTableController' ) ) {
			// Update HPOS orders table
			$wpdb->update(
				$wpdb->prefix . 'wc_orders',
				array( 'customer_id' => $new_id ),
				array( 'customer_id' => $old_id ),
			);
			
			// Update HPOS order meta table
			$wpdb->update(
				$wpdb->prefix . 'wc_order_meta',
				array( 'meta_value' => $new_id ),
				array(
					'meta_key'   => '_customer_user',
					'meta_value' => $old_id,
				),
			);
		}

		// Other custom tables

		// Update wp_stock_log table
		if ( table_exists( $wpdb->prefix . 'stock_log' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'stock_log',
				array( 'user_id' => $new_id ),
				array( 'user_id' => $old_id ),
			);
		}

		// Update wp_wallet_log table
		if ( table_exists( $wpdb->prefix . 'wallet_log' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'wallet_log',
				array( 'user_id' => $new_id ),
				array( 'user_id' => $old_id ),
			);
		}

		// Update wp_wallet_transaction table
		if ( table_exists( $wpdb->prefix . 'wallet_transaction' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'wallet_transaction',
				array( 'user_id' => $new_id ),
				array( 'user_id' => $old_id ),
			);
		}

		// Update wp_wflogins table (wordfence)
		if ( table_exists( $wpdb->prefix . 'wflogins' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'wflogins',
				array( 'userID' => $new_id ),
				array( 'userID' => $old_id ),
			);
		}

		// Update wp_wfhits table (wordfence)
		if ( table_exists( $wpdb->prefix . 'wfhits' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'wfhits',
				array( 'userID' => $new_id ),
				array( 'userID' => $old_id ),
			);
		}

		// Update wp_wfls_2fa_secrets table (wordfence)
		if ( table_exists( $wpdb->prefix . 'wfls_2fa_secrets' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'wfls_2fa_secrets',
				array( 'user_id' => $new_id ),
				array( 'user_id' => $old_id ),
			);
		}
	}

	// Reset auto-increment value
	$max_user_id = $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->users}" );

	$wpdb->query( "ALTER TABLE {$wpdb->users} AUTO_INCREMENT = " . ( $max_user_id + 1 ) );
}

register_activation_hook( __FILE__, 'update_user_ids' );