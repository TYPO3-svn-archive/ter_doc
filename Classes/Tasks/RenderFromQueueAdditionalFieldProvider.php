<?php

/**
 * Additional field provider
 */
class Tx_TerDoc_Tasks_RenderFromQueueAdditionalFieldProviderimplements implements  tx_scheduler_AdditionalFieldProvider {

	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {

			// Initialize extra field value
		if (empty($taskInfo['mode'])) {
			$taskInfo['mode'] = $parentObject->CMD == 'edit' ? $task->mode : 'renderQueue';
		}

		if (empty($taskInfo['limit'])) {
			$taskInfo['limit'] = $parentObject->CMD == 'edit' ? $task->limit : 25;
		}

		$additionalFields = array();
			// Write the code for the field
		$fieldID = 'task_mode';
		$fieldCode = '<input type="text" name="tx_scheduler[mode]" id="' . $fieldID . '" value="' . $taskInfo['mode'] . '" size="10" />';

		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'Mode (renderQueue|buildQueue)',
			'cshKey'   => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldID
		);

			// Write the code for the field
		$fieldID = 'task_limit';
		$fieldCode = '<input type="text" name="tx_scheduler[limit]" id="' . $fieldID . '" value="' . $taskInfo['limit'] . '" size="10" />';
		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'Limit (docs rendered in one run)',
			'cshKey'   => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldID
		);

		return $additionalFields;
	}

	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $parentObject) {
		return preg_match('/^(render|build)Queue$/', $submittedData['mode']);
	}

	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$task->mode = $submittedData['mode'];
		$task->limit = intval($submittedData['limit']);
	}
}
?>