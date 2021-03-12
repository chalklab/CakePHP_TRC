<?php

/**
 * Class Sampleprop
 * Sampleprop model
 */
class NewSampleprop extends AppModel
{

	public $useDbConfig='new';
	public $useTable='sampleprops';

	public $hasMany = [
		'NewData'=> [
			'foreignKey' => 'sampleprop_id'
		],
	];

	public $belongsTo = [
		'NewDataset'=> [
			'foreignKey' => 'dataset_id'
		],
		'NewProperty'=> [
			'foreignKey' => 'property_id'
		],
		'NewUnit'=> [
			'foreignKey' => 'unit_id'
		]
	];

}
