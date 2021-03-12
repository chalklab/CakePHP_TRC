<?php

/**
 * Class Identifier
 */
class NewIdentifier extends AppModel {

	public $useDbConfig='new';
	public $useTable='identifiers';

	public $belongsTo=[
    	'NewSubstance'=> [
			'foreignKey' => 'substance_id',
			'dependent' => true
		]
	];

    /**
     * General function to add a new identifier
     * @param array $data
     * @return integer
	 * @throws
     */
    public function add($data)
    {
        $model='NewIdentifier';
        $this->create();
        $ret=$this->save([$model=>$data]);
        $this->clear();
        return $ret[$model];
    }

    /**
     * Search CIR for identifiers
     * @param string $type
     * @param $id
     * @return bool
     */
    public function cir($type="name",$id)
    {
        $HttpSocket = new HttpSocket();
        if($type=="name"||$type=="cas") {
            $url="https://cactus.nci.nih.gov/chemical/structure/".rawurlencode($id)."/names/xml";
            $xmlfile =$HttpSocket->get($url);
            $xml = simplexml_load_string($xmlfile,'SimpleXMLElement',LIBXML_NOERROR|LIBXML_NOENT);
            $output=json_decode(json_encode($xml),true);
            //debug($output);exit;
            if(isset($output['data'][0])) {
				return $output['data'][0]['item'];
			} elseif(isset($output['data']['item'])) {
				return $output['data']['item'];
			} else {
				return false;
			}
        } else {
            return false;
        }
    }

	/**
	 * Find compound by CASRN on CIR
	 * @param $name
	 * @return bool|mixed
	 */
    public function getcircas($name)
	{
		$syns=$this->cir("cas",$name);
		if($syns) {
			$cas=[];
			if(!is_array($syns)) {
				$syns=[0=>$syns];
			}
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
		} else {
			return false;
		}
	}

	/**
	 * Get the value of an identifier field
	 * @param $field
	 * @param $cond
	 * @return bool
	 */
	public function getfield($field,$cond)
	{
		$j=$this->find('first',['conditions'=>$cond,'recursive'=>-1]);
		if(!empty($j)) {
			return $j['Identifier'][$field];
		} else {
			return false;
		}
	}

	/**
	 * Function to get the kingdom($type) and superclass($subtype)
	 * of a compound from ClassyFire based on InChIKey
	 * @param $key
	 * @return array|bool
	 */
	public function classy($key)
	{
		if(preg_match('/[A-Z]{14}-[A-Z]{10}-[A-Z,0-9]/',$key)) {
			$type=$subtype="";
			$path='http://classyfire.wishartlab.com/entities/';
			$headers=get_headers($path.$key.'.json');
			if(stristr($headers[0],'OK')) {
				$json=file_get_contents($path.$key.'.json');
				$classy=json_decode($json,true);
				if(!empty($classy)) {
					$kingdom=$classy['kingdom']['name'];
					$type=null;$subtype=null;
					if($kingdom=='Inorganic compounds') {
						$superclass=$classy['superclass']['name'];
						if($superclass=='Homogeneous metal compounds') {
							$type='element';$subtype='';// elements!
						} else {
							$type='compound';$subtype='inorganic compound';
						}
					} elseif($kingdom=='Organic compounds') {
						$type='compound';$subtype='organic compound';
					}
				} else {
					$type='compound';$subtype='not found on classyfire';
				}
			} else {
				$type='compound';$subtype='organic compound*';
			}
			if($type==""&&$subtype=="") {
				return false;
			} else {
				return ['type'=>$type,"subtype"=>$subtype];
			}
		} else {
			return false;
		}
	}
}
