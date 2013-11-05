<?php
class FraxionPayments {
	private $fraxionService; // Object to handle requests to the fraxion server.
	private $bannerWriter; // Object to render banner html
	private $urlProvider; // Object to provide URLs to fraxion services
	private $fraxion_site_id; // Id given to this site when registered with Fraxion.
	private $fut; // Fraxion User Token that identifies the current user session to the fraxion user session.
	private $logger = null;
	private $fraxOld;
	private $fp_post_status = "locked";
	private $const_the_tag = '[frax09alpha]'; // The tag in the content where the banner cuts in
	private $const_option_fraxion_site_id = 'fraxion_site_id'; // The database option name for the saved Site Id given at
	                                                           // registration.
	private $const_cookie_name_fraxion_fut = 'fraxion_fut'; // index to the fut value in the cookie.
	private $const_option_fraxion_resf_id = 'fraxion_resf_id'; // postfix for resource folder
	
	/**
	 * Class constructor sets up basic variables.
	 */
	public function __construct(
			FraxionService $fraxionService, 
			FraxionBannerWriter $bannerWriter, 
			FraxionURLProvider $urlProvider, 
			FraxionPaymentsOld $fraxOld) {
		$this->fraxionService = $fraxionService;
		$this->bannerWriter = $bannerWriter;
		$this->urlProvider = $urlProvider;
		$this->fraxOld = $fraxOld;
		$this->logger = FraxionLoggerImpl::getLogger ( "FraxionPayments" );
		$this->logger->writeLOG( '[__construct]');
		self::loadSiteId ();
	} // end __construct
	
	/**
	 * Get the site id from the options table in the DB and load it into $this->fraxion_site_id.
	 */
	private function loadSiteId() {
		if (function_exists ( 'get_option' ) && get_option ( $this->const_option_fraxion_site_id ) != false) {
			$this->fraxion_site_id = get_option ( $this->const_option_fraxion_site_id );
		} else {
			$this->fraxion_site_id = NULL;
		}
	} // end loadSiteId
	
	public function refreshPostPanel() {
		return $this->fraxOld . refreshPostPanel ();
	}
	
	public function fraxion_respond() {
		echo ('
			<script language="javascript" type="text/javascript">
				function showRespond(status) {
					if(status=="locked" && document.getElementById("commentform")) {
						var message = document.getElementById("commentform");
						do message = message.previousSibling;
						while (message && message.nodeType != 1);
						message.innerHTML = "To leave comments please unlock this post.";
						document.getElementById("commentform").innerHTML = "";
					}
				}
				window.onload=function(){showRespond("' . $this->fp_post_status . '");};
			</script>'
		);
	} // end fraxion_respond
	
	/**
	 * The init function calls this at the start of a page.
	 */
	public function checkFUT() {
		$this->logger->writeLOG( '[checkFUT] User Agent: ' . $_SERVER ['HTTP_USER_AGENT'] . ' : fraxion_site_id:' . $this->fraxion_site_id);
		
		
		if (! (is_robots () || is_feed () || is_trackback () || is_404 ()) && strpos ( $_SERVER ['HTTP_USER_AGENT'], 
				'XML-Sitemaps' ) === false) {
			if ($this->fraxion_site_id != NULL) { // Registered site
				$this->logger->writeLOG( '[checkFUT] fraxion_site_id=' . $this->fraxion_site_id);
				$renew_fut = false;
				if (array_key_exists ( $this->const_cookie_name_fraxion_fut, $_COOKIE )) {
					$this->fut = $_COOKIE [$this->const_cookie_name_fraxion_fut];
					$this->logger->writeLOG( 'Cur FUT:' . $this->fut );
						// TODO only test the fut if more than a few seconds has passed
					$fut_dom = $this->fraxionService->get ( $this->urlProvider->getStatFutUrl ( $this->fut ) );
					if ($fut_dom->getElementsByTagName ( 'reply' ) != null && $fut_dom->getElementsByTagName ( 'reply' ) != false) {
						$reply = $fut_dom->getElementsByTagName ( 'reply' );
						if ($reply->item ( 0 ) && $reply->item ( 0 )->hasAttribute ( 'futinvalid' ) && $reply->item ( 
								0 )->getAttribute ( 'futinvalid' ) == 'true') {
							$renew_fut = true; // reply was that fut was invalid and so it needs renewing
						} else { // leave fut in the cookie as is and bump up its live time
							setcookie ( "fraxion_fut", $this->fut, time () + 36000, '/' );
						}
					} else { // No good reply - site down?
						$this->fut = null;
					}
				} else { // no known FUT
					$renew_fut = true;
				}
				if ($renew_fut) { // get a new FUT to put in the cookies for this session, then redirect to confirm the FUT
				                 // via a fraxion session.
					$fut_dom = $this->fraxionService->getNewFUT ( $this->fraxion_site_id );
					$reply = $fut_dom->getElementsByTagName ( 'reply' );
					if ($reply->length > 0) {
						$this->fut = $reply->item ( 0 )->nodeValue; // Actual FUT value is content of reply tag.
						$returnURL = 'http://' . $_SERVER ['SERVER_NAME'] . $_SERVER ['REQUEST_URI']; // Call back to the
						                                                                            // initial request
						$redirectHeader = 'Location: ' . $this->urlProvider->getGetConfirmFutUrl ( $this->fut, 
								$returnURL );
						$this->logger->writeLOG( 'redirectHeader:' . $redirectHeader);
						setcookie ( "fraxion_fut", $this->fut, time () + 36000, '/' );
						header ( $redirectHeader );
						exit ( 0 ); // stop processing and send the redirect
					} else { // No good reply - site down?
						$this->fut = null;
					}
				} // end if $renew_fut
			}
		} else { // Its a bot - no login banner always
			$this->bot = true;
		}
	} // end checkFUT
	
	/**
	 * See if a banner is required, get it from fraxion payments and insert into the content.
	 * $the_content the article content
	 */
	public function push_banner(
			$the_content) {
		$tag_position = strpos ( $the_content, $this->const_the_tag );
		$hasTag = ($tag_position === false ? false : true);
		if ($hasTag) {
			self::loadSiteId ();
			if (! empty ( $this->fraxion_site_id )) {
				return self::push_banner_to_tagged_content ( $the_content, $tag_position );
			} else { // Unregistered site
				$loggedIn = false;
				$frax_dom = $this->fraxionService->get ( $this->urlProvider->getGetFutUrl ( $this->fraxion_site_id ) );
				$reply = $frax_dom->getElementsByTagName ( 'reply' );
				if ($reply->length > 0) { // not error
					if ($reply->item ( 0 )->hasAttribute ( 'isLoggedIn' )) {
						$loggedIn = $reply->item ( 0 )->getAttribute ( 'isLoggedIn' ) == 'true';
					}
				}
				$banner_content = $this->bannerWriter->getSiteNotRegisteredBanner ( $loggedIn );
				return self::insert_banner_at_tag ( $the_content, $tag_position, $banner_content );
			}
		} else {
			$this->fp_post_status = "unlocked";
			return $the_content;
		}
	} // end push_banner
	
	/**
	 * Add a banner to the content, which has a tag at the given position.
	 */
	private function push_banner_to_tagged_content(
			$the_content, 
			$tag_position) {
		$banner_content = "[OOPS]";
		$showAsFooter = false;
		$article_ID = get_the_ID ();
		$frax_dom = $this->fraxionService->get ( $this->urlProvider->getStatFutUrl ( $this->fut, $article_ID ) );
		$reply = $frax_dom->getElementsByTagName ( 'reply' );
		if ($reply->length > 0) { // not error
			$rItem0 = $reply->item ( 0 );
			if ($rItem0->hasAttribute ( 'lock' ) && $rItem0->getAttribute ( 'lock' ) == 'true') {
				// Article is locked for this user
				if ($rItem0->hasAttribute ( 'isLoggedIn' ) && $rItem0->getAttribute ( 'isLoggedIn' ) == 'true') { // User loggedin
					$this->logger->writeLOG( 'push_banner_to_tagged_content show locked and logged in:');
					if ($rItem0->getAttribute ( 'mayunlock' ) == 'true') { // logged-in - locked - enough fraxions
						$banner_content = $this->bannerWriter->getUserLoggedInLockFraxOkBanner ( 
								$this->fraxion_site_id, $this->fut, self::getRequestURL (), $article_ID, 
								$rItem0->getAttribute ( 'cost' ), $rItem0->getAttribute ( 'fraxions' ), 
								$rItem0->getAttribute ( 'email' ) );
					} else { // Not enough fraxions to unlock this
						$banner_content = $this->bannerWriter->getUserLoggedInLockFewFraxBanner ( 
								$this->fraxion_site_id, $this->fut, self::getRequestURL (), $article_ID, 
								$rItem0->getAttribute ( 'cost' ), $rItem0->getAttribute ( 'fraxions' ), 
								$rItem0->getAttribute ( 'email' ) );
					}
				} else { // not logged-in show login and register
					$banner_content = $this->bannerWriter->getUserNotLoggedInBanner ( self::getRequestURL () );
				}
			} else { // Article is not locked
				if ($rItem0->hasAttribute ( 'isFraxioned' ) && $rItem0->getAttribute ( 'isFraxioned' ) == 'true') { // User has
				                                                                                             // properly unlocked
					$showAsFooter = true;
					$this->fp_post_status = "unlocked";
					$this->logger->writeLOG( 'push_banner_to_tagged_content show is fraxioned i.e. logged in and unlocked');
					$banner_content = $this->bannerWriter->getUserLoggedInNotLockedBanner ( $this->fraxion_site_id, 
							$this->fut, self::getRequestURL (), $rItem0->getAttribute ( 'fraxions' ), 
							$rItem0->getAttribute ( 'email' ) );
				} else { // Article is not registered yet
					$userIsLoggedIn = ($rItem0->hasAttribute ( 'isLoggedIn' ) && $rItem0->getAttribute ( 'isLoggedIn' ) == 'true');
					$banner_content = $this->bannerWriter->getArticleNotRegisteredBanner ( $userIsLoggedIn );
				}
			}
		} else { // not reply hence error
			$error = $frax_dom->getElementsByTagName ( 'error' );
			if ($error->length > 0) {
				$errorMessage = $error->item ( 0 )->firstChild->nodeValue;
				if ($errorMessage == 'noServ') {
					$banner_content = $this->bannerWriter->getNoServiceBanner ();
				} else {
					$banner_content = $this->bannerWriter->getServiceErrorBanner ( $errorMessage );
				}
			} else { // got no error information. Lets be a bit generic
				$banner_content = $this->bannerWriter->getNoServiceBanner ();
			}
		}
		if ($showAsFooter) {
			return self::insert_banner_at_foot ( $the_content, $banner_content );
		} else {
			return self::insert_banner_at_tag ( $the_content, $tag_position, $banner_content );
		}
	} // end push_banner_to_tagged_content
	
	/**
	 */
	private function insert_banner_at_tag(
			$the_content, 
			$tag_position, 
			$banner_content) {
		$fraxion_content = self::closetags ( substr ( $the_content, 0, $tag_position ) . "... " . $banner_content );
		return "<div id='fraxion_post_content_" . get_the_ID () . "'>" . $fraxion_content . '</div>';
	}
	
	/**
	 */
	private function insert_banner_at_foot(
			$the_content, 
			$banner_content) {
		$fraxion_content = str_replace ( $this->const_the_tag, '', $the_content );
		return "<div id='fraxion_post_content_" . get_the_ID () . "'>" . $fraxion_content . $banner_content . '</div>';
	}
	
	/**
	 * Find all html tags in the html that have not been properly closed and close them.
	 */
	private static function closetags(
			$html) {
		// put all opened tags into an array
		preg_match_all ( "#<([a-z]+)( .*)?(?!/)>#iU", $html, $result );
		$openedtags = $result [1];
		// put all closed tags into an array
		preg_match_all ( "#</([a-z]+)>#iU", $html, $result );
		$closedtags = $result [1];
		$len_opened = count ( $openedtags );
		if (count ( $closedtags ) == $len_opened) { // all tags are closed
			return $html;
		}
		$openedtags = array_reverse ( $openedtags );
		// close tags
		for($i = 0; $i < $len_opened; $i ++) {
			if (! in_array ( $openedtags [$i], $closedtags ) && strtolower ( $openedtags [$i] ) != 'br') {
				$html .= "</" . $openedtags [$i] . ">";
			} else {
				unset ( $closedtags [array_search ( $openedtags [$i], $closedtags )] );
			}
		}
		return $html;
	} // end closetags
	
	/**
	 * Get the full URL used to access the current page.
	 */
	private function getRequestURL() {
		$protocol = (stripos ( $_SERVER ['SERVER_PROTOCOL'], 'https' ) !== false ? 'https://' : 'http://');
		$port = ($_SERVER ["SERVER_PORT"] == "80" ? '' : ':' . $_SERVER ["SERVER_PORT"]);
		return $protocol . $_SERVER ['SERVER_NAME'] . $port . urldecode ( $_SERVER ['REQUEST_URI'] );
	} // end getRequestURL
	
	/**
	 * Java script to include in page
	 */
	public function fraxion_js() {
		// echo ('<script type="text/javascript" src="/wp-content/plugins/fraxion/javascript/fraxion.js"></script>');
		// echo file_get_contents(PluginsPathImpl::get() . 'javascript/fraxion.js');
		echo ('<script type="text/javascript" src="' . plugins_url ( 'javascript/fraxion.js', __FILE__ ) . '"></script>');
	}
	
	/**
	 * Css to include in page
	 */
	public function fraxion_css() {
		// echo ('<link rel="stylesheet" href="/wp-content/plugins/fraxion/css/fraxion_banner.css" type="text/css" />');
		echo ('<link rel="stylesheet" href="' . plugins_url ( 'css/fraxion_banner.css', __FILE__ ) . '" type="text/css" />');
	}
	
	/**
	 * A function designed to confirm the site ID with the confirmurl and then
	 * insert the value into the database.
	 * The function does not require that
	 * index.php has been called, so it may be called directly.
	 * It updates or inserts the id into the options table where the option name is 'fraxion_site_id'.
	 */
	public static function setSiteID(
			$confirmurl) {
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
			
			$db_conn = @mysql_connect ( DB_HOST, DB_USER, DB_PASSWORD );
			$db_db = @mysql_select_db ( DB_NAME );
			// look for blog_id
			$option_present_result = mysql_query ( 
					'SELECT Count(*) FROM ' . $table_prefix . $table_blog_id . 'options WHERE option_name = "fraxion_site_id"' );
			if (@mysql_result ( $option_present_result, 0, 0 ) > 0) {
				$option_result = @mysql_query ( 
						'UPDATE ' . $table_prefix . $table_blog_id . 'options SET option_value = "' . $site_ID . '" WHERE option_name = "fraxion_site_id"' );
			} else {
				$option_result = @mysql_query ( 
						'INSERT INTO ' . $table_prefix . $table_blog_id . 'options (option_name,option_value) Values("fraxion_site_id","' . $site_ID . '")' );
			}
			$message = 'Your site has been registered!<br /><br />Site ID: ' . $site_ID . ' has been inserted!';
		} else {
			$message = $site_ID_full;
		}
		return $message;
	}
} // end class FraxionPayments
?>