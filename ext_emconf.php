<?php

########################################################################
# Extension Manager/Repository config file for ext: "ter_doc"
#
# Auto generated 25-02-2006 19:55
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'TER Documentation',
	'description' => 'Provides a renderer, frontend plugin and backend module for the TER documentation',
	'category' => 'misc',
	'author' => 'Robert Lemke',
	'author_email' => 'robert@typo3.org',
	'shy' => '',
	'dependencies' => 'cms',
	'conflicts' => '',
	'relations' => 'Array',
	'priority' => '',
	'module' => 'mod1',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/tx_terdoc/documentscache/',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'TYPO3 Association',
	'version' => '2.0.0',
	'_md5_values_when_last_written' => 'a:61:{s:8:".project";s:4:"bdad";s:9:"ChangeLog";s:4:"bc0a";s:23:"class.tx_terdoc_api.php";s:4:"0978";s:35:"class.tx_terdoc_renderdocuments.php";s:4:"66b4";s:21:"ext_conf_template.txt";s:4:"19df";s:12:"ext_icon.gif";s:4:"9568";s:17:"ext_localconf.php";s:4:"1161";s:14:"ext_tables.php";s:4:"5cfe";s:14:"ext_tables.sql";s:4:"112f";s:24:"ext_typoscript_setup.txt";s:4:"afd4";s:26:"flexform_ds_pluginmode.xml";s:4:"2dbb";s:29:"icon_tx_terdoc_categories.gif";s:4:"9568";s:13:"locallang.xml";s:4:"c9fd";s:16:"locallang_db.xml";s:4:"5ac0";s:7:"tca.php";s:4:"0360";s:13:"mod1/conf.php";s:4:"3707";s:14:"mod1/index.php";s:4:"aa15";s:18:"mod1/locallang.xml";s:4:"a652";s:22:"mod1/locallang_mod.php";s:4:"4050";s:19:"mod1/moduleicon.gif";s:4:"40fc";s:16:"mod1/CVS/Entries";s:4:"9c30";s:19:"mod1/CVS/Repository";s:4:"5e35";s:13:"mod1/CVS/Root";s:4:"a7f0";s:27:"pi1/class.tx_terdoc_pi1.php";s:4:"1fdd";s:17:"pi1/locallang.xml";s:4:"1d99";s:24:"pi1/static/editorcfg.txt";s:4:"7fd4";s:22:"pi1/static/CVS/Entries";s:4:"d63d";s:25:"pi1/static/CVS/Repository";s:4:"9b2f";s:19:"pi1/static/CVS/Root";s:4:"a7f0";s:15:"pi1/CVS/Entries";s:4:"d600";s:18:"pi1/CVS/Repository";s:4:"018a";s:12:"pi1/CVS/Root";s:4:"a7f0";s:12:"cli/conf.php";s:4:"4339";s:28:"cli/render-documents_cli.php";s:4:"a872";s:15:"cli/CVS/Entries";s:4:"7f4d";s:18:"cli/CVS/Repository";s:4:"1800";s:12:"cli/CVS/Root";s:4:"a7f0";s:8:"doc/TODO";s:4:"6cf5";s:14:"doc/manual.sxw";s:4:"4656";s:15:"doc/CVS/Entries";s:4:"822a";s:18:"doc/CVS/Repository";s:4:"c6fd";s:12:"doc/CVS/Root";s:4:"a7f0";s:24:"res/docbook-template.xml";s:4:"0ab6";s:24:"res/oomanual2docbook.xsl";s:4:"fa20";s:16:"res/flags/da.gif";s:4:"70b4";s:16:"res/flags/de.gif";s:4:"10d4";s:16:"res/flags/en.gif";s:4:"75b8";s:16:"res/flags/es.gif";s:4:"5aae";s:16:"res/flags/fr.gif";s:4:"41e7";s:16:"res/flags/it.gif";s:4:"8024";s:16:"res/flags/lv.gif";s:4:"0d00";s:16:"res/flags/ru.gif";s:4:"0def";s:21:"res/flags/CVS/Entries";s:4:"a4dc";s:24:"res/flags/CVS/Repository";s:4:"0165";s:18:"res/flags/CVS/Root";s:4:"a7f0";s:15:"res/CVS/Entries";s:4:"a006";s:18:"res/CVS/Repository";s:4:"ab02";s:12:"res/CVS/Root";s:4:"a7f0";s:11:"CVS/Entries";s:4:"5ced";s:14:"CVS/Repository";s:4:"375e";s:8:"CVS/Root";s:4:"a7f0";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.0.0-',
			'typo3' => '3.8.0-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);

?>