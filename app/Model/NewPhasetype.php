<?php

/**
 * Class phasetype
 * Phasetype model
 */
class NewPhasetype extends AppModel
{

	public $useDbConfig='new';
	public $useTable='phasetypes';

	public $hasMany = [
		'NewPhase'=> [
			'foreignKey' => 'phasetype_id',
			'dependent' => true
		]
	];
}
