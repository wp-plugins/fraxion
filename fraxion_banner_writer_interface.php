<?php
/**
 * Define a class of objects that write the different types of html banner for
 * inserting into articles.
 */
interface FraxionBannerWriter {
	/**
	 * Get the html for a banner explaining that the Fraxion service is currently unavailable.
	 */
	public function getNoServiceBanner();
	/**
	 * Get the html for a banner explaining that the Fraxion service returned an error.
	 */
	public function getServiceErrorBanner($errorMessage);
	/**
	 * Get the html for a banner explaining that the current article is locked
	 * but the site not yet been registered with Fraxion.
	 */
	public function getSiteNotRegisteredBanner($userIsLoggedIn);
	/**
	 * Get the html for a banner explaining that the current article is locked
	 * but has not yet been registered with Fraxion.
	 */
	public function getArticleNotRegisteredBanner($userIsLoggedIn);
	
	/**
	 * Get the html for a banner where the user is not logged in and can login or register.
	 */
	public function getUserNotLoggedInBanner($returnURL);
	
	/**
	 * Get the html for a banner where the user is logged in, the article is locked, not enough fraxions to unlock.
	 */
	public function getUserLoggedInLockFewFraxBanner($site_id, $fut, $returnURL, $articleId, $cost, $acct_fraxions, $user_email);
	
	/**
	 * Get the html for a banner where the user is logged in, the article is locked, not enough fraxions to unlock.
	 */
	public function getUserLoggedInLockFraxOkBanner($site_id, $fut, $returnURL, $article_id, $cost, $acct_fraxions, $user_email);
	
	/**
	 * Get the html for a banner where the user is logged in, and the article is not locked for the user.
	 */
	public function getUserLoggedInNotLockedBanner($site_id, $fut, $returnURL, $user_email, $acct_fraxions);
} // end class FraxionBannerWriter

?>