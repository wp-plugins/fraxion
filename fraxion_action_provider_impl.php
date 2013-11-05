<?php
include ("fraxion_action_provider_interface.php");

/**
 * Created on 17/03/2013 Provide the display text in a siutable language.
 */
class FraxionActionProviderImpl implements FraxionActionProvider {
	private $logger = null; // object to write trace logs to.
	private $fraxion_actions = null;
	private $urlProvider = null;
	private $languageProvider = null;
	
	/**
	 * Class constructor sets up basic variables.
	 * @param FraxionURLProvider $fraxURLProvider
	 * @param unknown $fraxLanguageProvider
	 */
	public function __construct(
			FraxionURLProvider $fraxURLProvider, 
			FraxionLanguageProvider $fraxLanguageProvider) {
		$this->urlProvider = $fraxURLProvider;
		$this->languageProvider = $fraxLanguageProvider;
		$hasURLProvider = false;
		if ($this->urlProvider)
			$hasURLProvider = true;
		$hasLanguageProvider = false;
		if ($this->languageProvider)
			$hasLanguageProvider = true;
		$this->logger = FraxionLoggerImpl::getLogger ( "FraxionActionProviderImpl" );
		$this->logger->writeLOG( "[__construct] hasURLProvider=" . $hasURLProvider . " hasLanguageProvider=" . $hasLanguageProvider);
	} // end __construct
	
	/**
	 * Get the URL with the given name as an index.
	 * The URL will have no query part unless $params are passed.
	 * @param string $actionName
	 * @return
	 * 			string Text to apply as an action in the html.
	 */
	private function getAction(
			$actionName) {
		if (is_null ( $this->fraxion_actions )) {
			$filepath = PluginsPathImpl::get () . 'javascript' . DIRECTORY_SEPARATOR . 'settings_actions.json';
			$resources = json_decode ( str_replace ( '\n', null, file_get_contents ( $filepath ) ), TRUE );
			$this->fraxion_actions = $resources ['actions'];
			if ($this->logger->isDebug ()) {
				$this->logger->writeLOG ( "loaded actions from " . $filepath . " found:" );
				foreach ( $this->fraxion_actions as $i => $value ) {
					$this->logger->writeLOG ( $i . ":" . $this->fraxion_actions [$i] );
				}
			}
		}
		return $this->fraxion_actions [$actionName];
	} // end getAction($actionName)
	
	/**
	 * Get the html part that encodes display for the fraxion payments logo.
	 */
	public function getLogoAction() {
		$imagePath = self::getImagePath ();
		$action = self::getAction ( 'logo' );
		$action = str_replace ( '{image_path}', $imagePath, $action );
		return $action;
	} // end getLogoAction
	
	/**
	 * Get the html part that displays who the user is logged in as and how many fraxions they have.
	 *
	 * @param
	 *        	user_email the logged in user's email address
	 * @param
	 *        	acct_fraxions the logged in user's number of
	 *        	fraxions in the account
	 */
	public function getAccountInfoAction(
			$user_email, 
			$acct_fraxions) {
		$action = self::getAction ( 'account_info' );
		$theText = $this->languageProvider->getAccountInfoLabel ( $user_email, $acct_fraxions );
		$action = str_replace ( array (
				'{info}' 
		), array (
				$theText 
		), $action );
		return $action;
	} // end getAccountInfoAction
	
	/**
	 * Get the html part that encodes a show catalogue page button for fraxion payments.
	 */
	public function getCatalogueAction() {
		$imagePath = self::getImagePath ();
		$theURL = $this->urlProvider->getCataloguePageUrl ();
		$theAlt = $this->languageProvider->getCatalogueAltLabel ();
		$theLabel = $this->languageProvider->getCatalogueLabel ();
		
		$action = self::getAction ( 'catalogue' );
		$action = str_replace ( 
				array (
						'{catalogue_url}',
						'{image_path}',
						'{catalogue_alt}',
						'{catalogue_label}' 
				), array (
						$theURL,
						$imagePath,
						$theAlt,
						$theLabel 
				), $action );
		return $action;
	} // end getCatalogueAction
	
	/**
	 * Get the html part that encodes a login button for fraxion payments.
	 *
	 * @param
	 *        	returnURL the url to call back to after login
	 */
	public function getLoginFPAction(
			$returnURL) {
		$imagePath = self::getImagePath ();
		$theURL = $this->urlProvider->getLoginPageURL ( $returnURL );
		$theAlt = $this->languageProvider->getLoginFPAltLabel ();
		$theLabel = $this->languageProvider->getLoginFPLabel ();
		
		$action = self::getAction ( 'login_fp' );
		$action = str_replace ( 
				array (
						'{class}',
						'{login_fp_url}',
						'{image_path}',
						'{login_fp_alt}',
						'{login_fp_label}' 
				), 
				array (
						'recommended',
						$theURL,
						$imagePath,
						$theAlt,
						$theLabel 
				), $action );
		$this->logger->writeLOG( "[getLoginFPAction] action is " . $action);
		return $action;
	} // end getLoginFPAction
	
	/**
	 * Get the html part that encodes a forgot password button for fraxion payments.
	 *
	 * @param
	 *        	returnURL the url to call back to after login
	 */
	public function getForgotPwdAction(
			$returnURL) {
		$theURL = $this->urlProvider->getForgotPwdUrl ( $returnURL );
		$theLabel = $this->languageProvider->getForgotPwdLabel ();
		
		$action = self::getAction ( 'forgot_pwd' );
		$action = str_replace ( array (
				'{forgot_pswd_url}',
				'{login_pwd_label}' 
		), array (
				$theURL,
				$theLabel 
		), $action );
		return $action;
	} // end getForgotPwdAction
	
	/**
	 * Get the html part that encodes a register new user account button for fraxion payments.
	 *
	 * @param
	 *        	returnURL the url to call back to after login
	 */
	public function getRegisterAcctFPAction(
			$returnURL) {
		$imagePath = self::getImagePath ();
		$theURL = $this->urlProvider->getRegisterAccountPageUrl ( $returnURL );
		$theAlt = $this->languageProvider->getRegAcctAltLabel ();
		$theLabel = $this->languageProvider->getRegAcctLabel ();
		
		$action = self::getAction ( 'reg_acct' );
		$action = str_replace ( 
				array (
						'{class}',
						'{reg_acct_url}',
						'{image_path}',
						'{reg_acct_alt}',
						'{reg_acct_label}' 
				), array (
						'',
						$theURL,
						$imagePath,
						$theAlt,
						$theLabel 
				), $action );
		return $action;
	}
	
	/**
	 * Get the HTML part that produces a logout button.
	 *
	 * @param $returnURL the
	 *        	url to return to the current page.
	 */
	public function getLogoutFPAction(
			$returnURL) {
		$imagePath = self::getImagePath ();
		$theURL = $this->urlProvider->getLogoutUrl ( $returnURL );
		$theAlt = $this->languageProvider->getLogoutFPAlt ();
		$theLabel = $this->languageProvider->getLogoutFPLabel ();
		
		$this->logger->writeLOG( "[getLogoutFPAction]\n returnURL=" . $returnURL . "\n theURL=" . $theURL);
		
		$action = self::getAction ( 'logout_fp' );
		$action = str_replace ( 
				array (
						'{class}',
						'{logout_fp_url}',
						'{image_path}',
						'{logout_fp_alt}',
						'{logout_fp_label}' 
				), array (
						'',
						$theURL,
						$imagePath,
						$theAlt,
						$theLabel 
				), $action );
		return $action;
	}
	
	/**
	 * Get the HTML part that produces a button to go to the purchase fraxions page.
	 *
	 * @param
	 *        	siteId - needed? Track user behaviour perhaps? 
	 *        @param fut - needed? Is fraxion page that uses session
	 *        	to know user 
	 *        @param returnURL - page to go back to 
	 *        @param isRecommended - true if the button should be
	 *        	highlighted as a recommended action
	 */
	public function getPurchaseFraxionsAction(
			$siteId, 
			$fut, 
			$returnURL, 
			$isRecommended) {
		if ($isRecommended) {
			$recommend = 'recommend';
		} else {
			$recommend = '';
		}
		$imagePath = self::getImagePath ();
		$theURL = $this->urlProvider->getPurchaseFraxionsPageUrl ( $siteId, $fut, $returnURL );
		$theAlt = $this->languageProvider->getPurchaseAlt ();
		$theLabel = $this->languageProvider->getPurchaseLabel ();
		
		$action = self::getAction ( 'purchase' );
		$action = str_replace ( 
				array (
						'{class}',
						'{purchase_url}',
						'{image_path}',
						'{purchase_alt}',
						'{purchase_label}' 
				), 
				array (
						$recommend,
						$theURL,
						$imagePath,
						$theAlt,
						$theLabel 
				), $action );
		return $action;
	}
	
	/**
	 * Get the HTML part that produces a button that unlocks the document.
	 *
	 * @param
	 *        	cost the number of fraxions that would be required to unlock the document.
	 */
	public function getUnlockArticleDisabledAction(
			$cost) {
		$imagePath = self::getImagePath ();
		$theAlt = $this->languageProvider->getUnlockDisabledAlt ();
		$theLabel = $this->languageProvider->getUnlockDisabledLabel ( $cost );
		
		$action = self::getAction ( 'unlock_disabled' );
		$action = str_replace ( 
				array (
						'{class}',
						'{image_path}',
						'{unlock_disabled_alt}',
						'{unlock_disabled_label}' 
				), array (
						'',
						$imagePath,
						$theAlt,
						$theLabel 
				), $action );
		return $action;
	}
	
	/**
	 * Get the HTML part that produces a button that unlocks the document.
	 *
	 * @param
	 *        	cost the number of fraxions that would be required to unlock the document.
	 */
	public function getUnlockArticleAction(
			$siteId, 
			$articleId, 
			$fut, 
			$returnURL, 
			$cost) {
		$imagePath = self::getImagePath ();
		$theURL = $this->urlProvider->getUnlockArticleUrl ( $siteId, $articleId, $fut, $returnURL );
		$theAlt = $this->languageProvider->getUnlockAlt ();
		$theLabel = $this->languageProvider->getUnlockLabel ( $cost );
		
		$action = self::getAction ( 'unlock' );
		$action = str_replace ( 
				array (
						'{class}',
						'{url_unlock}',
						'{image_path}',
						'{unlock_alt}',
						'{unlock_label}' 
				), array (
						'',
						$theURL,
						$imagePath,
						$theAlt,
						$theLabel 
				), $action );
		return $action;
	} // end getUnlockArticleAction
	
	/**
	 * Get the HTML part that produces a button to open the fraxion payments view account page.
	 *
	 * @param
	 *        	siteId the Id of the site where the view is launched from.
	 * @param
	 *        	returnURL link back to the current
	 *        	page as a convenient way back.
	 * @param
	 *        	isRecommended should the button be highlighted as a recommended
	 *        	action?
	 */
	public function getViewAccountAction(
			$siteId, 
			$returnURL, 
			$isRecommended) {
		if ($isRecommended) {
			$recommend = 'recommend';
		} else {
			$recommend = '';
		}
		$imagePath = self::getImagePath ();
		$theURL = $this->urlProvider->getViewAccountPageUrl ( $returnURL );
		$theAlt = $this->languageProvider->getViewAccountAlt ();
		$theLabel = $this->languageProvider->getViewAccountLabel ();
		
		$action = self::getAction ( 'view_account' );
		$action = str_replace ( 
				array (
						'{class}',
						'{view_acct_url}',
						'{image_path}',
						'{view_acct_alt}',
						'{view_acct_label}' 
				), 
				array (
						$recommend,
						$theURL,
						$imagePath,
						$theAlt,
						$theLabel 
				), $action );
		return $action;
	} // end getViewAccountAction
	
	/**
	 * Get the url path to the fraxion plugin images folder.
	 *
	 * @return string
	 */
	private function getImagePath() {
		return get_bloginfo ( 'wpurl' ) . "/wp-content/plugins/fraxion/images/";
	} // end getImagePath
}
?>