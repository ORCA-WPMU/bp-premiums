<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * @todo Description
 * @since 1.0.0
 */
if(!class_exists('SP_BuddyDrive_BPP_Addon')):
/**
 * @todo Description
 * @since 1.0.0
 */
class SP_BuddyDrive_BPP_Addon{
	/**
	 * @var string field_name_browse
	 * @since 1.0.0
	 */
	 public $field_name_browse = '_spbpp_buddydrive_browse';
	 /**
	 * @var string field_name_browse
	 * @since 1.0.0
	 */
	 public $field_name_uploads = '_spbpp_buddydrive_uploads';
	 /**
	 * @var string field_name_space
	 * @since 1.0.0
	 */
	 public $field_name_space = '_spbpp_buddydrive_space';
	 /**
	 * @var string user_meta
	 * @since 1.0.0
	 */
	 public $user_meta_upload = '_spbpp_buddydrive_upload_access';
	 /**
	 * @var string user_meta
	 * @since 1.0.0
	 */
	 public $user_meta_quota = '_spbpp_buddydrive_upload_quota';
	  /**
	 * @var string user_meta_browse
	 * @since 1.0.0
	 */
	 public $user_meta_browse = '_spbpp_buddydrive_browse_access';
	/**
	 * SP_BuddyDrive_BPP_Addon initiate
	 * @since 1.0.0
	 */
	public function __construct(){
		add_action( 'plugins_loaded', array($this, 'plugin_load_textdomain'));
		add_action('spbpp_options_list', array($this, 'spbpp_add_option'), 12, 2);
		add_action('spbpp_save_product', array($this, 'save_buddydrive_access'), 12, 2);
		add_action('spbpp_order_complete', array($this, 'add_buddydrive_access'), 10, 4);
		//BuddyPress and RTMedia Hooks
		add_action('bp_init', array($this, 'set_user_access'), 1000);
	}
	/**
	 * @todo Load language file
	 * @since 1.0.0
	 */
	public function plugin_load_textdomain(){
		load_plugin_textdomain( 'spbpp-buddydrive', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	/**
	 * Add option too WooCommerce BuddyPress Product Type
	 * @param array $woocommerce
	 * @param array $post
	 * @since 1.0.0
	 */
	public function spbpp_add_option($woocommerce, $post){
		//add title
		$title = __('BuddyDrive Options', 'spbpp-buddydrive');
		sbbp_section_title($title);
		//
		woocommerce_wp_checkbox(
		array(
			'id'            => $this->field_name_uploads,
			'label'         => __('Allow uploads', 'spbpp-buddydrive' ),
			'description'   => __( 'After purchase, users will be granted access to upload media with BuddyDrive', 'spbpp-buddydrive' ),
			)
		);
		//
		woocommerce_wp_checkbox(
		array(
			'id'            => $this->field_name_browse,
			'label'         => __('Allow browsing', 'spbpp-buddydrive' ),
			'description'   => __( 'After purchase, users will be granted access to view other user\s uploads with BuddyDrive', 'spbpp-buddydrive' ),
			)
		);

		woocommerce_wp_text_input(
		 array(
		   'name'			   => $this->field_name_space,
		   'id'                => $this->field_name_space,
		   'label'             => __( 'BuddyDrive Space Quota', 'sp-buddypress-premiums' ),
		   'type'			   => 'number',
		   'placeholder'       => __( '100', 'sp-buddypress-premiums' ),
		   'desc_tip'    => 'true',
		   'description'       => __( 'Enter the number of space a user (in bytes) can purchase to allocate to their account after purchase.', 'sp-buddypress-premiums' ),
		   ));
	}
	/**
	 * Saving product and groups users will be assigned to after purchase
	 * @param	int		$id
	 * @param	array	$post_id
	 * @since	1.0.0
	 */
	public function save_buddydrive_access($id, $post){
		if(!empty($_POST[$this->field_name_uploads])){
			update_post_meta($id, $this->field_name_uploads, $_POST[$this->field_name_uploads]);
			//set a global option for this
			update_option($this->field_name_uploads, 1);
		}else{
			//if no value was sent, let's delete any data if user wants to
			delete_post_meta($id, $this->field_name_uploads);
			delete_option($this->field_name_uploads);
		}

		if(!empty($_POST[$this->field_name_browse])){
			update_post_meta($id, $this->field_name_browse, $_POST[$this->field_name_browse]);
			//set a global option for this
			update_option($this->field_name_browse, 1);
		}else{
			//if no value was sent, let's delete any data if user wants to
			delete_post_meta($id, $this->field_name_browse);
			delete_option($this->field_name_browse);
		}

		if(!empty($_POST[$this->field_name_space])){
			update_post_meta($id, $this->field_name_space, (int)$_POST[$this->field_name_space]);
		}else{
			//if no value was sent, let's delete any data if user wants to
			delete_post_meta($id, $this->field_name_space);
		}
	}
	/**
	 * Give user group quota
	 * @param	array	$products
	 * @param	int		$user_id
	 * @param	int		$order_id
	 * @param	array	$order
	 *
	 * @since	1.0.0
	 */
	public function add_buddydrive_access($products=array(), $user_id=0, $order_id=0, $order=array()){
		if(!empty($products)):
			foreach($products as $product){
				$field_name_browse = get_post_meta($product['product_id'], $this->field_name_browse, true);
				$field_name_uploads = get_post_meta($product['product_id'], $this->field_name_uploads, true);
				$purchased_space = get_post_meta($product['product_id'], $this->field_name_space, true);
				//echo $product['item_meta']['_qty'][0];exit;
				$purchased_space = (int)$purchased_space * (int)$product['item_meta']['_qty'][0];
				//if product has media browsing enabled, let's give user access
				if(!empty($field_name_browse) && $field_name_browse == 'yes'){
					if(update_user_meta($user_id, $this->user_meta_browse, 1)){
						/*
						 *	Hook can be used for email, stats, notices etc
						 */
						do_action('spbpp_buddydrive_browse_access', $user_id);
					}
				}
				//if product has media browsing enabled, let's give user access
				if(!empty($field_name_uploads) && $field_name_uploads == 'yes'){
					if(update_user_meta($user_id, $this->user_meta_upload, 1)){
						/*
						 *	Hook can be used for email, stats, notices etc
						 */
						do_action('spbpp_buddydrive_upload_access', $user_id);
					}
				}
				if(!empty($purchased_space)){
					if(buddydrive_update_user_space( $user_id, $purchased_space)){
					/*
					 *	Hook can be used for email, stats, notices etc
					 */
					do_action('spbpp_buddydrive_increased_quota', $user_id, $purchased_space);
					}
				}
			}
		endif;
	}

	public function set_user_access(){
		global $bp;
		//admins can access everything
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			return;
		}
		//if buddydrive is not active, skip it all
		if(!function_exists('buddydrive')){
			return;
		}
		//are we on the profile area?
		if(!isset($bp->displayed_user->id)){
			return;
		}
		$browse_access_enabled = get_option($this->field_name_browse);
		$upload_access_enabled = get_option($this->field_name_uploads);
		//if no option set, then no product exists and we can skip everything
		if(!$browse_access_enabled || !$upload_access_enabled){
			return;
		}
		//set up loggedin id
		$loggedin_id = '';
		if(isset($bp->loggedin_user->id)){
			$loggedin_id = $bp->loggedin_user->id;
		}
		//let's restrict access to upload
		if($upload_access_enabled){
			//has user purchased this access
			$has_upload_access = get_user_meta($loggedin_id, $this->user_meta_upload, true);
			if(!$has_upload_access){
				//if user doesn't have access and looking at their profile then remove upload
				if($loggedin_id == $bp->displayed_user->id){
					unset($bp->bp_nav[buddydrive_get_slug()]);
				}
			}
		}
		//let's restrict access to browse another user media
		if($browse_access_enabled){
			//has user purchased this access
			$has_browse_access = get_user_meta($loggedin_id, $this->user_meta_browse, true);
			if(!$has_browse_access){
				//if user doesn't have access and looking at their profile then remove upload
				if($loggedin_id != $bp->displayed_user->id){
					unset($bp->bp_nav[buddydrive_get_slug()]);
				}
			}
		}

	}

}
endif;

function load_sp_buddydrive_addon(){
	if ( function_exists( 'buddydrive' ) ) {
		return new SP_BuddyDrive_BPP_Addon();
	}
}
add_action( 'bp_premiums_loaded', 'load_sp_buddydrive_addon');
