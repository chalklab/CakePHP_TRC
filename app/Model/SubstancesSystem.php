<?php

/**
 * Class SubstancesSystem
 * model for the substancessystem (join) table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class SubstancesSystem extends AppModel
{
	// relationships to other tables
	public $belongsTo = ['Substance','System'];

	/**
	 * function to add a new substances_system join if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('SubstancesSystem',$data);
	}

}
