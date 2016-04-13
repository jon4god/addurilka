<?php
/*
Plugin Name: Addurilka
Plugin URI: https://github.com/jon4god/addurilka
Text Domain: addurilka
Domain Path: /languages
Description: A simple plugin that adds a widget to the admin panel to add and verify the site links to search engine.
Version: 0.5.1
Author: jon4god
Author URI: http://starcoms.ru
License: GPL2
*/

add_action('plugins_loaded', 'wp_ya_addurl_plugin_init');
function wp_ya_addurl_plugin_init() {
		$plugin_dir = basename(dirname(__FILE__));
		load_plugin_textdomain( 'addurilka', false, $plugin_dir . '/languages/' );
		define('addurilka-dir', plugin_dir_path(__FILE__));
}

register_activation_hook( __FILE__, 'addurl_activate');
function addurl_activate() {
  set_transient( 'addurl-admin-notice', true, 5 );
  add_option('wp_ya_addurl_setting_show_yandex', 1);
  add_option('wp_ya_addurl_setting_show_google', 1);
  add_option('wp_ya_addurl_setting_webmaster_tool', 1);
}

add_action( 'admin_notices', 'addurl_on_activation_note' );
function addurl_on_activation_note() {
  if( get_transient( 'addurl-admin-notice' ) ){
      echo '<div class="updated notice is-dismissible">
      <p>' .__('Please, set setting for Addurilka plugin.', 'addurilka') . '</p> 
      </div>';
    delete_transient( 'addurl-admin-notice' );
  }
}

$plugin_file = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin_file", 'addurl_plugin_settings_link' );
function addurl_plugin_settings_link($links) { 
	$settings_link = '<a href="options-general.php?page=wp_ya_addurl-plugin">' . __('Settings', 'addurilka') . '</a>'; 
	array_unshift( $links, $settings_link ); 
	return $links; 
}

add_action('admin_bar_menu', 'wp_ya_addurl', 91);
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
	$linkforsenttogoogle = 'https://www.google.com/Webmaster/tools/submit-url?urlnt='.addurl_get_sent_URL();

	if (get_option('wp_ya_addurl_setting_autocheck') == true) {
		$addurilkacheck = '&#9675; ';
		if (get_option('wp_ya_addurl_setting_user') && get_option('wp_ya_addurl_setting_user_key') && get_option('wp_ya_addurl_setting_user_ip')) {
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
    } else {
      $checkyandex = 0;
      if( get_transient( 'addurl-admin-notice-2' ) ){
        echo '<div class="updated notice is-dismissible">
        <p>' .__('Please, enter all <strong><a href="options-general.php?page=wp_ya_addurl-plugin.php">setting</a></strong> for Yandex.XML. Automatic check link for Yandex not working.', 'addurilka') . '</p> 
        </div>';
        delete_transient( 'addurl-admin-notice-2' );
      }
    }
    
		$checkgoogle = 0;
		$url = 'http://ajax.googleapis.com/ajax/services/search/web?v=1.0&q=site:'. get_permalink();
		$body = file_get_contents($url);
		$json = json_decode($body);
		if ($json!="") {
		foreach ($json->responseData->results as $resultjson) {
			$result_google['urls']= $resultjson->url;
			if ($result_google = get_permalink()) {$checkgoogle = 1;}
		}
		}
	
		if ($checkyandex and $checkgoogle) $addurilkacheck = '&#9679; ';
		if ($checkyandex and !$checkgoogle) $addurilkacheck = '&#9686; ';
		if (!$checkyandex and $checkgoogle) $addurilkacheck = '&#9687; ';
	
		if (get_option('wp_ya_addurl_setting_short_name') == true) {
			$addurilkatitle = $addurilkacheck . __('A', 'addurilka');
		} else {
			$addurilkatitle = $addurilkacheck . __('Addurilka', 'addurilka');
		}
	} 
	else {
		if (get_option('wp_ya_addurl_setting_short_name') == true) {
			$addurilkatitle = __('A', 'addurilka');
		} else {
			$addurilkatitle = __('Addurilka', 'addurilka');
		}
	}
	
	$args = array(
		'id' => 'addurilka',
		'title' => $addurilkatitle,
		'meta' => array(
			'class' => 'addurilka',
			'target' => '_blank',
			'title' => __('Add url in search engine', 'addurilka')
		)
	);
	$wp_ya_addurl_admin_bar->add_node($args);

$args = array(
		'id' => 'addurlcheck',
		'title' => __('Check the link in', 'addurilka'),
		'parent' => 'addurilka',
		'meta' => array(
			'class' => 'addurlcheck',
			'target' => '_blank',
			'menu_icon'		=> 'dashicons-products',
			'title' => __('Checking the url to indexing', 'addurilka')
		)
	);
	$wp_ya_addurl_admin_bar->add_node($args);

	if (get_option('wp_ya_addurl_setting_show_yandex') == true) {
		$args = array(
			'id' => 'yandexurlcheck',
			'title' => __('Yandex', 'addurilka'),
			'href' => $linkforcheckyandex,
			'parent' => 'addurlcheck',
			'meta' => array(
				'class' => 'yandexurlcheck',
				'target' => '_blank',
				'title' => __('Checking the url to indexing in Yandex', 'addurilka')
			)
		);
		$wp_ya_addurl_admin_bar->add_node($args);
	}

	if (get_option('wp_ya_addurl_setting_show_google') == true) {
		$args = array(
			'id' => 'googleurlcheck',
			'title' => __('Google', 'addurilka'),
			'href' => $linkforcheckgoogle,
			'parent' => 'addurlcheck',
			'meta' => array(
				'class' => 'googleurlcheck',
				'target' => '_blank',
				'title' => __('Checking the url to indexing in Google', 'addurilka')
			)
		);
		$wp_ya_addurl_admin_bar->add_node($args);
	}

	$args = array(
		'id' => 'addurlsent',
		'title' => __('Send the link to', 'addurilka'),
		'parent' => 'addurilka',
		'meta' => array(
			'class' => 'addurlsent',
			'target' => '_blank',
			'title' => __('Send the url in search engine', 'addurilka')
		)
	);
	$wp_ya_addurl_admin_bar->add_node($args);

	if (get_option('wp_ya_addurl_setting_show_yandex') == true) {
		$args = array(
			'id' => 'yandexaddurlsent',
			'title' => __('Yandex', 'addurilka'),
			'href' => $linkforsenttoyandex,
			'parent' => 'addurlsent',
			'meta' => array(
				'class' => 'yandexaddurlsent',
				'target' => '_blank',
				'title' => __('Send this url to Yandex.Webmaster', 'addurilka')
			)
		);
		$wp_ya_addurl_admin_bar->add_node($args);
	}

	if (get_option('wp_ya_addurl_setting_show_google') == true) {
		$args = array(
			'id' => 'googleaddurlsent',
			'title' => __('Google', 'addurilka'),
			'href' => $linkforsenttogoogle,
			'parent' => 'addurlsent',
			'meta' => array(
				'class' => 'googleaddurlsent',
				'target' => '_blank',
				'title' => __('Send this url to Google', 'addurilka')
			)
		);
		$wp_ya_addurl_admin_bar->add_node($args);
	}
	
	if (get_option('wp_ya_addurl_setting_webmaster_tool') == true) {
  $args = array(
		'id' => 'addsite',
		'title' => __('Add the site to', 'addurilka'),
		'parent' => 'addurilka',
		'meta' => array(
			'class' => 'addsite',
			'target' => '_blank',
			'title' => __('Add the site in search engine webmaster', 'addurilka')
		)
	);
	$wp_ya_addurl_admin_bar->add_node($args);
	}
	
	  $args = array(
			'id' => 'googleWebmaster',
			'title' => __('Google Webmaster', 'addurilka'),
			'href' => 'https://www.google.com/Webmaster/tools/home',
			'parent' => 'addsite',
			'meta' => array(
				'class' => 'googleWebmaster',
				'target' => '_blank',
				'title' => __('Send site to Google Webmaster', 'addurilka')
			)
		);
		$wp_ya_addurl_admin_bar->add_node($args);
		
		$args = array(
			'id' => 'yaWebmaster',
			'title' => __('Yandex Webmaster', 'addurilka'),
			'href' => 'https://webmaster.yandex.ru/?tab=1',
			'parent' => 'addsite',
			'meta' => array(
				'class' => 'yaWebmaster',
				'target' => '_blank',
				'title' => __('Open Yandex Webmaster', 'addurilka')
			)
		);
		$wp_ya_addurl_admin_bar->add_node($args);
		
		$args = array(
			'id' => 'yasitesent',
			'title' => __('Yandex add site', 'addurilka'),
			'href' => 'https://webmaster.yandex.ru/site/?wizard=add.site',
			'parent' => 'yaWebmaster',
			'meta' => array(
				'class' => 'yasitesent',
				'target' => '_blank',
				'title' => __('Send site to Yandex Webmaster', 'addurilka')
			)
		);
		$wp_ya_addurl_admin_bar->add_node($args);
		
		$args = array(
			'id' => 'bingwebmaster',
			'title' => __('Bing Webmaster', 'addurilka'),
			'href' => 'http://www.bing.com/toolbox/webmaster',
			'parent' => 'addsite',
			'meta' => array(
				'class' => 'bingwebmaster',
				'target' => '_blank',
				'title' => __('Open Bing Webmaster', 'addurilka')
			)
		);
		$wp_ya_addurl_admin_bar->add_node($args);
		
		$args = array(
			'id' => 'bingsitesent',
			'title' => __('Bing add site', 'addurilka'),
			'href' => 'http://www.bing.com/toolbox/submit-site-url?url=' . home_url()  . '',
			'parent' => 'bingwebmaster',
			'meta' => array(
				'class' => 'bingsitesent',
				'target' => '_blank',
				'title' => __('Send site to Bing', 'addurilka')
			)
		);
		$wp_ya_addurl_admin_bar->add_node($args);
		
		$args = array(
			'id' => 'baiduWebmaster',
			'title' => __('Baidu Webmaster', 'addurilka'),
			'href' => 'http://zhanzhang.baidu.com/linksubmit/url',
			'parent' => 'addsite',
			'meta' => array(
				'class' => 'baiduWebmaster',
				'target' => '_blank',
				'title' => __('Send site to Baidu Webmaster', 'addurilka')
			)
		);
		$wp_ya_addurl_admin_bar->add_node($args);
		
		$args = array(
			'id' => 'mailWebmaster',
			'title' => __('Mail.ru Webmaster', 'addurilka'),
			'href' => 'http://webmaster.mail.ru/',
			'parent' => 'addsite',
			'meta' => array(
				'class' => 'mailWebmaster',
				'target' => '_blank',
				'title' => __('Open Mail.ru Webmaster', 'addurilka')
			)
		);
		$wp_ya_addurl_admin_bar->add_node($args);
		
		$args = array(
			'id' => 'sputnikWebmaster',
			'title' => __('Sputnik Webmaster', 'addurilka'),
			'href' => 'http://corp.sputnik.ru/webmaster',
			'parent' => 'addsite',
			'meta' => array(
				'class' => 'sputnikWebmaster',
				'target' => '_blank',
				'title' => __('Open Sputnik Webmaster', 'addurilka')
			)
		);
		$wp_ya_addurl_admin_bar->add_node($args);
}

add_action( 'admin_init', 'wp_ya_addurl_settings_init' );
function wp_ya_addurl_settings_init() {
	add_settings_field(
		'wp_ya_addurl_setting_user',
		__('User', 'addurilka'),
		'wp_ya_addurl_setting_user',
		'reading',
		'wp_ya_addurl_plugin_menu'
	);
	register_setting( 'reading', 'wp_ya_addurl_setting_user' );

	add_settings_field(
		'wp_ya_addurl_setting_user_key',
		__('Key', 'addurilka'),
		'wp_ya_addurl_setting_user_key',
		'reading',
		'wp_ya_addurl_plugin_menu'
	);
	register_setting( 'reading', 'wp_ya_addurl_setting_user_key' );

	add_settings_field(
		'wp_ya_addurl_setting_user_ip',
		__('IP', 'addurilka'),
		'wp_ya_addurl_setting_user_ip',
		'reading',
		'wp_ya_addurl_plugin_menu'
	);
	register_setting( 'reading', 'wp_ya_addurl_setting_user_ip' );
	
	add_settings_field(
		'wp_ya_addurl_setting_autocheck',
		__('Аutocheck', 'addurilka'),
		'wp_ya_addurl_setting_autocheck',
		'reading',
		'wp_ya_addurl_plugin_menu'
	);
	register_setting( 'reading', 'wp_ya_addurl_setting_autocheck' );
	
	add_settings_field(
		'wp_ya_addurl_setting_short_name',
		__('Short name', 'addurilka'),
		'wp_ya_addurl_setting_short_name',
		'reading',
		'wp_ya_addurl_plugin_menu'
	);
	register_setting( 'reading', 'wp_ya_addurl_setting_short_name' );
	
	add_settings_field(
		'wp_ya_addurl_setting_show_yandex',
		__('Show Yandex', 'addurilka'),
		'wp_ya_addurl_setting_show_yandex',
		'reading',
		'wp_ya_addurl_plugin_menu'
	);
	register_setting( 'reading', 'wp_ya_addurl_setting_show_yandex' );

	add_settings_field(
		'wp_ya_addurl_setting_show_google',
		__('Show Google', 'addurilka'),
		'wp_ya_addurl_setting_show_google',
		'reading',
		'wp_ya_addurl_plugin_menu'
	);
	register_setting( 'reading', 'wp_ya_addurl_setting_show_google' );
	
	add_settings_field(
		'wp_ya_addurl_setting_webmaster_tool',
		__('Show Webmaster Tools', 'addurilka'),
		'wp_ya_addurl_setting_webmaster_tool',
		'reading',
		'wp_ya_addurl_plugin_menu'
	);
	register_setting( 'reading', 'wp_ya_addurl_setting_webmaster_tool' );
}

add_action('admin_menu', 'wp_ya_addurl_plugin_menu');
function wp_ya_addurl_plugin_menu() {
	add_options_page(__('Addurilka', 'addurilka'), __('Addurilka', 'addurilka'), 'manage_options', 'wp_ya_addurl-plugin', 'wp_ya_addurl_plugin_page');
}

function wp_ya_addurl_plugin_page(){
	echo '<table width="100%">';
  echo '<tr><td valign="top">';
	echo '<div class="wrap">';
	echo "<h2>" . __('Settings for Addurilka', 'addurilka') . "</h2>";
	echo "<h3>" . __('Values ​​display for automatic check url (test)', 'addurilka') . "</h3>";
	echo "<p>&#9679; " . __('Addurilka - url in Yandex and Google', 'addurilka') . "</p>";
	echo "<p>&#9686; " . __('Addurilka - url in Yandex', 'addurilka') . "</p>";
	echo "<p>&#9687; " . __('Addurilka - url in Google', 'addurilka') . "</p>";
	echo "<p>&#9675; " . __('Addurilka - no url in Yandex and Google', 'addurilka') . "</p>";
	echo "<h3>" . __('Main options', 'addurilka') . "</h3>";
	echo '<form action="options.php" method="post">';
	wp_nonce_field('update-options');
	echo '<table class="form-table">
	<tr valign="top">
	<th scope="row">' . __('Enable short name', 'addurilka') . '<p class="description">' . __('Show "A"', 'addurilka') . '</p></th>
	<td>';
	echo '<input name="wp_ya_addurl_setting_short_name" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'wp_ya_addurl_setting_short_name' ), false ) . ' />';
	echo '</td>
	</tr>
  <tr valign="top">
	<th scope="row">' . __('Enable show Webmaster tool', 'addurilka') . '</th>
	<td>';
	echo '<input name="wp_ya_addurl_setting_webmaster_tool" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'wp_ya_addurl_setting_webmaster_tool' ), false ) . ' />';
	echo '</td>
	</tr>
	<tr valign="top">
	<th scope="row">' . __('Enable check Yandex', 'addurilka') . '</th>
	<td>';
	echo '<input name="wp_ya_addurl_setting_show_yandex" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'wp_ya_addurl_setting_show_yandex' ), false ) . ' />';
	echo '</td>
	</tr>
	<tr valign="top">
	<th scope="row">' . __('Enable check Google', 'addurilka') . '</th>
	<td>';
	echo '<input name="wp_ya_addurl_setting_show_google" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'wp_ya_addurl_setting_show_google' ), false ) . ' />';
	echo '</td>
	</tr>
	<tr valign="top">
	<th scope="row">' . __('Enable automatic check', 'addurilka') . '<p class="description">' . __('Only for Yandex & Google', 'addurilka') . '</p></th>
	<td>';
	echo '<input name="wp_ya_addurl_setting_autocheck" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'wp_ya_addurl_setting_autocheck' ), false ) . ' />';
	echo '</td>
	</tr>
	<tr>
	<td><h3>' . __('Setting for Yandex', 'addurilka') . '</h3></td>
	<td><p>' . __('In Yandex all very uncomfortable and paranoid, so try to set up autocheck. It can work, but maybe not.', 'addurilka') . '</p></td>
	</tr>
	<tr valign="top">
	<th scope="row">' . __('Yandex user', 'addurilka') . '</th>
	<td>';
	echo '<input name="wp_ya_addurl_setting_user" id="wp_ya_addurl_setting_user" type="text" class="code" value="' . get_option( 'wp_ya_addurl_setting_user' ) . '" />
			<p class="description">' . __('Get a user from <a href="https://xml.yandex.ru/settings/" target="_blank">https://xml.yandex.ru/settings/</a>', 'addurilka') . "</p>";
	echo '</td>
	</tr>
	<tr valign="top">
	<th scope="row">' . __('Secret Key', 'addurilka') . '</th>
	<td>';
	echo '<input name="wp_ya_addurl_setting_user_key" id="wp_ya_addurl_setting_user_key" type="text" class="code" value="' . get_option( 'wp_ya_addurl_setting_user_key' ) . '" />
			<p class="description">' . __('Get a key from <a href="https://xml.yandex.ru/settings/" target="_blank">https://xml.yandex.ru/settings/</a>', 'addurilka') . "</p>";
	echo '</td>
	</tr>
	<tr valign="top">
	<th scope="row">' . __('Your IP', 'addurilka') . '</th>
	<td>';
	echo '<input name="wp_ya_addurl_setting_user_ip" id="wp_ya_addurl_setting_user_ip" type="text" class="code" value="' . get_option( 'wp_ya_addurl_setting_user_ip' ) . '" />
			<p class="description">' . __('Get a IP from <a href="https://xml.yandex.ru/settings/" target="_blank">https://xml.yandex.ru/settings/</a>', 'addurilka') . "</p>";
	echo '</td>
	</tr>
	</table>
	</div>
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="wp_ya_addurl_setting_show_google,wp_ya_addurl_setting_webmaster_tool,wp_ya_addurl_setting_show_yandex,wp_ya_addurl_setting_short_name,wp_ya_addurl_setting_user,wp_ya_addurl_setting_user_key,wp_ya_addurl_setting_user_ip,wp_ya_addurl_setting_autocheck" />';
	echo '<p class="submit"><input type="submit" class="button-primary" value="' . __('Save setting', 'addurilka') .'"></p>
				</form>';
  echo '</td>';
  ?>
  <!-- !Информация о плагине -->
  <td valign="top" align="left" width="30%">
  <div style="padding: 1.5em; background-color: #FAFAFA; border: 1px solid #ddd; margin: 1em; float: right; width: 22em;">
	<h3><?php _e('Thanks for using Addurilka', 'addurilka') ?></h3>
	<p style="float: right; margin: 0 0 1em 1em;"><a href="http://starcoms.ru" target="_blank"><?php echo get_avatar("jon4god@mail.ru", '64'); ?></a></p>
	<p><?php _e('Dear admin!<br />Thank you for using my plugin!<br />I hope it is useful for your site.', 'addurilka') ?></p>
	<p><a href="http://starcoms.ru" target="_blank"><?php _e('Evgeniy Kutsenko', 'addurilka') ?></a></p>

	<h3><?php _e('I like this plugin<br>– how can I thank you?', 'addurilka') ?></h3>
	<p><?php _e('There are several ways for you to say thanks:', 'addurilka') ?></p>
	<ul style="list-style-type: disc; margin-left: 20px;">
		<li><?php printf(__('<a href="%1$s" target="_blank">Buy me a cup of coffee</a> to stay awake and work on this plugin', 'addurilka'), "https://www.paypal.me/jon4god") ?></li>
		<li><?php printf(__('<a href="%1$s" target="_blank">Give 5 stars</a> over at the WordPress Plugin Directory', 'addurilka'), "https://wordpress.org/support/view/plugin-reviews/addurilka") ?></li>
		<li><?php printf(__('Share infotmation or make a nice blog post about the plugin', 'addurilka')) ?></li>
	</ul>

	<h3><?php _e('Support', 'addurilka') ?></h3>
	<p><?php printf(__('Please see the <a href="%1$s" target="_blank">support forum</a> or <a href="%2$s" target="_blank">plugin\'s site</a> for help.', 'addurilka'), "https://wordpress.org/support/plugin/addurilka", "http://wp.starcoms.ru/plugin-addurilka/") ?></p>
	
	<h1><?php _e("Good luck!", 'addurilka') ?></h1>
  </div>
</td></tr></table>
<?php 
}
?>