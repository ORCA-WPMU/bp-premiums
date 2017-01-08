<?php
/**
 * Plugin Name: SuitePlugins - BP Premiums
 * Plugin URI:  http://suiteplugins.com
 * Description: 
 * Author:      SuitePlugins
 * Author URI:  http://suiteplugins.com
 * Version:     1.0.0
 * Text Domain: sp-bp-premiums
 * Domain Path: /languages/
 * License:     GPLv2 or later (license.txt)
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * @todo Description
 * @since 1.0.0
 */
if(!class_exists('SP_BuddyPress_Premium')):
/**
 * @todo Description
 * @since 1.0.0
 */
class SP_BuddyPress_Premium{
	public function __construct(){
		if($this->check_requirements()){
			do_action( 'spbpp_active' );
			add_action( 'plugins_loaded', array($this, 'plugin_load_textdomain'));
			$this->define_constants();
			$this->includes();
			add_filter( 'product_type_selector', array($this, 'add_product_type'), 1, 1);
			add_action( 'woocommerce_product_options_general_product_data', array($this, 'render_options_selector'));
			add_action( 'spbpp_before_options_list', array($this, 'product_option_wrapper_open'), 10);
			add_action( 'spbpp_after_options_list', array($this, 'product_option_wrapper_close'), 10);
			add_action( 'woocommerce_order_status_completed', array($this, 'order_complete'), 10, 1);
			add_action( 'save_post', array($this, 'save_product_meta'), 12, 2);
			
			//include modules
			$this->include_default_modules();
			do_action( 'spbpp_loaded' );
		}else{
			$this->display_requirement_message();
		}
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function define_constants(){
		define('spbpp_dir_path', plugin_dir_path( __FILE__ ));
		define('spbpp_dir_uri', plugins_url( '', __FILE__ ));
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function plugin_load_textdomain(){
		load_plugin_textdomain( 'sp-bp-premiums', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function includes(){
		require_once(spbpp_dir_path . 'includes/sp-bp-functions.php');
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function add_product_type($types) {
		$types['buddypress'] = apply_filters('spbpp_product_type_title', __( 'BuddyPress', 'sp-bp-premiums'));
		return $types;
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function render_options_selector(){
		global $woocommerce, $post;
		/*
		*	Used for opening wrapper div
		*/
		do_action('spbpp_before_options_list', $woocommerce, $post);
		/*
		*	Display the WooCommerce Product Attributes
		*	hookable by addons
		*/
		do_action('spbpp_options_list', $woocommerce, $post);
		/*
		*	Used for closing wrapper div
		*/
		do_action('spbpp_after_options_list', $woocommerce, $post);
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function product_option_wrapper_open(){
		echo '<div class="spbpp-wrapper options_group show_on_buddypress">';
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function product_option_wrapper_close(){
		echo '</div>';
		?>
        <script type="text/javascript">
		jQuery(document).ready(function($) {
			var spbpp_show_panel = function(){
				var wrapper = jQuery('.spbpp-wrapper');
				var product_type = jQuery('#product-type').val();
				if(product_type=='buddypress'){
					jQuery('.pricing').show();
					wrapper.show();
				}else{
					wrapper.hide();
				}
			}
			spbpp_show_panel();
			jQuery('#product-type').change(function(event) {
                spbpp_show_panel();
            });
			
		})
		</script>
		<?php
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function check_requirements(){
		//Check if WooCommerce is activated
		if (!in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return false;
		}
		if (!in_array( 'buddypress/bp-loader.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return false;
		}
		return true;
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function display_requirement_message(){
		add_action('admin_notices', array($this, 'display_admin_notice'));
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function display_admin_notice(){
			if(current_user_can('manage_options')):
			echo '<div class="error"><p>'; 
			echo __('Please install and activate <strong>WooCommerce</strong> and <strong>BuddyPress</strong> to use BuddyPress Premiums', 'sp-bp-premiums');
			echo "</p></div>";
			endif;
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
	public function save_product_meta($post_id, $post){
		/* Get the post type object. */
  		$post_type = get_post_type_object( $post->post_type );
		/* Check if we are using product post type. */
		if($post->post_type != 'product'){
			return $post_id;
		}
		/* Get the post type object. */
  		$post_type_obj = get_post_type_object( $post->post_type );
		/* Check if the current user has permission to edit the post. */
		if ( !current_user_can( $post_type_obj->cap->edit_post, $post_id ) ){
			return $post_id;
		}
		
		/* Check if current product type is BuddyPress Premium */
		if(!isset($_POST['product-type']) && 'buddypress' != $_POST['product-type']){
			return $post_id;
		}
		/*
		 *	@action spbpp_save_product Hook for modules to save
		 */
		do_action('spbpp_save_product', $post_id, $post);
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function order_complete($order_id){
		$order = new WC_Order( $order_id );
		$user_id = $order->customer_user;
		$products = $order->get_items();
		/*
		 *	@action spbpp_order_complete Hook for modules
		 */
		do_action('spbpp_order_complete', $products, $user_id, $order_id, $order);
	}
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
	public function include_default_modules(){
		$default_modules = apply_filters('spbpp_default_modules', array(
			'group_access'
		));
		if(!empty($default_modules)){
			foreach($default_modules as $i=>$module){
				include_once(spbpp_dir_path . 'modules/'.$module.'.php');
			}
		}
	}
}
endif;

add_action('bp_init','sp_premium_bp_initiate');
	/**
	 * @todo Description
	 * @since 1.0.0
	 */
function sp_premium_bp_initiate(){
	$SP_BuddyPress_Premium = new SP_BuddyPress_Premium();
}