<?php

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
 
$option_name = 'know__server_url';
delete_option($option_name);
delete_site_option($option_name);// for site options in Multisite

$option_name = 'know__api_key';
delete_option($option_name);
delete_site_option($option_name);// for site options in Multisite

$option_name = 'know__api_secret';
delete_option($option_name);
delete_site_option($option_name);// for site options in Multisite


 
// drop a custom database table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}know_base__cookies");