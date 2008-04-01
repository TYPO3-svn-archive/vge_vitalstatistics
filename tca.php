<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_vgevitalstatistics_processes'] = array (
	'ctrl' => $TCA['tx_vgevitalstatistics_processes']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'type,user,status,formdata'
	),
	'feInterface' => $TCA['tx_vgevitalstatistics_processes']['feInterface'],
	'columns' => array (
		'type' => Array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:vge_vitalstatistics/locallang_db.xml:tx_vgevitalstatistics_processes.type',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required,trim',
			)
		),
		'user' => Array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:vge_vitalstatistics/locallang_db.xml:tx_vgevitalstatistics_processes.user',		
			'config' => Array (
				'type' => 'group',	
				'internal_type' => 'db',	
				'allowed' => 'fe_users',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'status' => Array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:vge_vitalstatistics/locallang_db.xml:tx_vgevitalstatistics_processes.status',		
			'config' => Array (
				'type'     => 'input',
				'size'     => '4',
				'max'      => '4',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => Array (
					'upper' => '1000',
					'lower' => '10'
				),
				'default' => 0
			)
		),
		'formdata' => Array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:vge_vitalstatistics/locallang_db.xml:tx_vgevitalstatistics_processes.formdata',		
			'config' => Array (
				'type' => 'flex',
				'ds_pointerField' => 'type',
				'ds' => array('default' => 'FILE:EXT:vge_vitalstatistics/flexform_ds.xml'),
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'type;;;;1-1-1, user, status, formdata')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
?>