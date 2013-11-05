<?php
/**
 * Created on 13/03/2013
 *
 * Class of object that can provide fully qualified http/https links with queries correctly populated from
 * the list of known links used by the plugin.
 */
interface FraxionURLProvider {
	
	/**
	 * Query FUT and user status only unless siteArticleId given, then also article status, with site identified by fut
	 */
	public function getStatFutUrl(
			$fut, 
			$siteArticleId = null);
	
	/**
	 * Get a new fut for a session at the current site
	 */
	public function getGetFutUrl(
			$siteId);
	
	/**
	 * Get URL to redirect and confirm a new FUT.
	 */
	public function getGetConfirmFutUrl(
			$fut, 
			$returnURL);
	
	/**
	 * Get URL to the Fraxion catalogue page
	 */
	public function getCataloguePageUrl();
	
	/**
	 * Get URL to the Fraxion login page
	 */
	public function getLoginPageUrl(
			$returnURL);
	
	/**
	 * Get URL to the Fraxion register account page
	 */
	public function getRegisterAccountPageUrl(
			$returnURL);
	
	/**
	 * Get URL to have fraxion send a password reminder
	 */
	public function getForgotPwdUrl(
			$returnURL);
	
	/**
	 * Get URL to purchase fraxions page
	 */
	public function getPurchaseFraxionsPageUrl(
			$siteId, 
			$fut, 
			$returnURL);
	
	/**
	 * Get URL to unlock the current article
	 */
	public function getUnlockArticleUrl(
			$siteId, 
			$articleId, 
			$fut, 
			$returnURL);
	
	/**
	 * Get URL to view current user's account page
	 */
	public function getViewAccountPageUrl(
			$returnURL);
	
	/**
	 * Get URL to logout from fraxion account
	 */
	public function getLogoutUrl(
			$returnURL);
}
?>