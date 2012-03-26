<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Susanne Moog <s.moog@neusta.de>
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
 * The address controller for the Address package
 *
 * @version $Id: $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_TerDoc_Utility_Cli {

	/**
	 * This method is used to log messages on the console.
	 *
	 * $param mixed $message: the message to be outputted on the console
	 * @return void
	 */
	public static function log($message = '') {
		if (is_array($message) || is_object($message)) {
			print_r($message);
		} elseif (is_bool($message) || $message === NULL) {
			var_dump($message);
		} else {
			print $message . chr(10);
		}
	}

	/**
	 * Makes sure the directory path ends with a trailling slash "/"
	 *
	 * $param mixed $path: the path to be sanitzed.
	 * @return void
	 */
	public static function sanitizeDirectoryPath($path = '') {
		if (substr($path, -1, 1) != '/') {
			$path .= '/';
		}
		return $path;
	}

	/**
	 * This method is used to return the configuration in Cli mode
	 *
	 * @return array
	 */
	public static function getSettings() {
		$settings = $localSettings = array();

		// instantiate parser
		$parseObj = t3lib_div::makeInstance('t3lib_TSparser');
		$parseObj->setup = array();
		$defaultConfigurationFile = t3lib_div::getFileAbsFileName('EXT:ter_doc/Configuration/TypoScript/static.ts');
		$parseObj->parse(file_get_contents($defaultConfigurationFile));
		$defaultSettings = $parseObj->setup['plugin.']['tx_terdoc.']['settings.'];


		// retrieve user configuration ...and throw error if configuration is not correct
		$configurationArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ter_doc']);
		
		// Make sure the configuration file exists
		if (empty($configurationArray['typoscriptFile'])) {
			throw new Exception('Exception thrown #1294655536 : no configuration file is defined. Update key "typoscriptFile" in EM', 1294655536);
		}
		$configurationArray['typoscriptFile'] = t3lib_div::getFileAbsFileName($configurationArray['typoscriptFile']);
		
		if (!is_file($configurationArray['typoscriptFile'])) {
			throw new Exception('Exception thrown #1294657536: file does not exist "' . $configurationArray['typoscriptFile'] . '". Make sure key "typoscriptFile" in EM is correct', 1294657536);
		}

		if (isset($configurationArray['repositoryDir']) && is_dir($configurationArray['repositoryDir'])) {
			$localSettings['repositoryDir'] = $configurationArray['repositoryDir'];
		}

			// Fetch content from a typoscript file...
		$parseObj->setup = array();
		$parseObj->parse(file_get_contents($configurationArray['typoscriptFile']));
		$settings = $parseObj->setup['plugin.']['tx_terdoc.']['settings.'];

		$settings = array_merge($defaultSettings, $settings, $localSettings);
		if (empty($settings)) {
			throw new Exception('Exception thrown #1294659609: something went wrong, settings are empty. Can\'go any further', 1294659609);
		}
		
		return $settings;
	}

	/**
	 * Returns the full path including file name but excluding file extension of
	 * the specified extension version in the file repository.
	 *
	 * @param	string		$baseDir: The extension base directory
	 * @param	string		$extensionKey: The extension key
	 * @param	string		$version: The version string
	 * @return	string		Full path to the document directory for the specified extension version
	 */
	public static function getExtensionVersionPathAndBaseName($baseDir, $extensionKey, $version) {
		$firstLetter = strtolower(substr($extensionKey, 0, 1));
		$secondLetter = strtolower(substr($extensionKey, 1, 1));
		$fullPath = $baseDir . $firstLetter . '/' . $secondLetter . '/';

		list ($majorVersion, $minorVersion, $devVersion) = t3lib_div::intExplode('.', $version);

		return $fullPath . strtolower($extensionKey) . '_' . $majorVersion . '.' . $minorVersion . '.' . $devVersion;
	}

	/**
	 * Returns the full path of the document directory for the specified
	 * extension version. If the path does not exist yet, it will be created -
	 * given that the typo3temp/tx_terdoc/documentscache/ dir exists.
	 *
	 * In the document directory all rendered documents are stored.
	 *
	 * @param	string		$baseDir: The extension base directory
	 * @param	string		$extensionKey: The extension key
	 * @param	string		$version: The version string
	 * @return	string		Full path to the document directory for the specified extension version
	 */
	public static function getDocumentDirOfExtensionVersion ($baseDir, $extensionKey, $version) {
		$firstLetter = strtolower (substr ($extensionKey, 0, 1));
		$secondLetter = strtolower (substr ($extensionKey, 1, 1));

 		list ($majorVersion, $minorVersion, $devVersion) = t3lib_div::intExplode ('.', $version);
		$subPath = $firstLetter.'/'.$secondLetter.'/'.strtolower($extensionKey).'-'.$majorVersion.'.'.$minorVersion.'.'.$devVersion;
		$fullPath = $baseDir.$subPath;

		if (strlen($firstLetter.$secondLetter)) {

			@mkdir($fullPath, 0777, TRUE);
			t3lib_div::fixPermissions($baseDir.$firstLetter, TRUE);
			return $fullPath.'/';
		}
	}

	/**
	 * Removes directory with all files from the given path recursively!
	 * Path must somewhere below typo3temp/
	 *
	 * @param	string		$removePath: Absolute path to directory to remove
	 * @return	void
	 * @access	protected
	 */
	public static function removeDirRecursively($removePath) {
	
		// Debug message
		// Tx_TerDoc_Utility_Cli::log(' Delete -> ' . $removePath);
		if (! t3lib_div::validPathStr($removePath)) {
			throw new Exception('Exception thrown #1300155944: not a valid path "' . $removePath . '"', 1300155944);
		}

		// Go through dirs:
		$dirs = t3lib_div::get_dirs($removePath);

		if (is_array($dirs)) {
			foreach ($dirs as $subdirs) {
				if ($subdirs) {
					self::removeDirRecursively($removePath . '/' . $subdirs); # . '/'
				}
			}
		}

		// Then files in this dir:
		$fileArr = t3lib_div::getFilesInDir($removePath, '', 1);
		if (is_array($fileArr)) {
			foreach ($fileArr as $file) {
				unlink($file);
			}
		}

		// Remove this dir:
		rmdir($removePath);
	}
}

?>