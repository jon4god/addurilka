<?php
/*
Plugin Name: Addurilka
Plugin URI: https://github.com/jon4god/wp-yandex-addurl
Text Domain: wp-ya-addurl
Description: A simple plugin that adds a widget to the admin panel to add and verify the site links to search engine.
Version: 0.3
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
  $linkforcheckyandex = 'http://yandex.ru/yandsearch?text=url%3A%28www.'.addurl_get_check_URL().'%29+%7C+url%3A%28'.addurl_get_check_URL().'%29';
  $linkforcheckgoogle = 'https://www.google.ru/?q=site:'.addurl_get_check_URL().'#newwindow=1&q=site:'.addurl_get_check_URL().'';

  function addurl_get_sent_URL() {
    $sent_url = get_permalink();
    $sent_url = rawurlencode($sent_url);
    return $sent_url;
  }
  $linkforsenttoyandex = 'http://webmaster.yandex.ru/addurl.xml?url='.addurl_get_sent_URL();
  $linkforsenttogoogle = 'https://www.google.com/webmasters/tools/submit-url?urlnt='.addurl_get_sent_URL();

  $addurilkacheck = '&#9675; ';
  $checkyandex = 0;
  $checkgoogle = 0;
  
  $url = 'http://ajax.googleapis.com/ajax/services/search/web?v=1.0&q=site:'.addurl_get_sent_URL();
  $body = file_get_contents($url);
  $json = json_decode($body);
  foreach ($json->responseData->results as $resultjson) {
    $result_google['urls']= $resultjson->url;
    if ($result_google != '') {$checkgoogle = 1;}
  }

  if ($checkyandex and $checkgoogle) $addurilkacheck = '&#9679; ';
  if ($checkyandex and !$checkgoogle) $addurilkacheck = '&#9686; ';
  if (!$checkyandex and $checkgoogle) $addurilkacheck = '&#9687; ';

  $addurilkatitle = $addurilkacheck . __('Addurilka', 'wp-ya-addurl');

  $args = array(
    'id' => 'addurilka',
    'title' => $addurilkatitle,
    'meta' => array(
      'class' => 'addurilka',
      'target' => '_blank',
      'title' => __('Add url in search engine', 'wp-ya-addurl')
    )
  );
  $wp_ya_addurl_admin_bar->add_node($args);

$args = array(
    'id' => 'addurlcheck',
    'title' => __('Check the link in', 'wp-ya-addurl'),
    'href' => $linkforcheckyandex,
    'parent' => 'addurilka',
    'meta' => array(
      'class' => 'addurlcheck',
      'target' => '_blank',
      'menu_icon'   => 'dashicons-products',
      'title' => __('Checking the links to indexing', 'wp-ya-addurl')
    )
  );
  $wp_ya_addurl_admin_bar->add_node($args);

  $args = array(
    'id' => 'yandexurlcheck',
    'title' => __('Yandex', 'wp-ya-addurl'),
    'href' => $linkforcheckyandex,
    'parent' => 'addurlcheck',
    'meta' => array(
      'class' => 'yandexurlcheck',
      'target' => '_blank',
      'title' => __('Checking the links to indexing in Yandex', 'wp-ya-addurl')
    )
  );
  $wp_ya_addurl_admin_bar->add_node($args);

  $args = array(
    'id' => 'googleurlcheck',
    'title' => __('Google', 'wp-ya-addurl'),
    'href' => $linkforcheckgoogle,
    'parent' => 'addurlcheck',
    'meta' => array(
      'class' => 'googleurlcheck',
      'target' => '_blank',
      'title' => __('Checking the links to indexing in Google', 'wp-ya-addurl')
    )
  );
  $wp_ya_addurl_admin_bar->add_node($args);

  $args = array(
    'id' => 'addurlsent',
    'title' => __('Send the link to', 'wp-ya-addurl'),
    'href' => $linkforcheckyandex,
    'parent' => 'addurilka',
    'meta' => array(
      'class' => 'addurlsent',
      'target' => '_blank',
      'title' => __('Send the links in search engine', 'wp-ya-addurl')
    )
  );
  $wp_ya_addurl_admin_bar->add_node($args);

  $args = array(
    'id' => 'yandexaddurlsent',
    'title' => __('Yandex', 'wp-ya-addurl'),
    'href' => $linkforsenttoyandex,
    'parent' => 'addurlsent',
    'meta' => array(
      'class' => 'yandexaddurlsent',
      'target' => '_blank',
      'title' => __('Send this page to Yandex.Webmaster', 'wp-ya-addurl')
    )
  );
  $wp_ya_addurl_admin_bar->add_node($args);

  $args = array(
    'id' => 'googleaddurlsent',
    'title' => __('Google', 'wp-ya-addurl'),
    'href' => $linkforsenttogoogle,
    'parent' => 'addurlsent',
    'meta' => array(
      'class' => 'googleaddurlsent',
      'target' => '_blank',
      'title' => __('Send this page to Google', 'wp-ya-addurl')
    )
  );
  $wp_ya_addurl_admin_bar->add_node($args);
}

add_action('admin_bar_menu', 'wp_ya_addurl', 91);
