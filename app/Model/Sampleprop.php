<?php

/**
 * Class Sampleprop
 * model for the sampleprops table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Sampleprop extends AppModel
{
	// relationships to other tables
	public $hasMany = ['Data'];
    public $belongsTo = ['Dataset','Quantity','Unit'];

	// create additional 'virtual' fields built from real fields
	public $virtualFields=[
		'propstr' => 'CONCAT(Sampleprop.quantity_name," ",Sampleprop.orgnum)',
		'propcode'=>'CONCAT(Sampleprop.dataset_id,":",Sampleprop.propnum)'];

	// functions that can be used in controllers

	/**
	 * function to add a new sampleprop if it does not already exist
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Sampleprop',$data);
	}

	/**
	 * get data from a field in the sampleprops table
	 * @param string $field
	 * @param string $find
	 * @param string $type
	 * @return false|mixed
	 */
	public function getfield(string $field, string $find, string $type="id")
	{
		$j=$this->find('first',['conditions'=>[$type=>$find],'recursive'=>-1]);
		if(!empty($j)) {
			return $j['Sampleprop'][$field];
		} else {
			return false;
		}
	}
}
