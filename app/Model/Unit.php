<?php

/**
 * Class Unit
 * model for the units table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Unit extends AppModel
{
	// relationships to other tables
	public $hasMany = [
		'Data','Condition','SampleProp',
		'Quantity'=>['foreignKey' => 'defunit_id',],
		'Quantitykind'=>['foreignKey' => 'si_unit']
	];

	/**
	 * add a new unit if it does not already exist
	 * @param array $data
	 * @return int
	 */
	public function add(array $data): int
	{
		return $this->addentry('Unit',$data);
	}

	/**
	 * get contents of field
	 * @param string $field
	 * @param string $value
	 * @param string $findin
	 * @return mixed
	 */
	public function getfield(string $field, string $value, string $findin="id")
	{
		return $this->fieldval('Unit', $field, $value, $findin);
	}

	/**
	 * retrieve qudt unit from symbol
	 * @param $symbol
	 * @return string
	 */
	public function qudt($symbol): string
	{
		$qudt=$this->getfield('qudt',$symbol,'symbol');
		return "qudt:".$qudt;
	}

}
