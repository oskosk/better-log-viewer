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

function gutenberg_is_available_blv() {
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
			return true;
			return current_user_can( 'manage_options' );
		}
	) );
}


function show_debug_log_blv() {
	$debuglog = wpdebuglog_path_blv();
	$contents = tailCustom_blv( $debuglog, 100 );
	$contents = explode( "\n", $contents );
	$buffer = '';
	$new_buffer = false;
	$final = [];
	$found_line_with_date = false;

	foreach( $contents as $i => $line ) {
		if ( 1 === preg_match( '(\[.*\d{2}:\d{2}:\d{2}.*])', $line ) ) {
			if ( ! $found_line_with_date ) {
				$found_line_with_date = true;
			}
			// IF a line with timtestamp is found
			// collect in buffer until new line with timestamp is found
			$final[] = $buffer;
			$buffer = "$line\n";
			// But if it's the last line in the tail,
			// then just add this to the final array
			if ( count( $contents ) === ( $i + 1) ) {
				// Handle last line WITH timestamp
				$final[] = $buffer;
			} else {
				continue;
			}
		}
		if ( $found_line_with_date ) {
			$buffer .= "$line\n";
		}
		// Handle last line without timestamp
		if ( count( $contents ) === ( $i + 1 ) ) {
			$final[] = $buffer;
		}
		// Handle the case where no line in the tail has timestamp
		if ( count( $contents ) === ( $i + 1 ) && ! $found_line_with_date ) {
			$final[] = implode( "\n", $contents );
		}
	}
	// return ( $final );
	return array_reverse( $final );
}




function add_menu_page_blv() {
	add_menu_page(
		__( 'Better Log Viewer', 'textdomain' ),
		'Better Log Viewer',
		'manage_options',
		'better-log-viewer',
		function () {
			if ( ! gutenberg_is_available_blv()  ) {
				error_log( 'Better Log Viewer cannot work without wp.element' );
				?>
				<div id="better-log-viewer-error" class="wrap">
					<h1 class="wp-heading-inline">Better Log Viewer</h1>
					<strong>Better Log Viewer can only work if Gutenberg is available as it relies
						on <code>wp.element</code>.
					</strong>
				</div>
				<?php
				return;
			}
			?>
			<div id="better-log-viewer-container" class="wrap">
				<h1 class="wp-heading-inline">Better Log Viewer</h1>
				<div id="better-log-viewer-scroll" class="">
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