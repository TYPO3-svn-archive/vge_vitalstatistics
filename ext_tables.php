<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


t3lib_extMgm::allowTableOnStandardPages('tx_vgevitalstatistics_processes');

$TCA['tx_vgevitalstatistics_processes'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:vge_vitalstatistics/locallang_db.xml:tx_vgevitalstatistics_processes',		
		'label'     => 'type',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',	
		'delete' => 'deleted',	
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_vgevitalstatistics_processes.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'type, user, status, formdata',
	)
);
?>