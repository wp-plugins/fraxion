<?php

/* 
 * Fraxion Payments contoller for the post panel in the admin edit post view.
 * Copyright Fraxion Payments 2003-2015, all rights reserved.
 */

class FraxionAdminPostPanel {
	
	private $fraxService;
	private $urlProvider;
	
	public function __construct(
                FraxionURLProvider $urlProvider,
                FraxionService $fraxService) {
		$this->logger = FraxionLoggerImpl::getLogger ( "FraxionAdminPostPanel" );
		$this->logger->writeLOG( "[__construct]");
		if ($urlProvider == null) {
			FraxionErrorPageImpl::fatalError ( "urlProvider is null", $this->logger );
		}
		if ($fraxService == null) {
			FraxionErrorPageImpl::fatalError ( "fraxService is null", $this->logger );
		}
		$this->urlProvider = $urlProvider;
		$this->fraxService = $fraxService;
	} // end __construct
	
	private function setAndNotEmpty($aPostParam) {
		return	isset ( $_POST[$aPostParam] )
				&&	$_POST[$aPostParam] != '';
	}
	
	/**
	 * @return type json with elements  'locked', 'cost' and 'status_message'
	 */
	public function refresh_post_panel() {
		if  (self::setAndNotEmpty('siteID') && self::setAndNotEmpty('postID') && self::setAndNotEmpty('userID')) {
			if (function_exists ( 'get_option' ) && get_option ( 'fraxion_site_id' ) != false) {
				$siteId = get_option ( 'fraxion_site_id' );
				if ($siteId == $_POST ['siteID']) {
					self::doBuildPostPanelJSON($siteId, $_POST ['postID']);
				} else {
					$this->logger->writeLOG( '[refreshPostPanel] given site_id '.$_POST ['siteID'].' instead of expected '.$siteId);
					wp_die("Site ID error");
				}
			} else {
				$this->logger->writeLOG( '[refreshPostPanel] No registered site id');
				wp_die("No registered site id");
			}
		} else {
			$this->logger->writeLOG( '[refreshPostPanel] missing argument siteID, postID or userID');
			wp_die("Missing arguments");
		}
		$this->logger->writeLOG( '[refreshPostPanel] unexpected exit');
	}
	
	private function doBuildPostPanelJSON($siteId, $postId) {
		try {
			$frax_dom = self::getFraxDom($siteId,  $postId);
			$panelReply = self::getPanelReply($frax_dom);
			return $panelReply;
		} catch (Exception $ex1) {
			$this->logger->writeLOG($ex1->getMessage());
			wp_die($ex1);
		}
	}
	
	private function getFraxDom($siteId,  $postId) {
		return $this->fraxService->get(
			$this->urlProvider->getSiteArticleSettingsUrl($siteId,  $postId)
		);
	}
	
	private function getPanelReply($frax_dom) {
		$reply = $frax_dom->getElementsByTagName ( 'reply' );
		$error = $frax_dom->getElementsByTagName ( 'error' );
		return $this->getPostPanelJSON($reply, $error);
	}
	
	/**
	 * Get the contents of the call to the site settings URL and provide an
	 * error message in status_message or the locked state (true/false) and
	 * unlock cost values.
	 * @param type $reply reply from site settings URL
	 * @param type $error error returned with site settings if any
	 * @return type json with elements  'locked', 'cost' and 'status_message'
	 */
	private function getPostPanelJSON($reply, $error) {
		$locked = 'false';
		$fraxions_cost = '0';
		$status_message = '';
		if ($reply->length > 0 && $reply->item ( 0 )->hasAttribute ( 'lock' )) {
			if ($reply->item ( 0 )->getAttribute ( 'lock' ) == 'true') {
				$locked = "true";
				$fraxions_cost = $reply->item ( 0 )->getAttribute ( 'cost' );
			}
		} else {
			$status_message = $error->length > 0
					? $error->item ( 0 )->firstChild->nodeValue : 'noServ';
		}
		return $this->getPostPanelJSONWFields($locked, $fraxions_cost, $status_message);
	}
	private function getPostPanelJSONWFields($locked, $fraxions_cost, $status_message) {
		$jsonInputStructure = array (
			'locked' => $locked,
			'cost' => $fraxions_cost,
			'status_message' => $status_message,
			'am_doing_ajax' => (defined('DOING_AJAX') && DOING_AJAX)
		);
		$jsonResult = json_encode($jsonInputStructure);
		$this->logger->writeLOG('jsonResult ' . $jsonResult);
		@header('Content-Type: text/json; charset='.get_option('blog_charset'));
		echo $jsonResult;
		wp_die();
	}
}

