<?php

/**
 * Class Property
 * Property model
 */
class NewProperty extends AppModel
{

	public $useDbConfig='new';
	public $useTable='properties';

	public $hasMany = [
    	'NewData'=> [
			'foreignKey' => 'property_id',
			'dependent' => true
		],
		'NewCondition'=> [
			'foreignKey' => 'property_id',
			'dependent' => true
		]
	];

	public $belongsTo = [
		'NewQuantity'=> [
			'foreignKey' => 'quantity_id'
		]
	];

	/**
	 * get the value of a field
	 * @param $field
	 * @param $find
	 * @param string $type
	 * @return false|mixed
	 */
	public function getfield($field,$find,$type="id")
    {
        $j=$this->find('first',['conditions'=>[$type=>$find],'recursive'=>-1]);
        if(!empty($j)) {
            return $j['NewProperty'][$field];
        } else {
            return false;
        }
    }

}
