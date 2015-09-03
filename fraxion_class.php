<?php
class FraxionPayments {
	private $fraxionService; // Object to handle requests to the fraxion server.
//	private $bannerWriter; // Object to render banner html
	private $urlProvider; // Object to provide URLs to fraxion services
	private $languageProvider; // Object to provide pluggable text in some language by index
	private $FUTService; // Object to handle Fraxion User Tokens
	private $logger = null;
	private $lockSet = false;
	private $fp_post_status = "locked";
	private $const_the_tag = '[frax09alpha]'; // The tag in the content where the banner cuts in
	private $const_option_fraxion_site_id = 'fraxion_site_id'; // The database option name for the saved Site Id given at
	                                                           // registration.
//	private $const_option_fraxion_resf_id = 'fraxion_resf_id'; // postfix for resource folder
	private static $const_version = '2.1.1';
	private $fraxion_site_id; // Id given to this site when registered with Fraxion.
	
	public static function getVersion() {
		return self::$const_version;
	}
	/**
	 * Class constructor sets up basic variables.
	 */
	public function __construct(
			FraxionService $fraxionService, 
			FraxionURLProvider $urlProvider, 
			FraxionLanguageProvider $languageProvider,
			FUTServiceImpl $FUTService) {
		$this->fraxionService = $fraxionService;
		//			FraxionBannerWriter $bannerWriter, 
//		$this->bannerWriter = $bannerWriter;
		$this->languageProvider = $languageProvider;
		$this->urlProvider = $urlProvider;
		$this->FUTService = $FUTService;
		$this->logger = FraxionLoggerImpl::getLogger ( "FraxionPayments" );
		if ($this->logger->isDebug()) {
			$this->logger->writeLOG( '[__construct]');
		}
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
	
	public function is_show_comments($open, $content) {
		if ($this->fp_post_status == "locked" && $this->lockSet) {
			return false;
		} else {
			return $open;
		}
	}
	public function insert_enter_comments_footnote($content) {
		if (!is_feed() && !is_home() && $this->fp_post_status == "locked") {
			$content .= "<div><h3>To leave comments please unlock this post</h3></div>";
		}
		return $content;
	}
	
	private function setLockStatus($the_content) {
		
	}

	/**
	 * See if a banner is required, get it from fraxion payments and insert into the content.
	 * $the_content the article content
	 */
	public function push_banner(
			$the_content) {
		$this->lockSet = true;
		$tag_position = strpos ( $the_content, $this->const_the_tag );
		$hasTag = ($tag_position === false ? false : true);
		if ($hasTag) {
			self::loadSiteId ();
			if (! empty ( $this->fraxion_site_id )) {
				return self::push_banner_to_tagged_content ( $the_content, $tag_position );
			} else { // Unregistered site
				$banner_content = "<div>Site not registered</div>";
				return self::insert_banner_at_tag ( $the_content, $tag_position, $banner_content );
			}
		} else { // Doesn't have a tag so it is unlocked with no fraxion banner present
			$this->fp_post_status = "unlocked";
			return $the_content;
		}
	} // end push_banner
	
	/**
	 * Build a banner with no functionality that simply displays the message.
	 */
	private function getSimpleMessageBanner($message) {
		$site_url = get_bloginfo ( 'wpurl' );
		
		$banner_content = file_get_contents ( PluginsPathImpl::get () . 'html/' . 'fraxion_banner_simple_message.html' );
		$banner_content = str_replace ( '{site_url}', $site_url, $banner_content );
		$banner_content = str_replace ( '{version}', self::$const_version, $banner_content );
		$banner_content = str_replace ( '{message}', $message, $banner_content );
		return $banner_content;
	} // end getSimpleMessageBanner
	
	/**
	 * Get the html for a banner explaining that the Fraxion service is currently unavailable.
	 */
	public function getNoServiceBanner() {
		return self::getSimpleMessageBanner ( $this->languageProvider->getNoServerMessage() );
	} // end getNoServiceBanner
	
	/**
	 * Add a banner to the content, which has a tag at the given character position.
	 */
	private function push_banner_to_tagged_content(
			$the_content, 
			$tag_position) {
		$article_ID = get_the_ID ();
		$returnURL = urlencode( self::getRequestURL() );
		if ($this->FUTService->getFUT() === null) {
			$this->logger->writeLOG('[push_banner_to_tagged_content] FUT NOT SET!!! ');
		}
			// call to new get frax banner
		$banner_url_complete = $this->urlProvider->getBannerUrl ( $this->fraxion_site_id, $article_ID, $this->FUTService->getFUT(), $returnURL );
		$bannerXML = $this->fraxionService->getSimpleXML ( $banner_url_complete );
		if ($this->logger->isDebug()) {
			$this->logger->writeLOG('[push_banner_to_tagged_content] URL ' . $banner_url_complete);
			$this->logger->writeLOG('Reply: ' . (isset ($bannerXML) ? $bannerXML->asXML() : 'null') );
		}
		$banner_content = $this->get_banner_content($bannerXML);
		if ($this->is_banner_for_footer($bannerXML)) {
			$this->fp_post_status = "unlocked";
			return self::insert_banner_at_foot ( $the_content, $banner_content );
		} else {
			return self::insert_banner_at_tag ( $the_content, $tag_position, $banner_content );
		}
	} // end push_banner_to_tagged_content
	
	private function get_banner_content($bannerXML) {
		$banner_content = '';
		if (isset ($bannerXML)) {
			$banner_content = $bannerXML; // Content of the main tag <reply>
		} else {
			$banner_content = $this->getNoServiceBanner ();
		}
		return $banner_content;
	}
	private function is_banner_for_footer($bannerXML) {
		$showAsFooter = false;
		if (isset ($bannerXML)) {
			// read the attributes
			foreach($bannerXML->attributes() as $name => $value) {
				if ($name == 'status') {
					$showAsFooter = ($value != 'locked');
				}
			}
		}
		return $showAsFooter;
	}
	
	/**
	 * Chop the content at the tag position, closing off open html tags, and append the banner content.
	 * The whole thing is wrapped in a div with an id based on the article ID ( 'fraxion_post_content_xxx').
	 */
	private function insert_banner_at_tag(
			$the_content, 
			$tag_position, 
			$banner_content) {
		$fraxion_content = self::closetags ( substr ( $the_content, 0, $tag_position ) . "... " . $banner_content );
		return "<div id='fraxion_post_content_" . get_the_ID () . "'>" . $fraxion_content . '</div>';
	}
	
	/**
	 * Remove the fraxion tag and put the banner at the end of the content.
	 * The whole thing is wrapped in a div with an id based on the article ID ( 'fraxion_post_content_xxx').
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
	
	private static function sendConfirmSiteURLGetReply( $confirmurl	) {
		// / get site_ID
		$cFraxion = curl_init ();
		curl_setopt ( $cFraxion, CURLOPT_URL, $confirmurl );
		curl_setopt ( $cFraxion, CURLOPT_RETURNTRANSFER, true );
		$site_ID_full = curl_exec ( $cFraxion );
		curl_close ( $cFraxion );
		
		return $site_ID_full;
	}
	
	/**
	 * A function designed to confirm the site ID with the confirmurl and then
	 * insert the value into the database.
	 * The function does not require that index.php has been called,
	 * so it may be called directly.
	 * It updates or inserts the id into the options table where the option
	 * name is 'fraxion_site_id'.
	 */
	public static function setSiteID(
			$confirmurl) {
		global $table_prefix;
		$mu_site = false;
		$blog_id = 0;
		$table_blog_id = '';
		$site_ID_full = self::sendConfirmSiteURLGetReply ( $confirmurl );
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
	
	function enqueFraxionJavascript() {
		wp_enqueue_script(
			'fraxion-java-script',
			$this->urlProvider->getJavaScriptURL(),
			array( 'jquery' )
		);
	}
	
	function enqueFraxionStyleSheet() {
		wp_enqueue_style( 'fraxion-style-sheet', $this->urlProvider->getStyleSheetURL() );
	}
} // end class FraxionPayments
?>