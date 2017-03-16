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
            $url="http://cactus.nci.nih.gov/chemical/structure/".rawurlencode($id)."/names/xml";
            $xmlfile =$HttpSocket->get($url);
            $xml = simplexml_load_string($xmlfile,'SimpleXMLElement',LIBXML_NOERROR|LIBXML_NOENT);
            $output=json_decode(json_encode($xml),true);
            return $output['data']['item'];
        } else {
            return false;
        }
    }

}