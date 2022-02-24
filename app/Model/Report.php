<?php

/**
 * Class Report
 * model for the reports table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Report extends AppModel
{
	// relationships to other tables
	// datasets marked as dependent, so they get deleted when a report does
	public $hasMany = [
        'Dataset'=> [
            'foreignKey' => 'report_id',
            'dependent' => true]
    ];
    public $belongsTo = ['File','Reference'];

	/**
	 * function to add a new report if it does not already exist
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Report',$data);
	}

}
