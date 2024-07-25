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
 * Add the option page
 */
function ywp_custom_user_id_update_menu() {
	add_menu_page(
		'User ID Update', // Page title
		'User ID Update', // Menu title
		'edit_users', // Capability
		'user-id-update', // Menu slug
		'ywp_custom_user_id_update_page' // Callback function
	);
}

add_action( 'admin_menu', 'ywp_custom_user_id_update_menu' );

/**
 * Display the option page
 */
function ywp_custom_user_id_update_page() {
	?>
    <div class="wrap">
        <h1>User ID Update</h1>
        <div id="progress-container"
             style="width: 100%; background-color: #fff;border-radius: 8px;overflow: hidden;padding: 2px;border: 1px solid #b5b5b5;margin-bottom: 10px;">
            <div id="progress-bar"
                 style="width: 0; height: 30px; background-color: rgb(76, 175, 80); border-radius: 0px 7px 7px 0px;"></div>
        </div>
        <button id="start-update" class="button button-primary">Start Update</button>
        <textarea id="user-id-log" readonly style="width: 100%; height: 200px; margin-top: 20px;background: #fff"
                  placeholder="The operation progress log will be displayed here"></textarea>
    </div>
	<?php
}

/**
 * Enqueue the JavaScript
 */
function ywp_enqueue_custom_user_id_update_script( $hook ) {
	if ( $hook !== 'toplevel_page_user-id-update' ) {
		return;
	}

	wp_enqueue_script( 'custom-user-id-update', plugin_dir_url( __FILE__ ) . 'js/user-id-update.js', array( 'jquery' ), null, true );
	wp_localize_script( 'custom-user-id-update', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

add_action( 'admin_enqueue_scripts', 'ywp_enqueue_custom_user_id_update_script' );

/**
 * AJAX handler to update all user ids occurrences
 *
 * @return void
 */
function update_user_ids() {
	if ( ! current_user_can( 'edit_users' ) ) {
		wp_die( - 1 );
	}

	global $wpdb;

	// Get the current user ID from the AJAX request
	$old_id = intval( $_POST['user_id'] );

	// Get the current auto-increment value
	$auto_increment = $wpdb->get_var( "
    									SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES
     									WHERE TABLE_SCHEMA = DATABASE()
      										AND TABLE_NAME = '{$wpdb->users}'
      									",
	);

	$new_id = $auto_increment;

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
		if ( table_exists( $wpdb->prefix . 'wc_orders' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'wc_orders',
				array( 'customer_id' => $new_id ),
				array( 'customer_id' => $old_id ),
			);
		}

		// Update HPOS order meta table
		if ( table_exists( $wpdb->prefix . 'wc_order_meta' ) ) {
			$wpdb->update(
				$wpdb->prefix . 'wc_order_meta',
				array( 'meta_value' => $new_id ),
				array(
					'meta_key'   => '_customer_user',
					'meta_value' => $old_id,
				),
			);
		}
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


	// Reset auto-increment value
	$max_user_id = $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->users}" );

	$wpdb->query( "ALTER TABLE {$wpdb->users} AUTO_INCREMENT = " . ( $max_user_id + 1 ) );

	// Return the next user ID or "completed"
	$next_user_id = $old_id + 1;

	if ( $next_user_id > 200 ) {
		wp_send_json_success(
			array(
				'status' => 'completed',
				'old_id' => $old_id,
				'new_id' => $new_id,
			),
		);
	} else {
		wp_send_json_success(
			array(
				'status'       => 'continue',
				'next_user_id' => $next_user_id,
				'old_id'       => $old_id,
				'new_id'       => $new_id,
			),
		);
	}
}

add_action( 'wp_ajax_update_user_id', 'update_user_ids' );

/**
 * Function to check if a table exists
 */
function table_exists( $table_name ): bool {
	global $wpdb;

	return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
}