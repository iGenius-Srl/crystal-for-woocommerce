<?php
/**
 * Api realted to pixels
 */
class WC_Webhooks {
    public $cfw_at;
    public $cfw_active_pixel;
    public $item;

    public function __construct($post) {
        $this->item = $post;
        $this->cfw_at = get_option('cfw_at', '');
    }

    public function post($action, $args = []) {
        $date = new DateTime();
        $response = wp_remote_post( WEBHOOK_URL . '/' . $this->item['post_type'],
            [
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => ['Content-Type' => 'application/json'],
                'cookies' => $_COOKIE,
                'body' => json_encode([
                    'cfw_at' => $this->cfw_at,
                    'action' => $action,
                    'timestamp' => $date->getTimestamp(),
                    'type' => $this->item['post_type'],
                    'data' => $args // updated post
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
        }
    }

    public function create() {
        $this->post('create');
    }

    public function update($args) {
        $this->post('update', $args);
    }

    public function delete() {
        $this->post('delete');
    }

}
?>
