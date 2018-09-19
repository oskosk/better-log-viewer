<?php
/**
 * Plugin Name: Better Log Viewer
 */

if ( ! defined( 'ABSPATH' ) ) {
	die('');
}

define( 'PATH_BLV', __FILE__ );

add_action( 'admin_init', 'init_blv' );
add_action( 'rest_api_init', 'init_rest_api_blv' );

function init_rest_api_blv() {
	register_endpoints_blv();
}

function init_blv() {
	if ( ! is_gutenberg_available_blv()  ) {
		error_log( 'Better Log Viewer cannot work without wp.element' );
		return;
	}
	enqueue_wp_element_blv();
	register_endpoints_blv();
}

function enqueue_wp_element_blv() {
	
	add_action( 'admin_enqueue_scripts', function() {
		//wp_enqueue_script( 'wp-element' );
		wp_enqueue_script( 'better-log-viewer-js', plugins_url( 'better-log-viewer.js', PATH_BLV ), [ 'wp-element' ] );
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
	) );
}


function show_debug_log_blv() {
	$debuglog = wpdebuglog_path_blv();
	$contents = file_get_contents( $debuglog );	
	return explode( "\n", $contents );
}
