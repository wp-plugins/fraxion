<?php
class FraxionPaymentsOld {
	private $version = '2.0.0c1';
	public $site_ID;
	public $plugins_path;
	private $urls;
	private $actions;
	private $params;
	private $fp_post_status = "locked";
	private $the_tag = '[frax09alpha]';
	private $site_url;
	private $blog_id = 0;
	private $fut;
	private $requested_url;
	private $bot = false;
	
	private $resourceController;
	
	// -----------------
	public function __construct(FraxionResourceController $resourceController) {
		$this->resourceController = $resourceController;
		
		$fraxion_plugin_path = explode ( DIRECTORY_SEPARATOR, __FILE__ );
		$this->plugins_path = implode ( DIRECTORY_SEPARATOR, array_slice ( $fraxion_plugin_path, 0, - 1 ) ) . DIRECTORY_SEPARATOR;
		if (function_exists ( 'get_option' ) && get_option ( 'fraxion_site_id' ) != false) {
			$this->site_ID = get_option ( 'fraxion_site_id' );
		} else {
			$this->site_ID = NULL;
		}
	}
	// -----------------
	private function writeLOG($message) {
		$fp = fopen ( $_SERVER ['DOCUMENT_ROOT'] . '/log/frax_log.txt', 'a' );
		fwrite ( $fp, $message );
		fclose ( $fp );
	}
	// -----------------
	public function getFraxionService($uriID, $params) {
		if (empty ( $this->urls )) {
			$settings = json_decode ( str_replace ( '\n', null, file_get_contents ( $this->plugins_path . 'javascript' . DIRECTORY_SEPARATOR . 'settings.json' ) ), TRUE );
			$this->urls = $settings ['urls'];
		}
		$frax_doc = '';
		$cFraxion = curl_init ();
		curl_setopt ( $cFraxion, CURLOPT_URL, $this->urls [$uriID] . '?confid=0&' . http_build_query ( $params ) );
		curl_setopt ( $cFraxion, CURLOPT_RETURNTRANSFER, true );
		$frax_doc = curl_exec ( $cFraxion );
		curl_close ( $cFraxion );
		if ($frax_doc == '' || $frax_doc == false || strpos ( $frax_doc, '<?xml' ) === false) {
			$frax_reply = new DOMDocument ();
			// $ele = $frax_reply->createElement('error', 'noComm');
			$frax_reply->appendChild ( $frax_reply->createElement ( 'error', 'noServ' ) );
		} else {
			$frax_reply = DOMDocument::loadXML ( $frax_doc );
		}
		// self::writeLOG("Frax reply: " . serialize($frax_doc) . " time: " .date("Y-m-d H:i:s") . "\n");
		return $frax_reply;
	}
	
	private function getMoneyCost($fraxions_cost) {
		$money_cost = '';
		if ($fraxions_cost == 0) {
			$money_cost = 'free';
		} elseif ($fraxions_cost < 100) {
			$money_cost = 'about ' . $fraxions_cost . ' cents';
		} else {
			$dollars = $fraxions_cost / 100;
			$money_cost = 'about $' . number_format ( $dollars, 2 );
		}
		return $money_cost;
	}
	
	// /////
	// ///// ADMIN ///////////
	// //////
	private function checkSiteStatus() {
		$site_status = 'none';
		$frax_dom = $this->getFraxionService ( 'sitestatus', array (
				'burl' => get_option ( 'home' ) 
		) );
		$reply = $frax_dom->getElementsByTagName ( 'reply' );
		if ($reply->length > 0) {
			$site_status = $reply->item ( 0 )->getAttribute ( 'status' );
		}
		return $site_status;
	}
	// -----------------
	public function admin_js() {
		echo file_get_contents ( $this->plugins_path . 'javascript/fraxion_admin.js' );
	}
	
	// -----------------
	public function admin_TagButton() {
		echo '<script language="javascript" type="text/javascript">function setToolbarButton() {jQuery("#ed_toolbar").append("<input type=\'button\' class=\'ed_button\'  id=\'qt_content_fraxion_tag\' onclick=\'fppos=0;contents=\"\";fppos=document.getElementById(\"content\").selectionStart;contents=getElementById(\"content\").value;getElementById(\"content\").value=contents.substring(0,fppos)+\"' . $this->the_tag . '\"+contents.substring(fppos);\' title=\'Insert Fraxion lock tag\' value=\'fraxion\' />");}; jQuery(document).ready(function() {setTimeout("setToolbarButton()", 2000);});</script>';
	}
	// -----------------
	public function admin_Menu() {
		add_options_page ( 'Fraxion Settings', 'Fraxion Settings', 'administrator', 'fpsiteoptions', array (
				$this,
				'admin_Settings' 
		) );
	}
	// -----------------
	public function admin_Settings() {
		global $user_ID;
		get_currentuserinfo ();
		$settings = json_decode ( str_replace ( '\n', null, file_get_contents ( $this->plugins_path . 'javascript/settings.json' ) ), TRUE );
		$this->actions = $settings ['actions'];
		$status_message = '';
		$status_messages = $settings ['messages'];
		$this->urls = $settings ['urls'];
		$admin_site_settings_panel = '<div class="wrap">';
		$admin_site_settings_panel .= '<h3>Fraxion Settings</h3>';
		if (isset ( $_GET ['vurl'] )) {
			$admin_site_settings_panel .= $this->setSiteID ( $_GET ['vurl'] );
		}
		
		if (function_exists ( 'get_option' ) && get_option ( 'fraxion_site_id' ) != false) {
			$this->site_ID = get_option ( 'fraxion_site_id' );
		} else {
			$this->site_ID = null;
		}
		// TODO if siteid then checksitestatus in block - none means unverified
		if ($this->site_ID != null && $this->checkSiteStatus () != 'none') {
			if ($_GET ['sid']) {
				update_option ( 'fraxion_site_id', $_GET ['sid'] );
				$admin_site_settings_panel .= '<div class="updated"><p><strong>Fraxion Settings Saved :)</strong></p></div>';
			}
			if ($_POST ['fraxion_settings_update'] == 'Y') {
				update_option ( 'fraxion_site_id', $_POST ['fraxion_site_id'] );
				$admin_site_settings_panel .= '<div class="updated"><p><strong>Fraxion Settings Saved :)</strong></p></div>';
			}
			$settings = json_decode ( str_replace ( '\n', null, file_get_contents ( $this->plugins_path . 'javascript/settings.json' ) ), TRUE );
			$admin_site_settings_panel .= '<a href="' . $this->urls ['admin'] . '?returl=http' . ($_SERVER ['HTTPS'] ? 's' : null) . urlencode ( '://' . $_SERVER ['SERVER_NAME'] . $_SERVER ['REQUEST_URI'] ) . '&siteurl=' . urlencode ( get_option ( 'home' ) ) . '&blogname=' . urlencode ( get_option ( 'blogname' ) ) . '&sid=' . get_option ( 'fraxion_site_id' ) . '&uid_wp=' . $user_ID . '&confid=0">Fraxion Payment Admin</a><br />';
			if (function_exists ( 'get_blog_count' )) { // /// is MU //////
				$blog_details = get_active_blog_for_user ( $user_ID );
				if ($blog_details->blog_id == 1) { // is master //
					$admin_site_settings_panel .= $status_messages ['admin_mu_base'];
				} else {
					$admin_site_settings_panel .= $status_messages ['admin_mu_blog'];
				}
			} else { // ///// not MU /////////
				$admin_site_settings_panel .= $status_messages ['admin_single'];
			}
			$admin_site_settings_panel .= '<hr />';
		} else {
			if (function_exists ( 'get_blog_count' )) { // // is MU //////
				$blog_count = get_blog_count ();
				$admin_site_settings_panel .= '<p>';
				$blog_details = get_active_blog_for_user ( $user_ID );
				$admin_site_settings_panel .= 'Register your site with Fraxion Payments - <a href="' . $this->urls ['register'] . '?
													blog_id=' . $blog_details->blog_id . '&uid_wp=' . $user_ID . '&
													btitle=' . urlencode ( $blog_details->blogname ) . '&burl=' . urlencode ( get_option ( 'home' ) ) . '&base_site_url=' . urlencode ( get_blog_option ( $blog_details->blog_id, 'siteurl' ) . '/' ) . '&returl=' . urlencode ( get_blog_option ( $blog_details->blog_id, 'siteurl' ) . '/wp-admin/options-general.php?page=fpsiteoptions' ) . '&vurl=' . urlencode ( get_blog_option ( $blog_details->blog_id, 'siteurl' ) . '/wp-admin/options-general.php?page=fpsiteoptions' ) . '">Click Here</a><br />';
				if ($blog_details->blog_id == 1) { // is master //
					$admin_site_settings_panel .= $status_messages ['register_mu_base'];
				} else {
					$admin_site_settings_panel .= $status_messages ['register_mu_blog'];
				}
			} else { // /// not MU ///////
				$admin_site_settings_panel .= 'Register your site with Fraxion Payments - <a href="' . $this->urls ['register'] . '?
													uid_wp=' . $user_ID . '&btitle=' . urlencode ( get_option ( 'blogname' ) ) . '&
													burl=' . urlencode ( get_option ( 'home' ) ) . '&returl=' . urlencode ( get_option ( 'home' ) . '/wp-admin/options-general.php?page=fpsiteoptions' ) . '&vurl=' . urlencode ( get_option ( 'home' ) . '/wp-admin/options-general.php?page=fpsiteoptions' ) . '">Click Here</a><br />';
				$admin_site_settings_panel .= $status_messages ['register_single'];
			}
		}
		$admin_site_settings_panel .= '</div>';
		echo $admin_site_settings_panel;
	}
	// -----------------
	public function admin_Post() {
		add_meta_box ( 'frax_post_admin', 'Fraxion Payments', array (
				$this,
				'admin_PostPanel' 
		), 'post', 'side', 'high' );
		add_meta_box ( 'frax_post_admin', 'Fraxion Payments', array (
				$this,
				'admin_PostPanel' 
		), 'page', 'side', 'high' );
	}
	// -----------------
	public function admin_PostPanel($post_ID) {
		$fraxions_cost = 0;
		$status_message = '';
		global $user_ID;
		get_currentuserinfo ();
		$settings = json_decode ( str_replace ( '\n', null, file_get_contents ( $this->plugins_path . 'javascript/settings.json' ) ), TRUE );
		if ($this->site_ID != NULL) {
			$article_ID = '';
			$locked = 'false';
			if ($post_ID != '') {
				if ($the_post = wp_is_post_revision ( $post_ID->ID )) {
					$article_ID = $the_post;
				} else {
					$article_ID = $post_ID->ID;
				}
				$post_title = $post_ID->post_title;
				$frax_dom = $this->getFraxionService ( 'settings', array (
						'sid' => $this->site_ID,
						'aid' => $article_ID,
						'uid_wp' => $user_ID 
				) );
				$reply = $frax_dom->getElementsByTagName ( 'reply' );
				if ($reply->length > 0) {
					if ($reply->item ( 0 )->hasAttribute ( 'idcon' ) && $reply->item ( 0 )->getAttribute ( 'idcon' ) == 'false') {
						$status_message = 'editLock';
					} elseif ($reply->item ( 0 )->hasAttribute ( 'lock' ) && $reply->item ( 0 )->getAttribute ( 'lock' ) == 'true') {
						$locked = "true";
						$fraxions_cost = $reply->item ( 0 )->getAttribute ( 'cost' );
					}
				} else {
					$error = $frax_dom->getElementsByTagName ( 'error' );
					if ($error->length > 0) {
						$status_message = $error->item ( 0 )->firstChild->nodeValue;
					} else {
						$status_message = 'noServ';
					} // end if has 'lock'
				} // end if reply > 0
			}
			$permit = 'blank';
			$frax_dom = $this->getFraxionService ( 'getpermit', array (
					'sid' => $this->site_ID,
					'aid' => $article_ID,
					($user_ID == '' ? 'uid_none' : 'uid_wp') => $user_ID 
			) );
			$reply = $frax_dom->getElementsByTagName ( 'reply' );
			if ($reply->length > 0 && $reply->item ( 0 )->nodeValue != '') {
				$permit = $reply->item ( 0 )->nodeValue;
			} else {
				$error = $frax_dom->getElementsByTagName ( 'error' );
				if ($error->length > 0) {
					$status_message = $error->item ( 0 )->firstChild->nodeValue;
				} else {
					$status_message = 'noServ';
				}
			}
			echo "<div id='sid' style='display:none;'>" . $this->site_ID . "</div>";
			echo "<div id='uid_wp' style='display:none;'>$user_ID</div>";
			echo "<div id='aid' style='display:none'>$article_ID</div>";
			echo "<div id='locked' style='display:none;'>$locked</div>";
			echo "<div id='cost' style='display:none;'>$fraxions_cost</div>";
			echo "<div id='permit' style='display:none;'>$permit</div>";
			$this->echoFraxionDetails($statusMessage, $locked, $fraxions_cost);
			if ($status_message == "") {
				// Edit fraxion data dialog and button - JQuery dialog in iFrame
				echo '<style>';
				echo '	#mbox{background-color:#eee; padding:8px; border:2px outset #666;}';
				echo '	#mbm{font-family:sans-serif;font-weight:bold;float:right;padding-bottom:5px;}';
				echo '	#ol{background-image: url(' . plugins_url ( 'fraxion' ) . '/images/overlay.png);}';
				echo '	.mdDialog {display:none; background-color:#FFF;}';
				echo '	#fp_login { background-color:#FFF;}';
				echo '</style>';
				echo '<script language="javascript" type="text/javascript">';
				echo '	function mdClosed() {';
				echo '		jQuery.post("' . plugins_url ( 'fraxion' );
				echo '/fraxion_server.php",{"action":"refreshPostPanel","siteID":"';
				echo $this->site_ID . '","postID":"' . $article_ID . '","userID":"' . $user_ID;
				echo '"},function(fraxion_details) { ';
				echo 'jQuery("#fraxion_details").html(';
				echo '"This Post is <strong>"+(fraxion_details.locked=="true"?"locked":"unlocked")+"</strong>';
				echo '&nbsp;|&nbsp;Price is <strong>"+fraxion_details.cost+"</strong> fraxions"';
				echo ');jQuery("#locked").html(fraxion_details.locked);';
				echo 'jQuery("#permit").html(fraxion_details.permit);';
				echo 'jQuery("#cost").html(fraxion_details.cost)},"json");}';
				echo 'function resClosed() {';
				echo '		jQuery.post(';
				echo '			"/index.php?frax_res_list_for_post=' . $article_ID . '"';
				echo '			, function(data) { jQuery("#frax_res_list").html(data);}';
				echo '		);';
				echo '}';
				echo '</script>';
				
				echo '<div id="box" class="mdDialog">';
				echo '<div id="fp_login" title="Fraxion Post Details"></div><br />';
				echo '<button onclick="hm(\'box\');mdClosed();">&nbsp;Close&nbsp;</button>';
				echo '</div>';
				echo '<a href="#" title="Fraxion Payments Edit Post Details" ';
				echo 'onclick="';
				echo 'jQuery(\'#fp_login\').html(\'<iframe></iframe>\');';
				echo 'jQuery(\'#fp_login iframe\').attr(';
				echo '{\'width\':\'100%\',\'height\':\'460\',\'src\':\'' . $this->urls ['editpostinfo'];
				echo '?confid=0&sid=' . $this->site_ID . '&uid_wp=' . $user_ID . '&aid=' . $article_ID;
				echo '&atitle=' . urlencode ( $post_title );
				echo '&cost=\'+jQuery(\'#cost\').html()+\'&lock=\'+jQuery(\'#locked\').html()+\'&permit=\'';
				echo '+jQuery(\'#permit\').html()+\'\'});initmb(); sm(\'box\',700,520);';
				echo '">Change</a>';
				echo '<div id="valpermit"></div>';
				
				echo '<div id="box2" class="mdDialog">';
				echo '<div id="fp_res" title="Fraxion Upload File"></div><br />';
				echo '<button onclick="hm(\'box2\');resClosed();">&nbsp;Close&nbsp;</button>';
				echo '</div>';
				
				echo '</br><strong>Locked File Attachments</strong> ';
				
				echo '<a href="#" title="Fraxion Payments New Locked File" ';
				echo 'onclick="';
				echo 	'jQuery(\'#fp_res\').html(\'<iframe></iframe>\');';
				echo 	'jQuery(\'#fp_res iframe\').attr(';
				echo 	'{\'width\':\'80%\',\'height\':\'400\',\'src\':\'' . get_option ( 'siteurl' ) . '/index.php?frax_upload_resource_form=1&forPostId=' . $article_ID . '\'';
				echo 	'});initmb(); sm(\'box2\',600,520);';
				echo '">Upload File</a></br></br>';
				
				echo '<div id="frax_res_list">';
				echo $this->resourceController->getResourceListHTML($article_ID);
				echo '</div>';
			}
		} else { // Site not registered
			echo '<span style="color:red;"><a href="' . get_option ( 'siteurl' ) . '/wp-admin/options-general.php?page=fpsiteoptions">Register your Site</a></span>';
		}
	} // end admin_PostPanel
	
	private function echoFraxionDetails($statusMessage, $locked, $fraxions_cost) {
		echo '<div id="fraxion_details">';
		if ($status_message != "") {
			echo $settings ['messages'] [$status_message];
			echo '</div>';
		} else {
			echo 'This Post is <strong>' . ($locked == 'true' ? 'locked' : 'unlocked') . '</strong>&nbsp;|&nbsp;';
			echo 'Price is <strong>' . $fraxions_cost . '</strong> fraxions';
			echo '</div>';
		}
	}
	
	  // -----------------
	public function refreshPostPanel() {
		$locked = 'false';
		$fraxions_cost = '0';
		$frax_dom = $this->getFraxionService ( 'settings', array (
				'sid' => $_POST ['siteID'],
				'aid' => $_POST ['postID'],
				'uid_wp' => $_POST ['userID'] 
		) );
		$reply = $frax_dom->getElementsByTagName ( 'reply' );
		if ($reply->length > 0 && $reply->item ( 0 )->hasAttribute ( 'lock' )) {
			if ($reply->item ( 0 )->getAttribute ( 'lock' ) == 'true') {
				$locked = "true";
				$fraxions_cost = $reply->item ( 0 )->getAttribute ( 'cost' );
			}
		} else {
			$error = $frax_dom->getElementsByTagName ( 'error' );
			if ($error->length > 0) {
				$status_message = $error->item ( 0 )->firstChild->nodeValue;
			} else {
				$status_message = 'noServ';
			}
		}
		$permit = '';
		$frax_dom = $this->getFraxionService ( 'getpermit', array (
				'sid' => $_POST ['siteID'],
				'aid' => $_POST ['postID'],
				'uid_wp' => $_POST ['userID'] 
		) );
		$reply = $frax_dom->getElementsByTagName ( 'reply' );
		if ($reply->length > 0 && $reply->item ( 0 )->nodeValue != '') {
			$permit = $reply->item ( 0 )->nodeValue;
		} else {
			$error = $frax_dom->getElementsByTagName ( 'error' );
			if ($error->length > 0) {
				$status_message = $error->item ( 0 )->firstChild->nodeValue;
			} else {
				$status_message = 'noServ';
			}
		}
		$fraxion_details = array (
				'locked' => $locked,
				'cost' => $fraxions_cost,
				'permit' => $permit 
		);
		return json_encode ( $fraxion_details );
	}
	// -----------------
	public function admin_PostValidatePermit_JS($post_ID) {
		global $user_ID;
		get_currentuserinfo ();
		$this->site_ID = get_option ( 'fraxion_site_id' );
		$article_ID = '';
		$this->fp_post_status = "locked";
		// if($post_ID != '') {
		if ($the_post = wp_is_post_revision ( $post_ID->ID )) {
			$article_ID = $the_post;
		} else {
			$article_ID = $post_ID->ID;
		}
		$settings = json_decode ( str_replace ( '\n', null, file_get_contents ( $this->plugins_path . 'javascript/settings.json' ) ), TRUE );
		$this->urls = $settings ['urls'];
		echo '<script language="javascript" type="text/javascript">
				jQuery(document).ready( private function () {	
					sid = document.getElementById("site_ID").value;
					aid =document.getElementById("article_ID").value;
					uid_wp = document.getElementById("user_ID").value;
					permit = document.getElementById("permit").value;
					jQuery(\'<iframe id="fraxion_frame" width="100" height="20" src="' . $this->urls ['valpermit'] . '?confid=0&sid=\' + sid + \'&aid=\' + aid + \'&uid_wp=' . $user_ID . '&permit=\' + permit +\'">iFrame goes here</iframe>\').appendTo(\'#valpermit\');
					//setTimeout(\'var content = frames["fraxion_frame"];jQuery("#valpermit").prepend("Hello World");\',1000);
					}
					);
				</script>';
		// }
	}
	// -----------------
	public function admin_PostValidatePermit() {
		$frax_dom = $this->getFraxionService ( 'valpermit', array (
				'sid' => $_POST ['siteID'],
				'aid' => $_POST ['postID'],
				'uid_wp' => $_POST ['userID'],
				'permit' => $_POST ['permit'] 
		) );
		$reply = $frax_dom->getElementsByTagName ( 'reply' );
		if ($reply->length > 0) {
			$status_message = serialize ( $reply );
		} else {
			$status_message = serialize ( $frax_dom->getElementsByTagName ( 'error' ) );
		}
		return $status_message;
	}
	// -----------------
	public function admin_PostSave($post_id) {
		if (isset ( $_POST ['user_ID'] ) && $_POST ['user_ID'] != '' && isset ( $_POST ['site_ID'] ) && $_POST ['site_ID'] != '') {
			$frax_dom = $this->getFraxionService ( 'setartdata', array (
					'sid' => $_POST ['site_ID'],
					'aid' => $_POST ['article_ID'],
					'atitle' => $_POST ['post_title'],
					'cost' => $_POST ['fraxions_cost'],
					'uid_wp' => $_POST ['user_ID'],
					'permit' => $_POST ['permit'],
					'lock' => ($_POST ['fraxion_lock'] == 'lock' ? 'true' : 'false') 
			) );
			if ($reply->length > 0) {
				$status_message = serialize ( $reply );
			} else {
				$status_message = serialize ( $frax_dom->getElementsByTagName ( 'error' ) );
			}
		}
	}
	// -----------------
	public function checkPostDetails($site_ID, $article_ID, $user_ID) {
		$status_message = '';
		$url = '';
		$cost = 0;
		$this->fp_post_status = "locked";
		$frax_dom = $this->getFraxionService ( 'status', array (
				'sid' => $site_ID,
				'aid' => $article_ID,
				'uid_wp' => $user_ID 
		) );
		$reply = $frax_dom->getElementsByTagName ( 'reply' );
		if ($reply->length > 0) {
			if ($reply->item ( 0 )->hasAttribute ( 'lock' ) && $reply->item ( 0 )->getAttribute ( 'lock' ) == 'true') {
				$this->fp_post_status = "locked";
				$cost = $reply->item ( 0 )->getAttribute ( 'cost' );
			} else {
				$this->fp_post_status = 'unlocked';
				$cost = 0;
			}
		} else {
			$error = $frax_dom->getElementsByTagName ( 'error' );
			if ($error->length > 0) {
				$status_message = str_replace ( '{error}', $error->item ( 0 )->firstChild->nodeValue, $status_messages ['error'] );
			} else {
				$status_message = $status_messages ['noServ'];
			}
		}
		return array (
				$this->fp_post_status,
				$cost 
		);
	}
	// -----------------
	private static function setDBDetails() {
		global $table_prefix;
		if (! defined ( DB_NAME )) {
			$config = file_get_contents ( '../../../wp-config.php' );
			$db_name_start = strpos ( $config, 'define(\'DB_NAME\',' );
			$db_name_end = strpos ( $config, ';', $db_name_start );
			$db_name_define = substr ( $config, $db_name_start, $db_name_end - $db_name_start + 1 );
			eval ( $db_name_define );
			$db_user_start = strpos ( $config, 'define(\'DB_USER\',' );
			$db_user_end = strpos ( $config, ';', $db_user_start );
			$db_user_define = substr ( $config, $db_user_start, $db_user_end - $db_user_start + 1 );
			eval ( $db_user_define );
			$db_password_start = strpos ( $config, 'define(\'DB_PASSWORD\',' );
			$db_password_end = strpos ( $config, ';', $db_password_start );
			$db_password_define = substr ( $config, $db_password_start, $db_password_end - $db_password_start + 1 );
			eval ( $db_password_define );
			$db_host_start = strpos ( $config, 'define(\'DB_HOST\',' );
			$db_host_end = strpos ( $config, ';', $db_host_start );
			$db_host_define = substr ( $config, $db_host_start, $db_host_end - $db_host_start + 1 );
			eval ( $db_host_define );
			$db_table_start = strpos ( $config, '$table_prefix' );
			$db_table_end = strpos ( $config, ';', $db_table_start );
			$db_table_define = substr ( $config, $db_table_start, $db_table_end - $db_table_start + 1 );
			eval ( $db_table_define );
		}
		return true;
	}
	// -----------------
	public function setSiteID($confirmurl) {
		global $table_prefix;
		$mu_site = false;
		$blog_id = 0;
		$table_blog_id = '';
		// / get site_ID
		$cFraxion = curl_init ();
		curl_setopt ( $cFraxion, CURLOPT_URL, $confirmurl );
		curl_setopt ( $cFraxion, CURLOPT_RETURNTRANSFER, true );
		$site_ID_full = curl_exec ( $cFraxion );
		curl_close ( $cFraxion );
		if (substr ( $site_ID_full, 0, 2 ) == 'ok') {
			if (strpos ( $site_ID_full, ',' ) > 0 && strpos ( $site_ID_full, ',0' ) === false) {
				$mu_site = true;
				$site_and_blog_string = substr ( $site_ID_full, 2 );
				$site_and_blog_array = explode ( ',', $site_and_blog_string );
				$site_ID = $site_and_blog_array [0];
				$blog_id = $site_and_blog_array [1];
				$table_blog_id = $blog_id . '_';
			} else {
				$site_ID = substr ( $site_ID_full, 2 );
			}
			// self::setDBDetails();
			$db_conn = @mysql_connect ( DB_HOST, DB_USER, DB_PASSWORD );
			$db_db = @mysql_select_db ( DB_NAME );
			// look for blog_id
			$option_present_result = mysql_query ( 'SELECT Count(*) FROM ' . $table_prefix . $table_blog_id . 'options WHERE option_name = "fraxion_site_id"' );
			if (@mysql_result ( $option_present_result, 0, 0 ) > 0) {
				$option_result = @mysql_query ( 'UPDATE ' . $table_prefix . $table_blog_id . 'options SET option_value = "' . $site_ID . '" WHERE option_name = "fraxion_site_id"' );
			} else {
				$option_result = @mysql_query ( 'INSERT INTO ' . $table_prefix . $table_blog_id . 'options (option_name,option_value) Values("fraxion_site_id","' . $site_ID . '")' );
			}
			$message = $site_ID;
		} else {
			$message = $site_ID_full;
		}
		return 'Your site has been registered!<br /><br />Site ID: ' . $message . ' has been inserted!';
	}
} // End class FraxionPaymentsOld
?>