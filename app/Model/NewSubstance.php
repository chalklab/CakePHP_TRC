<?php

/**
 * Class Substance
 * Substance Model
 */
class NewSubstance extends AppModel {

	public $useDbConfig='new';
	public $useTable='substances';

	public $hasMany=[
        'NewIdentifier'=> [
            'foreignKey' => 'substance_id',
            'dependent' => true
        ],
		'NewChemical'=> [
			'foreignKey' => 'substance_id',
			'dependent' => true
		]
    ];

	public $hasAndBelongsToMany = [
    	'NewSystem'=> [
			'foreignKey' => 'substance_id'
		]
	];

    public $virtualFields=[
    	'first' => 'UPPER(SUBSTR(NewSubstance.name,1,1))',
		'caskey'=>"CONCAT(COALESCE(NewSubstance.casrn,'NULL'),':',NewSubstance.inchikey)"];

	/**
	 * function to add a new substance if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add(array $data): int
	{
		$model='NewSubstance';
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
