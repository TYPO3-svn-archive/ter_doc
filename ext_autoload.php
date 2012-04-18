<?php

$extensionPath = t3lib_extMgm::extPath('ter_doc');
return array(
	'tx_terdoc_api' => $extensionPath . 'class.tx_terdoc_api.php',
	'tx_terdoc_documentformat' => $extensionPath . 'class.tx_terdoc_documentformat.php',
	'tx_terdoc_documentformat_index' => $extensionPath . 'class.tx_terdoc_documentformat.php',
	'tx_terdoc_documentformat_display' => $extensionPath . 'class.tx_terdoc_documentformat.php',
	'tx_terdoc_documentformat_download' => $extensionPath . 'class.tx_terdoc_documentformat.php',
	'tx_terdoc_renderdocuments' => $extensionPath . 'class.tx_terdoc_renderdocuments.php',
	'tx_terdoc_cli_renderer' => $extensionPath . 'Classes/Cli/Renderer.php',
	'tx_terdoc_controller_clicontroller' => $extensionPath . 'Classes/Controller/CliController.php',
	'tx_terdoc_domain_model_queueitem' => $extensionPath . 'Classes/Domain/Model/QueueItem.php',
	'tx_terdoc_domain_repository_extensionrepository' => $extensionPath . 'Classes/Domain/Repository/ExtensionRepository.php',
	'tx_terdoc_domain_repository_queueitemrepository' => $extensionPath . 'Classes/Domain/Repository/QueueItemRepository.php',
	'tx_terdoc_tasks_rendermanualstask' => $extensionPath . 'Classes/Tasks/RenderManualsTask.php',
	'tx_terdoc_utility_cli' => $extensionPath . 'Classes/Utility/Cli.php',
	'tx_terdoc_validator_environment' => $extensionPath . 'Classes/Validator/Environment.php',
	'tx_terdoc_module1' => $extensionPath . 'mod1/index.php',
	'tx_terdoc_pi1' => $extensionPath . 'pi1/class.tx_terdoc_pi1.php',
);
?>