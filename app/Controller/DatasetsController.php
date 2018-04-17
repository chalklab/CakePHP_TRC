<?php

/**
 * Class DatasetsController
 */
class DatasetsController extends AppController
{
    public $uses=['Dataset','Journal','Report','Quantity','Dataseries','Parameter','Variable','Substance',
		'File','Reference','Unit'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * View a data set
     * @param integer $id
     * @return mixed
     */
    public function view($id)
    {
        $c=['Annotation',
            'Dataseries'=>[
                'Condition'=>['Unit', 'Property', 'Annotation'],
                'Setting'=>['Unit', 'Property'],
                'Datapoint'=>[
                    'Annotation',
                    'Condition'=>['Unit', 'Property'],
                    'Data'=>['Unit', 'Property','Sampleprop'],
					'Setting'=>['Unit', 'Property'],
                    'SupplementalData'=>['Metadata', 'Unit', 'Property']
                ],
				'Annotation'
            ],
			'File'=>['Chemical'=>['fields'=>['formula','orgnum','source','substance_id']]],
			'Reactionprop',
			'Reference'=>['Journal'],
        	'Sampleprop',
            'System'=>[
            	'Substance'=>[
            		'Identifier'=>['fields'=>['type','value']]
				]
			]
        ];
        $dump=$this->Dataset->find('first',['conditions'=>['Dataset.id'=>$id],'contain'=>$c,'recursive'=>-1]);
        $this->set('dump',$dump);
        $fid=$dump['Dataset']['file_id'];
        // Get a list of datsets that come from the same file
        $related=$this->Dataset->find('list',['conditions'=>['Dataset.file_id'=>$fid,'NOT'=>['Dataset.id'=>$id]],'recursive'=>1]);
        $this->set('related',$related);
        $this->set('dsid',$id);
        if($this->request->is('ajax')) {
            $title=$dump['Dataset']['title'];
            echo '{ "title" : "'.$title.'" }';exit;
        }
    }

    /**
     * View index of data sets
     */
    public function index()
    {
        $c=['File'=>['fields'=>['id','title'],'order'=>['title'],'Dataset'=>['fields'=>['id','title'],'order'=>'title']]];
        $data=$this->Journal->find('all',['fields'=>['id','name'],'order'=>['name'],'contain'=>$c,'recursive'=>1]);
        $this->set('data',$data);
    }

    /**
     * Function to find the most recent datasets
     * @return mixed
     */
    public function recent()
    {
        $data=$this->Dataset->find('list',['order'=>['updated'=>'desc'],'limit'=>6]);
        if($this->request->params['requested']) { return $data; }
        $this->set('data',$data);
    }

    /**
     * Total files
     * @return mixed
     */
    public function totalfiles()
    {
        $data=$this->Dataset->find('count');
        return $data;
    }

    /**
     * Generate SciData
     * @param $id
     * @param $down
     */
    public function scidata($id,$down="")
    {
        // Note: there is an issue with the retrival of substances under system if id is not requested as a field
        // This is a bug in CakePHP as it works without id if its at the top level...
        $contains=[
        	'Annotation',
            'Dataseries'=>[
                'Condition'=>['Unit',
                    'Property'=>['fields'=>['name'],
                        'Quantity'=>['fields'=>['name']]]],
                'Setting'=>['Unit', 'Property'=>['fields'=>['name'],
                    'Quantity'=>['fields'=>['name']]]],
                'Datapoint'=>[
                    'Condition'=>['Unit',
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]],
                    'Data'=>['Unit',
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]],
                    'Setting'=>['Unit',
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]],
                    'SupplementalData'=>['Unit',
                        'Metadata'=>['fields'=>['name']],
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]]],
                'Annotation'
            ],
			'File',
			'Reactionprop',
			'Reference'=>['Journal'],
			'Sampleprop',
            'System'=>[
                'Substance'=>[
                	'fields'=>['name','formula','molweight','type'],
                    'Identifier'=>['fields'=>['type','value'],'conditions'=>['type'=>['inchi','inchikey','iupacname']]],
					'Chemical'=>['fields'=>['orgnum','name','source','purity']]]
            ],
        ];
        $data=$this->Dataset->find('first',['conditions'=>['Dataset.id'=>$id],'contain'=>$contains,'recursive'=>-1]);
        //debug($data);exit;
        $set=$data['Dataset'];
        $file=$data['File'];
		$ref=$data['Reference'];
		$jnl=$ref['Journal'];
        $ser=$data['Dataseries'];
        $sys=$data['System'];
		//debug($ser);exit;

        // Other systems -> related
        $othersys=$this->Dataset->find('list',['fields'=>['id'],'conditions'=>['system_id'=>$sys['id'],'file_id'=>$file['id'],'NOT'=>['Dataset.id'=>$id]]]);
        //debug($othersys);exit;

        // Base
        $base="https://chalk.coas.unf.edu/trc/datasets/scidata/".$id."/";

        // Build the PHP array that will then be converted to JSON
        $json['@context']=['https://stuchalk.github.io/scidata/contexts/scidata.jsonld',
            ['sci'=>'http://stuchalk.github.io/scidata/ontology/scidata.owl#',
                'meas'=>'http://stuchalk.github.io/scidata/ontology/scidata_measurement.owl#',
                'qudt'=>'http://www.qudt.org/qudt/owl/1.0.0/unit.owl#',
                'dc'=>'http://purl.org/dc/terms/',
                'xsd'=>'http://www.w3.org/2001/XMLSchema#'],
            ['@base'=>$base]];

        // Main metadata
        $json['@id']="";
        $json['uid']="trc:dataset:".$id;
        $json['title']=$ref['title'];
        $json['author']=[];
        if($ref['authors']!=null) {
            if(stristr($ref['authors'],'[{')) {
                $authors=json_decode($ref['authors'],true);
            } else {
                $authors=explode(", ",$ref['authors']);
            }
            $acount=1;
            foreach ($authors as $au) {
            	$name=$au['firstname']." ".$au['lastname'];
                $json['author'][]=['@id'=>'author/'.$acount.'/','@type'=>'dc:creator','name'=>$name];
                $acount++;
            }
        }
		$json['description']="Report of thermochemical data in ThermoML format from the NIST TRC website http://www.trc.nist.gov/ThermoML/";
		$json['publisher']=$jnl['publisher'];
        $json['startdate']=$set['updated'];
        $json['permalink']="https://chalk.coas.unf.edu/trc/datasets/view/".$id;
        foreach($othersys as $os) {
            $json['related'][]="https://chalk.coas.unf.edu/trc/datasets/view/".$os;
        }
        $json['toc']=['@id'=>'toc','@type'=>'dc:tableOfContents','sections'=>[]];

        // Process data series to split out conditions, settings, and parameters
        $datas=$conds=$setts=$supps=[];
        foreach($ser[0]['Datapoint'] as $p=>$point) {
            foreach($point['Data'] as $d=>$dval) {
                $datas[$d][$p]=$dval;
            }
            foreach($point['Condition'] as $c=>$cval) {
                $conds[$c][$p]=$cval;
            }
            foreach($point['Setting'] as $s=>$sval) {
                $setts[$s][$p]=$sval;
            }
            foreach($point['SupplementalData'] as $u=>$uval) {
                $supps[$u][$p]=$uval;
            }
        }
        //debug($datas);debug($conds);debug($setts);debug($supps);exit;

        // SciData
        $setj['@id']="scidata";
        $setj['@type']="sci:scientificData";
        $json['scidata']=$setj;

        // Settings
        $metj=[];
        if(!empty($setts)) {
            // Methodology
            $metj['@id']='methodology';
            $metj['@type']='sci:methodology';
            $metj['evaluation']='experimental';
            $metj['aspects']=[];
            $json['toc']['sections'][] = $metj['@id'];
            $meaj['@id'] = 'measurement/1';
            $meaj['@type'] = 'meas:measurement';
            $json['toc']['sections'][] = $meaj['@id'];
            $meaj['settings'] = [];
            foreach($setts as $sid=>$sett) {
                //debug($sett);exit;
                $setgj = [];
                $setgj['@id'] = "setting/".($sid + 1);
                $setgj['@type'] = "sci:setting";
                $setgj['quantity'] = strtolower($sett[0]['Property']['Quantity']['name']);
                $setgj['property'] = $sett[0]['Property']['name'];
                foreach ($sett as $sidx => $s) {
                    $v=$vs=[];
                    if(!in_array($s['number'],$vs)) {
                        $vs[]=$s['number'];
                        $v['@id'] = "setting/" . ($sid + 1) . "/value/".(array_search($s['number'],$vs)+1);
                        $v['@type'] = "sci:value";
                        if (!is_null($s['number'])) {
                            $v['number'] = $s['number'];
                            if (isset($s['Unit']['symbol']) && !empty($s['Unit']['symbol'])) {
                                $v['unitref'] = $this->Unit->qudt($s['Unit']['symbol']);
                            }
                        } else {
                            $v['text'] = $s['text'];
                        }
                        $setgj['value'] = $v;

                    }
                    $setts[$sid][$sidx]['slink'][]="setting/".($sid + 1) . "/value/".(array_search($s['number'],$vs)+1);
                }
                $meaj['settings'][] = $setgj;
            }
            $metj['aspects'][] = $meaj;
        }
        $json['scidata']['methodology']=$metj;

        // System
        $sysj=[];
        if(is_array($sys)&&!empty($sys)||is_array($conds)&&!empty($conds)) {
			$sysj['@id']='system/';
			$json['toc']['sections'][]=$sysj['@id'];
            $sysj['@type']='sci:system';
            $sysj['discipline']='chemistry';
            $sysj['subdiscipline']='physical chemistry';
            $sysj['facets']=[];
        }

        // System sections
        // Mixture/Substance/Chemical
		//debug($sys);exit;
        $type='';
        if(is_array($sys)&&!empty($sys)) {
            // System
            if (count($sys['Substance']) == 1) {
                $type = "substance";
            } else {
                $type = "mixture";
            }
            $sid = "substance/1/";
            $json['toc']['sections'][] = $sid;
            $mixj['@id'] = $sid;
            $mixj['@type'] = "sci:".$type;
			$mixj['composition']=$sys['composition'];
			$mixj['phase']=$sys['phase'];
			$opts = ['name', 'description', 'type'];
            foreach ($opts as $opt) {
                if (isset($sys[$opt]) && $sys[$opt] != "") {
                    $mixj[$opt] = $sys[$opt];
                }
            }
            if (isset($sys['Substance'])) {
                for ($j = 0; $j < count($sys['Substance']); $j++) {
                    // Components
					unset($subj);
					$subj['@id'] = $sid."/component/".($j + 1)."/";
                    $subj['@type'] = "sci:chemical";
                    $subj['source'] = "chemical/".($j + 2).'/';
                    $mixj['components'][] = $subj;
                    // Substances
                    unset($subj);$sub = $sys['Substance'][$j];
                    $subj['@id'] = "substance/".($j + 2).'/';
                    $json['toc']['sections'][] = $subj['@id'];
					$subj['@type'] = "sci:".$sub['type'];
                    $opts = ['name', 'formula', 'molweight'];
                    foreach ($opts as $opt) {
                        if (isset($sub[$opt]) && $sub[$opt] != "") {
							$subj[$opt] = $sub[$opt];
                        }
                    }
                    if (isset($sub['Identifier'])) {
                        $opts = ['inchi', 'inchikey', 'iupacname'];
                        foreach ($sub['Identifier'] as $idn) {
                            foreach ($opts as $opt) {
                                if ($idn['type'] == $opt) {
									$subj[$opt] = $idn['value'];
                                }
                            }
                        }
                    }
                    $sysj['facets'][] = $subj;
                    // Chemicals
					$chem=$sub['Chemical'];
					$chmj['@id'] = "chemical/".($j + 2).'/';
					$json['toc']['sections'][] = $chmj['@id'];
					$chmj['@type'] = "sci:chemical";
					$chmj['source'] = "substance/".($j + 2).'/';
					$chmj['acquired'] = $chem['source'];
					if(!is_null($chem['purity'])) {
						$purj['@id'] = "purity/";
						$purj['@type'] = "sci:purity";
						$purity=json_decode($chem['purity'],true);
						foreach($purity as $step) {
							$stepsj[$step['step']]['@id'] = "step/".$step['step'].'/';
							$stepsj[$step['step']]['@type'] = "sci:value";
							$stepsj[$step['step']]['part'] = $step['type'];
							if(!is_null($step['analmeth'])) {
								$stepsj[$step['step']]['analysis']=$step['analmeth'];
							}
							if(!is_null($step['purimeth'])) {
								$stepsj[$step['step']]['purification']=$step['purimeth'];
							} else {
								$stepsj[$step['step']]['purification']=null;
							}
			
							if(!is_null($step['purity'])) {
								$stepsj[$step['step']]['number']=$step['purity'];
							}
							if(!is_null($step['puritysf'])) {
								$stepsj[$step['step']]['sigfigs']=$step['puritysf'];
							}
							if(!is_null($step['purityunit_id'])) {
								$qudtid=$this->Unit->getfield('qudt',$step['purityunit_id']);
								$stepsj[$step['step']]['unitref']='qudt:'.$qudtid;
							}
							$purj['steps']=$stepsj;
						}
						$chmj['purity']=$purj;
					}
					$sysj['facets'][] = $chmj;
				}
            }
			$mixj['@id'] = $sid;
	
			$sysj['facets'][] = $mixj;
        }
        // Conditions
        if(is_array($conds)&&!empty($conds)) {
            foreach($conds as $cid=>$cond) {
                //debug($cond);exit;
                $v=$vs=$condj = [];
                $condj['@id'] = "condition/".($cid + 1)."/";
                $json['toc']['sections'][] = $condj['@id'];
                $condj['@type'] = "sci:condition";
                $condj['quantity'] = strtolower($cond[0]['Property']['Quantity']['name']);
                $condj['property'] = $cond[0]['Property']['name'];
                foreach ($cond as $cidx => $c) {
                    if(!in_array($c['number'],$vs)) {
                        $vs[]=$c['number'];
                        $v['@id'] = "condition/" . ($cid + 1) . "/value/".(array_search($c['number'],$vs)+1).'/';
                        $v['@type'] = "sci:value";
                        if (!is_null($c['number'])) {
                            $v['number'] = $c['number'];
                            if (isset($c['Unit']['symbol']) && !empty($c['Unit']['symbol'])) {
                                $v['unitref'] = $this->Unit->qudt($c['Unit']['symbol']);
                            }
                        } else {
                            $v['text'] = $c['text'];
                        }
                        $condj['value'][] = $v;
                    }
                    $conds[$cid][$cidx]['clink']="condition/".($cid+1)."/value/".(array_search($c['number'],$vs)+1).'/';
                }
                $sysj['facets'][] = $condj;
            }
        }
        
		//debug($ser);exit;
		$cid++;
		// Dataseries conditions
		if(!is_null($ser[0]['Condition'])) {
			foreach($ser[0]['Condition'] as $scidx=>$scond) {
				$scondj=[];$cid++;
				$scondj['@id'] = "condition/".$cid."/";
				$json['toc']['sections'][] = $scondj['@id'];
				$scondj['@type'] = "sci:seriescondition";
				$scondj['quantity'] = strtolower($scond['Property']['Quantity']['name']);
				$scondj['property'] = $scond['Property']['name'];
				$v['@id'] = "condition/".$cid."/value/1/";
				$v['@type'] = "sci:value";
				if (!is_null($scond['number'])) {
					$v['number'] = $scond['number'];
					if (isset($scond['Unit']['symbol']) && !empty($scond['Unit']['symbol'])) {
						$v['unitref'] = $this->Unit->qudt($scond['Unit']['symbol']);
					}
				} else {
					$v['text'] = $scond['text'];
				}
				$scondj['value'][] = $v;
				$ser[0]['Condition'][$scidx]['sclink']=$v['@id'];
			}
			$sysj['facets'][] = $scondj;
		}
		$json['scidata']['system']=$sysj;
		
		//debug($conds);exit;
        
        // Data
        $resj=[];
        if(is_array($datas)&&!empty($datas)) {
			$resj['@id']='dataset/';
			$json['toc']['sections'][]=$resj['@id'];
            $resj['@type']='sci:dataset';
            $resj['source']='measurement/1/';
            $resj['scope']= $type.'/1/';
            $resj['datagroup']=[];
            // Group
            foreach($datas as $did=>$data) {
                $grpj['@id']='datagroup/'.($did+1).'/';
                $json['toc']['sections'][] = $grpj['@id'];
                $grpj['@type'] = 'sci:datagroup';
                $grpj['quantity']=strtolower($data[0]['Property']['Quantity']['name']);
                $grpj['property']=$data[0]['Property']['name'];
                foreach($data as $d=>$dtm) {
                    $dtmj=[];
                    $dtmj['@id'] = 'datagroup/'.($did+1).'/datapoint/'.($d+1).'/';
                    $dtmj['@type'] = 'sci:datapoint';
					if(!empty($conds)) {
						$dtmj['conditions']=[];
						foreach ($conds as $cond) {
							$dtmj['conditions'][]=$cond[$d]['clink'];
						}
					}
					if(!is_null($ser[0]['Condition'])) {
						foreach($ser[0]['Condition'] as $scidx=>$scond) {
							$dtmj['conditions'][]=$scond['sclink'];
						}
					}
                    if(!empty($setts)) {
						foreach ($setts as $sett) {
							$dtmj['settings'][] = $sett[$d]['slink'];
						}
                    }
                    // Value
                    $v=[];
                    if(!is_null($dtm['number'])) {
                        $unit="";
                        if(isset($dtm['Unit']['symbol'])&&!empty($dtm['Unit']['symbol'])) {
                            $unit=$this->Dataset->qudt($dtm['Unit']['symbol']);
                        }
                        if($dtm['datatype']=="datum") {
                            $v['@id']=$dtmj['@id']."value/";
                            $v['@type']="sci:value";
                            $v['number']=$dtm['number'];
                            if($unit!="") { $v['unitref']=$unit; }
                            $dtmj['value']=$v;
                        } else {
                            $v['@id']=$dtmj['@id']."valuearray/";
                            $v['@type']="sci:valuearray";
                            $v['numberarray']=json_decode($dtm['number'],true);
                            if($unit!="") { $v['unitref']=$unit; }
                            $dtmj['valuearray']=$v;
                        }
                    }
                    $grpj['datapoint'][]=$dtmj;
                }
                $resj['datagroup'][]=$grpj;
            }
        }
        $json['scidata']['dataset']=$resj;

        // Sources
        // Original Paper
        $paper=['@id'=>'reference/1','@type'=>'dc:source'];
        if($ref['bibliography']!=null) {
            $paper['citation'] = $ref['bibliography'];
        } elseif($ref['citation']!=null) {
            $paper['citation'] = $ref['citation'];
        }
        if(isset($ref['doi'])&&$ref['doi']!=null) {
            $paper['url']="http://dx.doi.org/".$ref['doi'];
        }
        if(isset($ref['url'])&&$ref['url']!=null) {
            $paper['url']=$ref['url'];
        }
        // On TRC ThermoML site
        $trc=['@id'=>'reference/2','@type'=>'dc:source'];
		$trc['citation'] = "Original data file from NIST TRC Archive at http://www.trc.nist.gov/ThermoML/";
		$trc['url']="http://www.trc.nist.gov/ThermoML/".$ref['doi'];
        
        $json['references'][]=$paper;
        $json['references'][]=$trc;

        // Rights
        $json['rights']=['@id'=>'rights','@type'=>'dc:rights'];
        $json['rights']['holder']=$jnl['publisher'];
        $json['rights']['license']='http://creativecommons.org/publicdomain/zero/1.0/';
        //debug($json);exit;

        // OK turn it back into JSON-LD
        header("Content-Type: application/json");
        if($down=="download") { header('Content-Disposition: attachment; filename="'.$id.'.json"'); }
        echo json_encode($json,JSON_UNESCAPED_UNICODE);exit;

    }
	
	/**
	 * Delete a dataset (and all data underneath)
	 * @param $id
	 */
	public function delete($id)
	{
		if($this->Dataset->delete($id)) {
			$this->Flash->deleted('Dataset '.$id.' deleted!');
		} else {
			$this->Flash->deleted('Dataset '.$id.' could not be deleted!');
		}
		$this->redirect('/files/index');
	}
	
}