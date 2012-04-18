<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Tolleiv Nietsch
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
 * Render manuals of new extensions in TER
 *
 * @package TYPO3
 * @subpackage tx_terdoc
 */
class Tx_TerDoc_Tasks_RenderFromQueueTask extends tx_scheduler_Task {

	/**
	 * Public method, usually called by scheduler
	 *
	 * @return boolean TRUE on success
	 */
	public function execute() {
		$argv = array(
			$this->mode ?: 'renderQueue',
			'--limit=' . (intval($this->limit) ?: 10)
		);
		$renderer = t3lib_div::makeInstance('Tx_TerDoc_Cli_Renderer');
		$renderer->main($argv);
		return TRUE;
	}

}
?>