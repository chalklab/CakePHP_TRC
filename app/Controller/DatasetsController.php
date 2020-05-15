<?php

/**
 * Class DatasetsController
 */
class DatasetsController extends AppController
{
    public $uses=['Dataset','Journal','Report','Quantity','Dataseries',
        'Parameter','Variable','Scidata','System','Substance','File',
		'Reference','Unit','Sampleprop','Trc','Chemical', 'Condition'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    // CRUD operations

	/**
	 * View index of data sets
	 */
	public function index()
	{
		$c=['File'=>['fields'=>['id','title'],'order'=>['title'],'limit'=>20,
			'Dataset'=>['fields'=>['id','title'],'order'=>'title',
				'System'=>['fields'=>['id','name']],
				'Sampleprop'=>['fields'=>['id','property_name']],
				'Dataseries'=>['fields'=>['id']]],
			'Chemical']
		];
		$data=$this->Journal->find('all',['fields'=>['id','name'],'order'=>['name'],'contain'=>$c,'recursive'=>1]);
		//debug($data);exit;
		$this->set('data',$data);
	}

	/**
     * View a data set
     * @param integer $id
     * @return mixed
     */
    public function view($id,$serid=null,$layout=null)
    {
        $ref =['id','journal','authors','year','volume','issue','startpage','endpage','title','url'];
        $con =['id','datapoint_id','system_id','property_name','number','significand','exponent','unit_id','accuracy'];
        $prop =['id','name','phase','field','label','symbol','definition','updated'];
        $chmf=['formula','orgnum','source','substance_id'];
        $c=['Annotation',
            'File'=>[
                'Chemical' => ['Substance']],
            'Dataseries'=>[
                'Condition'=>['Unit', 'Property', 'Annotation'],
                'Setting'=>['Unit', 'Property'],
                'Datapoint'=>[
                    'Annotation',
                    'Condition'=>['fields'=>$con,'Unit', 'Property'=>['fields'=>$prop]],
                    'Data'=>['Unit','Sampleprop', 'Property'=>['fields'=>$prop]],
                    'Setting'=>['Unit', 'Property']
                ],
                'Annotation'
            ],
            'Sampleprop',
            'Reactionprop',
            'Reference'=>['fields'=>$ref,'Journal'],
                'System'=>[
                    'Substance'=>[
                        'Identifier'=>['fields'=>['type','value']]
        ]]];

        $dump=$this->Dataset->find('first',['conditions'=>['Dataset.id'=>$id],'contain'=>$c,'recursive'=>-1]);
        //debug($cont);exit;
        $datum=$dump["Dataseries"][0]["Datapoint"][0];
        $xname=$datum["Condition"][0]["Property"]["name"];
        $xunit=$datum["Condition"][0]["Unit"]["label"];
        $xlabel=$xname.", ".$xunit;
        $sers=$dump["Dataseries"];
		$serconds=[];
		foreach($sers as $ser) {
			if(!empty($ser['Condition'])) {
				foreach($ser['Condition'] as $sc) {
					$scont=$sc["number"]+0;
					$sconunit=$sc["Unit"]["symbol"];
					$serconds[]=$scont." ".$scondunit;
				}
			}
        }
		$yunit=$datum["Data"][0]["Unit"]["header"];
        $samprop=$datum["Data"][0]["Sampleprop"]["property_name"];
        $ylabel=$samprop;
        $sub=$dump["System"]["name"];
        $formula=$dump["System"]["Substance"][0]["formula"];

		$xs=[];$ys=[];

        // loop through the datapoints
        $test=$dump['Dataseries'];
        $xy[0]=[];
        $num=0;
        foreach($test as $idx=>$tt){
            $count=1;
            $xy[0][]=["label"=>$xlabel,"role"=>"domain","type"=>"number"];
            $xy[0][]=["label"=>'Series '.($idx+1),"role"=>"data","type"=>"number"];
            $xy[0][]=["label"=>"Min Error","type"=>"number","role"=>"interval"];
            $xy[0][]=["label"=>"Max Error","type"=>"number","role"=>"interval"];

            $points=$tt['Datapoint'];
            $num++;
            foreach($points as $pnt) {
                $x=$pnt['Condition'][0]['number']+0;
                $y=$pnt['Data'][0]['number']+0;
                $error=$pnt['Data'][0]['error']+0;
                $errormin=$y-$error;
                $errormax=$y+$error;

                $xs[]=$x; $minx=min($xs)-(0.02*(min($xs))); $maxx=max($xs)+(0.02*(min($xs)));
                $ys[]=$y; $miny=min($ys)-(0.02*(min($ys))); $maxy=max($ys)+(0.02*(min($ys)));
                $errormins[]=$errormin;
                $errormaxs[]=$errormax;

                $xy[$count][]=$x;
                $xy[$count][]=$y;
                $xy[$count][]=$errormin;
                $xy[$count][]=$errormax;
                $count++;
            }
        }
        //debug($xy);exit;
        // send variable to the view
        $this->set('xy',$xy);
        $this->set('maxx',$maxx); $this->set('maxy',$maxy);
        $this->set('minx',$minx); $this->set('miny',$miny);
        $this->set('errormin',$errormin);$this->set('errormax',$errormax);
        $this->set('name', 'title');
        $this->set('xlabel',$xlabel); $this->set('ylabel',$ylabel);
        $this->set('dump',$dump); $this->set('test', $test);
        $fid=$dump['Dataset']['file_id'];
        // Get a list of datsets that come from the same file
        $related=$this->Dataset->find('list',['conditions'=>['Dataset.file_id'=>$fid,'NOT'=>['Dataset.id'=>$id]],'recursive'=>1]);
        if(!is_null($layout)) {
            $this->set('serid',$serid);
            $this->render('data');
        }
        $this->set('related',$related);
        $this->set('dsid',$id);
		$title=$dump['Dataset']['title'];
		$this->set('title',$title);
		if($this->request->is('ajax')) {
            echo '{ "title" : "'.$title.'" }';exit;
        }

         // debug($xy);exit;
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

	// other actions

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
	 * New SciData creation script that uses the class to create the file
	 * @param $id
	 * @param string $down
	 */
    public function scidata($id,$down="")
	{
		// Note: there is an issue with the retrival of substances under system if id is not requested as a field
		// This is a bug in CakePHP as it works without id if its at the top level...
		$c = [
			'Annotation',
			'Dataseries' => [
				'Condition' => ['Unit',
					'Property' => ['fields' => ['name'],
						'Quantity' => ['fields' => ['name']]]],
				'Setting' => ['Unit', 'Property' => ['fields' => ['name'],
					'Quantity' => ['fields' => ['name']]]],
				'Datapoint' => [
					'Condition' => ['fields' => ['id', 'datapoint_id', 'property_id', 'system_id',
						'property_name', 'datatype', 'number', 'significand', 'exponent', 'unit_id',
						'accuracy', 'exact'], 'Unit',
						'Property' => ['fields' => ['name'],
							'Quantity' => ['fields' => ['name']]]],
					'Data' => ['fields' => ['id', 'datapoint_id', 'property_id', 'sampleprop_id',
						'datatype', 'number', 'significand', 'exponent', 'error', 'error_type',
						'unit_id', 'accuracy', 'exact'], 'Unit',
						'Property' => ['fields' => ['name'],
							'Quantity' => ['fields' => ['name']]]],
					'Setting' => ['Unit',
						'Property' => ['fields' => ['name'],
							'Quantity' => ['fields' => ['name']]]]],
				'Annotation'
			],
			'File',
			'Reactionprop',
			'Reference' => ['Journal'],
			'Sampleprop',
			'System' => [
				'Substance' => [
					'fields' => ['name', 'formula', 'molweight', 'type'],
					'Identifier' => ['fields' => ['type', 'value'], 'conditions' => ['type' => ['inchi', 'inchikey', 'iupacname']]],
					'Chemical' => ['fields' => ['orgnum', 'name', 'source', 'purity']]]
			],
		];
		$data = $this->Dataset->find('first', ['conditions' => ['Dataset.id' => $id], 'contain' => $c, 'recursive' => -1]);
		//debug($data);exit;
		$set = $data['Dataset'];
		$file = $data['File'];
		$ref = $data['Reference'];
		$jnl = $ref['Journal'];
		$sers = $data['Dataseries'];
		$sys = $data['System'];
		//debug($ser);exit;

		// Other systems -> related
		$othersys = $this->Dataset->find('list', ['fields' => ['id'], 'conditions' => ['system_id' => $sys['id'], 'file_id' => $file['id'], 'NOT' => ['Dataset.id' => $id]]]);
		//debug($othersys);exit;

		// base
		$base = "https://chalk.coas.unf.edu/trc/datasets/scidata/" . $id . "/";

		// Build the PHP array that will then be converted to JSON
		$json['@context'] = ['https://stuchalk.github.io/scidata/contexts/scidata.jsonld',
			['sci' => 'https://stuchalk.github.io/scidata/ontology/scidata.owl#',
				'meas' => 'http://stuchalk.github.io/scidata/ontology/scidata_measurement.owl#',
				'qudt' => 'http://www.qudt.org/qudt/owl/1.0.0/unit.owl#',
				'dc' => 'http://purl.org/dc/terms/',
				'xsd' => 'http://www.w3.org/2001/XMLSchema#'],
			['@base' => $base]];
		// get the crosswalk data
		$fields = $nspaces = $ontlinks = [];
		$this->getcw('conditions', $fields, $nspaces, $ontlinks);
		$this->getcw('exptdata', $fields, $nspaces, $ontlinks);
		$this->getcw('deriveddata', $fields, $nspaces, $ontlinks);
		$this->getcw('suppdata', $fields, $nspaces, $ontlinks);
		//debug($fields);debug($nspaces);debug($ontlinks);exit;

		// create an instance of the Scidata class
		$trc = new $this->Scidata;
		$trc->setnspaces($nspaces);
		$trc->setpath("https://scidata.coas.unf.edu/trc/");
		$trc->setbase($id . "/");
		$trc->setid("https://scidata.coas.unf.edu/trc/" . $id . "/");
		$meta = ['title' => $ref['title'],
			'publisher' => $jnl['publisher'],
			'description' => '"Report of thermochemical data in ThermoML format from the
								NIST TRC website http://www.trc.nist.gov/ThermoML/"',
			'authors' => $ref['authors'],
			'uid' => "trc:dataset:" . $id];
		$trc->setdiscipline("chemistry");
		$trc->setsubdiscipline("physical chemistry");
		$trc->setmeta($meta);

		// Process data series to split out conditions, settings, and parameters
		$datas = $conds = $setts = [];
		foreach ($ser[0]['Datapoint'] as $p => $pnt) {
			foreach ($pnt['Data'] as $d => $dval) {
				$datas[$d][$p] = $dval;
			}
			foreach ($pnt['Condition'] as $c => $cval) {
				$conds[$c][$p] = $cval;
			}
			foreach ($pnt['Setting'] as $s => $sval) {
				$setts[$s][$p] = $sval;
			}
		}

		// Methodology (general info)

		// Methodology sections (add data to $aspects)
		// Settings

		// This is a line that also needs to be taken out!

		// System (general info)
		$sysj = [];
		if (is_array($sys) && !empty($sys) || is_array($conds) && !empty($conds)) {
			$sysj['@id'] = 'system/';
			$json['toc']['sections'][] = $sysj['@id'];
			$sysj['@type'] = 'sci:system';
			$sysj['discipline'] = 'chemistry';
			$sysj['subdiscipline'] = 'physical chemistry';
			$sysj['facets'] = [];
		}

		// System sections (add data to $facets)
		// Mixture/Substance/Chemical
		$facets=[];
		if (is_array($sys) && !empty($sys)) {
			// get substances
			$subs = $sys['Substance'];
			$substances = [];$chemicals = [];
			foreach ($subs as $subidx => $sub) {
				$s = [];
				$opts = ['name', 'formula', 'molweight', 'type'];
				foreach ($opts as $opt) {
					$s[$opt] = $sub[$opt];
				}
				foreach ($sub['Identifier'] as $subid) {
					$s[$subid['type']] = $subid['value'];
				}

				$substances[($subidx + 1)] = $s;

				// get chemicals
				$chmf = $sub['Chemical'];
				$temp = json_decode($chmf['purity'],true);
				$chmf['purity']=$temp;
				$c = [];
				$opts = ['name','source','purity'];
				foreach ($opts as $opt) {
					if($opt=='purity') {
						$value=$chmf[$opt][0]['purity'];
						$unit='% (w/w)'; // TODO: really should get unit from units table
						$desc=$chmf[$opt][0]['analmeth'][0];
						$chmf['purity']=$value.$unit.' ('.$desc.')';
					}
					$c[$opt] = $chmf[$opt];
				}
				$c['compound']='compound/'.($subidx + 1).'/';
				$chemicals[($subidx + 1)] = $c;
			}
			//debug($substances);
			$facets['sci:compound'] = $substances;
			//debug($chemicals);exit;
			$facets['sci:chemical'] = $chemicals;
			//$trc->setfacets($facets);
			//$sd=$trc->asarray();
			//debug($sd);exit;

			// conditions (organize first then write to a variable to send to the model)
			// here we need to process both series conditions and regular conditions
			// In a dataseries ($ser) $ser['Condition'] is where the series conditions are (1 -> n)
			// ... and the regular conditions are in datapoints ($pnt) under $pnt['Condition']
			$conditions = [];
			foreach ($sers as $seridx => $ser) {
				$cond = $ser['Condition']; // series conditions
				$pnts = $ser['Datapoint']; // all datapoints in a series
				foreach ($pnts as $pntidx => $pnt) {
					$conds = $pnt['Condition']; // conditions for datapoints (0 -> n)
					foreach ($conds as $conidx => $cond) {
						// generate array of unique values of each condition
                        array_values(array_unique($cond));
						// generate variable to capture the links between condition @ids and datapoints
						$s = [];
						$opts = ['property_name', 'number', 'unit_id'];
						foreach ($opts as $opt) {
							$s[$opt] = $sys[$opt];
						}
						foreach ($con['Property'] as $conid) {
							$s[$conid['name']] = $conid['symbol'];
						}
					}
				}
			}

			//debug($conditions);exit;
			$facets['sci:condition'] = $conditions;
		}

		// add facets data to instance
		$trc->setfacets($facets);

		// Dataset (general info)
		//

		// Data (add data to $series)
		$series=[];

		// add series data to instance


		// Sources
		// Go get the DOI
		$bib=$ref['title']." ".$ref['authors']." ".$ref['journal']." ".$ref['year']." ".$ref['volume']." ".$ref['startpage'];
		$src=[];
		$src['id']="source/".$ref['id'];
		$src['type']="paper";
		$src['citation']=$bib;
		$src['url']=$ref['url'];
		$sources[]=$src;
		$trc->setsources($sources);
		//debug($src);exit;

		// Rights

		// spit out data to view as an array
		$sd=$trc->asarray();
		debug($sd);exit;
	}

    /**
     * Generate SciData
     * @param $id
     * @param $down
     */
    public function scidata2($id,$down="")
    {
        // Note: there is an issue with the retrival of substances under system if id is not requested as a field
        // This is a bug in CakePHP as it works without id if its at the top level...
        $c=[
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
        $data=$this->Dataset->find('first',['conditions'=>['Dataset.id'=>$id],'contain'=>$c,'recursive'=>-1]);
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
                'meas'=>'https://stuchalk.github.io/scidata/ontology/scidata_measurement.owl#',
                'qudt'=>'http://www.qudt.org/qudt/owl/1.0.0/unit.owl#',
                'dc'=>'http://purl.org/dc/terms/',
                'xsd'=>'http://www.w3.org/2001/XMLSchema#'],
            ['@base'=>$base]];

        // Main metadata
        $json['@id']="$id";
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
        $datas=$conds=$setts=[];
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
        }

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
                    $subj['@id'] = $sid."component/".($j + 1)."/";
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
                        $opts = ['inchi', 'inchikey', 'iupacname','CASRN','SMILES'];
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
                        $purj['@id'] = "purity/".($j + 2).'/';
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
				$sysj['facets'][] = $scondj;
			}
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
        $json['rights']['holder']='NIST Thermodynamics Research Center (Boulder, CO)';
        $json['rights']['license']='https://creativecommons.org/licenses/by-nc/4.0/';
        //debug($json);exit;

        // OK turn it back into JSON-LD
        header("Content-Type: application/json");
        if($down=="download") { header('Content-Disposition: attachment; filename="'.$id.'.json"'); }
        echo json_encode($json,JSON_UNESCAPED_UNICODE);exit;

    }

    // private functions

	/**
	 * Get crosswalk info for fields that are a specific $type
	 * @param $type
	 * @param $fields
	 * @param $nspaces
	 * @param $ontlinks
	 */
	private function getcw($type,&$fields,&$nspaces,&$ontlinks) {
		$c=['Ontterm'=>['Nspace']];$table="Trc";
		$metas = $this->$table->find('all',['contain'=>$c, 'recursive'=>-1]);
		//debug($metas);
		$fields[$type]=$ontlinks[$type]=[];
		foreach ($metas as $meta) {
			if($meta[$table]['sdsubsection']==$type) {
				$fields[$type][]=$meta[$table]['field'];
			}
			if($meta['Ontterm']['sdsubsection']==$type&&$meta[$table]['sdsubsection']==null) {
				$fields[$type][]=$meta[$table]['field'];
			}
			if(in_array($meta[$table]['field'],$fields[$type])) {
				$ontlinks[$type][$meta[$table]['field']]=$meta['Ontterm']['url'];
				$nspaces[$meta['Ontterm']['Nspace']['ns']]=$meta['Ontterm']['Nspace']['path'];
			}
		}
		return;
	}

	/**
	 * Get crosswalk info for fields that are a specific $type by section
	 * @param $type
	 * @param $fields
	 * @param $nspaces
	 * @param $ontlinks
	 */
	private function getcwbysect($type,&$fields,&$nspaces,&$ontlinks) {
		$c=['Ontterm'=>['Nspace']];$table='Trc';
		$metas = $this->$table->find('all',['contain'=>$c, 'recursive'=>-1]);
		$fields[$type]=$ontlinks[$type]=[];$flist=[];
		foreach ($metas as $meta) {
			if($meta[$table]['sdsection']==$type) {
				$fields[$type][$meta[$table]['sdsubsection']][]=$meta[$table]['field'];
				$ontlinks[$type][$meta[$table]['sdsubsection']][$meta[$table]['field']]=$meta['Ontterm']['url'];
				$flist[]=$meta[$table]['field'];
			}
			if($meta['Ontterm']['sdsection']==$type&&$meta[$table]['sdsection']==null) {
				$fields[$type][$meta['Ontterm']['sdsubsection']][]=$meta[$table]['field'];
				$ontlinks[$type][$meta['Ontterm']['sdsubsection']][$meta[$table]['field']]=$meta['Ontterm']['url'];
				$flist[]=$meta[$table]['field'];
			}
			if(in_array($meta[$table]['field'],$flist)) {
				$nspaces[$meta['Ontterm']['Nspace']['ns']]=$meta['Ontterm']['Nspace']['path'];
			}
		}
		return;
	}

	/**
	 * Get crosswalk info for fields that are a specific $type by subsection
	 * @param $type
	 * @param $fields
	 * @param $nspaces
	 * @param $ontlinks
	 */
	private function getcwbysubsect($type,&$fields,&$nspaces,&$ontlinks) {
		$c=['Ontterm'=>['Nspace']];$table='Trc';
		$metas = $this->$table->find('all',['contain'=>$c, 'recursive'=>-1]);
		$fields[$type]=$ontlinks[$type]=[];
		foreach ($metas as $meta) {
			if($meta[$table]['sdsubsection']==$type) {
				$fields[$type][]=$meta[$table]['field'];
			}
			if($meta['Ontterm']['sdsubsection']==$type&&$meta[$table]['sdsubsection']==null) {
				$fields[$type][]=$meta[$table]['field'];
			}
			if(in_array($meta[$table]['field'],$fields[$type])) {
				$ontlinks[$type][$meta[$table]['field']]=$meta['Ontterm']['url'];
				$nspaces[$meta['Ontterm']['Nspace']['ns']]=$meta['Ontterm']['Nspace']['path'];
			}
		}
		return;
	}

	// other functions

	/**
	 * reaction data
	 * @param $id
	 */
	public function rdata($id){
		$c=[
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
		$data=$this->Dataset->find('first',['conditions'=>['Dataset.id'=>$id],'contain'=>$c,'recursive'=>-1]);
		// debug($data);exit;
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
		$json['@context']=[''];

		// Process data series to split out conditions, settings, and parameters
		$datas=$conds=$setts=[];
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
		}
		debug($datas);exit;
		// SciData

		// Settings
		$metj=[];
		if(!empty($setts)) {
			// Methodology
			$json['toc']['sections'][] = $metj['@id'];
			$meaj['@id'] = 'measurement/1';
			$meaj['@type'] = 'meas:measurement';
			$json['toc']['sections'][] = $meaj['@id'];
			$meaj['settings'] = [];
			foreach($setts as $sid=>$sett) {
				// debug($sett);exit;
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

}

