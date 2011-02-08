<?php

class FraxionPayments {
	private $version = '1.3.1';
	public $site_ID;
	public $plugins_path;
	private $urls;
	private $actions;
	private $params;
	private $fp_post_status = 'unlocked';
	private $the_tag = '[frax09alpha]';
	private $site_url;
	private $blog_id = 0;
	private $fut;
	private $requested_url;
	private $bot = false;
	
	//////////////////
	public function __construct() {
		if(function_exists('plugins_url')) {
			$plugins_url_parts = parse_url(plugins_url('fraxion'));
			$this->plugins_path = $_SERVER['DOCUMENT_ROOT'] . $plugins_url_parts['path'] . '/';
			}
		else {
			$this->plugins_path = '';
			}
		if(function_exists('get_option') && get_option('fraxion_site_id') != false) {	
			$this->site_ID = get_option('fraxion_site_id');
			}
		else {
			$this->site_ID = NULL;
			}
		}
	/////////////
	private function writeLOG($message) {
			$fp = fopen($_SERVER['DOCUMENT_ROOT'] . '/log/frax_log.txt', 'a');
			fwrite($fp, $message);
			fclose($fp);
		}
	///////////////
	public function getFraxionService($uriID,$params) {
			if(empty($this->urls)) {
				$settings = json_decode(str_replace('\n', null, file_get_contents($this->plugins_path . 'javascript/settings.json')), TRUE);
				$this->urls = $settings['urls'];
				}
			$cFraxion = curl_init();
			curl_setopt($cFraxion, CURLOPT_URL, $this->urls[$uriID] . '?confid=0&' . http_build_query($params));
			curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
			$frax_doc = curl_exec($cFraxion);
			curl_close($cFraxion);
			if(strpos($frax_doc,'xml') === false) {
				$frax_reply = $frax_doc;
				}
			else {
				$frax_reply = DOMDocument::loadXML($frax_doc);
				}
			//self::writeLOG("Frax reply: " . serialize($frax_doc) . " time: " .date("Y-m-d H:i:s") . "\n");
			return $frax_reply;
			}
	//////////////////
	private function convert_ms_chars($string) {
		$search = array(chr(145),chr(146),chr(147),chr(148),chr(151),'&acirc;&#128;&#156;','&acirc;&#128;&#157;','&acirc;&#128;&#153;','&acirc;&#128;&#147;','&acirc;&euro;&ldquo;','&#8230;');
		$replace = array("'","'",'"','"','-','"','"',"'","-","-",'...');
		return str_replace($search, $replace, $string);
		}
	////////////////////
	private function closetags ($html) {
			#put all opened tags into an array
			preg_match_all ( "#<([a-z]+)( .*)?(?!/)>#iU", $html, $result );
			$openedtags = $result[1];
			#put all closed tags into an array
			preg_match_all ( "#</([a-z]+)>#iU", $html, $result );
			$closedtags = $result[1];
			$len_opened = count ( $openedtags );
			# all tags are closed
			if( count ( $closedtags ) == $len_opened ) {
				return $html;
				}
			$openedtags = array_reverse ( $openedtags );
			# close tags
			for( $i = 0; $i < $len_opened; $i++ ) {
				if ( !in_array ( $openedtags[$i], $closedtags ) ) {
					$html .= "</" . $openedtags[$i] . ">";
					}
				else {
					unset ( $closedtags[array_search ( $openedtags[$i], $closedtags)] );
					}
				}
    		return $html;
    		}
	//////////////////////////////////
	public function checkFUT() {
		//$headers = headers_list();
		//self::writeLOG("User Agent: " . $_SERVER['HTTP_USER_AGENT'] . " time: " . time() . "\n");
		if(!is_robots() && !is_feed() && !is_trackback() && !is_404() && strpos($_SERVER['HTTP_USER_AGENT'],'XML-Sitemaps') === false) {
			if($this->site_ID != NULL) {
				$renew_fut = false;
				if(array_key_exists('fraxion_fut', $_COOKIE)) {
					$this->fut = $_COOKIE['fraxion_fut'];
					$fut_dom = $this->getFraxionService('statfut',array('fut' => $this->fut));
					if(is_object($fut_dom) && $fut_dom->getElementsByTagName('reply') != null) {
						$reply = $fut_dom->getElementsByTagName('reply');
						if($reply->item(0)->hasAttribute('futinvalid') && $reply->item(0)->getAttribute('futinvalid') == 'true') {
							$renew_fut = true;
							}
						else {
							setcookie("fraxion_fut", $_COOKIE['fraxion_fut'], time()+36000,'/');
							}
						}
					else {
						echo $fut_dom;
						}
					}
				else {
						$renew_fut = true;
					}
				if($renew_fut) {
					$fut_dom = $this->getFraxionService('getfut',array('sid' => $this->site_ID));
					if(is_object($fut_dom) && $fut_dom->getElementsByTagName('reply') != null) {
						$reply = $fut_dom->getElementsByTagName('reply');
						$this->fut = $reply->item(0)->nodeValue;
						setcookie("fraxion_fut", $this->fut, time()+36000,'/');
						header('Location: ' . $this->urls['confut'] . '?confid=0&fut=' . $this->fut . '&returl=' . urlencode('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']));
						exit(0);
						}
					else {
						echo $fut_dom;
						}
					}
				}
				//
			} // end if bot
		else {
			// provide content to banner - no banner
			$this->bot = true;
			}
		}
	////// Check Status /////////////
      public function checkStatus($the_content) {
      		$hasSiteID = get_option('fraxion_site_id');
			$this->site_url = get_bloginfo('wpurl');
			$status_message = '';
			$fraxion_content = '';
			$banner_content = array();
			$showFooter = false;
			$showNotLoggedIn = false;
			$showLocked = false;
			//$the_content = convert_chars($the_content);
			$tag_position = strpos($the_content,$this->the_tag);
			$hasTag = ($tag_position===false?false:true);
			$isLocked = false;
			if($hasTag && $hasSiteID != false) {
				$article_ID = get_the_ID();
				$settings = json_decode(str_replace('\n', null, file_get_contents($this->plugins_path . 'javascript/settings.json')), TRUE);
				$this->actions = $settings['actions'];
				$status_messages = $settings['messages'];
				$this->urls = $settings['urls'];
				$protocol = (stripos($_SERVER['SERVER_PROTOCOL'],'https')!==false?'https://':'http://');
				$this->requested_url =  $protocol . $_SERVER['SERVER_NAME'] . urldecode($_SERVER['REQUEST_URI']);
				$this->params = array('site_ID' => $this->site_ID, 'article_ID' => $article_ID, 'fut' => $this->fut, 'returl' => urlencode($this->requested_url));				
				$frax_dom = $this->getFraxionService('statfut',array('aid' => $article_ID, 'fut' => $this->fut));
				$reply = $frax_dom->getElementsByTagName('reply');					
				if($reply->length > 0) { // not error
					//if($reply->item(0)->hasAttribute('futinvalid') && $reply->item(0)->getAttribute('futinvalid') == 'true') {
					if($reply->item(0)->hasAttribute('lock') && $reply->item(0)->getAttribute('lock') == 'true') {
						$isLocked = true;
						$this->fp_post_status = "locked";
						if($reply->item(0)->hasAttribute('isLoggedIn') && $reply->item(0)->getAttribute('isLoggedIn') == 'true') { // connected to FP
							$showLocked = true;
							}
						else { // not logged-in show login and register
							$showNotLoggedIn = true;
							}
						}
					else { // not locked
						$this->fp_post_status = "unlocked";
						if($reply->item(0)->hasAttribute('isFraxioned') && $reply->item(0)->getAttribute('isFraxioned') == 'true') {
							$showFooter = true;
							}
						}
					}						
				else { // not reply hence error
					$error = $frax_dom->getElementsByTagName('error');
					if($error->length > 0) {
						$banner_content['status_message'] = str_replace('{error}', $error->item(0)->firstChild->nodeValue, $status_messages['error']);}
					else {
						$banner_content['status_message'] = $status_messages['noComm'];
						}
					}
				}
			if($hasTag && $showNotLoggedIn) { // not logged-in	
				$banner_content['action1'] = str_replace(array('{url_purchase}', '{class}', '{cost}', '{money}'),
																			array($this->urls['purchase'], 'disabled', $reply->item(0)->getAttribute('cost'), $this->getMoneyCost($reply->item(0)->getAttribute('cost'))),
																			$this->actions['unlockDisabled']);
				$banner_content['action2'] = '';
				$banner_content['action3'] = str_replace(array('{loginFP}', '{class}'), array($this->urls['loginFP'], 'recommended'),
															str_replace(array('{site_ID}','','{fut}','{returl}'),$this->params,$this->actions['loginFP']));
				$banner_content['action4'] = str_replace(array('{pluginurl}','{url_regacct}','{returl}', '{class}'), array(plugins_url('fraxion'), $this->urls['regacct'],urlencode($this->requested_url), ''), $this->actions['regacct']);
				$banner_content['message1'] = str_replace('{user_Name}', $this->params['user_Name'], $status_messages['connectFP']);																		
				$banner_content['message2'] = '';																		
				$banner_content['message3'] = str_replace(array('{forgotpswd}','{returl}'),array($this->urls['forgotpswd'],$this->params['returl']),$this->actions['forgotpswd']);																		
				$banner_content['message4'] = str_replace(array('{whatFrax}'),array($status_messages['whatFrax']),$this->actions['whatFrax']);																		
				$fraxion_content = $this->closetags(substr($the_content,0,$tag_position) . ' .....');
				$fraxion_content_full = "<div id='fraxion_post_content_" . get_the_ID() . "'>$fraxion_content" . $this->showBanner('',$banner_content) . '</div>';
				return $fraxion_content_full;
				}
			elseif($hasTag && $showLocked) { // logged-in - enough fraxions	
				if($reply->item(0)->getAttribute('mayunlock') == 'true') {
					$banner_content['action1'] = str_replace(array('{url_unlock}', '{url_purchase}', '{class}', '{cost}', '{money}'),
																				array($this->urls['unlock'],$this->urls['purchase'],'recommended', $reply->item(0)->getAttribute('cost'), $this->getMoneyCost($reply->item(0)->getAttribute('cost'))),
																				str_replace(array('{site_ID}','{article_ID}','{fut}','{returl}'),$this->params,$this->actions['unlock']));
					$banner_content['action2'] .= str_replace(array('{url_unlock}', '{url_purchase}', '{class}'), array($this->urls['unlock'], $this->urls['purchase'], ''), 
																str_replace(array('{site_ID}','{article_ID}','{fut}','{returl}'),$this->params,$this->actions['purchaseFrax']));
					$banner_content['action3'] .= str_replace(array('{logoutFP}', '{class}'), array($this->urls['logoutFP'], ''),
																str_replace(array('{site_ID}', '{article_ID}', '{fut}', '{returl}'),$this->params,$this->actions['logoutFP']));
					$banner_content['action4'] = str_replace(array('{viewaccount}', '{returl}', '{class}'), array($this->urls['viewaccount'], $this->params['returl'], ''),$this->actions['viewAccount']);
					$banner_content['message1'] = str_replace(array('{fraxions}','{cost}'), array($reply->item(0)->getAttribute('fraxions'), $reply->item(0)->getAttribute('cost')), str_replace('{user_Name}', $this->params['user_Name'], $status_messages['unlock']));
					$banner_content['message2'] = '';	
					$banner_content['message3'] = str_replace('{user_email}', $reply->item(0)->getAttribute('email'), $status_messages['logoutFP']);
					$banner_content['message4'] = '';	
					}
				else { // logged-in - not enough fraxions
					$banner_content['action1'] = str_replace(array('{class}', '{cost}', '{money}'),
																			array('disabled', $reply->item(0)->getAttribute('cost'), $this->getMoneyCost($reply->item(0)->getAttribute('cost'))),
																			$this->actions['unlockDisabled']);
					$banner_content['action2'] .= str_replace(array('{url_unlock}', '{url_purchase}', '{class}'),array($this->urls['unlock'], $this->urls['purchase'], 'recommended'), 
																str_replace(array('{site_ID}','{article_ID}','{fut}','{returl}'),$this->params,$this->actions['purchaseFrax']));
					$banner_content['action3'] .= str_replace(array('{logoutFP}', '{class}'), array($this->urls['logoutFP'], ''), 
																str_replace(array('{site_ID}', '{article_ID}', '{fut}', '{returl}'), $this->params, $this->actions['logoutFP']));
					$banner_content['action4'] = str_replace(array('{viewaccount}','{returl}', '{class}'),array($this->urls['viewaccount'], $this->params['returl'], ''),$this->actions['viewAccount']);
					$banner_content['message1'] = $status_messages['needFrax'];	
					$banner_content['message2'] = str_replace(array('{fraxions}','{cost}'), array($reply->item(0)->getAttribute('fraxions'), $reply->item(0)->getAttribute('cost')), str_replace('{user_Name}', $this->params['user_Name'], $status_messages['unlock']));
					$banner_content['message3'] = str_replace('{user_email}', $reply->item(0)->getAttribute('email'), $status_messages['logoutFP']);
					$banner_content['message4'] = '';	
					}
				$fraxion_content = $this->closetags(substr($the_content,0,$tag_position) . ' .....');
				$fraxion_content_full = "<div id='fraxion_post_content_" . get_the_ID() . "'>$fraxion_content" . $this->showBanner('',$banner_content) . '</div>';
				return $fraxion_content_full;
				}
			elseif($showFooter) { // logged-in and unlocked - footer
				$banner_content['action1'] = str_replace('{logoutFP}',$this->urls['logoutFP'],str_replace(array('{site_ID}','{article_ID}','{fut}','{returl}'),$this->params,$this->actions['logoutFP']));
				$banner_content['action2'] = str_replace(array('{viewaccount}','{returl}'),array($this->urls['viewaccount'],$this->params['returl']),$this->actions['viewAccount']);
				$banner_content['action3'] = '';
				$banner_content['action4'] = '';
				$banner_content['message1'] = str_replace('{user_email}', $reply->item(0)->getAttribute('email'), $status_messages['logoutFP']);
				$banner_content['message2'] = '';	
				$banner_content['message3'] = '';
				$banner_content['message4'] = '';	
				return str_replace($this->the_tag,'',$the_content) . $this->showBanner('footer',$banner_content);
				}
			elseif($hasTag && $this->bot) {
				$banner_content['action1'] ='';
				$banner_content['action2'] = '';
				$banner_content['action3'] = str_replace(array('{loginFP}', '{class}'), array($this->urls['loginFP'], 'recommended'),
															str_replace(array('{site_ID}','','{fut}','{returl}'),$this->params,$this->actions['loginFP']));
				$banner_content['action4'] = '';
				$banner_content['message1'] = '';																		
				$banner_content['message2'] = '';																		
				$banner_content['message3'] = str_replace('{user_Name}', $this->params['user_Name'], $status_messages['connectFP']);																		
				$banner_content['message4'] = '';																		
				$fraxion_content = $this->closetags(substr($the_content,0,$tag_position) . ' .....');
				$fraxion_content_full = "<div id='fraxion_post_content_" . get_the_ID() . "'>$fraxion_content" . $this->showBanner('',$banner_content) . '</div>';
				return $fraxion_content_full;
				}
			else { // do nothing
				return $the_content;
				}
		}
	/////
		private function showBanner($which='main',$banner_content=array()) {
			$banner = file_get_contents($this->plugins_path . 'html/' . ($which=='footer'?'fraxion_banner_foot.html':'fraxion_banner.html'));
			$banner = str_replace('{message}', $banner_content['message1'], str_replace('{action1}',$banner_content['action1'],$banner));
			$banner = str_replace('{message}', $banner_content['message2'], str_replace('{action2}',$banner_content['action2'],$banner));
			$banner = str_replace('{message}', $banner_content['message3'], str_replace('{action3}',$banner_content['action3'],$banner));
			$banner = str_replace('{message}', $banner_content['message4'], str_replace('{action4}',$banner_content['action4'],$banner));
			$banner = str_replace('{site_url}',$this->site_url,$banner);
			$banner = str_replace('{postID}',get_the_ID(),$banner);
			$banner = str_replace('{catalogue}',$this->urls['catalogue'],$banner);
			$banner = str_replace('{version}',$this->version,$banner);
			return $banner;
		}
		private function getMoneyCost($fraxions_cost) {
			$money_cost = '';
			if($fraxions_cost == 0) {
				$money_cost = 'free';
				}
			elseif($fraxions_cost < 100) {
				$money_cost = 'about ' . $fraxions_cost . ' cents';
				}
			else {
				$dollars = $fraxions_cost/100;
				$money_cost = 'about $' . number_format($dollars,2);
				}
			return $money_cost;
			}
	///////
		public function fraxion_js() {
			echo file_get_contents($this->plugins_path . 'javascript/fraxion.js');
		}
	///////
		public function fraxion_css() {
			echo str_replace('{pluginspath}', plugins_url('fraxion'), file_get_contents($this->plugins_path . 'css/fraxion_banner.css'));
		}
	//////
		public function fraxion_respond() {
			echo '<script language="javascript" type="text/javascript">var sShowRespond="' . $this->fp_post_status . '";</script>';
		}
	///////
	/////// ADMIN ///////////
	////////
		private function checkSiteStatus() {
			$site_status = 'none';
			$frax_dom = $this->getFraxionService('sitestatus',array('burl' => get_option('home')));
			$reply = $frax_dom->getElementsByTagName('reply');
			$site_status = $reply->item(0)->getAttribute('status');
			return $site_status;
		}
	///////
		public function admin_js() {
			echo file_get_contents($this->plugins_path . 'javascript/fraxion_admin.js');
		}
	///////
		public function admin_css() {
			echo '<link type="text/css" href="' . plugins_url('fraxion') . '/css/smoothness/jquery-ui.custom.css" rel="stylesheet" />';
		}
	///////
		public function admin_TagButton() {
			echo '<script language="javascript" type="text/javascript">jQuery(document).ready(function() {if(jQuery("#post").html()!="") {jQuery("#ed_toolbar").append("<input type=\'button\' class=\'ed_button\' onclick=\'fppos=0;contents=\"\";fppos=document.getElementById(\"content\").selectionStart;contents=getElementById(\"content\").value;getElementById(\"content\").value=contents.substring(0,fppos)+\"' . $this->the_tag . '\"+contents.substring(fppos);\' title=\'Insert Fraxion lock tag\' value=\'fraxion\' />");}});</script>';
			}
	///////
		public function admin_Menu() {
			  add_options_page('Fraxion Settings', 'Fraxion Settings', 'administrator', 'fpsiteoptions', array($this,'admin_Settings'));
			}
	////////
		public function admin_Settings() {
			global $user_ID;
			get_currentuserinfo();
			$settings = json_decode(str_replace('\n', null, file_get_contents($this->plugins_path . 'javascript/settings.json')), TRUE);
			$this->actions = $settings['actions'];
			$status_message = '';
			$status_messages = $settings['messages'];
			$this->urls = $settings['urls'];
			$admin_site_settings_panel = '<div class="wrap">';
			$admin_site_settings_panel .= '<h3>Fraxion Settings</h3>';
			if($this->checkSiteStatus() != 'none') {
				if($_GET['sid']) {
					update_option('fraxion_site_id',$_GET['sid'] );
					$admin_site_settings_panel .= '<div class="updated"><p><strong>Fraxion Settings Saved :)</strong></p></div>';
					}
				if( $_POST['fraxion_settings_update'] == 'Y' ) {
					update_option('fraxion_site_id',$_POST['fraxion_site_id'] );
					$admin_site_settings_panel .= '<div class="updated"><p><strong>Fraxion Settings Saved :)</strong></p></div>';
					}
				$settings = json_decode(str_replace('\n', null, file_get_contents($this->plugins_path . 'javascript/settings.json')), TRUE);
				$admin_site_settings_panel .= 'Fraxion Payment Admin - <a href="'. $this->urls['admin'] . '?returl=http' . ($_SERVER['HTTPS']?'s':null) . urlencode('://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) . '&siteurl=' . urlencode(get_option('home')) . '&blogname=' . urlencode(get_option('blogname')) .  '&sid=' . get_option('fraxion_site_id') .  '&uid_wp=' . $user_ID . '&confid=0">Click Here</a><br />';
				if (function_exists('get_blog_count')) { ///// is MU //////
					$blog_details =  get_active_blog_for_user($user_ID);
					if($blog_details->blog_id == 1) { // is master //
						$admin_site_settings_panel .= $status_messages['admin_mu_base'];
						}
					else {
						$admin_site_settings_panel .= $status_messages['admin_mu_blog'];
							}
					}
				else { /////// not MU /////////
					$admin_site_settings_panel .= $status_messages['admin_single'];								
					}
				$admin_site_settings_panel .= '<hr />';
				}
			else {
				if (function_exists('get_blog_count')) { //// is MU //////
					$blog_count = get_blog_count();
					$admin_site_settings_panel .= '<p>';
					$blog_details =  get_active_blog_for_user($user_ID);
					$admin_site_settings_panel .= 'Register your site with Fraxion Payments - <a href="'. $this->urls['register'] . '?
													blog_id=' . $blog_details->blog_id .
													'&uid_wp=' . $user_ID . '&
													btitle=' . urlencode($blog_details->blogname) .
													'&burl=' . urlencode(get_option('home')) .
													'&base_site_url=' . urlencode(get_blog_option(1,'siteurl')) . 
													'&returl=http' . ($_SERVER['HTTPS']?'s':null) . urlencode('://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) . '?page=fpsiteoptions' .
													'&vurl=' . urlencode(plugins_url('fraxion')) . '/set_site_id.php">Click Here</a><br />';
					if($blog_details->blog_id == 1) { // is master //
						$admin_site_settings_panel .= $status_messages['register_mu_base'];
						}
					else {
						$admin_site_settings_panel .= $status_messages['register_mu_blog'];
						}
					}
				else { ///// not MU ///////
					$admin_site_settings_panel .= 'Register your site with Fraxion Payments - <a href="'. $this->urls['register'] . '?&
													uid_wp=' . $user_ID . 
													'&btitle=' . urlencode(get_option('blogname')) .'&
													burl=' . urlencode(get_option('home')) . 
													'&returl=http' . ($_SERVER['HTTPS']?'s':null) . urlencode('://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) . 
													'&vurl=' . urlencode(plugins_url('fraxion')) . '/set_site_id.php">Click Here</a><br />';
					$admin_site_settings_panel .= $status_messages['register_single'];	
					}
				}
			$admin_site_settings_panel .= '</div>';
			echo $admin_site_settings_panel;
			}
	////////
		public function admin_Post() {
			add_meta_box('frax_post_admin','Fraxion Payments',array($this,'admin_PostPanel'),'post','side','high');
			add_meta_box('frax_post_admin','Fraxion Payments',array($this,'admin_PostPanel'),'page','side','high');
			}
	///////
		public function admin_PostPanel($post_ID) {
			$fraxions_cost = 0;
			$status_message;
			global $user_ID;
			get_currentuserinfo();
			$settings = json_decode(str_replace('\n', null, file_get_contents($this->plugins_path . 'javascript/settings.json')), TRUE);
			if($this->site_ID != NULL) {
				$article_ID = '';
				$locked = 'false';
				if($post_ID != '') {
					if ( $the_post = wp_is_post_revision($post_ID->ID) ) { $article_ID = $the_post; }
					else { $article_ID = $post_ID->ID; }
					$post_title = $post_ID->post_title;
						$frax_dom = $this->getFraxionService('settings',array('sid' => $this->site_ID, 'aid' => $article_ID, 'uid_wp' => $user_ID));
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
					$permit = 'blank';
					$frax_dom = $this->getFraxionService('getpermit',array('sid' => $this->site_ID, 'aid' => $article_ID, ($user_ID==''?'uid_none':'uid_wp') => $user_ID));
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
				echo "<div id='sid' style='display:none;'>" . $this->site_ID . "</div><div id='uid_wp' style='display:none;'>$user_ID</div><div id='aid' style='display:none'>$article_ID</div><div id='locked' style='display:none;'>$locked</div><div id='cost' style='display:none;'>$fraxions_cost</div><div id='permit' style='display:none;'>$permit</div>";
				echo '<div id="fraxion_details">';
				if($status_message != "") {
					echo $status_message;
					$this->actions = $settings['actions'];
					$this->requested_url =  $protocol . $_SERVER['SERVER_NAME'] . urldecode($_SERVER['REQUEST_URI']);				
					$admin_params = array('site_ID' => $this->site_ID, 'pluginurl' => plugins_url('fraxion'), 'article_ID' => $article_ID, 'user_ID' => $user_ID, 'returl' => urlencode('http://' . $this->requested_url));
					echo str_replace('{url_connectacct}', $this->urls['connectacct'],str_replace(array('{site_ID}','{pluginurl}','{article_ID}','{user_ID}','{returl}'),$admin_params,$this->actions['connectFP']));
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
									width: 700,
									height: 580,
									autoOpen: false,
									buttons: { "X Close": doClose },
									close: function() { jQuery.post("' . plugins_url('fraxion') . '/fraxion_server.php",{"action":"refreshPostPanel","siteID":"'. 
									$this->site_ID .'","postID":"'. $article_ID .'","userID":"'. $user_ID .
									'"},function(fraxion_details) { jQuery("#fraxion_details").html("This Post is <strong>"+(fraxion_details.locked=="true"?"locked":"unlocked")+"</strong>&nbsp;|&nbsp;Price is <strong>"+fraxion_details.cost+"</strong> fraxions");jQuery("#locked").html(fraxion_details.locked);jQuery("#permit").html(fraxion_details.permit);jQuery("#cost").html(fraxion_details.cost)},"json");}
									};
									jQuery(\'#fp_login\').dialog(dialogOpts);</script>';
					echo '<a href="#" title="Fraxion Payments Edit Post Details" onclick="jQuery(\'#fp_login\').html(\'<iframe></iframe>\').dialog(\'open\');jQuery(\'#fp_login iframe\').attr({\'width\':\'100%\',\'height\':\'100%\',\'src\':\'' . 
								$this->urls['editpostinfo'] . '?confid=0&sid='.$this->site_ID.'&uid_wp='.$user_ID.'&aid='.$article_ID.'&atitle=' . 
								urlencode($post_title) . '&cost=\'+jQuery(\'#cost\').html()+\'&lock=\'+jQuery(\'#locked\').html()+\'&permit=\'+jQuery(\'#permit\').html()+\'\'});">Change</a>';
					echo '<div id="valpermit"></div>';
					}
				}
			else {
				if(empty($this->urls)) {
					$settings = json_decode(str_replace('\n', null, file_get_contents($this->plugins_path . 'javascript/settings.json')), TRUE);
					$this->urls = $settings['urls'];
				}
				echo '<span style="color:red;"><a href="'. $this->urls['register'] . '?uid_wp=' . $user_ID . '&btitle=' . 
				urlencode(get_option('blogname')) .'&burl=' . urlencode(get_option('home')) . '&site_url=' . urlencode(get_option('home')) . 
				'&returl=http' . ($_SERVER['HTTPS']?'s':null) . urlencode('://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) . 
				'&vurl=' . urlencode(plugins_url('fraxion')) . '/set_site_id.php">Register your Site</a></span>';	
			}
			}
	////////
		public function refreshPostPanel() {
				$locked='false';
				$fraxions_cost = '0';
				$frax_dom = $this->getFraxionService('settings',array('sid' => $_POST['siteID'], 'aid' => $_POST['postID'], 'uid_wp' => $_POST['userID']));
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
				$frax_dom = $this->getFraxionService('getpermit',array('sid' => $_POST['siteID'], 'aid' => $_POST['postID'], 'uid_wp' => $_POST['userID']));				
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
		public function admin_PostValidatePermit_JS($post_ID) {
			global $user_ID;
			get_currentuserinfo();
			$this->site_ID = get_option('fraxion_site_id');
			$article_ID = '';
			$this->fp_post_status = 'unlocked';
			//if($post_ID != '') {
			if ( $the_post = wp_is_post_revision($post_ID->ID) ) { $article_ID = $the_post; }
			else { $article_ID = $post_ID->ID; }
			$settings = json_decode(str_replace('\n', null, file_get_contents($this->plugins_path . 'javascript/settings.json')), TRUE);
			$this->urls = $settings['urls'];
			echo '<script language="javascript" type="text/javascript">
				jQuery(document).ready( private function () {	
					sid = document.getElementById("site_ID").value;
					aid =document.getElementById("article_ID").value;
					uid_wp = document.getElementById("user_ID").value;
					permit = document.getElementById("permit").value;
					jQuery(\'<iframe id="fraxion_frame" width="100" height="20" src="' . $this->urls['valpermit'] . '?confid=0&sid=\' + sid + \'&aid=\' + aid + \'&uid_wp=' . $user_ID . '&permit=\' + permit +\'">iFrame goes here</iframe>\').appendTo(\'#valpermit\');
					//setTimeout(\'var content = frames["fraxion_frame"];jQuery("#valpermit").prepend("Hello World");\',1000);
					}
					);
				</script>';
			//}
		}
	//////	
		public function admin_PostValidatePermit() {
			$frax_dom = $this->getFraxionService('valpermit',array('sid' => $_POST['siteID'], 'aid' => $_POST['postID'], 'uid_wp' => $_POST['userID'], 'permit' => $_POST['permit']));
			$reply = $frax_dom->getElementsByTagName('reply');
			$status_message = serialize($reply);
			return $status_message;
		}
	/////
		public function admin_PostSave($post_id) {
			if(isset($_POST['user_ID']) && $_POST['user_ID'] != '' && isset($_POST['site_ID']) && $_POST['site_ID'] != '') {
				$frax_dom = $this->getFraxionService('setartdata',array('sid' => $_POST['site_ID'], 'aid' => $_POST['article_ID'], 'atitle' => $_POST['post_title'], 'cost' => $_POST['fraxions_cost'], 'uid_wp' => $_POST['user_ID'], 'permit' => $_POST['permit'], 'lock' => ($_POST['fraxion_lock']=='lock'?'true':'false')));
				$reply = $frax_dom->getElementsByTagName('reply');
				$status_message = serialize($reply);//}
				}
		}
	///////////////
		public function checkPostDetails($site_ID,$article_ID,$user_ID) {
			$status_message = '';
			$url = '';			
			$frax_dom = $this->getFraxionService('status',array('sid' => $site_ID, 'aid' => $article_ID, 'uid_wp' => $user_ID));
			$reply = $frax_dom->getElementsByTagName('reply');
			if($reply->length > 0) {
				if($reply->item(0)->hasAttribute('lock') && $reply->item(0)->getAttribute('lock') == 'true') {
					$this->fp_post_status = "locked";
					$cost = $reply->item(0)->getAttribute('cost'); }
				else {
					$this->fp_post_status = 'unlocked';
					$cost = 0; }
				}
			else {
				$error = $frax_dom->getElementsByTagName('error');
				if($error->length > 0) {
					$status_message = str_replace('{error}', $error->item(0)->firstChild->nodeValue, $status_messages['error']);}
				else {
					$status_message = $status_messages['noComm'];}
				}
			return array($this->fp_post_status,$cost);
		}
	//////////////////
		private static function setDBDetails() {
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
			$mu_site = false;
			$blog_id = 0;
			$table_blog_id = '';
			/// get site_ID
			$cFraxion = curl_init();
			curl_setopt($cFraxion, CURLOPT_URL, $confirmurl);
			curl_setopt($cFraxion,CURLOPT_RETURNTRANSFER, true);
			$site_ID_full = curl_exec($cFraxion);
			curl_close($cFraxion);
			if(substr($site_ID_full,0,2) == 'ok') {
				if(strpos($site_ID_full,',') > 0 && strpos($site_ID_full,',0') === false) {
					$mu_site = true;
					$site_and_blog_string = substr($site_ID_full,2);
					$site_and_blog_array = explode(',',$site_and_blog_string);
					$site_ID = $site_and_blog_array[0];
					$blog_id = $site_and_blog_array[1];
					$table_blog_id = $blog_id . '_';
					}
				else {
					$site_ID = substr($site_ID_full,2);
					}
				self::setDBDetails();
				$db_conn = @mysql_connect(DB_HOST,DB_USER,DB_PASSWORD);
				$db_db = @mysql_select_db(DB_NAME);
				// look for blog_id
				$option_present_result = mysql_query('SELECT Count(*) FROM ' . $table_prefix . $table_blog_id . 'options WHERE option_name = "fraxion_site_id"');
				if(mysql_result($option_present_result,0,0)>0) {
					$option_result = @mysql_query('UPDATE ' . $table_prefix . $table_blog_id . 'options SET option_value = "' . $site_ID . '" WHERE option_name = "fraxion_site_id"');}
				else {
					$option_result = @mysql_query('INSERT INTO ' . $table_prefix . $table_blog_id . 'options (option_name,option_value) Values("fraxion_site_id","' . $site_ID . '")');
					}
				$message = $site_ID;
				}
			else {
				$message = $site_ID_full;
				}
			return '<html><head><title>Fraxion Payments - Site Registration</title></head><body>Your site has been registered!<br /><br />Site ID: ' . $message . ' has been inserted!<br />Please visit your site admin panel!</body></html>';
		}
}
?>