<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * @todo Description
 * @since 1.0.0
 */
class BP_Premiums_Group_Create_Module{
	/**
	 * @var string field_name
	 * @since 1.0.0
	 */
	 public $field_name = '_spbpp_premium_groups_create';
	 /**
	 * @var string user_meta
	 * @since 1.0.0
	 */
	 public $user_meta = '_spbpp_groups_create_quota';
	/**
	 * BP_Premiums_Group_Create_Module initiate
	 * @since 1.0.0
	 */
	public function __construct(){
		add_action( 'plugins_loaded', array($this, 'plugin_load_textdomain' ) );
		add_action( 'spbpp_options_list', array($this, 'spbpp_add_group_acess_option' ), 12, 2 );
		add_action( 'spbpp_save_product', array($this, 'save_group_quota' ), 12, 2 );
		add_action( 'spbpp_order_complete', array($this, 'add_quota' ), 10, 4 );
		//BuddyPress filters and actions
		add_filter( 'bp_user_can_create_groups', array($this, 'group_create_access' ), 12, 2 );
		add_action( 'groups_group_create_complete', array($this, 'update_user_quota' ), 12, 1 );
	}
	/**
	 * @todo Load language file
	 * @since 1.0.0
	 */
	public function plugin_load_textdomain(){
		load_plugin_textdomain( 'spbpp-group-create', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	/**
	 * Add option too WooCommerce BuddyPress Product Type
	 * @param array $woocommerce
	 * @param array $post
	 * @since 1.0.0
	 */
	public function spbpp_add_group_acess_option($woocommerce, $post){
		//add title
		$title = __('Group Creation Access Options', 'spbpp-group-create');
		sbbp_section_title($title);
		//
		woocommerce_wp_text_input(
		 array(
		   'name'			   => $this->field_name,
		   'id'                => $this->field_name,
		   'label'             => __( 'Groups Creation Quota', 'spbpp-group-create' ),
		   'type'			   => 'number',
		   'placeholder'       => __( 'Enter # of groups allowed', 'spbpp-group-create' ),
		   'desc_tip'    => 'true',
		   'description'       => __( 'The amount of groups that a user would be allowed to created. Each purchase multiplies number of quota.', 'spbpp-group-create' ),
		   ));
	}
	/**
	 * Saving product and groups users will be assigned to after purchase
	 * @param	int		$id
	 * @param	array	$post_id
	 * @since	1.0.0
	 */
	public function save_group_quota($id, $post){
		if(!empty($_POST[$this->field_name])){
			update_post_meta($id, $this->field_name, $_POST[$this->field_name]);
		}else{
			//if no value was sent, let's delete any data if user wants to
			delete_post_meta($id, $this->field_name);
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
	public function add_quota($products=array(), $user_id=0, $order_id=0, $order=array()){
		if(!empty($products)):
			foreach($products as $product){
				$allowed_quota = get_post_meta($product['product_id'], $this->field_name, true);
				$user_quota = get_user_meta($user_id, $this->user_meta, true);
				//if user has no quota, let's add
				if(empty($user_quota)){
					$new_quota = $allowed_quota;
				}else{
					$new_quota = (int)$user_quota + (int)$allowed_quota;
				}
				if($new_quota){
					update_user_meta($user_id, $this->user_meta, $new_quota);
					/*
					 *	Hook can be used for email, stats, notices etc
					 */
					do_action('spbpp_user_quota_increased', $new_quota, $user_id);
				}
			}
		endif;
	}

	public function group_create_access($can_create, $restricted ){
		global $bp;
		// Super admin can always create groups
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			return true;
		}
		//logged in user id
		$user_id = $bp->loggedin_user->id;
		if($user_id){
			$user_quota = get_user_meta($user_id, $this->user_meta, true);
			//if user has a group creation quota
			if(empty($user_quota)){
				$can_create = false;
			}
		}
		return $can_create;
	}

	public function update_user_quota($group_id=0){
		global $bp;
		$user_id = $bp->loggedin_user->id;
		// Super admin has unlimited quota
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			return;
		}
		if($group_id){
			$user_quota = get_user_meta($user_id, $this->user_meta, true);
			if(!empty($user_quota)){
				$new_quota = (int)$user_quota - 1;
				update_user_meta($user_id, $this->user_meta, $new_quota);
				/*
				 *	Hook can be used for email, stats, notices etc
				 */
				do_action('spbpp_user_quota_decreased', $group_id, $user_id);
			}
		}
	}
}

/**
 * Load class
 * @since 1.0.0
 */
function spbpp_run_group_create(){
	$group_access = new BP_Premiums_Group_Create_Module();
}
add_action( 'bp_premiums_loaded', 'spbpp_run_group_create' );
