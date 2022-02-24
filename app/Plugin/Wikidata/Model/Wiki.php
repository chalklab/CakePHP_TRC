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
	 * search the Wikidata SPARQL service using substance inchikey
	 * @param string $key
	 * @return mixed
	 */
	public function findbykey(string $key='')
	{
		if($key!='') {
			$HttpSocket = new HttpSocket(['ssl_verify_host' => false,'ssl_verify_peer'=>false]);
			$path=$this->qpath;
			$sparql = 'SELECT DISTINCT * ';
			$sparql .= 'WHERE { ?wikidataId wdt:P235 "'.$key.'" .';
			$sparql .= 'OPTIONAL { ?wikidataId wdt:P231 ?casrn .} ';
			$sparql .= 'OPTIONAL { ?wikidataId wdt:P661 ?chemspiderId .} ';
			$sparql .= 'OPTIONAL { ?wikidataId wdt:P8494 ?dsstoxcmpId .} ';
			$sparql .= 'OPTIONAL { ?wikidataId wdt:P592 ?chemblId .} ';
			$sparql .= 'OPTIONAL { ?wikidataId wdt:P232 ?ecnumber .} ';
			$sparql .= 'SERVICE wikibase:label { bd:serviceParam wikibase:language "en". }}';
			$url=$path."?query=".$sparql."&format=json";
			$response=$HttpSocket->get($url);
			$json=$response['body'];
			$data=json_decode($json,true);
			//debug($key);debug($data);exit;
			if(empty($data['results']['bindings'])) {
				return false;
			} else {
				$meta=[];$fields=$data['head']['vars'];$vals=$data['results']['bindings'][0];
				//debug($fields);debug($vals);exit;
				foreach($fields as $field) {
					if(isset($vals[$field])) {
						if($vals[$field]['type']=='uri'&&$field=='wikidataId') {
							$meta[$field]=str_replace('http://www.wikidata.org/entity/','',$vals[$field]['value']);
						} else {
							$meta[$field]=$vals[$field]['value'];
						}
					}
				}
				return $meta;
			}
		} else {
			return false;
		}
	}

}
