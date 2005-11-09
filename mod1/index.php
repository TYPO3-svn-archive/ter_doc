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
 * Module 'TER Docs' for the 'ter_doc' extension.
 *
 * $Id$
 *
 * @author	Robert Lemke <robert@typo3.org>
 */

unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
$LANG->includeLLFile("EXT:ter_doc/mod1/locallang.php");

require_once (PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.

class tx_terdoc_module1 extends t3lib_SCbase {

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 * 
	 * @return	void
	 * @access	public
	 */
	public function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			'function' => Array (
				'information' => $LANG->getLL('function_information'),
				'categories' => $LANG->getLL('function_categories'),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * 
	 * @return	void
	 * @access	public
	 */
	public function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		if ($BE_USER->user['admin']) {

				// Draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section('',$this->doc->funcMenu ('', t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
			$this->content.=$this->doc->divider(5);

				// Render content:
			$this->moduleContent();

			$this->content.=$this->doc->spacer(10);

		} else {
				// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Generates the module content
	 * 
	 * @return	void
	 * @access	protected 
	 */
	protected function moduleContent()	{

		switch((string)$this->MOD_SETTINGS['function'])	{
			case 'information':
				$this->content .= $this->renderScreen_information();
			break;
			case 'categories':
				$this->content .= $this->renderScreen_categories();
			break;
		}

	}

	protected function renderScreen_information() {
		
	}
	

	/**
	 * Renders the category management screen
	 * 
	 * @return	string		HTML output
	 * @access	protected
	 */	
	protected function renderScreen_categories() {
		global $BACK_PATH, $TYPO3_DB, $LANG;

		$output = '';

		$categoriesArr = array();
		$res = $TYPO3_DB->exec_SELECTquery ('*', 'tx_terdoc_categories', '1');		
		if ($res) {
			while ($row = $TYPO3_DB->sql_fetch_assoc ($res)) {
				$categoriesArr[$row['uid']] = $row;
			}
		} 

		$manualsCategoriesArr = array();
		$res = $TYPO3_DB->exec_SELECTquery ('*', 'tx_terdoc_manualscategories', '1');		
		if ($res) {
			while ($row = $TYPO3_DB->sql_fetch_assoc ($res)) {
				$manualsCategoriesArr[] = $row;
			}
		} 

		$extensionKeysArr = array();
		$res = $TYPO3_DB->exec_SELECTquery ('DISTINCT extensionkey', 'tx_terdoc_manuals', '1');		
		if ($res) {
			while ($row = $TYPO3_DB->sql_fetch_assoc ($res)) {
				$extensionKeysArr[] = $row['extensionkey'];
			}
		} 


		switch ((string)t3lib_div::GPvar('categoriesCmd')) {
			case 'create' :
				$output.= '
					<h3>Create new category</h3>
					<form action="'.t3lib_div::linkThisScript(array('categoriesCmd'=> '')).'" method="post">
						<table>
							<tr>
								<td style="vertical-align:top; font-weight:bold;">Title:</td>
								<td><input type="text" name="categorytitle" style="width:300px;" /></td>
							</tr>
							<tr>
								<td style="vertical-align:top; font-weight:bold;">Description:</td>
								<td><textarea name="categorydescription" rows="10" style="width:300px;"></textarea></td>
							</tr>
							<tr>
								<td style="vertical-align:top; font-weight:bold;">Is default ?</td>
								<td><input type="checkbox" name="categoryisdefault" value="1" /> yes</td>
							</tr>
							<tr>
								<td style="vertical-align:top; font-weight:bold;">View page ID</td>
								<td><input type="input" size="6" name="viewpid" /></td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td style="text-align: right;"><input type="submit" value="Create" /></td>
							</tr>
						</table>
						<input type="hidden" name="categoriesCmd" value="docreate" />
					</form>
				';				
			return $output;
			case 'docreate':
				$categoryArr = array (
					'title' => t3lib_div::GPvar('categorytitle'),
					'description' => t3lib_div::GPvar('categorydescription'),
					'isdefault' => t3lib_div::GPvar('categoryisdefault'),
					'viewpid' => intval(t3lib_div::GPvar('categoryisdefault'))
				);
				$TYPO3_DB->exec_INSERTquery ('tx_terdoc_categories', $categoryArr);
			
				$output .= '<em>Category created.</em><br /><br /><a href="index.php">Refresh view</a>';
			return $output;
			case 'delete':
				$TYPO3_DB->exec_DELETEquery ('tx_terdoc_categories', 'uid='.intval(t3lib_div::GPvar('categoriesId')));
				$TYPO3_DB->exec_DELETEquery ('tx_terdoc_manualscategories', 'categoryuid='.intval(t3lib_div::GPvar('categoriesId')));
			
				$output .= '<em>Category deleted!</em><br /><a href="index.php">Refresh view</a>';
			return $output;
			case 'assign' :
				$optionsExtensionKeys = '';
				$optionsCategories = '';
				
				foreach ($extensionKeysArr as $extensionKey) {
					$optionsExtensionKeys .= '<option value="'.$extensionKey.'" />'.$extensionKey.'</option>';
				}
				foreach ($categoriesArr as $uid => $categoryArr) {
					$optionsCategories .= '<option value="'.$uid.'" />'.$categoryArr['title'].'</option>';
				}
				
				$output.= '
					<h3>Create new category assignment</h3>
					<form action="'.t3lib_div::linkThisScript(array('categoriesCmd'=> '')).'" method="post">
						<table>
							<tr>
								<td style="vertical-align:top; font-weight:bold;">Extension:</td>
								<td><select name="categoriesExtensionKey">'.$optionsExtensionKeys.'</select></td>
							</tr>
							<tr>
								<td style="vertical-align:top; font-weight:bold;">Category:</td>
								<td><select name="categoriesId">'.$optionsCategories.'</select></td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td style="text-align: right;"><input type="submit" value="Assign" /></td>
							</tr>
						</table>
						<input type="hidden" name="categoriesCmd" value="doassign" />
					</form>
				';				
			return $output;
			case 'doassign':
				$assignmentArr = array (
					'extensionkey' => t3lib_div::GPvar('categoriesExtensionKey'),
					'categoryuid' => t3lib_div::GPvar('categoriesId')
				);
				$TYPO3_DB->exec_INSERTquery ('tx_terdoc_manualscategories', $assignmentArr);
			
				$output .= '<em>Assigned manual to the category.</em><br /><br /><a href="index.php">Refresh view</a>';
			return $output;
			case 'removeassignment':
				$TYPO3_DB->exec_DELETEquery ('tx_terdoc_manualscategories', 'uid='.intval(t3lib_div::GPvar('categoriesId')));
			
				$output .= '<em>Assignment removed!</em><br /><br /><a href="index.php">Refresh view</a>';
			return $output;
		}
				
		$output.= '<h3>Categories</h3>';
		if (count ($categoriesArr)) {
			$tableRows = array();
			$tableRows[] = '
				<tr style="background-color: '.$this->doc->bgColor2.'">
					<td style="vertical-align:top;">Title</td>
					<td style="vertical-align:top;">Description</td>
					<td style="vertical-align:top;">Is default?</td>
					<td style="vertical-align:top;">View page ID</td>
					<td>&nbsp;</td>
				</tr>
			';
			foreach ($categoriesArr as $categoryArr) {
				$tableRows[] = '
					<tr style="background-color: '.$this->doc->bgColor4.'">
						<td style="vertical-align:top;">'.htmlspecialchars($categoryArr['title']).' <em>(UID: '.$categoryArr['uid'].')</em></td>
						<td style="vertical-align:top;">'.htmlspecialchars($categoryArr['description']).'</td>
						<td style="vertical-align:top;">'.($categoryArr['isdefault'] ? 'yes' : 'no').'</td>
						<td style="vertical-align:top;">'.$categoryArr['viewpid'].'</td>
						<td style="vertical-align:top; width:1%" nowrap="nowrap">
							<a href="'.t3lib_div::linkThisScript(array('categoriesCmd'=>'edit', 'categoriesId'=> $categoryArr['uid'])).'"><img '.t3lib_iconWorks::skinImg($BACK_PATH, '/t3lib/gfx/edit2.gif').' border="0" /></a>
							<a href="'.t3lib_div::linkThisScript(array('categoriesCmd'=>'delete', 'categoriesId'=> $categoryArr['uid'])).'"><img '.t3lib_iconWorks::skinImg($BACK_PATH, '/t3lib/gfx/delete_record.gif').' border="0" /></a>
						</td>
					</tr>
				';
			}
			$output .= '<table style="width:100%;">'.implode (chr(10), $tableRows).'</table>';
		} else {
			$output .= '<p><em>No categories found.</em></p><br />';
		}		
		$output .= '<br /><a href="'.t3lib_div::linkThisScript(array('categoriesCmd'=>'create')).'"><img '.t3lib_iconWorks::skinImg($BACK_PATH, '/t3lib/gfx/new_el.gif').' border="0" /> Create new category</a><br /><br />';

		

		$output.= '<h3>Documents assigned to categories</h3>';
		
		if (count ($manualsCategoriesArr)) {
			$tableRows = array();
			$tableRows[] = '
				<tr style="background-color: '.$this->doc->bgColor2.'">
					<td style="vertical-align:top;">Extension key</td>
					<td style="vertical-align:top;">Category</td>
					<td>&nbsp;</td>
				</tr>
			';
			foreach ($manualsCategoriesArr as $manualCategoryArr) {
				$tableRows[] = '
					<tr style="background-color: '.$this->doc->bgColor4.'">
						<td style="vertical-align:top;">'.htmlspecialchars($manualCategoryArr['extensionkey']).'</td>
						<td style="vertical-align:top;">'.htmlspecialchars($categoriesArr[$manualCategoryArr['categoryuid']]['title']).'</td>
						<td style="vertical-align:top; width:1%" nowrap="nowrap">
							<a href="'.t3lib_div::linkThisScript(array('categoriesCmd'=>'removeassignment', 'categoriesId'=> $manualCategoryArr['uid'])).'"><img '.t3lib_iconWorks::skinImg($BACK_PATH, '/t3lib/gfx/delete_record.gif').' border="0" /></a>
						</td>
					</tr>
				';
			}
			$output .= '<table style="width:100%;">'.implode (chr(10), $tableRows).'</table>';
		} else {
			$output .= '<p><em>No category assignments found.</em></p><br />';
		}		
		$output .= '<br /><a href="'.t3lib_div::linkThisScript(array('categoriesCmd'=>'assign')).'"><img '.t3lib_iconWorks::skinImg($BACK_PATH, '/t3lib/gfx/new_el.gif').' border="0" /> Create new assignment</a><br /><br />';

		return $output;		
	}

	/**
	 * Prints out the module HTML
	 * 
	 * @return	void
	 * @access	public
	 */
	public function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_doc/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_doc/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_terdoc_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>