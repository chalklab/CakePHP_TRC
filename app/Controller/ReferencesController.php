<?php
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

/**
 * Class ReferencesController
 * Actions related to reports
 * @author Stuart Chalk <schalk@unf.edu>
 */
class ReferencesController extends AppController
{

	public $uses=['Reference','Crossref','Phaseone','File','Dataset','Refcode','DataSystem','Property'];

	/**
	 * beforeFilter function
	 */
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Auth->allow();
	}
	
	public function index($term=null)
	{
		$query="SELECT distinct t1.id,t1.title,t3.name FROM `references` t1 left join data_systems t2 on t1.id=t2.reference_id left join properties t3 on t2.property_id=t3.id";
		if(is_null($term)) {
			$results=$this->Reference->query($query);
		} else {
            $results=$this->Reference->query($query." where t1.title like '%".$term."%'");
		}
		//debug($results);exit;
		$data=[];
		foreach($results as $result) {
		    $prop=$result['t3']['name'];
		    $refid=$result['t1']['id'];
		    $title=$result['t1']['title'];
		    if(!in_array($prop,$data)) {
		        $data[$prop]=[];
            }
            $data[$prop][]=[$refid=>$title];
        }
		$this->set('data',$data);
	}
	
    /**
     * View a reference
     * @param $id
     */
	public function view($id)
    {
        $data=$this->Reference->find('first',['conditions'=>['Reference.id'=>$id],'contain'=>['Dataset','Refcode'],'recursive'=>-1]);
        $this->set('data',$data);
    }

    /**
     * Multiple file ingest from folder
     * @param $folder
     * @param $pub
     */
	public function mingest($folder,$pub)
    {
        $dir=new Folder(WWW_ROOT."/files/refs/".$folder);
        $files = $dir->find('.*\.xml');
        foreach($files as $file) {
            $filename=str_replace(".xml","",$file);
            $chunks=explode("_",$filename);
            $this->ingest($folder."/".$filename,$pub,$chunks[2],"return");
        }
        exit;
    }

	/**
     * Ingest XML Reference file
	 * @param $filename
     * @param $type
     * @param $pub
     * @param $chapter
     * @param $end
	 */
	public function ingest($filename,$type,$pub,$chapter,$end="exit")
	{
		if($type=="xml") {
            $xml=simplexml_load_file(WWW_ROOT.'/files/refs/'.$filename.'.xml');
            $refs=$xml->xpath('//Citation');
            $data=[];
            foreach($refs as $ref) {
                //debug($ref);//exit;
                $reference=$refcode=[];
                $refcode['publication_id']=$pub;
                $refcode['chapter']=$chapter;
                $refcode['citeid']=(string) $ref['ID']; // Goes in Refcodes table
                $refcode['code']=(string) $ref->CitationNumber; // Goes in Refcodes table
                if($ref->BibArticle->Occurrence['Type']=="DOI") {
                    //debug($ref);
                    $doi=(string) $ref->BibArticle->Occurrence->Handle;
                    $reference=$this->Reference->crossref(['doi'=>$doi]);
                    $reference['url']="http://dx.doi.org/".$reference['doi'];
                    unset($reference['doi']);
                } else {
                    $reference['authors']=[];
                    $authors=$ref->BibArticle->BibAuthorName;
                    if(!empty($authors)) {
                        foreach($authors as $author) {
                            $reference['authors'][]=["firstname"=>(string) $author->Initials,"lastname"=>(string) $author->FamilyName];
                        }
                    }
                    $reference['authors']=json_encode($reference['authors']);
                    if(isset($ref->BibArticle->Year)) { $reference['year']=(string) $ref->BibArticle->Year; }
                    $reference['oldjournal']=(string) $ref->BibArticle->JournalTitle;
                    $reference['journal']=trim(str_replace(".",". ",$reference['oldjournal']));
                    $reference['volume']=(string) $ref->BibArticle->VolumeID;
                    $reference['startpage']=(string) $ref->BibArticle->FirstPage;
                    $reference['bibliography']=(string) $ref->BibUnstructured;
                    $reference['bibliography']=str_replace(["\t","\n"],[""," "],$reference['bibliography']);
                    $reference['bibliography']=str_replace($reference['oldjournal'],$reference['journal'],$reference['bibliography']);
                    unset($reference['oldjournal']);
                    // See if its in crossref...
                    $meta=$this->Reference->crossref($reference);
                    if(!empty($meta)) {
                        // If there is metadata from the Crossref search substitute it for what we started with
                        $reference=$meta;
                        $reference['url']="http://dx.doi.org/".$meta['doi'];
                        unset($reference['doi']);
                        //debug($reference);exit;
                    } else {
                        $reference['url']="no";
                    }
                }

                // See if the refcode has been saved before...
                $result=$this->Refcode->find('first',['conditions'=>['publication_id'=>$pub,'chapter'=>$chapter,'citeid'=>$refcode['citeid']]]);
                if(empty($result)) {
                    // Clean data
                    if($reference['authors']=="[]") { $reference['authors']=null; }
                    if($reference['journal']=="") { $reference['journal']=null; }
                    if($reference['startpage']=="") { $reference['startpage']=null; }
                    if($reference['volume']=="") { $reference['volume']=null; }
                    if(!isset($reference['year'])||$reference['year']==0) { $reference['year']=null; }
                    // Save data
                    // Check to see if the reference already exists in the DB
                    $found="";
                    if($reference['url']!="no") {
                        $found=$this->Reference->find('first',['conditions'=>['url'=>$reference['url']]]);
                    } elseif(!is_null($reference['journal'])&&!is_null($reference['volume'])&&!is_null($reference['year']&&!is_null($reference['startpage']))) {
                        $found=$this->Reference->find('first',[
                            'conditions'=>['journal'=>$reference['journal'],'volume'=>$reference['volume'],'year'=>$reference['year'],'startpage'=>$reference['startpage']]]);
                    } else {
                        $found=$this->Reference->find('first',['conditions'=>['bibliography'=>$reference['bibliography']]]);
                    }
                    if(empty($found)) {
                        // Add new ref as not found in DB
                        //debug($reference);exit;
                        $this->Reference->create();
                        $this->Reference->save(['Reference'=>$reference]);
                        $refid=$this->Reference->id;
                        $this->Reference->clear();
                        $refcode['reference_id']=$refid;
                    } else {
                        $refcode['reference_id']=$found['Reference']['id'];
                    }
                    if(stristr($refcode['chapter'],",")) {
                        $chapters=explode(",",$refcode['chapter']);
                        foreach ($chapters as $c) {
                            $refcode['chapter']=$c;
                            $this->Refcode->create();
                            $this->Refcode->save(['Refcode' => $refcode]);
                            $this->Refcode->clear();
                        }
                    } else {
                        $this->Refcode->create();
                        $this->Refcode->save(['Refcode' => $refcode]);
                        $this->Refcode->clear();
                    }
                    echo 'TRC citation number '.$refcode['code']." ingested (reference ".$refcode['reference_id'].")<br />";
                } else {
                    echo $refcode['code']." already exists in DB<br />";
                }
            }
        } elseif($type=="txt") {
            // Data for refcode table
            $reference=$refcode=[];
            $refcode['publication_id']=$pub;
            $refcode['chapter']=$chapter;
            $refcode['citeid']=null; // Goes in Refcodes table
            $lines = file(WWW_ROOT . '/files/refs/' . $filename . '.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $refs=[];$i=0;
            // Chunk lines based on refcode
            foreach($lines as $line) {
                if(preg_match("/^[A-Z][0-9]{3}$/",$line)) {
                    $i++;
                    $refs[$i]['refcode']=$line;
                } else {
                    list($field,$value)=explode(":",$line);
                    $refs[$i][strtolower($field)]=trim($value);
                }
            }
            // Convert authors to JSON
            foreach($refs as $key=>$ref) {
                $aus=$ref['authors'];
                $aus=str_replace(" and ",",",$aus);
                $aus=str_replace(". ",".,",$aus);
                $aus=str_replace(",,",",",$aus);
                $authors=explode(",",$aus);
                foreach($authors as $i=>$value) {
                    $authors[$i]=trim($value);
                }
                // Switch first author
                $temp=$authors[0];
                $authors[0]=$authors[1];
                $authors[1]=$temp;
                // Create array of authors and JSON
                $numau=count($authors)/2;
                $aarray=[];
                for($a=0;$a<$numau;$a++) {
                    $i=($a*2);
                    $aarray[$a]=['lastname'=>$authors[($i+1)],'firstname'=>$authors[$i]];
                }
                $refs[$key]['authors']=json_encode($aarray);
            }
            // OK, process references (add to or find in references table and add to refcodes)
            foreach($refs as $ref) {
                // Get the DOI of paper if it exists
                if(stristr($ref['pages'],"-")) {
                    list($ref['startpage'],$ref['endpage'])=explode("-",$ref['pages']);
                } else {
                    $ref['startpage']=$ref['pages'];
                }
                $meta=$this->Reference->crossref($ref);
                if(!empty($meta)) {
                    // If there is metadata from the Crossref search substitute it for what we started with
                    $ref=$meta;
                    $ref['url']="http://dx.doi.org/".$meta['doi'];
                    unset($ref['doi']);
                } else {
                    $ref['url']="no";
                }

                // See if the refcode has been saved before...
                $result=$this->Refcode->find('first',['conditions'=>['publication_id'=>$pub,'chapter'=>$chapter,'code'=>$ref['refcode']]]);
                if(empty($result)) {
                    // Clean data
                    if($ref['authors']=="[]") { $ref['authors']=null; }
                    if($ref['journal']=="") { $ref['journal']=null; }
                    if($ref['startpage']=="") { $ref['startpage']=null; }
                    if($ref['volume']=="") { $ref['volume']=null; }
                    if(!isset($ref['year'])||$ref['year']==0) { $reference['year']=null; }
                    // Save data
                    // Check to see if the reference already exists in the DB
                    $found="";
                    if($ref['url']!="no") {
                        $found=$this->Reference->find('first',['conditions'=>['url'=>$ref['url']]]);
                    } elseif(!is_null($ref['journal'])&&!is_null($ref['volume'])&&!is_null($ref['year']&&!is_null($ref['startpage']))) {
                        $found=$this->Reference->find('first',[
                            'conditions'=>['journal'=>$ref['journal'],'volume'=>$ref['volume'],'year'=>$ref['year'],'startpage'=>$ref['startpage']]]);
                    } elseif(isset($ref['bibliography'])) {
                        $found=$this->Reference->find('first',['conditions'=>['bibliography'=>$ref['bibliography']]]);
                    }
                    if(empty($found)) {
                        // Add new ref as not found in DB
                        //debug($reference);exit;
                        $this->Reference->create();
                        $this->Reference->save(['Reference'=>$ref]);
                        $refid=$this->Reference->id;
                        $this->Reference->clear();
                        $refcode['reference_id']=$refid;
                    } else {
                        $refcode['reference_id']=$found['Reference']['id'];
                    }
                    $refcode['code']=$ref['refcode'];
                    //debug($ref);debug($refcode);exit;
                    if(stristr($refcode['chapter'],",")) {
                        $chapters=explode(",",$refcode['chapter']);
                        foreach ($chapters as $c) {
                            $refcode['chapter']=$c;
                            $this->Refcode->create();
                            $this->Refcode->save(['Refcode' => $refcode]);
                            $this->Refcode->clear();
                        }
                    } else {
                        $this->Refcode->create();
                        $this->Refcode->save(['Refcode' => $refcode]);
                        $this->Refcode->clear();
                    }
                    echo 'Citation number '.$refcode['code']." ingested (reference ".$refcode['reference_id'].")<br />";
                } else {
                    echo $ref['refcode']." already exists in DB<br />";
                }
            }
        }
        echo "Reference list imported<br />&nbsp;<br />";
		if($end=="exit") {
            exit;
        } else {
            return;
        }
	}

	/**
     * Get fielded data from the http://freecite.library.brown.edu service
	 * @param $fileID
	 */
	public function extract($fileID){
        if(isset($fileID)) {
            $file = $this->File->find('first',
                [
                    'conditions' =>
                        ['File.id' => $fileID],
                    'contain' =>[
                        'TextFile' => [
                            'order' => 'TextFile.updated DESC',
                            'limit' => 1
                        ]
                    ]
                ]); //get the file of interest
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $pdfToTextPath = Configure::read("pdftotextPath.windows"); //save path to the pdftotext for the server
            }elseif (PHP_OS=="Linux") {
                $pdfToTextPath=Configure::read("pdftotextPath.linux");
            }elseif (PHP_OS=="FreeBSD") {
                $pdfToTextPath=Configure::read("pdftotextPath.freebsd");
            }else{
                $pdfToTextPath=Configure::read("pdftotextPath.mac");
            }
            $fileToExtract=WWW_ROOT.'files'.DS.'pdf'.DS.$file['File']['publication_id'].DS.$file['File']['filename'];// find the path to the file name

            exec($pdfToTextPath.' -layout -r 300  "'. $fileToExtract.'" -',$lines); //run the extraction
            $start=false;
            $data=json_decode($file['TextFile'][0]['extracted_data'],true);
            var_dump($data['citation']);
            $citation="";
            foreach($lines as $line) {
                if(strpos($line,$data['citation'])!==false){
                    $start=true;
                }
                if($start==true){
                    if($line!=="") {
                        $citation.=$line." ";
                    } else {
                        break;
                    }
                }
            }
            var_dump($citation);
            $client = new SoapClient("http://wing.comp.nus.edu.sg/parsCit/wing.nus.wsdl");
            $str=$client->extract_citations($citation);
            echo "<pre>".$str."</pre>";
            die();
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'http://freecite.library.brown.edu/citations/create',
                CURLOPT_USERAGENT => 'ChalkLab Citation Retriever',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => ['citation' => $citation],
                CURLOPT_HEADER=>'Accept: text/xml'

            ));
            $result = curl_exec($curl);
            curl_close($curl);
            echo $result;
            die();
        }

    }

	/**
	 * Go snag DOI's from Crossref
	 */
	public function getdois()
	{
		$refs=$this->Reference->find('all',['conditions'=>['id >'=>5000],'limit'=>4830]);
		foreach($refs as $ref) {
			$response=$this->Reference->crossref($ref['Reference']);
			if($response['crossref']=='yes') {
				$this->Reference->save(['Reference'=>$response]);
				echo $response['sid']." updated<br />";
			} else {
				echo $response['sid']." not found<br />";
			}
		}
		exit;
	}

    /**
     * Count how many times a reference is cited
     * TODO - Implement using counterCahce in CakePHP
     * http://book.cakephp.org/2.0/en/models/associations-linking-models-together.html#countercache-cache-your-count
     */
	public function totals()
	{
		$c=['Dataset'];
		$refs=$this->Reference->find('list',['fields'=>['id','count'],'order'=>['count DESC'],'conditions'=>['count'=>0]]);
		//debug($refs);exit;
		foreach($refs as $rid=>$cnt) {
			echo $rid.":".$cnt."<br/>";
			$sets=$this->Dataset->find('list',['fields'=>['id','reference_id'],'conditions'=>['reference_id'=>$rid]]);
			if(count($sets)!=$cnt) {
				echo "->  From ".$cnt." to ".count($sets)."<br/>";
				$this->Reference->id=$rid;
				$this->Reference->saveField('count',count($sets));
				$this->Reference->clear;

			}
		}
		exit;
	}

	public function phaseone()
    {
        $refs=$this->Phaseone->find('all',['conditions'=>['journal'=>null,'title like'=>"%J. Chem. Soc.%"]]);
        foreach($refs as $ref) {
            $r=$ref['Phaseone'];
            $result=$this->Phaseone->crossrefapi($r);
            $result['crossref']='yes';
            $this->Phaseone->id=$r['id'];
            $this->Phaseone->save(['Phaseone'=>$result]);
            echo "Reference ".$r['id']." updated<br />";
        }
        exit;
    }

 }