<?php
/**
 * Plugin Name: Better Log Viewer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PATH_BLV', __FILE__ );

init_blv();

function init_blv() {
	add_action( 'admin_init', 'admin_init_blv' );
	add_action( 'rest_api_init', 'init_rest_api_blv' );
	add_action( 'admin_menu', 'add_menu_page_blv' );
}

function admin_init_blv() {
	if ( ! is_gutenberg_available_blv()  ) {
		error_log( 'Better Log Viewer cannot work without wp.element' );
		return;
	}	
	enqueue_wp_element_blv();
}

function init_rest_api_blv() {
	register_endpoints_blv();
	add_action( 'wp_enqueue_scripts', function() {
		// Add the nonce under the /create path and
		// if the user can manage options, add it also on /specialops
		if ( page_is_better_log_viewer_blv() ) {
			wp_localize_script( 'better-log-viewer-js', 'restApiSettings', array(
				'root' => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			) );
		}
	} );
}

function page_is_better_log_viewer_blv() {
	return isset( $_GET['page'] ) && $_GET['page'] === 'better-log-viewer';
}



function enqueue_wp_element_blv() {
	wp_register_script( 'better-log-viewer-js', plugins_url( 'better-log-viewer.js', PATH_BLV ), [ 'wp-element', 'wp-data', 'wp-api-fetch' ] );
	
	add_action( 'admin_enqueue_scripts', function() {
		//wp_enqueue_script( 'wp-element' );
		wp_enqueue_script( 'better-log-viewer-js' );
	} );
}

function is_gutenberg_available_blv() {
	return function_exists( 'register_block_type' );
}
function wpdebuglog_path_blv() {
	return WP_CONTENT_DIR . '/debug.log';
}

function register_endpoints_blv() {
	register_rest_route( 'better-log-viewer/v1', '/debug.log', array(
		'methods' => 'GET',
		'callback' => 'show_debug_log_blv',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		}
	) );
}


function show_debug_log_blv() {
	$debuglog = wpdebuglog_path_blv();
	$contents = file_get_contents( $debuglog );	
	return array_reverse( explode( "\n", $contents ) );
}




function add_menu_page_blv() {
	add_menu_page(
		__( 'Better Log Viewer', 'textdomain' ),
		'Better Log Viewer',
		'manage_options',
		'better-log-viewer',
		function () { echo '<div id="better-log-viewer-container"></div>'; }
	);
	add_action( 'admin_head', 'hide_update_notices_blv', 1 );

}

function hide_update_notices_blv() {
	if ( page_is_better_log_viewer_blv() ) {
		remove_all_actions( 'admin_notices' );
	}
}
