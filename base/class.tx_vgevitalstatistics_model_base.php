<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Francois Suter (Cobweb) <support@cobweb.ch>
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
***************************************************************/

require_once(t3lib_extMgm::extPath('vge_processes').'class.tx_vgeprocesses_model_base.php');
require_once(t3lib_extMgm::extPath('advancedform').'lib/class.tx_advancedform_tcatoforms.php');
require_once(t3lib_extMgm::extPath('advancedform').'lib/class.tx_advancedform_formelements.php');
require_once(t3lib_extMgm::extPath('vge_vitalstatistics').'base/class.tx_vgevitalstatistics_common_base.php');

/**
 * Model service for base processes related to vital statistics
 *
 * @author	Francois Suter (Cobweb) <support@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_vgevitalstatistics
 */
class tx_vgevitalstatistics_model_base extends tx_vgeprocesses_model_base {

	public function __construct() {
		$this->processList = tx_vgevitalstatistics_common_base::$processList;
		$this->locallangFile = tx_vgevitalstatistics_common_base::$locallangFile;
		parent::__construct();
	}

	/**
	 * This method is expected to return a list of processes it is capable of handling,
	 * along with the parameters to use when calling up each process
	 * TODO: finalize array structure
	 *
	 * @return	array	list of available processes with parameters
	 */
	public function listProcesses() {
		$processes = array();
		foreach ($this->processList as $aProcess) {
			$processes[] = array(
								'name' => $this->getProcessName($aProcess),
								'process' => $aProcess,
								'linkParameters' => array(
														'process' => $aProcess,
													),
							);
		}
		return $processes;
	}

	/**
	 * This process returns all the data related to a given process at a given step for vital statistics
	 *
	 * @param	array	$parameters: list of parameters, including process name, step, etc.
	 *
	 * @return	array	all data related to the process and step
	 */
	public function getDataForStep($parameters) {
			// Make sure a step number is defined
		if (empty($parameters['step'])) $parameters['step'] = 1;

			// Read the extension configuration to get step information
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vge_vitalstatistics']['processes'][$parameters['process']])) {
			$configuration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vge_vitalstatistics']['processes'][$parameters['process']];
		}
		else {
			$configuration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vge_vitalstatistics']['processes']['_DEFAULT'];
		}
		$stepConfiguration = $configuration['steps'][$parameters['step']];
//t3lib_div::debug($stepConfiguration);
//t3lib_div::debug($parameters);

			// Get data model for current step depending on type
		$data = array();
		switch ($stepConfiguration['type']) {
			case 'form':
				$data['current'] = $this->processFormStep($stepConfiguration, $parameters);
				$data['current']['type'] = 'form';
				break;
			case 'validation':
				$data['current'] = $this->processValidationStep($stepConfiguration, $parameters);
				$data['current']['type'] = 'validation';
				break;
			default:
					// The step has no configuration (issue error?)
				$data['current'] = array('type' => '');
				break;
		}
		$data['current']['name'] = $this->getProcessName($parameters['process']);

		// TODO: change definition of $data, because previous or next could already be defined inside calls above
			// Get data model for previous step
		$data['previous'] = $this->getPreviousStepLinkInfo($parameters);
			// Get data model for next step
		if ($stepConfiguration['displayNextButton']) {
			$data['next'] = $this->getNextStepLinkInfo($configuration, $parameters);
		}
//t3lib_div::debug($data);
		return $data;
	}

	/**
	 * This method assembles the data model for a step of type form
	 * Essentially this means converting info from a FlexForm sheet to a FORM cObj-compatible syntax
	 *
	 * @param	array	$configuration: configuration options for the step
	 * @param	array	$parameters: current process parameters
	 *
	 * @return	array	data model
	 */
	protected function processFormStep($configuration, $parameters) {
		$data = array();

		$flexformSheet = $configuration['sheet'];
			// Load the full TCA for the vital statistics tables
		t3lib_div::loadTCA('tx_vgevitalstatistics_processes');
			// Load the formdata flexform
		$dataStructureArray = t3lib_BEfunc::getFlexFormDS($GLOBALS['TCA']['tx_vgevitalstatistics_processes']['columns']['formdata']['config'], array(), 'tx_vgevitalstatistics_processes');
			// Get selected flexform sheet
		if (isset($dataStructureArray['sheets'][$flexformSheet]['ROOT'])) {
			$sheet = $dataStructureArray['sheets'][$flexformSheet]['ROOT'];
			$data['stepTitle'] = $GLOBALS['LANG']->sL($sheet['TCEforms']['sheetTitle']);

				// Get object to transform TCA data to FORM definition
			$tcaConverter = t3lib_div::makeInstance('tx_advancedform_tcatoforms');
				// Define wrapper for field names
			$tcaConverter->setPrefix('tx_vgeprocesses_controller');
			$formInfo = '';
				// Convert TCEforms info to FORM cObj info
			foreach ($sheet['el'] as $field => $fieldData) {
				$formInfo .= $tcaConverter->columnToForm($field, $fieldData['TCEforms'], $parameters[$field]);
			}

				// Get helper object for creating FORM fields
			$formHelper = t3lib_div::makeInstance('tx_advancedform_formelements');
				// Define wrapper for field names
			$formHelper->setPrefix('tx_vgeprocesses_controller');
				// Add hidden fields with information about process type and subtype
			$formInfo .= $formHelper->addHidden('viewType', 'run');
			$formInfo .= $formHelper->addHidden('subtype', $parameters['subtype']);
			$formInfo .= $formHelper->addHidden('process', $parameters['process']);

				// Add hidden field with information about step
			$formInfo .= $formHelper->addHidden('step', $parameters['step'] + 1);
			
				// Add submit button
			$formInfo .= ' |form_submit = submit| Valider';

				// Store the form information
			$data['form'] = $formInfo;
		}
		return $data;
	}

	/**
	 * This method assembles the data model for a step of type form input validation
	 * Essentially this means converting info from a FlexForm sheet to a FORM cObj-compatible syntax
	 *
	 * @param	array	$configuration: configuration options for the step
	 * @param	array	$parameters: current process parameters
	 *
	 * @return	array	data model
	 */
	protected function processValidationStep($configuration, $parameters) {
		$data = array();

		$flexformSheet = $configuration['sheet'];
			// Load the full TCA for the vital statistics tables
		t3lib_div::loadTCA('tx_vgevitalstatistics_processes');
			// Load the formdata flexform
		$dataStructureArray = t3lib_BEfunc::getFlexFormDS($GLOBALS['TCA']['tx_vgevitalstatistics_processes']['columns']['formdata']['config'], array(), 'tx_vgevitalstatistics_processes');
			// Get selected flexform sheet
		if (isset($dataStructureArray['sheets'][$flexformSheet]['ROOT'])) {
			$sheet = $dataStructureArray['sheets'][$flexformSheet]['ROOT'];
			$data['stepTitle'] = $GLOBALS['LANG']->getLL('confirmation');

				// Loop on sheet elements to assemble key-values pairs of field names and input values
			$data['input'] = array();
			foreach ($sheet['el'] as $field => $fieldData) {
				if (isset($parameters[$field])) {
					$fieldName = $GLOBALS['LANG']->sL($fieldData['TCEforms']['label']);
					$data['input'][$fieldName] = $parameters[$field];
				}
				else {
					$data['input'][$fieldName] = '';
				}
			}
		}
//t3lib_div::debug($data);
		return $data;
	}

	/**
	 * This method returns the data model for linking to the previous step in the process
	 * The data model contains information for usage both in links and to display a form with a "Back" button
	 *
	 * @param	array	$parameters: current process parameters
	 *
	 * @return	array	data model
	 */
	public function getPreviousStepLinkInfo($parameters) {
		$data = array();
		if ($parameters['step'] > 1) {
			$data['linkParameters'] = '&step='.($parameters['step'] - 1).'&process='.$parameters['process'];
			$formInfo = '';
				// Get helper object for creating FORM fields
			$formHelper = t3lib_div::makeInstance('tx_advancedform_formelements');
				// Define wrapper for field names
			$formHelper->setPrefix('tx_vgeprocesses_controller');
				// Add hidden fields with information about process type and subtype
			$formInfo .= $formHelper->addHidden('viewType', 'run');
			$formInfo .= $formHelper->addHidden('step', $parameters['step'] - 1);
			$formInfo .= $formHelper->addHidden('subtype', $parameters['subtype']);
			$formInfo .= $formHelper->addHidden('process', $parameters['process']);
				// Add back button
			$formInfo .= ' |form_submit = submit| Back';
			$data['form'] = $formInfo;
		}
		return $data;
	}

	/**
	 * This method returns the data model for linking to the next step in the process
	 * The data model contains information for usage both in links and to display a form with a "Back" button
	 *
	 * @param	array	$configuration: configuration options of the process
	 * @param	array	$parameters: current process parameters
	 *
	 * @return	array	data model
	 */
	public function getNextStepLinkInfo($configuration, $parameters) {
		$data = array();
		if ($parameters['step'] < count($configuration['steps'])) {
			$data['linkParameters'] = '&step='.($parameters['step'] + 1).'&process='.$parameters['process'];
			$formInfo = '';
				// Get helper object for creating FORM fields
			$formHelper = t3lib_div::makeInstance('tx_advancedform_formelements');
				// Define wrapper for field names
			$formHelper->setPrefix('tx_vgeprocesses_controller');
				// Add hidden fields with information about process type and subtype
			$formInfo .= $formHelper->addHidden('viewType', 'run');
			$formInfo .= $formHelper->addHidden('step', $parameters['step'] + 1);
			$formInfo .= $formHelper->addHidden('subtype', $parameters['subtype']);
			$formInfo .= $formHelper->addHidden('process', $parameters['process']);
				// Add back button
			$formInfo .= ' |form_submit = submit| Next';
			$data['form'] = $formInfo;
		}
		return $data;
	}

	/**
	 * This method returns the process' full name
	 *
	 * @param	string	$processKey: keyword corresponding to the process
	 *
	 * @return	string	full process name
	 */
	public function getProcessName($processKey) {
		return $GLOBALS['LANG']->getLL('process.'.$processKey);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_vitalstatistics/base/class.tx_vgevitalstatistics_model_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_vitalstatistics/base/class.tx_vgevitalstatistics_model_base.php']);
}

?>