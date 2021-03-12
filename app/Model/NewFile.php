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
			'dependent' => true],
		'NewError'=> [
			'foreignKey' => 'file_id',
			'dependent' => true],
		'NewDataset'=> [
			'foreignKey' => 'file_id',
			'dependent' => true],

	];

	public $belongsTo = ['NewJournal','NewReference'];

	//public $virtualFields=['titlecount'=>"concat(File.title,' (',File.datapoints,')'"];

	/**
	 * General function to add a new file
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add($data)
	{
		$model='NewFile';
		$this->create();
		$ret=$this->save([$model=>$data]);
		$this->clear();
		return $ret[$model];
	}
}
