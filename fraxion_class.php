<?php
// Version: 0.4.9

class FraxionPayments {
	public static $version = "0.4.9";
	public static $site_ID;
	public static $urls;
	public static $fp_post_status;
	public static $the_tag = '[frax09alpha]';
	public static $site_url;
	////////////
	public static function getFraxionService($uriID='',$params=array()) {
		if(!($fp_url = get_option('fraxion_url'))) { $fp_url = "http://www.fraxionpayments.com/";};
		$uris = json_decode(str_replace('\n', null, file_get_contents('/wp-content/plugins/fraxion/uris_service.json')), TRUE);
		$url = $fp_url . $uriID;
		$cFraxion = curl_init();
		curl_setopt($cFraxion, CURLOPT_URL, $url . '?confid=0&' . http_build_query($params));
		curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
		$frax_doc = curl_exec($cFraxion);
		curl_close($cFraxion);
		if(strpos($frax_doc,'xml') === false) {
			$frax_reply = $frax_doc;}
		else {
			$frax_reply = DOMDocument::loadXML($frax_doc);}
		return $frax_reply;
		}
	//////////////////
	private static function convert_smart_quotes($string) {
		$search = array(chr(145),chr(146),chr(147),chr(148),chr(151));
		$replace = array("'","'",'"','"','-');
		return str_replace($search, $replace, $string);
		}
	//////////////////////////////////
	public static function checkStatus($the_content) {
			self::$site_url = get_bloginfo('wpurl');
			$fp_content = convert_chars($the_content);
			$fp_content = strip_shortcodes($the_content);
			$fp_content = self::convert_smart_quotes($fp_content);
			self::$fp_post_status = 'unlocked';
			$status_message = '';
			$fraxion_content = '';
			$tag_position = strpos($fp_content,self::$the_tag);
			if($tag_position !== false && get_option('fraxion_site_id') != false) {
				global $user_ID;
				get_currentuserinfo();
				$article_ID = get_the_ID();
				$action = '';
				$settings = json_decode(str_replace('\n', null, file_get_contents('wp-content/plugins/fraxion/settings.json')), TRUE);
				$actions = $settings['actions'];
				$status_message = '';
				$status_messages = $settings['messages'];
				self::$urls = $settings['urls'];
				$params = array('site_ID' => self::$site_ID, 'article_ID' => $article_ID, 'user_ID' => $user_ID, 'user_Name' => get_usermeta($user_ID, 'nickname'));
				//$params = array('confid' => 0, 'sid' => self::$site_ID, 'aid' => $article_ID, 'uid_wp' => $user_ID, 'user_Name' => get_usermeta($user_ID, 'nickname'));
				//self::getFraxionService('stat',$params)
				$cFraxion = curl_init();
				curl_setopt($cFraxion, CURLOPT_URL, self::$urls['status'].'?confid=0&sid=' . self::$site_ID . '&aid=' . $article_ID . '&' . ($user_ID==''?'uid_none=true':'uid_wp=' . $user_ID));
				curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
				$frax_doc = curl_exec($cFraxion);
				curl_close($cFraxion);
				if(strpos($frax_doc,'xml') === false) {
					$status_message = $frax_doc;
					$action = '';
					}
				else {
					$frax_dom = DOMDocument::loadXML($frax_doc);
					$reply = $frax_dom->getElementsByTagName('reply');
					if($reply->length > 0) {
						if($reply->item(0)->hasAttribute('lock') && $reply->item(0)->getAttribute('lock') == 'true') {
							self::$fp_post_status = "locked";
							if($user_ID == '') {
								$action = $actions['loginWP'];
								$status_message = $status_messages['loginWP'];}
							elseif(!$reply->item(0)->hasAttribute('userCode') || $reply->item(0)->getAttribute('userCode') == '') {
								$action = str_replace('{url_connectacct}', self::$urls['connectacct'],str_replace(array('{site_ID}','{article_ID}','{user_ID}'),$params,$actions['connectFP']));
								$status_message = str_replace('{user_Name}', $params['user_Name'], $status_messages['connectFP']);}
							elseif($reply->item(0)->hasAttribute('userCode') && $reply->item(0)->getAttribute('userCode') != '') {
								if($reply->item(0)->getAttribute('fraxions') >= $reply->item(0)->getAttribute('cost')) {
									$action = str_replace(array('{url_unlock}','{url_purchase}'),array(self::$urls['unlock'],self::$urls['purchase']),str_replace(array('{site_ID}','{article_ID}','{user_ID}','{user_Name}'),$params,$actions['unlock']));
									$action .= str_replace(array('{url_unlock}','{url_purchase}'),array(self::$urls['unlock'],self::$urls['purchase']),str_replace(array('{site_ID}','{article_ID}','{user_ID}','{user_Name}'),$params,$actions['purchaseFrax']));}
								else {
									$action = str_replace(array('{url_unlock}','{url_purchase}'),array(self::$urls['unlock'],self::$urls['purchase']),str_replace(array('{site_ID}','{article_ID}','{user_ID}','{user_Name}'),$params,$actions['purchaseFrax']));}
								$status_message = str_replace(array('{fraxions}','{cost}'), array($reply->item(0)->getAttribute('fraxions'), $reply->item(0)->getAttribute('cost')), str_replace('{user_Name}', $params['user_Name'], $status_messages['unlock']));}
							else {
								$action = '';
								$status_message = str_replace('{error}', htmlentities($frax_doc), $status_messages['error']);}
							}
						else {
							$fraxion_content = str_replace(self::$the_tag,'',$fp_content);}
						}
					else {
						$error = $frax_dom->getElementsByTagName('error');
						if($error->length > 0) {
							$status_message = str_replace('{error}', $error->item(0)->firstChild->nodeValue, $status_messages['error']);}
						else {
							$status_message = $status_messages['noComm'];}
						}
					}
				}
			else {
				$fraxion_content = $fp_content;}
			if($status_message != '') {
				$fraxion_content = DOMDocument::loadHTML('<?xml version="1.0" encoding="UTF-16"?>' . substr($fp_content,0,$tag_position) . ' .....');
				$fraxion_content = $fraxion_content->saveHTML();
				$ms_chars = array('&acirc;&#128;&#156;','&acirc;&#128;&#157;','&acirc;&#128;&#153;');
				$real_chars = array('"','"',"'");
				$fraxion_content = str_replace($ms_chars,$real_chars,$fraxion_content);
				$fraxion_content_full = "<div id='fraxion_post_content_" . get_the_ID() . "'><p>$fraxion_content</p>" . self::showBanner($status_message,$action) . '</div>';
				echo $fraxion_content_full;}
			else {
				echo $fraxion_content;}
		}
	/////
		public static function showBanner($status_message,$action,$debug_message='') {
			$banner = file_get_contents(self::$site_url . '/wp-content/plugins/fraxion/fraxion_banner.html');
			$banner = str_replace('{action}',$action,$banner);
			$banner = str_replace('{site_url}',self::$site_url,$banner);
			$banner = str_replace('{postID}',get_the_ID(),$banner);
			$banner = str_replace('{status_message}',$status_message,$banner);
			$banner = str_replace('{debug_message}',$debug_message,$banner);
			$banner = str_replace('{version}',self::$version,$banner);
			return $banner;
		}
	///////
		public static function fraxion_js() {
			echo file_get_contents('wp-content/plugins/fraxion/fraxion.js');
		}
	///////
		public static function fraxion_css() {
			echo file_get_contents('wp-content/plugins/fraxion/fraxion_banner.css');
		}
	//////
		public static function fraxion_respond() {
			echo '<script language="javascript" type="text/javascript">showRespond("' . self::$fp_post_status . '");</script>';
		}
	///////
	/////// ADMIN ///////////
	////////
		public static function checkSiteStatus() {
			$site_status = false;
			$cFraxion = curl_init();
			curl_setopt($cFraxion, CURLOPT_URL, self::$urls['sitestatus'].'?burl=' . urlencode(get_option('home')));
			curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
			$frax_doc = curl_exec($cFraxion);
			curl_close($cFraxion);
			$frax_dom = DOMDocument::loadXML($frax_doc);
			$reply = $frax_dom->getElementsByTagName('reply');
			$site_status = $reply->item(0)->getAttribute('status');
			return $site_status;
		}
	///////
		public static function admin_js() {
			echo file_get_contents('../wp-content/plugins/fraxion/fraxion_admin.js');
		}
	///////
		public static function admin_css() {
			//echo '<link type="text/css" href="http://jqueryui.com/latest/themes/base/ui.all.css" rel="stylesheet" />';
			echo '<link type="text/css" href="../wp-content/plugins/fraxion/css/smoothness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />';
		}
	///////
		public static function admin_TagButton() {
			echo '<script language="javascript" type="text/javascript">jQuery(document).ready(function() {if(jQuery("#post").html()!="") {jQuery("#ed_toolbar").append("<input type=\'button\' class=\'ed_button\' onclick=\'fppos=0;contents=\"\";fppos=document.getElementById(\"content\").selectionStart;contents=getElementById(\"content\").value;getElementById(\"content\").value=contents.substring(0,fppos)+\"' . self::$the_tag . '\"+contents.substring(fppos);\' title=\'Insert Fraxion lock tag\' value=\'fraxion\' />");}});</script>';
			}
	///////
		public static function admin_Menu() {
			  add_options_page('Fraxion Settings', 'Fraxion Settings', 'administrator', 'fpsiteoptions', array('FraxionPayments','admin_Settings'));
			}
	////////
		public static function admin_Settings() {
			global $user_ID;
			get_currentuserinfo();
			$settings = json_decode(str_replace('\n', null, file_get_contents('../wp-content/plugins/fraxion/settings.json')), TRUE);
			$actions = $settings['actions'];
			$status_message = '';
			$status_messages = $settings['messages'];
			self::$urls = $settings['urls'];
			$admin_site_settings_panel = '<div class="wrap">';
			$admin_site_settings_panel .= '<h3>Fraxion Settings</h3>';
			if(self::checkSiteStatus()!='none') {
			if($_GET['sid']) {
				update_option('fraxion_site_id',$_GET['sid'] );
				$admin_site_settings_panel .= '<div class="updated"><p><strong>Fraxion Settings Saved :)</strong></p></div>';}
			if( $_POST['fraxion_settings_update'] == 'Y' ) {
				update_option('fraxion_site_id',$_POST['fraxion_site_id'] );
				$admin_site_settings_panel .= '<div class="updated"><p><strong>Fraxion Settings Saved :)</strong></p></div>';}
				$settings = json_decode(str_replace('\n', null, file_get_contents('../wp-content/plugins/fraxion/settings.json')), TRUE);
				$admin_site_settings_panel .= 'Fraxion Payment Admin - <a href="'. self::$urls['admin'] . '?returl=http' . ($_SERVER['HTTPS']?'s':null) . urlencode('://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) . '&siteurl=' . urlencode(get_option('home')) . '&blogname=' . urlencode(get_option('blogname')) .  '&sid=' . get_option('fraxion_site_id') .  '&uid_wp=' . $user_ID . '&confid=0">Click Here</a><br />';
				$admin_site_settings_panel .= '<hr />';
				$admin_site_settings_panel .= '<form name="form1" method="post" action=""><input type="hidden" name="fraxion_settings_update" value="Y" />
																<!--Site ID: <input type="text" name="fraxion_site_id" value="' . get_option('fraxion_site_id') . '" size="20" /></p>-->
																<p class="submit"><!-- input type="submit" name="Submit" value="Update Fraxion Settings" / --></p>
																</form><hr />';			
				}
			else {
				$admin_site_settings_panel .= 'Register your site with Fraxion Payments - <a href="'. self::$urls['register'] . '?uid_wp=' . $user_ID . '&btitle=' . urlencode(get_option('blogname')) .'&burl=' . urlencode(get_option('home')) . '&returl=http' . ($_SERVER['HTTPS']?'s':null) . urlencode('://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) . '&vurl=' . urlencode(get_option('home')) . '/wp-content/plugins/fraxion/set_site_id.php">Click Here</a><br />';
				}
			$admin_site_settings_panel .= '</div>';
			echo $admin_site_settings_panel;
			}
	////////
		public static function admin_Post() {
			add_meta_box('frax_post_admin','Fraxion Payments',array('FraxionPayments','admin_PostPanel'),'post','side','high');
			add_meta_box('frax_post_admin','Fraxion Payments',array('FraxionPayments','admin_PostPanel'),'page','side','high');
			}
	///////
		public static function admin_PostPanel($post_ID) {
			$fraxions_cost = 0;
			$status_message;
			global $user_ID;
			get_currentuserinfo();
			$settings = json_decode(str_replace('\n', null, file_get_contents('../wp-content/plugins/fraxion/settings.json')), TRUE);
			self::$urls = $settings['urls'];
			self::$site_ID = get_option('fraxion_site_id');
			if(self::$site_ID != '') {
				$article_ID = '';
				$locked = 'false';
				if($post_ID != '') {
					if ( $the_post = wp_is_post_revision($post_ID->ID) ) { $article_ID = $the_post; }
					else { $article_ID = $post_ID->ID; }
					$post_title = $post_ID->post_title;
					//echo 'art ID: ' . $article_ID;
					$cFraxion = curl_init();
					curl_setopt($cFraxion, CURLOPT_URL, self::$urls['settings'].'?confid=0&sid=' . self::$site_ID . '&aid=' . $article_ID . '&uid_wp=' . $user_ID);
					curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
					$frax_doc = curl_exec($cFraxion);
					curl_close($cFraxion);
					if(strpos($frax_doc,'xml')) {
						$frax_dom = DOMDocument::loadXML($frax_doc);
						$reply = $frax_dom->getElementsByTagName('reply');
						if($reply->length > 0) {
						if($reply->item(0)->hasAttribute('idcon') && $reply->item(0)->getAttribute('idcon') == 'false') {
							$status_message = 'Please Connect to Fraxion Payments to edit lock details';
							}
						elseif($reply->item(0)->hasAttribute('lock') && $reply->item(0)->getAttribute('lock') == 'true') {
								$locked = "true";
								$fraxions_cost = $reply->item(0)->getAttribute('cost');
								}
							}
						else {
							$error = $frax_dom->getElementsByTagName('error');
							if($error->length > 0) {
								$status_message = $error->item(0)->firstChild->nodeValue;}
							else {
								$status_message = 'noComm';
								} // end if has 'lock'
							} // end if reply > 0
						}
					}
					$permit = 'blank';
					$cFraxion = curl_init();
					curl_setopt($cFraxion, CURLOPT_URL, self::$urls['getpermit'].'?confid=0&sid=' . self::$site_ID . '&aid=' . $article_ID . '&' . ($user_ID==''?'uid_none=true':'uid_wp=' . $user_ID));
					curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
					$frax_doc = curl_exec($cFraxion);
					curl_close($cFraxion);
					if(strpos($frax_doc,'xml')) {
						$frax_dom = DOMDocument::loadXML($frax_doc);
						$reply = $frax_dom->getElementsByTagName('reply');
						if($reply->length > 0 && $reply->item(0)->nodeValue != '') {
								$permit = $reply->item(0)->nodeValue;}
						else {
							$error = $frax_dom->getElementsByTagName('error');
							if($error->length > 0) {
								$status_message = $error->item(0)->firstChild->nodeValue;}
							else {
								$status_message = 'Fraxion Server Error!!!';}
							}
						}
				echo "<div id='sid' style='display:none;'>" . self::$site_ID . "</div><div id='uid_wp' style='display:none;'>$user_ID</div><div id='aid' style='display:none'>$article_ID</div><div id='locked' style='display:none;'>$locked</div><div id='cost' style='display:none;'>$fraxions_cost</div><div id='permit' style='display:none;'>$permit</div>";
				echo '<div id="fraxion_details">';
				if($status_message != "") {
					echo $status_message;
					$actions = $settings['actions'];
					$params = array('site_ID' => self::$site_ID, 'article_ID' => $article_ID, 'user_ID' => $user_ID, 'user_Name' => get_usermeta($user_ID, 'nickname'));
					echo str_replace('{url_connectacct}', self::$urls['connectacct'],str_replace(array('{site_ID}','{article_ID}','{user_ID}'),$params,$actions['connectFP']));
					echo '</div>';
					}
				else {
					echo 'This Post is <strong>' . ($locked=='true'?'locked':'unlocked') . '</strong>&nbsp;|&nbsp;';
					echo 'Price is <strong>' . $fraxions_cost . '</strong> fraxions';
					echo '</div>';
					echo '<div id="fp_login" title="Fraxion Post Details"></div>';
					echo '<script language="javascript" type="text/javascript">
							var doClose = function() { jQuery(\'#fp_login\').dialog(\'close\');};
							var dialogOpts = {
									modal: true,
									width: 480,
									height: 540,
									autoOpen: false,
									buttons: { "X Close": doClose },
									close: function() { jQuery.post("../wp-content/plugins/fraxion/fraxion_server.php",{"action":"refreshPostPanel","siteID":"'.self::$site_ID.'","postID":"'.$article_ID.'","userID":"'.$user_ID.'"},function(fraxion_details) { jQuery("#fraxion_details").html("This Post is <strong>"+(fraxion_details.locked=="true"?"locked":"unlocked")+"</strong>&nbsp;|&nbsp;Price is <strong>"+fraxion_details.cost+"</strong> fraxions");jQuery("#locked").html(fraxion_details.locked);jQuery("#permit").html(fraxion_details.permit);jQuery("#cost").html(fraxion_details.cost)},"json");}
									};
									jQuery(\'#fp_login\').dialog(dialogOpts);</script>';
					echo '<a href="#" title="Fraxion Payments Edit Post Details" onclick="jQuery(\'#fp_login\').html(\'<iframe></iframe>\').dialog(\'open\');jQuery(\'#fp_login iframe\').attr({\'width\':\'100%\',\'height\':\'100%\',\'src\':\'' . self::$urls['editpostinfo'] . '?confid=0&sid='.self::$site_ID.'&uid_wp='.$user_ID.'&aid='.$article_ID.'&atitle=' . urlencode($post_title) . '&cost=\'+jQuery(\'#cost\').html()+\'&lock=\'+jQuery(\'#locked\').html()+\'&permit=\'+jQuery(\'#permit\').html()+\'\'});">Change</a>';
					echo '<div id="valpermit"></div>';
					}
				}
			else {
				//echo '<span style="color:red;">Site not registered!!!</span>';
				echo '<span style="color:red;"><a href="'. self::$urls['register'] . '?uid_wp=' . $user_ID . '&btitle=' . urlencode(get_option('blogname')) .'&burl=' . urlencode(get_option('home')) . '&returl=http' . ($_SERVER['HTTPS']?'s':null) . urlencode('://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) . '&vurl=' . urlencode(get_option('home')) . '/wp-content/plugins/fraxion/set_site_id.php">Register your Site</a></span>';	
			}
			}
	////////
		public static function refreshPostPanel() {
				$settings = json_decode(str_replace('\n', null, file_get_contents('settings.json')), TRUE);
				self::$urls = $settings['urls'];
				$locked='false';
				$fraxions_cost = '0';
				$cFraxion = curl_init();
				curl_setopt($cFraxion, CURLOPT_URL, self::$urls['settings'].'?confid=0&sid=' . $_POST['siteID'] . '&aid=' . $_POST['postID'] . '&uid_wp=' . $_POST['userID']);
				curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
				$frax_doc = curl_exec($cFraxion);
				curl_close($cFraxion);
				$frax_dom = DOMDocument::loadXML($frax_doc);
				$reply = $frax_dom->getElementsByTagName('reply');
				if($reply->length > 0 && $reply->item(0)->hasAttribute('lock')) {
					if($reply->item(0)->getAttribute('lock') == 'true') {
						$locked = "true";
						$fraxions_cost = $reply->item(0)->getAttribute('cost');}
					}
				else {
					$error = $frax_dom->getElementsByTagName('error');
					if($error->length > 0) {
						$status_message = $error->item(0)->firstChild->nodeValue;}
					else {
						$status_message = 'noComm';}
					}
	
				$permit = '';
				$cFraxion = curl_init();
				curl_setopt($cFraxion, CURLOPT_URL, self::$urls['getpermit'].'?confid=0&sid=' . $_POST['siteID'] . '&aid=' . $_POST['postID'] . '&uid_wp=' . $_POST['userID']);
				curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
				$frax_doc = curl_exec($cFraxion);
				curl_close($cFraxion);
				$frax_dom = DOMDocument::loadXML($frax_doc);
				$reply = $frax_dom->getElementsByTagName('reply');
				if($reply->length > 0 && $reply->item(0)->nodeValue != '') {
						$permit = $reply->item(0)->nodeValue;}
				else {
					$error = $frax_dom->getElementsByTagName('error');
					if($error->length > 0) {
						$status_message = $error->item(0)->firstChild->nodeValue;}
					else {
						$status_message = 'Fraxion Server Error!!!';}
					}
	
				$fraxion_details = array('locked'=>$locked,'cost'=>$fraxions_cost,'permit'=>$permit);
				return json_encode($fraxion_details);
		}
	//////	/
		public static function admin_PostValidatePermit_JS($post_ID) {
			global $user_ID;
			get_currentuserinfo();
			self::$site_ID = get_option('fraxion_site_id');
			$article_ID = '';
			self::$fp_post_status = 'unlocked';
			//if($post_ID != '') {
			if ( $the_post = wp_is_post_revision($post_ID->ID) ) { $article_ID = $the_post; }
			else { $article_ID = $post_ID->ID; }
			$settings = json_decode(str_replace('\n', null, file_get_contents('../wp-content/plugins/fraxion/settings.json')), TRUE);
			self::$urls = $settings['urls'];
			echo '<script language="javascript" type="text/javascript">
				jQuery(document).ready( public static function () {	
					sid = document.getElementById("site_ID").value;
					aid =document.getElementById("article_ID").value;
					uid_wp = document.getElementById("user_ID").value;
					permit = document.getElementById("permit").value;
					jQuery(\'<iframe id="fraxion_frame" width="100" height="20" src="' . self::$urls['valpermit'] . '?confid=0&sid=\' + sid + \'&aid=\' + aid + \'&uid_wp=' . $user_ID . '&permit=\' + permit +\'">iFrame goes here</iframe>\').appendTo(\'#valpermit\');
					//setTimeout(\'var content = frames["fraxion_frame"];jQuery("#valpermit").prepend("Hello World");\',1000);
					}
					);
				</script>';
			//}
		}
	//////	
		public static function admin_PostValidatePermit() {
			$settings = json_decode(str_replace('\n', null, file_get_contents('settings.json')), TRUE);
			self::$urls = $settings['urls'];
			$cFraxion = curl_init();
			curl_setopt($cFraxion, CURLOPT_URL, self::$urls['valpermit'] . '?confid=0&sid=' . $_POST['siteID'] . '&aid=' . $_POST['postID'] . '&uid_wp=' . $_POST['userID'] . '&permit=' . $_POST['permit']);
			curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
			$frax_doc = curl_exec($cFraxion);
			curl_close($cFraxion);
			if(strpos($frax_doc,'xml')!==false) {
				$frax_dom = DOMDocument::loadXML($frax_doc);
				$reply = $frax_dom->getElementsByTagName('reply');
				$status_message = serialize($reply);}
			else {
				$status_message = $frax_doc;}
			return $status_message;
		}
	/////
		public static function admin_PostSave($post_id) {
			if(isset($_POST['user_ID']) && $_POST['user_ID'] != '' && isset($_POST['site_ID']) && $_POST['site_ID'] != '') {
				$settings = json_decode(str_replace('\n', null, file_get_contents('../wp-content/plugins/fraxion/settings.json')), TRUE);
				self::$urls = $settings['urls'];
				$cFraxion = curl_init();
				curl_setopt($cFraxion, CURLOPT_URL, self::$urls['setartdata']);
				curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
				curl_setopt($cFraxion,CURLOPT_POST, true);
				curl_setopt($cFraxion,CURLOPT_POSTFIELDS,'confid=0&sid=' . $_POST['site_ID'] . '&aid=' . $_POST['article_ID'] . '&atitle=' . $_POST['post_title'] . '&cost=' . $_POST['fraxions_cost'] . '&uid_wp=' . $_POST['user_ID'] . '&permit=' . $_POST['permit'] . '&lock=' . ($_POST['fraxion_lock']=='lock'?'true':'false'));
				$frax_doc = curl_exec($cFraxion);
				curl_close($cFraxion);
				if(strpos($frax_doc,'xml')!==false) {
					$frax_dom = DOMDocument::loadXML($frax_doc);
					$reply = $frax_dom->getElementsByTagName('reply');
					$status_message = serialize($reply);}
				else {
					$status_message = $frax_doc;}
			}
		}
	///////////////
		public static function checkPostDetails($site_ID,$article_ID,$user_ID) {
			$status_message = '';
			$settings = json_decode(str_replace('\n', null, file_get_contents('settings.json')), TRUE);
			$url = '';
			self::$urls = $settings['urls'];
			$cFraxion = curl_init();
			curl_setopt($cFraxion, CURLOPT_URL, self::$urls['status'].'?confid=0&sid=' . $site_ID . '&aid=' . $article_ID . '&uid_wp=' . $user_ID);
			curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
			$frax_doc = curl_exec($cFraxion);
			curl_close($cFraxion);
			if(strpos($frax_doc,'xml') === false) {
				$status_message = $frax_doc;
				$action = '';
				}
			else {
				$frax_dom = DOMDocument::loadXML($frax_doc);
				$reply = $frax_dom->getElementsByTagName('reply');
				if($reply->length > 0) {
					if($reply->item(0)->hasAttribute('lock') && $reply->item(0)->getAttribute('lock') == 'true') {
						self::$fp_post_status = "locked";
						$cost = $reply->item(0)->getAttribute('cost'); }
					else {
						self::$fp_post_status = 'unlocked';
						$cost = 0; }
					}
				else {
					$error = $frax_dom->getElementsByTagName('error');
					if($error->length > 0) {
						$status_message = str_replace('{error}', $error->item(0)->firstChild->nodeValue, $status_messages['error']);}
					else {
						$status_message = $status_messages['noComm'];}
					}
				}
				return array(self::$fp_post_status,$cost);
		}
	//////////////////
		public static function setDBDetails() {
			global $table_prefix;
			if(!defined(DB_NAME)) {
				$config = file_get_contents('../../../wp-config.php');
				$db_name_start = strpos($config,'define(\'DB_NAME\',');
				$db_name_end = strpos($config,';',$db_name_start);
				$db_name_define = substr($config,$db_name_start,$db_name_end-$db_name_start+1);
				eval($db_name_define);
				$db_user_start = strpos($config,'define(\'DB_USER\',');
				$db_user_end = strpos($config,';',$db_user_start);
				$db_user_define = substr($config,$db_user_start,$db_user_end-$db_user_start+1);
				eval($db_user_define);
				$db_password_start = strpos($config,'define(\'DB_PASSWORD\',');
				$db_password_end = strpos($config,';',$db_password_start);
				$db_password_define = substr($config,$db_password_start,$db_password_end-$db_password_start+1);
				eval($db_password_define);
				$db_host_start = strpos($config,'define(\'DB_HOST\',');
				$db_host_end = strpos($config,';',$db_host_start);
				$db_host_define = substr($config,$db_host_start,$db_host_end-$db_host_start+1);
				eval($db_host_define);
				$db_table_start = strpos($config,'$table_prefix');
				$db_table_end = strpos($config,';',$db_table_start);
				$db_table_define = substr($config,$db_table_start,$db_table_end-$db_table_start+1);
				eval($db_table_define);
			}
			return true;
		}
	/////////////////
		public static function setSiteID($confirmurl) {
			global $table_prefix;
			/// get site_ID
			$cFraxion = curl_init();
			curl_setopt($cFraxion, CURLOPT_URL, $confirmurl);
			curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
			$site_ID_full = curl_exec($cFraxion);
			curl_close($cFraxion);
			if(substr($site_ID_full,0,2) == 'ok') {
				$site_ID = substr($site_ID_full,2);
				self::setDBDetails();
				$db_conn = @mysql_connect(DB_HOST,DB_USER,DB_PASSWORD);
				$db_db = @mysql_select_db(DB_NAME);
				$option_present_result = mysql_query('SELECT Count(*) FROM ' . $table_prefix . 'options WHERE option_name = "fraxion_site_id"');
				if(mysql_result($option_present_result,0,0)>0) {
					$option_result = @mysql_query('UPDATE ' . $table_prefix . 'options SET option_value = "' . $site_ID . '" WHERE option_name = "fraxion_site_id"');}
				else {
					$option_result = @mysql_query('INSERT INTO ' . $table_prefix . 'options (option_name,option_value) Values("fraxion_site_id","' . $site_ID . '")');}
				$message = $site_ID;
				}
			else {
				$message = $site_ID_full;
				}
			return '<html><head><title>Fraxion Payments - Site Registration</title></head><body>Your site has been registered!<br /><br />Site ID: ' . $message . ' has been inserted!<br />Please visit your site admin panel!</body></html>';
		}
}
?>