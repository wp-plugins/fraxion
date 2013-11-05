<?php
/**
* Business logic for wp articles.
*/
class FraxionArticleLogic {
	private $fraxionService; // Object to handle requests to the fraxion server.
	private $urlProvider; // Object to provide URLs to fraxion services
	private $logger = null;
	
	public function __construct(
			FraxionService $fraxionService, 
			FraxionURLProvider $urlProvider) {
		$this->fraxionService = $fraxionService;
		$this->urlProvider = $urlProvider;
		
		$this->logger = FraxionLoggerImpl::getLogger ( 'FraxionArticleLogic' );
		$this->logger->writeLOG( '[__construct]');
		
	} // end __construct
	
	/**
	 * @param unknown $theFUT
	 * @param unknown $postId
	 * @return boolean
	 */
	public function isArticleUnlockedForUser(
			$theFUT, 
			$postId) {
		
		$this->logger->writeLOG( 'isArticleUnlockedForUser theFUT=' . $theFUT . ' postId=' . postId);
		
		$isUnlocked = false;
		$frax_dom = $this->fraxionService->get ( $this->urlProvider->getStatFutUrl ( $theFUT, $postId ) );
		if (! empty ( $frax_dom )) {
			$reply = $frax_dom->getElementsByTagName ( 'reply' );
			if (! empty ( $reply ) && $reply->length > 0) { // not error
				$rItem0 = $reply->item ( 0 );
				$lockedForUser = $rItem0->hasAttribute ( 'lock' ) && $rItem0->getAttribute ( 'lock' ) == 'true';
				if (! $lockedForUser) {
					if ($rItem0->hasAttribute ( 'isFraxioned' ) && $rItem0->getAttribute ( 'isFraxioned' ) == 'true') { // User has properly unlocked
						$isUnlocked = true;
					} // else Article is not registered yet
				} // else is locked for this user
			} // else not reply hence error
		} // else got no response at all hence error
		
		$this->logger->writeLOG( 'isArticleUnlockedForUser isUnlocked=' . $isUnlocked);
		
		return $isUnlocked;
	} // end isArticleUnlockedForUser
} // end class FraxionArticleLogic
?>