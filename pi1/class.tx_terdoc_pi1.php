<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Robert Lemke (robert@typo3.org)
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
 * @author	Robert Lemke <robert@typo3.org>
 */


require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(PATH_t3lib.'class.t3lib_iconworks.php');
require_once(t3lib_extMgm::extPath ('ter_doc').'class.tx_terdoc_renderdocuments.php');

class tx_terdoc_pi1 extends tslib_pibase {
	
	public		$prefixId = 'tx_terdoc_pi1';									// Same as class name
	public		$scriptRelPath = 'pi1/class.tx_terdoc_pi1.php';					// Path to this script relative to the extension dir.
	public		$extKey = 'ter_doc';											// The extension key.
	public		$pi_checkCHash = TRUE;											// Make sure that empty CHashes are handled correctly
	
	protected	$confViewMode = '';													// View mode, one of the following: categories, documents
	protected	$confCategory = '';												// If set, only this category is shown in documents mode
	protected	$singleViewPID; 
		
	/**
	 * Initializes the plugin, only called from main()
	 * 
	 * @param	array	$conf: The plugin configuration array
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
	}
	
	/**
	 * The plugin's main function
	 * 
	 * @param	string	$content: Content rendered so far (not used)
	 * @param	array	$conf: The plugin configuration array
	 * @return	string	The plugin's HTML output
	 * @access	public
	 */
	public function main($content,$conf)	{		
		$this->init($conf);

		if (isset ($this->piVars['extensionkey'])) {
			if (isset ($this->piVars['format'])) {
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





	/**
	 * Renders a simple list of all available documents
	 * 
	 * @return	string	HTML output
	 * @access	protected
	 */
	protected function renderSimpleListOfDocuments () {
		global $TYPO3_DB, $TSFE;

		$output = '';
		$tableRows = array();
		
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
				$title = $this->csConvHSC($detailsArr['title']).' <em>('.$this->csConvHSC($extensionKey).')</em>';			
				$parameters = array(
					'extensionkey' => $extensionKey, 
					'version' => 'current',
				);
				
				$tableRows[] = '<tr><td>'.$this->pi_linkTP_keepPIVars ($title, $parameters, 1).'</td></tr>';
			}
		}
					
			// Assemble the whole table:
		$output = '
			'.$this->renderCategoryDescription(intval($this->confCategory)).'
			<br />
			<table>
				'.implode ('', $tableRows).'
			</table>
		';		
		
		return $output;
	}

	/**
	 * Renders a list of all available documents sorted alphabetically
	 * 
	 * @return	string	HTML output
	 * @access	protected
	 */
	protected function renderSortedListOfDocuments () {
		global $TYPO3_DB, $TSFE;

		$output = '';
		$tableRows = array();
		
		$renderDocumentsObj = tx_terdoc_renderdocuments::getInstance();
		$outputFormatsArr = $renderDocumentsObj->getOutputFormats();

		$manualsArr = $this->db_fetchManualRecords(intval($this->confCategory));

		$cellsArr = array();
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
				$title = $this->csConvHSC($detailsArr['title']).' <em>('.$this->csConvHSC($extensionKey).')</em>';			
				$parameters = array(
					'extensionkey' => $extensionKey, 
					'version' => 'current',
				);
				
				if (strtoupper(substr ($title,0,1)) != $currentLetter) {
					$currentLetter = strtoupper(substr ($title,0,1));
					$cellsArr[] = '<td><strong>'.$currentLetter.'</strong></td>';
				}			
				$cellsArr[] = '<td nowrap="nowrap">'.$this->pi_linkTP_keepPIVars ($title, $parameters, 1).'</td>';
			}
		}
					
		$leftColumnArr = array();
		$rightColumnArr = array();
		$counter = 0;
		$entriesPerColumn = ceil (count ($cellsArr) / 2);
		foreach ($cellsArr as $cell) {
			if ($counter < $entriesPerColumn) {
				$leftColumnArr[] = $cell;
			} else {
				$rightColumnArr[] = $cell;
			}
			$counter++;
		}		
		
			// Assemble the whole table:
		$tableRows = array();
		reset ($leftColumnArr);
		reset ($rightColumnArr);
		for ($i=0; $i <= $entriesPerColumn; $i++) {
			$tableRows[] = '<tr>'.array_shift($leftColumnArr).array_shift($rightColumnArr).'</tr>';
		}			

		$output = '
			'.$this->renderCategoryDescription(intval($this->confCategory)).'
			<br />
			<table>
				'.implode ('', $tableRows).'
			</table>
		';		
		
		return $output;
	}

	/**
	 * Renders a list of available document formats for the specified extension version
	 * 
	 * @return	string	HTML output
	 * @access	protected
	 */
	protected function renderListOfFormats ($extensionKey, $version) {
		global $TYPO3_DB, $TSFE;

		$output = '';
		$tableRows = array();
		$version = isset ($version) ? $version : 'current';
		
		$renderDocumentsObj = tx_terdoc_renderdocuments::getInstance();
		$outputFormatsArr = $renderDocumentsObj->getOutputFormats();

		$manualArr = $this->db_fetchManualRecord($extensionKey, $version);
		$title = $this->csConvHSC($manualArr['title']);
		$author =  $this->cObj->getTypoLink ($this->csConvHSC($manualArr['authorname']), $manualArr['authoremail']);

		$versionInfo = '<p>This document is related to version '.$manualArr['version'].' of the extension '.$this->csConvHSC($extensionKey).'.</p>';
		
		$formatLinks = '';
		foreach ($outputFormatsArr as $key => $formatDetailsArr) {
			if ($formatDetailsArr['object']->isAvailable ($extensionKey, $manualArr['version'])) {				
				$label = $this->csConvHSC($TSFE->sL($formatDetailsArr['label']));
				$link = $this->pi_linkTP_keepPIVars ($label, array('extensionkey' => $extensionKey, 'version' => $version, 'format' => $key), 1);
				$size = ($formatDetailsArr['type'] == 'download') ? t3lib_div::formatSize($formatDetailsArr['object']->getDownloadFileSize ($extensionKey, $manualArr['version'])).'B': '';
				
				$formatLinks .= '[ICON] '.$link.' '.$size.'<br />';
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
			<br />
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
	 * @return	string	HTML output
	 * @access	protected
	 */
	protected function renderDocumentFormat($extensionKey, $version, $format) {
		global $TSFE;
		
				// Fetch output formats information:
		$renderDocumentsObj = tx_terdoc_renderdocuments::getInstance();
		$outputFormatsArr = $renderDocumentsObj->getOutputFormats();
		$manualArr = $this->db_fetchManualRecord($extensionKey, $version);

		if (!is_array ($outputFormatsArr[$format])) return $this->pi_getLL('error_outputformatnotavailable','',1);
		if (!is_object ($outputFormatsArr[$format]['object'])) return $this->pi_getLL('error_outputformatnoobject','',1);
		if (!method_exists ($outputFormatsArr[$format]['object'], 'renderDisplay')) return $this->pi_getLL('error_outputformathasnodisplaymethod','',1);
		if (!$outputFormatsArr[$format]['object']->isAvailable ($extensionKey, $manualArr['version'])) {
			return $this->pi_getLL('error_documentiscurrentlynotavailableinthisformat','',1);
		}
		
		$output = '
			'.$this->renderTopNavigation().'
			'.$outputFormatsArr[$format]['object']->renderDisplay ($extensionKey, $manualArr['version'], $this);
		
		return $output;		
	}

	/**
	 * Renders the description of the given document category
	 * 
	 * @param	integer	$categoryUid: Uid of the category to render
	 * @return	string	HTML output
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
			$output = '
				'.$this->renderTopNavigation().'<br />
				<h2>'.htmlspecialchars($row['title']).'</h2>
				<p>'.htmlspecialchars($row['description']).'</p>
			';
		}
		return $output;							
	}
	
	/**
	 *
	 * @access	protected 
	 */
	protected function renderTopNavigation() {
		global $TSFE;
		
		$output = '';		
		$breadCrumbs = 'Path: '.$this->pi_linkTP ('',array(),1);	
		
		if (isset($this->piVars['extensionkey'])) {
			$breadCrumbs .= ' &gt '.$this->pi_linkTP ($this->piVars['extensionkey'].' formats', array ('tx_terdoc_pi1[extensionkey]' => $this->piVars['extensionkey']), 1);	
		}
		if (isset($this->piVars['format'])) {
			$renderDocumentsObj = tx_terdoc_renderdocuments::getInstance();
			$outputFormatsArr = $renderDocumentsObj->getOutputFormats();
			$formatLabel = $this->csConvHSC($TSFE->sL($outputFormatsArr[$this->piVars['format']]['label']));

			$breadCrumbs .= ' &gt '.$this->pi_linkTP ($formatLabel, array ('tx_terdoc_pi1[extensionkey]' => $this->piVars['extensionkey'], 'tx_terdoc_pi1[version]' => $this->piVars['version'], 'tx_terdoc_pi1[format]' => $this->piVars['format']), 1);	
		}
		$output = $breadCrumbs;
		
		return $output;	
	}





	/**
	 * Returns all records from tx_terdoc_manualscategories which assign
	 * the categories to certain extension keys.
	 * 
	 * @return	array	The extensionkey / category records
	 * @access	protected 
	 */
	protected function db_fetchCategoryAssignments() {
		global $TYPO3_DB;
		
		$categoriesArr = array();
		$res = $TYPO3_DB->exec_SELECTquery (
			'extensionkey, categoryuid',
			'tx_terdoc_manualscategories',
			'1'
		);
		while ($row = $TYPO3_DB->sql_fetch_assoc ($res)) {
			$categoriesArr[$row['extensionkey']] = $row['categoryuid'];
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

		$categoryAssignmentsArr = ($filterByCategory)  ? $this->db_fetchCategoryAssignments() : array();
		if ($filterByCategory) {
			$res = $TYPO3_DB->exec_SELECTquery('isdefault', 'tx_terdoc_categories', 'uid='.intval($filterByCategory));
			$categoryArr = $TYPO3_DB->sql_fetch_assoc($res);
		}

		$res = $TYPO3_DB->exec_SELECTquery (
			'*',
			'tx_terdoc_manuals',
			'1',
			'title,version',
			'title ASC'
		);
		
		if ($res) {
			$manualsArr = array();
			while ($row = $TYPO3_DB->sql_fetch_assoc ($res)) {
				if (
						!$filterByCategory || 
						$categoryAssignmentsArr[$row['extensionkey']] == intval($this->confCategory) ||
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

		if ($version == 'current') {
			$res = $TYPO3_DB->exec_SELECTquery (
				'*',
				'tx_terdoc_manuals',
				'extensionkey="'.$extensionKey.'"'
			);

			if ($res) {
				$manualArr = array();
				while ($row = $TYPO3_DB->sql_fetch_assoc ($res)) {
					if (!isset ($manualArr['version']) || version_compare($row['version'], $manualArr['version'], '>')) {
						$manualArr = $row;
					}
				}
				return $manualArr;
			} else return FALSE;
		} else {
			$res = $TYPO3_DB->exec_SELECTquery (
				'*',
				'tx_terdoc_manuals',
				'extensionkey="'.$extensionKey.'" AND version="'.$version.'"'
			);
			
			if ($res) {
				return $TYPO3_DB->sql_fetch_assoc ($res);
			} else return FALSE;

		}		
	}
	
	/**
	 * Processes the given string with htmlspecialchars and converts the result
	 * from utf-8 to the charset of the current frontend
	 * page 
	 * 
	 * @param	string	$string: The utf-8 string to convert
	 * @return	string	The converted string
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
	 * @param	string	$string: The utf-8 string to convert
	 * @return	string	The converted string
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

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_doc/pi1/class.tx_terdoc_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_doc/pi1/class.tx_terdoc_pi1.php']);
}

?>