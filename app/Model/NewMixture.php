<?php

/**
 * Class mixture
 * Mixture model
 */
class NewMixture extends AppModel
{

	public $useDbConfig='new';
	public $useTable='mixtures';

	public $belongsTo=[
		'NewSystem'=> [
			'foreignKey' => 'system_id'
		],
		'NewDataset'=> [
			'foreignKey' => 'dataset_id'
		]
	];

	public $hasMany=[
		'NewComponent'=> [
			'foreignKey' => 'mixture_id'
		],
	];

}
