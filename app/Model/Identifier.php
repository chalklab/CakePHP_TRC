<?php

/**
 * Class Identifier
 */
class Identifier extends AppModel {

    public $belongsTo=['Substance'];

    /**
     * General function to add a new identifier
     * @param array $data
     * @return integer
	 * @throws
     */
    public function add($data)
    {
        $model='Identifier';
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
	
	public function getfield($field,$cond)
	{
		$j=$this->find('first',['conditions'=>$cond,'recursive'=>-1]);
		if(!empty($j)) {
			return $j['Identifier'][$field];
		} else {
			return false;
		}
	}
}