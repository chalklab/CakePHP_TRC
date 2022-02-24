<?php

/**
 * Class Dataseries
 * model for the datasereis table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Dataseries extends AppModel
{
	// relationships to other tables
	// datapoints and conditions tables marked as dependent, so they get deleted when a dataseries does
	public $hasMany = [
        'Datapoint'=> [
            'foreignKey' => 'dataseries_id',
            'dependent' => true,
        ],
        'Condition'=> [
            'foreignKey' => 'dataseries_id',
            'dependent' => true,
        ]
    ];
    public $belongsTo = ['Dataset'];

	/**
	 * add new dataseries OR find existing dataseries and return dataseries id
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Dataseries',$data);
	}

}
