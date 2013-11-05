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
Version: 2.0.0
Author URI: http://www.fraxionpayments.com/
*/

include("plugins_path_impl.php");
include("fraxion_logger_impl.php");
include("fraxion_error_page_impl.php");

// include("fraxion_url_sender_mock.php");
// $urlSender = new FraxionURLSenderMock();
include("fraxion_url_sender_impl.php");
$urlSender = new FraxionURLSenderImpl();

include("fraxion_url_provider_impl.php");
$fraxURLProvider = new FraxionURLProviderImpl();

include("fraxion_language_provider_impl.php");
$fraxLanguageProvider = new FraxionLanguageProviderImpl();

include("fraxion_action_provider_impl.php");
$fraxActionProvider = new FraxionActionProviderImpl($fraxURLProvider, $fraxLanguageProvider);

include("fraxion_service_impl.php");
$fraxService = new FraxionServiceImpl($urlSender, $fraxURLProvider);
//include("fraxion_service_mock.php");
//$fraxService = new FraxionServiceMock();

include("fraxion_article_logic.php");
$articleLogic = new FraxionArticleLogic($fraxService, $fraxURLProvider);

include("fraxion_resource_service_impl.php");
$resourceService = new FraxionResourceServiceImpl();

include("fraxion_resource_controller.php");
$resourceController = new FraxionResourceController($articleLogic, $resourceService);

include("fraxion_banner_writer_impl.php");
$bannerWriter = new FraxionBannerWriterImpl($fraxURLProvider, $fraxLanguageProvider, $fraxActionProvider);

include("fraxion_old_class.php");
$fraxold = new FraxionPaymentsOld($resourceController);

include("fraxion_class.php");
$frax = new FraxionPayments($fraxService, $bannerWriter, $fraxURLProvider, $fraxold);


add_action('init', array($frax,'checkFUT'));
add_action('admin_head', array($fraxold,'admin_js'));
add_action('admin_head', array($fraxold,'admin_TagButton'));
add_action('admin_menu', array($fraxold,'admin_Post'));
add_action('admin_menu', array($fraxold,'admin_Menu'));
add_action('publish_post', array($fraxold,'admin_PostSave'));
add_action('wp_head', array($frax,'fraxion_js'));
add_action('wp_head', array($frax,'fraxion_css'));
add_filter('the_content', 'do_shortcode',1);
add_filter('the_content', array($frax,'push_banner'),10,1);
add_filter('the_excerpt', array($frax,'push_banner'),10,1);
add_action('wp_footer',  array($frax,'fraxion_respond'));

add_filter('query_vars', array($resourceController,'my_plugin_query_vars'));
add_action('parse_request', array($resourceController,'doFraxResourceRequest'));
register_activation_hook( __FILE__, array($resourceController,'install_resources') );
add_action( 'plugins_loaded', array($resourceController,'update_resources') );