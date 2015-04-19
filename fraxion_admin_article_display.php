<?php

/* 
 * Class to handle displaying the admin widget in post and page editing.
 * @author Danny Stevens
 */
class FraxionAdminArticleDisplay {
	private $fraxions_cost = 0;
	private $status_message = null;
	private $article_ID = '';
	private $locked = 'false';
	private $post_title = '';
	
	private $logger = null;
	
	private $status_messages;
	private $resourceController;
	private $plugins_path;
	private $site_ID;
	
	public function __construct(
                FraxionResourceController $resourceController,
                FraxionURLProvider $urlProvider,
                FraxionService $fraxService) {
	
		if (FraxionLoggerImpl::isDebug ()) {
			$this->logger = FraxionLoggerImpl::getLogger ( "FraxionAdminArticleDisplay" );
			if ($this->logger != null && $this->logger->isDebug ()) {
				$this->logger->writeLOG ( "[__construct]" );
			}
		}
		
		$this->resourceController = $resourceController;
		$this->urlProvider = $urlProvider;
		$this->fraxService = $fraxService;

		$fraxion_plugin_path = explode ( DIRECTORY_SEPARATOR, __FILE__ );
		$this->plugins_path = implode ( DIRECTORY_SEPARATOR, array_slice ( $fraxion_plugin_path, 0, - 1 ) ) . DIRECTORY_SEPARATOR;
		if (function_exists ( 'get_option' ) && get_option ( 'fraxion_site_id' ) != false) {
				$this->site_ID = get_option ( 'fraxion_site_id' );
		} else {
				$this->site_ID = NULL;
		}
		$settings = self::loadSettings();
		$this->status_messages = $settings ['messages'];
		
		if ($this->logger != null && $this->logger->isDebug()) {
			$this->logger->writeLOG("settings xx>" . var_export($settings, TRUE) . "<xx");
			$this->logger->writeLOG("status_messages xx>" . var_export($this->status_messages, true) . "<xx");
		}
	}
	
	private function loadSettings() {
		$fileContent = file_get_contents ( $this->plugins_path . 'javascript/settings.json' );
		$noNewLineContent = str_replace ( '\n', '', $fileContent );
		$noTabsContent = str_replace ( '\t', '', $noNewLineContent );
		return json_decode ( $noTabsContent , TRUE );
	}
	
	/**
	 * Entry point for inserting admin widget to both posts and pages.
	 */
	public function admin_Post() {
		// Creates call backs to the admin_PostPanel method
		if ($this->logger != null && $this->logger->isDebug()) {
			$this->logger->writeLOG('[admin_Post]');
		}
		add_meta_box ( 'frax_post_admin', 'Fraxion Payments',
			array ($this, 'admin_PostPanel'), 'post', 'side', 'high' );
		add_meta_box ( 'frax_post_admin', 'Fraxion Payments',
			array ($this, 'admin_PostPanel'), 'page', 'side', 'high' );
	}
	/**
	 * Call back method when word press wants to add the admin widget for a post or page.
	 * @param type $post_ID
	 */
	public function admin_PostPanel($post_ID) {
		get_currentuserinfo ();
		if ($this->site_ID != NULL) {
			$this->admin_include_PostPanelRegisteredSite($post_ID);
		} else { // Site not registered
			$sitehref = get_option ( 'siteurl' )
					. '/wp-admin/options-general.php?page=fpsiteoptions';
			echo ('<span style="color:red;"><a href="' . $sitehref . '">'
					. 'Register your Site</a></span>');
		}
	} // end admin_PostPanel
	
	private function isEmptyMessage($message) {
		$noMessage = false;
		if (empty($message)) {
			$noMessage = true;
		} else {
			$trimmedMsg = trim($message);
			$noMessage = empty($trimmedMsg);
		}
		if ($this->logger != null && $this->logger->isDebug()) {
			$this->logger->writeLOG ('xx>' . $message . '<xx noMessage = ' . strval($noMessage));
		}
		return $noMessage;
	}
	
	/**
	 * Echo the post panel for a registered site into the page output.
	 * @global type $user_ID
	 * @param type $post_ID
	 */
	private function admin_include_PostPanelRegisteredSite($post_ID) {
		if (empty($post_ID)) {
			$this->status_message = 'no_id';
			if ($this->logger != null && $this->logger->isDebug()) {
				$this->logger->writeLOG("admin_include_PostPanelRegisteredSite no post id");
			}
		} else {
			$this->loadPostInfo($post_ID);
		}
		if ($this->isEmptyMessage($this->status_message)) {
			$this->echoAdminPostPanelWithNoStatusMessage();
		} else {
			$this->echoAdminPostPanelWithStatusMessage();
		}
	}
	
	private function echoAdminPostPanelWithNoStatusMessage() {
		global $user_ID; // wordpress user ID for logged in user
		echo $this->plugFile($this->plugins_path . 'html/admin_post_panel_no_messages.html', 
			array(
				'site_id' => $this->site_ID, 'user_id' => $user_ID, 'article_id' => $this->article_ID,
				'locked' => $this->locked, 'fraxions_cost' => $this->fraxions_cost,
				'lock_value' => ($this->locked == 'true' ? 'locked' : 'unlocked'),
				'plugins_url' => plugins_url ( 'fraxion' ),
				'edit_post_info_url' => $this->urlProvider->getEditPostInfoURL($this->site_ID, $this->article_ID, $this->post_title),
				'site_url' => get_option ( 'siteurl' ),
				'resource_list' => $this->resourceController->getResourceListHTML($this->article_ID) ));
	}
	
	private function echoAdminPostPanelWithStatusMessage() {
		global $user_ID; // wordpress user ID for logged in user
		$panelHTML = $this->plugFile($this->plugins_path . 'html/admin_post_panel_messages.html', 
			array(
				'site_id' => $this->site_ID, 'user_id' => $user_ID, 'article_id' => $this->article_ID,
				'locked' => $this->locked, 'fraxions_cost' => $this->fraxions_cost,
				'status_message' => $this->status_messages [$this->status_message],
				'plugins_url' => plugins_url ( 'fraxion' ),
				'edit_post_info_url' => $this->urlProvider->getEditPostInfoURL($this->site_ID, $this->article_ID, $this->post_title),
				'site_url' => get_option ( 'siteurl' ),
				'resource_list' => $this->resourceController->getResourceListHTML($this->article_ID) ));
		echo $panelHTML;
	}

	/**
	 * 
	 * @param type $post_ID a non null, non empty post_ID object with ID and post_title fields.
	 */
	private function loadPostInfo($post_ID) {
		$this->article_ID = $this->getActualArticleId($post_ID);
		$this->post_title = $post_ID->post_title;
		$urlToSend = $this->urlProvider->getSiteArticleSettingsUrl($this->site_ID, $this->article_ID);
		$frax_dom = $this->fraxService->get($urlToSend);
		self::usePostInfoReply($frax_dom);
	} // end loadPostInfo
	
	private function usePostInfoReply($frax_dom) {
		$reply = $frax_dom->getElementsByTagName ( 'reply' );
		if ($reply->length > 0) {
			if ($reply->item ( 0 )->hasAttribute ( 'idcon' )
					&& $reply->item ( 0 )->getAttribute ( 'idcon' ) == 'false') {
				$this->status_message = 'editLock';
			} elseif ($reply->item ( 0 )->hasAttribute ( 'lock' )
					&& $reply->item ( 0 )->getAttribute ( 'lock' ) == 'true') {
				$this->locked = 'true';
				$this->fraxions_cost = $reply->item ( 0 )->getAttribute ( 'cost' );
			}
		} else { // reply <= 0
			$error = $frax_dom->getElementsByTagName ( 'error' );
			if ($error->length > 0) {
				$this->status_message = "! ".$error->item ( 0 )->firstChild->nodeValue;
			} else {
				$this->status_message = 'noServ';
			} // end if has 'lock'
		} // end if
	}
	
	private function getActualArticleId($post_ID) {
		$the_post = wp_is_post_revision ( $post_ID->ID );
		if ($the_post) {
			return $the_post;
		} else {
			return $post_ID->ID;
		}
	} // end getActualArticleId
	
	private function plugFile($filepath, $values) {
		$filecontent = file_get_contents ( $filepath );
		
		if ($this->logger != null && $this->logger->isDebug ()) {
			$this->logger->writeLOG ( "plugFile filepath=" . $filepath);
			foreach ($values as $key => $value) {
				$this->logger->writeLOG ( "plugFile value " . $key . "=" . $value);
			}
			$this->logger->writeLOG ( "plugFile filecontent=" . $filecontent);
		}
		
		$finalFilecontent = $this->plugInArrayValues($filecontent, $values);
		if ($this->logger != null && $this->logger->isDebug ()) {
			$this->logger->writeLOG ( "plugFile post finalFilecontent=" . $finalFilecontent);
		}
		
		return $finalFilecontent;
	} // end plugFile
	
	private function plugInArrayValues($filecontent, $values) {
		if (isset($filecontent) && ! is_null ( $values )) {
			if ($this->logger != null && $this->logger->isDebug ()) {
				$this->logger->writeLOG ( "plugInArrayValues start");
			}
			foreach ($values as $key => $value) {
				if ($value == NULL) {
					$value = "";
				}
				if ($this->logger != null && $this->logger->isDebug ()) {
					$this->logger->writeLOG ( "plugInArrayValues key " . $key . "=" . $value);
				}
				$filecontent = str_replace ( '{'.$key.'}', $value, $filecontent );
			}
		}
		return $filecontent;
	}
} // end class FraxionAdminArticleDisplay
?>
