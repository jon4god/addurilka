<?php
/*
Plugin Name: Addurilka
Plugin URI: https://github.com/jon4god/wp-yandex-addurl
Text Domain: wp-ya-addurl
Description: A simple plugin that adds a widget to the admin panel to add and verify the site links to Yandex.
Version: 0.1
Author: Evgeniy Kutsenko
Author URI: http://starcoms.ru
License: GPL2
*/

function wp_ya_addurl_plugin_init() {
    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain( 'wp-ya-addurl', false, $plugin_dir . '/languages/' );
    define('wp-ya-addurl-dir', plugin_dir_path(__FILE__));
}
add_action('plugins_loaded', 'wp_ya_addurl_plugin_init');

function wp_ya_addurl($wp_ya_addurl_admin_bar) {

  function addurl_get_check_URL() {
    $check_url = get_permalink();
    $check_url = preg_replace('~^https?://(?:www\.)?|/$~', '', $check_url);
    $check_url = rawurlencode($check_url);
    return $check_url;
  }
  $linkforcheck = 'http://yandex.ru/yandsearch?text=url%3A%28www.'.addurl_get_check_URL().'%29+%7C+url%3A%28'.addurl_get_check_URL().'%29';

  function addurl_get_sent_URL() {
    $sent_url = get_permalink();
    $sent_url = rawurlencode($sent_url);
    return $sent_url;
  }
  $linkforsent = 'http://webmaster.yandex.ru/addurl.xml?url='.addurl_get_sent_URL();

  $args = array(
    'id' => 'yandexaddurl',
    'title' => __('ADDURILKA', 'wp-ya-addurl'),
    'href' => 'http://webmaster.yandex.ru/addurl.xml',
    'meta' => array(
      'class' => 'yandexaddurl',
      'target' => '_blank',
      'title' => __('Go to Yandex.AddUrl', 'wp-ya-addurl')
    )
  );
  $wp_ya_addurl_admin_bar->add_node($args);

  $args = array(
    'id' => 'yandexurlcheck',
    'title' => __('Check Links', 'wp-ya-addurl'),
    'href' => $linkforcheck,
    'parent' => 'yandexaddurl',
    'meta' => array(
      'class' => 'yandexurlcheck',
      'target' => '_blank',
      'title' => __('Checking the links to indexing in Yandex', 'wp-ya-addurl')
    )
  );
  $wp_ya_addurl_admin_bar->add_node($args);

  $args = array(
    'id' => 'yandexaddurlsent',
    'title' => __('Sending links', 'wp-ya-addurl'),
    'href' => $linkforsent,
    'parent' => 'yandexaddurl',
    'meta' => array(
      'class' => 'yandexaddurlsent',
      'target' => '_blank',
      'title' => __('Send this page to Yandex.Webmaster', 'wp-ya-addurl')
    )
  );
  $wp_ya_addurl_admin_bar->add_node($args);
}

add_action('admin_bar_menu', 'wp_ya_addurl', 999);
