<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Fabien Udriot <fabien.udriot@ecodev.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * A repository for extensions which does not extend Tx_Extbase_Persistence_Repository
 *
 * @copyright Copyright belongs to the respective authors
 */
class Tx_TerDoc_Domain_Repository_ExtensionRepository {
	const ERRORCODE_PROBLEMWHILEXTRACTINGMANUALSXW = 1;
	const ERRORCODE_COULDNOTEXTRACTABSTRACT = 6;
	const ERRORCODE_DOCMANUALSXWFOUNDINT3XARCHIVE = 13;
	const ERRORCODE_ERRORWHILEREADINGT3XFILE = 7;
	const ERRORCODE_T3XARCHIVECORRUPTED = 8;
	const ERRORCODE_ERRORWHILEUNCOMPRESSINGT3XFILE = 9;
	const ERRORCODE_CORRUPTEDT3XSTRUCTURENOFILESFOUND = 10;
	const ERRORCODE_FILEOFT3XISCORRUPTED = 11;
	const ERRORCODE_FILENOTFOUNDINT3XARCHIVE = 12;

	/**
	 * constructor
	 *
	 * @param array $settings options passed from the CLI
	 */
	public function __construct($settings, $arguments) {
		$this->settings = $settings;
		$this->arguments = $arguments;
		$this->loadStoragePid();
	}

	/*	 * ****************************************************
	 *
	 * Extension index functions
	 *
	 * **************************************************** */

		/**
		 * Load storage PID from ext conf
		 *
		 * @return void
		 */
		public function loadStoragePid() {
				if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ter_doc'])) {
						$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ter_doc']);
						if (!empty($config['storagePid'])) {
								$this->storagePid = (int) $config['storagePid'];
						}
				}
		}

	/**
	 * Checks if the extension index file (extensions.xml.gz) was modified
	 * since the last built of the extension index in the database.
	 *
	 * @return	boolean		TRUE if the index has changed
	 * @access	protected
	 */
	public function wasModified() {
		$oldMD5Hash = $currentMD5Hash = '';
		if (file_exists($this->settings['md5File'])) {
			$oldMD5Hash = file_get_contents($this->settings['md5File']);
		}

		return ($oldMD5Hash != $currentMD5Hash);
	}

	/**
	 * Reads the extension index file (extensions.xml.gz) and updates
	 * the the manual caching table accordingly.
	 *
	 * @return	object
	 */
	public function findAll() {
		// Transfer data from extensions.xml.gz to database:
		$unzippedExtensionsXML = implode('', @gzfile($this->settings['repositoryDir'] . 'extensions.xml.gz'));
		$extensions = simplexml_load_string($unzippedExtensionsXML);
		if (!is_object($extensions)) {
			throw new Exception('Exception thrown #1300783708: Error while parsing ' . $this->settings['repositoryDir'] . 'extensions.xml.gz', 1300783708);
		}

		return $extensions;
	}

	/**
	 * Reads the extension index file (extensions.xml.gz) and updates
	 * the the manual caching table accordingly.
	 *
	 * @return	boolean		TRUE if operation was successful
	 * @access	protected
	 */
	public function updateAll() {

		Tx_TerDoc_Utility_Cli::log('* Deleting cached manual information from database');

		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_terdoc_manuals', '1');

		$extensions = $this->findAll();

		$loop = 0;
		foreach ($extensions as $extension) {

			foreach ($extension as $version) {
				if (strlen($version['version'])) {
					$documentDir = Tx_TerDoc_Utility_Cli::getDocumentDirOfExtensionVersion($this->settings['documentsCache'], $extension['extensionkey'], $version['version']);
					$abstract = @file_get_contents($documentDir . 'abstract.txt');
					$language = @file_get_contents($documentDir . 'language.txt');

					$extensionsRow = array(
						'pid' => $this->storagePid,
						'extensionkey' => $extension['extensionkey'],
						'version' => $version['version'],
						'title' => $version->title,
						'language' => $language,
						'abstract' => $abstract,
						'modificationdate' => $version->lastuploaddate,
						'authorname' => $version->authorname,
						'authoremail' => $version->authoremail,
						't3xfilemd5' => $version->t3xfilemd5
					);
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_terdoc_manuals', $extensionsRow);
				}
			}

			// prevent the script to loop to many times in a development context
			// Otherwise will process more than 20000 extensions
			$loop++;
			if ($loop == $this->arguments['limit']) {
				break;
			}
		}

		// Create new MD5 hash:
		#t3lib_div::writeFile($this->settings['extensionFile'], md5_file($this->settings['repositoryDir'] . 'extensions.xml.gz'));
		Tx_TerDoc_Utility_Cli::log('* Manual DB index was sucessfully reindexed');

		return TRUE;
	}

	/**
	 * Update extension according to $extensionKey and $version
	 *
	 * @param	string	$extensionKey: Extension key of the extension
	 * @param	string	$version: Version number of the extension
	 * @param	array	$dataSet: dataSet that contains the data to store
	 */
	public function update($extensionKey, $version, $dataSet) {

		// Store abstract and language information:
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'tx_terdoc_manuals',
				'extensionkey="' . $extensionKey . '" AND version="' . $version . '"',
				array(
					'abstract' => $dataSet['abstract'],
					'language' => $dataSet['documentLanguage'],
				)
		);
	}

	/**
	 * Delete extension according to $extensionKey and $version
	 *
	 * @param	string	$extensionKey: Extension key of the extension
	 * @param	string	$version: Version number of the extension
	 * @return	void
	 */
	public function delete($extensionKey, $version) {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_terdoc_manuals', 'extensionkey="' . $extensionKey . '" AND version="' . $version . '"');
	}

	/**
	 * Log extension according to $extensionKey and $version
	 *
	 * @param	string	$extensionKey: Extension key of the extension
	 * @param	string	$version: Version number of the extension
	 * @param	array	$errorCodes
	 * @return	void
	 */
	public function log($extensionKey, $version, $errorCode) {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_terdoc_renderproblems', array('extensionkey' => $extensionKey, 'version' => $version, 'tstamp' => time(), 'errorcode' => $errorCode));
	}

	/**
	 * Prepare environment by deleting obsolete rendering problem log and cleaning up directories
	 *
	 * @param string $extensionKey
	 * @param string $version
	 * @return void
	 */
	public function prepare($extensionKey, $version) {

		// Computes the cache directory of the extension
		$documentDir = Tx_TerDoc_Utility_Cli::getDocumentDirOfExtensionVersion($this->settings['documentsCache'], $extensionKey, $version);

		// set directory to be created
		$directories = array('sxw', 'html_online');

		// Computes docbook directories
		$docBookVersions = explode(',', $this->settings['docbook_version']);
		foreach ($docBookVersions as $docBookVersion) {
			$directories[] = 'docbook' . $docBookVersion;
		}

		// Empty and create blank directory
		foreach ($directories as $directory) {
			if (is_dir($documentDir . $directory)) {
				Tx_TerDoc_Utility_Cli::removeDirRecursively($documentDir . $directory);
			}
			t3lib_div::mkdir_deep($documentDir, $directory);
		}

		// "docbook" is a special case -> symlink to the default docbook version
		if (is_dir($documentDir . 'docbook')) {
			Tx_TerDoc_Utility_Cli::removeDirRecursively($documentDir . 'docbook');
		} else if (file_exists($documentDir . 'docbook')) {
			unlink($documentDir . 'docbook');
		}

		@symlink($documentDir . 'docbook' . $this->settings['docbook_version_default'], $documentDir . 'docbook');

		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_terdoc_renderproblems', 'extensionkey="' . $extensionKey . '" AND version="' . $version . '"');
	}

	/**
	 * Clean up environement by removing temporary files
	 *
	 * @param string $extensionKey
	 * @param string $version
	 * @return void
	 */
	public function cleanUp($extensionKey, $version) {

		// Computes the cache directory of the extension
		$documentDir = Tx_TerDoc_Utility_Cli::getDocumentDirOfExtensionVersion($this->settings['documentsCache'], $extensionKey, $version);

		// set directory to be deleted
		$directories = array('sxw');

		// Delete empty directory because doc/manual.sxw was not found)
		if (!file_exists($documentDir . 'sxw/content.xml')) {

			$directories[] = 'html_online';
			$directories[] = 'docbook';

			// Empty and create blank directory
			$docBookVersions = explode(',', $this->settings['docbook_version']);
			foreach ($docBookVersions as $docBookVersion) {
				$directories[] = 'docbook' . $docBookVersion;
			}
		}

		// Delete temporary directory
		foreach ($directories as $directory) {
			if (is_link($documentDir . $directory)) {
				unlink($documentDir . $directory);
			}
			elseif (is_dir($documentDir . $directory)) {
				Tx_TerDoc_Utility_Cli::removeDirRecursively($documentDir . $directory);
			}
		}

		$cacheUids = $this->getCacheUidsForExtension($extensionKey);

		if ($cacheUids !== FALSE) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
					'cache_pages',
					'reg1 IN (' . $cacheUids . ')'
			);
		}
	}

	/**
	 * Clean up environement
	 *
	 * @return void
	 */
	public function cleanUpAll() {

		$cacheUids = $this->getCacheUidsForExtension('_all');

		if ($cacheUids !== FALSE) {

			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
					'cache_pages',
					'reg1 IN (' . $cacheUids . ')'
			);
		}
	}

	/**
	 * Returns a comma separated list of UIDs of TER DOC cache entries for the specified
	 * extension key. This UID can be used as a "reg1" parameter for the page
	 * caching so the cache can be cleared for manuals of a certain extension.
	 *
	 * @param	string		$extensionKey: The extension key
	 * @return	mixed		The terdoc internal cache uids or FALSE if an error ocurred or no uid was found
	 * @access	protected
	 */
	protected function getCacheUidsForExtension($extensionKey) {

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid',
						'tx_terdoc_manualspagecache',
						'extensionkey="' . $GLOBALS['TYPO3_DB']->quoteStr($extensionKey, 'tx_terdoc_manualspagecache') . '"'
		);

		if (!$res)
			return FALSE;

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			$uidsArr = array();
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$uidsArr[] = $row['uid'];
			}
			return implode(',', $uidsArr);
		}

		return FALSE;
	}

	/*	 * ****************************************************
	 *
	 * EXTRACTION
	 *
	 * **************************************************** */

	/**
	 * Download some files. Currently there are the t3x and gif files
	 *
	 * @param	string	$extensionKey: Extension key of the extension
	 * @param	string	$version: Version number of the extension
	 * @return	mixed	FALSE if operation fails, TRUE if file was written successfully, Array if operation was successful and $targetFullPath was NULL
	 * @access	protected
	 */
	public function downloadExtension($extensionKey, $version) {

		$fileExtensions = array('.t3x', '.gif');

		foreach ($fileExtensions as $fileExtension) {

			$file = Tx_TerDoc_Utility_Cli::getExtensionVersionPathAndBaseName($this->settings['repositoryDir'], $extensionKey, $version) . $fileExtension;

			// special case -> download the t3x archive when not already present on the harddrive.
			if (!file_exists($file)) {
				$parts = explode('/', str_replace($this->settings['repositoryDir'], '', $file));
				$t3xName = array_pop($parts);
				t3lib_div::mkdir_deep($this->settings['repositoryDir'], implode('/', $parts));

					// Find extract part of path starting with "/fileadmin" and assemble request URL
				$fileadminPath = substr($file, strpos($file, '/fileadmin'));
				$t3xOnline = 'http://typo3.org' . $fileadminPath;

				Tx_TerDoc_Utility_Cli::log('   * Downloading from typo3.org: ' . $t3xName);
				$data = file_get_contents($t3xOnline);
				$result = file_put_contents($file, $data);
				if (!$result) {
					Tx_TerDoc_Utility_Cli::log('      * Warning could not write or download "' . $file . '"');
					#throw new Exception('Exception thrown #1300153669: could not write or download "' . $file . '"', 1300153669);
				}
			}
		}
	}

	/**
	 * Unpacks the T3X file of the given extension version and extracts the file specified
	 * in $sourceName. If $targetFullPath is given, the file will be saved into the given
	 * location, otherwize the content of the extracted file will be returned.
	 *
	 * If the operation fails, FALSE is returned.
	 *
	 * @param	string	$extensionKey: Extension key of the extension
	 * @param	string	$version: Version number of the extension
	 * @param	string	$sourceName: relative path and filename of the file to be extracted. Example: doc/manual.sxw
	 * @param	array	$errorCodes: This variable will contain an array of error codes if errors occurred.
	 * @param	string	$targetFullPath: full path and filename of the target file. Example: /tmp/test.sxw
	 * @return	mixed	FALSE if operation fails, TRUE if file was written successfully, Array if operation was successful and $targetFullPath was NULL
	 * @access	protected
	 */
	public function extractT3x($extensionKey, $version, $sourceName, &$errorCodes, $targetFullPath=NULL) {

		// Computes the cache directory of the extension
		$documentDir = Tx_TerDoc_Utility_Cli::getDocumentDirOfExtensionVersion($this->settings['documentsCache'], $extensionKey, $version);
		$targetFullPath = $documentDir . 'manual.sxw';

		// computes the t3x file
		$t3xFile = Tx_TerDoc_Utility_Cli::getExtensionVersionPathAndBaseName($this->settings['repositoryDir'], $extensionKey, $version) . '.t3x';
		if (! is_file($t3xFile)) {
			throw new Exception('Exception thrown #1300111630: file does not exist "' . $t3xFile . '"', 1300111630);
		}

		$t3xFileRaw = file_get_contents($t3xFile);
		if ($t3xFileRaw === FALSE) {
			$errorCodes[] = self::ERRORCODE_ERRORWHILEREADINGT3XFILE;
			return FALSE;
		}

		list ($md5Hash, $compressionFlag, $dataRaw) = preg_split('/:/is', $t3xFileRaw, 3);
		unset($t3xFileRaw);

		$dataUncompressed = gzuncompress($dataRaw);


		if ($md5Hash != md5($dataUncompressed)) {
			Tx_TerDoc_Utility_Cli::log('   * T3X archive is corrupted, MD5 hash didn\'t match!');
			$errorCodes[] = self::ERRORCODE_T3XARCHIVECORRUPTED;
			return FALSE;
		}
		unset($dataRaw);

		$t3xArr = unserialize($dataUncompressed);
		if (!is_array($t3xArr)) {
			Tx_TerDoc_Utility_Cli::log('   * ERROR while uncompressing t3x file!');
			$errorCodes[] = self::ERRORCODE_ERRORWHILEUNCOMPRESSINGT3XFILE;
			return FALSE;
		}
		if (!is_array($t3xArr['FILES'])) {
			Tx_TerDoc_Utility_Cli::log('   * ERROR: Corrupted t3x structure - no files found');
			$errorCodes[] = self::ERRORCODE_CORRUPTEDT3XSTRUCTURENOFILESFOUND;
			return FALSE;
		}
		if (!is_array($t3xArr['FILES'][$sourceName])) {
			Tx_TerDoc_Utility_Cli::log('   * File "' . $sourceName . '" not found in this t3x archive!');
			$errorCodes[] = self::ERRORCODE_FILENOTFOUNDINT3XARCHIVE;
			return FALSE;
		}
		if ($t3xArr['FILES'][$sourceName]['content_md5'] != md5($t3xArr['FILES'][$sourceName]['content'])) {
			Tx_TerDoc_Utility_Cli::log('   * File "' . $sourceName . '" is corrupted, MD5 hash didn\'t match!');
			$errorCodes[] = self::ERRORCODE_FILEOFT3XISCORRUPTED;
			return FALSE;
		}

		if (is_null($targetFullPath)) {
			return $t3xArr['FILES'][$sourceName]['content'];
		}

		$result = t3lib_div::writeFile($targetFullPath, $t3xArr['FILES'][$sourceName]['content']) ? TRUE : FALSE;

		// if process un-succeeded, then errors must be collected
		if (! $result) {
			Tx_TerDoc_Utility_Cli::log('	* extract: problem while extracting manual.sxw from t3x file');
			if ($errorCodes[(count($errorCodes) - 1)] == self::ERRORCODE_FILENOTFOUNDINT3XARCHIVE) {
				$errorCodes[(count($errorCodes) - 1)] = self::ERRORCODE_DOCMANUALSXWFOUNDINT3XARCHIVE;
			}
			else {
				$errorCodes[] = self::ERRORCODE_PROBLEMWHILEXTRACTINGMANUALSXW;
			}
		}
		return $result;
	}

	/**
	 * Decompress manual.sxw into unzip
	 *
	 * @param	string	$extensionKey: Extension key of the extension
	 * @param	string	$version: Version number of the extension
	 * @return	mixed	FALSE if operation fails, TRUE if file was written successfully, Array if operation was successful and $targetFullPath was NULL
	 */
	public function decompressManual($extensionKey, $version) {

		// Computes the cache directory of the extension
		$documentDir = Tx_TerDoc_Utility_Cli::getDocumentDirOfExtensionVersion($this->settings['documentsCache'], $extensionKey, $version);
		$targetFullPath = $documentDir . 'manual.sxw';

		// makes sure the documentation exists
		if (is_file($targetFullPath)) {

			// Unzip the Open Office Writer file:
			$unzipCommand = $this->settings['unzipCommand'];
			$unzipCommand = str_replace('###ARCHIVENAME###', $targetFullPath, $unzipCommand);
			$unzipCommand = str_replace('###DIRECTORY###', $documentDir . 'sxw/', $unzipCommand);
			$unzipResultArr = array();

			exec($unzipCommand, $unzipResultArr);

			if (is_dir($documentDir . 'sxw/Pictures')) {
				rename($documentDir . 'sxw/Pictures', $documentDir . 'docbook/pictures');
			}
		}
	}

	/*	 * ****************************************************
	 *
	 * Cache related functions
	 *
	 * **************************************************** */

	/**
	 * Deletes rendered documents and directories of those extensions which don't
	 * exist in the extension index (anymore).
	 *
	 * @return	void
	 * @access	protected
	 */
	public function deleteOutdatedDocuments() {
		// FIXME
		Tx_TerDoc_Utility_Cli::log('* FIXME: deleteOutDatedDocuments not implemented');
	}

	/**
	 * Returns an array of extension keys and version numbers of those
	 * extensions which were modified since the last time the documents
	 * were rendered for this extension.
	 *
	 * @return	array		Array of extensionkey and version
	 * @access	protected
	 */
	public function getModifiedExtensionVersions() {

		$extensionKeysAndVersionsArr = array();
		Tx_TerDoc_Utility_Cli::log('* Checking for modified extension versions');

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'extensionkey,version,t3xfilemd5',
						'tx_terdoc_manuals',
						'1'
		);
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					// no need to do the heavy IO stuff when we force rendering
				if ($this->arguments['force']) {
					$extensionKeysAndVersionsArr[] = array(
						'extensionkey' => $row['extensionkey'],
						'version' => $row['version'],
						't3xfilemd5' => $row['t3xfilemd5']
					);
					continue;
				}

				$documentDir = Tx_TerDoc_Utility_Cli::getDocumentDirOfExtensionVersion($this->settings['documentsCache'], $row['extensionkey'], $row['version']);
				$t3xMD5OfRenderedDocuments = @file_get_contents($documentDir . 't3xfilemd5.txt');
				if ($t3xMD5OfRenderedDocuments != $row['t3xfilemd5']) {
					$extensionKeysAndVersionsArr[] = array(
						'extensionkey' => $row['extensionkey'],
						'version' => $row['version'],
						't3xfilemd5' => $row['t3xfilemd5']
					);
				}
			}
		}
		Tx_TerDoc_Utility_Cli::log('* Found ' . count($extensionKeysAndVersionsArr) . ' modified extension versions');
		return $extensionKeysAndVersionsArr;
	}

}

?>
