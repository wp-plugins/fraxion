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
		if ($this->logger->isDebug())
			$this->logger->writeLOG ( "[__construct]" );
		
	} // end __construct
	
	public function simpleGet($urlToSend) {
		$this->logger->writeLOG ( "[simpleGet] request: " . $urlToSend );
		$frax_doc = $this->urlSender->sendFraxURL ( $urlToSend );
		$this->logger->writeLOG ( "[simpleGet] reply: " . serialize ( $frax_doc ) );
		return $frax_doc;
	}
	
	/*
	 * (non-PHPdoc) @see FraxionService::get()
	 */
	public function get(
			$urlToSend) {
		if ($this->logger->isDebug()) {
			$this->logger->writeLOG ( "[get] request: " . $urlToSend );
		}
		$frax_doc = $this->urlSender->sendFraxURL ( $urlToSend );
		if ($this->logger->isDebug()) {
			$this->logger->writeLOG ( "[get] reply: " . var_dump_ret($frax_doc) );
		}
		
		if ($frax_doc == '' || $frax_doc == false || strpos ( $frax_doc, '<?xml' ) === false) {
			$frax_reply = new DOMDocument ();
			$frax_reply->appendChild ( $frax_reply->createElement ( 'error', 'noServ' ) );
			$this->logger->writeLOG ( "[get] request failed to get xml reply. Got " .  $frax_doc );
		} else {
			$frax_reply = $this->getDOMDocFromXML ( $frax_doc );
		}
		return $frax_reply;
	}
        
	private function getDOMDocFromXML($frax_doc) {
		$doc = new DOMDocument();
		if ($doc->loadXML($frax_doc)) {
			return $doc;
		} else {
			return null;
		}
	}
	/*
	 * (non-PHPdoc) @see FraxionService::getSimpleXML()
	*/
	public function getSimpleXML(
			$urlToSend) {
		if ($this->logger->isDebug()) {
			$this->logger->writeLOG ( "[getSimpleXML] request: " . $urlToSend );
		}
		$frax_doc = $this->urlSender->sendFraxURL ( $urlToSend );
		if ($this->logger->isDebug()) {
			$this->logger->writeLOG ( "[getSimpleXML] reply: " . var_dump_ret ( $frax_doc ) );
		}
		
		if ($frax_doc == '' || $frax_doc == false) {
			$frax_reply = new SimpleXMLElement('<error>noServ</error>');
		} else {
			
			// TODO what if not xml - get status code????????????
			 //  || strpos ( $frax_doc, '<?xml' ) === false
			if (strpos ( $frax_doc, '<?xml' ) === false) { // Didn't get xml
				$this->logger->writeLOG ( "[getSimpleXML] not XML !" );
				$frax_reply = new SimpleXMLElement('<error>notXML</error>');
			} else {
				$frax_reply = new SimpleXMLElement ( $frax_doc );
			}
		}
		return $frax_reply;
	} // end getSimpleXML
        
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