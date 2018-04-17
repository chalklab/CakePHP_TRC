<?php

/**
 * Class Unit
 * Unit model
 */
class Unit extends AppModel
{

	public $hasAndBelongsToMany = ['Quantity','Parameter','Variable'];

    public $hasMany=['Rulesnippet'];
	
	public function getfield($field,$find,$type="id")
	{
		$j=$this->find('first',['conditions'=>[$type=>$find],'recursive'=>-1]);
		if(!empty($j)) {
			return $j['Unit'][$field];
		} else {
			return false;
		}
	}
	
	
	/**
	 * retrieve qudt unit from symbol
	 * @param $unit
	 * @return string
	 */
	public function qudt($symbol)
	{
		$qudt=$this->getfield('qudt',$symbol,'symbol');
		return "qudt:".$qudt;
	}
}