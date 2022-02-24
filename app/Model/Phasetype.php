<?php

/**
 * Class phasetype
 * model for the phasetypes table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Phasetype extends AppModel
{
	// relationships to other tables
	// phases table marked as dependent, so they get deleted when a phasetype does
	public $hasMany = [
		'Phase'=> [
			'foreignKey' => 'phasetype_id',
			'dependent' => true
		]
	];
}
