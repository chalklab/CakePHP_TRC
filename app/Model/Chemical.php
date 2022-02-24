<?php

/**
 * Class Chemical
 * model for the chemicals table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Chemical extends AppModel
{
	// relationships to other tables
	// components and purificationstep tables marked as dependent, so they get deleted when a chemical does
	public $belongsTo = [
		'File'=>['foreignKey' => 'file_id',],
		'Substance'=>['foreignKey' => 'substance_id',]
	];
	public $hasMany = [
		'Compohnent'=> [
			'foreignKey' => 'chemical_id',
			'dependent' => true
		],
		'Purificationstep'=> [
			'foreignKey' => 'chemical_id',
			'dependent' => true
		]
	];
	public $hasAndBelongsToMany=['Dataset'];

	/**
	 * function to add a new chemical if it does not already exist
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Chemical',$data);
	}

}
