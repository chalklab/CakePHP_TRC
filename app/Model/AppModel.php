<?php
App::uses('Model', 'Model');
App::uses('ClassRegistry', 'Utility');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {

    public $actsAs = ['Containable'];

	/**
	 * function to add a new entry in DB table ($model) if it does not already exist
	 * @param string $model
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function addentry(string $model, array $data): int
	{
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

	/**
	 * get contents of field
	 * @param string $model
	 * @param string $field
	 * @param string $value
	 * @param string $findin
	 * @return mixed
	 */
	public function fieldval(string $model, string $field, string $value, string $findin="id")
	{
		$found=$this->find('first',['conditions'=>[$findin=>$value],'recursive'=>-1]);
		if(!empty($found)) {
			return $found[$model][$field];
		} else {
			return false;
		}
	}
}
