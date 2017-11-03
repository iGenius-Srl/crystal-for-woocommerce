<?php
/**
 * @package FacebookCommerce
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('WC_Facebookcommerce_Graph_API')) :

  if (!class_exists('WC_Facebookcommerce_Async_Request')) {
    include_once 'fbasync.php';
  }

/**
 * FB Graph API helper functions
 *
 */
class WC_Facebookcommerce_Graph_API {
  const GRAPH_API_URL = 'https://graph.facebook.com/v2.9/';
  const CURL_TIMEOUT = 500;

  /**
   * Cache the api_key
   */
  var $api_key;

  /**
   * Init
   */
  public function __construct($api_key) {
    $this->api_key = $api_key;
  }

  public function _get($url, $api_key = '') {
    $api_key = $api_key ?: $this->api_key;
    return wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'timeout' => self::CURL_TIMEOUT,
    ));
  }

  public function _post($url, $data, $api_key = '') {
    if (class_exists('WC_Facebookcommerce_Async_Request')) {
      return self::_post_async($url, $data);
    } else {
      return self::_post_sync($url, $data);
    }
  }

  public function _post_sync($url, $data, $api_key = '') {
    $api_key = $api_key ?: $this->api_key;
    return wp_remote_post($url, array(
        'body'    => $data,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'timeout' => self::CURL_TIMEOUT,
    ));
  }

  public function _post_async($url, $data, $api_key = '') {
    if (!class_exists('WC_Facebookcommerce_Async_Request')) {
      return;
    }

    $api_key = $api_key ?: $this->api_key;
    $fbasync = new WC_Facebookcommerce_Async_Request();

    $fbasync->query_url = $url;
    $fbasync->query_args = array();
    $fbasync->post_args = array(
      'body'    => $data,
      'headers' => array(
        'Authorization' => 'Bearer ' . $api_key,
      ),
      'timeout' => self::CURL_TIMEOUT,
    );

    return $fbasync->dispatch();
  }

  public function _delete($url, $api_key = '') {
    $api_key = $api_key ?: $this->api_key;

    return wp_remote_request($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'timeout' => self::CURL_TIMEOUT,
        'method' => 'DELETE',
    ));
  }

  // GET https://graph.facebook.com/vX.X/{page-id}/?fields=name
  public function get_page_name($page_id, $api_key = '') {
    $api_key = $api_key ?: $this->api_key;

    $url = self::GRAPH_API_URL . (string)$page_id
      . '/?fields=name';
    $response = self::_get($url, $api_key);
    if (is_wp_error($response)) {
      WC_Facebookcommerce_Integration::log($response->get_error_message());
      return;
    }
    if ($response['response']['code'] != '200') {
      return;
    }

    $response_body = wp_remote_retrieve_body($response);

    return json_decode($response_body)->name;
  }

  public function validate_product_catalog($product_catalog_id) {
    $url = self::GRAPH_API_URL . (string)$product_catalog_id;
    $response = self::_get($url);
    if (is_wp_error($response)) {
      WC_Facebookcommerce_Integration::log($response->get_error_message());
      return;
    }
    return $response['response']['code'] == '200';
  }

  // POST https://graph.facebook.com/vX.X/{product-catalog-id}/product_groups
  public function create_product_group($product_catalog_id, $data) {
    $url = self::GRAPH_API_URL . (string)$product_catalog_id
      . '/product_groups';
    return self::_post($url, $data);
  }

  // POST https://graph.facebook.com/vX.X/{product-group-id}/products
  public function create_product_item($product_group_id, $data) {
    $url = self::GRAPH_API_URL . (string)$product_group_id . '/products';
    return self::_post($url, $data);
  }

  public function update_product_group($product_catalog_id, $data) {
    $url = self::GRAPH_API_URL . (string)$product_catalog_id;
    return self::_post($url, $data);
  }

  public function update_product_item($product_id, $data) {
    $url = self::GRAPH_API_URL . (string)$product_id;
    return self::_post($url, $data);
  }

  public function delete_product_item($product_item_id) {
    $product_item_url = self::GRAPH_API_URL . (string)$product_item_id;
    return self::_delete($product_item_url);
  }

  public function delete_product_group($product_group_id) {
    $product_group_url = self::GRAPH_API_URL . (string)$product_group_id;
    return self::_delete($product_group_url);
  }

  public function log($ems_id, $message, $error) {
    $log_url = self::GRAPH_API_URL . (string)$ems_id . '/log_events';

    $data = array(
      'message'=> $message,
      'error' => $error
    );

    self::_post($log_url, $data);
  }
}

endif;
