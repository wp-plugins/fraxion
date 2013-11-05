<?php
/**
 * Database operations for lockable resources.
 */
interface FraxionResourceService {

	public function ensureDataBaseTable(
			$wp);

	/**
	 * Is the given name already in the database?
	 *
	 * @param unknown $newResName
	 * @return boolean true if name matched in database
	 */
	public function isResourceNameInUse(
			$newResName);

	/**
	 * return a structure with the data or null if not found.
	 * elements returned:
	 * resource_ID, resource_post_ID, resource_friendly_name, resource_mime_type, resource_snippet_mime_type,
	 * download_file_name, download_snippet_filename
	 */
	public function getResourceEntryByName(
			$resourceName);
	/**
	 * @param $postId the id of the post that the resources are for
	 * @return Array of resource names, or null if none found
	 */
	public function getResourceNamesForPost(
			$postId);

	/**
	 * Create the database resource record for the request and return the new resource ID.
	 * Validation is assumed to be already done.
	 */
	public function insertNewResourceDatabaseEntry(
			$resourcePostID,
			$newResName,
			$newMimeType,
			$downloadFileName,
			$downloadSnippetFileName);


	public function getCurrentDBResourceTableVersion();
	
	public function setRequiredDBResourceTableVersion($currentDBVersion);
	
	public function getResourceFolderId();

	public function setResourceFolderId($resFolderId);
	
	public function isRequiredTableVersion($dbTableVers);
	
	public function updateDatabase();
	
} // end interface FraxionResourceService
?>