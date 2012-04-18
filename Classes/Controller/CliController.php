<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Susanne Moog <s.moog@neusta.de>
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
 * ************************************************************* */

/**
 * The CLI controller for the ter_doc package
 *
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_TerDoc_Controller_CliController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * @var Tx_TerDoc_Domain_Repository_ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @var Tx_TerDoc_Domain_Repository_QueueItemRepository
	 */
	protected $queueRepository;

	/**
	 * @var Tx_Extbase_Persistence_Manager
	 */
	protected $persistenceManager;

	/**
	 * @var array
	 */
	protected $settings;
	/**
	 * @var string Path to the unzip command
	 */
	protected $unzipCommand;
	/**
	 * @var bool Verbose output flag
	 */
	protected $verbose = FALSE;
	/**
	 * @var string
	 */
	protected $logFullPath;
	/**
	 * @var t3lib_svbase Instance of the language guessing service
	 */
	protected $languageGuesserServiceObj;
	/**
	 * @var Tx_TerDoc_Validator_Environment Instance of the validation class
	 */
	protected $validator;

	/**
	 * @param Tx_TerDoc_Domain_Repository_QueueItemRepository $repository
	 */
	public function injectQueueRepository(Tx_TerDoc_Domain_Repository_QueueItemRepository $repository) {
		$this->queueRepository = $repository;
	}

	/**
	 * @param Tx_Extbase_Persistence_Manager $persistenceManager
	 */
	public function injectPersistenceManager(Tx_Extbase_Persistence_Manager $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Initializes the current action
	 *
	 * @return void
	 */
	public function initializeAction() {

			// Define controller property here
		$this->settings = Tx_TerDoc_Utility_Cli::getSettings();
		$this->settings['repositoryDir'] = Tx_TerDoc_Utility_Cli::sanitizeDirectoryPath($this->settings['repositoryDir']);
		$this->unzipCommand = $this->settings['unzipCommand'];
		$this->verbose = $this->settings['cliVerbose'] ? TRUE : FALSE;
		$this->logFullPath = strlen($this->settings['logFullPath']) ? $this->settings['logFullPath'] : FALSE;

			// Extends settings
		$this->settings['homeDir'] = Tx_TerDoc_Utility_Cli::sanitizeDirectoryPath(PATH_site . $this->settings['homeDir']);
		$this->settings['documentsCache'] = $this->settings['homeDir'] . 'documentscache/';
		$this->settings['lockFile'] = $this->settings['homeDir'] . 'tx_terdoc_render.lock';
		$this->settings['md5File'] = $this->settings['homeDir'] . 'tx_terdoc_extensionsmd5.txt';
		$this->settings['extensionFile'] = $this->settings['repositoryDir'] . 'extensions.xml.gz';

			// Initialize objects
		$this->languageGuesserServiceObj = t3lib_div::makeInstanceService('textLang'); // Initialize language guessing service:
		$this->extensionRepository = t3lib_div::makeInstance('Tx_TerDoc_Domain_Repository_ExtensionRepository', $this->settings, $this->arguments); // Initialize repository
		$this->validator = t3lib_div::makeInstance('Tx_TerDoc_Validator_Environment', $this->settings, $this->arguments); // Initialize repository
	}

	/**
	 * Update the latest datasource of extensions from typo3.org. Basically, this is a XML file.
	 *
	 * @param  array $arguments list of possible arguments
	 * @return void
	 */
	public function updateAction($arguments) {
			// Options  coming from the CLI
		$this->arguments = $arguments;

		$this->initializeAction();
		$this->validator->validateFileStructure();

			// fetch content
		$content = file_get_contents('http://typo3.org/fileadmin/ter/extensions.xml.gz');

			// write content
		$datasource = $this->settings['repositoryDir'] . 'extensions.xml.gz';
		$result = file_put_contents($datasource, $content);

		if (!$result) {
			throw new Exception('Exception thrown #1300100506: not possible to write datasource at "' . $datasource . '" or to fetch the datasource from "' . $content . '"', 1300100506);
		}

		Tx_TerDoc_Utility_Cli::log('Data Source has been updated with success');
	}

	/**
	 * Download all extension from typo3.org (t3x files)
	 *
	 * @param  array $arguments list of possible arguments
	 * @return void
	 */
	public function downloadAction($arguments) {

			// Options  coming from the CLI
		$this->arguments = $arguments;

		$this->initializeAction();

			// Makes sure the environment is good and throw an error if that is not the case
		$this->validator->validateFileStructure();
		$this->validator->validateDataSource();

		if (!$this->isLocked() || $this->arguments['force']) {
				// create a lock
			touch($this->settings['lockFile']);

			Tx_TerDoc_Utility_Cli::log(strftime('%d.%m.%y %R') . ' ter_doc downloading started...');

			$extensions = $this->extensionRepository->findAll();
			foreach ($extensions as $extension) {
				foreach ($extension as $version) {
					if (strlen($version['version'])) {
						$this->extensionRepository->downloadExtension($extension['extensionkey'], $version['version']);
					}
				}
			}
		}
		unlink($this->settings['lockFile']);
	}

	/**
	 * Index action for this controller. Displays a list of addresses.
	 *
	 * @param  array $arguments list of possible arguments
	 * @return void
	 */
	public function generateIndexAction($arguments) {

			// Options  coming from the CLI
		$this->arguments = $arguments;


			// variable that should probably goes into the settings
		#Tx_TerDoc_Utility_Cli::log($this->settings);
		$urlPrefix = 'documentation/document-library/extension-manuals/';

			// Options  coming from the CLI
		$this->arguments = $arguments;

		$this->initializeAction();

			// array that contains all the indexes
		$indexes = array();

		$extensions = $this->extensionRepository->findAll();
		$loop = 0;
		foreach ($extensions as $extension) {
			$_index = array();

			$extensionKey = (string) $extension['extensionkey'];

				// Not very efficient at this stage but works to retrieve the last version of the extension
			foreach ($extension as $version) {
				$lastVersion = $version;
			}
			$version = (string) $lastVersion['version'];
			$pathToExtension = $documentDir = Tx_TerDoc_Utility_Cli::getDocumentDirOfExtensionVersion($this->settings['documentsCache'], $extensionKey, $version);
			$toc = $pathToExtension . 'toc.dat';

			if (is_file($toc)) {

				$tocData = unserialize(file_get_contents($toc));

				if (!empty($tocData)) {

						// Initialize some variables
					$sections = array();
					$sectionName = '';
					$_loop = 1;
					foreach ($tocData[1]['sections'] as $section) {

						if ($_loop > 1) {
							if ($_loop < 10) {
								$sectionName = 's' . sprintf("0%d", $_loop);
							} else {
								$sectionName = 's' . sprintf("%d", $_loop);
							}
						}
						$file = $pathToExtension . 'html_online/ch01' . $sectionName . '.html';

						// Makes sure the file exists
						if (is_file($file)) {
							$_section = array();
							$_section['file'] = $file;
							$_section['title'] = $section['title'];
							$_section['url'] = $urlPrefix . $extensionKey . '/' . $version . '/view/1/' . $_loop;

							$sections[] = $_section;
						} else {
							Tx_TerDoc_Utility_Cli::log('* Serious warning for ' . $extensionKey . ' version ' . $version . ': file does not exists ' . $file);
						}
						$_loop++;
					}

					if (count($tocData) > 1) {
						Tx_TerDoc_Utility_Cli::log('* Serious warning for ' . $extensionKey . ' version ' . $version . ': more that one entry has been detected in the TOC');
					}


					// build up the array
					$_index['extensionkey'] = $extensionKey;
					$_index['version'] = $version;
					$_index['path'] = $pathToExtension;
					$_index['documentation'] = $sections;

					$indexes[] = $_index;


						// prevent the script from looping too many times in a development context
						// Otherwise will process more than 4000 extensions
					$loop++;
					if ($loop == $this->arguments['limit']) {
						break;
					}
				}
			}
		}

			// Write the serialize table
		$pathToStorage = $this->settings['homeDir'] . 'extension_index.serialize';
		try {

			// debug the array. Beware, it can be a very large array
			#Tx_TerDoc_Utility_Cli::log($indexes);

			file_put_contents($pathToStorage, serialize($indexes));
		} catch (Exception $e) {
			Tx_TerDoc_Utility_Cli::log($e->getMessage());
		}

		Tx_TerDoc_Utility_Cli::log('Action ended sucessfully!');
		Tx_TerDoc_Utility_Cli::log('New index file has been written at ' . $pathToStorage);
	}

	/**
	 * Index action for this controller. Displays a list of addresses.
	 *
	 * @param  array $arguments list of possible arguments
	 * @return void
	 */
	public function renderAction($arguments) {

			// Options  coming from the CLI
		$this->arguments = $arguments;

		$this->initializeAction();

			// Makes sure the environment is good and throw an error if that is not the case
		$this->validator->validateFileStructure();
		$this->validator->validateDataSource();

		if ($this->isLocked() && !$this->arguments['force']) {
			Tx_TerDoc_Utility_Cli::log('... aborting - another process seems to render documents right now! Try running with option "--force" enabled');
			return;
		}
				// create a lock
		touch($this->settings['lockFile']);

		Tx_TerDoc_Utility_Cli::log(strftime('%d.%m.%y %R') . ' ter_doc renderer starting ...');

		if ($this->extensionRepository->wasModified() || $this->arguments['force']) {
			Tx_TerDoc_Utility_Cli::log('* extensions.xml was modified since last run');

				// Reads the extension index file (extensions.xml.gz) and updates the the manual caching table accordingly.
			$hasUpdate = $this->extensionRepository->updateAll();
			if ($hasUpdate) {

				$this->extensionRepository->deleteOutdatedDocuments();
				$modifiedExtensionVersionsArr = $this->extensionRepository->getModifiedExtensionVersions();

				$counter = 0;
				foreach ($modifiedExtensionVersionsArr as $extensionAndVersionArr) {

					$extensionKey = $extensionAndVersionArr['extensionkey'];
					$version = $extensionAndVersionArr['version'];
						// Check if manual should be rendered or not depending on arguments
					if (!empty($this->arguments['extension'])) {
							// Skip rendering if extension is defined and doesn't match. Same for version number.
						if ($extensionKey == $this->arguments['extension']) {
							if (!empty($this->arguments['version']) && $version != $this->arguments['version']) {
								continue;
							}
						} else {
							continue;
						}
					}

					$this->renderExtensionDocumentation($extensionKey, $version, $extensionAndVersionArr['t3xfilemd5']);

					$counter++;
				}
				$this->extensionRepository->cleanUpAll();
					// If no extensions were rendered, give some feedback, as this might be unexpected
					// (e.g. the user has entered erroneous version/extension arguments)
				if ($counter == 0) {
					Tx_TerDoc_Utility_Cli::log('Note: no extension manuals were rendered with the given arguments.');
				}
			}
			Tx_TerDoc_Utility_Cli::log(strftime('%d.%m.%y %R') . ' done.');
		} else {
			Tx_TerDoc_Utility_Cli::log('Extensions.xml was not modified since last run, so nothing to do - done.');
		}

		unlink($this->settings['lockFile']);
	}

	protected function renderExtensionDocumentation($extensionKey, $version, $manualMd5) {
		$transformationErrorCodes = array();
	 	// Computes the cache directory of the extension
		$documentDir = Tx_TerDoc_Utility_Cli::getDocumentDirOfExtensionVersion($this->settings['documentsCache'], $extensionKey, $version);

		// Prepare environment by deleting obsolete rendering problem log
		Tx_TerDoc_Utility_Cli::log('* Rendering documents for extension "' . $extensionKey . '" (' . $version . ')');
		$this->extensionRepository->prepare($extensionKey, $version);

		// Extracting manual from t3x
		Tx_TerDoc_Utility_Cli::log('   * Extracting "doc/manual.sxw" from extension ' . $extensionKey . ' (' . $version . ')');
		$this->extensionRepository->downloadExtension($extensionKey, $version);
		$this->extensionRepository->extractT3x($extensionKey, $version, 'doc/manual.sxw', $errorCodes);
		$this->extensionRepository->decompressManual($extensionKey, $version);

		// Initialize service
		/* @var $xslObject Tx_TerXsl_Transformer_DocBook */
		$xslObject = t3lib_div::makeInstanceService('xsl', 'xslt');
		$xslObject->setSettings($this->settings);

		// Rendering manual.sxw to Docbook
		$isDocBookTransformationOk = FALSE;
		$docBookVersions = explode(',', $this->settings['docbook_version']);
		if (file_exists($documentDir . 'sxw/content.xml')) {
			foreach ($docBookVersions as $docBookVersion) {
				// @todo: improve the transformation: some staff are duplicated e.g. genartion of TOC
				$isDocBookTransformationOk = $xslObject->transformManualToDocBook($documentDir, $docBookVersion, $transformationErrorCodes);
			}
		}

		if ($isDocBookTransformationOk) {
			// Store some info from the Docbook transformation into the database
			$dataSet = $xslObject->getInformation();
			$this->extensionRepository->update($extensionKey, $version, $dataSet);

			Tx_TerDoc_Utility_Cli::log('   * Generating HTML for extension ' . $extensionKey . ' (' . $version . ')');
			// Transform Docbook to HTML
			$xslObject->transformDocBookToHtml($documentDir);
		} else {
			Tx_TerDoc_Utility_Cli::log('	* No manual found or problem while extracting manual');
			$this->extensionRepository->delete($extensionKey, $version);
		}

		// Clean up environment by removing temporary files
		$this->extensionRepository->cleanUp($extensionKey, $version);
		t3lib_div::writeFile($documentDir . 't3xfilemd5.txt', $manualMd5);

		foreach ($transformationErrorCodes as $errorCode) {
			$this->extensionRepository->log($extensionKey, $version, $errorCode);
		}

		if (!empty($transformationErrorCodes)) {
			Tx_TerDoc_Utility_Cli::log('   * Error code(s): ' . implode(',', $transformationErrorCodes));
			return $transformationErrorCodes;
		}
		return $transformationErrorCodes;
	}

	/**
	 * Walk through all entries of the extensions.xml.gz and
	 * add them to the queue (if needed)
	 *
	 * This is quite memory intensive - adding all manuals at once,
	 * might easily exceed 1GB memory. But actually adding all extension-versions at once
	 * will happen only once afterwards only the missing items are added,
	 * therefore it's considered to be ok that adding all manuals might break in the middle once or twice.
	 *
	 * @param $arguments
	 */
	public function buildQueueAction($arguments) {

			// Options  coming from the CLI
		$this->arguments = $arguments;
		$this->initializeAction();

		$extensions = $this->extensionRepository->findAll();
		Tx_TerDoc_Utility_Cli::log(sprintf('Having %d extensions to check ', count($extensions)));

		$persistCount = 100;

		foreach($extensions as $extension) {
			foreach($extension as $extensionVersion) {

				$key = (string) $extension['extensionkey'];
				$version = (string) $extensionVersion['version'];
				$hash = (string) $extensionVersion->t3xfilemd5;

				if (!$key || !$version) {
					continue;
				}

					// Check if that extension version already exists
				if ($this->queueRepository->isUnchangedExtensionVersion($key,$version,$hash)) {
					continue;
				} else {
					/**
					 * @var $queueItem Tx_TerDoc_Domain_Model_QueueItem
					 */
					$newItem = FALSE;
					$queueItem = $this->queueRepository->findOneByExtensionKeyAndVersion($key, $version);
				}

				Tx_TerDoc_Utility_Cli::log(sprintf('Add %s : %s (%s) to queue ', $key, $version, $hash));

				if (!$queueItem) {
					$newItem = TRUE;
					$queueItem = $this->objectManager->create('Tx_TerDoc_Domain_Model_QueueItem');
				}




				$queueItem->setExtensionkey($key)
					->setVersion($version)
					->setFilehash($hash)
					->setPriority($this->getPrio($key, $version))
					->setFinished(new DateTime('@0'));

				if ($newItem) {
					$this->queueRepository->add($queueItem);
				} else {
					$this->queueRepository->update($queueItem);
				}
					// Add manual also to tx_terdoc_manual -> abstract and language will be added during doc rendering
				$this->extensionRepository->delete($key, $version);
				$this->extensionRepository->insertExtensionFromFilesystem($extension, $extensionVersion, FALSE);

				$persistCount--;
				if (!$persistCount) {
					$this->persistenceManager->persistAll();
					Tx_TerDoc_Utility_Cli::log('Persisted changes' . gc_collect_cycles());
					$persistCount = 100;
				}
			}
		}

		$this->persistenceManager->persistAll();

	}

	/**
	 * Core documentation get a high prio all others
	 * get a prio which relates to their "age"
	 *
	 * @param $key
	 * @param $version
	 * @return int
	 */
	protected function getPrio($key, $version) {
		if (stristr($key, 'doc_core_') !== FALSE) {
			$prio = 1000;
		} else  {
			$parts = explode('.', $version);
			$prio = $parts[0]*100 + $parts[1]*10 + $parts[2];
		}
		return $prio;
	}

	/**
	 * Walk through our queue and render all items
	 * which need rendering
	 *
	 * @param $arguments
	 * @return mixed
	 */
	public function renderFromQueueAction($arguments) {
			// Options  coming from the CLI
		$this->arguments = $arguments;

		$this->initializeAction();

		if ($this->isLocked() && !$this->arguments['force']) {
			Tx_TerDoc_Utility_Cli::log('... aborting - another process seems to render documents right now! Try running with option "--force" enabled');
			return;
		}
				// create a lock
		touch($this->settings['lockFile']);

		$limit = intval($this->arguments['limit']) ?: 0;
		$queueItems = $this->queueRepository->findUnfinished($limit);
		$persistCount = 10;

		/**
		 * @var $queueItem Tx_TerDoc_Domain_Model_QueueItem
		 */
		foreach($queueItems as $queueItem) {
			if ($this->extensionRepository->getFilehash($queueItem->getExtensionkey(), $queueItem->getVersion()) != $queueItem->getFilehash()) {
				Tx_TerDoc_Utility_Cli::log(sprintf('Render %s : %s from queue ', $queueItem->getExtensionkey(), $queueItem->getVersion()));
				try {
					$this->renderExtensionDocumentation(
						$queueItem->getExtensionkey(),
						$queueItem->getVersion(),
						$queueItem->getFilehash()
					);
				} catch (Exception $e) {
					Tx_TerDoc_Utility_Cli::log($e->getMessage());
				}
			}
			$queueItem->setFinished(new DateTime());
			$this->queueRepository->update($queueItem);
			Tx_TerDoc_Utility_Cli::log(sprintf('Done rendering %s : %s ', $queueItem->getExtensionkey(), $queueItem->getVersion()));

			$persistCount--;
			if (!$persistCount) {
				$this->persistenceManager->persistAll();
				$persistCount = 10;
			}
		}

		$this->persistenceManager->persistAll();

		unlink($this->settings['lockFile']);
	}



	/**
	 * Index action for this controller. Displays a list of addresses.
	 *
	 * @return void
	 */
	protected function isLocked() {
		$result = FALSE;
			// Check if another process currently renders the documents:
		if (file_exists($this->settings['lockFile'])) {
			Tx_TerDoc_Utility_Cli::log('Found .lock file ...');

				// If the lock is not older than X minutes, skip index creation:
			if (filemtime($this->settings['lockFile']) > (time() - (6 * 60 * 60))) {
				if (!$this->debug) {
					$result = TRUE;
				}
			} else {
				Tx_TerDoc_Utility_Cli::log('... lock file was older than 6 hours, so start rendering anyway');
			}
		}
		return $result;
	}

	/**
	 * Display some help on the console
	 *
	 * @return void
	 */
	public function helpAction() {

		$message = <<< EOF
handles TER documentation

usage:
    /var/www/typo3/cli_dispatch.phpsh ter_doc <options> <commands>

options:
    -h, --help           - print this message
    -f, --force          - force the command to be executed
    -l=10, --limit=10    - limit the number of extensions/versions processed (applies only to generateIndex command)
    --extension=foo      - act only on the given extension (applies only to render command)
    --version=x.y.z      - act only a the given version (used only if --extension is defined too)

commands:
    render                - render documentation cache
    generateIndex         - generate an index of the documentation
    update                - update the latest datasource of extensions from typo3.org. Basically, this will fetch an XML file.
    download              - download all extension from typo3.org (t3x files)
EOF;

		Tx_TerDoc_Utility_Cli::log($message);
	}

}

?>