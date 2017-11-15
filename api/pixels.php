<?php

/**
 * Api realted to pixels
 */
class WC_Pixels_Api {
    public function __construct() {
    }
    //TODO: updated header
    public static function list() {
        $response = wp_remote_get( WEBHOOK_URL.'/pixels', array(
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'access-token' => get_option('cfw_at', '')
            ],
            'cookies' => $_COOKIE,
            'body' => null,
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
            $optionActivePixel = get_option('cfw_active_pixel', null);
            if($optionActivePixel) {
                update_option( 'cfw_pixels', $pixels);
                $existAP = false;
                foreach($pixels as $index => $pixel) {
                    if($pixel->id === $optionActivePixel->id) {
                        $existAP = true;
                    }
                }
                if(!$existAP) {
                    update_option( 'cfw_active_pixel', $activePixel, '', 'yes' );
                    if(count($pixels) > 0) {
                        update_option( 'facebook_config', [
                            'pixel_id' => $optionActivePixel->id,
                            'use_pii' => 0
                        ], '', 'yes' );
                    }
                    $WC_Webhooks = new WC_Webhooks($pixels[0]);
                    $args = [
                        'field' => 'active_pixel_id',
                        'value' => $optionActivePixel->id
                    ];
                    $WC_Webhooks->update($args);
                }else {
                    update_option( 'cfw_active_pixel', $activePixel, '', 'yes' );
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


            }else {
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
}
?>
