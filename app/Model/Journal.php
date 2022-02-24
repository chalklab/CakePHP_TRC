<?php

/**
 * Class Journal
 * model for the journals table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Journal extends AppModel
{
	// relationships to other tables
	public $hasMany = ['Reference'];

	/**
	 * function to add a new file if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Journal',$data);
	}

	/**
	 * get data from supplied field
	 * @param string $field
	 * @param string $find
	 * @param string $type
	 * @return false|mixed
	 */
	public function getfield(string $field, string $find, string $type="abbrev")
	{
		$j=$this->find('first',['conditions'=>[$type=>$find],'recursive'=>-1]);
		if(!empty($j)) {
            return $j['Journal'][$field];
        } else {
		    return false;
        }
	}
}
