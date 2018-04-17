<?php
App::uses('AppModel', 'Model');
App::uses('ClassRegistry', 'Utility');
App::uses('HttpSocket','Network/Http');

/**
 * Class API
 * Crossref model
 * see http://api.crossref.org
 */
class Api extends CrossrefAppModel
{

    public $path="http://api.crossref.org";

    public $useTable = false;

    /**
     * Get metadata for a resource with a specific DOI
     * @param $doi
     * @return array
     */
    public function getmeta($doi) {
        $data=file_get_contents($this->path.'/works/'.$doi);
        return json_decode($data,true);
    }

    /**
     * Find articles
     * @param $term
     * @param $filter
     * @param $rows
     * @param $offset
     * @param $sort
     * @param $order
     * @return bool
     */
    public function works($term='',$filter=[],$rows=150,$offset=0,$sort='score',$order='asc')
    {
        $HttpSocket = new HttpSocket();
        $Cite=ClassRegistry::init('Citations');
        $Bad=ClassRegistry::init('Baddois');
        $New=ClassRegistry::init('Newcites');
        $Pub=ClassRegistry::init('Publishers');
        $J=ClassRegistry::init('Journals');
        $A=ClassRegistry::init('Pauthors');
	
		$url=$this->path.'/works?query.bibliographic='.str_replace(" ","+",$term);
		if(!empty($filter)) {
			$url.='&filter=';
			$c=1;
			foreach($filter as $n=>$v) {
				if($n=='publisher') { $n='publisher-name'; }
				if($n=='date') {
					$url.='from-pub-date:'.$v.',until-pub-date:'.$v;
				} else {
					$url.=$n.':'.str_replace(" ","+",$v);
				}
				if($c<count($filter)) { $url.=','; }
				$c++;
			}
		}
		$url.='&rows='.$rows;
		$url.='&offset='.$offset;
		$url.='&sort='.$sort;
		$url.='&order='.$order;
		//echo $url;exit;
		$json=$HttpSocket->get($url);
		$papers=json_decode($json,true);
	
		if(!$papers['message-type']=='work-list') {
			return false;
		} else {
			return $papers['message'];
		}
	}

    /**
     * Find journals
     * @param $field
     * @param $value
     * @return array
     */
    public function journal($field="issn",$value="")
    {
        $HttpSocket = new HttpSocket();

        $url="";
        if($field=="issn") {
            $url=$this->path.'/journals/'.$value;
        } elseif ($field=="name") {
            $url=$this->path.'/journals?query='.str_replace(" ","+",$value);
        }
        $json=$HttpSocket->get($url);
        $j=json_decode($json,true);
        return $j['message']['items'][0];
    }

    /**
     * Search the CrossRef text and data mining site
     * @param $doi
     */
    public function tdm($doi)
    {
        // DOI is $prefix/$id as Cake thinks the slash indicates another variable for the action
        $url="http://dx.doi.org/".$doi;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.crossref.unixsd+xml']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $rawxml=curl_exec($ch);
        curl_close($ch);
        $xml=simplexml_load_string($rawxml);
        echo "<pre>";print_r(json_decode(json_encode($xml),true));echo "</pre>";exit;

    }

    
    
}