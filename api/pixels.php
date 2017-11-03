<?php

/**
 * Api realted to pixels
 */
class WC_Pixels_Api {
    public function __construct() {
    }

    public static function list() {
        $response = wp_remote_get( WEBHOOK_URL.'/pixels', array(
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'),
            'cookies' => $_COOKIE,
            'body' => array(
                'cfw_at' => get_option('cfw_at', ''),
            ),
        ));
        $code = $response['response']['code'];
        if ( $code === 404 ) {
            $error_message = $code = $response['response']['message'];
            add_action('admin_notices', function() {?>
                <div class="error below-h3">
                    <p>Services temporary unavailable. Come back soon.</p>
                </div>
            <?});
        } else {
            $response = json_decode($response['body']);
            $responseData = $response->body;
            $pixels = $responseData->pixels;
            $activePixel = count($pixels) > 0 ? $pixels[0] : null;
            add_option( 'cfw_pixels', $pixels);
            add_option( 'cfw_active_pixel', $activePixel, '', 'yes' );
            if(count($pixels) > 0) {
                update_option( 'facebook_config', [
                    'pixel_id' => $activePixel->id,
                    'use_pii' => 0
                ], '', 'yes' );
            }
            $WC_Webhooks = new WC_Webhooks($pixels[0]);
            $args = [
                'field' => 'active_pixel_id',
                'value' => $activePixel->id
            ];
            $WC_Webhooks->update($args);
        }
    }
}
?>
