<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';

t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:ter_doc/flexform_ds_pluginmode.xml');
t3lib_extMgm::addPlugin(Array('LLL:EXT:'.$_EXTKEY.'/locallang_db.php:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');

if (TYPO3_MODE=='BE')	{		
	t3lib_extMgm::addModule('tools','txterdocM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
}

$TCA['tx_terdoc_categories'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_categories',
		'label' => 'title',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_terdoc_categories.gif',
	),
);

?>