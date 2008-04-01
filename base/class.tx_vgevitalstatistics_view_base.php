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

require_once(t3lib_extMgm::extPath('vge_processes').'class.tx_vgeprocesses_view_base.php');
require_once(t3lib_extMgm::extPath('vge_vitalstatistics').'base/class.tx_vgevitalstatistics_common_base.php');

/**
 * View service for base processes related to vital statistics
 *
 * @author	Francois Suter (Cobweb) <support@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_vgevitalstatistics
 */
class tx_vgevitalstatistics_view_base extends tx_vgeprocesses_view_base {

	public function __construct() {
		$this->processList = tx_vgevitalstatistics_common_base::$processList;
		$this->locallangFile = tx_vgevitalstatistics_common_base::$locallangFile;
		parent::__construct();
	}

	/** 
	 * This method displays a running process given some data
	 *
	 * @param	array	$data: data relevant to the process, as provided by the model
	 * @param	object	$pObj: reference to the controller
	 *
	 * @return	string	HTML code to display
	 */
	public function displayProcess($data, &$pObj) {
//t3lib_div::debug($pObj->cObj->data);
//t3lib_div::debug($data);
		$localCObj = t3lib_div::makeInstance('tslib_cObj');
			// Display current step
		$content = '<h2>'.$data['current']['name'].'</h2>';
		$content .= '<h3>'.$data['current']['stepTitle'].'</h3>';
		switch ($data['current']['type']) {
			case 'form':
					// Load the form structure into the bodytext field of the cObj data
				$localCObj->data['bodytext'] = $data['current']['form'];
					// Render the form with the appropriate configuration
				$content .= $localCObj->cObjGetSingle('FORM', $pObj->conf['forms.']);
				break;
			case 'validation':
				$content .= '<ul>';
				foreach ($data['current']['input'] as $name => $value) {
					$content .= '<li>'.$name.': '.$value.'</li>';
				}
				$content .= '</ul>';
				break;
			case 'payment':
					// Load the form structure into the bodytext field of the cObj data
				$localCObj->data['bodytext'] = $data['current']['form'];
					// Render the form with the appropriate configuration
				$content .= $localCObj->cObjGetSingle('FORM', $pObj->conf['forms.']);
				break;
			case 'paymentvalidation':
				if (isset($data['current']['error'])) {
					$content .= '<p><strong>'.$data['current']['error'].'</strong></p>';
				}
				if (isset($data['current']['form'])) {
						// Load the form structure into the bodytext field of the cObj data
					$localCObj->data['bodytext'] = $data['current']['form'];
						// Render the form with the appropriate configuration
					$content .= $localCObj->cObjGetSingle('FORM', $pObj->conf['forms.']);
				}
				break;
			default:
				$content .= '<p>It seems this step was not configured properly.</p>';
				break;
		}
		if (isset($data['previous']) && count($data['previous']) > 0) {
			$localCObj->data['bodytext'] = $data['previous']['form'];
			$content .= $localCObj->cObjGetSingle('FORM', $pObj->conf['forms.']);
		}
		if (isset($data['next']) && count($data['next']) > 0) {
			$localCObj->data['bodytext'] = $data['next']['form'];
			$content .= $localCObj->cObjGetSingle('FORM', $pObj->conf['forms.']);
		}
		return $content;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_vitalstatistics/base/class.tx_vgevitalstatistics_model_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_vitalstatistics/base/class.tx_vgevitalstatistics_model_base.php']);
}

?>