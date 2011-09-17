<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Kai Vogel (kai.vogel@speedprogs.de)
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

require_once t3lib_extMgm::extPath('ter_doc') . 'class.tx_terdoc_renderdocuments.php';

/**
 * Render manuals of new extensions in TER
 *
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 * @package TYPO3
 * @subpackage tx_terdoc
 */
class Tx_TerDoc_Tasks_RenderManualsTask extends tx_scheduler_Task {

	/**
	 * Public method, usually called by scheduler
	 *
	 * @return boolean TRUE on success
	 */
	public function execute() {
		$renderDocsObj = tx_terdoc_renderdocuments::getInstance();
		$renderDocsObj->renderCache();
		return TRUE;
	}

}
?>