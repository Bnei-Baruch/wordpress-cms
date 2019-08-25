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

	public function post_endpoints() {
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

class Banner {
	private $req_meta = [ 'header', 'sub-header', 'link', 'image' ];

	private function get_meta( $post_id ) {
		$all_meta = get_post_custom( $post_id );
		$meta     = array();
		foreach ( $all_meta as $key => $value ) {
			if ( in_array( $key, $this->req_meta, true ) ) {
				if ( $key != 'image' ) {
					$meta[ $key ] = $value[0];
				} else {
					$meta[ $key ] = wp_get_attachment_image_src( $value[0], 'full' )[0];
				}
			}
		}

		return $meta;
	}

	public function get_banner_content( $request ) {
		$parameters = $request->get_params();
		$slug       = $parameters['slug'];
		$args       = array(
			'name'                   => $slug,
			'post_type'              => 'banner',
			'post_status'            => 'publish',
			'numberofposts'          => 1,
			'update_post_term_cache' => false,
		);
		$posts      = get_posts( $args );
		if ( ! $posts ) {
			return [];
		}

		$meta = $this->get_meta( $posts[0]->ID );

		return [ 'meta' => $meta ];
	}

	public function get_banners_content( $request ) {
		$args  = array(
			'post_type'              => 'banner',
			'post_status'            => 'publish',
			'posts_per_page'         => - 1,
			'update_post_term_cache' => false,
		);
		$posts = get_posts( $args );
		if ( ! $posts ) {
			return [];
		}

		$banners = array();
//		$banners[] = array(
//			'total' => count( $posts )
//		);
		foreach ( $posts as $post ) {
			$meta      = $this->get_meta( $post->ID );
			$banners[] = array(
				'id'    => $post->ID,
				'slug'  => $post->post_name,
				'title' => $post->post_title,
				'meta'  => $meta,
			);
		}

		return $banners;
	}

	public function banner_endpoints() {
		register_rest_route(
			'get-post-plugin/v1', '/get-banner/(?P<slug>.+)',
			array(
				'methods'  => 'GET',
				'callback' => [ $this, 'get_banner_content' ],
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
			'get-post-plugin/v1', '/get-banners/(?P<slug>.+)',
			array(
				'methods'  => 'GET',
				'callback' => [ $this, 'get_banners_content' ],
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

class Person {
	public function get_person_content( $request ) {
		$parameters = $request->get_params();
		$slug       = $parameters['slug'];
		$args       = array(
			'name'                   => $slug,
			'post_type'              => 'person',
			'post_status'            => 'publish',
			'numberofposts'          => 1,
			'update_post_term_cache' => false,
		);
		$posts      = get_posts( $args );
		if ( ! $posts ) {
			return [];
		}

		$person = $posts[0];

		return [
			'id'      => $person->ID,
			'slug'    => $person->post_name,
			'title'   => $person->post_title,
			'content' => strip_html_comments( $person->post_content ),
		];
	}

	public function get_persons_content( $request ) {
		$args  = array(
			'post_type'              => 'person',
			'post_status'            => 'publish',
			'posts_per_page'         => - 1,
			'update_post_term_cache' => false,
		);
		$posts = get_posts( $args );
		if ( ! $posts ) {
			return [];
		}

		$persons = array();
		foreach ( $posts as $person ) {
			$persons[] = array(
				'id'      => $person->ID,
				'slug'    => $person->post_name,
				'title'   => $person->post_title,
				'content' => strip_html_comments( $person->post_content ),
			);
		}

		return $persons;
	}

	public function person_endpoints() {
		register_rest_route(
			'get-post-plugin/v1', '/get-person/(?P<slug>.+)',
			array(
				'methods'  => 'GET',
				'callback' => [ $this, 'get_person_content' ],
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
			'get-post-plugin/v1', '/get-persons/(?P<slug>.+)',
			array(
				'methods'  => 'GET',
				'callback' => [ $this, 'get_persons_content' ],
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

function strip_html_comments( $html ) {
	return preg_replace( '/<!--(.*)-->/Uis', '', $html );
}

$post = new Post();
add_action( 'rest_api_init', [ $post, 'post_endpoints' ] );

$banner = new Banner();
add_action( 'rest_api_init', [ $banner, 'banner_endpoints' ] );

$person = new Person();
add_action( 'rest_api_init', [ $person, 'person_endpoints' ] );

// CORS -- enable to access api for everyone
remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
add_filter( 'rest_pre_serve_request', function ( $value ) {
	header( 'Access-Control-Allow-Origin: *' );
	header( 'Access-Control-Allow-Methods: GET, OPTIONS' );
	header( 'Access-Control-Allow-Credentials: true' );

	return $value;
} );

add_action( 'init', function () {
	register_post_type( 'banner', [
		'public'       => true,
		'label'        => 'Banners',
		'show_in_rest' => true,
	] );
} );

add_action( 'init', function () {
	register_post_type( 'person', [
		'public'       => true,
		'label'        => 'Persons',
		'show_in_rest' => true,
	] );
} );
