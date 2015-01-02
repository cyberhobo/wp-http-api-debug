<?php
/*
 * Plugin Name: WP Http API Debug
 * Plugin URI:  https://github.com/cyberhobo/wp-http-api-debug
 * Description: Provides a way to record and view recent requests made using the WordPress HTTP API.
 * Version:     0.1.0
 * Author:      Dylan Kuhn
 * Author URI:  http://cyberhobo.net/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: cyberhobo/wp-http-api-debug
 */

DKK_Http_Api_Debug::load();

class DKK_Http_Api_Debug {

	protected static $options_name = 'dkk_http_api_debug_options';
	protected static $options_action = 'update_options';
	protected static $clear_log_action = 'clear_log';
	protected static $log_name = 'dkk_http_api_debug_log';
	protected static $menu_slug = 'dkk-http-api-debug';

	protected static $default_options = array(
		'log_max' => 50,
		'url_regex' => '',
	);

	public static function load() {

		add_filter( 'http_api_debug', array( __CLASS__, 'log_request' ), 10, 5 );

		add_action( 'admin_menu', array( __CLASS__, 'action_admin_menu' ) );

	}

	public static function log_request( $response, $context, $transport, $request, $url ) {

		if ( 'response' != $context )
			return $response;

		$options = get_option( self::$options_name, self::$default_options );

		if ( !empty( $options['url_regex' ] ) and !preg_match( '#' . $options['url_regex'] . '#', $url ) )
			return $response;

		$log = self::get_log();

		if ( count( $log ) >= $options['log_max'] )
			$log = array_slice( $log, 0, $options['log_max'] - 1 );

		$timestamp = date( 'c' );

  		array_unshift( $log, compact( 'timestamp', 'url', 'request', 'transport', 'response' ) );

		update_option( self::$log_name, $log );
	}

	public static function action_admin_menu() {

		add_submenu_page(
			'tools.php',
			'HTTP API Debug',
			'HTTP API Debug',
			'manage_options',
			self::$menu_slug,
			array( __CLASS__, 'page_content' )
		);

	}

	public static function page_content() {

		if ( isset( $_POST['action'] ) )
			call_user_func( array( __CLASS__, $_POST['action'] ) );

		echo '<h2>Options</h2>';

		$options = get_option( self::$options_name, self::$default_options );

		printf( '<form method="POST">' );
		printf( '<input name="action" type="hidden" value="%s" />', self::$options_action );
		printf( '<label for="url_regex">Log only URLs matching regular expression:</label>' );
		printf( '<input name="url_regex" id="url_regex" type="text" value="%s" />', $options['url_regex'] );
		printf( '<label for="log_max">Number of requests to log:</label>' );
		printf( '<input name="log_max" id="log_max" type="text" value="%s" />', $options['log_max'] );
		printf( '<input type="submit" class="button" />' );
		printf( '</form>' );

		echo '<h2>Log</h2>';

		$log = self::get_log();
		if ( !is_serialized_string( $log ) )
			$log = serialize( $log );

		echo '<pre>';
		echo $log;
		echo '</pre>';

		printf( '<form method="POST">' );
		printf( '<input name="action" type="hidden" value="%s" />', self::$clear_log_action );
		printf( '<input type="submit" class="button" value="Clear log" />' );
		printf( '</form>' );
	}

	public static function update_options() {
		$options = array(
			'url_regex' => trim( $_POST['url_regex'], '/#' ),
			'log_max' => intval( $_POST['log_max'] ),
		);
		update_option( self::$options_name, $options );

		echo '<div class="updated"><p>Options updated.</p></div>';
	}

	public static function clear_log() {
		update_option( self::$log_name, array() );
		echo '<div class="updated"><p>Log cleared.</p></div>';
	}

	protected static function get_log() {
		return get_option( self::$log_name, array() );
	}
}
