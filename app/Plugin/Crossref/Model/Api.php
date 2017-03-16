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
    public function works($term='',$filter=[],$rows=100,$offset=0,$sort='published',$order='asc')
    {
        $HttpSocket = new HttpSocket();
        $Cite=ClassRegistry::init('Citations');
        $Bad=ClassRegistry::init('Baddois');
        $New=ClassRegistry::init('Newcites');
        $Pub=ClassRegistry::init('Publishers');
        $J=ClassRegistry::init('Journals');
        $A=ClassRegistry::init('Pauthors');

        $all=1000000;
        while($offset<$all) {
            $url=$this->path.'/works?query='.str_replace(" ","+",$term);
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
            $json=$HttpSocket->get($url);
            $papers=json_decode($json,true);

            if(!$papers['message-type']=='work-list') { return false; }
            if($all==1000000) {
                $all=$papers['message']['total-results'];
                echo "<p><b>Hits: ".$all."</b></p>";
            }
            echo "OFFSET: ".$offset."<br />";
            foreach($papers['message']['items'] as $paper) {
                //debug($paper);exit;
                $exist=$Cite->find('first',['conditions'=>['url'=>$paper['DOI']]]);
                if(!empty($exist)) { echo "Aready have....".$paper['DOI']."<br />";continue; } // Continue if already have DOI
                $baddoi=$Bad->find('first',['conditions'=>['doi'=>$paper['DOI']]]);
                if(!empty($baddoi)) { echo "Found bad DOI....".$paper['DOI']."<br />";continue; } // Continue if DOI is bad
                $new=$New->find('first',['conditions'=>['url'=>$paper['DOI']]]);
                if(!empty($new)) { echo "Found in new....".$paper['DOI']."<br />";continue; } // Continue if DOI is in new citations
                $term=str_replace("%20"," ",$term);
                if(!stristr($paper['title'][0],$term)&&!stristr($paper['title'][0],str_replace(" ","-",$term))) {
                    $Bad->create();
                    $Bad->save(['Baddois'=>['doi'=>$paper['DOI'],'title'=>$paper['title'][0]]]); // Save bad doi
                    $Bad->clear();
                    echo "Added as bad DOI....".$paper['DOI']."<br />";continue;
                }
                //debug($paper);
                $add=[];
                $add['title']=ucwords($paper['title'][0]);
                $austr=$auwebstr="";
                if(isset($paper['author'])) {
                    foreach($paper['author'] as $author) {
                        $family=ucwords(strtolower($author['family']));
                        $names=explode(" ",$author['given']);
                        $inits="";
                        foreach($names as $name) {
                            $inits.=$name[0].".";
                        }
                        $austr.=$family.", ".$inits.';';
                        $auwebstr.=$author['given']." ".$family.",";
                    }
                    $austr=substr($austr,0,-1);
                    $auwebstr=substr($auwebstr,0,-1);
                    // Primary author
                    if(count($paper['author'])==1) {
                        // Find in authors table
                        $au=$A->find('first',['conditions'=>['OR'=>[['abbrev'=>$austr],['abbrev'=>str_replace("-", " ",$austr)]]],'fields'=>['id']]);
                        if(empty($au)) {
                            // Add new pauthor
                            $A->create();
                            $pau=['name'=>$auwebstr,'abbrev'=>$austr,'first_name'=>$paper['author'][0]['given'],'lastname'=>$paper['author'][0]['family']];
                            $A->save(['Pauthors'=>$pau]);
                            $auid=$A->id;
                            $A->clear();
                        } else {
                            $auid=$au['Pauthors']['id'];
                        }
                        $add['pauthor_id']=$auid;
                        $add['pauthor']=$austr;
                    }
                }
                $add['authors']=$austr;
                $add['authorsweb']=$auwebstr;
                if(isset($paper['volume'])) { $add['volume']=ucwords($paper['volume']); }
                if(isset($paper['page'])) {
                    if (!stristr($paper['page'], "-")) {
                        list($add['startpage']) = $paper['page'];
                    } else {
                        list($add['startpage'], $add['endpage']) = explode("-", $paper['page']);
                    }
                }
                if(isset($paper['volume'])) { $add['issue']=ucwords($paper['issue']); }
                if(isset($paper['container-title'][1])) {
                    $add['journal']=ucwords($paper['container-title'][1]);
                } else {
                    $add['journal']=ucwords($paper['container-title'][0]);
                }
                $add['url']=ucwords($paper['DOI']);
                $add['urltype']='doi';
                $add['refcount']=ucwords($paper['reference-count']);
                // Check if publisher is in the system yet
                list($doiprefix,)=explode("/",$paper['DOI']);
                $pub=$Pub->find('list',['fields'=>['doiprefix','id'],'conditions'=>['doiprefix like'=>'%'.$doiprefix.'%']]);
                if(empty($pub)) {
                    // Add publisher to the system
                    echo "Adding Publisher: ".$paper['publisher']."<br />";
                    $Pub->create();
                    $Pub->save(['Publishers'=>['name'=>$paper['publisher'],'doiprefix'=>$doiprefix]]);
                    $pubid=$Pub->id;
                    $Pub->clear();
                } else {
                    $pubid=$pub[$doiprefix];
                }
                // Check if journal is in the system yet
                $jnlid="";$issn="";
                // Check ISSNs
                if(isset($paper['ISSN'])) {
                    foreach ($paper['ISSN'] as $issn) {
                        $jnl = $J->find('list', ['conditions' => ['issn' => $issn], 'fields' => ['issn', 'id']]);
                        if (!empty($jnl)) {
                            $jnlid = $jnl[$issn];
                            $issn = $jnlid;
                            break;
                        }
                    }
                }
                // Check journal title
                if($jnlid=="") {
                    if(isset($paper['container-title'][0])) {
                        $jnl=$J->find('list',['conditions'=>['name'=>$paper['container-title'][0]],'fields'=>['name','id']]);
                        if(!empty($jnl)) {
                            $jnlid=$jnl[$paper['container-title'][0]];
                        }
                    }
                }
                // Check journal abbreviation
                if($jnlid=="") {
                    if(isset($paper['container-title'][1])) {
                        $jnl=$J->find('list',['conditions'=>['abbrev'=>$paper['container-title'][1]],'fields'=>['abbrev','id']]);
                        if(!empty($jnl)) {
                            $jnlid=$jnl[$paper['container-title'][1]];
                        }
                    }
                }
                // Add journal if not found
                if($jnlid=="") {
                    echo "Adding Journal: ".$paper['container-title'][0]."<br />";
                    $J->create();
                    (isset($paper['container-title'][1])) ? $abbrev=$paper['container-title'][1] : $abbrev="";
                    $J->save(['Journals'=>['name'=>$paper['container-title'][0],'abbrev'=>$abbrev,'issn'=>$issn,'publisher_id'=>$pubid]]);
                    $jnlid=$J->id;
                    $J->clear();
                }
                $add['journal_id']=$jnlid;
                $add['year']=$paper['issued']['date-parts'][0][0];
                $add['keywords']="";
                if(isset($paper['subject'])) {
                    foreach($paper['subject'] as $sub) {
                        $add['keywords'].=$sub.";";
                    }
                }
                //debug($add);exit;
                $New->create();
                $New->save(['Newcites'=>$add]);
                $New->clear();
                echo "New....".$paper['DOI']."<br />";
                debug($add);
            }
            $offset+=100;
        }
        return true;
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