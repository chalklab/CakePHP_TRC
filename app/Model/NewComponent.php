<?php

/**
 * Class components (of mixtures)
 * Components model
 */
class NewComponent extends AppModel
{

	public $useDbConfig='new';
	public $useTable='components';

	public $belongsTo=[
		'NewChemical'=> [
			'foreignKey' => 'chemical_id'
		],
		'NewMixture'=> [
			'foreignKey' => 'mixture_id'
		]
	];

	public $hasMany=[  // no need for dependent=true as already deleted based on datapoint
		'NewCondition'=> [
			'foreignKey' => 'component_id'
		],
		'NewData'=> [
			'foreignKey' => 'data_id'
		]
	];

	/**
	 * function to add a new component if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add(array $data): int
	{
		$model='NewComponent';
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
