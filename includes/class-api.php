<?php
/**
 * BP Premiums Api
 *
 * @since 1.2.0
 * @package BP Premiums
 */

/**
 * BP Premiums Api.
 *
 * @since 1.2.0
 */
class BP_Premiums_Api {
	/**
	 * Parent plugin class
	 *
	 * @var   BP_Premiums
	 * @since 1.2.0
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since  1.2.0
	 * @param  BP_Premiums $plugin Main plugin object.
	 * @return void
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
		$this->include_default_modules();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  1.2.0
	 * @return void
	 */
	public function hooks() {
		add_filter( 'product_type_selector', array($this, 'add_product_type' ), 1, 1);
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_options_selector' ) );
		add_action( 'spbpp_before_options_list', array( $this, 'product_option_wrapper_open' ), 1 );
		add_action( 'spbpp_after_options_list', array( $this, 'product_option_wrapper_close' ), 1 );
		add_action( 'woocommerce_order_status_completed', array($this, 'order_complete' ), 10, 1 );
		add_action( 'save_post', array($this, 'save_product_meta'), 12, 2);
	}

	/**
	 * Add BuddyPress as a WC Product type
	 *
	 * @param array $types
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function add_product_type( $types = array() ) {
		$types['buddypress'] = apply_filters( 'spbpp_product_type_title', __( 'BuddyPress', 'sp-bp-premiums' ) );
		return $types;
	}
	/**
	 * Renders actions for use by modules.
	 *
	 * @since 1.0.0
	 */
	public function render_options_selector(){
		global $woocommerce, $post;
		/*
		*	Used for opening wrapper div
		*/
		do_action( 'spbpp_before_options_list', $woocommerce, $post );
		/*
		*	Display the WooCommerce Product Attributes
		*	hookable by addons
		*/
		do_action( 'spbpp_options_list', $woocommerce, $post );
		/*
		*	Used for closing wrapper div
		*/
		do_action( 'spbpp_after_options_list', $woocommerce, $post );
	}
	/**
	 * Start of Product selector
	 *
	 * @since 1.0.0
	 */
	public function product_option_wrapper_open(){
		echo '<div class="spbpp-wrapper options_group show_if_buddypress">';
	}

	/**
	 * End of Product Selector
	 *
	 * @since 1.0.0
	 */
	public function product_option_wrapper_close(){
		echo '</div>';
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var pricingPane = $('#woocommerce-product-data');
			if ( pricingPane.length ){
				pricingPane.find('.pricing').addClass('show_if_buddypress').end()
					.find('.inventory_tab').addClass('hide_if_buddypress').end()
					.find('.shipping_tab').addClass('hide_if_buddypress').end()
					.find('.linked_product_tab').addClass('hide_if_buddypress').end()
					.find('.attributes_tab').addClass('hide_if_buddypress');
			}
		})
		</script>
		<?php
	}

	/**
	 *	Provides a way for addon/modules to save to product meta
	 *
	 *	Developers can hook into spbpp_save_product to save product meta without validating if post type is
	 *	product and if BuddyPress Premium is selected.
	 *
	 *
	 *	@since 1.0.0
	 */
	public function save_product_meta( $post_id = 0, $post = array() ){

		// Get the post type object.
		$post_type = get_post_type_object( $post->post_type );

		// Check if we are using product post type.
		if( 'product' != $post->post_type ){
			return $post_id;
		}
		// Get the post type object.
		$post_type_obj = get_post_type_object( $post->post_type );

		// Check if the current user has permission to edit the post.
		if ( ! current_user_can( $post_type_obj->cap->edit_post, $post_id ) ){
			return $post_id;
		}

		/* Check if current product type is BuddyPress Premium */
		if ( ! empty( $_POST['product-type'] ) && 'buddypress' != $_POST['product-type'] ) {
			return $post_id;
		}
		/*
		 *	@action spbpp_save_product Hook for modules to save
		 */
		do_action( 'spbpp_save_product', $post_id, $post );
	}
	/**
	 * Update data after WooCommerce Order is updated.
	 *
	 *
	 * @since 1.0.0
	 */
	public function order_complete( $order_id ) {
		$order = new WC_Order( $order_id );
		$user_id = $order->customer_user;
		$products = $order->get_items();
		/*
		 *	@action spbpp_order_complete Hook for modules
		 */
		do_action('spbpp_order_complete', $products, $user_id, $order_id, $order );
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function include_default_modules(){
		$default_modules = apply_filters( 'spbpp_default_modules', array(
			'group-access',
			'group-create',
			'buddydrive',
			'rtmedia',
		) );
		if ( ! empty( $default_modules ) ) {
			foreach ( $default_modules as $module ) {
				include_once( bp_premiums()->dir( 'modules/' . $module . '.php' ) );
			}
		}
	}
}
