<?php

/**
 * Class Chemical
 * Chemical model
 */
class NewChemical extends AppModel
{

	public $useDbConfig='new';
	public $useTable='chemicals';

	public $hasMany = [
		'NewComponent'=> [
			'foreignKey' => 'chemical_id',
			'dependent' => true
		]
	];

	public $belongsTo = [
		'NewFile'=>[
			'foreignKey' => 'file_id',
			],
		'NewSubstance'=>[
			'foreignKey' => 'substance_id',
		]
	];

}
