<?php

/**
 * Class phase
 * model for the phases table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
*/
class Phase extends AppModel
{
	// relationships to other tables
	public $belongsTo = ['Phasetype','Mixture'];

	/**
	 * function to add a new phase if it does not already exist
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Phase',$data);
	}

}
