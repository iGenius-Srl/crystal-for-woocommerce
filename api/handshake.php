<?php

/**
 * Api realted to handshake
 */
class WC_Handshake_Api {

    public function __construct($key, $secret) {

        $this->post($key, $secret);
    }

    public function post($consumerKey, $consumerSecret) {
        $response = wp_remote_post( WEBHOOK_URL.'/handshake', [
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => ['Content-Type' => 'application/json'],
            'cookies' => $_COOKIE,
            'body' => json_encode([
                'domain' => get_site_url(),
                'consumer_key'  =>  $consumerKey,
                'consumer_secret'    =>  $consumerSecret,
                'name'    =>  get_bloginfo('name'),
            ]),
        ]);
        $code = $response['response']['code'];
        if ( $code === 404 ) {
            $error_message = $code = $response['response']['message'];
            add_action('admin_notices', function() {?>
                <div class="error below-h3">
                    <p>Service temporary unavailable. Come back soon.</p>
                </div>
            <?});
        } else {
            $response = json_decode($response['body']);
            $responseData = $response->body;
            //save user access token
            update_option( 'cfw_at', $responseData->cfw_at, '', 'yes' );
            update_option( 'cfw_plan', $responseData->cfw_plan, '', 'yes' );
            if($responseData->cfw_plan > 1) {
                $WC_Pixels_Api = new WC_Pixels_Api();
                $WC_Pixels_Api->list();
            }else {
                update_option( 'cfw_pixels', []);
                update_option( 'cfw_active_pixel', [], '', 'yes' );
                update_option( 'facebook_config', [
                    'pixel_id' => 0,
                    'use_pii' => 0
                ], '', 'yes' );
            }
        }
    }
}
?>
