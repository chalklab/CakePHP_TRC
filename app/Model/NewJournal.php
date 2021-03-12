<?php

/**
 * Class Journal
 * Journal model
 */
class NewJournal extends AppModel
{

	public $useDbConfig='new';
	public $useTable='journals';

	public $hasMany = [
		'NewReference'=> [
			'foreignKey' => 'journal_id',
			'dependent' => true
		],
		'NewFile'=> [
			'foreignKey' => 'journal_id',
			'dependent' => true
		]
	];

	/**
	 * General function to add a new file
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add($data)
	{
		$model='Journal';
		$this->create();
		$ret=$this->save([$model=>$data]);
		$this->clear();
		return $ret[$model];
	}

	public function getfield($field,$find,$type="abbrev")
	{
		$j=$this->find('first',['conditions'=>[$type=>$find],'recursive'=>-1]);
		if(!empty($j)) {
            return $j['NewJournal'][$field];
        } else {
		    return false;
        }
	}
}
