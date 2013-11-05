<?php
/**
* Business logic for lockable resources.
*/
class FraxionResourceController {
	private $fraxion_site_id; // Id given to this site when registered with Fraxion.
	private $fraxionArticleLogic; // Provides knowledge about articles resources are attached to
	private $fraxionResourceService; // handles persistant data about resources
	private $logger = null;
	private $const_option_fraxion_site_id = 'fraxion_site_id'; // The database option name for the saved Site Id given at
	                                                           // 	registration.
	private $const_cookie_name_fraxion_fut = 'fraxion_fut'; // index to the fut value in the cookie.
	public function __construct(
			FraxionArticleLogic $fraxionArticleLogic, 
			FraxionResourceService $fraxionResourceService) {
		if ($fraxionArticleLogic == null)
			FraxionErrorPageImpl::fatalError ( "fraxionArticleLogic is null", $this->logger );
		if ($fraxionResourceService == null)
			FraxionErrorPageImpl::fatalError ( "fraxionResourceService is null", $this->logger );
		$this->fraxionArticleLogic = $fraxionArticleLogic;
		$this->fraxionResourceService = $fraxionResourceService;
		
		$this->logger = FraxionLoggerImpl::getLogger ( "FraxionResourceController" );
		$this->logger->writeLOG( "[__construct]");
	} // end __construct
	
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
	public function my_plugin_query_vars(
			$vars) {
		// action names
		$vars [] = 'frax_resource';
		$vars [] = 'frax_res_list_for_post';
		$vars [] = 'frax_new_resource';
		$vars [] = 'frax_upload_resource_form';
		$vars [] = 'frax_show_resource_code';
		
		// Field Names
		$vars [] = 'file1';
		$vars [] = 'file2';
		$vars [] = 'forPostId';
		$vars [] = 'force';
		$vars [] = 'for_name';
		
		return $vars;
	}
	public function doFraxResourceRequest(
			$wp) {
		// see if a request is for our getr function and handle it
		if (array_key_exists ( 'frax_resource', $wp->query_vars )) {
			try {
				self::doGetFraxResource ( $wp );
			} catch ( Exception $e ) {
				echo 'Caught exception: ', $e->getMessage (), "\n";
			}
			exit ( 0 );
		} else {
			if (array_key_exists ( 'frax_new_resource', $wp->query_vars )) {
				$this->logger->writeLOG( "doFraxResourceRequest : frax_new_resource start");
				if (self::isNewResourcePermissions ( $wp ) && self::isValidNewResourceInput ( $wp )) {
					$resourceID = self::newResourceDatabaseEntry ( $wp );
					$resourceDir = self::storeNewResourceFiles ( $wp, $resourceID );
					try {
						$theName = self::getNewResourceName ( $wp );
						$this->echoResourceUseSnippet($theName);
					} catch ( Exception $e ) {
						echo 'Caught exception: ', $e->getMessage (), "\n";
					}
				}
				$this->logger->writeLOG( "doFraxResourceRequest : frax_new_resource end");
				exit ( 0 );
				// End if 'frax_new_resource'
			} elseif (array_key_exists ( 'frax_upload_resource_form', $wp->query_vars )) {
				$this->logger->writeLOG( "doFraxResourceRequest : frax_upload_resource_form start");
				
				$postid = $wp->query_vars ['forPostId'];
				
				$form_content = file_get_contents ( PluginsPathImpl::get () . 'html/' . 'fraxion_upload_resource_form.html' );
				$form_content = str_replace ( '{postId}', $postid, $form_content );

				echo $form_content;
				
				$this->logger->writeLOG( "doFraxResourceRequest : frax_upload_resource_form end postId=" . $postid);
				exit ( 0 );
				// End if 'frax_upload_resource_form'
			} elseif (array_key_exists ( 'frax_res_list_for_post', $wp->query_vars )) {
				$postid = $wp->query_vars ['frax_res_list_for_post'];
				$listContent = $this->getResourceListHTML($postid);
				echo $listContent;
				exit ( 0 );
				// End if 'frax_res_list_for_post'
			} elseif (array_key_exists ( 'frax_show_resource_code', $wp->query_vars )) {
				$theName = $wp->query_vars ['for_name'];
				$this->echoResourceUseSnippet($theName);
				exit ( 0 );
			}
		} // else not a frax_resource call
	} // end doFraxResourceRequest
	
	/** Show the user the html code for including a link to the named resource */
	private function echoResourceUseSnippet($theName) {
		echo '<html><body><h2>File Attachment Loaded</h2>';
		echo '<p>Attached file name is <strong>' . $theName . '</strong></p>';
		echo '<p>Copy the following code into your content to make a link to this file.</p>';
		echo '<form><textarea rows="2" cols="50">';
		echo '<a href="/index.php?frax_resource=' . $theName . '">' . $theName . '</a>';
		echo '</textarea></form>';
		echo '</body></html>';
	}
	
	/**
	 * Is the information passed for a new resource correct?
	 * Includes not clashing with existing data and files actually being uploaded.
	 */
	private function isValidNewResourceInput(
			$wp) {
		$this->logger->writeLOG(  "isValidNewResourceInput - start");
			// both full and snippet files present, not empty and no upload errors
		if ($_FILES ["file1"] ["size"] == 0) {
			FraxionErrorPageImpl::clientError ( "Full version file is empty","", $this->logger );
		}
		if ($_FILES ["file2"] ["size"] == 0) {
			FraxionErrorPageImpl::clientError ( "Snippet version file is empty","", $this->logger );
		}
		if ($_FILES ["file1"] ["error"] > 0) {
			FraxionErrorPageImpl::clientError ( "Error with full version file","Code:" . $_FILES ["file1"] ["error"], 
					$this->logger );
		}
		if ($_FILES ["file2"] ["error"] > 0) {
			FraxionErrorPageImpl::clientError ( "Error with snippet version file","Code:" . $_FILES ["file2"] ["error"], 
					$this->logger );
		}
		// both files have same extension
		$fullVersExtension = end ( explode ( ".", $_FILES ["file1"] ["name"] ) );
		if (empty ( $fullVersExtension )) {
			FraxionErrorPageImpl::clientError (
					"Missing Extension",
					"File name " . $_FILES ["file1"] ["name"] . " has no file type extension (e.g. JPEG).", 
					$this->logger );
		}
		$snipVersExtension = end ( explode ( ".", $_FILES ["file2"] ["name"] ) );
		if (empty ( $snipVersExtension )) {
			FraxionErrorPageImpl::clientError (
					"Missing Extension",
					"File name " . $_FILES ["file2"] ["name"] . " has no file type extension (e.g. JPEG).", 
					$this->logger );
		}
		if ($fullVersExtension != $snipVersExtension) {
			FraxionErrorPageImpl::clientError (
					"Extensions Dont Match", 
					"Snippet and full version file type extensions do not match (" . $fullVersExtension . " and " . $snipVersExtension . ")", 
					$this->logger );
		}
		$extensionToConvert = $fullVersExtension;
		$fileMimeType = null;
		foreach ( wp_get_mime_types () as $exts => $mime ) {
			if (preg_match ( '!^(' . $exts . ')$!i', $extensionToConvert )) {
				$fileMimeType = $mime;
				break;
			}
		}
		if ($fileMimeType == null) {
			FraxionErrorPageImpl::clientError ( "Unknown Type", "File type " . $fullVersExtension . " not recognised ", $this->logger );
		}
		$mimeAllowed = false;
		$allowedMimes = get_allowed_mime_types ();
		foreach ( $allowedMimes as $type => $mime ) {
			if ($mime == $fileMimeType) {
				$mimeAllowed = true;
				break;
			}
		}
		if (! $mimeAllowed) {
			FraxionErrorPageImpl::clientError ( "Illegal File Type", "File type " . $fullVersExtension . " not allowed in upload.", 
					$this->logger );
		}
		
		// both files fit max upload size
		// ????????????
		$this->fraxionResourceService->ensureDataBaseTable ( $wp );
		
		// build friendly name
		$newResName = self::getNewResourceName ( $wp );
		// check friendly name does not exist for a resource in database
		if ($this->fraxionResourceService->isResourceNameInUse ( $newResName )) {
			FraxionErrorPageImpl::clientError ( "Duplicate Name", 'The resource ' . $newResName . ' is already in the database.', $this->logger );
		}
		$this->logger->writeLOG( "isValidNewResourceInput - end - true");
		return true;
	} // end isValidNewResourceInput
	
	/**
	 * Get the friendly url text for the database from the full version file upload.
	 * Never too long :)
	 */
	private function getNewResourceName(
			$wp) {
		$massagedFileName = str_replace ( ' ', '_', 
				str_replace ( '-', '_', strtolower ( strip_tags ( $_FILES ["file1"] ["name"] ) ) ) );
		while ( true ) {
			if (strpos ( $massagedFileName, '__' ) === false)
				break;
			$massagedFileName = str_replace ( '__', '_', $massagedFileName );
		}
		if ($massagedFileName->length > 190) {
			return substr ( $massagedFileName, 0, 80 ) . '_' . substr ( $massagedFileName, 
					$massagedFileName->length - 81, 80 );
		} else {
			return $massagedFileName;
		}
	} // end getNewResourceName
	
	/**
	 * Get the mime type for the database from the full version file upload.
	 */
	private function getNewResourceMimeType(
			$wp) {
		// $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
		// $fileMimeType finfo_file($finfo, $_FILES["file1"]["name"]);
		// finfo_close($finfo);
		$filetype = wp_check_filetype ( $_FILES ["file1"] ["name"] );
		return $filetype ['type'];
	} // end getNewResourceMimeType
	
	/**
	 * Create the directory and move and rename the files into place.
	 */
	private function storeNewResourceFiles(
			$wp, 
			$resourceID) {
		$this->logger->writeLOG( "storeNewResourceFiles - start resourceID=" . $resourceID);
		$resourceDir = self::ensureResourceDirectory ( self::getFraxResourceDirPath (), $resourceID );
		self::moveUploadedResourcesToDirectory ( $resourceDir, $resourceID );
		$this->logger->writeLOG( "storeNewResourceFiles - end resourceDir=" . $resourceDir);
		return $resourceDir;
	} // end storeNewResourceFiles
	
	/**
	 * get the uploaded files and put them into the directory
	 * as <resourceID>_snip and <resourceID>_full respectively.
	 */
	private function moveUploadedResourcesToDirectory(
			$resourceDir, 
			$resourceID) {
		$baseNewFilePath = '' . $resourceDir . DIRECTORY_SEPARATOR . $resourceID;
		$this->logger->writeLOG( "moveUploadedResourcesToDirectory - start for " . $baseNewFilePath);
		
		$fullFilePath = $baseNewFilePath . '_full';
		self::moveAnUploadFile ( "file1", $baseNewFilePath . '_full' );
		self::moveAnUploadFile ( "file2", $baseNewFilePath . '_snip' );
		$this->logger->writeLOG( "moveUploadedResourcesToDirectory - end for " . $baseNewFilePath);
	} // end moveUploadedResourcesToDirectory
	
	private function moveAnUploadFile(
			$fileKey, 
			$filePath) {
		$uploadTempName = $_FILES [$fileKey] ["tmp_name"];
		$this->logger->writeLOG( "moveAnUploadFile - fileKey=" . $fileKey . " uploadTempName=" . $uploadTempName . " filePath=" . $filePath);
		if (! move_uploaded_file ( $uploadTempName, $filePath )) {
			FraxionErrorPageImpl::fatalError ( "Could not move uploaded file " . $uploadTempName . "<br/> to " . $filePath, 
					$this->logger );
		}
	} // end moveAnUploadFile
	
	/**
	 * Create the database resource record for the request and return the new resource ID.
	 * Validation is already done.
	 */
	private function newResourceDatabaseEntry(
			$wp) {
		$this->logger->writeLOG( "newResourceDatabaseEntry - start");
		global $wpdb;
		if ($wpdb == null) {
			FraxionErrorPageImpl::fatalError ( "Database unavailable.", $this->logger );
		} else {
			$resourcePostID = null;
			if (array_key_exists ( 'forPostId', $wp->query_vars )) {
				$resourcePostID = $wp->query_vars ['forPostId'];
			}
			$this->logger->writeLOG( "resourcePostID=" . $resourcePostID);
			$newResName = self::getNewResourceName ( $wp );
			$this->logger->writeLOG( "newResName=" . $newResName);
			$newMimeType = self::getNewResourceMimeType ( $wp );
			$this->logger->writeLOG( "newMimeType=" . $newMimeType);
			
			$downloadFileName = null;
			$downloadSnippetFileName = null;
			if (array_key_exists ( 'force', $wp->query_vars )) {
				$force = $wp->query_vars ['force'];
				if ($force == 'true') {
					$downloadFileName = $newResName;
					$lastDotPos = strripos ( $newResName, '.' );
					$downloadSnippetFileName = null;
					if ($lastDotPos) {
						$downloadSnippetFileName = substr ( $newResName, 0, $lastDotPos ) . "_snippet" . substr ( 
								$newResName, $lastDotPos );
					} else {
						$downloadSnippetFileName = $newResName . "_snippet";
					}
				}
			}
			$this->logger->writeLOG( "downloadFileName=" . $downloadFileName);
			$this->logger->writeLOG( "downloadSnippetFileName=" . $downloadSnippetFileName);
			
			$newID = $this->fraxionResourceService->insertNewResourceDatabaseEntry ( $resourcePostID, $newResName, 
					$newMimeType, $downloadFileName, $downloadSnippetFileName );
			
			if (! $newID) {
				FraxionErrorPageImpl::fatalError ( "Failed to save data for resource. " . $newResName, $this->logger );
			}
		}
		$this->logger->writeLOG( "newResourceDatabaseEntry - end");
		
		return $newID;
	} // end newResourceDatabaseEntry
	
	/**
	 * Does the current user have all necessary permissions to
	 * create a resource for the current post?
	 * Error messages will be set in the response if the answer is no and the system will exit.
	 */
	private function isNewResourcePermissions(
			$wp) {
		$this->logger->writeLOG( "isNewResourcePermissions - start");
		if (! current_user_can ( 'upload_files' )) {
			$this->logger->writeLOG( "isNewResourcePermissions - end = false - user no upload permission" . self::getQuickUserInfo ());
			// real auth required is in wp_app
			FraxionErrorPageImpl::auth_required ( __ ( 'You do not have permission to upload files.' ), $this->logger );
		}
		if (array_key_exists ( 'forPostId', $wp->query_vars )) {
			$targetPostId = $wp->query_vars ['forPostId'];
			if (! current_user_can ( 'edit_post', $targetPostId )) {
				$this->logger->writeLOG( "isNewResourcePermissions - end = false - user no edit post " . $targetPostId . " permission" . self::getQuickUserInfo ());
				// real auth required is in wp_app
				FraxionErrorPageImpl::auth_required ( __ ( 'You do not have permission to edit this post. (' . $targetPostId . ')' ), 
						$this->logger );
			}
		} else {
			FraxionErrorPageImpl::clientError ( __ ( 'No post targetted.' ), '', $this->logger );
		}
		$this->logger->writeLOG( "isNewResourcePermissions - end = true");
		return true;
	} // end isNewResourcePermissions
	
	/**
	 * Test current user and return either "no current user"
	 * or current user's email and display_name.
	 */
	private function getQuickUserInfo() {
		$aUser = wp_get_current_user ();
		$qUserInfo = "x";
		if ($aUser == null) {
			$qUserInfo = "no current user";
		} else {
			$qUserInfo = $aUser->user_email . " " . $aUser->display_name;
		}
		return $qUserInfo;
	} // end getQuickUserInfo
	
	/**
	 * Get a string that gives the file system path to the directory
	 * that contains the resources.
	 * There is no path seoerator at the
	 * end of the string.
	 */
	private function getFraxResourceDirPath() {
		return self::getFraxResourceDirPathWithFolderId($this->fraxionResourceService->getResourceFolderId());
	} // end getFraxResourceDirPath
	
	private function getFraxResourceDirPathWithFolderId($fraxResFolderId) {
		$fraxResFolderIdForSite = PluginsPathImpl::getPluginsDirContainer () . 'frax_resources';
		if ($fraxResFolderId != false) {
			$fraxResFolderIdForSite = $fraxResFolderIdForSite . '_' . $fraxResFolderId;
		}
		return $fraxResFolderIdForSite;
	} // end getFraxResourceDirPathWithFolderId
	
	
	/**
	 *
	 * @param unknown $wp        	
	 */
	private function doGetFraxResource(
			$wp) {
		$this->logger->writeLOG( "doGetFraxResource -- start");
		$results = $this->fraxionResourceService->getResourceEntryByName ( $wp->query_vars ['frax_resource'] );
		if ($results != null) {
			$showFullVersion = self::isShowFullResource ( $results [0]->resource_post_ID ); // (rand(1,2) == 2);
			$forceDownload = false;
			$forceFilename = null;
			$filePath = self::getFraxResourceDirPath () . DIRECTORY_SEPARATOR;
			$extraPath = self::resourceIDToFilePath ( $results [0]->resource_ID );
			if (strlen ( $extraPath ) > 0) {
				$filePath .= $extraPath;
			}
			$filename = $results [0]->resource_ID . '_';
			$mimetype = 'text/plain';
			if ($showFullVersion) {
				if ($results [0]->download_file_name != null) {
					$forceDownload = true;
					$forceFilename = $results [0]->download_file_name;
				}
				$filename = $filename . 'full';
				$mimetype = $results [0]->resource_mime_type;
			} else {
				if ($results [0]->download_snippet_filename != null) {
					$forceDownload = true;
					$forceFilename = $results [0]->download_snippet_filename;
				}
				$filename = $filename . 'snip';
				$mimetype = $results [0]->resource_snippet_mime_type;
			}
			$this->logger->writeLOG(  "path " . $filePath . $filename . " - " . $mimetype);
			header ( "Content-type:" . $mimetype );
			if ($forceDownload) {
				header ( "Content-Disposition: Attachment;filename=" . $forceFilename );
			}
			readfile ( $filePath . $filename );
		} else {
			echo '<br/>Could not find ' . $wp->query_vars ['frax_resource'] . '<br/>';
		}
		$this->logger->writeLOG( "doGetFraxResource -- end");
	} // end doGetFraxResource

	public function getResourceListHTML($postId) {
		$showCodeDialogLink =	'<a href="#" title="Fraxion Payments New Locked Resource" ';
		$showCodeDialogLink .=	'onclick="';
		$showCodeDialogLink .= 	'jQuery(\'#fp_res\').html(\'<iframe></iframe>\');';
		$showCodeDialogLink .= 	'jQuery(\'#fp_res iframe\').attr(';
		$showCodeDialogLink .= 	'{\'width\':\'100%\',\'height\':\'460\',\'src\':\'' . get_option ( 'siteurl' ) . '/index.php?frax_show_resource_code=1&for_name=RESNAME\'';
		$showCodeDialogLink .= 	'});initmb(); sm(\'box2\',600,520);';
		$showCodeDialogLink .= '">RESNAME</a>';
		
		$reply = "";
		$result = $this->fraxionResourceService->getResourceNamesForPost($postId);
		if ($result != null && count ( $result ) > 0) {
			$reply = "<div><strong>Attached Files</strong>";
			foreach ($result as $resRecord) {
				$reply .= "<br/>".str_replace('RESNAME', $resRecord->resource_friendly_name, $showCodeDialogLink);
			}
			$reply .= "<br/></div>";
		}
		return $reply;
	} // end getResourceListHTML
	
	/**
	 * Make sure the correct folder structure exists for the given resource ID
	 * and return the path to the directory that will contain it.
	 */
	private function ensureResourceDirectory(
			$rootDirPath, 
			$resID) {
		if (! is_dir ( $rootDirPath ))
			FraxionErrorPageImpl::fatalError ( 'no directory for rootDirPath=' . $rootDirPath, $this->logger );
		if ($resID == null)
			FraxionErrorPageImpl::fatalError ( 'no resID provided', $this->logger );
			
			// error if root is null or not a folder
		$currentLocationPath = $rootDirPath;
		$resIDString = "" . $resID;
		$this->logger->writeLOG( 'ensureResourceDirectory - rootDirPath=' . $rootDirPath . ' resIDString=' . $resIDString);
		$workarray = str_split ( $resIDString, 2 );
		$arrlength = count ( $workarray );
		if ($arrlength > 1) {
			for($x = 0; $x < $arrlength - 1; $x ++) {
				// Advance the current location and see if the folder exists
				// in the new current location, if not then create it
				$currentLocationPath = $currentLocationPath . DIRECTORY_SEPARATOR . $workarray [$x];
				$this->logger->writeLOG( 'x=' . $x . ' currentLocationPath=' . $currentLocationPath);
				self::ensureDirectory($currentLocationPath);
			}
		}
		$this->logger->writeLOG( 'ensureResourceDirectory - end - currentLocationPath=' . $currentLocationPath);
		return $currentLocationPath; // where to put the files
	} // end ensureResourceDirectory
	
	/**
	 * Make sure the given path is an actual directory, building it if necessary.
	 * @param unknown $directoryPath The path that should name a directory.
	 */
	private function ensureDirectory($directoryPath) {
		if (! is_dir ( $directoryPath )) {
			$this->logger->writeLOG(  '-not already a directory ' . $directoryPath);
			if (mkdir ( $directoryPath )) {
				$this->logger->writeLOG( '-created directory ' . $directoryPath);
			} else {
				$this->logger->writeLOG( '-failed to create directory ' . $directoryPath);
			}
		} else {
			$this->logger->writeLOG(  '-existing directory ' . $directoryPath);
		}
	}
	
	/**
	 * The $resIDString is an integer resource primary key, nnnnn, and the value
	 * returned is the folder heirarchy path for that ID, nn/nn/ (pairs except last pair)
	 */
	private function resourceIDToFilePath(
			$resIDString) {
		$thePath = "";
		$workarray = str_split ( $resIDString, 2 );
		$arrlength = count ( $workarray );
		$this->logger->writeLOG( 'resourceIDToFilePath - resIDString=' . $resIDString . ' arrlength=' . $arrlength);
		if ($arrlength > 1) {
			for($x = 0; $x < $arrlength - 1; $x ++) {
				$thePath = $thePath . $workarray [$x] . DIRECTORY_SEPARATOR;
				$this->logger->writeLOG( 'x=' . $x . ' workarray[x]=' . $workarray [$x] . " thePath=" . $thePath);
			}
		}
		return $thePath;
	} // end resourceIDToFilePath
	
	/**
	 * There must be a site Id, and the session needs a FUT, and then we
	 * check with Fraxion as to the FUT being a logged in user and if so
	 * have they unlocked the resource or an article it is attached to?
	 */
	private function isShowFullResource(
			$resourcePostId) {
		$showFullResource = false;
		self::loadSiteId ();
		if (! empty ( $this->fraxion_site_id )) { // there is a site id
			$theFUT = self::getFUT ();
			if (! empty ( $theFUT )) { // there is a fut
				if (! empty ( $resourcePostId )) { // the resource has an article Id
					$showFullResource = $this->fraxionArticleLogic->isArticleUnlockedForUser ( $theFUT, 
							$resourcePostId );
				} else {
					// TODO - independant resource - ask fraction about user/resource lock status
				}
			}
		}
		return $showFullResource;
	} // isShowFullResource
	
	/**
	 *
	 * @return string FUT value or NULL
	 */
	private function getFUT() {
		if (array_key_exists ( $this->const_cookie_name_fraxion_fut, $_COOKIE )) {
			$this->fut = $_COOKIE [$this->const_cookie_name_fraxion_fut];
			return $this->fut;
		} else {
			return null;
		}
	}
	
	public function install_resources() {
		$this->logger->writeLOG( "install_resources - start");
		self::setupLatestResources();
		$this->logger->writeLOG( "install_resources - end");
	}
	
	public function update_resources() {
		$this->logger->writeLOG( "update_resources - start");
		self::setupLatestResources();
		$this->logger->writeLOG( "update_resources - end");
	}
	
	private function setupLatestResources() {
		$this->logger->writeLOG( "setupLatestResources - start");
		
		self::loadSiteId ();
		if (! empty ( $this->fraxion_site_id )) { // there is a site id
			if ($this->fraxionResourceService->getResourceFolderId() == false) {
				$folderId = rand(1000000000, 9999999999);
				$this->logger->writeLOG( "New Folder Id " . $folderId);
				self::ensureDirectory(self::getFraxResourceDirPathWithFolderId($folderId));
				$this->fraxionResourceService->setResourceFolderId($folderId);
			}
			$currentDBVersion = $this->fraxionResourceService->getCurrentDBResourceTableVersion();
			if ( ! $this->fraxionResourceService->isRequiredTableVersion($currentDBVersion)) {
				$this->logger->writeLOG( "Update DB from version " . $currentDBVersion);
				$this->fraxionResourceService->updateDatabase();
				$this->fraxionResourceService->setRequiredDBResourceTableVersion($currentDBVersion);
			}
		}
		
		$this->logger->writeLOG( "setupLatestResources - end");
	}
} // end class FraxionResourceController
?>