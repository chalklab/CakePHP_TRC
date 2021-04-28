<?php

/**
 * Class SubstancesSystem
 * SubstancesSystem model
 */
class NewSubstancesSystem extends AppModel
{
	public $useDbConfig='new';
	public $useTable='substances_systems';

	/**
	 * function to add a new substances_system join if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add(array $data): int
	{
		$model='NewSubstancesSystem';
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
