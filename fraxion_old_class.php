<?php
class FraxionPaymentsOld {
	public $site_ID;
	public $plugins_path;
	private $actions;
	private $status_messages;
	private $the_tag = '[frax09alpha]';
	private $blog_id = 0;
	
	private $resourceController;
	private $urlProvider; // Object to provide URLs to fraxion services
	private $fraxService;
	
	private $logger = null;
	
	private $javascript_html_start_tag = '<script language = "javascript" type = "text/javascript" >';
	private $javascript_html_end_tag = '</script>';
	
	// -----------------
	public function __construct(
                FraxionResourceController $resourceController,
                FraxionURLProvider $urlProvider,
                FraxionService $fraxService) {
	
		if (FraxionLoggerImpl::isDebug ()) {
			$this->logger = FraxionLoggerImpl::getLogger ( "FraxionPaymentsOld" );
			if ($this->logger != null && $this->logger->isDebug ()) {
				$this->logger->writeLOG ( "[__construct]" );
			}
		}
		
		$this->resourceController = $resourceController;
		$this->urlProvider = $urlProvider;
		$this->fraxService = $fraxService;

		$fraxion_plugin_path = explode ( DIRECTORY_SEPARATOR, __FILE__ );
		$this->plugins_path = implode ( DIRECTORY_SEPARATOR, array_slice ( $fraxion_plugin_path, 0, - 1 ) ) . DIRECTORY_SEPARATOR;
		if (function_exists ( 'get_option' ) && get_option ( 'fraxion_site_id' ) != false) {
				$this->site_ID = get_option ( 'fraxion_site_id' );
		} else {
				$this->site_ID = NULL;
		}
		$javascriptContent = file_get_contents (
				$this->plugins_path . 'javascript/settings.json' );
		$settings = json_decode ( str_replace ( '\n', null
				, $this->javascript_html_start_tag
					. $javascriptContent
					. $this->javascript_html_end_tag)
				, TRUE );
		$this->actions = $settings ['actions'];
		$this->status_messages = $settings ['messages'];
	}
//	private function getMoneyCost($fraxions_cost) {
//		$money_cost = '';
//		if ($fraxions_cost == 0) {
//			$money_cost = 'free';
//		} elseif ($fraxions_cost < 100) {
//			$money_cost = 'about ' . $fraxions_cost . ' cents';
//		} else {
//			$dollars = $fraxions_cost / 100;
//			$money_cost = 'about $' . number_format ( $dollars, 2 );
//		}
//		return $money_cost;
//	}
	
	// /////
	// ///// ADMIN ///////////
	// //////
	/** Is the site registered or not
	 * @return 'none' if not, else other status from server. */
	private function checkSiteStatus() {
		$site_status = 'none';
		$urlToSend = $this->urlProvider->getSiteStatusUrl(get_option ( 'home' ));
		$frax_dom = $this->fraxService->get($urlToSend);
                
		$reply = $frax_dom->getElementsByTagName ( 'reply' );
		if ($reply->length > 0) {
			$site_status = $reply->item ( 0 )->getAttribute ( 'status' );
		}
		return $site_status;
	}
	/** Echoes the fraxion_admin.js into the page */
	public function admin_js() {
		$javascriptContent = file_get_contents ( $this->plugins_path . 'javascript/fraxion_admin.js' );
		if ($this->logger != null && $this->logger->isDebug ()) {
			$this->logger->writeLOG ( "admin_js " . $javascriptContent );
		}
		echo $this->javascript_html_start_tag
				. $javascriptContent
				. $this->javascript_html_end_tag;
	}
	// -----------------
	public function admin_TagButton() {
		echo '<script language="javascript" type="text/javascript">function setToolbarButton() {jQuery("#ed_toolbar").append("<input type=\'button\' class=\'ed_button\'  id=\'qt_content_fraxion_tag\' onclick=\'fppos=0;contents=\"\";fppos=document.getElementById(\"content\").selectionStart;contents=getElementById(\"content\").value;getElementById(\"content\").value=contents.substring(0,fppos)+\"' . $this->the_tag . '\"+contents.substring(fppos);\' title=\'Insert Fraxion lock tag\' value=\'fraxion\' />");}; jQuery(document).ready(function() {setTimeout("setToolbarButton()", 2000);});</script>';
	}
	// -----------------
        // Set the dashboard setting menu item that displays the fraxion settings
        // by calling admin_Settings()
	public function admin_Menu() {
		add_options_page ( 'Fraxion Settings', 'Fraxion Settings', 'administrator', 'fpsiteoptions', array (
				$this,
				'admin_Settings' 
		) );
	}
	
	private function loadSiteIDFromOptionsTable() {
		if (function_exists ( 'get_option' ) && get_option ( 'fraxion_site_id' ) != false) {
			$this->site_ID = get_option ( 'fraxion_site_id' );
		} else {
			$this->site_ID = null;
		}
	}
	
	private function buildReturnURL() {
		global $user_ID;
		return 'http' . (array_key_exists ( 'HTTPS' , $_SERVER ) ? 's' : null) . urlencode (
					'://' . $_SERVER ['SERVER_NAME'] . $_SERVER ['REQUEST_URI'] 
					);
	}
	
	private function getAdminRegisteredSiteStatusMessage($status_messages) {
		global $user_ID;
		
		if (function_exists ( 'get_blog_count' )) { // /// is MU //////
			$blog_details = get_active_blog_for_user ( $user_ID );
			if ($blog_details->blog_id == 1) { // is master //
				return $status_messages ['admin_mu_base'];
			} else {
				return $status_messages ['admin_mu_blog'];
			}
		} else { // ///// not MU /////////
			return $status_messages ['admin_single'];
		}
	}
	
	private function getUnregisteredSiteStatusMessage($status_messages) {
		global $user_ID;
		
		if (function_exists ( 'get_blog_count' )) { // /// is MU //////
			$blog_details = get_active_blog_for_user ( $user_ID );
			if ($blog_details->blog_id == 1) { // is master //
				return $status_messages ['register_mu_base'];
			} else {
				return $status_messages ['register_mu_blog'];
			}
		} else { // ///// not MU /////////
			return $status_messages ['register_single'];
		}
	}
	// -----------------
        // DIsplay the fraxion settings panel in the settings area
	public function admin_Settings() {
		global $user_ID;
		get_currentuserinfo ();
		$admin_site_settings_panel = '<div class="wrap">';
		$admin_site_settings_panel .= '<h3>Fraxion Settings</h3>';
		$admin_site_settings_panel .= 'v' . FraxionPayments::getVersion() . '<br/>';
		$admin_site_settings_panel .= $this->handleVerifySiteID();
		$this->loadSiteIDFromOptionsTable();
		
		// TODO if siteid then checksitestatus in block - none means unverified
		if ($this->site_ID != null && $this->checkSiteStatus () != 'none') {
			if (array_key_exists ( 'sid' , $_GET )) {
				update_option ( 'fraxion_site_id', $_GET ['sid'] );
				$admin_site_settings_panel .= '<div class="updated"><p><strong>Fraxion Settings Saved :)</strong></p></div>';
			}
			if (array_key_exists ( 'fraxion_settings_update' , $_POST )
					&& array_key_exists ( 'fraxion_site_id' , $_POST )
					&& $_POST ['fraxion_settings_update'] == 'Y') {
				update_option ( 'fraxion_site_id', $_POST ['fraxion_site_id'] );
				$admin_site_settings_panel .= '<div class="updated"><p><strong>Fraxion Settings Saved :)</strong></p></div>';
			}
			
			$theReturnURL = $this->buildReturnURL();
			$urlToSend = $this->urlProvider->getFraxSiteAdminUrl($theReturnURL);
			$admin_site_settings_panel .= '<a href="' . $urlToSend . '">Fraxion Payment Admin</a><br />';
			$admin_site_settings_panel .= $this->getAdminRegisteredSiteStatusMessage($this->status_messages);
			$admin_site_settings_panel .= '<hr />';
		} else { // site_id is null or status is 'none'
			if (function_exists ( 'get_blog_count' )) { // // is MU //////
				$blog_count = get_blog_count ();
				$admin_site_settings_panel .= '<p>';
				$blog_details = get_active_blog_for_user ( $user_ID );
				$admin_site_settings_panel .= ' '
					. '<a href="' . $this->urlProvider->getRegisterSiteURL()
					. '?blog_id=' . $blog_details->blog_id . '&uid_wp=' . $user_ID
					. '&btitle=' . urlencode ( $blog_details->blogname )
					. '&burl=' . urlencode ( get_option ( 'home' ) )
					. '&base_site_url=' . urlencode ( get_blog_option ( $blog_details->blog_id, 'siteurl' ) . '/' )
					. '&returl=' . urlencode ( get_blog_option ( $blog_details->blog_id, 'siteurl' )
						. '/wp-admin/options-general.php?page=fpsiteoptions' )
					. '&vurl=' . urlencode ( get_blog_option ( $blog_details->blog_id, 'siteurl' )
						. '/wp-admin/options-general.php?page=fpsiteoptions' )
					. '">Register your site with Fraxion Payments</a><br />';
			} else { // /// not MU ///////
				$admin_site_settings_panel .= ' '
					. '<a href="' . $this->urlProvider->getRegisterSiteURL()
					. '?uid_wp=' . $user_ID 
					. '&btitle=' . urlencode ( get_option ( 'blogname' ) )
					. '&burl=' . urlencode ( get_option ( 'home' ) ) 
					. '&returl=' . urlencode ( get_option ( 'home' ) . '/wp-admin/options-general.php?page=fpsiteoptions' )
					. '&vurl=' . urlencode ( get_option ( 'home' ) . '/wp-admin/options-general.php?page=fpsiteoptions' )
					. '">Register your site with Fraxion Payments</a><br />';
			}
			$admin_site_settings_panel .= ' You may then lock articles and be paid for access to your content.<br />';
			$admin_site_settings_panel .= $this->getUnregisteredSiteStatusMessage($this->status_messages);
			$admin_site_settings_panel .= '<hr />';
		}
		$admin_site_settings_panel .= '</div>';
		echo $admin_site_settings_panel;
	}
	
	// -----------------
	// called when the event 'publish_post' is triggered
	public function admin_PostSave($post_id) {
		if (isset ( $_POST ['user_ID'] ) && $_POST ['user_ID'] != '' && isset ( $_POST ['site_ID'] ) && $_POST ['site_ID'] != '') {
			// Updates the fraxion database with title etc.
			$urlToSend = $this->urlProvider->getPublishArticleDataToFraxionUrl(
				$_POST ['site_ID'],
				$_POST ['article_ID'],
				$_POST ['post_title'],
				$_POST ['fraxions_cost'],
				($_POST ['fraxion_lock'] == 'lock' ? 'true' : 'false') );
			$frax_dom = $this->fraxService->get($urlToSend);
			if ($reply->length > 0) {
				$status_message = serialize ( $reply );
			} else {
				$status_message = serialize ( $frax_dom->getElementsByTagName ( 'error' ) );
			}
			// TODO Status message is set but not used - what should be done with it??????s
		}
	}

	
	private function handleVerifySiteID() {
		if (array_key_exists ( 'vurl' , $_GET )) {
			$verifyurl = $_GET ['vurl'];
		}

		if (isset ( $verifyurl )) {
			return $this->setSiteID ( $verifyurl );
		}
		return '';
	}
	
	// -----------------
        /** Execute the confirmurl, sent from a confirm email,
         * and receive and store this sites new site Id.
         * @param $confirmurl url sent from a confirm site registration email
         */
	public function setSiteID($confirmurl) {
		$site_ID_full = $this->fraxService->simpleGet($confirmurl);
		if ($site_ID_full == false || $site_ID_full == '') {
			return '<br/>There has been a problem processing the confirm link.'
			. '<br/>Please try again later.'
			. '<br/>If the problem continues please contact Fraction Payments for assistance<br />';
		} else if (substr ( $site_ID_full, 0, 2 ) == 'ok') {
			$table_blog_id = '';
			if (strpos ( $site_ID_full, ',' ) > 0 && strpos ( $site_ID_full, ',0' ) === false) {
				$site_and_blog_array = explode ( ',', substr ( $site_ID_full, 2 ) );
				$site_ID = $site_and_blog_array [0];
				$table_blog_id = $site_and_blog_array [1] . '_';
			} else {
				$site_ID = substr ( $site_ID_full, 2 );
			}
			$this->writeSiteIdToDB($site_ID, $table_blog_id);
			$message = $site_ID;
		} else {
			$message = $site_ID_full;
		}
		return '<br/>Your site has been registered!<br /><br />Site ID: ' . $message . ' has been inserted!<br/>';
	}
	
	private function writeSiteIdToDB($site_ID, $table_blog_id) {
		global $table_prefix;
		
		@mysql_connect ( DB_HOST, DB_USER, DB_PASSWORD );
		mysql_select_db ( DB_NAME );
		// look for blog_id
		$option_present_result = mysql_query ( 'SELECT Count(*) FROM ' . $table_prefix . $table_blog_id . 'options WHERE option_name = "fraxion_site_id"' );
		if (mysql_result ( $option_present_result, 0, 0 ) > 0) {
			$option_result = mysql_query ( 'UPDATE ' . $table_prefix . $table_blog_id . 'options SET option_value = "' . $site_ID . '" WHERE option_name = "fraxion_site_id"' );
		} else {
			$option_result = mysql_query ( 'INSERT INTO ' . $table_prefix . $table_blog_id . 'options (option_name,option_value) Values("fraxion_site_id","' . $site_ID . '")' );
		}
	}
} // End class FraxionPaymentsOld
?>