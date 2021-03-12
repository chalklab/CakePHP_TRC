<?php

/**
 * Class Setting
 * Setting model
 */
class NewSetting extends AppModel
{

	public $useDbConfig='new';
	public $useTable='settings';

	public $belongsTo = [
    	'NewDataset'=> [
			'foreignKey' => 'dataset_id'
		],
		'NewDataseries'=> [
			'foreignKey' => 'dataseries_id'
		],
		'NewDatapoint'=> [
			'foreignKey' => 'datapoint_id'
		],
		'NewProperty'=> [
			'foreignKey' => 'property_id'
		],
		'NewUnit'=> [
			'foreignKey' => 'unit_id'
		]
	];

}
