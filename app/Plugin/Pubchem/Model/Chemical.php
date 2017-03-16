<?php
App::uses('AppModel', 'Model');
App::uses('ClassRegistry', 'Utility');
App::uses('HttpSocket', 'Network/Http');
Configure::load('Pubchem.pugrest', 'default');

/**
 * Class Chemical
 * Chemical model
 */
class Chemical extends AppModel
{

    public $path="http://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/";

    public $useTable = false;

    /**
     * Get the PubChem CID for chemical based on name or CAS search of names
     * You can use names, ids, cas# etc...
     * Format returned has CID and Synonyms in separate parts of array
     * @param $type
     * @param $value
     * @return bool
     */
    public function cid($type="name",$value="")
    {
        $HttpSocket = new HttpSocket();
        $nss=Configure::read('compound.namespaces');
        if(in_array($type,$nss)) {
            if($type=="inchi") {
                $url=$this->path.$type.'/cids/JSON'; // value is in post data
            } else {
                $url=$this->path.$type.'/'.rawurlencode($value).'/cids/JSON';
            }
        } else {
            return false;
        }
        //echo $url;
        if($type=="inchi") {
            $json=$HttpSocket->post($url,['inchi'=>$value]); // requires post
        } else {
            $json=$HttpSocket->get($url);
        }
        $cid=json_decode($json,true);
        //echo "<h3>".$value."</h3>";
        //debug($cid);
        if(isset($cid['Fault'])) {
            return false;
        } else {
            return $cid['IdentifierList']['CID'][0];
        }
    }

    /**
     * Get a property of a chemical
     * List of proprties available at
     * http://pubchem.ncbi.nlm.nih.gov/pug_rest/PUG_REST.html#_Toc409516770
     * @param $props
     * @param $cid
     * @return array
     */
    public function property($props,$cid) {
        $HttpSocket = new HttpSocket();
        $ps=Configure::read('compound.props');
        if(in_array($props,$ps)) {
            if($props=='synonyms') {
                $url=$this->path.'cid/'.rawurlencode($cid).'/synonyms/JSON';
            } else {
                $url=$this->path.'cid/'.rawurlencode($cid).'/property/'.$props.'/JSON';
            }
        } else {
            return false;
        }
        //echo $url."<br />";
        $json=$HttpSocket->get($url);
        $meta=json_decode($json['body'],true);
        if(isset($meta['Fault'])) {
            return false;
        } else {
            //debug($meta);
            if($props=='synonyms') {
                return implode(", ",$meta['InformationList']['Information'][0]['Synonym']);
            } else {
                return $meta['PropertyTable']['Properties'][0];
            }
        }
    }

    /**
     * Check for a
     * @param $name
     * @param $cas
     * @return mixed
     */
    public function check($name,$cas="")
    {
        // Get CID if exists by checking name then CAS
        $cid=$this->cid($name);
        if($cid==false) {
            $cid=$this->cid($cas);
            if($cid==false) {
                return false;
            }
        }
        //echo $cid;exit;
        // Get property data
        $props="MolecularFormula,MolecularWeight,CanonicalSMILES,InChI,InChIKey,IUPACName";
        return $this->property($props,$cid);
    }
}