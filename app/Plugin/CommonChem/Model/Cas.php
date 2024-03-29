<?php
App::uses('AppModel', 'Model');
App::uses('ClassRegistry', 'Utility');
App::uses('HttpSocket', 'Network/Http');

/**
 * Class Cas
 * model to allow access to the CommonChemistry API
 * https://commonchemistry.cas.org/api/
 */
class Cas extends AppModel
{

	public string $spath='https://commonchemistry.cas.org/api/search?q=';

	public string $cpath='https://commonchemistry.cas.org/api/detail?cas_rn=';

	/**
	 * search the common chemistry API using an identifier (inchikey, casrn are best)
	 * @param $id
	 * @return mixed
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

	/**
	 * search the commonchemistry API for a specific query string
	 * NOTE: InChIKey searches must start with InChIKey= at the front of the query string
	 * @param $id
	 * @return mixed
	 * @return string
	 */
	public function detail($id)
	{
		$HttpSocket = new HttpSocket();
		$url=$this->cpath.$id;
		$json=$HttpSocket->get($url);
		return json_decode($json->body(),true);
	}
}
