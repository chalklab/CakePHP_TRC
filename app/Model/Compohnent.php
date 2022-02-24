<?php

/**
 * Class Compohnent (Component is conflict with CakePHP)
 * model for the components (of mixtures) table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Compohnent extends AppModel
{
	// assign model to table because of naming conflict with CakePHP
	public $useTable='components';

	// relationships to other tables
	public $belongsTo=['Chemical','Mixture'];

	// no need for dependent=true as conditions and data are already deleted based on datapoint
	public $hasMany=[
		'Condition'=> ['foreignKey' => 'component_id'],
		'Data'=> ['foreignKey' => 'data_id']
	];

	/**
	 * function to add a new component if it does not already exist
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Compohnent',$data);
	}

}
