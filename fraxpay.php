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
Version: 1.3.6
Author URI: http://www.fraxionpayments.com/
*/

include("fraxion_class.php");
$frax = new FraxionPayments();

add_action('init', array($frax,'checkFUT'));
add_action('admin_head', array($frax,'admin_js'));
add_action('admin_head', array($frax,'admin_TagButton'));
add_action('admin_menu', array($frax,'admin_Post'));
add_action('admin_menu', array($frax,'admin_Menu'));
add_action('publish_post', array($frax,'admin_PostSave'));
add_action('wp_head', array($frax,'fraxion_js'));
add_action('wp_head', array($frax,'fraxion_css'));
add_filter('the_content', 'do_shortcode',1);
add_filter('the_content', array($frax,'checkStatus'),10,1);
add_filter('the_excerpt', array($frax,'checkStatus'),10,1);
add_action('wp_footer',  array($frax,'fraxion_respond'));