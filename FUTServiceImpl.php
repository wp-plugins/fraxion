<?php

/**
 * Provide FUT operations including FUT in the session and connecting to
 * fraxion payments as needed. 
 *
 * @author Danny Stevens
 */
class FUTServiceImpl {
	private $fraxionService; // Object to handle requests to the fraxion server.
	private $urlProvider; // Object to provide URLs to fraxion services
	private $fut; // Fraxion User Token that identifies the current user session to the fraxion user session.
	private $bot = false;
	private $logger = null;
	private $fraxion_site_id; // Id given to this site when registered with Fraxion.
	
	private $const_cookie_name_fraxion_fut = 'fraxion_fut'; // index to the fut value in the cookie.
	
	private $const_option_fraxion_site_id = 'fraxion_site_id'; // The database option name for the saved Site Id given at
	                                                           // registration.
	
	/**
	 * Class constructor sets up basic variables.
	 */
	public function __construct(
			FraxionService $fraxionService, 
			FraxionURLProvider $urlProvider) {
		$this->logger = FraxionLoggerImpl::getLogger ( "FUTServiceImpl" );
		if ($this->logger->isDebugThis()) {
			$this->logger->writeLOG( '[__construct]');
		}
		
		$this->fraxionService = $fraxionService;
		$this->urlProvider = $urlProvider;
		self::loadSiteId ();
	}
	
	public function getFUT() {
		return $this->fut;
	}

	/**
	 * Get the site id from the options table in the DB and load it into $this->fraxion_site_id.
	 */
	private function loadSiteId() {
		if (function_exists ( 'get_option' ) && get_option ( $this->const_option_fraxion_site_id ) != false) {
			$this->fraxion_site_id = get_option ( $this->const_option_fraxion_site_id );
		} else {
			$this->fraxion_site_id = NULL;
		}
	} // end loadSiteId
	
	/**
	 * If we are not dealing with some bot, feed, track back or 404 error then
	 * call self::executeCheckFUT().
	 * The init function calls this at the start of a page. Do not call this
	 * from anywhere else.
	 */
	public function checkFUT() {
		if (frax_is_debugging($this->logger)) {
			$this->logger->writeLOG(
					'[checkFUT] User Agent: ' . $_SERVER ['HTTP_USER_AGENT']
					. ' : fraxion_site_id:' . $this->fraxion_site_id
					. ' : cur FUT:' . $this->fut);
		}
		if ((! (is_robots () || is_feed () || is_trackback () || is_404 ()))
			&& strpos ( $_SERVER ['HTTP_USER_AGENT'],'XML-Sitemaps' ) === false) {
			if (defined('DOING_AJAX') && DOING_AJAX) {
				$this->logger->writeLOG( '[checkFUT] refresh_post_panel !');
			} else if ($this->fraxion_site_id != NULL) { // Registered site
				self::executeCheckFUT();
			} // else unregistered site - nothing to do
		} else { // Its a bot - no login banner always
			$this->bot = true;
			if (frax_is_debugging($this->logger)) {
				$this->logger->writeLOG( '[checkFUT] its a bot!');
			}
		}
	} // end checkFUT
	
	/**
	 * The Fraxion User Token ties the session to the fraxion server, so check
	 * to see if we need a new one, and if so call self::renewFUT().
	 */
	private function executeCheckFUT() {
		if (frax_is_debugging($this->logger)) {
			$this->logger->writeLOG( '[executeCheckFUT] fraxion_site_id=' . $this->fraxion_site_id);
		}
		$this->fut = self::getFUTFromCookie();
		if (! self::isFutBlockedForABit() ) {
			if (self::checkFutStatus()) {
				self::renewFUT();
			} // end if $renew_fut
		}
	} // end executeCheckFUT
	
	/**
	 * See if this has been called in the last few seconds and if so return true,
	 * otherwise retur false and set the cookie that tells us that this got called.
	 * @return boolean true if FUT queries should not be performed
	 */
	private function isFutBlockedForABit() {
		// time() returns seconds http://php.net/manual/en/function.time.php
		// microtime() returns thousandths of seconds
		if ($this->fut === null) {
			return false;
		}
		
		$futBlockedForABit = false;
		if (array_key_exists ( 'fraxion_fut_block', $_COOKIE )) {
			$futBlockTime = $_COOKIE['fraxion_fut_block'];
			if ($futBlockTime < time()) {
				setcookie ( 'fraxion_fut_block', time()+2, time () + 2, '/' );
			} else {
				$futBlockedForABit = true;
			}
		} else {
			setcookie ( 'fraxion_fut_block', time()+2, time () + 2, '/' );
		}
		return $futBlockedForABit;
	}
	
	private function checkFutStatus() {
		if (frax_is_debugging($this->logger)) {
			$this->logger->writeLOG('[checkFutStatus]');
		}
		$renew_fut = false;
		if ($this->fut !== null) {
			if (frax_is_debugging($this->logger)) {
				$this->logger->writeLOG( 'Cur FUT in cookie:' . $this->fut );
			}
			$fut_dom = $this->fraxionService->get ( $this->urlProvider->getStatFutUrl ( $this->fut ) );
			$renew_fut = self::handleStatFutReply($fut_dom);
		} else { // no known FUT
			$renew_fut = true;
		}
		return $renew_fut;
	}
	
	private function getFUTFromCookie() {
		if (array_key_exists ( $this->const_cookie_name_fraxion_fut, $_COOKIE )) {
			return $_COOKIE [$this->const_cookie_name_fraxion_fut];
		}
		return null;
	}
	
	private function handleStatFutReply($fut_dom) {
		if (frax_is_debugging($this->logger)) {
			$this->logger->writeLOG('[handleStatFutReply]');
		}
		$renew_fut = false;
		if ($fut_dom->getElementsByTagName ( 'reply' ) != null && $fut_dom->getElementsByTagName ( 'reply' ) != false) {
			$reply = $fut_dom->getElementsByTagName ( 'reply' );
			if ($reply->item ( 0 ) && $reply->item ( 0 )->hasAttribute ( 'futinvalid' ) && $reply->item ( 
					0 )->getAttribute ( 'futinvalid' ) == 'true') {
				$renew_fut = true; // reply was that fut was invalid and so it needs renewing
			} else { // leave fut in the cookie as is and bump up its live time
				setcookie ( $this->const_cookie_name_fraxion_fut, $this->fut, time () + 36000, '/' );
			}
		} else { // No good reply - site down?
			$this->fut = null;
		}
		return $renew_fut;
	}
	
	/**
	 * get a new FUT to put in the cookies for this session,
	 * then redirect to confirm the FUT via a fraxion session.
	 */
	private function renewFUT() {
		if (frax_is_debugging($this->logger)) {
			$this->logger->writeLOG('[renewFUT]');
		}
		$fut_dom = $this->fraxionService->getNewFUT ( $this->fraxion_site_id );
		$reply = isset($fut_dom) ? $fut_dom->getElementsByTagName ( 'reply' ) : null;
		$this->fut = null;
		if (isset($reply) ) {
			if ($reply->length > 0) {
				$this->fut = $reply->item ( 0 )->nodeValue; // Actual FUT value is content of reply tag.
				$returnURL = 'http://' . $_SERVER ['SERVER_NAME'] . $_SERVER ['REQUEST_URI']; // Call back to the initial request
				$redirectHeader = 'Location: ' . $this->urlProvider->getGetConfirmFutUrl ( $this->fut, 
						$returnURL );
				$this->logger->writeLOG( 'redirectHeader:' . $redirectHeader);
				setcookie ( "fraxion_fut", $this->fut, time () + 36000, '/' );
				header ( $redirectHeader );
				exit ( 0 ); // stop processing and send the redirect
			} // reply length 0 ????
		} // else no good reply - site down?
	} // end renewFUT
	
}
