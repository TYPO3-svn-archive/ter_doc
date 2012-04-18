<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_div::loadTCA('tt_content');

	// Add tables to TCA
$TCA['tx_terdoc_manuals'] = array (
	'ctrl' => array (
		'label'             => 'extensionkey',
		'default_sortby'    => 'ORDER BY extensionkey',
		'tstamp'            => 'tstamp',
		'crdate'            => 'crdate',
		'title'             => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manuals',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'tx_terdoc_manuals.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'dividers2tabs'     => TRUE,
	)
);

$TCA['tx_terdoc_renderproblems'] = array (
	'ctrl' => array (
		'label'             => 'extensionkey',
		'default_sortby'    => 'ORDER BY tstamp',
		'tstamp'            => 'tstamp',
		'crdate'            => 'crdate',
		'title'             => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_renderproblems',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'tx_terdoc_renderproblems.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'dividers2tabs'     => TRUE,
	)
);

$TCA['tx_terdoc_categories'] = array (
	'ctrl' => array (
		'label'             => 'title',
		'default_sortby'    => 'ORDER BY title',
		'tstamp'            => 'tstamp',
		'crdate'            => 'crdate',
		'title'             => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_categories',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'tx_terdoc_categories.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'dividers2tabs'     => TRUE,
	)
);

$TCA['tx_terdoc_manualscategories'] = array (
	'ctrl' => array (
		'label'             => 'extensionkey',
		'default_sortby'    => 'ORDER BY extensionkey',
		'tstamp'            => 'tstamp',
		'crdate'            => 'crdate',
		'title'             => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manualscategories',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'tx_terdoc_manualscategories.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'dividers2tabs'     => TRUE,
	)
);

$TCA['tx_terdoc_manualspagecache'] = array (
	'ctrl' => array (
		'label'             => 'extensionkey',
		'default_sortby'    => 'ORDER BY extensionkey',
		'tstamp'            => 'tstamp',
		'crdate'            => 'crdate',
		'title'             => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manualspagecache',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'tx_terdoc_manualspagecache.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'dividers2tabs'     => TRUE,
	)
);

$TCA['tx_terdoc_renderqueue'] = array (
	'ctrl' => array (
		'label'             => 'extensionkey',
		'default_sortby'    => 'ORDER BY extensionkey',
		'tstamp'            => 'tstamp',
		'crdate'            => 'crdate',
		'title'             => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_renderqueue',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'tx_terdoc_manualscategories.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'dividers2tabs'     => TRUE,
	)
);

	// Remove the old "CODE", "Layout" and the "recursive" fields and add "flexform"
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1'] = 'layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1'] = 'pi_flexform';

	// Add plugin and datasets
t3lib_extMgm::addPlugin(array('LLL:EXT:' . $_EXTKEY . '/locallang_db.php:tt_content.list_type_pi1', $_EXTKEY . '_pi1'), 'list_type');
t3lib_extMgm::allowTableOnStandardPages('tx_terdoc_manuals');
t3lib_extMgm::allowTableOnStandardPages('tx_terdoc_renderproblems');
t3lib_extMgm::allowTableOnStandardPages('tx_terdoc_categories');
t3lib_extMgm::allowTableOnStandardPages('tx_terdoc_manualscategories');
t3lib_extMgm::allowTableOnStandardPages('tx_terdoc_manualspagecache');

	// Add flexform configuration
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:ter_doc/flexform_ds_pluginmode.xml');

	// Register module
if (TYPO3_MODE == 'BE') {
	t3lib_extMgm::addModule('user', 'txterdocM1', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
}

	// Add static configuration files
t3lib_extMgm::addStaticFile($_EXTKEY, 'res/static/', 'TER Documentation');

?>