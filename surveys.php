<?php
defined('ABSPATH') or die('Hack your own stuff!');
$key = go_getKey();
echo '<div class="wrap" id="getopenion_admin">';

echo '<h2>' . __('Surveys', 'getopenion');
if (isset($_GET['oauth'])) {
	echo '</h2>';
	
	echo '<h2 class="center loader"><img src="' . get_bloginfo('wpurl') . '/wp-admin/images/spinner-2x.gif" width="20" /> Connecting...</h2>';
} elseif (empty($key)) {
	echo '</h2>';
	echo '<div id="connect_getopenion">';
	{
		echo '<h3>' . __('Connect to getOpenion', 'getopenion') . '</h3>';
		echo '<p>' . __("Your account has not been connected with your account at getOpenion. Click the button to login at getOpenion and connect it with your blog. It's free and secure. Your surveys will not be visible to other users of your blog.", 'getopenion') . '</p>';
		echo '<p class="button_wrap"><a class="button" href="' . GETOPENION_DOMAIN . '/oauth/connect?app=' . get_option('getopenion_appId') . '&redirect=' . urlencode(substr(get_admin_url(null, 'admin.php?page=' . $_GET['page'] . '&oauth'), strlen( get_bloginfo('wpurl')))) . '">' . __('Connect now', 'getopenion') . '</a></p>';
	}
	echo '</div>';
} else {
	echo ' <a class="add-new-h2" href="' . GETOPENION_DOMAIN . '/Dashboard/addRemote?back='.go_backUrl().'">' . __('Add', 'getopenion') . '</a>';
	echo '</h2>';
	require (__DIR__ . '/class.table.php');
	/* Sample Data */
	if (!isset($_GET['filter_by']) || ! isset($_SESSION['cache']['getopenion']) || $_SESSION['cache']['userid'] != get_current_user_id()) {
		$surveys = wp_remote_get(GETOPENION_DOMAIN . '/api/?list_surveys&app=' . get_option('getopenion_appId') . '&token=' .  $key);
		$data = json_decode($surveys['body'], true);
		foreach($data as $survey) {
			$wpdb->insert( 
				GETOPENION_DB, 
				array( 
					'survey_code' => $survey['ID'], 
					'user_id' => get_current_user_id(), 
				) 
			);
		}
		$_SESSION['cache']['getopenion'] = $data;
		$_SESSION['cache']['userid'] = get_current_user_id();
	} else {
		$data = $_SESSION['cache']['getopenion'];
	}
	/* END Sample Data */
	$surveyList = new Survey_List_Table($data, 10);
	$surveyList->prepare_items();
	if (isset($_GET['filter_by'])) {
		unset($_GET['filter_by']);
		echo '<a href="?page=' . $_REQUEST['page'] . '&' . http_build_query($_GET) . '">' . __('All', 'getopenion') . '</a> | ' . __('Published', 'getopenion');
	} else {
		$_GET['filter_by'] = 'published';
		echo __('All', 'getopenion') . ' | <a href="?page=' . $_REQUEST['page'] . '&' . http_build_query($_GET) . '">' . __('Published', 'getopenion') . '</a>';
	}
	$surveyList->display();
}
echo '</div>';