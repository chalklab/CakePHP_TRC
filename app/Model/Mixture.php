<?php

/**
 * Class mixture
 * model for the mixtures table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Mixture extends AppModel
{
	// relationships to other tables
	// components and phase tables marked as dependent, so they get deleted when a datapoint does
	public $hasMany=[
		'Compohnent'=> [
			'foreignKey' => 'mixture_id',
			'dependent' => true
		],
		'Phase'=> [
			'foreignKey' => 'mixture_id',
			'dependent' => true
		]
	];
	public $belongsTo=['System','Dataset'];

	/**
	 * function to add a new mixture if it does not already exist
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Mixture',$data);
	}

}
