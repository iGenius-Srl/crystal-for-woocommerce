<?php

/**
 * Api realted to handshake
 */
class CFW_Api {
    private $acccess_token;

    public function __construct($access_token) {
        $this->acccess_token = $access_token;
    }

    public function getUserData() {
        $response = wp_remote_get( WEBHOOK_URL.'/brandData', [
            'method' => 'GET',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '2.0',
            'blocking' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'access-token' => $this->acccess_token,
                'referer' => admin_url('options-general.php?page=cfw_settings')
            ],
            'cookies' => $_COOKIE
        ]);
        $code = $response['response']['code'];
        if ( $code !== 200 ) {
            $error_message = $code = $response['response']['message'];
            add_action('admin_notices', function() {?>
                <div class="error below-h3">
                    <p>Service temporary unavailable. Come back soon.</p>
                </div>
            <?});
        } else {
            $response = json_decode($response['body']);
            $responseData = $response->data;
            // success call
            $pixels = $responseData->pixels;
            $plan = $responseData->plan;
            if ($plan == 1) {
                // user with free plan
                $pixels = [];
                $activePixel = false;
            } else {
                // user with premium plan
                $activePixel = (array) get_option('cfw_active_pixel');
                if (isset($activePixel['id'])) {
                    // user without active pixel
                    $activePixel = count($pixels) > 0 ? (array) $pixels[0] : false;
                } else {
                    foreach ($pixels as $pixel) {
                        $found = false;
                        $pixel = (array) $pixel;
                        if ($pixel['id'] == $activePixel['id']) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        // user with active pixel not in available pixels
                        $activePixel = count($pixels) > 0 ? (array) $pixels[0] : false;
                    }
                }
            }

            //update user plan
            update_option( 'cfw_plan', $responseData->plan, '', 'yes' );
            // update user pixelse and active pixel
            update_option( 'cfw_pixels', $pixels);
            update_option( 'cfw_active_pixel', $activePixel);
            if(count($pixels) > 0) {
                update_option( 'facebook_config', [
                    'pixel_id' => $activePixel['id'],
                    'use_pii' => 0
                ], '', 'yes' );
                $WC_Webhooks = new WC_Webhooks($pixels[0]);
                $args = [
                    'field' => 'active_pixel_id',
                    'value' => $activePixel['id']
                ];
            } else {
                update_option( 'facebook_config', [
                    'pixel_id' => 0,
                    'use_pii' => 0
                ], '', 'yes' );
            }
        }
    }

    public function sendPluginDeactivation() {
        $response = wp_remote_post( WEBHOOK_URL.'/pluginDeactivation', [
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '2.0',
            'blocking' => true,
            'headers' => ['Content-Type' => 'application/json', 'access-token' => $this->acccess_token],
            'cookies' => $_COOKIE
        ]);
        $response = json_decode($response['body']);
        $responseData = $response->body;
        if ( $response->status === 404  ) {
            $error_message = $responseData->message;
        }
    }
}
?>
