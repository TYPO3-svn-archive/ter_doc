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
 * Plugin 'TER Documentation' for the 'ter_doc' extension.
 *
 * $Id: class.tx_terdoc_pi1.php 18283 2009-03-24 22:40:28Z steffenk $
 *
 * @author	Robert Lemke <robert@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   72: class tx_terdoc_pi1 extends tslib_pibase
 *   90:     protected function init($conf)
 *  110:     public function main($content,$conf)
 *
 *              SECTION: Render functions (protected)
 *  161:     protected function renderSimpleListOfDocuments ()
 *  210:     protected function renderSortedListOfDocuments ()
 *  267:     protected function renderListOfFormats ($extensionKey, $version)
 *  336:     protected function renderDocumentFormat($extensionKey, $version, $format)
 *  379:     protected function renderCategoryDescription ($categoryUid)
 *  403:     protected function renderTopNavigation()
 *  434:     protected function renderTER1Redirect ()
 *
 *              SECTION: Database related functions (protected)
 *  523:     protected function db_fetchCategoryAssignments()
 *  549:     protected function db_fetchManualRecords ($filterByCategory)
 *  597:     protected function db_fetchManualRecord ($extensionKey, $version)
 *
 *              SECTION: Miscellaneous helper functions (protected)
 *  649:     protected function csConvHSC ($string)
 *  669:     protected function arraySortBySubKey ($array, $subKey)
 *  690:     protected function transferFile ($fullPath)
 *
 * TOTAL FUNCTIONS: 15
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(PATH_t3lib.'class.t3lib_iconworks.php');
require_once(t3lib_extMgm::extPath ('ter_doc').'class.tx_terdoc_renderdocuments.php');
require_once(t3lib_extMgm::extPath('ter_doc').'class.tx_terdoc_api.php');


class tx_terdoc_pi1 extends tslib_pibase {

	public		$prefixId = 'tx_terdoc_pi1';									// Same as class name
	public		$scriptRelPath = 'pi1/class.tx_terdoc_pi1.php';					// Path to this script relative to the extension dir.
	public		$extKey = 'ter_doc';											// The extension key.
	public		$pi_checkCHash = TRUE;											// Make sure that empty CHashes are handled correctly

	protected	$confViewMode = '';												// View mode
	protected	$confCategory = '';												// If set, only this category is shown in documents mode
	protected	$singleViewPID;
	protected	$storagePid = 0;

	/**
	 * Initializes the plugin, only called from main()
	 *
	 * @param	array		$conf: The plugin configuration array
	 * @return	void
	 * @access	protected
	 */
	protected function init($conf) {
		global $TSFE;

		$this->conf=$conf;
		$this->pi_setPiVarDefaults(); 			// Set default piVars from TS
		$this->pi_initPIflexForm();				// Init FlexForm configuration for plugin
		$this->pi_loadLL();

		$this->confViewMode = $this->pi_getFFvalue ($this->cObj->data['pi_flexform'], 'view', 'sDEF');
		$this->confCategory = $this->pi_getFFvalue ($this->cObj->data['pi_flexform'], 'category', 'sDEF');

		$this->loadStoragePid();
	}

	/**
	 * Load storage PID from ext conf
	 *
	 * @return void
	 */
	protected function loadStoragePid() {
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ter_doc'])) {
			$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ter_doc']);
			if (!empty($config['storagePid'])) {
				$this->storagePid = (int) $config['storagePid'];
			}
		}
	}

	/**
	 * The plugin's main function
	 *
	 * @param	string		$content: Content rendered so far (not used)
	 * @param	array		$conf: The plugin configuration array
	 * @return	string		The plugin's HTML output
	 * @access	public
	 */
	public function main($content,$conf)	{
		global $TSFE;

		$this->init($conf);

				// Set the magic "reg1" so we can clear the cache for this page if a manual was changed.
				// The default is "_all" which means that the cache is cleared if any manual changes.
				// Single views usually override this setting so their cache is only flushed when one specific manual changes.
		if (t3lib_extMgm::isLoaded ('ter_doc')) {
			$terDocAPIObj = tx_terdoc_api::getInstance();
			$TSFE->page_cache_reg1 = $terDocAPIObj->createAndGetCacheUidForExtensionVersion('_all','');
		}

		if ($this->confViewMode == 'ter1_redirect') {
			return $this->pi_wrapInBaseClass($this->renderTER1Redirect ());
		}

		if (isset ($this->piVars['extensionkey']) && $this->piVars['extensionkey']) {
			if (strlen($this->piVars['version']) == 0 || $this->piVars['version'] == 'current') {
				$this->piVars['version'] = $this->db_getMostCurrentVersionNumberOfManual($this->piVars['extensionkey']);
			}
			if (isset ($this->piVars['format']) && $this->piVars['format']) {
				$content .= $this->renderDocumentFormat ($this->piVars['extensionkey'], $this->piVars['version'], $this->piVars['format']);
			} else {
				$content .= $this->renderListOfFormats ($this->piVars['extensionkey'], $this->piVars['version']);
			}
		} elseif ($this->confViewMode == 'documents_sorted') {
			$content .= $this->renderSortedListOfDocuments();
		} else {
			$content .= $this->renderSimpleListOfDocuments();
		}

		return $this->pi_wrapInBaseClass($content);
	}





	/******************************************************
	 *
	 * Render functions (protected)
	 *
	 ******************************************************/

	/**
	 * Renders a simple list of all available documents
	 *
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderSimpleListOfDocuments () {
		global $TYPO3_DB, $TSFE;

		$output = '';
		$listItems = array();

		$renderDocumentsObj = tx_terdoc_renderdocuments::getInstance();
		$outputFormatsArr = $renderDocumentsObj->getOutputFormats();

		$manualsArr = $this->db_fetchManualRecords(intval($this->confCategory));

		foreach ($manualsArr as $extensionKey => $detailsArr) {

				// Check if at least one output format is available:
			$formatsAvailable = FALSE;
			foreach ($outputFormatsArr as $formtKey => $formatDetailsArr) {
				if ($formatDetailsArr['object']->isAvailable ($extensionKey, $detailsArr['version'])) {
					$formatsAvailable = TRUE;
				}
			}

			if ($formatsAvailable) {
				$title = $this->csConvHSC($detailsArr['title']).' <span class="extensionkey">('.$this->csConvHSC($extensionKey).')</span>';
				$parameters = array(
					'extensionkey' => $extensionKey,
					'version' => 'current',
				);

				$listItems[] = '<li class="level-1">'.$this->pi_linkTP_keepPIVars ($title, $parameters, 1).'</li>';
			}
		}

			// Assemble the whole table:
		$output = '
			'.$this->renderCategoryDescription(intval($this->confCategory)).'
			<ul>
				'.implode ('', $listItems).'
			</ul>
		';

		return $output;
	}

	/**
	 * Renders a list of all available documents sorted alphabetically
	 *
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderSortedListOfDocuments () {
		global $TYPO3_DB, $TSFE;

		$output = '';
		$listRows = array();

		$renderDocumentsObj = tx_terdoc_renderdocuments::getInstance();
		$outputFormatsArr = $renderDocumentsObj->getOutputFormats();

		$manualsArr = $this->db_fetchManualRecords(intval($this->confCategory));

		$itemsArr = array();
		$currentLetter = '';
		foreach ($manualsArr as $extensionKey => $detailsArr) {

				// Check if at least one output format is available:
			$formatsAvailable = FALSE;
			foreach ($outputFormatsArr as $formtKey => $formatDetailsArr) {
				if ($formatDetailsArr['object']->isAvailable ($extensionKey, $detailsArr['version'])) {
					$formatsAvailable = TRUE;
				}
			}

			if ($formatsAvailable) {
				$title = $this->csConvHSC($detailsArr['title']).' <span class="extensionkey">('.$this->csConvHSC($extensionKey).')</span>';
				$parameters = array(
					'extensionkey' => $extensionKey,
					'version' => 'current',
				);

				if (strtoupper(substr ($title,0,1)) != $currentLetter) {
					$currentLetter = strtoupper(substr ($title,0,1));
					$itemsArr[] = '<li><h3>'.$currentLetter.'</h3></li>';
				}
				$itemsArr[] = '<li class="level-1" >'.$this->pi_linkTP_keepPIVars ($title, $parameters, 1).'</li>';
			}
		}

			// Assemble the whole directory:
		$output = '
			'.$this->renderCategoryDescription(intval($this->confCategory)).'
			<ul>
				'.implode ('', $itemsArr).'
			</ul>
		';

		return $output;
	}

	/**
	 * Renders a list of available document formats for the specified extension version
	 *
	 * @param	string		$extensionKey: Extension key of the extension we render document formats for
	 * @param	string		$version: Version string of the extension
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderListOfFormats ($extensionKey, $version) {
		global $TYPO3_DB, $TSFE;

		$output = '';
		$tableRows = array();

		$renderDocumentsObj = tx_terdoc_renderdocuments::getInstance();
		$outputFormatsArr = $renderDocumentsObj->getOutputFormats();

		$manualArr = $this->db_fetchManualRecord($extensionKey, $version);
		$title = $this->csConvHSC($manualArr['title']);
		$author =  $this->cObj->getTypoLink ($this->csConvHSC($manualArr['authorname']), $manualArr['authoremail']);

			// Set the magic "reg1" so we can clear the cache for this manual if a new one is uploaded:
		$terDocAPIObj = tx_terdoc_api::getInstance();
		$TSFE->page_cache_reg1 = $terDocAPIObj->createAndGetCacheUidForExtensionVersion ($extensionKey, $manualArr['version']);
		$TSFE->altPageTitle = 'Documentation: '.$title.' (available formats)';
		$TSFE->page['title'] = $TSFE->altPageTitle;
		$TSFE->indexedDocTitle = $TSFE->altPageTitle;

		$versionInfo = '<p>'.sprintf(htmlspecialchars($this->pi_getLL('formats_relatestoversion')), $manualArr['version'], $this->csConvHSC($extensionKey)).'</p>';

		$formatLinks = '';
		foreach ($outputFormatsArr as $key => $formatDetailsArr) {
			if ($formatDetailsArr['object']->isAvailable ($extensionKey, $manualArr['version'])) {
				$label = $this->csConvHSC($TSFE->sL($formatDetailsArr['label']));
				$link = $this->pi_linkTP_keepPIVars ($label, array('extensionkey' => $extensionKey, 'version' => $version, 'format' => $key), ($formatDetailsArr['type'] == 'download' ? 0 : 1));
				$size = ($formatDetailsArr['type'] == 'download') ? t3lib_div::formatSize($formatDetailsArr['object']->getDownloadFileSize ($extensionKey, $manualArr['version'])).'B': '';

				$formatLinks .= $link.' '.$size.'<br />';
			}
		}

		$tableRows[] = '
			<tr>
				<td><img src="'.t3lib_extMgm::siteRelPath('ter_doc').'res/flags/'.($manualArr['language'] ? $manualArr['language'] : 'en').'.gif" width="20" height="12" alt="" title="" /></td>
				<td><strong>'.$title.'</strong> - last update: '.strftime ('%d.%m.%Y %H:%M', $manualArr['modificationdate']).'</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>'.$formatLinks.'</td>
			</tr>
		';

			// Assemble the whole output:
		$output = '
			'.$this->renderTopNavigation().'
			<h2>'.$title.'</h2>
			<p>Author: '.$author.'</p>
			'.(strlen($manualArr['abstract']) ? ('<p>'.$this->csConvHSC($manualArr['abstract'], 'utf-8').'</p><br />') : '') .'
			<table>
				'.implode ('', $tableRows).'
			</table>
			<br />
			'.$versionInfo.'
		';

		return $output;
	}

	/**
	 * Renders a single document in a certain format
	 *
	 * @param	[type]		$extensionKey: ...
	 * @param	[type]		$version: ...
	 * @param	[type]		$format: ...
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderDocumentFormat($extensionKey, $version, $format) {
		global $TSFE, $TYPO3_DB;

			// Fetch output formats information:
		$renderDocumentsObj = tx_terdoc_renderdocuments::getInstance();
		$outputFormatsArr = $renderDocumentsObj->getOutputFormats();
		$manualArr = $this->db_fetchManualRecord($extensionKey, $version);

			// Set the magic "reg1" so we can clear the cache for this manual if a new one is uploaded:
		$terDocAPIObj = tx_terdoc_api::getInstance();
		$TSFE->page_cache_reg1 = $terDocAPIObj->createAndGetCacheUidForExtensionVersion ($extensionKey, $manualArr['version']);

		if (!is_array ($outputFormatsArr[$format])) return $this->pi_getLL('error_outputformatnotavailable','',1);
		if (!is_object ($outputFormatsArr[$format]['object'])) return $this->pi_getLL('error_outputformatnoobject','',1);
		if (!$outputFormatsArr[$format]['object']->isAvailable ($extensionKey, $version)) {
			return $this->pi_getLL('error_documentiscurrentlynotavailableinthisformat','',1);
		}

		switch ($outputFormatsArr[$format]['type']) {
			case 'download' :
				if (!is_a ($outputFormatsArr[$format]['object'], 'tx_terdoc_documentformat_download')) return $this->pi_getLL('error_outputformatisofwrongclasstype','',1);
				$this->transferFile ($outputFormatsArr[$format]['object']->getDownloadFileFullPath ($extensionKey, $manualArr['version']));
				exit();
			break;
			case 'display':
			default:
				if (!is_a ($outputFormatsArr[$format]['object'], 'tx_terdoc_documentformat_display')) return $this->pi_getLL('error_outputformatisofwrongclasstype','',1);
				$output = '
					'.$this->renderTopNavigation().'
					'.$outputFormatsArr[$format]['object']->renderDisplay ($extensionKey, $version, $this);
			break;
		}

		return $output;
	}

	/**
	 * Renders the description of the given document category
	 *
	 * @param	integer		$categoryUid: Uid of the category to render
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderCategoryDescription ($categoryUid) {
		global $TYPO3_DB;

		$output = '';
		$res = $TYPO3_DB->exec_SELECTquery (
			'title, description',
			'tx_terdoc_categories',
			'uid='.intval($categoryUid)
		);
		if ($res) {
			$row = $TYPO3_DB->sql_fetch_assoc ($res);
			$output = $this->renderTopNavigation().
				//'<h2>' . htmlspecialchars($row['title']) . '</h2>' .
				'<p>' . htmlspecialchars($row['description']) . '</p>';
		}
		return $output;
	}

	/**
	 * @return	[type]		...
	 * @access	protected
	 */
	protected function renderTopNavigation() {
		global $TSFE;

return '';
		$output = '';
		$breadCrumbs = 'Path: '.$this->pi_linkTP ('',array(),1);

		if (isset($this->piVars['extensionkey'])) {
			$breadCrumbs .= ' &gt '.$this->pi_linkTP ($this->piVars['extensionkey'].' formats', array ('tx_terdoc_pi1[extensionkey]' => $this->piVars['extensionkey']), 1);
		}
		if (isset($this->piVars['format']) && $this->piVars['format']) {
			$renderDocumentsObj = tx_terdoc_renderdocuments::getInstance();
			$outputFormatsArr = $renderDocumentsObj->getOutputFormats();
			$formatLabel = $this->csConvHSC($TSFE->sL($outputFormatsArr[$this->piVars['format']]['label']));

			$breadCrumbs .= ' &gt '.$this->pi_linkTP ($formatLabel, array ('tx_terdoc_pi1[extensionkey]' => $this->piVars['extensionkey'], 'tx_terdoc_pi1[version]' => $this->piVars['version'], 'tx_terdoc_pi1[format]' => $this->piVars['format']), 1);
		}
		$output = $breadCrumbs;

		return $output;
	}

	/**
	 * Compatibility function.
	 *
	 * Renders a message and a link to the actual manual for those requests which
	 * come from links used in the TER version 1.
	 *
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderTER1Redirect () {
		global $TYPO3_DB, $TSFE;

		$extensionKey = '';

		if (strlen($this->piVars['extensionkey'])) {
			$extensionKey = $this->piVars['extensionkey'];
		} elseif (is_array(t3lib_div::_GET('tx_extrepmgm_pi1'))) {
			$extrepMgmPi1Arr = t3lib_div::_GET('tx_extrepmgm_pi1');
			$res = $TYPO3_DB->exec_SELECTquery (
				'extension_key',
				'tx_extrep_keytable',
				'uid='.intval($extrepMgmPi1Arr['extUid'])
			);
			if ($res) {
				$row = $TYPO3_DB->sql_fetch_assoc($res);
				$extensionKey = $row['extension_key'];
			}
		}

		if (!strlen ($extensionKey)) return '';

		$output = '
			<h2>'.$this->pi_getLL('ter1redirect_heading','',1).'</h2>
			<p>'.nl2br ($this->pi_getLL('ter1redirect_introduction','',1)).'</p>
		';

		$renderDocumentsObj = tx_terdoc_renderdocuments::getInstance();
		$outputFormatsArr = $renderDocumentsObj->getOutputFormats();

		$manualRecord = $this->db_fetchManualRecord ($extensionKey, 'current');

			// Check if at least one output format is available:
		$formatsAvailable = FALSE;
		if (is_array ($manualRecord)) {
			foreach ($outputFormatsArr as $formatKey => $formatDetailsArr) {
				if ($formatDetailsArr['object']->isAvailable ($extensionKey, $manualRecord['version'])) {
					$formatsAvailable = TRUE;
				}
			}
		}

		if ($formatsAvailable) {

			$terDocAPIObj = tx_terdoc_api::getInstance();

			$uid = $terDocAPIObj->getViewPageIdForExtensionVersion ($extensionKey, $manualRecord['version']);

			$parameters = array(
				'tx_terdoc_pi1[extensionkey]' => $extensionKey,
				'tx_terdoc_pi1[version]' => 'current',
				'tx_terdoc_pi1[format]' => 'ter_doc_html_onlinehtml',
			);


			$documentURL = $this->pi_getPageLink($uid, '', $parameters);
			$linkToDocument = '<a href="'.$documentURL.'">'.t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST').'/'.$documentURL.'</a>';

			$output .= '
				<p>'.$this->pi_getLL('ter1redirect_formatavailable','',1).'</p><br />
				'.$linkToDocument.'
			';
		} else {
			$output .= '
				<p>'.$this->pi_getLL('ter1redirect_noformatavailable','',1).'</p><br />
			';

		}

		return $output;
	}





	/******************************************************
	 *
	 * Database related functions (protected)
	 *
	 ******************************************************/

	/**
	 * Returns all records from tx_terdoc_manualscategories which assign
	 * the categories to certain extension keys.
	 *
	 * @return	array		The extensionkey / category records
	 * @access	protected
	 */
	protected function db_fetchCategoryAssignments() {
		global $TYPO3_DB;

		$categoriesArr = array();
		$res = $TYPO3_DB->exec_SELECTquery (
			'extensionkey, categoryuid',
			'tx_terdoc_manualscategories',
			'pid=' . (int) $this->storagePid
		);
		while ($row = $TYPO3_DB->sql_fetch_assoc ($res)) {
			$categoriesArr[$row['categoryuid']][] = $row['extensionkey'];
		}
		return $categoriesArr;
	}

	/**
	 * Returns all manual records from tx_terdoc_manuals. If $filterByCategory is
	 * greater than zero, only manuals with that category uid will be returned.
	 *
	 * Note: Only the manual record of the most recent version of an extension will be
	 *       returned!
	 *
	 * @param	integer		$filterByCategory: UID of the category which the manuals must be in or zero.
	 * @return	array		Manual records
	 * @access	protected
	 */
	protected function db_fetchManualRecords ($filterByCategory) {
		global $TYPO3_DB;

		$categoryAssignmentsArr = array();
		if ($filterByCategory) {
		  	$tmp = $this->db_fetchCategoryAssignments();
		  	$categoryAssignmentsArr = $tmp[$filterByCategory] ? $tmp[$filterByCategory] : array();
		} 

		if ($filterByCategory) {
			$res = $TYPO3_DB->exec_SELECTquery(
				'isdefault',
				'tx_terdoc_categories',
				'uid='.intval($filterByCategory)
			);
			$categoryArr = $TYPO3_DB->sql_fetch_assoc($res);
		}

		$res = $TYPO3_DB->exec_SELECTquery (
			'*',
			'tx_terdoc_manuals',
			'pid=' . (int) $this->storagePid,
			'title,version',
			'title ASC'
		);

		if ($res) {
			$manualsArr = array();
			while ($row = $TYPO3_DB->sql_fetch_assoc ($res)) { 
				if (
						!$filterByCategory ||
						in_array($row['extensionkey'], $categoryAssignmentsArr) ||
						($categoryArr['isdefault'] && !isset($categoryAssignmentsArr[$row['extensionkey']]))
					) {
					if (!is_array ($manualsArr[$row['extensionkey']]) || version_compare($row['version'], $manualsArr[$row['extensionkey']]['version'], '>')) {
						$manualsArr[$row['extensionkey']] = array (
							'version' => $row['version'],
							'title' => $row['title'],
							'language' => $row['language'],
							'modificationdate' => $row['modificationdate']
						);
					}
				}
			}
			return $this->arraySortBySubKey ($manualsArr, 'title');
		} else return FALSE;
	}

	/**
	 * Returns one manual record from tx_terdoc_manuals for the specified
	 * extension version
	 *
	 * @param	string		$extensionKey: Extension key
	 * @param	string		$version: Version string of the extension or "current" to fetch the most recent version
	 * @return	mixed		One manual record as an array or FALSE if request was not succesful
	 * @access	protected
	 */
	protected function db_fetchManualRecord ($extensionKey, $version) {
		global $TYPO3_DB;

		$res = $TYPO3_DB->exec_SELECTquery (
			'*',
			'tx_terdoc_manuals',
			'extensionkey="'.$TYPO3_DB->quoteStr($extensionKey,'tx_terdoc_manuals').'" AND version="'.$TYPO3_DB->quoteStr($version,'tx_terdoc_manuals').'" AND pid=' . (int) $this->storagePid
		);

		if ($res) {
			return $TYPO3_DB->sql_fetch_assoc ($res);
		} else return FALSE;
	}
	
	/**
	 * Returns the version number of the most current manual version of the specified
	 * extension.
	 * 
	 * @param	string		$extensionKey: The extension key
	 * @return	mixed		Either the version number or FALSE if no manual could be found at all
	 */
	protected function db_getMostCurrentVersionNumberOfManual($extensionKey) {
		global $TYPO3_DB;
		
		$res = $TYPO3_DB->exec_SELECTquery (
			'version',
			'tx_terdoc_manuals',
			'extensionkey="'.$TYPO3_DB->quoteStr($extensionKey,'tx_terdoc_manuals').'" AND pid=' . (int) $this->storagePid
		);

		if ($res) {
			$manualArr = NULL;
			while ($row = $TYPO3_DB->sql_fetch_assoc ($res)) {
				if (!isset ($manualArr['version']) || version_compare($row['version'], $manualArr['version'], '>')) {
					$manualArr = $row;
				}
			}
			return (is_array($manualArr) ? $manualArr['version'] : FALSE);
		}
	}





	/******************************************************
	 *
	 * Miscellaneous helper functions (protected)
	 *
	 ******************************************************/

	/**
	 * Processes the given string with htmlspecialchars and converts the result
	 * from utf-8 to the charset of the current frontend
	 * page
	 *
	 * @param	string		$string: The utf-8 string to convert
	 * @return	string		The converted string
	 * @access	protected
	 */
	protected function csConvHSC ($string) {
		return $GLOBALS['TSFE']->csConv(htmlspecialchars($string), 'utf-8');
	}

	/**
	 * Sorts the given associative array by the value of the
	 * given subkey from the second level array.
	 *
	 * $array = array (
	 *    'key' => array (
	 *       'subkey' => $value,
	 *       'otherkey' => $otherValue,
	 *    )
	 * )
	 *
	 * @param	string		$string: The utf-8 string to convert
	 * @param	[type]		$subKey: ...
	 * @return	string		The converted string
	 * @access	protected
	 */
	protected function arraySortBySubKey ($array, $subKey) {
		$sortedArr = array();
		$sortValuesArr = array();
		foreach ($array as $key => $valueArr) {
			$sortValuesArr[$key] = $array[$key][$subKey];
		}
		natcasesort ($sortValuesArr);
		foreach ($sortValuesArr as $key => $dummyValue) {
			$sortedArr[$key] = $array[$key];
		}
		return $sortedArr;
	}

	/**
	 * Transfers a file to the client browser.
	 * NOTE: This function must be called *before* any HTTP headers have been sent!
	 *
	 * @param	string		$fullPath: Full absolute path including filename which leads to the file to be transfered
	 * @return	boolean		TRUE if successful, FALSE if file did not exist.
	 * @access	protected
	 */
	protected function transferFile ($fullPath) {

		if (!@file_exists($fullPath)) return FALSE;

		$filename = basename($fullPath);
		header('Content-Disposition: attachment; filename='.$filename.'');
		header('Content-type: x-application/octet-stream');
		header('Content-Transfer-Encoding: binary');
		header('Content-length:'.filesize($fullPath).'');
		readfile($fullPath);
		return TRUE;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_doc/pi1/class.tx_terdoc_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_doc/pi1/class.tx_terdoc_pi1.php']);
}

?>