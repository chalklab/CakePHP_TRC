<?php

/**
 * Class ChemicalsDataset
 * model for the chemicals_datasets join table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class ChemicalsDataset extends AppModel
{
	// relationships to other tables
	public $belongsTo = ['Chemical','Dataset'];

	/**
	 * function to add a new chemicals_dataset join if it does not already exist
	 * @param array $data
	 * @return int
	 */
	public function add(array $data): int
	{
		return $this->addentry('ChemicalsDataset',$data);
	}

}
