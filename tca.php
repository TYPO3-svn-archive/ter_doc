<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}


$TCA['tx_terdoc_manuals'] = array(
	'ctrl' => $TCA['tx_terdoc_manuals']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'title,extensionkey,version,language,modificationdate,authorname,authoremail,abstract,t3xfilemd5',
	),
	'columns' => array (
		'title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manuals.title',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '250',
				'eval' => 'trim,required',
			),
		),
		'extensionkey' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manuals.extensionkey',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '250',
				'eval' => 'trim,required',
			),
		),
		'version' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manuals.version',
			'config' => array (
				'type' => 'input',
				'size' => '15',
				'max'  => '250',
				'eval' => 'trim,required',
			),
		),
		'language' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manuals.language',
			'config' => array (
				'type' => 'input',
				'size' => '5',
				'max'  => '5',
				'eval' => 'trim',
			),
		),
		'modificationdate' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manuals.modificationdate',
			'config' => array (
				'type' => 'input',
				'size' => '15',
				'max'  => '11',
				'eval' => 'datetime',
			),
		),
		'authorname' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manuals.authorname',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '250',
				'eval' => 'trim',
			),
		),
		'authoremail' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manuals.authoremail',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '250',
				'eval' => 'trim',
			),
		),
		'abstract' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manuals.abstract',
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			),
		),
		't3xfilemd5' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manuals.t3xfilemd5',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '250',
				'eval' => 'trim',
			),
		),
	),
	'types' => array (
		'0' => array('showitem' => 'title,extensionkey,version,language,modificationdate,authorname,authoremail,abstract,t3xfilemd5'),
	),
);


$TCA['tx_terdoc_renderproblems'] = array(
	'ctrl' => $TCA['tx_terdoc_renderproblems']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'extensionkey,version,tstamp,errorcode',
	),
	'columns' => array (
		'extensionkey' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_renderproblems.extensionkey',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '250',
				'eval' => 'trim,required',
			),
		),
		'version' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_renderproblems.version',
			'config' => array (
				'type' => 'input',
				'size' => '15',
				'max'  => '250',
				'eval' => 'trim,required',
			),
		),
		'tstamp' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_renderproblems.tstamp',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max'  => '11',
				'eval' => 'datetime',
			),
		),
		'errorcode' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_renderproblems.errorcode',
			'config' => array (
				'type' => 'input',
				'size' => '5',
				'eval' => 'int',
			),
		),
	),
	'types' => array (
		'0' => array('showitem' => 'extensionkey,version,tstamp,errorcode'),
	),
);


$TCA['tx_terdoc_categories'] = array (
	'ctrl' => $TCA['tx_terdoc_categories']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'title,description,isdefault,viewpid',
	),
	'columns' => array (
		'title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_categories.title',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			),
		),
		'description' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_categories.description',
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			),
		),
		'isdefault' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_categories.isdefault',
			'config' => array (
				'type' => 'check',
			),
		),
		'viewpid' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_categories.viewpid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '0',
				'show_thumbs' => '1',
			),
		),
	),
	'types' => array (
		'0' => array('showitem' => 'title,description,isdefault,viewpid'),
	),
);


$TCA['tx_terdoc_manualscategories'] = array(
	'ctrl' => $TCA['tx_terdoc_manualscategories']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'extensionkey,categoryuid',
	),
	'columns' => array (
		'extensionkey' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manualscategories.extensionkey',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '250',
				'eval' => 'trim,required',
			),
		),
		'categoryuid' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manualscategories.categoryuid',
			'config' => array (
				'type' => 'input',
				'size' => '5',
				'eval' => 'int',
			),
		),
	),
	'types' => array (
		'0' => array('showitem' => 'extensionkey,categoryuid'),
	),
);


$TCA['tx_terdoc_manualspagecache'] = array(
	'ctrl' => $TCA['tx_terdoc_manualspagecache']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'extensionkey,version',
	),
	'columns' => array (
		'extensionkey' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manualspagecache.extensionkey',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '250',
				'eval' => 'trim,required',
			),
		),
		'version' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ter_doc/locallang_db.xml:tx_terdoc_manualspagecache.version',
			'config' => array (
				'type' => 'input',
				'size' => '15',
				'max'  => '250',
				'eval' => 'trim',
			),
		),
	),
	'types' => array (
		'0' => array('showitem' => 'extensionkey,version'),
	),
);

?>