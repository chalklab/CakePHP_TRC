<?php
App::uses('AppModel', 'Model');
App::uses('ClassRegistry', 'Utility');
App::uses('HttpSocket', 'Network/Http');
Configure::load('Pubchem.pugrest');

/**
 * Model Class Compound
 */
class Compound extends AppModel
{

    public $path="https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/";

    public $useTable = false;

    /**
     * Get the PubChem CID for chemical based on name or CAS search of names
     * You can use names, ids, cas# etc...
     * Format returned has CID and Synonyms in separate parts of array
     * @param string $type
     * @param string $value
     * @return bool
     */
    public function cid(string $type="name", string $value=""): bool
    {
        $HttpSocket = new HttpSocket();
		if($type=="cas"||$type=='casrn') { $type="name"; }
		$nss=Configure::read('compound.namespaces');
        if(in_array($type,$nss)) {
            if($type=="inchi") {
                $url=$this->path.$type.'/cids/JSON'; // value is in post data
			} elseif($type=="formula") {
				$url=$this->path.'fastformula/'.rawurlencode($value).'/cids/JSON';
			} else {
                $url=$this->path.$type.'/'.str_replace(' ','%20',$value).'/cids/JSON';
            }
			//debug($url);
        } else {
            return false;
        }
		if($type=="inchi") {
            $json=$HttpSocket->post($url,['inchi'=>$value]); // requires post
        } else {
            $json=$HttpSocket->get($url);
        }
		$cid=json_decode($json,true);

		if(isset($cid['Fault'])) {
            return false;
        } else {
            return $cid['IdentifierList']['CID'][0]; // get first CID in list
        }
    }

	/**
	 * get all the data for a particular compound via its cid
	 * @param int $cid
	 * @return array
	 */
    public function allcid(int $cid=0): array
	{
		$HttpSocket = new HttpSocket();
		$url=$this->path.'cid/'.$cid.'/json';
		$json=$HttpSocket->get($url);
		$data=json_decode($json,true);
		$cmpd=$data['PC_Compounds'][0];
		$props=$cmpd['props'];
		//debug($props);exit;
		$output=[];
		foreach($props as $prop) {
			if($prop['urn']['label']=='IUPAC Name'&&$prop['urn']['name']=='Preferred') {
				$output['iupacname']=$prop['value']['sval'];
			} elseif($prop['urn']['label']=='Mass') {
				$output['monomass']=$prop['value']['sval'];
			} elseif($prop['urn']['label']=='Weight') {
				$output['exactmass']=$prop['value']['sval'];
			} elseif($prop['urn']['label']=='Molecular Weight') {
				$output['mw']=$prop['value']['fval'];
			} elseif($prop['urn']['label']=='Molecular Formula') {
				$output['formula']=$prop['value']['sval'];
			} elseif($prop['urn']['label']=='SMILES'&&$prop['urn']['name']=='Canonical') {
				$output['csmiles']=$prop['value']['sval'];
			} elseif($prop['urn']['label']=='SMILES'&&$prop['urn']['name']=='Isomeric') {
				$output['ismiles']=$prop['value']['sval'];
			} elseif($prop['urn']['label']=='InChI') {
				$output['inchi']=$prop['value']['sval'];
			}
		}
		return $output;
	}

    /**
     * Get a property of a chemical
     * List of proprties available at
     * http://pubchem.ncbi.nlm.nih.gov/pug_rest/PUG_REST.html#_Toc409516770
     * @param $props
     * @param $cid
     * @return mixed
     */
    public function property($props,$cid) {
        $HttpSocket = new HttpSocket();
        $ps=Configure::read('compound.props');
		if(stristr($props,",")) {
			$props2=explode(",",$props);
			foreach($props2 as $idx=>$prop) {
				if(!in_array($prop,$ps)) {
					unset($props2[$idx]);
				}
			}
			$props=implode(',',$props2);
			$url=$this->path.'cid/'.rawurlencode($cid).'/property/'.$props.'/JSON';
		} else {
			if (in_array($props, $ps)) {
				if ($props == 'synonyms') {
					$url = $this->path . 'cid/' . rawurlencode($cid) . '/synonyms/JSON';
				} else {
					$url = $this->path . 'cid/' . rawurlencode($cid) . '/property/' . $props . '/JSON';
				}
			} else {
				return [];
			}
		}
		//echo $url."<br />";
        $json=$HttpSocket->get($url);
        $meta=json_decode($json['body'],true);
        if(isset($meta['Fault'])) {
            return false;
        } else {
            //debug($meta);
            if($props=='synonyms') {
                return implode("|",$meta['InformationList']['Information'][0]['Synonym']);
            } else {
                return $meta['PropertyTable']['Properties'][0];
            }
        }
    }

    /**
     * Check for a
     * @param string $name
     * @param string $cas
     * @return mixed
     */
    public function check(string $name, string $cas="")
    {
        // Get CID if exists by checking name then CAS
        $cid=$this->cid("name",$name);
        if($cid==false) {
            $cid=$this->cid("cas",$cas);
            if($cid==false) {
                return false;
            }
        }
        // Get property data
        $props="MolecularFormula,MolecularWeight,CanonicalSMILES,InChI,InChIKey,IUPACName";
        return $this->property($props,$cid);
    }

    /**
     * Get CAS # from synonyms
     * @param $name
     * @return mixed
     */
    public function getcas($name) {
    	if(preg_match('/[A-Z]{14}-[A-Z]{10}-[A-Z]/',$name)) {
			$cid=$this->cid("inchikey",$name);
		} else {
			$cid=$this->cid("name",$name);
		}
        $url=$this->path.'cid/'.$cid.'/synonyms/JSON';
		$HttpSocket = new HttpSocket();
        $json=$HttpSocket->get($url);
		$meta=json_decode($json['body'],true);
		$cas=[];
        if(isset($meta['InformationList'])) {
            $syns=$meta['InformationList']['Information'][0]['Synonym'];
            foreach($syns as $syn) {
                if(preg_match('/[0-9]{2,7}-[0-9]{2}-[0-9]/i',$syn)) {
                    $cas[]=$syn;
                }
            }
        }
        if(!empty($cas)) {
        	if(count($cas)==1) {
				return $cas[0];
			} else {
				// if multiple cas #'s the shortest string is the most likely the general one
                asort($cas);
				$lengths=array_map('strlen',$cas);
				$min=min($lengths);
				foreach ($cas as $str) {
					if(strlen($str)==$min) {
						return $str;
					}
				}
			}
		}
		return false;
    }

	/**
	 * get the molecular weight of a compound from its formula
	 * uses the fastformula function in PUG-REST
	 * see: https://pubchemdocs.ncbi.nlm.nih.gov/pug-rest$_Toc494865584
	 * https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/fastformula/C6H12O/cids/json
	 * @param string $formula
	 * @return mixed
	 */
	public function getmw(string $formula='')
	{
		$HttpSocket = new HttpSocket();
		$path=$this->path.'fastformula/';$mw=null;
		if($formula!='') {
			$url=$path.$formula.'/cids/json';
			$json=$HttpSocket->get($url);
			$meta=json_decode($json['body'],true);
			// pick the first CID to get the molecular weight from
			if(isset($meta['IdentifierList']['CID'][0])) {
				$cid=$meta['IdentifierList']['CID'][0];
				$response=$this->property('MolecularWeight',$cid);
				$mw=$response['MolecularWeight'];
			}
		}
		return $mw;
	}
}
