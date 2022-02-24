<?php

/**
 * Class System
 * model for the systems table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class System extends AppModel
{
	// relationships to other tables
	public $hasAndBelongsToMany = ['Substance'];
    public $hasMany = ['Dataset','Condition','Mixture'];

	// create additional 'virtual' fields built from real fields
	public $virtualFields = [
		'first' => 'UPPER(SUBSTR(System.name,1,1))',
		'namercnt' => "CONCAT(System.name,':',System.refcnt)"];

	/**
	 * function to add a new system if it does not already exist
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('System',$data);
	}
}
