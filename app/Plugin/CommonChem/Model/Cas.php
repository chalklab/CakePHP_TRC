<?php
App::uses('AppModel', 'Model');
App::uses('ClassRegistry', 'Utility');
App::uses('HttpSocket', 'Network/Http');

/**
 * Class Compound
 * Compound model
 */
class Cas extends AppModel
{

	public $spath='https://commonchemistry.cas.org/api/search?q=';

	public $cpath='https://commonchemistry.cas.org/api/detail?cas_rn=';

	/**
	 * search the common chemistry API using an identifier (inchikey, casrn are best)
	 * @param $id
	 */
	public function search($id)
	{
		$HttpSocket = new HttpSocket();
		$url=$this->spath.$id;
		$json=$HttpSocket->get($url);
		$hits=json_decode($json,true);
		if($hits['count']==0) {
			return false;
		} else {
			return $hits['results'][0]['rn'];
		}
	}

	public function detail($id)
	{
		$HttpSocket = new HttpSocket();
		$url=$this->cpath.$id;
		$json=$HttpSocket->get($url);
		return json_decode($json,true);
	}
}
