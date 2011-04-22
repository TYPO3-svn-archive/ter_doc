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
 * A set of validation method
 *
 * @copyright Copyright belongs to the respective authors
 */
class Tx_TerDoc_Validator_Environment {

	/**
	 * constructor
	 *
	 * @param array $settings options passed from the CLI
	 */
	public function __construct($settings, $arguments) {
		$this->settings = $settings;
		$this->arguments = $arguments;
		
		// possible check to come whether the appropriate modules are loaded
		#$extensions = get_loaded_extensions();
	}

	/**
	 * Validate if the Data Source exists. If not the method will throw an exception
	 *
	 * @return void
	 */
	public function validateDataSource() {
	
		// Check if the datasource exists
		if (file_exists($this->settings['extensionFile'])) {
			$currentMD5Hash = md5_file($this->settings['extensionFile']);
		} else {
			throw new Exception('Exception thrown #1294747712: no data source has been found at "' . $this->settings['extensionFile'] . '". Please run command "fetch". Type option --help for more info', 1294747712);
		}
	}
	
	/**
	 * Validate the running environment. Check whether the path are correct and if some directories exist
	 *
	 * @return void
	 */
	public function validateFileStructure() {

		// if home directory is not defined, create this one now.
		if (!is_dir($this->settings['documentsCache'])) {
			// @todo: check whether a set up action would be preferable
			//throw new Exception('Exception thrown #1294746784: temp directory does not exist "' . $this->settings['documentsCache'] . '". Run command setUp', 1294746784);
			try {
				mkdir($this->settings['documentsCache'], 0777, TRUE);
			} catch (Exception $e) {
				Tx_TerDoc_Utility_Cli::log($e->getMessage());
			}
		}

		// Check if configuration is valid ...and throw error if that is not the case
		if (!is_dir($this->settings['repositoryDir'])) {
			throw new Exception('Exception thrown #1294657643: directory does not exist "' . $this->settings['repositoryDir'] . '". Make sure key "repositoryDir" is properly defined in file ' . $configurationArray['typoscriptFile'], 1294657643);
		}
	}

}

?>