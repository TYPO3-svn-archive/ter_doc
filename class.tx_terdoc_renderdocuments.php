<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Robert Lemke (robert@typo3.org)
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
***************************************************************/
/**
 * Documentation renderer for the ter_doc extension. Called from the CLI
 * script as well as from the ter_doc_* extensions for registering
 * output formats. 
 *
 * $Id$
 *
 * @author	Robert Lemke <robert@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 */

class tx_terdoc_renderdocuments {

	const ERRORCODE_PROBLEMWHILEXTRACTINGMANUALSXW = 1;
	const ERRORCODE_CONTENTXMLDIDNOTEXISTAFTEREXTRACTINGSXW = 2;
	const ERRORCODE_SIMPLEXMLERRORWHILETRANSFORMINGXMLTODOCBOOK = 3;
	const ERRORCODE_COULDNOTGUESSDOCUMENTLANGUAGENOTEXTLANGSERVICEAVAILABLE = 4;
	const ERRORCODE_COULDNOTGUESSDOCUMENTLANGUAGEEXCERPTEMPTY = 5;
	const ERRORCODE_COULDNOTEXTRACTABSTRACT = 6;
	const ERRORCODE_ERRORWHILEREADINGT3XFILE = 7;
	const ERRORCODE_T3XARCHIVECORRUPTED = 8;
	const ERRORCODE_ERRORWHILEUNCOMPRESSINGT3XFILE = 9;
	const ERRORCODE_CORRUPTEDT3XSTRUCTURENOFILESFOUND = 10;
	const ERRORCODE_FILEOFT3XISCORRUPTED = 11;
	const ERRORCODE_FILENOTFOUNDINT3XARCHIVE = 12;
	const ERRORCODE_DOCMANUALSXWFOUNDINT3XARCHIVE = 13;

	protected $repositoryDir = '';									// Full path to the local extension repository. Configured in the Extension Manager
	protected $verbose = FALSE;										// If TRUE, some debugging output will be sent to STDOUT. Configured in the Extension Manager
	protected $fullPath = FALSE;									// If set to a path and file name, logging will be redirected to that file
	protected $unzipCommand = '';									// Commandline for unzipping files
	protected $debug = FALSE;										// Makes it easer to debug this class. Set to FALSE in productional use!
	
	protected $outputFormats = array();								// Objects and method names of the render classes. Add new class by calling registerRenderClass()
	protected $languageGuesserServiceObj = array();					// Holds an instance of the service "textLang" 

	private static $instance = FALSE;								// Holds an instance of this class

	/**
	 * This constructor is private because you may only instantiate this class by calling
	 * the function getInstance() which returns a unique instance of this class (Singleton).
	 * 
	 * @return		void
	 * @access		private
	 */
	private function __construct() {
	}

	/**
	 * Returns a unique instance of this class. Call this function instead of creating a new
	 * instance manually!
	 * 
	 * @return		object		Unique instance of tx_terdoc_renderdocuments
	 * @access		public
	 */
	public function getInstance() {
		if (self::$instance === FALSE) {
			self::$instance = new tx_terdoc_renderdocuments;	
		}
		return self::$instance;	
	} 

	/**
	 * Initializes this class and checks if another process is running already.
	 * 
	 * @return	void
	 * @access	protected
	 */
	protected function init() {

			// Fetch static configuration from Extension Manager:
		$staticConfArr = unserialize ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ter_doc']);
		if (is_array ($staticConfArr)) {
			
			$this->repositoryDir = $staticConfArr['repositoryDir'];
			if (substr ($this->repositoryDir, -1, 1) != '/') $this->repositoryDir .= '/';
			$this->unzipCommand = $staticConfArr['unzipCommand'];
			$this->verbose = $staticConfArr['cliVerbose'] ? TRUE : FALSE;
			$this->logFullPath = strlen ($staticConfArr['logFullPath']) ? $staticConfArr['logFullPath'] : FALSE;
		}

			// Check if another process currently renders the documents:
		if (@file_exists (PATH_site.'typo3temp/tx_terdoc/tx_terdoc_render.lock')) {
			$this->log ('Found .lock file ...');			
				// If the lock is not older than X minutes, skip index creation:
			if (filemtime (PATH_site.'typo3temp/tx_terdoc/tx_terdoc_render.lock') > (time() - (6*60*60))) {
				$this->log ('... aborting - another process seems to render documents right now!'.chr(10));
				if (!$this->debug) die();
			} else {
				$this->log ('... lock file was older than 6 hours, so start rendering anyway'.chr(10));
			}
		}		
		
			// Initialize language guessing service:
		$this->languageGuesserServiceObj = t3lib_div::makeInstanceService('textLang');
	}





	/******************************************************
	 *
	 * Main API functions (public)
	 *
	 ******************************************************/

	/**
	 * Main function - starts the document cache rendering process
	 * 
	 * @return	void
	 * @access	public
	 */
	public function renderCache() {		
		global $TYPO3_DB;
		
		$this->init();

		touch (PATH_site.'typo3temp/tx_terdoc/tx_terdoc_render.lock');

		$this->log(chr(10).strftime('%d.%m.%y %R').' ter_doc renderer starting ...');
		if ($this->extensionIndex_wasModified()) {
			$this->log ('* extensions.xml was modified since last run');
			
			if ($this->extensionIndex_updateDB()) {
				$this->documentCache_deleteOutdatedDocuments(); 
				$modifiedExtensionVersionsArr = $this->documentCache_getModifiedExtensionVersions ();

				foreach ($modifiedExtensionVersionsArr as $extensionAndVersionArr) {					
					$transformationErrorCodes = array();
					$extensionKey = $extensionAndVersionArr['extensionkey'];
					$version = $extensionAndVersionArr['version'];
					$documentDir = $this->getDocumentDirOfExtensionVersion ($extensionKey, $version);
					
					$this->log ('* Rendering documents for extension "'.$extensionKey.'" ('.$version.')');
					$TYPO3_DB->exec_DELETEquery ('tx_terdoc_renderproblems', 'extensionkey="'.$extensionKey.'" AND version="'.$version.'"');						
					
					if ($this->documentCache_transformManualToDocBook($extensionKey, $version, $transformationErrorCodes)) {
						foreach ($this->outputFormats as $label => $formatInfoArr) {
							$this->log ('   * Rendering '.$label);
							$formatInfoArr['object']->renderCache($documentDir);
						}
					} else {
						$TYPO3_DB->exec_DELETEquery ('tx_terdoc_manuals', 'extensionkey="'.$extensionKey.'" AND version="'.$version.'"');						
						$this->log ('	* No manual found or problem while extracting manual');	
					}
					$this->pageCache_clearForExtension ($extensionKey);
					t3lib_div::writeFile ($documentDir.'t3xfilemd5.txt', $extensionAndVersionArr['t3xfilemd5']);

					foreach($transformationErrorCodes as $errorCode) {
						$TYPO3_DB->exec_INSERTquery ('tx_terdoc_renderproblems', array('extensionkey' => $extensionKey, 'version' => $version, 'tstamp' => time(), 'errorcode' => $errorCode));
					}
					$this->log ('   * Error code(s): ' . implode(',', $transformationErrorCodes));
				}				
				$this->pageCache_clearForAll();
			}
			$this->log(chr(10).strftime('%d.%m.%y %R').' done.'.chr(10));
		} else $this->log('Extensions.xml was not modified since last run, so nothing to do - done.');		

		@unlink (PATH_site.'typo3temp/tx_terdoc/tx_terdoc_render.lock');
	}

	/**
	 * Registers a new output format.
	 * 
	 * @param	string		$label: Unique name (label) of the output format. Can be a locallang string (eg. "LLL:EXT:terdoc_html/locallang.php:label")
	 * @param	string		$type: Possible values: "download" and "readonline" 
	 * @param	object		$objectReference: Instance of the render class. If $type is "readonline", a method "renderOnline" must exist. A method "renderCache" is mandatory.
	 * @return	void
	 * @access	public
	 */
	public function registerOutputFormat ($key, $label, $type, &$objectReference) {
		$this->outputFormats[$key] = array (
			'label' => $label,
			'type' => $type,
			'object' => $objectReference,
		);
	}
	
	/**
	 * Returns an array of output formats which were previously
	 * rendered with registerOutputFormat()
	 * 
	 * @return	array		Array of output formats and instantiated rendering objects
	 * @access	public
	 */
	public function getOutputFormats () {
		return $this->outputFormats;	
	}





	/******************************************************
	 *
	 * Extension index functions (protected)
	 *
	 ******************************************************/

	/**
	 * Checks if the extension index file (extensions.xml.gz) was modified
	 * since the last built of the extension index in the database.
	 * 
	 * @return	boolean		TRUE if the index has changed
	 * @access	protected
	 */
	protected function extensionIndex_wasModified () {
		$oldMD5Hash = @file_get_contents (PATH_site.'typo3temp/tx_terdoc/tx_terdoc_extensionsmd5.txt');
		$currentMD5Hash = md5_file ($this->repositoryDir.'extensions.xml.gz');
		return ($oldMD5Hash != $currentMD5Hash);
	}

	/**
	 * Reads the extension index file (extensions.xml.gz) and updates
	 * the the manual caching table accordingly.
	 * 
	 * @return	boolean		TRUE if operation was successful
	 * @access	protected
	 */
	protected function extensionIndex_updateDB() {
		global $TYPO3_DB;
		
		$this->log ('* Deleting cached manual information from database');
		$TYPO3_DB->exec_DELETEquery ('tx_terdoc_manuals', '1');		

			// Transfer data from extensions.xml.gz to database:
		$unzippedExtensionsXML = implode ('', @gzfile($this->repositoryDir.'extensions.xml.gz'));
		$extensions = simplexml_load_string ($unzippedExtensionsXML);
		if (!is_object($extensions)) {
			$this->log ('Error while parsing '. $this->repositoryDir.'extensions.xml.gz - aborting!');
			return FALSE;
		}
		
		foreach ($extensions as $extension) {
			foreach ($extension as $version) {
				if (strlen($version['version'])) {
					$documentDir = $this->getDocumentDirOfExtensionVersion ($extension['extensionkey'], $version['version']);				
					$abstract = @file_get_contents($documentDir.'abstract.txt');
					$language = @file_get_contents($documentDir.'language.txt');
					
					$extensionsRow = array (
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
					$TYPO3_DB->exec_INSERTquery ('tx_terdoc_manuals', $extensionsRow);
				}
			}	
		}

			// Create new MD5 hash:
		t3lib_div::writeFile (PATH_site.'typo3temp/tx_terdoc/tx_terdoc_extensionsmd5.txt', md5_file ($this->repositoryDir.'extensions.xml.gz'));		
		$this->log ('* Manual DB index was sucessfully reindexed');
				
		return TRUE;
	}





	/******************************************************
	 *
	 * Cache related functions (protected)
	 *
	 ******************************************************/

	/**
	 * Deletes rendered documents and directories of those extensions which don't
	 * exist in the extension index (anymore).
	 * 
	 * @return	void
	 * @access	protected
	 */
	protected function documentCache_deleteOutdatedDocuments() {
		// FIXME
		$this->log ('* FIXME: deleteOutDatedDocuments not implemented');
	}

	/**
	 * Returns an array of extension keys and version numbers of those
	 * extensions which were modified since the last time the documents
	 * were rendered for this extension.
	 * 
	 * @return	array		Array of extensionkey and version
	 * @access	protected
	 */
	protected function documentCache_getModifiedExtensionVersions() {
		global $TYPO3_DB;
		
		$extensionKeysAndVersionsArr = array();
		$this->log ('* Checking for modified extension versions');

		$res = $TYPO3_DB->exec_SELECTquery (
			'extensionkey,version,t3xfilemd5',
			'tx_terdoc_manuals',
			'1'
		);
		if ($res) {
			while ($row = $TYPO3_DB->sql_fetch_assoc ($res)) {
				$documentDir = $this->getDocumentDirOfExtensionVersion ($row['extensionkey'], $row['version']);
				$t3xMD5OfRenderedDocuments = @file_get_contents ($documentDir.'t3xfilemd5.txt');				
				if ($t3xMD5OfRenderedDocuments != $row['t3xfilemd5']) {
					$extensionKeysAndVersionsArr[] = array (
						'extensionkey' => $row['extensionkey'],
						'version' => $row['version'],
						't3xfilemd5' => $row['t3xfilemd5']
					);
				}
			}	
		}
		$this->log ('* Found '.count($extensionKeysAndVersionsArr).' modified extension versions');
		return $extensionKeysAndVersionsArr;
	}
	
	/**
	 * Transforms the manual of the specified extension version to docbook and saves
	 * the result in a file called "manual.xml" in the document cache directory of the
	 * extension.
	 * 
	 * @param	string		$extensionKey: The extension key
	 * @param	string		$version: The extension's version string
	 * @param	array		$errorCodes: An array of error codes which occurred during the transformation
	 * @return	mixed		returns TRUE if operation was successful, otherwise FALSE.
	 * @access	protected
	 */
	protected function documentCache_transformManualToDocBook($extensionKey, $version, &$errorCodes) {
		global $TYPO3_DB;
		
		$documentDir = $this->getDocumentDirOfExtensionVersion ($extensionKey, $version);

		if (!$this->t3x_extractFileFromT3X ($extensionKey, $version, 'doc/manual.sxw', $errorCodes, $documentDir.'manual.sxw')) {
			$this->log ('	* documentCache_transformManualToDocBook: problem while extracting manual.sxw from t3x file');
			if ($errorCodes[(count($errorCodes)-1)] == self::ERRORCODE_FILENOTFOUNDINT3XARCHIVE) {
				$errorCodes[(count($errorCodes)-1)] = self::ERRORCODE_DOCMANUALSXWFOUNDINT3XARCHIVE;
			} else {
				$errorCodes[] = self::ERRORCODE_PROBLEMWHILEXTRACTINGMANUALSXW;
			}
			return FALSE;
		}

			// Prepare output directory:
		if (@is_dir ($documentDir.'sxw')) $this->removeDirRecursively ($documentDir.'sxw');
		if (@is_dir ($documentDir.'docbook')) $this->removeDirRecursively ($documentDir.'docbook');
		@mkdir ($documentDir.'sxw');
		@mkdir ($documentDir.'docbook');

			// Unzip the Open Office Writer file:
		$unzipCommand = $this->unzipCommand;
		$unzipCommand = str_replace ('###ARCHIVENAME###', $documentDir.'manual.sxw', $unzipCommand);
		$unzipCommand = str_replace ('###DIRECTORY###', $documentDir.'sxw/', $unzipCommand);
		$unzipResultArr = array();
		exec($unzipCommand, $unzipResultArr);
				
		if (@is_dir ($documentDir.'sxw/Pictures')) {
			rename ($documentDir.'sxw/Pictures', $documentDir.'docbook/pictures');
		}  

			// Transform the manual's content.xml to DocBook:
		$this->log ('   * Rendering DocBook');
		$xsl = new DomDocument();
		$xsl->load(t3lib_extMgm::extPath ('ter_doc').'res/oomanual2docbook.xsl');

		if (!@file_exists ($documentDir.'sxw/content.xml')) {
			$this->log ('	* documentCache_transformManualToDocBook: '.$documentDir.'sxw/content.xml does not exist.');	
			$errorCodes[] = self::ERRORCODE_CONTENTXMLDIDNOTEXISTAFTEREXTRACTINGSXW;
			return FALSE;
		}
		
		$manualDom = new DomDocument();
		$manualDom->load($documentDir.'sxw/content.xml');
				
		$xsltProc = new XsltProcessor();
		$xsl = $xsltProc->importStylesheet($xsl);
		
		$docBookDom = $xsltProc->transformToDoc($manualDom);
		$docBookDom->formatOutput = FALSE;
		$docBookDom->save($documentDir.'docbook/manual.xml');

			// Create Table Of Content:
		$tocArr = array ();
		$chapterCount = 1;
		$sectionCount = 1;			
		$subSectionCount = 1;			
		$simpleDocBook = simplexml_import_dom ($docBookDom);
		if ($simpleDocBook === FALSE) {
			$this->log ('	* documentCache_transformManualToDocBook: SimpleXML error while transforming XML to DocBook');	
			$errorCodes[] = self::ERRORCODE_SIMPLEXMLERRORWHILETRANSFORMINGXMLTODOCBOOK;
			return FALSE;
		}
		
		$abstract = '';
		$textExcerpt = '';

		foreach ($simpleDocBook->chapter as $chapter) {
			$tocArr[$chapterCount]['title'] = (string)$chapter->title;
			foreach ($chapter->section as $section) {
				$tocArr[$chapterCount]['sections'][$sectionCount]['title'] = (string)$section->title;

					// Try to extract an abstract out of the first paragraph of a section usually called "What does it do?":
				if ($chapterCount <= 1) {
					foreach ($section->section as $subSection) {
						if (strlen($abstract) == 0) {
							$abstract = (string)$subSection->para;
						}
					}						
				}				
				if (strlen($textExcerpt) < 2000) {
					$textExcerpt .= (string)$section->para;
					foreach ($section->section as $subSection) {
						if (strlen($textExcerpt) < 2000) {
							$textExcerpt .= (string)$subSection->para;	
							$textExcerpt .= (string)$subSection->itemizedlist->listitem->para;	
						}
					}						
				}

				foreach ($section->section as $subSection) {
					$tocArr[$chapterCount]['sections'][$sectionCount]['subsections'][$subSectionCount]['title'] = (string)$subSection->title;
					$subSectionCount ++;
				}
				$sectionCount++;
				$subSectionCount = 1;
			}
			$chapterCount++;
			$sectionCount = 1;
		}
		t3lib_div::writeFile ($documentDir.'toc.dat', serialize ($tocArr));

		if(strlen($abstract) < 5) $errorCodes[] = self::ERRORCODE_COULDNOTEXTRACTABSTRACT;
		
			// Identify the language of the document:
		if (strlen ($textExcerpt)) {
			if (is_object($this->languageGuesserServiceObj)) {
				$this->languageGuesserServiceObj->process($textExcerpt, '', array('encoding' => 'utf-8'));
			    $documentLanguage = strtolower($this->languageGuesserServiceObj->getOutput());
			} else {
				$this->log ('   * Warning: Could not guess language because textLang service was not available');
				$errorCodes[] = self::ERRORCODE_COULDNOTGUESSDOCUMENTLANGUAGENOTEXTLANGSERVICEAVAILABLE;
				$metaXML = simplexml_load_file ($documentDir.'sxw/meta.xml');
				$DCLanguageArr = $metaXML->xpath ('//dc:language');
				$documentLanguage = is_array ($DCLanguageArr) ? strtolower(substr ($DCLanguageArr[0], 0, 2)) : '';	
			}
		} else {
			$this->log ('   * Warning: Could not guess language because the text excerpt was empty!');		
			$errorCodes[] = self::ERRORCODE_COULDNOTGUESSDOCUMENTLANGUAGEEXCERPTEMPTY;
		}		

			// Store abstract and language information:		
		$TYPO3_DB->exec_UPDATEquery (
			'tx_terdoc_manuals', 
			'extensionkey="'.$extensionKey.'" AND version="'.$version.'"', 
			array(
				'abstract' => $abstract,
				'language' => $documentLanguage,
			) 
		);	
		t3lib_div::writeFile ($documentDir.'abstract.txt', $abstract);
		t3lib_div::writeFile ($documentDir.'text-excerpt.txt', $textExcerpt);
		t3lib_div::writeFile ($documentDir.'language.txt', $documentLanguage);
		
		$this->removeDirRecursively ($documentDir.'sxw');

		return TRUE;
	}

	/**
	 * Clears the page cache of those pages (in the FE) which display information about the specified
	 * extension 
	 * 
	 * @param		string	$extensionKey: Extension key of the extension
	 * @return		void
	 * @access		protected
	 */
	protected function pageCache_clearForExtension($extensionKey) {
		global $TYPO3_DB;
		
		$cacheUids = $this->pageCache_getCacheUidsForExtension($extensionKey);

		if ($cacheUids === FALSE) return;

		$TYPO3_DB->exec_DELETEquery (
			'cache_pages',
			'reg1 IN ('.$cacheUids.')'
		);
	}

	/**
	 * Clears the page cache of those pages (in the FE) which display information from all
	 * extensions (or most of them). Typically overview and listing pages.
	 * 
	 * @return		void
	 * @access		protected
	 */
	protected function pageCache_clearForAll() {
		global $TYPO3_DB;
		
		$cacheUids = $this->pageCache_getCacheUidsForExtension('_all');

		if ($cacheUids === FALSE) return;
		
		$TYPO3_DB->exec_DELETEquery (
			'cache_pages',
			'reg1 IN ('.$cacheUids.')'
		);
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
	protected function pageCache_getCacheUidsForExtension($extensionKey) {
		global $TYPO3_DB;

		$res = $TYPO3_DB->exec_SELECTquery (
			'uid',
			'tx_terdoc_manualspagecache',
			'extensionkey="'.$TYPO3_DB->quoteStr($extensionKey,'tx_terdoc_manualspagecache').'"'
		);

		if (!$res) return FALSE; 

		if ($TYPO3_DB->sql_num_rows ($res) > 0) {
			$uidsArr = array();
			while ($row = $TYPO3_DB->sql_fetch_assoc ($res)) {
				$uidsArr[] = $row['uid'];
			}	
			return implode (',', $uidsArr);			
		}

		return FALSE;		
	}





	/******************************************************
	 *
	 * File related functions (protected)
	 *
	 ******************************************************/

	/**
	 * Unpacks the T3X file of the given extension version and extracts the file specified
	 * in $sourceName. If $targetFullPath is given, the file will be saved into the given
	 * location, otherwize the content of the extracted file will be returned.
	 * 
	 * If the operation fails, FALSE is returned. 
	 * 
	 * @param		string	$extensionKey: Extension key of the extension
	 * @param		string	$version: Version number of the extension
	 * @param		string	$sourceName: relative path and filename of the file to be extracted. Example: doc/manual.sxw
	 * @param		array	$errorCodes: This variable will contain an array of error codes if errors occurred.
	 * @param		string	$targetFullPath: full path and filename of the target file. Example: /tmp/test.sxw
	 * @return		mixed	FALSE if operation fails, TRUE if file was written successfully, Array if operation was successful and $targetFullPath was NULL
	 * @access		protected
	 */
	protected function t3x_extractFileFromT3X ($extensionKey, $version, $sourceName, &$errorCodes, $targetFullPath=NULL) {
		$this->log ('   * Extracting "'.$sourceName.'" from extension '.$extensionKey.' ('.$version.')');

		$t3xFileRaw = @file_get_contents ($this->getExtensionVersionPathAndBaseName($extensionKey, $version).'.t3x'); 
		if ($t3xFileRaw === FALSE) {
			$errorCodes[] = self::ERRORCODE_ERRORWHILEREADINGT3XFILE;
			return FALSE;
		}

		list ($md5Hash, $compressionFlag, $dataRaw) = split (':', $t3xFileRaw, 3);
		unset ($t3xFileRaw);
		
		$dataUncompressed = gzuncompress ($dataRaw);
		if ($md5Hash != md5 ($dataUncompressed)) { 
			$this->log ('   * T3X archive is corrupted, MD5 hash didn\'t match!'); 
			$errorCodes[] = self::ERRORCODE_T3XARCHIVECORRUPTED;
			return FALSE; 
		}
		unset ($dataRaw);		
	
		$t3xArr = unserialize ($dataUncompressed);
		if (!is_array ($t3xArr)) { 
			$this->log ('   * ERROR while uncompressing t3x file!'); 
			$errorCodes[] = self::ERRORCODE_ERRORWHILEUNCOMPRESSINGT3XFILE;
			return FALSE; 
		}	
		if (!is_array ($t3xArr['FILES'])) { 
			$this->log ('   * ERROR: Corrupted t3x structure - no files found'); 
			$errorCodes[] = self::ERRORCODE_CORRUPTEDT3XSTRUCTURENOFILESFOUND;
			return FALSE; 
		}
		if (!is_array ($t3xArr['FILES'][$sourceName])) { 
			$this->log ('   * File "'.$sourceName.'" not found in this t3x archive!'); 
			$errorCodes[] = self::ERRORCODE_FILENOTFOUNDINT3XARCHIVE;
			return FALSE; 
		}
		if ($t3xArr['FILES'][$sourceName]['content_md5'] != md5 ($t3xArr['FILES'][$sourceName]['content']))  { 
			$this->log ('   * File "'.$sourceName.'" is corrupted, MD5 hash didn\'t match!');
			$errorCodes[] = self::ERRORCODE_FILEOFT3XISCORRUPTED;
			return FALSE; 
		}

		if (is_null ($targetFullPath)) {
			return $t3xArr['FILES'][$sourceName]['content'];	
		}
		
		return t3lib_div::writeFile ($targetFullPath, $t3xArr['FILES'][$sourceName]['content']) ? TRUE : FALSE;
	}





	/**
	 * Returns the full path of the document directory for the specified
	 * extension version. If the path does not exist yet, it will be created - 
	 * given that the typo3temp/tx_terdoc/documentscache/ dir exists.  
	 * 
	 * In the document directory all rendered documents are stored.
	 * 
	 * @param	string		$extensionKey: The extension key
	 * @param	string		$version: The version string
	 * @return	string		Full path to the document directory for the specified extension version
	 */
	protected function getDocumentDirOfExtensionVersion ($extensionKey, $version) {
		$firstLetter = strtolower (substr ($extensionKey, 0, 1));
		$secondLetter = strtolower (substr ($extensionKey, 1, 1));
		$baseDir = PATH_site.'typo3temp/tx_terdoc/documentscache/';

 		list ($majorVersion, $minorVersion, $devVersion) = t3lib_div::intExplode ('.', $version);
		$fullPath = $baseDir.$firstLetter.'/'.$secondLetter.'/'.strtolower($extensionKey).'-'.$majorVersion.'.'.$minorVersion.'.'.$devVersion;
		
		if (strlen($firstLetter.$secondLetter)) {
			@mkdir ($baseDir.$firstLetter);
			@mkdir ($baseDir.$firstLetter.'/'.$secondLetter);
			@mkdir ($fullPath);
					
			return $fullPath.'/';
		}		
	}

	/**
	 * Returns the full path including file name but excluding file extension of
	 * the specified extension version in the file repository.
	 */
	protected function getExtensionVersionPathAndBaseName ($extensionKey, $version) {
		$firstLetter = strtolower (substr ($extensionKey, 0, 1));
		$secondLetter = strtolower (substr ($extensionKey, 1, 1));
		$fullPath = $this->repositoryDir.$firstLetter.'/'.$secondLetter.'/';

		list ($majorVersion, $minorVersion, $devVersion) = t3lib_div::intExplode ('.', $version);
		
		return $fullPath . strtolower ($extensionKey).'_'.$majorVersion.'.'.$minorVersion.'.'.$devVersion;		
	}

	/**
	 * Removes directory with all files from the given path recursively! 
	 * Path must somewhere below typo3temp/
	 * 
	 * @param	string		$removePath: Absolute path to directory to remove
	 * @return	void		
	 * @access	protected
	 */
	protected function removeDirRecursively ($removePath)	{

			// Checking that input directory was within
		$testDir = PATH_site.'typo3temp/';
		if (t3lib_div::validPathStr($removePath) && !t3lib_div::isFirstPartOfStr ($removePath,$testDir)) die($removePath.' was not within '.$testDir);

			// Go through dirs:
		$dirs = t3lib_div::get_dirs($removePath);
		if (is_array($dirs))	{
			foreach($dirs as $subdirs)	{
				if ($subdirs)	{
					$this->removeDirRecursively($removePath.'/'.$subdirs.'/');
				}
			}
		}

			// Then files in this dir:
		$fileArr = t3lib_div::getFilesInDir($removePath,'',1);
		if (is_array($fileArr))	{
			foreach($fileArr as $file)	{
				if (!t3lib_div::isFirstPartOfStr($file,$testDir)) die($file.' was not within '.$testDir);	// Paranoid...
				unlink($file);
			}
		}
			// Remove this dir:
		rmdir($removePath);
	}





	/******************************************************
	 *
	 * Other helper functions (protected)
	 *
	 ******************************************************/

	/**
	 * Writes a message to STDOUT if in verbose mode
	 * 
	 * @param	string		$msg: The message to output
	 * @return	void
	 * @access	protected
	 */
	protected function log ($msg) {
		if ($this->verbose) {
			if ($this->logFullPath) {
				$fh = fopen ($this->logFullPath, 'a');
				if ($fh) {
					fwrite ($fh, $msg.chr(10));
					fclose ($fh);
				}
			} else {
				echo ($msg.chr(10));
			}
		}		
	}
	
}

?>