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
 * Version:     0.1-alpha-2
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
	 * Name of publisher post type.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $publisher_post_type;

	/**
	 * Name of site post type.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $site_post_type;

	/**
	 * Name of page post type.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $page_post_type;

	/**
	 * Name of zone post type.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $zone_post_type;

	/**
	 * Name of advertiser post type.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $advertiser_post_type;

	/**
	 * Name of campaign post type.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $campaign_post_type;

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

		// Set post types names
		$this->publisher_post_type  = $this->publisher_post_type();
		$this->site_post_type       = $this->site_post_type();
		$this->page_post_type       = $this->page_post_type();
		$this->zone_post_type       = $this->zone_post_type();
		$this->advertiser_post_type = $this->advertiser_post_type();
		$this->campaign_post_type   = $this->campaign_post_type();
		$this->ad_post_type         = $this->ad_post_type();

		// Register main hooks
		add_action( 'init',      array( $this, 'init'             )    );
		add_action( 'p2p_init',  array( $this, 'connection_types' )    );
		add_action( 'wp_loaded', array( $this, 'wp_loaded'        ), 2 );

		// Register public AJAX handlers
		add_action( 'wp_ajax_ad_server_jsonp_page_data',        array( $this, 'jsonp_page' ) );
		add_action( 'wp_ajax_nopriv_ad_server_jsonp_page_data', array( $this, 'jsonp_page' ) );

		add_action( 'wp_ajax_ad_server_jsonp_zone_data',        array( $this, 'jsonp_zone' ) );
		add_action( 'wp_ajax_nopriv_ad_server_jsonp_zone_data', array( $this, 'jsonp_zone' ) );

		add_action( 'wp_ajax_ad_server_redirect_ad',            array( $this, 'redirect_ad' ) );
		add_action( 'wp_ajax_nopriv_ad_server_redirect_ad',     array( $this, 'redirect_ad' ) );
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
		// Register publisher post type
		$default_publisher_post_type_labels = array(
			'name'                => _x( 'Publishers', 'Post Type General Name', 'ad-server' ),
			'singular_name'       => _x( 'Publisher', 'Post Type Singular Name', 'ad-server' ),
			'menu_name'           => __( 'Publishers', 'ad-server' ),
			'name_admin_bar'      => __( 'Publishers', 'ad-server' ),
			'parent_item_colon'   => __( 'Parent Publisher:', 'ad-server' ),
			'all_items'           => __( 'All Publishers', 'ad-server' ),
			'add_new_item'        => __( 'Add New Publisher', 'ad-server' ),
			'add_new'             => __( 'Add New', 'ad-server' ),
			'new_item'            => __( 'New Publisher', 'ad-server' ),
			'edit_item'           => __( 'Edit Publisher', 'ad-server' ),
			'update_item'         => __( 'Update Publisher', 'ad-server' ),
			'view_item'           => __( 'View Publisher', 'ad-server' ),
			'search_items'        => __( 'Search Publisher', 'ad-server' ),
			'not_found'           => __( 'Not found', 'ad-server' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'ad-server' ),
		);
		$default_publisher_post_type_args = array(
			'label'               => __( 'Publisher', 'ad-server' ),
			'description'         => __( 'Post Type Description', 'ad-server' ),
			'labels'              => $default_publisher_post_type_labels,
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
		 * Filter parameters used when registering publisher post type.
		 *
		 * @see register_post_type()
		 *
		 * @param array $args The array of parameters used when registering ad post type.
		 */
		$publisher_post_type_args = (array) apply_filters( 'ad_server_register_publisher_post_type_args', $default_publisher_post_type_args );
		$publisher_post_type_args = wp_parse_args( $publisher_post_type_args, $default_publisher_post_type_args );

		register_post_type(
			$this->publisher_post_type,
			$publisher_post_type_args
		);

		// Register site post type
		$default_site_post_type_labels = array(
			'name'                => _x( 'Sites', 'Post Type General Name', 'ad-server' ),
			'singular_name'       => _x( 'Site', 'Post Type Singular Name', 'ad-server' ),
			'menu_name'           => __( 'Sites', 'ad-server' ),
			'name_admin_bar'      => __( 'Sites', 'ad-server' ),
			'parent_item_colon'   => __( 'Parent Site:', 'ad-server' ),
			'all_items'           => __( 'All Sites', 'ad-server' ),
			'add_new_item'        => __( 'Add New Site', 'ad-server' ),
			'add_new'             => __( 'Add New', 'ad-server' ),
			'new_item'            => __( 'New Site', 'ad-server' ),
			'edit_item'           => __( 'Edit Site', 'ad-server' ),
			'update_item'         => __( 'Update Site', 'ad-server' ),
			'view_item'           => __( 'View Site', 'ad-server' ),
			'search_items'        => __( 'Search Site', 'ad-server' ),
			'not_found'           => __( 'Not found', 'ad-server' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'ad-server' ),
		);
		$default_site_post_type_args = array(
			'label'               => __( 'Site', 'ad-server' ),
			'description'         => __( 'Post Type Description', 'ad-server' ),
			'labels'              => $default_site_post_type_labels,
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
		 * Filter parameters used when registering site post type.
		 *
		 * @see register_post_type()
		 *
		 * @param array $args The array of parameters used when registering ad post type.
		 */
		$site_post_type_args = (array) apply_filters( 'ad_server_register_site_post_type_args', $default_site_post_type_args );
		$site_post_type_args = wp_parse_args( $site_post_type_args, $default_site_post_type_args );

		register_post_type(
			$this->site_post_type,
			$site_post_type_args
		);

		// Register page post type
		$default_page_post_type_labels = array(
			'name'                => _x( 'Pages', 'Post Type General Name', 'ad-server' ),
			'singular_name'       => _x( 'Page', 'Post Type Singular Name', 'ad-server' ),
			'menu_name'           => __( 'Pages', 'ad-server' ),
			'name_admin_bar'      => __( 'Pages', 'ad-server' ),
			'parent_item_colon'   => __( 'Parent Page:', 'ad-server' ),
			'all_items'           => __( 'All Pages', 'ad-server' ),
			'add_new_item'        => __( 'Add New Page', 'ad-server' ),
			'add_new'             => __( 'Add New', 'ad-server' ),
			'new_item'            => __( 'New Page', 'ad-server' ),
			'edit_item'           => __( 'Edit Page', 'ad-server' ),
			'update_item'         => __( 'Update Page', 'ad-server' ),
			'view_item'           => __( 'View Page', 'ad-server' ),
			'search_items'        => __( 'Search Page', 'ad-server' ),
			'not_found'           => __( 'Not found', 'ad-server' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'ad-server' ),
		);
		$default_page_post_type_args = array(
			'label'               => __( 'Page', 'ad-server' ),
			'description'         => __( 'Post Type Description', 'ad-server' ),
			'labels'              => $default_page_post_type_labels,
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
		 * Filter parameters used when registering page post type.
		 *
		 * @see register_post_type()
		 *
		 * @param array $args The array of parameters used when registering ad post type.
		 */
		$page_post_type_args = (array) apply_filters( 'ad_server_register_page_post_type_args', $default_page_post_type_args );
		$page_post_type_args = wp_parse_args( $page_post_type_args, $default_page_post_type_args );

		register_post_type(
			$this->page_post_type,
			$page_post_type_args
		);

		// Register zone post type
		$default_zone_post_type_labels = array(
			'name'                => _x( 'Zones', 'Post Type General Name', 'ad-server' ),
			'singular_name'       => _x( 'Zone', 'Post Type Singular Name', 'ad-server' ),
			'menu_name'           => __( 'Zones', 'ad-server' ),
			'name_admin_bar'      => __( 'Zones', 'ad-server' ),
			'parent_item_colon'   => __( 'Parent Zone:', 'ad-server' ),
			'all_items'           => __( 'All Zones', 'ad-server' ),
			'add_new_item'        => __( 'Add New Zone', 'ad-server' ),
			'add_new'             => __( 'Add New', 'ad-server' ),
			'new_item'            => __( 'New Zone', 'ad-server' ),
			'edit_item'           => __( 'Edit Zone', 'ad-server' ),
			'update_item'         => __( 'Update Zone', 'ad-server' ),
			'view_item'           => __( 'View Zone', 'ad-server' ),
			'search_items'        => __( 'Search Zone', 'ad-server' ),
			'not_found'           => __( 'Not found', 'ad-server' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'ad-server' ),
		);
		$default_zone_post_type_args = array(
			'label'               => __( 'Zone', 'ad-server' ),
			'description'         => __( 'Post Type Description', 'ad-server' ),
			'labels'              => $default_zone_post_type_labels,
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
		 * Filter parameters used when registering zone post type.
		 *
		 * @see register_post_type()
		 *
		 * @param array $args The array of parameters used when registering ad post type.
		 */
		$zone_post_type_args = (array) apply_filters( 'ad_server_register_zone_post_type_args', $default_zone_post_type_args );
		$zone_post_type_args = wp_parse_args( $zone_post_type_args, $default_zone_post_type_args );

		register_post_type(
			$this->zone_post_type,
			$zone_post_type_args
		);

		// Register advertiser post type
		$default_advertiser_post_type_labels = array(
			'name'                => _x( 'Advertisers', 'Post Type General Name', 'ad-server' ),
			'singular_name'       => _x( 'Advertiser', 'Post Type Singular Name', 'ad-server' ),
			'menu_name'           => __( 'Advertisers', 'ad-server' ),
			'name_admin_bar'      => __( 'Advertisers', 'ad-server' ),
			'parent_item_colon'   => __( 'Parent Advertiser:', 'ad-server' ),
			'all_items'           => __( 'All Advertisers', 'ad-server' ),
			'add_new_item'        => __( 'Add New Advertiser', 'ad-server' ),
			'add_new'             => __( 'Add New', 'ad-server' ),
			'new_item'            => __( 'New Advertiser', 'ad-server' ),
			'edit_item'           => __( 'Edit Advertiser', 'ad-server' ),
			'update_item'         => __( 'Update Advertiser', 'ad-server' ),
			'view_item'           => __( 'View Advertiser', 'ad-server' ),
			'search_items'        => __( 'Search Advertiser', 'ad-server' ),
			'not_found'           => __( 'Not found', 'ad-server' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'ad-server' ),
		);
		$default_advertiser_post_type_args = array(
			'label'               => __( 'Advertiser', 'ad-server' ),
			'description'         => __( 'Post Type Description', 'ad-server' ),
			'labels'              => $default_advertiser_post_type_labels,
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
		 * Filter parameters used when registering advertiser post type.
		 *
		 * @see register_post_type()
		 *
		 * @param array $args The array of parameters used when registering ad post type.
		 */
		$advertiser_post_type_args = (array) apply_filters( 'ad_server_register_advertiser_post_type_args', $default_advertiser_post_type_args );
		$advertiser_post_type_args = wp_parse_args( $advertiser_post_type_args, $default_advertiser_post_type_args );

		register_post_type(
			$this->advertiser_post_type,
			$advertiser_post_type_args
		);

		// Register campaign post type
		$default_campaign_post_type_labels = array(
			'name'                => _x( 'Campaigns', 'Post Type General Name', 'ad-server' ),
			'singular_name'       => _x( 'Campaign', 'Post Type Singular Name', 'ad-server' ),
			'menu_name'           => __( 'Campaigns', 'ad-server' ),
			'name_admin_bar'      => __( 'Campaigns', 'ad-server' ),
			'parent_item_colon'   => __( 'Parent Campaign:', 'ad-server' ),
			'all_items'           => __( 'All Campaigns', 'ad-server' ),
			'add_new_item'        => __( 'Add New Campaign', 'ad-server' ),
			'add_new'             => __( 'Add New', 'ad-server' ),
			'new_item'            => __( 'New Campaign', 'ad-server' ),
			'edit_item'           => __( 'Edit Campaign', 'ad-server' ),
			'update_item'         => __( 'Update Campaign', 'ad-server' ),
			'view_item'           => __( 'View Campaign', 'ad-server' ),
			'search_items'        => __( 'Search Campaign', 'ad-server' ),
			'not_found'           => __( 'Not found', 'ad-server' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'ad-server' ),
		);
		$default_campaign_post_type_args = array(
			'label'               => __( 'Campaign', 'ad-server' ),
			'description'         => __( 'Post Type Description', 'ad-server' ),
			'labels'              => $default_campaign_post_type_labels,
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
		 * Filter parameters used when registering campaign post type.
		 *
		 * @see register_post_type()
		 *
		 * @param array $args The array of parameters used when registering ad post type.
		 */
		$campaign_post_type_args = (array) apply_filters( 'ad_server_register_campaign_post_type_args', $default_campaign_post_type_args );
		$campaign_post_type_args = wp_parse_args( $campaign_post_type_args, $default_campaign_post_type_args );

		register_post_type(
			$this->campaign_post_type,
			$campaign_post_type_args
		);

		// Register ad post type
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
	 * Connect post types via Posts 2 Posts.
	 *
	 * @access public
	 */
	public function connection_types() {
		// Site belongs to publisher
		p2p_register_connection_type( array(
			'name' => 'publisher_to_site',
			'from' => $this->publisher_post_type,
			'to'   => $this->site_post_type
		) );

		// Page belongs to site
		p2p_register_connection_type( array(
			'name' => 'site_to_page',
			'from' => $this->site_post_type,
			'to'   => $this->page_post_type
		) );

		// Zone belongs to page
		p2p_register_connection_type( array(
			'name' => 'page_to_zone',
			'from' => $this->page_post_type,
			'to'   => $this->zone_post_type
		) );

		// Campaign belongs to advertiser
		p2p_register_connection_type( array(
			'name' => 'advertiser_to_campaign',
			'from' => $this->advertiser_post_type,
			'to'   => $this->campaign_post_type
		) );

		// Ad belongs to campaign
		p2p_register_connection_type( array(
			'name' => 'campaign_to_ad',
			'from' => $this->campaign_post_type,
			'to'   => $this->ad_post_type
		) );

		// Ad can be added to zone
		p2p_register_connection_type( array(
			'name' => 'ad_to_zone',
			'from' => $this->ad_post_type,
			'to'   => $this->zone_post_type
		) );

		// Campaign can be added to zone
		p2p_register_connection_type( array(
			'name' => 'campaign_to_zone',
			'from' => $this->campaign_post_type,
			'to'   => $this->zone_post_type
		) );

		// Advertiser can be added to zone
		p2p_register_connection_type( array(
			'name' => 'advertiser_to_zone',
			'from' => $this->advertiser_post_type,
			'to'   => $this->zone_post_type
		) );
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
	 * Get name of publisher post type.
	 *
	 * @access public
	 *
	 * @return string $ad_post_type The name of the post type. Default 'ad_server_publisher'.
	 */
	public function publisher_post_type() {
		/**
		 * Filter the name of the publisher post type.
		 *
		 * @param string $ad_post_type The name of the publisher post type. Default 'ad_server_publisher'.
		 */
		$publisher_post_type = sanitize_key( apply_filters( 'ad_server_publisher_post_type_name', 'ad_server_publisher' ) );

		return $publisher_post_type;
	}

	/**
	 * Get name of site post type.
	 *
	 * @access public
	 *
	 * @return string $ad_post_type The name of the post type. Default 'ad_server_site'.
	 */
	public function site_post_type() {
		/**
		 * Filter the name of the site post type.
		 *
		 * @param string $ad_post_type The name of the site post type. Default 'ad_server_site'.
		 */
		$site_post_type = sanitize_key( apply_filters( 'ad_server_site_post_type_name', 'ad_server_site' ) );

		return $site_post_type;
	}

	/**
	 * Get name of page post type.
	 *
	 * @access public
	 *
	 * @return string $ad_post_type The name of the post type. Default 'ad_server_page'.
	 */
	public function page_post_type() {
		/**
		 * Filter the name of the page post type.
		 *
		 * @param string $ad_post_type The name of the page post type. Default 'ad_server_page'.
		 */
		$page_post_type = sanitize_key( apply_filters( 'ad_server_page_post_type_name', 'ad_server_page' ) );

		return $page_post_type;
	}

	/**
	 * Get name of zone post type.
	 *
	 * @access public
	 *
	 * @return string $ad_post_type The name of the post type. Default 'ad_server_zone'.
	 */
	public function zone_post_type() {
		/**
		 * Filter the name of the zone post type.
		 *
		 * @param string $ad_post_type The name of the zone post type. Default 'ad_server_zone'.
		 */
		$zone_post_type = sanitize_key( apply_filters( 'ad_server_zone_post_type_name', 'ad_server_zone' ) );

		return $zone_post_type;
	}

	/**
	 * Get name of advertiser post type.
	 *
	 * @access public
	 *
	 * @return string $ad_post_type The name of the post type. Default 'ad_server_advertiser'.
	 */
	public function advertiser_post_type() {
		/**
		 * Filter the name of the advertiser post type.
		 *
		 * @param string $ad_post_type The name of the advertiser post type. Default 'ad_server_advertiser'.
		 */
		$advertiser_post_type = sanitize_key( apply_filters( 'ad_server_advertiser_post_type_name', 'ad_server_advertiser' ) );

		return $advertiser_post_type;
	}

	/**
	 * Get name of campaign post type.
	 *
	 * @access public
	 *
	 * @return string $ad_post_type The name of the post type. Default 'ad_server_campaign'.
	 */
	public function campaign_post_type() {
		/**
		 * Filter the name of the campaign post type.
		 *
		 * @param string $ad_post_type The name of the campaign post type. Default 'ad_server_campaign'.
		 */
		$campaign_post_type = sanitize_key( apply_filters( 'ad_server_campaign_post_type_name', 'ad_server_campaign' ) );

		return $campaign_post_type;
	}

	/**
	 * Get name of ad post type.
	 *
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
	 * Get an array of zones from a page.
	 *
	 * @access public
	 *
	 * @param int $page ID of the page.
	 * @return array $page_data An array of zones of the page.
	 */
	public function get_page_data( $page ) {
		$page_data = array();

		$args = array (
			'post_type'       => $this->zone_post_type,
			'posts_per_page'  => '-1',
			'fields'          => 'ids',
			'connected_type'  => 'page_to_zone',
			'connected_items' => $page,
			'nopaging'        => true,
		);

		$zones = get_posts( $args );

		if ( ! $zones ) {
			return $page_data;
		}

		foreach ( $zones as $zone ) {
			$zone_data = get_ad_server_zone_data( $zone );

			$page_data[ $zone ] = $zone_data;
		}

		return $page_data;
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
		$keys      = array();
		$zone_data = array();
		$zone      = absint( $zone );

		$args = array (
			'post_type'       => $this->ad_post_type,
			'posts_per_page'  => '-1',
			'fields'          => 'ids',
			'connected_type'  => 'ad_to_zone',
			'connected_items' => $zone,
			'nopaging'        => true,
			'meta_query'      => array(
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

		if ( ! $ads ) {
			return $zone_data;
		}

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

		// Get ad's data
		$zone_data = $this->get_ad_data( $ad_id );

		return $zone_data;
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

	/**
	 * Get an array of elements of ad.
	 *
	 * @access public
	 *
	 * @param int $ad_id ID of the ad.
	 * @return array $ad_data An array of elements of the ad.
	 */
	public function get_ad_data( $ad_id ) {
		$ad_data = array();
		$ad_id   = absint( $ad_id );

		// Get ad's image
		if ( $ad_image = get_the_post_thumbnail( $ad_id, 'full' ) )  {
			$ad_data['image_html'] = $ad_image;
		}

		// Get ad's URL
		if ( $ad_url = get_post_meta( $ad_id, '_ad_server_url', true ) ) {
			$ad_data['url'] = $ad_url;
		}

		// Get ad's tracking URL
		if ( isset( $ad_data['url'] ) ) {
			$ad_data['tracking_url'] = $this->get_ad_tracking_url( $ad_data['url'], $ad_id );
		}

		/**
		 * Filter elements of ad.
		 *
		 * @param array $ad_data An array of elements of the ad.
		 * @param int   $ad_id   ID of the ad.
		 */
		$ad_data = (array) apply_filters( 'ad_server_ad_elements', $ad_data, $ad_id );

		return $ad_data;
	}

	/**
	 * Get tracking URL of ad.
	 *
	 * @access public
	 *
	 * @param string $ad_url URL of ad.
	 * @param int    $ad_id ID of the ad.
	 * @return string $ad_url URL of ad with tracking information.
	 */
	public function get_ad_tracking_url( $ad_url, $ad_id ) {
		// Only change it if AJAX hooks exist
		if ( has_filter( 'wp_ajax_ad_server_redirect_ad', array( $this, 'redirect_ad' ) ) && has_filter( 'wp_ajax_nopriv_ad_server_redirect_ad', array( $this, 'redirect_ad' ) ) ) {
			$ad_url = add_query_arg( array( 'action' => 'ad_server_redirect_ad', 'ad_id' => $ad_id ), admin_url( 'admin-ajax.php' ) );
		}

		/**
		 * Filter tracking URL of ad.
		 *
		 * @param string $ad_url URL of ad.
		 * @param int    $ad_id  ID of the ad.
		 */
		$ad_url = (string) apply_filters( 'ad_server_ad_tracking_url', $ad_url, $ad_id );

		return $ad_url;
	}

	/**
	 * Get page data in JSONP from AJAX request.
	 */
	public function jsonp_page() {
		// Get page ID
		$page_id = ( isset( $_GET['page_id'] ) && $_GET['page_id'] ) ? $_GET['page_id'] : '';
		$page_id = absint( $page_id );

		// Get callback parameter
		$callback = ( isset( $_GET['callback'] ) && $_GET['callback'] ) ? $_GET['callback'] : '';

		// Create default empty parameters
		$ad_jsonp = $ad_html = '';

		// Get page data
		$page_data = $this->get_page_data( $page_id );

		// If there is no page send that there is no content
		if ( ! $page_data ) {
			$status = 204;
		} else {
			$status = 200;
		}

		// Encode everything in JSON
		$ad_json = wp_json_encode( array( 'status' => $status, 'page_data' => $page_data ) );

		// If there is a callback, use it, othewise simple JSON output
		$ad_jsonp = $callback ? $callback . '(' . $ad_json . ');' : $ad_json;

		die( $ad_jsonp );
	}

	/**
	 * Get zone data in JSONP from AJAX request.
	 */
	public function jsonp_zone() {
		// Get zone ID
		$zone_id = ( isset( $_GET['zone_id'] ) && $_GET['zone_id'] ) ? $_GET['zone_id'] : '';
		$zone_id = absint( $zone_id );

		// Get callback parameter
		$callback = ( isset( $_GET['callback'] ) && $_GET['callback'] ) ? $_GET['callback'] : '';

		// Create default empty parameters
		$ad_jsonp = $ad_html = '';

		// Get zone data
		$zone_data = $this->get_ad_server_zone_data( $zone_id );

		// If there is no zone send that there is no content
		if ( ! $zone_data ) {
			$status = 204;
		} else {
			$status = 200;
		}

		// Encode everything in JSON
		$ad_json = wp_json_encode( array( 'status' => $status, 'zone_data' => $zone_data ) );

		// If there is a callback, use it, othewise simple JSON output
		$ad_jsonp = $callback ? $callback . '(' . $ad_json . ');' : $ad_json;

		die( $ad_jsonp );
	}

	/**
	 * Redirect to ad's URL from AJAX request.
	 */
	public function redirect_ad() {
		// Get ad ID
		$ad_id = ( isset( $_GET['ad_id'] ) && $_GET['ad_id'] ) ? $_GET['ad_id'] : '';
		$ad_id = absint( $ad_id );

		// Get ad URL
		$ad_url = $ad_id ? get_post_meta( $ad_id, '_ad_server_url', true ) : '';

		/**
		 * Filter URL of ad.
		 *
		 * @param string $ad_url URL of ad.
		 * @param int    $ad_id  ID of the ad.
		 */
		$ad_url = (string) apply_filters( 'ad_server_ad_redirect_url', $ad_url, $ad_id );

		// If ad's URL is empty, redirect to site's homepage
		if ( ! $ad_url ) {
			$ad_url = site_url( '/' );
		}

		/**
		 * Fires before redirecting.
		 *
		 * @param string $ad_url URL of ad.
		 * @param int    $ad_id  ID of the ad.
		 */
		do_action( 'ad_server_before_ad_redirect', $ad_url, $ad_id );

		wp_redirect( $ad_url );
		exit;
	}
}
endif;
