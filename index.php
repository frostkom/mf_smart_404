<?php
/*
Plugin Name: MF Smart 404
Plugin URI: https://github.com/frostkom/mf_smart_404
Description:
Version: 2.6.16
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_smart_404
Domain Path: /lang

Original Author URI: http://atastypixel.com/blog/
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	$obj_smart_404 = new mf_smart_404();

	add_action('cron_base', 'activate_smart_404', mt_rand(1, 10));

	add_action('init', array($obj_smart_404, 'init'));

	if(is_admin())
	{
		register_activation_hook(__FILE__, 'activate_smart_404');
		register_uninstall_hook(__FILE__, 'uninstall_smart_404');

		add_action('admin_init', array($obj_smart_404, 'settings_smart_404'));

		add_action('post_updated', array($obj_smart_404, 'post_updated'), 10, 3);
	}

	else
	{
		add_action('pre_get_posts', array($obj_smart_404, 'pre_get_posts'));
	}

	if(wp_doing_ajax())
	{
		add_action('wp_ajax_api_smart_404_save_redirect', array($obj_smart_404, 'api_smart_404_save_redirect'));
		add_action('wp_ajax_api_smart_404_remove_redirect', array($obj_smart_404, 'api_smart_404_remove_redirect'));
	}

	add_action('template_redirect', array($obj_smart_404, 'template_redirect'));
	add_filter('redirect_canonical', array($obj_smart_404, 'redirect_canonical'), 10, 2);

	function activate_smart_404()
	{
		global $wpdb;

		$default_charset = (DB_CHARSET != '' ? DB_CHARSET : 'utf8');

		$arr_add_column = $arr_update_column = $arr_add_index = array();

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."redirect (
			redirectID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			blogID TINYINT UNSIGNED,
			redirectStatus VARCHAR(20) DEFAULT 'publish',
			redirectFrom VARCHAR(255) DEFAULT NULL,
			redirectTo VARCHAR(255) DEFAULT NULL,
			redirectCreated DATETIME DEFAULT NULL,
			redirectUsedDate DATETIME DEFAULT NULL,
			redirectUsedAmount INT UNSIGNED DEFAULT '0',
			PRIMARY KEY (redirectID),
			KEY redirectFrom (redirectFrom),
			KEY redirectCreated (redirectCreated)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->base_prefix."redirect"] = array(
			'redirectUsedDate' => "ALTER TABLE [table] ADD [column] DATETIME DEFAULT NULL AFTER redirectCreated", //260117
			'redirectUsedAmount' => "ALTER TABLE [table] ADD [column] INT UNSIGNED DEFAULT '0' AFTER redirectUsedDate", //260117
			'redirectStatus' => "ALTER TABLE [table] ADD [column] VARCHAR(20) DEFAULT 'publish' AFTER blogID", //260117
		);

		$arr_update_column[$wpdb->base_prefix."redirect"] = array(
			'postID' => "ALTER TABLE [table] DROP COLUMN [column]", //260117
			'redirectDeletedDate' => "ALTER TABLE [table] DROP COLUMN [column]", //260117
			'redirectDeletedID' => "ALTER TABLE [table] DROP COLUMN [column]", //260117
			'redirectDeleted' => "ALTER TABLE [table] DROP COLUMN [column]", //260117
			'userID' => "ALTER TABLE [table] DROP COLUMN [column]", //260117
		);

		update_columns($arr_update_column);
		add_columns($arr_add_column);
		add_index($arr_add_index);
	}

	function uninstall_smart_404()
	{
		mf_uninstall_plugin(array(
			'tables' => 'redirect',
			'options' => array('setting_also_search', 'setting_redirects'),
		));
	}
}