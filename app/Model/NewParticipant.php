<?php

/**
 * Class Participant
 * Participant model
 */
class NewParticipant extends AppModel
{

	public $useDbConfig='new';
	public $useTable='participants';

	public $belongsTo = [
		'NewReaction'=>[
			'foreignKey' => 'reaction_id',
		],
		'NewChemical'=>[
			'foreignKey' => 'chemical_id',
		],
		'NewSubstance'=>[
			'foreignKey' => 'substance_id',
		],
		'NewPhase'=>[
			'foreignKey' => 'phase_id',
		]
	];

	/**
	 * function to add a new particpant (of a reaction) if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws Exception
	 */
	public function add(array $data): int
	{
		$model='NewParticipant';
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
