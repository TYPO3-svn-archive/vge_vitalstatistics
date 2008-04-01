<?php
// Register model

t3lib_extMgm::addService($_EXTKEY,  'process_models' /* sv type */,  'tx_vgevitalstatistics_model_base' /* sv key */,
		array(

			'title' => 'Vital Statistics',
			'description' => 'Base processes models for Vital Statistics',

			'subtype' => 'vitalstatistics',

			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'base/class.tx_vgevitalstatistics_model_base.php',
			'className' => 'tx_vgevitalstatistics_model_base',
		)
	);

// Register views

t3lib_extMgm::addService($_EXTKEY,  'process_views' /* sv type */,  'tx_vgevitalstatistics_view_base' /* sv key */,
		array(

			'title' => 'Vital Statistics',
			'description' => 'Base processes views for Vital Statistics',

			'subtype' => 'vitalstatistics',

			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'base/class.tx_vgevitalstatistics_view_base.php',
			'className' => 'tx_vgevitalstatistics_view_base',
		)
	);

// Include steps configuration

require_once(t3lib_extMgm::extPath($_EXTKEY, 'process_steps.php'));
?>