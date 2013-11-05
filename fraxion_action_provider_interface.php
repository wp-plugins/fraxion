<?php
/**
 * Created on 17/03/2013
 *
 * Provide the display text in a siutable language.
 */
 interface FraxionActionProvider {
 	
 	/**
 	 * Get the html part that encodes display for the fraxion payments logo.
 	 */
 	public function getLogoAction();
 	
 	/**
 	 * Get the html part that displays who the user is logged in as and how many fraxions they have.
 	 * @param user_email the logged in user's email address
 	 * @param acct_fraxions the logged in user's number of fraxions in the account
 	 */
 	public function getAccountInfoAction($user_email, $acct_fraxions);
 	
 	/**
 	 * Get the html part that encodes a show catalogue page button for fraxion payments.
 	 */
 	public function getCatalogueAction();
 	
 	/**
 	 * Get the html part that encodes a login button for fraxion payments.
 	 * @param returnURL the url to call back to after login
 	 */
 	public function getLoginFPAction($returnURL);
 	
 	/**
 	 * Get the html part that encodes a forgot password button for fraxion payments.
 	 * @param returnURL the url to call back to after login
 	 */
 	public function getForgotPwdAction($returnURL);
 	
 	/**
 	 * Get the html part that encodes a register new user account button for fraxion payments.
 	 * @param returnURL the url to call back to after login
 	 */
 	public function getRegisterAcctFPAction($returnURL);
 	
 	/**
 	 * Get the HTML part that produces a logout button.
 	 * @param $returnURL the url to return to the current page.
 	 */
 	public function getLogoutFPAction($returnURL);
 	
 	
 	/**
 	 * Get the HTML part that produces a button to go to the purchase fraxions page.
 	 * @param siteId - needed? Track user behaviour perhaps?
 	 * @param fut - needed? Is fraxion page that uses session to know user
 	 * @param returnURL - page to go back to
 	 * @param isRecommended - true if the button should be highlighted as a recommended action
 	 */
 	public function getPurchaseFraxionsAction($siteId,$fut,$returnURL, $isRecommended);
 	
 	/**
 	 * Get the HTML part that produces a disabled button that purports to unlock the document.
 	 * @param cost the number of fraxions that would be required to unlock the document.
 	 */
 	public function getUnlockArticleDisabledAction($cost);
 	
 	/**
 	 * Get the HTML part that produces a button that unlocks the document.
 	 */
 	public function getUnlockArticleAction($siteId, $articleId, $fut, $returnURL, $cost);
 	
 	/**
 	 * Get the HTML part that produces a button to open the fraxion payments view account page.
 	 * @param siteId the Id of the site where the view is launched from.
 	 * @param returnURL link back to the current page as a convenient way back.
 	 * @param isRecommended should the button be highlighted as a recommended action?
 	 */
 	public function getViewAccountAction($siteId, $returnURL, $isRecommended);
 }
?>
