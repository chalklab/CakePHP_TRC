<?php

/**
 * Class Error
 */
class NewError extends AppModel {

	public $belongsTo=['NewFile'];

	/**
	 * General function to add a new error
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add($data)
	{
		$model='Error';
		$this->create();
		$ret=$this->save([$model=>$data]);
		$this->clear();
		return $ret[$model];
	}

}
