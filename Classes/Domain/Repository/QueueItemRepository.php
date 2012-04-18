<?php

class Tx_TerDoc_Domain_Repository_QueueItemRepository extends Tx_Extbase_Persistence_Repository {

	/**
	 * @param string $key
	 * @param string $version
	 * @return Tx_Extbase_Persistence_QueryResultInterface
	 */
	public function findOneByExtensionKeyAndVersion($key, $version) {
		$query = $this->createQuery();
		$constraints = array(
			$query->equals('extensionkey', $key),
			$query->equals('version', $version)
		);
		$query->matching($query->logicalAnd($constraints));
		return $query->execute()->getFirst();
	}

	/**
	 * @return Tx_Extbase_Persistence_QueryResultInterface
	 */
	public function findUnfinished($limit = 0) {
		$query = $this->createQuery();
		$query->matching($query->equals('finished', 0));

		if ($limit) {
			$query->limit($limit);
		}

		$query->setOrderings(array('priority' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING));

		return $query->execute();
	}

	/**
	 * Used to avoid that we instantiate object which aren't really
	 * used in our process.
	 *
	 * @param $key
	 * @param $version
	 * @param $hash
	 * @return bool
	 */
	public function isUnchangedExtensionVersion($key, $version, $hash) {
		$where = sprintf('extensionkey=%s AND version=%s AND filehash=%s',
			$GLOBALS['TYPO3_DB']->fullQuoteStr($key),
			$GLOBALS['TYPO3_DB']->fullQuoteStr($version),
			$GLOBALS['TYPO3_DB']->fullQuoteStr($hash)
		);
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*',  'tx_terdoc_renderqueue', $where );
		return $count == 1;
	}

}
