<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Fabien Udriot <fabien.udriot@ecodev.ch>
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
 * Documentation renderer for the ter_doc extension
 * mini-howto:
 * php typo3/cli_dispatch.phpsh ter_doc help
 *
 * $Id$
 *
 * @author	Fabien Udriot <fabien.udriot@ecodev.ch>
 */
if (!defined('TYPO3_cliMode'))
	die('You cannot run this script directly!');

/**
 * CLI class that handles TER documentation
 *
 */
class Tx_TerDoc_Cli_Renderer {

	/**
	 * CLI dispatcher
	 *
	 * @param array $argv Command-line arguments
	 *
	 * @internal param \Command $array line arguments
	 * @return void
	 */
	function main($argv) {
			/** @var $controller Tx_TerDoc_Controller_CliController */
		$controller = t3lib_div::makeInstance('Tx_TerDoc_Controller_CliController');

		$arguments = $commands = array();
		$arguments['help'] = $arguments['force'] = $arguments['limit'] = FALSE;

			// Process the command's arguments
		array_shift($argv);
		$argv = array_map('trim', $argv);
		foreach ($argv as $arg) {
			if (preg_match('/^-/is', $arg)) {
				if (preg_match('/^--force$|^-f$/is', $arg)) {
					$arguments['force'] = TRUE;
				} elseif (preg_match('/^--help$|^-h$/is', $arg)) {
					$arguments['help'] = TRUE;
				} elseif (preg_match('/^--limit=(.+)$/is', $arg, $matches)) {
					$arguments['limit'] = (int) $matches[1];
				} elseif (preg_match('/^-l=(.+)$/is', $arg, $matches)) {
					$arguments['limit'] = (int) $matches[1];
				} else {
						// argument is not valid
					Tx_TerDoc_Utility_Cli::log('Uknown argument ' . $arg);
					Tx_TerDoc_Utility_Cli::log('give "--help" option to see usage');
					die();
				}
			} else {
				$commands[] = $arg;
			}
		}

			// Display help if necessary
		if (count($argv) == 0 || $arguments['help']) {
			$controller->helpAction();
			die();
		}

			// Call the right command
		if ($commands[0] == 'render') {
			try {
				$controller->renderAction($arguments);
			} catch (Exception $e) {
				Tx_TerDoc_Utility_Cli::log($e->getMessage());
			}
		}
		if ($commands[0] == 'generateIndex') {
			try {
				$controller->generateIndexAction($arguments);
			} catch (Exception $e) {
				Tx_TerDoc_Utility_Cli::log($e->getMessage());
			}
		} elseif ($commands[0] == 'update') {
			try {
				$controller->updateAction($arguments);
			} catch (Exception $e) {
				Tx_TerDoc_Utility_Cli::log($e->getMessage());
			}
		} elseif ($commands[0] == 'download') {
			try {
				$controller->downloadAction($arguments);
			} catch (Exception $e) {
				Tx_TerDoc_Utility_Cli::log($e->getMessage());
			}
		}
		else {
			Tx_TerDoc_Utility_Cli::log('Unknown command');
			Tx_TerDoc_Utility_Cli::log('Type option "--help" for usage.');
		}
	}
}

	// Run the script
	/** @var $ter Tx_TerDoc_Cli_Renderer */
$ter = t3lib_div::makeInstance('Tx_TerDoc_Cli_Renderer');
$ter->main($_SERVER['argv']);
?>