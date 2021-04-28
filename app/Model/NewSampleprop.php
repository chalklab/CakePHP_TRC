<?php

/**
 * Class Sampleprop
 * Sampleprop model
 */
class NewSampleprop extends AppModel
{

	public $useDbConfig='new';
	public $useTable='sampleprops';

	public $hasMany = [
		'NewData'=> [
			'foreignKey' => 'sampleprop_id',
			'dependent' => true
		],
	];

	public $belongsTo = [
		'NewDataset'=> [
			'foreignKey' => 'dataset_id'
		],
		'NewProperty'=> [
			'foreignKey' => 'property_id'
		],
		'NewUnit'=> [
			'foreignKey' => 'unit_id'
		]
	];

	/**
	 * function to add a new sampleprop if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add(array $data): int
	{
		$model='NewSampleprop';
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
