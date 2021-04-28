<?php

/**
 * Class Unit
 * Unit model
 */
class NewUnit extends AppModel
{

	public $useDbConfig='new';
	public $useTable='units';

	public $hasAndBelongsToMany = ['NewQuantity'];
	public $hasMany = ['NewData','NewCondition','NewSampleProp'];

	/**
	 * retrieve contents of field
	 * @param $field
	 * @param $find
	 * @param string $type
	 * @return false|mixed
	 */
	public function getfield($field,$find,$type="id")
	{
		$j=$this->find('first',['conditions'=>[$type=>$find],'recursive'=>-1]);
		if(!empty($j)) {
			return $j['NewUnit'][$field];
		} else {
			return false;
		}
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
