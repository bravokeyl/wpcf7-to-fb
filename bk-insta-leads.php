<?php
/*
 * Plugin Name: InstaLeads
 * Plugin URI:  https://mu-bit.com/
 * Description: Contact form 7 to Facebook page post
 * Author:      bravokeyl
 * Version:     1.2.1
 * Author URI:  https://bravokeyl.com
 *
*/

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'BKIL_PLUGIN', __FILE__ );
define( 'BKIL_VERSION', '1.0.1' );
define( 'BKIL_MINIMUM_WP_VERSION', '4.0' );
define( 'BKIL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BKIL_API_URL', '');
define( 'BKIL_API_KEY', '' );

function bkil_plugin_url( $path = '' ) {
	$url = plugins_url( $path, BKIL_PLUGIN );
	if ( is_ssl() && 'http:' == substr( $url, 0, 5 ) ) {
		$url = 'https:' . substr( $url, 5 );
	}
	return $url;
}

function bkil_post_tofb($data){
	$rdata = array(
		'timeout' => 60,
		'headers' => array(
			'Content-Type' => 'application/json; charset=utf-8',
			'X-Api-Key' => BKIL_API_KEY,
			'X-Pithre' => 'bad.ra',
			'origin' => get_http_origin(),
			'Referer' => wp_get_referer(),
		),
	 	'body' => json_encode($data),
	);
	$response = wp_remote_post(BKIL_API_URL, $rdata);
}

function bkil_mailsent($cf, $result){
	if ( empty( $result['status'] ) ) {
		return;
	}
	$submission = WPCF7_Submission::get_instance();
	if ( ! $submission || ! $posted_data = $submission->get_posted_data() ) {
		return;
	}
	foreach ( $posted_data as $key => $value ) {
		if ( '_' == substr( $key, 0, 1 ) ) {
			unset( $posted_data[$key] );
		}
	}
	if ( 'mail_sent' == $result['status'] ) {
		bkil_post_tofb($posted_data);
	}
}

add_action('wpcf7_submit', 'bkil_mailsent', 10, 2);

/* Log HTTP Requests partially */
function bkil_log_http_requests( $response, $args, $url ) {
	$bklog = BKIL_PLUGIN_DIR . '/bkil.log';

	if ( is_wp_error( $response ) ) {
		$error_message = $response->get_error_message();
		file_put_contents( $bklog, sprintf( "### %s, URL: %s\nREQUEST: %sERROR: %s\n", date( 'c' ), $url, print_r( $args, true ), print_r( $error_message, true ) ), FILE_APPEND );
	} else {
		file_put_contents( $bklog, sprintf( "=== %s ===\nURL: %s\nREQUEST: %s\n", date( 'c' ), $url, print_r( $args['body'], true )), FILE_APPEND );
	}

	return $response;
}
add_filter( 'http_response', 'bkil_log_http_requests', 10, 3 );
