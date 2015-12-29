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

  if (get_option('wp_ya_addurl_setting_autocheck') == true) {
    $addurilkacheck = '&#9675; ';
    
    $url = 'https://yandex.ru/search/xml?user=' . get_option('wp_ya_addurl_setting_user') . '&key=' . get_option('wp_ya_addurl_setting_user_key') . '&query='. get_permalink() . '';
    $ip = get_option('wp_ya_addurl_setting_user_ip');
    
    function addurl_autocheckyandex ($url, $ip) {
      $checkyandex = 0;
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_NOBODY, false);
      curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (Windows; U; Windows NT 5.0; En; rv:1.8.0.2) Gecko/20070306 Firefox/1.0.0.4");
      curl_setopt($ch, CURLOPT_INTERFACE, $ip);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      $xml_data = curl_exec($ch);
      curl_close($ch);
      $xml = new SimpleXMLElement($xml_data);
      if (isset($xml->response->results->grouping->group->doc->url)) $xml_url = $xml->response->results->grouping->group->doc->url;
      if ($xml_url = get_permalink()) $checkyandex = 1;
      return $checkyandex;
    }
    $checkyandex = addurl_autocheckyandex ($url, $ip);
  
    $checkgoogle = 0;
    $url = 'http://ajax.googleapis.com/ajax/services/search/web?v=1.0&q=site:'. get_permalink();
    $body = file_get_contents($url);
    $json = json_decode($body);
    foreach ($json->responseData->results as $resultjson) {
      $result_google['urls']= $resultjson->url;
      if ($result_google = get_permalink()) {$checkgoogle = 1;}
    }
  
    if ($checkyandex and $checkgoogle) $addurilkacheck = '&#9679; ';
    if ($checkyandex and !$checkgoogle) $addurilkacheck = '&#9686; ';
    if (!$checkyandex and $checkgoogle) $addurilkacheck = '&#9687; ';
  
    $addurilkatitle = $addurilkacheck . __('Addurilka', 'wp-ya-addurl');
  } 
  else $addurilkatitle = __('Addurilka', 'wp-ya-addurl');
  
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
      'title' => __('Checking the url to indexing', 'wp-ya-addurl')
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
      'title' => __('Checking the url to indexing in Yandex', 'wp-ya-addurl')
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
      'title' => __('Checking the url to indexing in Google', 'wp-ya-addurl')
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
      'title' => __('Send the url in search engine', 'wp-ya-addurl')
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
      'title' => __('Send this url to Yandex.Webmaster', 'wp-ya-addurl')
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
      'title' => __('Send this url to Google', 'wp-ya-addurl')
    )
  );
  $wp_ya_addurl_admin_bar->add_node($args);
}
add_action('admin_bar_menu', 'wp_ya_addurl', 91);

function wp_ya_addurl_settings_init() {
  add_settings_field(
    'wp_ya_addurl_setting_user',
    __('User', 'wp-ya-addurl'),
    'wp_ya_addurl_setting_user',
    'reading',
    'wp_ya_addurl_plugin_menu'
  );
  register_setting( 'reading', 'wp_ya_addurl_setting_user' );

  add_settings_field(
    'wp_ya_addurl_setting_user_key',
    __('Key', 'wp-ya-addurl'),
    'wp_ya_addurl_setting_user_key',
    'reading',
    'wp_ya_addurl_plugin_menu'
  );
  register_setting( 'reading', 'wp_ya_addurl_setting_user_key' );

  add_settings_field(
    'wp_ya_addurl_setting_user_ip',
    __('IP', 'wp-ya-addurl'),
    'wp_ya_addurl_setting_user_ip',
    'reading',
    'wp_ya_addurl_plugin_menu'
  );
  register_setting( 'reading', 'wp_ya_addurl_setting_user_ip' );
  
  add_settings_field(
    'wp_ya_addurl_setting_autocheck',
    __('Аutocheck', 'wp-ya-addurl'),
    'wp_ya_addurl_setting_autocheck',
    'reading',
    'wp_ya_addurl_plugin_menu'
  );
  register_setting( 'reading', 'wp_ya_addurl_setting_autocheck' );
}
add_action( 'admin_init', 'wp_ya_addurl_settings_init' );

function wp_ya_addurl_plugin_menu() {
  add_options_page(__('Addurilka', 'wp-ya-addurl'), __('Addurilka', 'wp-ya-addurl'), 'manage_options', 'wp_ya_addurl-plugin', 'wp_ya_addurl_plugin_page');
}
add_action('admin_menu', 'wp_ya_addurl_plugin_menu');

function wp_ya_addurl_plugin_page(){
  echo '<div class="wrap">';
  echo "<h2>" . __('Setting for Addurilka', 'wp-ya-addurl') . "</h2>";
  echo "<h3>" . __('Values ​​display for automatic check url (test)', 'wp-ya-addurl') . "</h3>";
  echo "<p>&#9679; " . __('Addurilka - url in Yandex and Google', 'wp-ya-addurl') . "</p>";
  echo "<p>&#9686; " . __('Addurilka - url in Yandex', 'wp-ya-addurl') . "</p>";
  echo "<p>&#9687; " . __('Addurilka - url in Google', 'wp-ya-addurl') . "</p>";
  echo "<p>&#9675; " . __('Addurilka - no url in Yandex and Google', 'wp-ya-addurl') . "</p>";
  echo "<h3>" . __('Setting for Yandex', 'wp-ya-addurl') . "</h3>";
  echo "<p>" . __('In Yandex all very uncomfortable and paranoid, so try to set up autocheck. It can work, but maybe not.', 'wp-ya-addurl') . "</p>";
  echo '<form action="options.php" method="post">';
  wp_nonce_field('update-options');
  echo '<table class="form-table">
  <tr valign="top">
  <th scope="row">' . __('Enable automatic check', 'wp-ya-addurl') . '</th>
  <td>';
  echo '<input name="wp_ya_addurl_setting_autocheck" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'wp_ya_addurl_setting_autocheck' ), false ) . ' />';
  echo '</td>
  </tr>
  <tr valign="top">
  <th scope="row">' . __('Yandex user', 'wp-ya-addurl') . '</th>
  <td>';
  echo '<input name="wp_ya_addurl_setting_user" id="wp_ya_addurl_setting_user" type="text" class="code" value="' . get_option( 'wp_ya_addurl_setting_user' ) . '" />
      <p class="description">' . __('Get a user from <a href="https://xml.yandex.ru/settings/" target="_blank">https://xml.yandex.ru/settings/</a>', 'wp-ya-addurl') . "</p>";
  echo '</td>
  </tr>
  <tr valign="top">
  <th scope="row">' . __('Secret Key', 'wp-ya-addurl') . '</th>
  <td>';
  echo '<input name="wp_ya_addurl_setting_user_key" id="wp_ya_addurl_setting_user_key" type="text" class="code" value="' . get_option( 'wp_ya_addurl_setting_user_key' ) . '" />
      <p class="description">' . __('Get a key from <a href="https://xml.yandex.ru/settings/" target="_blank">https://xml.yandex.ru/settings/</a>', 'wp-ya-addurl') . "</p>";
  echo '</td>
  </tr>
  <tr valign="top">
  <th scope="row">' . __('Your IP', 'wp-ya-addurl') . '</th>
  <td>';
  echo '<input name="wp_ya_addurl_setting_user_ip" id="wp_ya_addurl_setting_user_ip" type="text" class="code" value="' . get_option( 'wp_ya_addurl_setting_user_ip' ) . '" />
      <p class="description">' . __('Get a IP from <a href="https://xml.yandex.ru/settings/" target="_blank">https://xml.yandex.ru/settings/</a>', 'wp-ya-addurl') . "</p>";
  echo '</td>
  </tr>
  </table>
  </div>
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="page_options" value="wp_ya_addurl_setting_user,wp_ya_addurl_setting_user_key,wp_ya_addurl_setting_user_ip,wp_ya_addurl_setting_autocheck" />';
  echo '<p class="submit"><input type="submit" class="button-primary" value="' . __('Save setting', 'wp-ya-addurl') .'"></p>
        </form>';
}
