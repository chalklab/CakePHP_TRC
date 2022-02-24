<?php

/**
 * Class Stat
 * model for the stats table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Stat extends AppModel
{
	// relationships to other tables
	public $belongsTo = ['File','Dataset'];

	/**
	 * function to add a new dataset if it does not already exist
	 * @param array $data
	 * @return int
	 */
	public function add(array $data): int
	{
		return $this->addentry('Stat',$data);
	}

}
