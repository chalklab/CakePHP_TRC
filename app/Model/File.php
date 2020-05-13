<?php

/**
 * Class File
 * File model
 */
class File extends AppModel
{

    public $hasMany = [
        'Chemical'=> [
            'foreignKey' => 'file_id',
            'dependent' => true],
        'Error'=> [
            'foreignKey' => 'file_id',
            'dependent' => true],
		'Dataset'=> [
		    'foreignKey' => 'file_id',
		    'dependent' => true],

    ];

    public $belongsTo = ['Journal','Reference'];

    //public $virtualFields=['titlecount'=>"concat(File.title,' (',File.datapoints,')'"];

	/**
	 * General function to add a new file
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add($data)
	{
		$model='File';
		$this->create();
		$ret=$this->save([$model=>$data]);
		$this->clear();
		return $ret[$model];
	}
}
