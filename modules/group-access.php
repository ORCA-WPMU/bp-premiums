<?php
/**
 * BuddyPress Premium Addon for granting user access to groups especially Private Groups
 *
 * @since 1.0.0
 */
class BP_Premiums_Group_Access_Module{
	/**
	 * @var string field_name
	 * @since 1.0.0
	 */
	 public $field_name = '_spbpp_premium_groups_field';
	/**
	 * BP_Premiums_Group_Access_Module initiate
	 * @since 1.0.0
	 */
	public function __construct(){
		add_action('spbpp_options_list', array($this, 'spbpp_add_group_acess_option'), 12, 2);
		add_action('spbpp_save_product', array($this, 'save_premium_groups'), 12, 2);
		add_action('spbpp_order_complete', array($this, 'grant_access'), 10, 4);
	}
	/**
	 * Add option too WooCommerce BuddyPress Product Type
	 * @param array $woocommerce
	 * @param array $post
	 * @since 1.0.0
	 */
	public function spbpp_add_group_acess_option($woocommerce, $post){
		//add title
		$title = __('Group Access Options', 'sp-groupacess-for-buddypress-premium');
		sbbp_section_title($title);
		// Create a dropdown of groups to select from
		spbp_groups_dropdown(
		  array(
		   'name'			   => $this->field_name.'[]',
		   'id'                => $this->field_name,
		   'label'             => __( 'Premium Groups Access', 'sp-bp-premiums' ),
		   'placeholder'       => __( 'Enter # of groups', 'sp-bp-premiums' ),
		   'desc_tip'    => 'true',
		   'description'       => __( 'Grant users access to a number of premium groups.', 'sp-bp-premiums' ),
		   'custom_attributes'  => array('multiple'=>'multiple')
		   ));
	}
	/**
	 * Saving product and groups users will be assigned to after purchase
	 * @param	int		$id
	 * @param	array	$post_id
	 * @since	1.0.0
	 */
	public function save_premium_groups( $id, $post ) {
		//check if any value was sent
		if( ! empty( $_POST[ $this->field_name ] ) ) {
			update_post_meta( $id, $this->field_name, $_POST[ $this->field_name ] );
		}else{
			//if no value was sent, let's delete any data if user wants to
			delete_post_meta( $id, $this->field_name );
		}
	}
	/**
	 * Add user to groups based on user purchase
	 *
	 * @param	array	$products
	 * @param	int		$user_id
	 * @param	int		$order_id
	 * @param	array	$order
	 *
	 * @since	1.0.0
	 */
	public function grant_access( $products = array(), $user_id = 0, $order_id = 0, $order=array() ) {
		if ( ! empty( $products ) ) :
			foreach ( $products as $product ) {
				$groups = get_post_meta( $product['product_id'], $this->field_name, true );
				//
				if ( ! empty( $groups ) && is_array( $groups ) ) {
					foreach ( $groups as $key=>$group_id ) {
						groups_join_group( $group_id, $user_id );
					}
				}
			}
		endif;
	}
}

/**
 * Load class
 * @since 1.0.0
 */
function bp_premium_module_group_access(){
	$group_access = new BP_Premiums_Group_Access_Module();
}
add_action( 'bp_premiums_loaded', 'bp_premium_module_group_access' );
