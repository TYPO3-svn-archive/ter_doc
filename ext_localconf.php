<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_terdoc_pi1 = < plugin.tx_terdoc_pi1.CSS_editor
',43);

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_terdoc_pi1.php','_pi1','list_type',1);

if (TYPO3_MODE=='BE')    {
    // Setting up scripts that can be run from the cli_dispatch.phpsh script.
	$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys']['ter_doc'] = array('EXT:ter_doc/Classes/Cli/Renderer.php', '_CLI_ter_doc');
}

?>