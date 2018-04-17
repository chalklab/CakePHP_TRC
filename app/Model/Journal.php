<?php

/**
 * Class Journal
 * Journal model
 */
class Journal extends AppModel
{
	
	public $hasMany = ['Reference','File'];
	
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
            return $j['Journal'][$field];
        } else {
		    return false;
        }
	}
}