<?php
$extensionClassesPath = t3lib_extMgm::extPath('ter_doc', 'Classes/');
return array(
	'tx_terdoc_utility_cli' => $extensionClassesPath . 'Utility/Cli.php',
	'tx_terdoc_controller_clicontroller' => $extensionClassesPath . 'Controller/CliController.php',
	'tx_terdoc_domain_repository_extensionrepository' => $extensionClassesPath . 'Domain/Repository/ExtensionRepository.php',
	'tx_terdoc_validator_environment' => $extensionClassesPath . 'Validator/Environment.php',
	'tx_terdoc_tasks_rendermanualstask' => $extensionClassesPath . 'Tasks/RenderManualsTask.php',
);
?>