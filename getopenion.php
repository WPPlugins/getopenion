<?php
defined('ABSPATH') or die('Hack your own stuff!');
/*
 * Plugin Name: getOpenion
 * Plugin URI: https://getopenion.com/wordpress
 * Description: getOpenion is an extremely powerful tool to create online surveys. Add surveys from getOpenion to your posts with this plugin.
 * Version: 1.0.7
 * Author: Pius Ladenburger
 * Author URI: https://pius-ladenburger.de
 * Domain Path: /languages
 * Text Domain: getopenion
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

define('GETOPENION_DOMAIN', 'https://getopenion.com');
define('GETOPENION_DB', $wpdb->prefix . 'go_surveys');
define('GETOPENION_VERSION', '1.0.7');

if (! session_id())
	session_start();
	
	// Internationalization
load_plugin_textdomain('getopenion', false, basename(dirname(__FILE__)) . '/langs');
if (GETOPENION_VERSION !== get_option('getopenion_ver') && get_option('getopenion_appId')) {
	// do some update scripts
	
	// Install the database
	if(str_replace('.', '', get_option('getopenion_ver')) < str_replace('.', '', GETOPENION_VERSION))
		getopenion_db_install();
		
	update_option('getopenion_ver', GETOPENION_VERSION);
} elseif (get_option('getopenion_appId') && get_option('getopenion_check') < strtotime('now -1 day')) { // check if app still exists
	$data = wp_remote_get(GETOPENION_DOMAIN . '/oauth/connect?check&app=' . get_option('getopenion_appId'));
	if ($data['body'] == 'App not found') {
		// App has been removed serverside
		update_option('getopenion_disconnected', true);
		// Remove app clientside + reconnect
		$users = get_users('fields=ID');
		foreach ( $users as $u ) {
			delete_user_meta($u, 'getOpenion_key');
		}
		delete_option('getopenion_appId');
		getopenion_register();
	} else {
		update_option('getopenion_disconnected', false);
	}
	update_option('getopenion_check', time());
}
if (get_option('getopenion_disconnected')) {
		add_action( 'admin_notices', 'go_disconnected' );
}
add_action( 'admin_notices', 'go_ing_offline' );

/* Register this blog at getopenion (this is only used for oauth!) */
register_activation_hook(__FILE__, 'getopenion_setup');
function getopenion_setup() {
	getopenion_db_install();
	getopenion_register();
	update_option('getopenion_ver', GETOPENION_VERSION);
}

function getopenion_db_install() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE ".GETOPENION_DB." (
  survey_code int(11) NOT NULL,
  user_id bigint(20) NOT NULL,
  PRIMARY KEY  (survey_code)
) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

function getopenion_register() {
	if (! get_option('getopenion_appId')) {
		$app_info = wp_remote_get(GETOPENION_DOMAIN . '/oauth/register?domain=' . urlencode(get_bloginfo('wpurl')) . '&name=' . urlencode(get_bloginfo('name')));

		$resp = json_decode($app_info['body'], true);
		update_option('getopenion_appId', $resp['appId']);
		update_option('getopenion_secret', $resp['secret']);
	}
}

/* Update the App ID if the App ID has been lost */
if (!get_option('getopenion_appId')) {
	getopenion_register();
}

/* Delete this blog from getopenion */
register_uninstall_hook(__FILE__, 'getopenion_remove');
function getopenion_remove() {
	$users = get_users('fields=ID');
	foreach ( $users as $u ) {
		delete_user_meta($u, 'getOpenion_key');
	}
	if (get_option('getopenion_appId')) {
		wp_remote_get(GETOPENION_DOMAIN . '/oauth/drop?app=' . get_option('getopenion_appId') . '&sk=' . get_option('getopenion_secret'));
		delete_option('getopenion_appId');
	}
}

/*
 * Add pages in Admin Dashbaord
 */
add_action('admin_menu', 'getopenion_add_menus');
function getopenion_add_menus() {
	// add_options_page('getOpenion', 'getOpenion', 'manage_options', 'getopenion', 'getopenion_settings_page');
	add_menu_page(__('Surveys', 'getopenion'), __('Surveys', 'getopenion'), 'publish_posts', 'getopenion-surveys', 'getopenion_load_surveys', 'dashicons-media-text', 11);
}

/* getOpenion Admin Settings */
function getopenion_settings_page() {
	// Nothing here yet
}

/* getOpenion list users surveys */
function getopenion_load_surveys() {
	global $wpdb;
	include (__DIR__ . '/surveys.php');
}
wp_register_style('getopenion_style', plugins_url('/admin.css', __FILE__));
wp_enqueue_style('getopenion_style');
/*
 * AJAX Stuff
 */

/* Connect the user with getOpenion */
add_action('wp_ajax_connect_getopenion', 'getopenion_connect_user');
function getopenion_connect_user() {
	if (isset($_POST['token'])) {
		$key = wp_remote_get(GETOPENION_DOMAIN . '/oauth/retrieve?app=' . get_option('getopenion_appId') . '&token=' . $_POST['token']);
		if ($key['body'] != '"error"') {
			$key = $key['body'];
			update_user_meta(get_current_user_id(), 'getOpenion_key', $key);
			echo 'success';
			wp_die();
		}
		echo 'error';
	} else {
		echo 'error';
	}
	
	wp_die();
}

add_action('admin_enqueue_scripts', 'getopenion_oauth_scripts');
function getopenion_oauth_scripts($hook) {
	if (! isset($_GET['oauth'])) {
		return;
	}
	wp_enqueue_script('ajax-script', plugins_url('/js/save.js', __FILE__), array(
			'jquery' 
	));
	wp_localize_script('ajax-script', 'localized', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'error' => __('Error'),
			'token' => $_GET['token'],
			'page' => get_admin_url(null, 'admin.php?page=' . $_GET['page']) 
	));
}

add_action('admin_enqueue_scripts', 'getopenion_load_surveys_ajax');
function getopenion_load_surveys_ajax($hook) {
	if ($hook != 'post-new.php' && $hook != 'post.php')
		return;
	
	wp_enqueue_script('ajax-script', plugins_url('/js/surveys.js', __FILE__), array(
			'jquery' 
	));
	wp_localize_script('ajax-script', 'localized', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'error' => __('Error') 
	));
}

add_action('wp_ajax_load_surveys', 'getopenion_surveys_ajax');
function getopenion_surveys_ajax() {
	$key = go_getKey();
	
	$data = wp_remote_get(GETOPENION_DOMAIN . '/api/?list_surveys&contain_folders&app=' . get_option('getopenion_appId') . '&token=' . $key);
	$data = json_decode($data['body'], true);
	
	// Do some sorting
	if (is_array($data['folders'])) {
		foreach ( $data['folders'] as $f ) {
			$folder[$f['ID']] = $f;
			$folder[$f['ID']]['children'] = [];
		}
	}
	
	$items = [];
	foreach ( $data['surveys'] as $s ) {
		if ($s['Parent'] != '0') {
			$folder[$s['Parent']]['children'][$s['order']] = $s;
		} else {
			$items[$s['order']] = $s;
		}
	}
	
	if (is_array($data['folders'])) {
		foreach ( $folder as $f ) {
			if ($f['Parent'] != '0') {
				$folder[$f['Parent']]['children'][$f['order']] = $f;
				unset($folder[$f['ID']]);
			}
		}
		foreach ( $folder as $f ) {
			ksort($folder[$f['ID']]['children']);
			$items[$f['order']] = $f;
		}
	}
	
	ksort($items);
	echo json_encode($items);
	
	wp_die();
}

/*
 * TinyMCE Button Stuff
 */
add_filter('mce_buttons', 'getopenion_register_buttons');
function getopenion_register_buttons($buttons) {
	array_push($buttons, 'separator', 'getopenion_ins');
	return $buttons;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
add_filter('mce_external_plugins', 'getopenion_register_tinymce_js');
function getopenion_register_tinymce_js($plugin_array) {
	$plugin_array['getopenion'] = plugins_url('/js/getopenion-tinymce.js', __FILE__);
	return $plugin_array;
}

$check = false;
function go_getKey($user = null) {
	global $check;
	if(is_null($user)) {
		$user = get_current_user_id();
	}
	$key = str_replace('"', '', get_user_meta($user, 'getOpenion_key', true));
	if (! $check && get_user_meta($$user, 'getOpenion_key', true)) {
		// check if still connected
		$data = wp_remote_get(GETOPENION_DOMAIN . '/api/?app=' . get_option('getopenion_appId') . '&token=' . $key);
		if ($data['body'] == 'invalid') {
			delete_user_meta($$user, 'getOpenion_key');
			$key = '';
		}
		$check = true;
	}
	return $key;
}
function go_backUrl() {
	return urlencode(get_admin_url(null, 'admin.php?page=' . $_GET['page']));
}

function go_disconnected() {
	$class = 'error';
	$message = __('All getOpenion users have been disconnected. They will need to reconnect. (This message will be dismissed tomorrow)', 'getopenion');
    echo "<div class=\"$class\"> <p>$message</p></div>"; 
}

function go_ing_offline() {
	$class = 'error';
	$message = __('getOpenion will not exist anymore after the 1. of July. Please look for alternatives. Sorry.', 'getopenion');
    echo "<div class=\"$class\"> <p>$message</p></div>"; 
}

/*
 * The SHORTCODE!
 */
function getopenion_survey_sc($atts) {
	global $wpdb;
	
	// defaults
	$attrs = shortcode_atts(array(
			'id' => '1',
			'color' => 'transparent',
			'width' => 'auto',
			'height' => 'auto' 
	), $atts);
	
	// Retrieve user token
	$user = $wpdb->get_var($wpdb->prepare('SELECT user_id FROM '.GETOPENION_DB.' WHERE survey_code = %d', $attrs['id']));
	$key = go_getKey($user);
	$output = '';

	// get survey data
	$data = wp_remote_get(GETOPENION_DOMAIN . '/api/?survey_frame&id=' . $attrs['id'] . '&app=' . get_option('getopenion_appId') . '&token=' . $key);
	$data = json_decode($data['body'], true);
	$url = $data['url'];
	$height = ($attrs['height'] === 'auto')? $data['height']:$attrs['height'];
	$height = (!$height)? 800:$height;
	
	// buffer output
	$output .= '<div class="getopenion_survey" style="background-color:' . $attrs['color'] . ';width:' . $attrs['width'] . ';height:' . $height . 'px">';
	$output .= '<iframe style="height:100%;width:100%;border:0;background:transparent;overflow:scroll;" src="' . $url . '"></iframe>';
	$output .= '</div>';
	
	return $output;
}
add_shortcode('getopenion', 'getopenion_survey_sc');