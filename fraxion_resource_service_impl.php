<?php
include ("fraxion_resource_service_interface.php");
/**
* Database operations for lockable resources.
*/
class FraxionResourceServiceImpl implements FraxionResourceService {
	
	private $logger = null;
	private $resourceTableName;
	private $const_base_resource_table_name = 'frax_resource'; // base name that is added to servers prefix
	private $const_required_table_version = '1'; // The version of the resource table structure required by this version of the code
	private $const_option_fraxion_resource_table_vers = 'fraxion_restabv'; // The database option name for the saved resource table version
	private $const_option_fraxion_resf_id = 'fraxion_resf_id';
	
	public function __construct() {
		global $wpdb;
		$this->logger = FraxionLoggerImpl::getLogger ( "FraxionResourceService" );
		$this->logger->writeLOG("[__construct]");
		$this->resourceTableName = $wpdb->prefix . $this->const_base_resource_table_name;
		$this->logger->writeLOG("[__construct end table:" . $this->resourceTableName . "]");
	} // end __construct
	
	public function ensureDataBaseTable(
			$wp) {
	} // end ensureDataBaseTable
	
	/**
	 * Is the given name already in the database?
	 * 
	 * @param unknown $newResName        	
	 * @return boolean true if name matched in database
	 */
	public function isResourceNameInUse(
			$newResName) {
		global $wpdb;
		
		$this->logger->writeLOG("isResourceNameInUse:" . $newResName . " - start");
		
		if ($wpdb == null) {
			FraxionErrorPageImpl::fatalError ( "Database unavailable.", $this->logger );
		} else {
			$theQ = 'SELECT resource_ID' . ' FROM ' . $this->resourceTableName . ' WHERE resource_friendly_name="' . $newResName . '"';
			$results = $wpdb->get_results ( $theQ );
			$inUse = ($wpdb->num_rows > 0);
// 			if ($inUse) {
// 				$arrlength=count($results);
// 				$this->logger->writeLOG("array dump :" . $results . " length=" . $arrlength);
// 				for($x=0;$x<$arrlength;$x++) {
// 					$this->logger->writeLOG($results[$x]);
// 	  			}
// 			}
			$this->logger->writeLOG("isResourceNameInUse:" . $newResName . " - end = " . $inUse);
			
			return $inUse;
		}
	} // end isResourceNameInUse
	
	/**
	 * return a structure with the data or null if not found.
	 * elements returned:
	 * resource_ID, resource_post_ID, resource_friendly_name, resource_mime_type, resource_snippet_mime_type,
	 * download_file_name, download_snippet_filename
	 */
	public function getResourceEntryByName(
			$resourceName) {
		global $wpdb;
		if ($wpdb == null) {
			FraxionErrorPageImpl::fatalError ( "Database unavailable.", $this->logger );
		} else {
			// echo '<br>we are in<br>';
			$theQ = 'SELECT resource_ID, resource_post_ID, resource_friendly_name, resource_mime_type, resource_snippet_mime_type, download_file_name, download_snippet_filename FROM ';
			$theQ = $theQ . $this->resourceTableName . ' WHERE resource_friendly_name="' . $resourceName . '"';
			// echo $theQ;
			$results = $wpdb->get_results ( $theQ );
			if ($wpdb->num_rows > 0) {
				return $results;
			} else {
				return null;
			}
		}
	} // end getResourceEntryByName
	
	/**
	 * @param $postId the id of the post that the resources are for
	 * @return Array of resource names, or null if none found
	 */
	public function getResourceNamesForPost(
			$postId) {
		global $wpdb;
		if ($wpdb == null) {
			FraxionErrorPageImpl::fatalError ( "Database unavailable.", $this->logger );
		} else {
			// echo '<br>we are in<br>';
			$theQ = 'SELECT resource_friendly_name' . ' FROM ' . $this->resourceTableName . ' WHERE resource_post_ID="' . $postId . '"';
			// echo $theQ;
			$results = $wpdb->get_results ( $theQ );
			if ($wpdb->num_rows > 0) {
				return $results;
			} else {
				return null;
			}
		}
	} // end getResourceEntryByName
	
	/**
	 * Create the database resource record for the request and return the new resource ID.
	 * Validation is assumed to be already done.
	 */
	public function insertNewResourceDatabaseEntry(
			$resourcePostID, 
			$newResName, 
			$newMimeType, 
			$downloadFileName, 
			$downloadSnippetFileName) {
		global $wpdb;
		
		$this->logger->writeLOG('insertNewResourceDatabaseEntry - start');
		
		if ($wpdb == null) {
			FraxionErrorPageImpl::fatalError ( "Database unavailable.", $this->logger );
		} else {
			$msg = 'insertNewResourceDatabaseEntry - values to insert :' . 'Insert resourcePostID=' . $resourcePostID;
			$msg .= '\n newResName=' . $newResName . '\n newMimeType=' . $newMimeType . '\n downloadFileName=' . $downloadFileName;
			$msg .= '\n downloadSnippetFileName=' . $downloadSnippetFileName;
			$this->logger->writeLOG($msg);
			
			// Table name should include this wp's prefix value (or does insert add that?) - need a mechanism for making
			// that correct
			$fieldsArray = array (
					"resource_post_ID" => $resourcePostID,
					"resource_friendly_name" => $newResName,
					"resource_mime_type" => $newMimeType,
					"resource_snippet_mime_type" => $newMimeType 
			);
			$formatsArray = array (
					"%d",
					"%s",
					"%s",
					"%s" 
			);
			if (! ($downloadFileName == null || strlen ( $downloadFileName ) == 0)) {
				$fieldsArray = array_merge ( $fieldsArray, array (
						"download_file_name" => $downloadFileName 
				) );
				$formatsArray = array_merge ( $formatsArray, array (
						"%s" 
				) );
			}
			if (! ($downloadSnippetFileName == null || strlen ( $downloadSnippetFileName ) == 0)) {
				$fieldsArray = array_merge ( $fieldsArray, 
						array (
								"download_snippet_filename" => $downloadSnippetFileName 
						) );
				$formatsArray = array_merge ( $formatsArray, array (
						"%s" 
				) );
			}
			if ($this->logger != null && $this->logger->isDebug ()) {
				$this->logger->writeLOG('insertNewResourceDatabaseEntry - fieldsArray length is ' . count ( $fieldsArray )
					. ' - formatsArray length is ' . count ($formatsArray ));
				$outstr = '';
				foreach ( $fieldsArray as $x => $x_value ) {
					$outstr .= " [" . $x . ", " . $x_value . "]";
				}
				$this->logger->writeLOG($outstr);
				
				$arrlength = count ( $formatsArray );
				$outstr = '';
				for($x = 0; $x < $arrlength; $x ++) {
					$outstr .= " [" . $formatsArray [$x] . "]";
				}
				$this->logger->writeLOG($outstr);
			}
			$wpdb->insert ( $this->resourceTableName, $fieldsArray, $formatsArray );
			$newResID = $wpdb->insert_id;
			
			$this->logger->writeLOG('insertNewResourceDatabaseEntry - end newResID=' . $newResID);
			
			return $newResID;
		}
	} // end insertNewResourceDatabaseEntry
	
	public function getCurrentDBResourceTableVersion() {
		if (function_exists ( 'get_option' )) {
			return get_option ( $this->const_option_fraxion_resource_table_vers );
		} else {
			return false;
		}
	}
	public function setRequiredDBResourceTableVersion($currentDBVersion) {
		if ($currentDBVersion == false) {
			$this->logger->writeLOG('add required DB version as ' . $this->const_required_table_version);
			add_option($this->const_option_fraxion_resource_table_vers, $this->const_required_table_version);
		} elseif ($currentDBVersion != $this->const_required_table_version) {
			$this->logger->writeLOG('update required DB version from ' . $currentDBVersion . ' to ' . $this->const_required_table_version);
			update_option($this->const_option_fraxion_resource_table_vers, $this->const_required_table_version);
		}
	}
	
	public function getResourceFolderId() {
		if (function_exists ( 'get_option' )) {
			return get_option ( $this->const_option_fraxion_resf_id );
		} else {
			return false;
		}
	}
	
	public function setResourceFolderId($resFolderId) {
		add_option($this->const_option_fraxion_resf_id, $resFolderId);
	}
	
	public function isRequiredTableVersion($dbTableVers) {
		return $dbTableVers == $this->const_required_table_version;
	}
	
	public function updateDatabase() {
		global $wpdb;
		
		$sql = "CREATE TABLE " . $this->resourceTableName . " (
		resource_ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		resource_post_ID bigint(20) unsigned DEFAULT NULL,
		resource_friendly_name varchar(200) NOT NULL,
		resource_mime_type varchar(100) NOT NULL,
		resource_snippet_mime_type varchar(100) NOT NULL,
		download_file_name varchar(200) DEFAULT NULL,
		download_snippet_filename varchar(200) DEFAULT NULL,
		PRIMARY KEY  (resource_ID),
		UNIQUE KEY (resource_friendly_name)
		);";

		$this->logger->writeLOG('updateDatabase sql :' . $sql);
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   		dbDelta( $sql );
		
// 		You must put each field on its own line in your SQL statement.
// 		You must have two spaces between the words PRIMARY KEY and the definition of your primary key.
// 		You must use the key word KEY rather than its synonym INDEX and you must include at least one KEY.
// 		You must not use any apostrophes or backticks around field names.
//		http://codex.wordpress.org/Creating_Tables_with_Plugins
	}
} // end class FraxionResourceService
?>