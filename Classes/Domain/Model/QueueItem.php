<?php

class Tx_TerDoc_Domain_Model_QueueItem extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 * @var string
	 */
	protected $extensionkey;

	/**
	 * @var string;
	 */
	protected $version;

	/**
	 * @var DateTime
	 */
	protected $finished;

	/**
	 * @var int
	 */
	protected $priority=0;

	/**
	 * @var string
	 */
	protected $filehash = 'default';

	/**
	 * @param string $extensionkey
	 * @return Tx_Terdoc_Domain_Model_QueueItem
	 */
	public function setExtensionkey($extensionkey) {
		$this->extensionkey = (string) $extensionkey;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getExtensionkey() {
		return $this->extensionkey;
	}

	/**
	 * @param \DateTime $finished
	 * @return Tx_Terdoc_Domain_Model_QueueItem
	 */
	public function setFinished($finished) {
		$this->finished = $finished;
		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getFinished() {
		return $this->finished;
	}

	/**
	 * @param string $version
	 * @return Tx_Terdoc_Domain_Model_QueueItem
	 */
	public function setVersion($version) {
		$this->version = (string) $version;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @param string $filehash
	 * @return Tx_Terdoc_Domain_Model_QueueItem
	 */
	public function setFilehash($filehash) {
		$this->filehash = (string) $filehash;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFilehash() {
		return $this->filehash;
	}

	/**
	 * @param int $priority
	 * @return Tx_Terdoc_Domain_Model_QueueItem
	 */
	public function setPriority($priority) {
		$this->priority = intval($priority);
		return $this;
	}

	/**
	 * @return int
	 */
	public function getPriority() {
		return $this->priority;
	}
}
