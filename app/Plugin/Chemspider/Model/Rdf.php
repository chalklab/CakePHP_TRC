<?php
App::uses('AppModel', 'Model');
App::uses('ClassRegistry', 'Utility');
App::uses('Xml','Utility');
App::uses('HttpSocket', 'Network/Http');
Configure::load('Chemspider.csprefs', 'default');

/**
 * Class RDF
 * RDF model
 */
class Rdf extends AppModel
{

    public $useTable = false;

    /**
     * Get the RDF metadata for a chemical based on name, CAS, inchi etc.
     * Format returned has CSID and Synonyms in separate parts of array
     * @param $cmpd
     * @return mixed
     */
    public function search($cmpd)
    {
        $HttpSocket = new HttpSocket();
        $url=Configure::read('rdf.search').$cmpd;
        $resp=$HttpSocket->get($url,[],['redirect'=>true]);
        $xml=$resp->body();
        if(stristr($xml,"<?xml")) {
            $meta=$this->xmlToArray($xml);
            if(isset($meta['RDF'])) {
                $return=[];
                if(isset($meta['RDF']['foaf:Image'])) {
                    list(,$return['id'])=explode("=",$meta['RDF']['foaf:Image']['@rdf:about']);
                }
                if(isset($meta['RDF']['chemdomain:InChI'])) {
                    $return['inchi']=$meta['RDF']['chemdomain:InChI']['chemdomain:hasValue'];
                }
                if(isset($meta['RDF']['chemdomain:InChIKey'])) {
                    $return['inchikey']=$meta['RDF']['chemdomain:InChIKey']['chemdomain:hasValue'];
                }
                if(isset($meta['RDF']['chemdomain:SMILES'])) {
                    $return['smiles']=$meta['RDF']['chemdomain:SMILES']['chemdomain:hasValue'];
                }
                if(isset($meta['RDF']['chemdomain:Synonym'])) {
                    $return['name']=$meta['RDF']['chemdomain:Synonym']['chemdomain:hasValue'];
                }
                if(isset($meta['RDF']['chemdomain:MolecularFormula'])) {
                    $return['formula']=$meta['RDF']['chemdomain:MolecularFormula']['chemdomain:hasValue'];
                }
                return $return;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Convert XML response into array
     * @param $response
     * @return array
     */
    private function xmlToArray($response)
    {
        $array = Xml::toArray(Xml::build($response));
        return $array;
    }

}