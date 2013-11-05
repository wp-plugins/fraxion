<?php
interface FraxionService {
	/**
	 * Call the fraxion payments server with the identified URI and passing the request parameters.
	 * $uriID index to the URI in the json files, loaded in $this->fraxion_urls
	 * $params parameter name value pairs to add as query on the request
	 * return The reply as a DOMDocument
	 */
	public function get($urlToSend);
	/**
	 * @param unknown $siteId
	 */
	public function getNewFUT($siteId);
} // end inerface FraxionService
?>