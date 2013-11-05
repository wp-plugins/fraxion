<?php
include ("fraxion_language_provider_interface.php");

/**
 * Created on 17/03/2013
 *
 * Provide the display text in a siutable language.
 */
class FraxionLanguageProviderImpl implements FraxionLanguageProvider {
	private $fraxion_messages = null;
	
	/**
	 * Get the text with the given name as an index.
	 * The $params, if given, will replace corresponding text in the returned value.
	 */
	private function getText(
			$messageName, 
			$params = null) {
		if (is_null ( $this->fraxion_messages )) {
			$resources = json_decode ( 
					str_replace ( '\n', null, 
							file_get_contents ( 
									PluginsPathImpl::get () . 'javascript' . DIRECTORY_SEPARATOR . 'settings_language.json' ) ), 
					TRUE );
			$this->fraxion_messages = $resources ['messages'];
		}
		$theText = $this->fraxion_messages [$messageName];
		if (is_null ( $params )) {
			return $theText;
		} else {
			foreach ( $params as $i => $value ) {
				$theText = str_replace ( $i, $params [$i], $theText );
			}
			return $theText;
		}
	} // end getURL($messageName)
	public function getAccountInfoLabel(
			$user_email, 
			$acct_fraxions) {
		return self::getText ( 'account_info', array (
				'{email}' => $user_email,
				'{fraxions}' => $acct_fraxions 
		) );
	}
	public function getLoginFPLabel() {
		return self::getText ( 'login_fp_label' );
	}
	public function getLoginFPAltLabel() {
		return self::getText ( 'login_fp_alt' );
	}
	public function getRegAcctLabel() {
		return self::getText ( 'reg_acct_label' );
	}
	public function getRegAcctAltLabel() {
		return self::getText ( 'reg_acct_alt' );
	}
	public function getCatalogueLabel() {
		return self::getText ( 'catalogue_label' );
	}
	public function getCatalogueAltLabel() {
		return self::getText ( 'catalogue_alt' );
	}
	public function getForgotPwdLabel() {
		return self::getText ( 'forgot_pwd_label' );
	}
	public function getPurchaseAlt() {
		return self::getText ( 'purchase_alt' );
	}
	public function getPurchaseLabel() {
		return self::getText ( 'purchase_label' );
	}
	public function getUnlockDisabledAlt() {
		return self::getText ( 'unlock_disabled_alt' );
	}
	public function getUnlockDisabledLabel(
			$cost) {
		return self::getText ( 'unlock_disabled_label', array (
				'{cost}' => $cost 
		) );
	}
	public function getUnlockAlt() {
		return self::getText ( 'unlock_alt' );
	}
	public function getUnlockLabel(
			$cost) {
		return self::getText ( 'unlock_label', array (
				'{cost}' => $cost 
		) );
	}
	public function getLogoutFPLabel() {
		return self::getText ( 'logout_fp_label' );
	}
	public function getLogoutFPAlt() {
		return self::getText ( 'logout_fp_alt' );
	}
	public function getViewAccountLabel() {
		return self::getText ( 'view_account_label' );
	}
	public function getViewAccountAlt() {
		return self::getText ( 'view_account_alt' );
	}
}
?>