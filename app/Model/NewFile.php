<?php

/**
 * Class File
 * File model
 */
class NewFile extends AppModel
{

	public $useDbConfig='new';
	public $useTable='files';

	public $hasMany = [
		'NewChemical'=> [
			'foreignKey' => 'file_id',
			'dependent' => true
		],
		'NewError'=> [
			'foreignKey' => 'file_id',
			'dependent' => true
		],
		'NewDataset'=> [
			'foreignKey' => 'file_id',
			'dependent' => true
		],
	];

	public $belongsTo = [
		'NewJournal'=> [
			'foreignKey' => 'journal_id'
		],
		'NewReference'=> [
			'foreignKey' => 'reference_id'
		]
	];

	//public $virtualFields=['titlecount'=>"concat(File.title,' (',File.datapoints,')'"];

	/**
	 * function to add a new file if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add(array $data): int
	{
		$model='NewFile';
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
