<?php
include ("fraxion_banner_writer_interface.php");

/**
 * Define a class of objects that write the different types of html banner for
 * inserting into articles.
 */
class FraxionBannerWriterImpl implements FraxionBannerWriter {
	private $logger = null; // object to write trace logs to.
	private $const_version = '2.0.0c3';
	private $actions = null;
	private $urls = null;
	private $messages = null;
	private $urlProvider = null;
	private $languageProvider = null;
	private $actionProvider = null;
	
	/**
	 * Class constructor sets up basic variables.
	 */
	public function __construct(
			$fraxURLProvider, 
			$fraxLanguageProvider, 
			$fraxActionProvider) {
		$this->urlProvider = $fraxURLProvider;
		$this->languageProvider = $fraxLanguageProvider;
		$this->actionProvider = $fraxActionProvider;
		
		$this->logger = FraxionLoggerImpl::getLogger ( "FraxionBannerWriterImpl" );
		$hasURLProvider = false;
		if ($this->urlProvider)
			$hasURLProvider = true;
		$hasLanguageProvider = false;
		if ($this->languageProvider)
			$hasLanguageProvider = true;
		$hasActionProvider = false;
		if ($this->actionProvider)
			$hasActionProvider = true;
		$this->logger->writeLOG( "[__construct] hasURLProvider=" . $hasURLProvider . " hasLanguageProvider=" . $hasLanguageProvider . " hasActionProvider=" . $hasActionProvider);
		
		
	} // end __construct
	
	/**
	 * Get the html for a banner where the user is not logged in and can login or register.
	 */
	public function getUserNotLoggedInBanner(
			$returnURL) {
		$this->logger->writeLOG( "[getUserNotLoggedInBanner] returnURL=" . $returnURL);
		
		$logoAction = $this->actionProvider->getLogoAction ();
		$catalogueAction = $this->actionProvider->getCatalogueAction ();
		$postid = get_the_ID ();
		
		$loginAction = $this->actionProvider->getLoginFPAction ( $returnURL );
		$registerAction = $this->actionProvider->getRegisterAcctFPAction ( $returnURL );
		// $forgotPasswordAction = $this->actionProvider->getForgotPwdAction($returnURL);
		
		$banner_content = file_get_contents ( PluginsPathImpl::get () . 'html/' . 'fraxion_banner_not_logged_in.html' );
		
		$banner_content = str_replace ( '{version}', $this->const_version, $banner_content );
		$banner_content = str_replace ( '{logoAction}', $logoAction, $banner_content );
		$banner_content = str_replace ( '{catalogueAction}', $catalogueAction, $banner_content );
		$banner_content = str_replace ( '{postID}', $postid, $banner_content );
		
		$banner_content = str_replace ( '{loginAction}', $loginAction, $banner_content );
		$banner_content = str_replace ( '{registerAction}', $registerAction, $banner_content );
		// $banner_content = str_replace('{forgotPasswordAction}',$forgotPasswordAction,$banner_content);
		
		return $banner_content;
	} // end getUserNotLoggedInBanner
	
	/**
	 * Get the html for a banner where the user is logged in, the article is locked, not enough fraxions to unlock.
	 */
	public function getUserLoggedInLockFewFraxBanner(
			$site_id, 
			$fut, 
			$returnURL, 
			$articleId, 
			$cost, 
			$acct_fraxions, 
			$user_email) {
		$this->logger->writeLOG( "[getUserLoggedInLockFewFraxBanner] site_id=" . $site_id . " fut=" . $fut . " returnURL=" . $returnURL . " user_email=" . $user_email);
		
		$logoAction = $this->actionProvider->getLogoAction ();
		$catalogueAction = $this->actionProvider->getCatalogueAction ();
		$postid = get_the_ID ();
		
		$purchaseAction = $this->actionProvider->getPurchaseFraxionsAction ( $site_id, $fut, $returnURL, true );
		$unlockTooMuchAction = $this->actionProvider->getUnlockArticleDisabledAction ( $cost );
		$viewAccountAction = $this->actionProvider->getViewAccountAction ( $site_id, $returnURL, false );
		$logoutAccountAction = $this->actionProvider->getLogoutFPAction ( $returnURL );
		$accountInfoAction = $this->actionProvider->getAccountInfoAction ( $user_email, $acct_fraxions );
		
		$banner_content = file_get_contents ( 
				PluginsPathImpl::get () . 'html/' . 'fraxion_banner_logged_in_lk_few_frax.html' );
		
		$banner_content = str_replace ( '{version}', $this->const_version, $banner_content );
		$banner_content = str_replace ( '{logoAction}', $logoAction, $banner_content );
		$banner_content = str_replace ( '{catalogueAction}', $catalogueAction, $banner_content );
		$banner_content = str_replace ( '{postID}', $postid, $banner_content );
		
		$banner_content = str_replace ( '{accountInfoAction}', $accountInfoAction, $banner_content );
		$banner_content = str_replace ( '{unlockTooMuchAction}', $unlockTooMuchAction, $banner_content );
		$banner_content = str_replace ( '{purchaseAction}', $purchaseAction, $banner_content );
		$banner_content = str_replace ( '{viewAccountAction}', $viewAccountAction, $banner_content );
		$banner_content = str_replace ( '{logoutAccountAction}', $logoutAccountAction, $banner_content );
		
		return $banner_content;
	} // end getUserLoggedInLockFewFraxBanner
	
	/**
	 * Get the html for a banner where the user is logged in, the article is locked, not enough fraxions to unlock.
	 */
	public function getUserLoggedInLockFraxOkBanner(
			$site_id, 
			$fut, 
			$returnURL, 
			$article_id, 
			$cost, 
			$acct_fraxions, 
			$user_email) {
		$this->logger->writeLOG( "[getUserLoggedInLockFraxOkBanner] site_id=" . $site_id . " fut=" . $fut . " returnURL=" . $returnURL . " user_email=" . $user_email);
		
		$logoAction = $this->actionProvider->getLogoAction ();
		$catalogueAction = $this->actionProvider->getCatalogueAction ();
		$postid = get_the_ID ();
		
		$accountInfoAction = $this->actionProvider->getAccountInfoAction ( $user_email, $acct_fraxions );
		$purchaseAction = $this->actionProvider->getPurchaseFraxionsAction ( $site_id, $fut, $returnURL, true );
		$unlockOkAction = $this->actionProvider->getUnlockArticleAction ( $site_id, $article_id, $fut, $returnURL, 
				$cost );
		$viewAccountAction = $this->actionProvider->getViewAccountAction ( $site_id, $returnURL, false );
		$logoutAccountAction = $this->actionProvider->getLogoutFPAction ( $returnURL );
		
		$banner_content = file_get_contents ( PluginsPathImpl::get () . 'html/' . 'fraxion_banner_logged_in_lk_ok.html' );
		
		$banner_content = str_replace ( '{version}', $this->const_version, $banner_content );
		$banner_content = str_replace ( '{logoAction}', $logoAction, $banner_content );
		$banner_content = str_replace ( '{catalogueAction}', $catalogueAction, $banner_content );
		$banner_content = str_replace ( '{postID}', $postid, $banner_content );
		
		$banner_content = str_replace ( '{accountInfoAction}', $accountInfoAction, $banner_content );
		$banner_content = str_replace ( '{unlockOkAction}', $unlockOkAction, $banner_content );
		$banner_content = str_replace ( '{purchaseAction}', $purchaseAction, $banner_content );
		$banner_content = str_replace ( '{viewAccountAction}', $viewAccountAction, $banner_content );
		$banner_content = str_replace ( '{logoutAccountAction}', $logoutAccountAction, $banner_content );
		
		return $banner_content;
	} // end getUserLoggedInLockFraxOkBanner
	
	/**
	 * Get the html for a banner where the user is logged in, and the article is not locked for the user.
	 */
	public function getUserLoggedInNotLockedBanner(
			$site_id, 
			$fut, 
			$returnURL, 
			$acct_fraxions, 
			$user_email) {
		$this->logger->writeLOG( "[getUserLoggedInNotLockedBanner] site_id=" . $site_id . " fut=" . $fut . " returnURL=" . $returnURL . " user_email=" . $user_email . " acct_fraxions=" . $acct_fraxions);
		
		self::initJSONdata ();
		
		$logoAction = $this->actionProvider->getLogoAction ();
		$catalogueAction = $this->actionProvider->getCatalogueAction ();
		$postid = get_the_ID ();
		
		$accountInfoAction = $this->actionProvider->getAccountInfoAction ( $user_email, $acct_fraxions );
		$purchaseAction = $this->actionProvider->getPurchaseFraxionsAction ( $site_id, $fut, $returnURL, true );
		$viewAccountAction = $this->actionProvider->getViewAccountAction ( $site_id, $returnURL, false );
		$logoutAccountAction = $this->actionProvider->getLogoutFPAction ( $returnURL );
		
		$banner_content = file_get_contents ( 
				PluginsPathImpl::get () . 'html/' . 'fraxion_banner_logged_in_unlocked.html' );
		
		$banner_content = str_replace ( '{version}', $this->const_version, $banner_content );
		$banner_content = str_replace ( '{logoAction}', $logoAction, $banner_content );
		$banner_content = str_replace ( '{catalogueAction}', $catalogueAction, $banner_content );
		$banner_content = str_replace ( '{postID}', $postid, $banner_content );
		
		$banner_content = str_replace ( '{accountInfoAction}', $accountInfoAction, $banner_content );
		$banner_content = str_replace ( '{purchaseAction}', $purchaseAction, $banner_content );
		$banner_content = str_replace ( '{viewAccountAction}', $viewAccountAction, $banner_content );
		$banner_content = str_replace ( '{logoutAccountAction}', $logoutAccountAction, $banner_content );
		
		return $banner_content;
	} // end getUserLoggedInNotLockedBanner
	
	/**
	 * Get the html for a banner explaining that the Fraxion service is currently unavailable.
	 */
	public function getNoServiceBanner() {
		return self::getSimpleMessageBanner ( self::getMessageTemplate ( 'noServ' ) );
	} // end getNoServiceBanner
	
	/**
	 * Get the html for a banner explaining that the Fraxion service returned an error.
	 */
	public function getServiceErrorBanner(
			$errorMessage) {
		return self::getSimpleMessageBanner ( str_replace ( '{error}', $errorMessage, 
				self::getMessageTemplate ( 'error' ) ) );
	} // end getServiceErrorBanner
	
	/**
	 * Get the html for a banner explaining that the current article is locked
	 * but the site not yet been registered with Fraxion.
	 */
	public function getSiteNotRegisteredBanner(
			$userIsLoggedIn) {
		if ($userIsLoggedIn) {
			return self::getFunctionalMessageBanner ( self::getMessageTemplate ( 'artNoSite' ) );
		} else {
			return self::getSimpleMessageBanner ( self::getMessageTemplate ( 'artNoSite' ) );
		}
	} // end getSiteNotRegisteredBanner
	
	/**
	 * Get the html for a banner explaining that the current article is locked
	 * but has not yet been registered with Fraxion.
	 */
	public function getArticleNotRegisteredBanner(
			$userIsLoggedIn) {
		if ($userIsLoggedIn) {
			return self::getFunctionalMessageBanner ( self::getMessageTemplate ( 'artNotReg' ) );
		} else {
			return self::getSimpleMessageBanner ( self::getMessageTemplate ( 'artNotReg' ) );
		}
	} // end getArticleNotRegisteredBanner
	
	/**
	 * Build a banner with no functionality that simply displays the message.
	 */
	private function getSimpleMessageBanner(
			$message) {
		$site_url = get_bloginfo ( 'wpurl' );
		
		$banner_content = file_get_contents ( PluginsPathImpl::get () . 'html/' . 'fraxion_banner_simple_message.html' );
		$banner_content = str_replace ( '{site_url}', $site_url, $banner_content );
		$banner_content = str_replace ( '{version}', $this->const_version, $banner_content );
		$banner_content = str_replace ( '{message}', $message, $banner_content );
		return $banner_content;
	} // end getSimpleMessageBanner
	
	/**
	 * Build a banner that displays the message and includes non-article functionality to
	 * logout, go to user account, get more fraxions and go to the catalogue.
	 */
	private function getFunctionalMessageBanner(
			$message) {
		$site_url = get_bloginfo ( 'wpurl' );
		
		$banner_content = file_get_contents ( 
				PluginsPathImpl::get () . 'html/' . 'fraxion_banner_functional_message.html' );
		$banner_content = str_replace ( '{site_url}', $site_url, $banner_content );
		$banner_content = str_replace ( '{version}', $this->const_version, $banner_content );
		$banner_content = str_replace ( '{message}', $message, $banner_content );
		
		// Currently identical to the simple banner
		// go to account
		// logout
		// get fraxions
		// go to catalogue
		return $banner_content;
	} // end getFunctionalMessageBanner
	/**
	 * @param unknown $messageID
	 */
	private function getMessageTemplate(
			$messageID) {
		self::initJSONdata ();
		return $this->messages [$messageID];
	} // end getMessageTemplate
	/**
	 * @return string
	 */
	private function getCurrentRequestURL() {
		$protocol = (stripos ( $_SERVER ['SERVER_PROTOCOL'], 'https' ) !== false ? 'https://' : 'http://');
		return $protocol . $_SERVER ['SERVER_NAME'] . urldecode ( $_SERVER ['REQUEST_URI'] );
	} // end getCurrentRequestURL
	  
	// deprecated
	private function initJSONdata() {
		$resources = json_decode ( 
				str_replace ( '\n', null, file_get_contents ( PluginsPathImpl::get () . 'javascript/settings.json' ) ), 
				TRUE );
		$actions = $resources ['actions'];
		$urls = $resources ['urls'];
		$messages = $resources ['messages'];
	}
} // end class FraxionBannerWriterImpl

?>