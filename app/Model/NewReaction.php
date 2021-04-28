<?php

/**
 * Class Reaction
 * Reaction model
 */
class NewReaction extends AppModel
{

	public $useDbConfig='new';
	public $useTable='reactions';

	public $hasMany = [
		'NewParticipant'=> [
			'foreignKey' => 'reaction_id',
			'dependent' => true
		]
	];

	public $belongsTo = [
		'NewDataset'=>[
			'foreignKey' => 'dataset_id',
		]
	];

	/**
	 * function to add a new reaction if it does not already exist
	 * @param array $data
	 * @param $setcnt
	 * @return integer
	 * @throws Exception
	 */
	public function add(array $data): int
	{
		$model='NewReaction';
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
