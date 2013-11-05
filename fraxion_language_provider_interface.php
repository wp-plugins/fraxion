<?php
/**
 * Created on 17/03/2013
 *
 * Provide the display text in a siutable language.
 */
interface FraxionLanguageProvider {
	public function getAccountInfoLabel(
			$user_email, 
			$acct_fraxions);
	public function getLoginFPLabel();
	public function getLoginFPAltLabel();
	public function getRegAcctLabel();
	public function getRegAcctAltLabel();
	public function getCatalogueLabel();
	public function getCatalogueAltLabel();
	public function getForgotPwdLabel();
	public function getPurchaseAlt();
	public function getPurchaseLabel();
	public function getUnlockDisabledAlt();
	public function getUnlockDisabledLabel(
			$cost);
	public function getUnlockAlt();
	public function getUnlockLabel(
			$cost);
	public function getLogoutFPLabel();
	public function getLogoutFPAlt();
	public function getViewAccountLabel();
	public function getViewAccountAlt();
}
?>