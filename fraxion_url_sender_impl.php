<?php
include ("fraxion_url_sender_interface.php");
class FraxionURLSenderImpl implements FraxionURLSender {
	public function sendFraxURL(
			$frax_request) {
		$cFraxion = curl_init ();
		curl_setopt ( $cFraxion, CURLOPT_URL, $frax_request );
		curl_setopt ( $cFraxion, CURLOPT_RETURNTRANSFER, true );
		$frax_doc = curl_exec ( $cFraxion );
		curl_close ( $cFraxion );
		return $frax_doc;
	}
} // end class FraxionURLSender
?>