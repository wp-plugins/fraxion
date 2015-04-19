<?php
interface FraxionService {
	/**
	 * Call the URL and return the text reply.
	 */
	public function simpleGet($urlToSend);
	
	/**
	 * Call the fraxion payments server with the identified URL
	 * return The reply as a DOMDocument which may contain a child element of 'error'.
	 */
	public function get($urlToSend);
	/**
	 * @param unknown $siteId
	 */
	public function getNewFUT($siteId);
	
	public function getSimpleXML($urlToSend);
} // end inerface FraxionService
?>