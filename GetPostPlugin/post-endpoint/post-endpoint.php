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

		$banner = $posts[0];
		$image  = wp_get_attachment_image_src( $banner->image, 'full' )[0];

		return [
			'meta' => array(
				'header'     => $banner->header,
				'link'       => $banner->link,
				'sub-header' => $banner->sub_header,
				'image'      => $image,
			)
		];
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
		foreach ( $posts as $banner ) {
			$image     = wp_get_attachment_image_src( $banner->image, 'full' )[0];
			$banners[] = array(
				'id'    => $banner->ID,
				'slug'  => $banner->post_name,
				'title' => $banner->post_title,
				'meta'  => array(
					'header'     => $banner->header,
					'link'       => $banner->link,
					'sub-header' => $banner->sub_header,
					'image'      => $image,
				),
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

class Source {
	private function one_source( $source, $skip_content ) {
		return [
			'id'       => $source->ID,
			'slug'     => $source->post_name,
			'title'    => $source->title,
			'unit'     => $source->unit,
			'language' => $source->language,
			'md5'      => $source->md5,
			'content'  => $skip_content ? "" : strip_html_comments( $source->post_content ),
		];
	}

	public function get_source( $request ) {
		$parameters = $request->get_params();
		$slug       = $parameters['slug'];
		$args       = array(
			'name'                   => $slug,
			'post_type'              => 'source',
			'post_status'            => 'publish',
			'numberofposts'          => 10,
			'update_post_term_cache' => false,
		);
		$posts      = get_posts( $args );
		if ( ! $posts ) {
			return [];
		}

		$parameters   = $request->get_params();
		$skip_content = $parameters['skip_content'] == 'true';

		return [ $this->one_source( $posts[0], $skip_content ) ];
	}

	public function delete_sources() {
		$args = array(
			'post_type'              => 'source',
			'post_status'            => 'publish',
			'update_post_term_cache' => false,
			'numberposts'            => 1000,
		);

		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			// Delete's each post.
			wp_delete_post( $post->ID, true );
			// Set to False if you want to send them to Trash.
		}

		return 'Deleted ' . count( $posts );
	}

	public function get_sources( $request ) {
		$parameters = $request->get_params();
		$page       = $parameters['page'];
		$args       = array(
			'posts_per_page'         => 1000,
			'orderby'                => 'slug',
			'order'                  => 'ASC',
			'post_type'              => 'source',
			'post_status'            => 'publish',
			'update_post_term_cache' => false,
			'paged'                  => $page,
		);
		$posts      = get_posts( $args );
		if ( ! $posts ) {
			return [];
		}

		$skip_content = $parameters['skip_content'] == 'true';
		$sources = array();
		foreach ( $posts as $source ) {
			$sources[] = $this->one_source( $source, $skip_content );
		}

		return $sources;
	}

	public function set_source( $request ) {
		$params  = $request->get_json_params();
		$post    = array(
			'post_type'    => 'source',
			'ID'           => $params['id'],
			'post_slug'    => $params['slug'],
			'post_title'   => $params['title'],
			'post_content' => $params['content'],
			'post_status'  => 'publish',
		);
		$post_id = wp_insert_post( $post, true );
		if ( is_wp_error( $post_id ) ) {
			$errors = $post_id->get_error_messages();

			return array( 'code' => "error", 'message' => $errors );
		}

		update_field( 'title', $params['title'], $post_id );
		update_field( 'unit', $params['unit'], $post_id );
		update_field( 'language', $params['language'], $post_id );
		update_field( 'md5', $params['md5'], $post_id );

		return array( 'code' => 'success', 'message' => '', 'data' => array( 'post_id' => $post_id ) );
	}

	public function source_endpoints() {
		register_rest_route(
			'get-post-plugin/v1', '/get-source/(?P<slug>.+)',
			array(
				'methods'  => 'GET',
				'callback' => [ $this, 'get_source' ],
				'args'     => array(
					'slug'         => array(
						'required' => true,
					),
					'skip_content' => array(
						'required' => false,
						'default'  => 'false',
					),
				),
			)
		);
		register_rest_route(
			'get-post-plugin/v1', '/set-source',
			array(
				'methods'  => 'POST',
				'callback' => [ $this, 'set_source' ],
				'args'     => array(
					'id'       => array(),
					'slug'     => array(),
					'unit'     => array(),
					'language' => array(),
					'md5'      => array(),
					'content'  => array(),
				),
			)
		);
		register_rest_route(
			'get-post-plugin/v1', '/get-sources',
			array(
				'methods'  => 'GET',
				'callback' => [ $this, 'get_sources' ],
				'args'     => array(
					'page' => array(
						'required' => false,
						'default'  => 1
					),
					'skip_content' => array(
						'required' => false,
						'default'  => 'false',
					),
				),
			)
		);
		register_rest_route(
			'get-post-plugin/v1', '/delete-sources',
			array(
				'methods'  => 'POST',
				'callback' => [ $this, 'delete_sources' ],
				'args'     => array(),
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

$source = new Source();
add_action( 'rest_api_init', [ $source, 'source_endpoints' ] );

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

add_action( 'init', function () {
	register_post_type( 'source', [
		'public'                => true,
		'label'                 => 'Sources',
		'show_in_rest'          => true,
		'rest_base'             => 'source',
		'rest_controller_class' => 'WP_REST_Posts_Controller'
	] );
} );
