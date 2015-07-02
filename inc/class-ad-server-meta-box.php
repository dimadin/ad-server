<?php
/**
 * Ad Server Post Meta Functions.
 *
 * @package Ad_Server
 * @subpackage Ad_Server_Meta_Box
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Ad_Server_Meta_Box' ) ) :
/**
 * Load Ad Server meta box component.
 */
class Ad_Server_Meta_Box {
	/**
	 * Initialize Ad_Server_Meta_Box object.
	 *
	 * Set class properties and add methods to appropriate hooks.
	 *
	 * @access public
	 *
	 * @param Ad_Server $ad_server Object of Ad_Server class.
	 */
	public function __construct( Ad_Server $ad_server ) {
		// Add Ad_Server class
		$this->ad_server = $ad_server;

		// Register meta boxes using Meta Box
		add_filter( 'rwmb_meta_boxes', array( $this, 'register_meta_boxes' )  );

		// Register meta boxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' )        );

		// Register meta boxes
		add_action( 'save_post',      array( $this, 'save_post'      ), 10, 2 );
	}

	/**
	 * Register meta boxes using Meta Box.
	 *
	 * @access public
	 *
	 * @param array $meta_boxes List of meta boxes.
	 * @return array $meta_boxes List of new meta boxes.
	 */
	public function register_meta_boxes( $meta_boxes ) {
		// 1st meta box
		$meta_boxes[] = array(
			'title'      => _x( 'Default', 'page status', 'ad-server' ),
			'post_types' => array( $this->ad_server->page_post_type ),
			'context'    => 'normal',
			'priority'   => 'high',
			'autosave'   => false,
			'fields'     => array(
				array(
					'name' => _x( 'Default', 'page status', 'ad-server' ),
					'id'   => '_ad_server_page_default',
					'type' => 'checkbox',
					'std'  => 0,
				),
			),
		);

		return $meta_boxes;
	}

	/**
	 * Register post meta boxes.
	 *
	 * @access public
	 */
	public function add_meta_boxes() {
		add_meta_box( 'ad-server-url',        __( 'URL',     'ad-server' ), array( $this, 'display_url'        ), $this->ad_server->ad_post_type, 'normal', 'low' );
		add_meta_box( 'ad-server-ad-country', __( 'Country', 'ad-server' ), array( $this, 'display_ad_country' ), $this->ad_server->ad_post_type, 'normal', 'low' );
	}

	/**
	 * Display a meta box that allows users to set URL of the ad.
	 *
	 * @access public
	 *
	 * @param WP_Post $object Post object of current post.
	 * @param array   $box    Data of current meta box.
	 */
	public function display_url( $object, $box ) {
		// Get current value
		$current = get_post_meta( $object->ID, '_ad_server_url', true );
		?>
		<input type="hidden" name="ad-server-url-nonce" value="<?php echo wp_create_nonce( 'ad-server-url-nonce' ); ?>" />

		<div class="form-table">
			<p>
				<label for="_ad_server_url"><input type="text" name="_ad_server_url" id="_ad_server_url" value="<?php echo esc_attr( $current ); ?>" /><?php _e( 'URL', 'ad-server' ); ?></label>
				<br />
			</p>
		</div><!-- .form-table -->
		<?php
	}

	/**
	 * Display a meta box that allows users to set country of the ad.
	 *
	 * @access public
	 *
	 * @param WP_Post $object Post object of current post.
	 * @param array   $box    Data of current meta box.
	 */
	public function display_ad_country( $object, $box ) {
		// Get current value
		$current = get_post_meta( $object->ID, '_ad_server_ad_country', true );
		?>
		<input type="hidden" name="ad-server-ad-country-nonce" value="<?php echo wp_create_nonce( 'ad-server-ad-country-nonce' ); ?>" />

		<div class="form-table">
			<p>
				<select name="_ad_server_ad_country" id="_ad_server_ad_country">
					<option value="ALL"><?php _ex( 'All', 'ad countries', 'ad-server'); ?></option> 
					<?php
						$iso3166 = new Alcohol\ISO3166;
						foreach ( $iso3166->getAll() as $country ) {
							echo '<option value="' . esc_attr( $country['alpha2'] ) . '"' . selected( $country['alpha2'], $current, false ) . '>' . $country['name'] . '</option>';
						}
					?>
				</select>
			</p>
		</div><!-- .form-table -->
		<?php
	}

	/**
	 * Saves the post meta box settings as post metadata.
	 *
	 * @access public
	 *
	 * @param int $post_id The ID of the current post being saved.
	 * @param int $post    The post object currently being saved.
	 */
	public function save_post( $post_id, $post ) {
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Get the post type object.
		$post_type = get_post_type_object( $post->post_type );

		// Check if the current user has permission to edit the post.
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		}

		// Get keys by post type
		switch ( $post->post_type ) {
			case $this->ad_server->ad_post_type:
				$keys = array( 'ad-server-url', 'ad-server-ad-country' );
				break;
			default:
				$keys = array();
				break;
		}

		// Get keys for each post type
		$default_keys = array(  );

		// Get all keys
		$keys = array_merge( $keys, $default_keys );

		// Verify the nonce before proceeding
		foreach ( $keys as $key ) {
			$nonce = $key . '-nonce';
			if ( ! isset( $_POST[ $nonce ] ) || ! wp_verify_nonce( $_POST[ $nonce ], $nonce ) ) {
				return $post_id;
			}
		}

		// Convert keys to meta keys
		foreach ( $keys as &$key ) {
			$key = '_' . str_replace( '-', '_', $key );
		}

		foreach ( $keys as $meta_key ) {

			/* Get the new meta value. */
			$new_meta_value = wp_kses_post( $_POST[$meta_key] );

			/* Get the meta value of the custom field key. */
			$meta_value = get_post_meta( $post_id, $meta_key, true );

			/* If a new meta value was added and there was no previous value, add it. */
			if ( $new_meta_value && '' == $meta_value ) {
				add_post_meta( $post_id, $meta_key, $new_meta_value, true );
			}

			/* If the new meta value does not match the old value, update it. */
			elseif ( $new_meta_value && $new_meta_value != $meta_value ) {
				update_post_meta( $post_id, $meta_key, $new_meta_value );
			}

			/* If there is no new meta value but an old value exists, delete it. */
			elseif ( '' == $new_meta_value && $meta_value ) {
				delete_post_meta( $post_id, $meta_key, $meta_value );
			}
		}
	}
}
endif;
