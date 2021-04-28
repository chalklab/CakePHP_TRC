<?php

/**
 * Class System
 * System model
 */
class NewSystem extends AppModel
{
	public $useDbConfig='new';
	public $useTable='systems';

	public $hasAndBelongsToMany = [
		'NewSubstance'=> [
			'foreignKey' => 'system_id'
		],
		'NewData'=> [
			'foreignKey' => 'data_id'
		]
	];

    public $hasMany = [
    	'NewDataset'=> [
			'foreignKey' => 'system_id',
			'dependent' => true
		],
		'NewCondition'=> [
			'foreignKey' => 'system_id',
			'dependent' => true
		],
		'NewMixture'=> [
			'foreignKey' => 'system_id',
			'dependent' => true
		],
		'NewSubstancesSystem'=> [
			'foreignKey' => 'system_id',
			'dependent' => true
		]
	];

    public $virtualFields=['first' => 'UPPER(SUBSTR(NewSystem.name,1,1))'];

	/**
	 * function to add a new system if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add(array $data): int
	{
		$model='NewSystem';
		$found=$this->find('first',['conditions'=>$data,'recursive'=>-1]);
		if(!$found) {
			$this->create();
			$this->save([$model=>$data]);
			$id=$this->id;
			$this->clear();
		} else {
			$id=$found[$model]['id'];
		}
		return $id;
	}

}
