<?php

/**
 * Class mixture
 * Mixture model
 */
class NewMixture extends AppModel
{

	public $useDbConfig='new';
	public $useTable='mixtures';

	public $belongsTo=[
		'NewSystem'=> [
			'foreignKey' => 'system_id'
		],
		'NewDataset'=> [
			'foreignKey' => 'dataset_id'
		]
	];

	public $hasMany=[
		'NewComponent'=> [
			'foreignKey' => 'mixture_id',
			'dependent' => true
		],
		'NewPhase'=> [
			'foreignKey' => 'mixture_id',
			'dependent' => true
		]
	];

	/**
	 * function to add a new mixture if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add(array $data): int
	{
		$model='NewMixture';
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
