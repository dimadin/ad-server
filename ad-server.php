<?php

/**
 * The Ad Server Plugin
 *
 * Simple ad manager.
 *
 * @package Ad_Server
 * @subpackage Main
 */

/**
 * Plugin Name: Ad Server
 * Plugin URI:  http://blog.milandinic.com/wordpress/plugins/
 * Description: Simple ad manager.
 * Author:      Milan Dinić
 * Author URI:  http://blog.milandinic.com/
 * Version:     0.1-alpha-1
 * License:     GPL
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

require __DIR__ . '/vendor/autoload.php';

/**
 * Initialize a plugin.
 *
 * Load class when all plugins are loaded
 * so that other plugins can overwrite it.
 */
function ad_server_instantiate() {
	global $ad_server;
	$ad_server = new Ad_Server();
}
add_action( 'plugins_loaded', 'ad_server_instantiate', 15 );

if ( ! class_exists( 'Ad_Server' ) ) :
/**
 * Ad Server main class.
 *
 * Ad server using native WordPress.
 */
class Ad_Server {
	/**
	 * Path to plugin's directory.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Name of ad post type.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $ad_post_type;

	/**
	 * IP address.
	 *
	 * @access public
	 * 
	 * @var bool
	 */
	public $ip_address;

	/**
	 * Country of IP address.
	 *
	 * @access public
	 * 
	 * @var bool
	 */
	public $country;

	/**
	 * Continent of IP address.
	 *
	 * @access public
	 * 
	 * @var bool
	 */
	public $continent;

	/**
	 * Initialize Ad_Server object.
	 *
	 * Set class properties and add main methods to appropriate hooks.
	 *
	 * @access public
	 */
	public function __construct() {
		if ( ! defined( 'P2P_PLUGIN_VERSION' ) || ! defined( 'RWMB_VER' ) ) {
			add_action( 'tgmpa_register', array( 'Ad_Server', 'register_required_plugins' ) );
			return;
		}

		// Set path
		$this->path = rtrim( plugin_dir_path( __FILE__ ), '/' );

		// Set ad post type name
		$this->ad_post_type = $this->ad_post_type();

		// Register main hooks
		add_action( 'init',      array( $this, 'init'      )    );
		add_action( 'wp_loaded', array( $this, 'wp_loaded' ), 2 );
	}

	/**
	 * Register the required plugins for this plugin.
	 *
	 * @access public
	 */
	public static function register_required_plugins() {
		if ( ! function_exists( 'tgmpa' ) ) {
			return;
		}

		/*
		 * Array of plugin arrays. Required keys are name and slug.
		 * If the source is NOT from the .org repo, then source is also required.
		 */
		$plugins = array(
			array(
				'name'             => 'Posts 2 Posts',
				'slug'             => 'posts-to-posts',
				'required'         => true,
				'force_activation' => true,
			),
			array(
				'name'             => 'Meta Box',
				'slug'             => 'meta-box',
				'required'         => true,
				'force_activation' => true,
			),
		);

		tgmpa( $plugins );
	}

	/**
	 * Register post types and add most of the hooks.
	 *
	 * @access public
	 */
	public function init() {
		// Register ad post types
		$default_ad_post_type_labels = array(
			'name'                => _x( 'Ads', 'Post Type General Name', 'ad-server' ),
			'singular_name'       => _x( 'Ad', 'Post Type Singular Name', 'ad-server' ),
			'menu_name'           => __( 'Ads', 'ad-server' ),
			'name_admin_bar'      => __( 'Ads', 'ad-server' ),
			'parent_item_colon'   => __( 'Parent Ad:', 'ad-server' ),
			'all_items'           => __( 'All Ads', 'ad-server' ),
			'add_new_item'        => __( 'Add New Ad', 'ad-server' ),
			'add_new'             => __( 'Add New', 'ad-server' ),
			'new_item'            => __( 'New Ad', 'ad-server' ),
			'edit_item'           => __( 'Edit Ad', 'ad-server' ),
			'update_item'         => __( 'Update Ad', 'ad-server' ),
			'view_item'           => __( 'View Ad', 'ad-server' ),
			'search_items'        => __( 'Search Ad', 'ad-server' ),
			'not_found'           => __( 'Not found', 'ad-server' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'ad-server' ),
		);
		$default_ad_post_type_args = array(
			'label'               => __( 'Ad', 'ad-server' ),
			'description'         => __( 'Post Type Description', 'ad-server' ),
			'labels'              => $default_ad_post_type_labels,
			'supports'            => array( 'title', 'thumbnail', ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
		);
		/**
		 * Filter parameters used when registering ad post type.
		 *
		 * @see register_post_type()
		 *
		 * @param array $args The array of parameters used when registering ad post type.
		 */
		$ad_post_type_args = (array) apply_filters( 'ad_server_register_ad_post_type_args', $default_ad_post_type_args );
		$ad_post_type_args = wp_parse_args( $ad_post_type_args, $default_ad_post_type_args );

		register_post_type(
			$this->ad_post_type,
			$ad_post_type_args
		);
	}

	/**
	 * Load admin classes and get IP data.
	 *
	 * @access public
	 */
	public function wp_loaded() {
		// Set information about current IP
		new Ad_Server_IP_Resolver( $this );

		// Load additional classes for admin only
		if ( is_admin() ) {
			// Load admin files
			$this->maybe_load_admin();

			// Hook meta box class
			new Ad_Server_Meta_Box( $this );
		}
	}

	/**
	 * Get name of ad post type.
	 * @access public
	 *
	 * @return string $ad_post_type The name of the post type. Default 'ad'.
	 */
	public function ad_post_type() {
		/**
		 * Filter the name of the ad post type.
		 *
		 * @param string $ad_post_type The name of the ad post type. Default 'ad'.
		 */
		$ad_post_type = sanitize_key( apply_filters( 'ad_server_ad_post_type_name', 'ad' ) );

		return $ad_post_type;
	}

	/**
	 * Load Admin classes files if they are not loaded.
	 *
	 * @access public
	 */
	public function maybe_load_admin() {
		if ( ! class_exists( 'Ad_Server_Meta_Box' ) ) {
			require_once( $this->path . '/inc/class-ad-server-meta-box.php' );
		}
	}

	/**
	 * Get an array of elements from a zone.
	 *
	 * @access public
	 *
	 * @param int $zone ID of the zone.
	 * @return array $ad_data An array of elements of the ad.
	 */
	public function get_ad_server_zone_data( $zone ) {
		$keys    = array();
		$data    = array();

		$args = array (
			'post_type'      => $this->ad_post_type,
			'posts_per_page' => '-1',
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation'  => 'OR',
				array(
					'key'   => '_ad_server_ad_country',
					'value' => 'ALL',
				),
				array(
					'key'   => '_ad_server_ad_country',
					'value' => $this->country,
				),
			),
		);

		$ads = get_posts( $args );

		foreach ( $ads as $ad ) {
			$priority = get_post_meta( $ad, '_ad_server_priority', true );

			if ( $priority ) {
				$priorities = range( 1, $priority );

				foreach ( $priorities as $priority ) {
					$keys[] = $ad;
				}
			} else {
				$keys[] = $ad;
			}
		}

		// Get real random key https://php.net/manual/en/function.array-rand.php#112227
		$random_key = mt_rand( 0, count( $keys ) - 1 );
		$ad_id = $keys[ $random_key ];

		// Get ad's image
		$ad_image = get_the_post_thumbnail( $ad_id, 'full' );
		$ad_url   = get_post_meta( $ad_id, '_ad_server_url', true );

		$ad_data = array(
			'url'        => $ad_url,
			'image_html' => $ad_image,
		);

		return $ad_data;
	}

	/**
	 * Get ad HTML from a zone.
	 *
	 * @access public
	 *
	 * @param int $zone ID of the zone.
	 * @return string $ad_html HTML code of the ad.
	 */
	public function get_ad_server_zone( $zone ) {
		$ad_html = '';

		extract( $this->get_ad_server_zone_data( $zone ) );

		if ( $image_html && $url ) {
			$ad_html = '<a href="' . $url . '">' . $image_html . '</a>';
		}

		return $ad_html;
	}
}
endif;
