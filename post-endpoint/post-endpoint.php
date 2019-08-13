<?php
/**
 * Plugin
 *
 * @since             1.0.0
 * @package           get-post-plugin
 *
 * @wordpress-plugin
 * Plugin Name:       Get Post Plugin
 * Plugin URI:
 * Description:       The Get Post plugin that adds rest functionality
 * Version:           1.0.0
 * Author:            gshilin <gshilin@gmail.com>
 * Author URI:
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       get-post-plugin
 */

namespace Get_Post_Plugin;

define( 'SHORTINIT', true );

//// Enable WP_DEBUG mode
//define( 'WP_DEBUG', true );
//
//// Enable Debug logging to the /wp-content/debug.log file
//define( 'WP_DEBUG_LOG', true );
//
//// Disable display of errors and warnings
//define( 'WP_DEBUG_DISPLAY', false );
//@ini_set( 'display_errors', 0 );
//
//// Use dev versions of core JS and CSS files (only needed if you are modifying these core files)
//define( 'SCRIPT_DEBUG', true );

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class that holds all the necessary functionality for the
 * custom post type
 *
 * @since  1.0.0
 */
class Post {
	public function get_post_content( $request ) {
		/* Some permission checks can be added here. */

		// Get the combined, merged set of parameters:
		$parameters = $request->get_params();
		$slug       = $parameters['slug'];
		$page       = get_page_by_path( $slug );
		$all_meta   = get_post_custom( $page->ID );
		$req_meta   = explode( ',', $parameters['meta'] );
		$meta       = array();
		foreach ( $all_meta as $key => $value ) {
			if ( in_array( $key, $req_meta, true ) ) {
				$meta[ $key ] = $value[0];
			}
		}

		if ( $page ) {
			return [
				"content" => strip_html_comments( $page->post_content ),
				"meta"    => $meta,
			];
		} else {
			return [];
		}
	}

	public function get_posts_content( $request ) {
		$parameters = $request->get_params();
		$slug       = $parameters['slug'];
		$pattern    = '/^' . $slug . '-/';
		$items      = array();
		$pages      = get_pages( array() );
		if ( $pages ) {
			foreach ( $pages as $page ) {
				if ( preg_match( $pattern, $page->post_name ) ) {
					$all_meta = get_post_custom( $page->ID );
					$meta     = array_filter( $all_meta, function ( $v, $k ) {
						return ! preg_match( '/^_/', $k );
					}, ARRAY_FILTER_USE_BOTH );
					$items[]  = array(
						'id'      => $page->ID,
						'slug'    => $page->post_name,
						'title'   => $page->post_title,
						'content' => strip_html_comments( $page->post_content ),
						'meta'    => $meta,
					);
				}
			}
		}

		return $items;
	}

	public function create_post_endpoints() {
		register_rest_route(
			'get-post-plugin/v1', '/get-post/(?P<slug>.+)',
			array(
				'methods'  => 'GET',
				'callback' => [ $this, 'get_post_content' ],
				'args'     => array(
					'slug' => array(
						'required' => true,
					),
					'meta' => array(
						'required' => false,
					),
				),
			)
		);
		register_rest_route(
			'get-post-plugin/v1', '/get-posts/(?P<slug>.+)',
			array(
				'methods'  => 'GET',
				'callback' => [ $this, 'get_posts_content' ],
				'args'     => array(
					'slug' => array(
						'required' => true,
					),
					'meta' => array(
						'required' => false,
					),
				),
			)
		);
	}
}

function strip_html_comments( string $html ) {
	return preg_replace( '/<!--(.*)-->/Uis', '', $html );
}

$post = new Post();

add_action( 'rest_api_init', [ $post, 'create_post_endpoints' ] );

// CORS -- enable to access api for everyone
remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
add_filter( 'rest_pre_serve_request', function ( $value ) {
	header( 'Access-Control-Allow-Origin: *' );
	header( 'Access-Control-Allow-Methods: GET, OPTIONS' );
	header( 'Access-Control-Allow-Credentials: true' );

	return $value;
} );

// Enable meta over REST API
register_meta('post', 'name', array(
	'show_in_rest' => true,
	'single' => true,
));
register_meta('post', 'unit', array(
	'show_in_rest' => true,
	'single' => true,
));