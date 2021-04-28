<?php
App::uses('AppModel', 'Model');
App::uses('ClassRegistry', 'Utility');
App::uses('HttpSocket', 'Network/Http');

/**
 * Class Wiki
 * Wikidata model
 */
class Wiki extends AppModel
{

	public $qpath='https://query.wikidata.org/sparql';

	/**
	 * search the Wikidata SPARQL service
	 * @param $id
	 * @return false|mixed
	 */
	public function search($id)
	{
		$HttpSocket = new HttpSocket();
		$path=$this->qpath;
		$q1 = 'SELECT DISTINCT ?compound ';
    	$q2 = 'WHERE { ?compound wdt:P235 "'.$id.'" .';
    	$q3 = 'SERVICE wikibase:label { bd:serviceParam wikibase:language "en". }}';
    	$sparql = $q1.$q2.$q3;
    	$url=$path."?query=".$sparql."&format=json";
    	echo $url;exit;
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
		$hit=json_decode($json,true);
		debug($hit);exit;

	}
}
