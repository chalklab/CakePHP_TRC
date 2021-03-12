<?php

/**
 * Class DataSystem
 * Parameter model
 */
class NewDataSystem extends AppModel
{
	public $useDbConfig='new';
	public $useTable='data_systems';

	public $belongsTo = [
    	'NewDataset'=> [
			'foreignKey' => 'dataset_id'
		],
		'NewReference'=> [
			'foreignKey' => 'reference_id'
		],
		'NewProperty'=> [
			'foreignKey' => 'property_id'
		]
	];
}
