<?php
include ("fraxion_url_provider_interface.php");
/**
 * Created on 13/03/2013
 *
 * Class of object that can provide fully qualified http/https links with queries correctly populated from
 * the list of known links used by the plugin.
 */
class FraxionURLProviderImpl implements FraxionURLProvider {
	private $fraxion_urls = null;
	private $logger = null;
	private $const_confid = 0; // Configuration ID. Future this could change to cause the plugin to have different URL settings.
	private $debugURLLoading=false;
	public function __construct() {
		if (FraxionLoggerImpl::isDebug ()) {
			$this->logger = FraxionLoggerImpl::getLogger ( "FraxionURLProviderImpl" );
			if ($this->logger != null && $this->logger->isDebug ())
				$this->logger->writeLOG ( "[__construct]" );
		}
	} // end __construct
	
	private function loadURLs() {
		$filepath = PluginsPathImpl::get () . 'javascript' . DIRECTORY_SEPARATOR . 'settings_urls.json';
		$resources = json_decode ( str_replace ( '\n', null, file_get_contents ( $filepath ) ), TRUE );
		$this->fraxion_urls = $resources ['urls'];
		if ($this->debugURLLoading && frax_is_debugging($this->logger)) {
			$this->logger->writeLOG ( "loaded urls from " . $filepath . " found:" );
			foreach ( $this->fraxion_urls as $i => $value ) {
				$this->logger->writeLOG ( $i . ":" . $this->fraxion_urls [$i] );
			}
		}
	}
	
	private function applyURLBase($theURI) {
		$fraxURLBase = $this->fraxion_urls ['frax_url_base'];
		if ($fraxURLBase != null) {
			$theURI = str_replace ( '{frax_url_base}', $fraxURLBase, $theURI );
		}
		return $theURI;
	}
	
	private function getRawURL($urlName) {
		if (is_null ( $this->fraxion_urls )) {
			self::loadURLs();
		}
		$theURI = self::applyURLBase($this->fraxion_urls [$urlName]);
		if ($this->logger != null && $this->logger->isDebug ()) {
			$this->logger->writeLOG ( "for  urlName " . $urlName . " found " . $theURI );
		}
		return $theURI;
	}
	
	private function hasSubstitutePatterns($theURI) {
		return strpos($theURI, '{');
	}
	
	private function getURLWithAppendedQuery($theURI, $params) {
		if (is_null ( $params )) {
			return $theURI; // No Query to append
		} else {
			$query = http_build_query ( $params );
			$theURL = $theURI . '?' . $query;
			if ($this->logger != null && $this->logger->isDebug ()) {
				$this->logger->writeLOG ( "[getURLWithAppendedQuery] URL=" . $theURL );
			}
			return $theURL;
		}
	}
	
	/**
	 * theURI has substitute patterns, {param_name}, that need replacing with param values.
	 * @param type $theURI
	 * @param type $params
	 * @return type String the full URL with the substitute patterns replaced
	 * @throws Exception
	 */
	private function replaceSubstitutePatternsWithParams($theURI, $params) {
			
			if (is_null($params)) {
				if ($this->logger != null) {
					$this->logger->writeLOG ("[replaceSubstitutePatternsWithParams] No params given for URI "+$theURI);
				}
				throw new Exception("No params given for URI "+$theURI);
			} else {
				foreach ($params as $name => $value) {
					$theURI = str_replace ('{'.$name.'}',$value,$theURI);
				}
				if ($this->logger != null && $this->logger->isDebug ()) {
					$this->logger->writeLOG ( "[replaceSubstitutePatternsWithParams] getURL URI after substitutions " . $theURI );
				}
			}
			return $theURI;
	}
	
	/**
	 * Get the URL with the given name as an index.
	 * The URL will have no query part unless $params are passed.
	 * @param type $urlName
	 * @param type $params
	 * @return type
	 */
	private function getURL(
			$urlName, 
			$params = null
		) {
		$theURI = self::getRawURL($urlName);
		if (self::hasSubstitutePatterns($theURI)) {
			return self::replaceSubstitutePatternsWithParams($theURI,$params);
		} else {
			return self::getURLWithAppendedQuery($theURI,$params);
		}
	} // end getURL($urlName, $params)
	
	public function getBannerURL(
			$siteId,
			$siteArticleId,
			$fut,
			$returnURL
	) {
		return self::getURL ( 'banner',
				array (
						'site_id' => $siteId,
						'art_id' => $siteArticleId,
						'fut' => $fut,
						'returl' => $returnURL
				) );
	} // end getBannerURL
	
	public function getEditPostInfoURL($site_ID, $article_ID, $post_title) {
		global $user_ID;
		return self::getURL ( 'editpostinfo', array (
					'confid' => $this->const_confid,
					'sid' => $site_ID,
					'uid_wp' => $user_ID,
					'aid' => $article_ID,
					'atitle' => urlencode ( $post_title )
			) );
	}
	
	public function getJavaScriptURL() {
		return self::getURL ( 'javascriptfile' );
	}
	
	public function getStyleSheetURL() {
		return self::getURL ( 'cssfile' );
	}
	
	/**
	 * Query FUT and user status only unless siteArticleId given, then also article status, with site identified by fut
	 */
	public function getStatFutUrl(
			$fut, 
			$siteArticleId = null) {
		if (is_null ( $siteArticleId )) {
			return self::getURL ( 'statfut', array (
					'confid' => $this->const_confid,
					'fut' => $fut 
			) );
		} else {
			return self::getURL ( 'statfut', 
					array (
							'confid' => $this->const_confid,
							'fut' => $fut,
							'aid' => $siteArticleId 
					) );
		}
	} // end getStatFutUrl
	
	/**
	 * Get a new fut for a session at the current site
	 */
	public function getGetFutUrl(
			$siteId) {
		return self::getURL ( 'getfut', array (
				'confid' => $this->const_confid,
				'sid' => $siteId 
		) );
	}
	
	/**
	 * Get URL to redirect and confirm a new FUT.
	 */
	public function getGetConfirmFutUrl(
			$fut, 
			$returnURL) {
		return self::getURL ( 'confut', array (
				'confid' => $this->const_confid,
				'fut' => $fut,
				'returl' => $returnURL 
		) );
	}
	
	/**
	 * Get URL to the Fraxion catalogue page
	 */
	public function getCataloguePageUrl() {
		return self::getURL ( 'catalogue' );
	}
	
	/**
	 * Get URL to the Fraxion login page
	 */
	public function getLoginPageUrl(
			$returnURL) {
		return self::getURL ( 'loginFP', array (
				'confid' => '0',
				'returl' => $returnURL 
		) );
	}
	
	/**
	 * Get URL to the Fraxion register account page
	 */
	public function getRegisterAccountPageUrl(
			$returnURL) {
		return self::getURL ( 'regacct', array (
				'confid' => '0',
				'returl' => $returnURL 
		) );
	}
	
	public function getRegisterSiteURL() {
		return self::getURL ( 'regsite');
	}
	
	/**
	 * Get URL to have fraxion send a password reminder
	 */
	public function getForgotPwdUrl(
			$returnURL) {
		return self::getURL ( 'forgotpswd', array (
				'returl' => $returnURL 
		) );
	}
	
	/**
	 * Get URL to purchase fraxions page
	 */
	public function getPurchaseFraxionsPageUrl(
			$siteId, 
			$fut, 
			$returnURL) {
		return self::getURL ( 'purchase', array (
				'confid' => '0',
				'sid' => $siteId,
				'fut' => $fut,
				'returl' => $returnURL 
		) );
	}
	
	/**
	 * Get URL to unlock the current article
	 */
	public function getUnlockArticleUrl(
			$siteId, 
			$articleId, 
			$fut, 
			$returnURL) {
		return self::getURL ( 'unlock', 
				array (
						'confid' => '0',
						'sid' => $siteId,
						'aid' => $articleId,
						'fut' => $fut,
						'returl' => $returnURL 
				) );
	}
	
	/**
	 * Get URL to view current user's account page
	 */
	public function getViewAccountPageUrl(
			$returnURL) {
		return self::getURL ( 'viewaccount', array (
				'returl' => $returnURL 
		) );
	}
	
	/**
	 * Get URL to logout from fraxion account
	 */
	public function getLogoutUrl(
			$returnURL) {
		return self::getURL ( 'logoutFP', array (
				'returl' => $returnURL 
		) );
	}
	
	public function getFraxSiteAdminUrl($returnURL) {
		global $user_ID;
		return self::getURL ( 'siteadmin', array (
				 'returl'	=> $returnURL
				,'siteurl'	=> get_option ( 'home' )
				,'blogname'	=> get_option ( 'blogname' )
				,'sid'		=> get_option ( 'fraxion_site_id' )
				,'uid_wp'	=> $user_ID
				,'confid'	=> 0
		) );
	}
	
	public function getSiteStatusUrl($siteHomeURL) {
		return self::getURL ( 'sitestatus', array (
				'burl' => $siteHomeURL 
		) );
	}
	
	public function getSiteArticleSettingsUrl($site_ID, $article_ID) {
		global $user_ID;
		return self::getURL ( 'articlesettings', array (
			'sid' => $site_ID,
			'aid' => $article_ID,
			'uid_wp' => $user_ID 
		) );
	}
	
	public function getPublishArticleDataToFraxionUrl(
			$site_ID, $article_ID, $post_title, $fraxions_cost, $islocked) {
		global $user_ID;
		return self::getURL ( 'setartdata', array (
			'sid' => $site_ID,
			'aid' => $article_ID,
			'atitle' => $post_title,
			'cost' => $fraxions_cost,
			'uid_wp' => $user_ID,
			'lock' => $islocked 
		) );
	}
}
?>