<?php
/*
Plugin Name: Yandex Addurl Admin Bar Widgets
Plugin URI: https://github.com/jon4god/wp-yandex-addurl
Text Domain: wp-yandex-addurl
Description: A simple plugin that adds a widget to the admin panel to add and verify the site links to Yandex.
Version: 0.1
Author: Evgeniy Kutsenko
Author URI: http://starcoms.ru
License: GPL2
*/

function plugin_init() {
    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain( 'wp-yandex-addurl', false, $plugin_dir . '/languages/' );
    define('wp-yandex-addurl-dir', plugin_dir_path(__FILE__));
}
add_action('plugins_loaded', 'plugin_init');

function custom_toolbar_link($wp_admin_bar) {

  function get_check_URL() {
    $check_url = get_permalink();
    $check_url = preg_replace('~^https?://(?:www\.)?|/$~', '', $check_url);
    $check_url = rawurlencode($check_url);
    return $check_url;
  }
  $linkforcheck = 'http://yandex.ru/yandsearch?text=url%3A%28www.'.get_check_URL().'%29+%7C+url%3A%28'.get_check_URL().'%29';

  function get_sent_URL() {
    $sent_url = get_permalink();
    $sent_url = rawurlencode($sent_url);
    return $sent_url;
  }
  $linkforsent = 'http://webmaster.yandex.ru/addurl.xml?url='.get_sent_URL();

  $args = array(
    'id' => 'yandexaddurl',
    'title' => __('ADDURILKA', 'wp-yandex-addurl'),
    'href' => 'http://webmaster.yandex.ru/addurl.xml',
    'meta' => array(
      'class' => 'yandexaddurl',
      'target' => '_blank',
      'title' => __('Go to Yandex.AddUrl', 'wp-yandex-addurl')
    )
  );
  $wp_admin_bar->add_node($args);

  $args = array(
    'id' => 'yandexurlcheck',
    'title' => __('Check Links', 'wp-yandex-addurl'),
    'href' => $linkforcheck,
    'parent' => 'yandexaddurl',
    'meta' => array(
      'class' => 'yandexurlcheck',
      'target' => '_blank',
      'title' => __('Checking the links to indexing in Yandex', 'wp-yandex-addurl')
    )
  );
  $wp_admin_bar->add_node($args);

  $args = array(
    'id' => 'yandexaddurlsent',
    'title' => __('Sending links', 'wp-yandex-addurl'),
    'href' => $linkforsent,
    'parent' => 'yandexaddurl',
    'meta' => array(
      'class' => 'yandexaddurlsent',
      'target' => '_blank',
      'title' => __('Send this page to Yandex.Webmaster', 'wp-yandex-addurl')
    )
  );
  $wp_admin_bar->add_node($args);
}

add_action('admin_bar_menu', 'custom_toolbar_link', 999);
