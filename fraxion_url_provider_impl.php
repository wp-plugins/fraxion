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
	private $const_confid = 0; // Configuration ID. Future this could change to cause the plugin to have different URL
	                           // settings.
	public function __construct() {
		if (FraxionLoggerImpl::isDebug ()) {
			$this->logger = FraxionLoggerImpl::getLogger ( "FraxionURLProviderImpl" );
			if ($this->logger != null && $this->logger->isDebug ())
				$this->logger->writeLOG ( "[__construct]" );
		}
	} // end __construct
	
	/**
	 * Get the URL with the given name as an index.
	 * The URL will have no query part unless $params are passed.
	 */
	private function getURL(
			$urlName, 
			$params = null) {
		if (is_null ( $this->fraxion_urls )) {
			$filepath = PluginsPathImpl::get () . 'javascript' . DIRECTORY_SEPARATOR . 'settings_urls.json';
			$resources = json_decode ( str_replace ( '\n', null, file_get_contents ( $filepath ) ), TRUE );
			$this->fraxion_urls = $resources ['urls'];
			if ($this->logger != null && $this->logger->isDebug ()) {
				$this->logger->writeLOG ( "loaded urls from " . $filepath . " found:" );
				foreach ( $this->fraxion_urls as $i => $value ) {
					$this->logger->writeLOG ( $i . ":" . $this->fraxion_urls [$i] );
				}
			}
		}
		$theURI = $this->fraxion_urls [$urlName];
		if ($this->logger != null && $this->logger->isDebug ())
			$this->logger->writeLOG ( "for  urlName " . $urlName . " found " . $theURI );
		if (is_null ( $params )) {
			return $theURI;
		} else {
			$query = http_build_query ( $params );
			if ($this->logger != null && $this->logger->isDebug ()) {
				$this->logger->writeLOG ( "getURL query is " . $query );
			}
			return $theURI . '?' . $query;
		}
	} // end getURL($urlName, $params)
	
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
}
?>