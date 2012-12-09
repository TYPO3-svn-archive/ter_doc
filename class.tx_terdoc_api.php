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
 * API for other TYPO3 extensions
 *
 * $Id: class.tx_terdoc_api.php 4354 2006-12-15 17:47:51Z robert $
 *
 * @author	Robert Lemke <robert@typo3.org>
 */

class tx_terdoc_api {

	protected	$repositoryDir = '';								// Full path to the local extension repository. Configured in the Extension Manager
	protected	$localLangArr = array();							// Contains the locallang strings for this API
	protected	$storagePid = 0;

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
	 * @return		tx_terdoc_api		Unique instance of tx_terdoc_renderdocuments
	 * @access		public
	 */
	public function getInstance() {
		if (self::$instance === FALSE) {
			self::$instance = new tx_terdoc_api;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Initializes this class
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

			if (!empty($staticConfArr['storagePid'])) {
				$this->storagePid = (int) $staticConfArr['storagePid'];
			}
		}
	}

	/**
	 * Returns one level of a rendered Table Of Content of the given extension version. If no manual exists,
	 * a message will be returned instead.
	 *
	 * Note: The parameter $withLinks only works if the extension ter_doc_html is installed.
	 *
	 * @param		string		$extensionKey: Extension key of the manual
	 * @param		string		$version: Version string of the manual
	 * @param		boolean		$withLinks: If set, the TOC entries are linked with a page which actually displays the chapters and sections
	 * @param		boolean		$smartLevels: Usually the first level (chapters) will be rendered. If the first level only conntains one entry and $smartLevels is set, the second level will be rendered instead. (Compatibility option for older OOo manuals where first level is always the extension title)
	 * @return		string		HTML code of the rendered TOC
	 * @access		public
	 */
	public function getRenderedTOC ($extensionKey, $version, $withLinks = TRUE, $smartLevels = TRUE) {
		global $TSFE;

		$documentDir = $this->getDocumentDirOfExtensionVersion ($extensionKey, $version);
		$tocArr = unserialize (@file_get_contents ($documentDir.'toc.dat'));
		if (!is_array ($tocArr)) return '<strong style="color:red;">'.htmlspecialchars($TSFE->sL('LLL:EXT:ter_doc/locallang.xml:api_error_documentationnotavailable')).'</strong>';

		$levelToRenderArr = ($smartLevels && count ($tocArr) == 1) ? $tocArr[1]['sections'] : $tocArr;

		if (is_array ($levelToRenderArr)) {
			$pageId = $this->getViewPageIdForExtensionVersion($extensionKey, $version);

			foreach ($levelToRenderArr as $itemsArr) {
				if ($withLinks && t3lib_extMgm::isLoaded ('ter_doc_html')) {
					$parametersArr = array(
						'tx_terdoc_pi1[extensionkey]' => $extensionKey,
						'tx_terdoc_pi1[version]' => $version,
						'tx_terdoc_pi1[format]' => 'ter_doc_html_onlinehtml',
						'tx_terdoc_pi1[html_readonline_chapter]' => '',
					);
					$typoLinkConf = array (
						'parameter' => $pageId,
						'additionalParams' => t3lib_div::implodeArrayForUrl('', $parametersArr),
						'useCacheHash' => 1
					);
					$item = $TSFE->cObj->typoLink($this->csConvHSC($itemsArr['title']), $typoLinkConf);
				} else {
					$item = $this->csConvHSC($itemsArr['title']);
				}

				$output .= $item.'<br />';
			}
		} else {
			$output = '<strong style="color:red;">'.htmlspecialchars($TSFE->sL('LLL:EXT:ter_doc/locallang.xml:api_error_tocempty')).'</strong>';
		}

		return $output;
	}

	/**
	 * Returns a link to the documentation (table of contents) of the given extension version
	 *
	 * @param		string		$extensionKey: Extension key of the manual
	 * @param		string		$version: Version string of the manual
	 * @param		string		$format: Format of the manual
	 * @return		string		HTML code of the link
	 * @access		public
	 */
	public function getDocumentationLink ($extensionKey, $version, $format = '') {
		global $TSFE;

		if (!t3lib_extMgm::isLoaded ('ter_doc_html')) return '<span style="color:red;">'.htmlspecialchars($TSFE->sL('LLL:EXT:ter_doc/locallang.xml:api_error_terdochtmlnotinstalled')).'</span>';

		$documentDir = $this->getDocumentDirOfExtensionVersion ($extensionKey, $version);
		$tocArr = unserialize (@file_get_contents ($documentDir.'toc.dat'));
		if (!is_array ($tocArr)) {
			if (@file_exists($documentDir.'t3xfilemd5.txt')) {
				$message = '<span style="color:red;">'.htmlspecialchars($TSFE->sL('LLL:EXT:ter_doc/locallang.xml:api_error_documentationnotavailable')).'</span>';
				$message .= ' ' . $this->getDocumentationRenderProblemsLink($extensionKey, $version, htmlspecialchars($TSFE->sL('LLL:EXT:ter_doc/locallang.xml:api_error_whyisdocumentationnotavailable')));
			} else {
				$message = '<span style="color:red;">'.htmlspecialchars($TSFE->sL('LLL:EXT:ter_doc/locallang.xml:api_error_documentationnotyetrendered')).'</span>';
			}
			return $message;
		}

		$renderDocumentsObj = tx_terdoc_renderdocuments::getInstance();
		$outputFormatsArr = $renderDocumentsObj->getOutputFormats();

		if (!is_array ($outputFormatsArr) || !$outputFormatsArr['ter_doc_html_onlinehtml']['object']->isAvailable ($extensionKey, $version)) {
			$message = '<strong style="color:red;">'.htmlspecialchars($TSFE->sL('LLL:EXT:ter_doc/locallang.xml:api_error_documentationnotavailable')).'</strong>';
			$message .= ' ' . $this->getDocumentationRenderProblemsLink($extensionKey, $version, htmlspecialchars($TSFE->sL('LLL:EXT:ter_doc/locallang.xml:api_error_whyisdocumentationnotavailable')));
			return $message;
		}

		$pageId = $this->getViewPageIdForExtensionVersion($extensionKey, $version);
		$parametersArr = array(
			'tx_terdoc_pi1[extensionkey]' => $extensionKey,
			'tx_terdoc_pi1[version]' => 'current',
			//'tx_terdoc_pi1[format]' => 'ter_doc_html_onlinehtml',
			//'tx_terdoc_pi1[html_readonline_chapter]' => '',
		);

		switch ($format) {
			case 'sxw':
				$parametersArr['tx_terdoc_pi1[format]'] = 'ter_doc_sxw';
				break;
			default:
				break;
		}

		$label = $this->csConvHSC($TSFE->sL($outputFormatsArr['ter_doc_html_onlinehtml']['label']));
		$typoLinkConf = array (
			'parameter' => $pageId,
			'additionalParams' => t3lib_div::implodeArrayForUrl('', $parametersArr),
			'useCacheHash' => 1
		);

		$link = $TSFE->cObj->typoLink($this->csConvHSC($label), $typoLinkConf);

		return $link;
	}

	/**
	 * Returns a link to a page listing all documentation render problems of the specified extension
	 *
	 * @param		string		$extensionKey: Extension key of the manual
	 * @param		string		$version: Version string of the manual
	 * @param 		string		$label: If set, this label is used for the link
	 * @return		string		HTML code of the link
	 * @access		public
	 */
	public function getDocumentationRenderProblemsLink ($extensionKey, $version, $label=NULL) {
		global $TSFE, $TYPO3_DB;

		if (!t3lib_extMgm::isLoaded ('ter_doc_renderproblems')) return '';

		$renderDocumentsObj = tx_terdoc_renderdocuments::getInstance();
		$outputFormatsArr = $renderDocumentsObj->getOutputFormats();

		if (!is_array ($outputFormatsArr) || !$outputFormatsArr['ter_doc_renderproblems']['object']->isAvailable ($extensionKey, $version)) {
			return '';
		}

		$pageId = $this->getViewPageIdForExtensionVersion($extensionKey, $version);
		$parametersArr = array(
			'tx_terdoc_pi1[extensionkey]' => $extensionKey,
			'tx_terdoc_pi1[version]' => $version,
			'tx_terdoc_pi1[format]' => 'ter_doc_renderproblems',
		);

		$label = (!is_null($label) ? $label : $this->csConvHSC($TSFE->sL($outputFormatsArr['ter_doc_renderproblems']['label'])));
		$typoLinkConf = array (
			'parameter' => $pageId,
			'additionalParams' => t3lib_div::implodeArrayForUrl('', $parametersArr),
			'useCacheHash' => 1
		);

		$link = $TSFE->cObj->typoLink($this->csConvHSC($label), $typoLinkConf);

		return $link;
	}





	/**
	 * Returns the page ID of the page where the category of the specified extension
	 * version can be read online. Of course that requires that a ter_doc frontend
	 * plugin is installed on that page.
	 *
	 * The PIDs for each category must be set in the ter_doc_categories record.
	 *
	 * If ter_doc_html is not installed, FALSE will be returned.
	 *
	 * @param	string		$extensionKey: The extension key
	 * @param	string		$version: The version string
	 * @return	mixed		Page ID (integer) or FALSE
	 * @access	public
	 */
	public function getViewPageIdForExtensionVersion ($extensionKey, $version) {
		global $TYPO3_DB;

		if (!t3lib_extMgm::isLoaded ('ter_doc_html')) return FALSE;

		$res = $TYPO3_DB->exec_SELECTquery (
			'categoryuid',
			'tx_terdoc_manualscategories',
			'extensionkey="'.$TYPO3_DB->quoteStr($extensionKey, 'tx_terdoc_manualscategories').'" AND pid=' . (int) $this->storagePid
		);
		if (!$res) return FALSE;

		if ($TYPO3_DB->sql_num_rows ($res) == 0) {
			$res = $TYPO3_DB->exec_SELECTquery (
				'viewpid',
				'tx_terdoc_categories',
				'isdefault=1 AND pid=' . (int) $this->storagePid
			);
			if (!$res) return FALSE;
			$categoryRow = $TYPO3_DB->sql_fetch_assoc ($res);

			return $categoryRow['viewpid'];
		} else {
			$assignmentRow = $TYPO3_DB->sql_fetch_assoc ($res);

			$res = $TYPO3_DB->exec_SELECTquery (
				'viewpid',
				'tx_terdoc_categories',
				'uid='.$assignmentRow['categoryuid']
			);
			if (!$res) return FALSE;
			$categoryRow = $TYPO3_DB->sql_fetch_assoc ($res);

			return $categoryRow['viewpid'];
		}
	}

	/**
	 * Returns a unique id (integer) for the combinition of the specified extension key
	 * and version string. This UID can be used as a "reg1" parameter for the page
	 * caching so later the cache can be cleared for a certain manual.
	 *
	 * If such a uid does not exist for the combinition of extension key and version string,
	 * a database record will be created.
	 *
	 * @param	string		$extensionKey: The extension key
	 * @param	string		$version: The version string
	 * @return	mixed		The terdoc internal cache uid or FALSE if an error ocurred
	 * @access	public
	 */

	public function createAndGetCacheUidForExtensionVersion ($extensionKey, $version) {
		global $TYPO3_DB;

		$res = $TYPO3_DB->exec_SELECTquery (
			'uid',
			'tx_terdoc_manualspagecache',
			'extensionkey="'.$TYPO3_DB->quoteStr($extensionKey,'tx_terdoc_manualspagecache').'" AND version="'.$TYPO3_DB->quoteStr($version,'tx_terdoc_manualspagecache').'" AND pid=' . (int) $this->storagePid
		);
		if (!$res) return FALSE;

		if ($TYPO3_DB->sql_num_rows ($res) > 0) {
			$row = $TYPO3_DB->sql_fetch_assoc ($res);
			return is_array ($row) ? $row['uid'] : FALSE;
		} else {
			$fields = array(
				'pid' => (int) $this->storagePid,
				'extensionkey' => $extensionKey,
				'version' => $version
			);
			$res = $TYPO3_DB->exec_INSERTquery ('tx_terdoc_manualspagecache', $fields);
			if (!$res) return FALSE;

			return $TYPO3_DB->sql_insert_id ($res);
		}

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
	 * @access	public
	 */
	public function getDocumentDirOfExtensionVersion ($extensionKey, $version) {
		$firstLetter = strtolower (substr ($extensionKey, 0, 1));
		$secondLetter = strtolower (substr ($extensionKey, 1, 1));
		$baseDir = PATH_site.'typo3temp/tx_terdoc/documentscache/';

 		list ($majorVersion, $minorVersion, $devVersion) = t3lib_div::intExplode ('.', $version);
		$fullPath = $baseDir.$firstLetter.'/'.$secondLetter.'/'.strtolower($extensionKey).'-'.$majorVersion.'.'.$minorVersion.'.'.$devVersion;

		return $fullPath.'/';
	}

	/**
	 * Processes the given string with htmlspecialchars and converts the result
	 * from utf-8 to the charset of the current frontend
	 * page
	 *
	 * @param	string	$string: The utf-8 string to convert
	 * @return	string	The converted string
	 * @access	public
	 */
	public function csConvHSC ($string) {
		return $GLOBALS['TSFE']->csConv(htmlspecialchars($string), 'utf-8');
	}

}

?>