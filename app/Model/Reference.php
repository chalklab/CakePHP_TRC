<?php

/**
 * Class Reference
 * Reference model
 */
class Reference extends AppModel
{

    public $hasMany = ['Dataset','Refcode'];

    public $virtualFields=['citation'=>'CONCAT("\'",Reference.title,"\' ",Reference.authors,", ",Reference.journal," ",Reference.year," ",Reference.volume,"(",Reference.issue,") ",Reference.startpage,"-",Reference.endpage)'];

    /**
     * Get papers via Crossrefs OpenURL API
     * @param $citation
     * @return mixed
     */
    public function crossref($citation)
    {
        // Do DOI lookup via Crossref (get article title and full names of authors)
        $HttpSocket = new HttpSocket();
        $get=['pid'=>'schalk@unf.edu','noredirect'=>'true','format'=>'unixref'];
        if(isset($citation['doi'])) {
            $get['id']="doi:".$citation['doi'];
        } else {
            if($citation['authors']!="[]") {
                $authors=json_decode($citation['authors'],true);
                $get['aulast']=$authors[0]['lastname'];
            } else {
                $get['aulast']="";
            }
            if(isset($citation['journal'])&&$citation['journal']!='')		{ $get['title']=$citation['journal']; }
            if(isset($citation['volume'])&&$citation['volume']!='')		    { $get['volume']=$citation['volume']; }
            if(isset($citation['issue'])&&$citation['issue']!='')		    { $get['issue']=$citation['issue']; }
            if(isset($citation['startpage'])&&$citation['startpage']!='')	{ $get['spage']=$citation['startpage']; }
            if(isset($citation['year'])&&$citation['year']!='')		        { $get['date']=$citation['year']; }
        }

        // Go get data
        $response=$HttpSocket->get("https://doi.crossref.org/openurl",$get);
        $xml=simplexml_load_string($response['body']);
        $meta=json_decode(json_encode($xml->doi_record->crossref->journal),true);

        // Did we get a DOI hit?
        if(empty($meta)) {
            return $meta;
        } else {
            if(isset($meta['journal_article']['contributors']['person_name'])) {
                $authors=[]; // Deletes out authors obtained from citation
                $cons=$meta['journal_article']['contributors']['person_name'];
                (!isset($cons[0])) ? $aus=[$cons] : $aus=$cons;
                foreach($aus as $au) {
                    if(isset($au['given_name'])):	$authors[]=['firstname'=>$au['given_name'],'lastname'=>$au['surname']];
                    else:							$authors[]=['firstname'=>'','lastname'=>$au['surname']];
                    endif;
                }
                $citation['authors']=json_encode($authors);
            }
            if(isset($meta['journal_metadata']['abbrev_title'])) {
                if(is_array($meta['journal_metadata']['abbrev_title'])) {
                    $citation['journal']=$meta['journal_metadata']['abbrev_title'][0];
                } else {
                    $citation['journal']=$meta['journal_metadata']['abbrev_title'];
                }
            } else {
                $citation['journal']=$meta['journal_metadata']['full_title'];
            }
            if(isset($meta['journal_article']['titles'])) {
                if (isset($meta['journal_article']['titles'][0])) {
                    $title = $meta['journal_article']['titles'][0]['title'];
                    if (is_array($title)) {
                        $citation['title'] = "HTML in title";
                    } else {
                        $citation['title'] = $title;
                    }
                } else {
                    $title = $meta['journal_article']['titles']['title'];
                    if (is_array($title)) {
                        $citation['title'] = "HTML in title";
                    } else {
                        $citation['title'] = $title;
                    }
                }
            } else {
                $citation['title'] = "No title from CrossRef";
            }
            if(isset($meta['journal_issue']['journal_volume']['volume'])) {
                $citation['volume']=$meta['journal_issue']['journal_volume']['volume'];
            } else {
                $citation['volume']=null;
            }

            if(isset($meta['journal_issue']['issue'])) {
                $citation['issue']=$meta['journal_issue']['issue'];
            } else {
                $citation['issue']=null;
            }

            if(isset($meta['journal_article']['pages']['first_page'])) {
                $citation['startpage']=$meta['journal_article']['pages']['first_page'];
            } else {
                $citation['startpage']=null;
            }

            if(isset($meta['journal_article']['pages']['last_page'])) {
                $citation['endpage']=$meta['journal_article']['pages']['last_page'];
            } else {
                $citation['endpage']=null;
            }

            $citation['url']="http://dx.doi.org/".$meta['journal_article']['doi_data']['doi'];

            if(isset($meta['journal_issue']['publication_date'])) {
                if(isset($meta['journal_issue']['publication_date'][0])) {
                    foreach($meta['journal_issue']['publication_date'] as $date) {
                        if($date['@attributes']['media_type']=="print") {
                            $citation['year']=$date['year'];
                        }
                    }
                } else {
                    $citation['year']=$meta['journal_issue']['publication_date']['year'];
                }
            } else {
                if(isset($meta['journal_article']['publication_date'][0])) {
                    foreach($meta['journal_article']['publication_date'] as $date) {
                        if($date['@attributes']['media_type']=="print") {
                            $citation['year']=$date['year'];
                        }
                    }
                } else {
                    $citation['year']=$meta['journal_article']['publication_date']['year'];
                }
            }
            return $citation;
        }
    }

    /**
     * Get papers via Crossrefs API
     * @param array $citation
     * @return array $return
     */
    public function crossrefapi($citation)
    {
        $Char=ClassRegistry::init('Char');
        $options=[];$return=[];
        $citation['title']=$Char->clean($citation['title']);
        if(isset($citation['title'])) {
            $options[] = "query.title=".urlencode($citation['title']);
        }
        if(isset($citation['author'])) {
            $options[] = "query.author=".urlencode($citation['author']);
        }
        $optstr=implode("&",$options);
        $query="http://api.crossref.org/works?".$optstr;
        $json=file_get_contents($query);
        $hits=json_decode($json,true);
        foreach($hits['message']['items'] as $hit) {
            $strlen=strlen($citation['title']);
            $haystack=$Char->clean($hit['title'][0]);
            $haystack=strtolower(substr($haystack,0,$strlen));
            $needle=strtolower($citation['title']);
            if($haystack==$needle) {
                $return=$hit;
            }
        }
        // Spit out metadata ready for DB ingest
        // journal, abbrev, authors (JSON), year, volume, issue, startpage, endpage, title, url, publisher, doi, type
        if(!empty($return)) {
            $t=$return;$return=[];$aus=[];
            if(isset($t['container-title'][0])) { $return['journal']=$t['container-title'][0]; }
            if(isset($t['container-title'][1])) { $return['abbrev']=$t['container-title'][1]; }
            if(isset($t['author'])) {
                foreach($t['author'] as $au) {
                    $aus[]=["firstname"=>$au['given'],"lastname"=>$au['family']];
                }
                $return['authors']=json_encode($aus);
            }
            if(isset($t['published-print']['date-parts'][0][0])) { $return['year']=$t['published-print']['date-parts'][0][0]; }
            if(isset($t['volume']))         { $return['volume']=$t['volume']; }
            if(isset($t['issue']))          { $return['issue']=$t['issue']; }
            if(isset($t['page']))           { list($return['startpage'],$return['endpage'])=explode("-",$t['page']); }
            if(isset($t['title'][0]))       { $return['title']=$t['title'][0]; }
            if(isset($t['URL']))            { $return['url']=$t['URL']; }
            if(isset($t['publisher']))      { $return['publisher']=$t['publisher']; }
            if(isset($t['DOI']))            { $return['doi']=$t['DOI']; }
            if(isset($t['type']))           { $return['type']=$t['type']; }
        }
        return $return;
    }

    /**
     * Search Crossref via search API
     * @param $citation
     * @return array
     */
    public function crossrefsearch($citation) {
        // Experimental service - may go away...
        $query="http://search.crossref.org/dois?q=".urlencode($citation);
        $json=file_get_contents($query);
        $results=json_decode($json,true);
        $result=[];
        foreach($results as $r) {
            // Check to make sure the title is in the citation
            if(stristr($citation,$r['title'])) {
                $result=$r;
                break;
            }
        }
        return $result;
    }

    /**
     * Process citation using the frecite service a Brown
     * @param $citation
     * @return mixed
     */
    public function freecite($citation) {
        $curl = curl_init();
        $options =[
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'http://freecite.library.brown.edu/citations/create',
            CURLOPT_USERAGENT => 'ChalkLab Citation Retriever',
            CURLOPT_POSTFIELDS => ['citation' => $citation],
            CURLOPT_HTTPHEADER => ['Accept: application/xml']
        ];
        curl_setopt_array($curl, $options);
        $result = curl_exec($curl);
        curl_close($curl);
        $xml = simplexml_load_string($result,'SimpleXMLElement',LIBXML_NOERROR|LIBXML_NOENT);
        $ref=json_decode(json_encode($xml),true);
        debug($ref);exit;
        $found=[];
        if(!empty($ref)) {
            // Send to crossref OpenURL
            // firstauthor, journal, volume, startpage
            $authors=$ref['citation']['authors']['author'];$aus=[];
            if(!is_array($authors)) {
                $temp=$authors;$authors=[$temp];
            }
            foreach($authors as $au) {
                list($first,$last)=explode(" ",$au,2);
                $aus[]=['firstname'=>$first,'lastname'=>$last];
            }
            $jaus=json_encode($aus);
            $j=$ref['citation']['journal'];
            $v=$ref['citation']['volume'];
            list($sp,$ep)=explode("-",$ref['citation']['pages']);
            $citation=['authors'=>$jaus,'journal'=>$j,'volume'=>$v,'startpage'=>$sp];
            $found=$this->crossref($citation);

            // Send to crossref API
            // title and firstauthor
            if(empty($found)) {
                $title=str_replace("\n"," ",$ref['citation']['title']);
                $firstauthor=$ref['citation']['authors']['author'][0];
                $found=$this->crossrefapi(['title'=>$title,'author'=>$firstauthor]);
            }

            // OK if not found previously just take the output from freecite
            if(empty($found)) {
                $citation['title'] = $ref['citation']['title'];
                $citation['endpage'] = str_replace("-", " ", trim($ep));
                $found = $citation;
            }
        }
        return $found;
    }

    /**
     * Add reference to DB based on its DOI
     * @param $doi
     * @return array
     */
    public function addbydoi($doi)
    {
        $cite=$this->crossref(['doi'=>str_replace("http://dx.doi.org/","",$doi)]);
        $this->create();
        $ref=$this->save(["Reference"=>$cite]);
        $this->clear();
        return $ref;
    }
}