<?php

/**
 * Class Sampleprop
 * Sampleprop model
 */
class Sampleprop extends AppModel
{

    public $belongsTo = ['Dataset'];
	
	public $virtualFields=['propstr' => 'CONCAT(property_name," ",orgnum)'];
	
	public function getfield($field,$find,$type="id")
	{
		$j=$this->find('first',['conditions'=>[$type=>$find],'recursive'=>-1]);
		if(!empty($j)) {
			return $j['Sampleprop'][$field];
		} else {
			return false;
		}
	}
}