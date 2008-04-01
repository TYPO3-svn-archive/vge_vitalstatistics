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

/**
 * Common values (and methods?) for vital statistics
 *
 * @author	Francois Suter (Cobweb) <support@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_vgevitalstatistics
 */
class tx_vgevitalstatistics_common_base {
	static public $processList = array('birth', 'marriage', 'death');
	static public $locallangFile = 'EXT:vge_vitalstatistics/base/locallang.xml';
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_vitalstatistics/base/class.tx_vgevitalstatistics_model_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_vitalstatistics/base/class.tx_vgevitalstatistics_model_base.php']);
}

?>