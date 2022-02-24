<?php

/**
 * Class Identifier
 * model for the identifiers table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Identifier extends AppModel {

	// relationships to other tables
	public $belongsTo=['Substance'];

	/**
	 * function to add a new identifier if it does not already exist
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Identifier',$data);
	}

	/**
     * search the CIR API for identifiers
     * @param string $term
     * @return array
     */
    public function cir(string $term=""): array
    {
        $HttpSocket = new HttpSocket();
		$url="https://cactus.nci.nih.gov/chemical/structure/".rawurlencode($term)."/names/xml";
		$xmlfile =$HttpSocket->get($url);
		$xml = simplexml_load_string($xmlfile,'SimpleXMLElement',LIBXML_NOERROR|LIBXML_NOENT);
		$output=json_decode(json_encode($xml),true);
		if(isset($output['data'][0])) {
			return $output['data'][0]['item'];
		} elseif(isset($output['data']['item'])) {
			return $output['data']['item'];
		}
		return [];
	}

	/**
	 * find compound by CASRN on the CIR API
	 * @param $name
	 * @return bool|mixed
	 */
    public function getcircas($name)
	{
		$syns=$this->cir($name);
		if($syns) {
			$cas=[];
			foreach($syns as $syn) {
				if(preg_match('/[0-9]{2,7}-[0-9]{2}-[0-9]/i',$syn)) {
					$cas[]=$syn;
				}
			}
			if(!empty($cas)) {
				if(count($cas)==1) {
					return $cas[0];
				} else {
					// if multiple cas #'s the shortest string is the most likely the general one
                    asort($cas); // ensures the first casrn of a certain length is numeric lowest
                    $lengths=array_map('strlen',$cas);
					$min=min($lengths);
					foreach ($cas as $str) {
						if(strlen($str)==$min) {
							return $str;
						}
					}
				}
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * get the value of an identifier field
	 * $cond array allows multiple conditions to be defined
	 * @param string $field
	 * @param array $cond
	 * @return mixed
	 */
	public function getfield(string $field, array $cond)
	{
		$j=$this->find('first',['conditions'=>$cond,'recursive'=>-1]);
		if(!empty($j)) {
			return $j['Identifier'][$field];
		} else {
			return false;
		}
	}

	/**
	 * function to get the kingdom($type) and superclass($subtype)
	 * of a compound from ClassyFire based on InChIKey
	 * @param $key
	 * @return array|bool
	 */
	public function classy($key)
	{
		if(preg_match('/[A-Z]{14}-[A-Z]{10}-[A-Z,0-9]/',$key)) {
			$type=null;$subtype=null;
			$path='http://classyfire.wishartlab.com/entities/';
			$url=$path.$key.'.json';
			$headers=get_headers($url);
			if(stristr($headers[0],'OK')) {
				$json=file_get_contents($url);
				$classy=json_decode($json,true);
				if(!empty($classy)) {
					$kingdom=$classy['kingdom']['name'];
					if($kingdom=='Inorganic compounds') {
						$superclass=$classy['superclass']['name'];
						if($superclass=='Homogeneous metal compounds') {
							$type='element'; // elements!
						} else {
							$type='compound';$subtype='inorganic compound';
						}
					} elseif($kingdom=='Organic compounds') {
						$type='compound';$subtype='organic compound';
					}
				}
			}
			if(is_null($type)&&is_null($subtype)) {
				return false;
			} else {
				return ['type'=>$type,"subtype"=>$subtype];
			}
		} else {
			return false;
		}
	}
}
