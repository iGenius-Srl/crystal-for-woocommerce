<?php

/**
 * Api realted to healthcheck
 */
class WC_Healthcheck_Api {
    public function __construct() {
        add_action('init', array($this, 'set_ajax_hooks'));
    }

    public function set_ajax_hooks() {
        add_action('wp_ajax_cfw_get_healthcheck', array($this, 'cfw_get_healthcheck'));
        add_action('wp_ajax_nopriv_cfw_get_healthcheck', array($this, 'cfw_get_healthcheck'));
    }


    public function cfw_get_healthcheck() {
        wp_send_json(['server_time' => getdate()[0] * 1000]);
    }

}
$WC_Healthcheck_Api = new WC_Healthcheck_Api();
?>
