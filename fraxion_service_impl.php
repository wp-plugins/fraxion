<?php
include ("fraxion_service_interface.php");

/**
 * Get information from and post information to Fraxion Payments.
 */
class FraxionServiceImpl implements FraxionService {
	private $urlSender; // Object to send messages to the fraxion server
	private $urlProvider; // Object to get urls to send to the fraxion server
	private $logger; // object to log to
	
	/**
	 * Class constructor sets up basic variables.
	 */
	public function __construct(
			FraxionURLSender $urlSender, 
			FraxionURLProvider $urlProvider) {
		$this->urlSender = $urlSender;
		$this->urlProvider = $urlProvider;
	
		$this->logger = FraxionLoggerImpl::getLogger ( "FraxionServiceImpl" );
		$this->logger->writeLOG ( "[__construct]" );
		
	} // end __construct
	
	/*
	 * (non-PHPdoc) @see FraxionService::get()
	 */
	public function get(
			$urlToSend) {
		$this->logger->writeLOG ( "[get] request: " . $urlToSend );
		$frax_doc = $this->urlSender->sendFraxURL ( $urlToSend );
		$this->logger->writeLOG ( "[get] reply: " . serialize ( $frax_doc ) );
		
		if ($frax_doc == '' || $frax_doc == false || strpos ( $frax_doc, '<?xml' ) === false) {
			$frax_reply = new DOMDocument ();
			$frax_reply->appendChild ( $frax_reply->createElement ( 'error', 'noServ' ) );
		} else {
			$frax_reply = DOMDocument::loadXML ( $frax_doc );
		}
		return $frax_reply;
	}
	/* (non-PHPdoc)
	 * @see FraxionService::getNewFUT()
	 */
	public function getNewFUT(
			$siteId) {
		$getFUTURL = $this->urlProvider->getGetFutUrl ( $siteId );
		return self::get ( $getFUTURL );
	}
} // end class FraxionServiceImpl
?>