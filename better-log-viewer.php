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
	$contents = tailCustom_blv( $debuglog, 100 );
	$contents = array_reverse( explode( "\n", $contents ) );
	$contents = array_map( function( $line ) {
		return preg_replace('`\[([^\]]*)\]`' , '$1===:::', $line);
	}, $contents );
	return $contents;
}




function add_menu_page_blv() {
	add_menu_page(
		__( 'Better Log Viewer', 'textdomain' ),
		'Better Log Viewer',
		'manage_options',
		'better-log-viewer',
		function () { 
			?>
			<div id="better-log-viewer-container">
				<h1>Better Log Viewer</h1>
				<div id="better-log-viewer-scroll">
				</div>
			</div>
			<?php
		}
	);
	add_action( 'admin_head', 'hide_update_notices_blv', 1 );

}

function hide_update_notices_blv() {
	if ( page_is_better_log_viewer_blv() ) {
		remove_all_actions( 'admin_notices' );
	}
}

/**
 * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
 * @author Torleif Berger, Lorenzo Stanco
 * @link http://stackoverflow.com/a/15025877/995958
 * @license http://creativecommons.org/licenses/by/3.0/
 */
function tailCustom_blv($filepath, $lines = 1, $adaptive = true) {
	// Open file
	$f = @fopen($filepath, "rb");
	if ($f === false) return false;
	// Sets buffer size, according to the number of lines to retrieve.
	// This gives a performance boost when reading a few lines from the file.
	if (!$adaptive) $buffer = 4096;
	else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
	// Jump to last character
	fseek($f, -1, SEEK_END);
	// Read it and adjust line number if necessary
	// (Otherwise the result would be wrong if file doesn't end with a blank line)
	if (fread($f, 1) != "\n") $lines -= 1;

	// Start reading
	$output = '';
	$chunk = '';
	// While we would like more
	while (ftell($f) > 0 && $lines >= 0) {
		// Figure out how far back we should jump
		$seek = min(ftell($f), $buffer);
		// Do the jump (backwards, relative to where we are)
		fseek($f, -$seek, SEEK_CUR);
		// Read a chunk and prepend it to our output
		$output = ($chunk = fread($f, $seek)) . $output;
		// Jump back to where we started reading
		fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
		// Decrease our line counter
		$lines -= substr_count($chunk, "\n");
	}
	// While we have too many lines
	// (Because of buffer size we might have read too many)
	while ($lines++ < 0) {
		// Find first newline and remove all text before that
		$output = substr($output, strpos($output, "\n") + 1);
	}
	// Close file and return
	fclose($f);
	return trim($output);
}