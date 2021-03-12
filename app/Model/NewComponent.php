<?php

/**
 * Class components (of mixtures)
 * Components model
 */
class NewComponent extends AppModel
{

	public $useDbConfig='new';
	public $useTable='components';

	public $belongsTo=[
		'NewChemical'=> [
			'foreignKey' => 'chemical_id'
		],
		'NewMixture'=> [
			'foreignKey' => 'mixture_id'
		]
	];

	public $hasMany=[
		'NewCondition'=> [
			'foreignKey' => 'component_id',
			'dependent' => true
		],
		'NewData'=> [
			'foreignKey' => 'data_id',
			'dependent' => true
		]
	];

}
