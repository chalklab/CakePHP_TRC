<?php

/**
 * Class Datapoint
 * model for the datapoint table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Datapoint extends AppModel
{
	// relationships to other tables
	// conditions and data tables marked as dependent, so they get deleted when a datapoint does
    public $hasMany = [
        'Condition'=> [
            'foreignKey' => 'datapoint_id',
            'dependent' => true,
        ],
        'Data'=> [
            'foreignKey' => 'datapoint_id',
            'dependent' => true,
        ]
	];
    public $belongsTo = ['Dataseries','Dataset'];

	/**
	 * function to add a new dataset if it does not already exist
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Datapoint',$data);
	}

}
