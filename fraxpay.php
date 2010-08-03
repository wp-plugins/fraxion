<?php
/**
 * @package Fraxion_Payments
 * @author Fraxion Payments
 */
/*
Plugin Name: Fraxion Payments
Plugin URI: http://www.fraxionpayments.com/
Description: This plugin manages document locking.
Author: Fraxion Payments
Version: 0.5.5
Author URI: http://www.fraxionpayments.com/
*/

include("fraxion_class.php");
FraxionPayments::$site_ID = get_option('fraxion_site_id');

add_action('admin_head', array('FraxionPayments','admin_css'));
add_action('admin_head', array('FraxionPayments','admin_js'));
add_action('admin_head', array('FraxionPayments','admin_TagButton'));
add_action('admin_menu', array('FraxionPayments','admin_Post'));
add_action('admin_menu', array('FraxionPayments','admin_Menu'));
add_action('publish_post', array('FraxionPayments','admin_PostSave'));
add_action('wp_head', array('FraxionPayments','fraxion_js'));
add_action('wp_head', array('FraxionPayments','fraxion_css'));
add_filter('the_content', 'do_shortcode', 9);
add_action('the_content', array('FraxionPayments','checkStatus'),10,1);
add_action('wp_footer',  array('FraxionPayments','fraxion_respond'));