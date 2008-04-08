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
	 * @param	array		$parameters: list of parameters, including process name, step, etc.
	 * @param	array		$configuration: TypoScript configuration of the process subtype
	 * @param	reference	$controller: reference to the controller object
	 *
	 * @return	array	all data related to the process and step
	 */
	public function getDataForStep($parameters, $configuration, &$controller) {
			// Store reference to the controller
		$this->controller = $controller;

			// Make sure a step number is defined
		if (empty($parameters['step'])) $parameters['step'] = 1;

			// Read the extension configuration to get step information
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vge_vitalstatistics']['processes'][$parameters['process']])) {
			$extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vge_vitalstatistics']['processes'][$parameters['process']];
		}
		else {
			$extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vge_vitalstatistics']['processes']['_DEFAULT'];
		}
		$stepConfiguration = $extensionConfiguration['steps'][$parameters['step']];
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
			case 'payment':
				$data['current'] = $this->processPaymentStep($stepConfiguration, $parameters);
				$data['current']['type'] = 'payment';
				break;
			case 'paymentvalidation':
				$data['current'] = $this->processPaymentValidationStep($stepConfiguration, $parameters);
				$data['current']['type'] = 'paymentvalidation';
				break;
			case 'confirmation':
				$data['current'] = $this->processConfirmationStep($stepConfiguration, $configuration, $parameters, $extensionConfiguration['steps']);
				$data['current']['type'] = 'confirmation';
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
			$data['next'] = $this->getNextStepLinkInfo($extensionConfiguration, $parameters);
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
			$data['stepTitle'] = $GLOBALS['TSFE']->sL($sheet['TCEforms']['sheetTitle']);

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
					$fieldName = $GLOBALS['TSFE']->sL($fieldData['TCEforms']['label']);
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
	 * This method gathers all the data related to the payment process
	 *
	 * @param	array	$configuration: configuration options for the step
	 * @param	array	$parameters: current process parameters
	 *
	 * @return	array	data model
	 */
	public function processPaymentStep($configuration, $parameters) {
		$data = array();

			// Reset session vars
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_vgevitalstatistics_payment_reference', false);
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_vgevitalstatistics_payment_piVars', array());

			// Get the list of payment methods
        $providerFactoryObj = tx_paymentlib_providerfactory::getInstance();
		$paymentMethodsArr = array();
		if ($providerObjectsArr = $providerFactoryObj->getProviderObjects()) {
			foreach ($providerObjectsArr as $providerObj) {
				$tmpArr = $providerObj->getAvailablePaymentMethods();
				$keys = array_intersect(array_keys($tmpArr), $configuration['paymethods']);
				foreach ($keys as $key) {
					$paymentMethodsArr[$key] = $tmpArr[$key];
				}
			}
		}
		$data['payment'] = $paymentMethodsArr;

				// Get helper object for creating FORM fields
		$formHelper = t3lib_div::makeInstance('tx_advancedform_formelements');
			// Define wrapper for field names
		$formHelper->setPrefix('tx_vgeprocesses_controller');
			// Prepare form syntax for choice of payment methods
		$selectedPayment = $parameters['paymethod'];
		$formInfo = '';
		$options = array();
		foreach ($paymentMethodsArr as $paymentMethodKey => $paymentMethodConf) {
			$paymentMethodConf['iconpath'] = str_replace('EXT:', '', $paymentMethodConf['iconpath']);
			$label = htmlspecialchars($GLOBALS['TSFE']->sL($paymentMethodConf['label']));
			if (!empty($paymentMethodConf['iconpath'])) $label = '<img src="/typo3conf/ext/' . $paymentMethodConf['iconpath'] . '" alt="'.$label.'" /> '.$label;
			$options[$paymentMethodKey] = $label;
		}
		$formInfo .= $formHelper->addRadioButton('Méthode de paiement', 'paymethod', $options);
		$formInfo .= $formHelper->addHidden('amount', $configuration['paymentinfo']['amount']);
		$formInfo .= $formHelper->addHidden('currency', $configuration['paymentinfo']['currency']);

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
			
//t3lib_div::debug($data);
		return $data;
	}

	/**
	 * This method gathers all the data related to the payment validation step
	 * In such a step the user is expected to confirm the choice of payment method
	 * and fill out any additional data required by the payment method
	 *
	 * @param	array	$configuration: configuration options for the step
	 * @param	array	$parameters: current process parameters
	 *
	 * @return	array	data model
	 */
	public function processPaymentValidationStep($configuration, $parameters) {
		$data = array();

// A reference must be defined so that if the payment has alreday been processed, it cannot be processed a second time

		if (!$paymentReference = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_vgevitalstatistics_payment_reference')) {
			$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_vgevitalstatistics_payment_reference', time());
			$paymentReference = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_vgevitalstatistics_payment_reference');
		}

// Plugin variables must be stored in session for when the payment process redirects to the donations process
// If values are not stored yet, do it. Else read them.

		if (!$GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_vgevitalstatistics_payment_piVars')) {
			$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_vgevitalstatistics_payment_piVars', $parameters);
		}
		else {
			$localParameters = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_vgevitalstatistics_payment_piVars');
			if (is_array($localParameters)) {
				foreach ($localParameters as $key => $val) {
					$parameters[$key] = !empty($parameters[$key]) ? $parameters[$key] : $localParameters[$key];
				}
			}
		}

// Get payment method information, in particular hidden or visible fields

		$providerFactoryObj = tx_paymentlib_providerfactory::getInstance();
		$providerObj = $providerFactoryObj->getProviderObjectByPaymentMethod($parameters['paymethod']);

//		$methods = $providerObj->getAvailablePaymentMethods();
//		$paymethodLabel = $methods[$parameters['paymethod']]['label'];

		$ok =  $providerObj->transaction_init(TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER, $parameters['paymethod'], TX_PAYMENTLIB_GATEWAYMODE_FORM, tx_vgevitalstatistics_common_base::$extKey);
		if (!$ok) {
			$data['error'] = $GLOBALS['LANG']->getLL('transaction_init_failed');
			return $data;
		}

// The confirm page can be called again if the payment failed
// If that is the case, issue error message

		if (is_array($transactionResultsArr = $providerObj->transaction_getResults($paymentReference))) {
			$message = $GLOBALS['LANG']->getLL('payment_declined');
		}

// TODO: change hardcoded amount and currency
		$transactionDetailsArr = array (
			'transaction' => array (
				'amount' => $parameters['amount'],
				'currency' => $parameters['currency'],
			),
			'options' => array (
				'reference' => $paymentReference,
			),
		);
		$ok = $providerObj->transaction_setDetails($transactionDetailsArr);
		if (!$ok) {
			$data['error'] = $GLOBALS['LANG']->getLL('transaction_settings_failed');
			return $data;
		}

// Set response URLs
// They are both very similar except for the step
// The OK page leads to the next step, whereas the error page stays on the same step

		$baseURI = ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http://' : 'https://') . t3lib_div::getThisUrl();
		$baseParameters = array(
								'tx_vgeprocesses_controller[viewType]' => 'run',
								'tx_vgeprocesses_controller[subtype]' => $parameters['subtype'],
								'tx_vgeprocesses_controller[process]' => $parameters['process']
							);
		$providerObj->transaction_setErrorPage($baseURI.$this->controller->pi_getPageLink($GLOBALS['TSFE']->id, '', array_merge($baseParameters, array('tx_vgeprocesses_controller[step]' => $parameters['step']))));
		$providerObj->transaction_setOKPage($baseURI.$this->controller->pi_getPageLink($GLOBALS['TSFE']->id, '', array_merge($baseParameters, array('tx_vgeprocesses_controller[step]' => $parameters['step'] + 1))));

// Define hidden fields for payment method

				// Get helper object for creating FORM fields
		$formHelper = t3lib_div::makeInstance('tx_advancedform_formelements');
			// Define wrapper for field names
//		$formHelper->setPrefix('tx_vgeprocesses_controller');
		$formInfo = '';
		$hiddenFields = array();
		if ($hf = $providerObj->transaction_formGetHiddenFields()) {
			foreach ($hf as $name => $value) {
				$formInfo .= $formHelper->addHidden($name, $value);
//				$hiddenFields[] = '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
			}
		}

// Define any visible field needed for payment method

		if ($vf = $providerObj->transaction_formGetVisibleFields()) {
			foreach ($vf as $name => $field) {
				$options = array('size' => $field['config']['size'], 'maxlength' => $field['config']['max']);
				$formInfo .= $formHelper->addElement($field['config']['type'], $name, $GLOBALS['TSFE']->sL($field['label']), '', false, $options);
			}
		}

		//TODO: modify FORM syntax to accept those parameters
//		$markers['###FORM_FORM_PARAMS###'] = $providerObj->transaction_formGetFormParms();
//		$markers['###BUTTONS###'] .= '<input type="submit" name="submit" value="'.$this->pi_getLL('confirm').'" '.($providerObj->transaction_formGetSubmitParms()).' />';
		$data['formconfig'] = array();
		$data['formconfig']['type'] = $providerObj->transaction_formGetActionURI();

			// Add hidden fields with information about process type and subtype
		$formInfo .= $formHelper->addHidden('viewType', 'run');
		$formInfo .= $formHelper->addHidden('subtype', $parameters['subtype']);
		$formInfo .= $formHelper->addHidden('process', $parameters['process']);

			// Add hidden field with information about step
		$formInfo .= $formHelper->addHidden('step', $parameters['step'] + 1);
		
			// Add submit button (temporary)
		$formInfo .= ' |form_submit = submit| Valider';

			// Store the form information
		$data['form'] = $formInfo;

			// Define additional data for display
		$data['data'] = array('amount' => $parameters['amount']);
		$data['data']['currency'] = $this->getCurrencyInformation($parameters['currency']);

//t3lib_div::debug($data);
		return $data;
	}

	/**
	 * This method gathers all the data related to the confirmation step
	 * This is the final step of the process. It stores data to the database and prepares a confirmation message.
	 *
	 * @param	array	$configuration: configuration options for the step
	 * @param	array	$processConfiguration: TypoScript configuration for the process subtype
	 * @param	array	$parameters: current process parameters
	 * @param	array	$steps: configuration options of all steps
	 *
	 * @return	array	data model
	 */
	public function processConfirmationStep($configuration, $processConfiguration, $parameters, $steps) {
		$data = array();

			// Get the payment transaction's reference
		$paymentReference = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_vgevitalstatistics_payment_reference');

			// Get the payment information stored in session
		if (!$GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_vgevitalstatistics_payment_piVars')) {
			$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_vgevitalstatistics_payment_piVars', $parameters);
		}
		else {
			$localParameters = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_vgevitalstatistics_payment_piVars');
			if (is_array($localParameters)) {
				foreach ($localParameters as $key => $val) {
					$parameters[$key] = !empty($parameters[$key]) ? $parameters[$key] : $localParameters[$key];
				}
			}
		}

			// If a payment was made store the transaction in the database
		if ($paymentReference) {
				// First get the payment transaction results
			$providerFactoryObj = tx_paymentlib_providerfactory::getInstance();
			$providerObj = $providerFactoryObj->getProviderObjectByPaymentMethod($parameters['paymethod']);
//			$methods = $providerObj->getAvailablePaymentMethods();
//			$paymethodLabel = $methods[$this->getPiVars('paymethod')]['label'];
			$providerObj->transaction_init(TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER, $parameters['paymethod'], TX_PAYMENTLIB_GATEWAYMODE_FORM, tx_vgevitalstatistics_common_base::$extKey);
			$transactionResultsArr = $providerObj->transaction_getResults($paymentReference);

				// Prepare data for storage into flexform field
			$flexformData = array('data' => array());
				// Load the full TCA for the vital statistics tables
			t3lib_div::loadTCA('tx_vgevitalstatistics_processes');
				// Load the formdata flexform
			$dataStructureArray = t3lib_BEfunc::getFlexFormDS($GLOBALS['TCA']['tx_vgevitalstatistics_processes']['columns']['formdata']['config'], array(), 'tx_vgevitalstatistics_processes');
				// Loop on all steps and handle those that have a sheet
			foreach ($steps as $stepData) {
				if (isset($stepData['sheet'])) {
					$stepInput = array();
					if (isset($dataStructureArray['sheets'][$stepData['sheet']]['ROOT'])) {
						$sheet = $dataStructureArray['sheets'][$stepData['sheet']]['ROOT'];
			
							// Loop on sheet elements
							// If an element has a value, store it
						foreach ($sheet['el'] as $field => $fieldData) {
							if (isset($parameters[$field])) {
								$stepInput[$field] = array('vDEF' => $parameters[$field]);
							}
						}
					}
					$flexformData['data'][$stepData['sheet']] = array('lDEF' => $stepInput);
				}
			}
				// Transform array to flexform data structure with the help of t3lib_flexformtools
			$flexTool = t3lib_div::makeInstance('t3lib_flexformtools');
			$flexformValue = $flexTool->flexArray2Xml($flexformData, true);

				// Store vital statistics transaction
			$fields = array ();
			$time = time();
			$fields['tstamp'] = $time;
			$fields['crdate'] = $time;
			$fields['pid'] = $processConfiguration['storagePID'];
			$fields['cruser_id'] = '';
			$fields['type'] = $parameters['process'];
			$fields['user'] = '';
			$fields['status'] = 1;
			$fields['formdata'] = $flexformValue;
			$fields['paymentlib_trx_uid'] = $transactionResultsArr['uid'];
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_vgevitalstatistics_processes', $fields);
//TODO: send confirmation mail (in view?)
	
				// Transaction is finished, reset session vars
			$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_vgevitalstatistics_payment_reference', false);
			$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_vgevitalstatistics_payment_piVars', array());
			$GLOBALS['TSFE']->fe_user->setKey('ses', 'processData', array());
		}
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

	/**
	 * This method gets the information about a currency from static_currencies
	 *
	 * @param	string	3-letter ISO code of the currency
	 *
	 * @return	array	currency data
	 */
	protected function getCurrencyInformation($code) {
		$currency = array();
		if (!empty($code)) {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','static_currencies', "cu_iso_3 = '".$code."'");
			if ($result) $currency = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		}
		return $currency;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_vitalstatistics/base/class.tx_vgevitalstatistics_model_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_vitalstatistics/base/class.tx_vgevitalstatistics_model_base.php']);
}

?>