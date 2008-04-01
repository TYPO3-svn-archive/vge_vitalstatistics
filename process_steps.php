<?php
/*
 * This file contains the configuration for the steps of the processes of the Vital Statistics extension
 *
 * NOTE: The file must be included in ext_localconf.php
 *
 * $Id: $
 */
$TYPO3_CONF_VARS['EXTCONF']['vge_vitalstatistics']['processes'] = array(
	'_DEFAULT' => array(
		'steps' => array(
			1 => array(
				'type' => 'form',
				'sheet' => 'sDEF'
			),
			2 => array(
				'type' => 'validation',
				'sheet' => 'sDEF',
				'displayNextButton' => true
			),
			3 => array(
				'type' => 'payment',
				'paymethods' => array(
									'paymentlib_offline_bank_check',
									'paymentlib_offline_giro',
									'paymentlib_offline_cod',
									'paymentlib_offline_cash'
								),
			),
			4 => array(
				'type' => 'paymentvalidation',
			),
			5 => array(
				'type' => 'confirmation'
			)
		)
	)
);
?>