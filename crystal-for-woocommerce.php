<?php /**
* Plugin Name: crystal for WooCommerce
* Plugin URI: https://github.com/iGenius-Srl/crystal-for-woocommerce
* Description: crystal for WooCommerce is the smart plug-in to monitor your e-shop, maximize results and boost your business.
* Author: crystalfordata
* Author URI: https://crystal.io/
* Version: 1.0.0
* Text Domain: crystal-for-woocommerce
*/

/**
* @package crystalCommerce
*/

// const CRYSTAL_URL = 'https://crystal.io';
const CRYSTAL_URL = 'https://crystaldata.io';
const CRYSTAL_API_URL = CRYSTAL_URL.'/api';
const WEBHOOK_URL = CRYSTAL_API_URL.'/webhook/woocommerce';
const REST_API_URL = 'cfw-api/v1';
const DEFAULT_POST_TYPES = ['product', 'shop_coupon', 'shop_order'];
require_once('api/wc_api.php');

if (!class_exists('WC_Crystalcommerce') ) :

    /**
     * Instantiate new endpoints cycilng each files in api folder(not: index.php)
     */
    $path = dirname(__FILE__);
    $files = array_diff(scandir($path.'/api'), array('..', '.'));

    foreach ($files as $file) {
        require_once($path . '/api/' .$file);
    }
    require_once('webhooks/class-webhooks.php');

    class WC_Crystalcommerce {

        public function __construct() {
            add_action('admin_init', array( $this, 'detect_woocommerce_exist'));
            add_action('plugins_loaded', array( $this, 'cfw_init'));
        }

        public function cfw_init() {
            $access_token = get_option('cfw_at');
            if ($access_token != false) {
                // refresh user infos
                $wc_api = new CFW_Api($access_token);
                $wc_api->getUserData();
            }
            add_action( 'admin_menu', array($this, 'cfw_menu_page') );
            add_action( 'transition_post_status', array( $this, 'cfw_set_hooks'), 10, 3);
            add_action( 'updated_post_meta',  array( $this, 'cfw_post_updated'), 10, 4);
            add_action( 'profile_update',  array( $this, 'cfw_update_customer'), 10, 2);
            add_action( 'woocommerce_new_customer',  array( $this, 'cfw_new_customer'), 10, 2);
            add_action( 'woocommerce_delete_customer',  array( $this, 'cfw_delete_customer'), 10, 2);
        }

        public function detect_woocommerce_exist() {
            if(!is_plugin_active( 'woocommerce/woocommerce.php' ) ):
                deactivate_plugins( plugin_basename( __FILE__ ) );
                delete_option('cfw_redirect');
                add_action('admin_notices', array($this,'woocommerce_missing_error'));
            else:
                if(get_option('cfw_redirect')) {
                    if(wp_redirect( admin_url( 'options-general.php?page=cfw_settings' ) )) {
                        delete_option('cfw_redirect');
                        wp_redirect( admin_url('options-general.php?page=cfw_settings') );
                        exit;
                    }
                }
            endif;
        }

        public function woocommerce_missing_error() {
            ?>
            <div class="error below-h3">
                <p>To install crystal for WooCommerce you need to activate the <a href="https://it.wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce plugin</a>.</p>
            </div>
            <?php
        }

        public function cfw_menu_page() {
          $page = add_options_page(
            'crystal Settings',
            'crystal',
            'manage_options',
            'cfw_settings',
            array($this, 'create_menu_page'));
            add_action('admin_print_scripts-' . $page, array($this,'cfw_admin_scripts'));
        }

        /**
        * It will be called only on your plugin admin page, enqueue our script here
        */
        public function cfw_admin_scripts() {
            wp_enqueue_style( 'cfw_admin_css', plugin_dir_url(__FILE__).'/assets/css/cfw.css' );
            wp_enqueue_script( 'cfw_admin_js', plugin_dir_url(__FILE__).'/assets/js/cfw.js' , array('jquery'));
        }


        public function create_menu_page() {
          if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page'));
          }
          include_once 'includes/cfw-settings.php';
        }

        /**
         * [calls the correct webhook based on entity type]
         * @param [string] $new_status [new entity status]
         * @param [string] $old_status [old entity status]
         * @param [object] $post       [entity { product | coupon | order }]
         */
        public static function cfw_set_hooks($new_status, $old_status, $post) {

            if ( array_search($post->post_type, DEFAULT_POST_TYPES) >= 0 ) {

                $WC_Webhooks = new WC_Webhooks($post);
                if ( 'publish' === $new_status && 'publish' !== $old_status ) {

                    $WC_Webhooks->create();

                } elseif ( 'trash' === $new_status ) {

                    $WC_Webhooks->delete();

                }
            }
        }

        /**
        * On post update hook
        * @param  [int] $meta_id    [the meta id]
        * @param  [int] $postID     [the post id]
        * @param  [string] $meta_key   [meta field name]
        * @param  [string] $meta_value [meta field new value]
        */
        public static function cfw_post_updated($meta_id, $postID, $meta_key, $meta_value){
            $post = get_post($postID);

            if ($post->post_status === 'publish' && array_search($post->post_type, DEFAULT_POST_TYPES) >= 0 ) {
                $WC_Webhooks = new WC_Webhooks($post);
                $args = [
                    'id' => $postID,
                    'field' => $meta_key,
                    'value' => $meta_value
                ];
                $WC_Webhooks->update($args);
            }
        }

        /**
         * Start webhook  on new customer registration
         * @param  int $id the customer id
         */
        public static function cfw_new_customer($id) {
            $WC_Webhooks = new WC_Webhooks(['post_type' => 'customer']);
            $WC_Webhooks->create();
        }

        /**
         * Start webhook  on customer delete
         * @param  int $id the customer id
         */
        public static function cfw_delete_customer($id) {
            $WC_Webhooks = new WC_Webhooks(['post_type' => 'customer']);
            $WC_Webhooks->delete();
        }

        /**
        * On customer update email webhook to crystal
        * @param  [int] $meta_id    [the meta id]
        * @param  [int] $postID     [the post id]
        * @param  [string] $meta_key   [meta field name]
        * @param  [string] $meta_value [meta field new value]
        */
        public static function cfw_update_customer($customerID, $oldData){
            $oldEmail = $oldData->user_email;
            $newEmail = get_userdata($customerID)->user_email;
            if($oldEmail !== $newEmail) {
                $WC_Webhooks = new WC_Webhooks(['post_type' => 'customer']);
                $args = [
                        'id' => $customerID,
                        'field' => 'email',
                        'value' => $newEmail
                    ];
                    $WC_Webhooks->update($args);
            }
        }

        /**
        * Enable api, activate fb pixel and generate wc key
        */
    	public static function cfw_activation_success() {
            update_option( 'woocommerce_api_enabled', 'yes', '', 'yes' );
            self::cfw_activate_pixel();
            update_option( 'cfw_redirect', 'yes', '', 'yes' );

      	}

        public static function cfw_activate_pixel() {
            if(!is_plugin_active('crystal-for-woocommerce/facebook-for-wordpress.php')) {
                activate_plugin('crystal-for-woocommerce/facebook-for-wordpress.php');
            }
        }

        public static function cfw_unistall() {
            $access_token = get_option('cfw_at');
            if ($access_token != false) {
                // plugin deactivation call
                $wc_api = new CFW_Api($access_token);
                $wc_api->sendPluginDeactivation();
            }
            delete_option('cfw_at');
            delete_option('cfw_plan');
            delete_option('cfw_pixels');
            delete_option('cfw_active_pixel');
        }

    }

    $WC_Crystalcommerce = new WC_Crystalcommerce();
    register_activation_hook( __FILE__, array( $WC_Crystalcommerce, 'cfw_activation_success' ) );
    register_deactivation_hook( __FILE__, array( $WC_Crystalcommerce, 'cfw_unistall' ) );



endif;
?>
