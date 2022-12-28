<?php

/**
 * Class FilesController
 * actions related to working with the files table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 12/28/22
 */
class FilesController extends AppController
{
	public $uses = ['Chemical','Compohnent','Condition','Data','Datapoint','Dataseries','Dataset',
		'File','Journal','Identifier','Mixture','Phasetype','Phase','Quantity','Reference','Report','Sampleprop',
		'Scidata','Substance','SubstancesSystem','System','Unit','Pubchem.Compound','Crossref.Api','CommonChem.Cas'];

	public string $ccapi='https://commonchemistry.cas.org/api/search?q=';

	public array $c = [
		'Chemical' => ['Substance'],
		'Reference' => ['Journal'],
		'Dataset' => [
			'Dataseries' => [
				'Condition' => ['Unit',
					'Quantity' => ['fields' => ['name'],
						'Quantitykind' => ['fields' => ['name']]]],
				'Datapoint' => [
					'Condition' => ['Unit',
						'Quantity' => ['fields' => ['name'],
							'Quantitykind' => ['fields' => ['name']]]],
					'Data' => ['Unit',
						'Quantity' => ['fields' => ['name'],
							'Quantitykind' => ['fields' => ['name']]]]
				]
			],
			'Sampleprop',
			'System' => [
				'Substance' => ['fields' => ['name', 'formula', 'mw', 'type'],
					'Identifier' => ['fields' => ['type', 'value'],
						'conditions' => ['type' => ['inchi','inchikey','iupacname','casrn']]]
				]
			]
		]
	];

	/**
	 * function beforeFilter
	 */
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Auth->allow('index','view','most');
	}

	/**
	 * show a list of NIST TRC ThermoML XML files
	 * @return void
	 */
	public function index()
	{
		$f=['File.id','Reference.title','File.year'];$c=['Reference'];$o=['year','title'];
		$data = $this->File->find('list',['fields'=>$f,'contain'=>$c,'order'=>$o,'recursive'=>-1]);
		$this->set('data',$data);
	}

	/**
	 * view general information about a NIST TRC ThermoML XML file
	 * @param int $id
	 * @return void
	 */
	public function view(int $id)
	{
		$data = $this->File->find('first', ['conditions' => ['File.id' => $id], 'contain' => $this->c, 'recursive' => -1]);
		$ref = $data['Reference'];
		$sets = $data['Dataset'];
		$this->set('ref',$ref);
		$this->set('sets',$sets);
	}

	/**
	 * get a list of files containing the most data
	 * called via a requestAction call in the 'most.ctp' element view
	 * no most view file
	 * @param int $l
	 * @return void
	 */
	public function most(int $l=6)
	{
		$this->Reference->virtualFields['titlepnts'] = "CONCAT(Reference.title,' (',File.points,' points)')";
		$f=['Reference.id','Reference.titlepnts'];$c=['File'];$o=['File.points'=>'desc'];
		$data=$this->Reference->find('list',['fields'=>$f,'order'=>$o,'contain'=>$c,'limit'=>$l]);
		if($this->request->params['requested']) { return $data; }
	}

	// functions requiring login (not in Auth::allow)

	/**
	 * ingest TRC xml files
	 * @param int $maxfiles
	 * @return void
	 */
	public function ingest(int $maxfiles=10)
	{
		// updated for change of tables: properties -> quantities and quantities -> quantitykinds... SJC 11/08/21
		$code = 'ijt';  // change code to process files from different journals
		$path = WWW_ROOT.'files'.DS.'trc'.DS.$code.DS;  // from path webroot/files/<code>
		$maindir = new Folder($path);
		$files = $maindir->find('^.*\.xml$',true); // find all files ending with the '.xml' extension

		$count=0;
		$done = $this->File->find('list', ['fields' => ['id','filename'],'recursive'=>-1]);
		foreach ($files as $filename) {
			if(in_array($filename,$done)) { continue; }  // echo $filename." already processed<br/>";
			$filepath = $path.$filename;

			$xml = simplexml_load_file($filepath);$count++;$errors = [];
			$trc = json_decode(json_encode($xml), true);  // convert from XML to PHP array

			# check for the presence of data (not processing reaction data)
			if(!isset($trc['PureOrMixtureData'])) { echo "No data in '".$filename."'<br/>";continue; }

			// get DOI
			$doi = $title = null;
			if (isset($trc['Citation']['sDOI'])) {
				// DOI in ThermoML file
				$doi = $trc['Citation']['sDOI'];
				$title = $trc['Citation']['sTitle'];
			} else {
				// get DOI from crossref using citation metadata
				$pages = str_replace('  ', ' ', $trc['Citation']['sPage']);
				$journal =  $trc['Citation']['sPubName'];
				if($journal=='J.Chem.Eng.Data') { $journal='J. Chem. Eng. Data'; }
				if($journal=='Int J Thermophys') { $journal='Int. J. Thermophys.';}
				$issn = $this->Journal->getfield('issn',$journal);
				$year = $trc['Citation']['yrPubYr'];
                $volume = $trc['Citation']['sVol'];
                $filter = ['issn' => $issn, 'date' => $year];
				// search crossref API (http://api.crossref.org/works/<DOI>)
				$found = $this->Api->works($pages, $filter);  // in Plugins/Crossref/Model/Api.php
				if ($found) {
					if (count($found['items']) == 1) {
						$doi = $found['items'][0]['DOI'];
					} else {
					    foreach($found['items'] as $hit) {
					        if($issn==$hit['ISSN'][0]||$issn==$hit['ISSN'][1]) {
					            if($volume==$hit['volume']) {
					                if($pages==$hit['page']) {
					                    $doi = $hit['DOI'];break;
					                }
					            }
					        }
					    }
                    }
                    if(is_null($doi)) { $errors[] = 'No reference matched'; }
				} else {
					$errors[] = 'No DOI found';
				}
			}

			// check for errors
			if(!empty($errors)) {
				echo "File '".$filename."' has errors.<br/>";
				debug($errors);continue;
			}

			// organize the substance and chemical info (indexed by orgnum)
			$compds = $trc['Compound'];$subs = [];$chems = [];
			if (!isset($compds[0])) { $compds = ['0' => $compds]; }
			foreach ($compds as $comp) {
				$chem = [];$sub = [];
				$chem['orgnum'] = $comp['RegNum']['nOrgNum'];
				if(isset($comp['sStandardInChI'])) {
					$sub['inchi'] = $comp['sStandardInChI'];
				}
				if(isset($comp['sStandardInChIKey'])) {
					$sub['inchikey'] = $comp['sStandardInChIKey'];
				}
				if(isset($comp['sIUPACName'])) {
					$sub['iupacname'] = $chem['iupacname'] = $comp['sIUPACName'];
				}
				if(isset($comp['sCommonName'])) { // either string or array
					$sub['names'] = $comp['sCommonName'];
				}
				if(is_array($sub['names'])) {
					$sub['name'] = $chem['name'] = $sub['names'][0];
				} else {
					$sub['name'] = $chem['name'] = $sub['names'];
				}
				$sub['formula'] = $chem['formula'] = $comp['sFormulaMolec'];
				if (isset($comp['Sample'])) {
					if (isset($comp['Sample']['eSource'])) {
						$chem['source'] = $comp['Sample']['eSource'];
					} else {
						$chem['source'] = null;
					}
					if (isset($comp['Sample']['purity'])) {
						if (!isset($comp['Sample']['purity'][0])) {
							$comp['Sample']['purity'] = [0 => $comp['Sample']['purity']];
						}
						foreach ($comp['Sample']['purity'] as $p) {
							$pur = [];
							$pur['step'] = $p['nStep'];
							if (isset($p['nPurityMass'])) {
								$pur['type'] = 'compound';
								$pur['purity'] = $p['nPurityMass'];
								$pur['puritysf'] = $p['nPurityMassDigits'];
								$pur['purityunit_id'] = 20;
							} elseif (isset($p['nPurityMol'])) {
								$pur['type'] = 'compound';
								$pur['purity'] = $p['nPurityMol'];
								$pur['puritysf'] = $p['nPurityMolDigits'];
								$pur['purityunit_id'] = 75;
							} elseif (isset($p['nPurityVol'])) {
								$pur['type'] = 'compound';
								$pur['purity'] = $p['nPurityVol'];
								$pur['puritysf'] = $p['nPurityVolDigits'];
								$pur['purityunit_id'] = 76;
							} elseif (isset($p['nUnknownPerCent'])) {
								$pur['type'] = 'unknown';
								$pur['purity'] = $p['nUnknownPerCent'];
								$pur['puritysf'] = $p['nUnknownPerCentDigits'];
								$pur['purityunit_id'] = 77;
							} elseif (isset($p['nWaterMassPerCent'])) {
								$pur['type'] = 'water';
								$pur['purity'] = $p['nWaterMassPerCent'];
								$pur['puritysf'] = $p['nWaterMassPerCentDigits'];
								$pur['purityunit_id'] = 20;
							} elseif (isset($p['nWaterMolPerCent'])) {
								$pur['type'] = 'water';
								$pur['purity'] = $p['nWaterMolPerCent'];
								$pur['puritysf'] = $p['nWaterMolPerCentDigits'];
								$pur['purityunit_id'] = 75;
							} elseif (isset($p['nHalideMolPerCent'])) {
								$pur['type'] = 'halide';
								$pur['purity'] = $p['nHalideMolPerCent'];
								$pur['puritysf'] = $p['nHalideMolPerCentDigits'];
								$pur['purityunit_id'] = 75;
							} elseif (isset($p['nHalideMassPerCent'])) {
								$pur['type'] = 'halide';
								$pur['purity'] = $p['nHalideMassPerCent'];
								$pur['puritysf'] = $p['nHalideMassPerCentDigits'];
								$pur['purityunit_id'] = 20;
							} else {
								$pur['type'] = 'undefined';
								$pur['purity'] = null;
								$pur['puritysf'] = null;
								$pur['purityunit_id'] = null;
							}

							if (isset($p['eAnalMeth'])) {
								if (is_array($p['eAnalMeth'])) {
									$pur['analmeth'] = $p['eAnalMeth'];
								} else {
									$pur['analmeth'][] = $p['eAnalMeth'];
								}
							} elseif (isset($p['sAnalMeth'])) {
								if (is_array($p['sAnalMeth'])) {
									$pur['analmeth'] = $p['sAnalMeth'];
								} else {
									$pur['analmeth'][] = $p['sAnalMeth'];
								}
							} else {
								$pur['analmeth'] = null;
							}
							if (isset($p['ePurifMethod'])) {
								if (is_array($p['ePurifMethod'])) {
									$pur['purimeth'] = $p['ePurifMethod'];
								} else {
									$pur['purimeth'][] = $p['ePurifMethod'];
								}
							} elseif (isset($p['sPurifMethod'])) {
								if (is_array($p['sPurifMethod'])) {
									$pur['purimeth'] = $p['sPurifMethod'];
								} else {
									$pur['purimeth'][] = $p['sPurifMethod'];
								}
							} else {
								$pur['purimeth'] = null;
							}
							$chem['purity'][] = $pur;
						}
					}
				} else {
					$chem['source'] = null;
					$chem['purity'] = null;
				}
				// Get CASRN from commonchemistry or PubChem
				$sub['casrn']=null;
				if(isset($sub['inchikey'])) {
					$json=file_get_contents($this->ccapi.$sub['inchikey']);
					$cc=json_decode($json,true);
					if($cc['count']>0) {
						$sub['casrn']=$cc['results'][0]['rn'];
					} else {
						$found=$this->Compound->getcas($sub['inchikey']);
						if($found) {
							$sub['casrn']=$found;
						}
					}
				}
				$subs[$chem['orgnum']] = $sub;
				$chems[$chem['orgnum']] = $chem;
			}

			// add reference if not already present in the 'references' database table
			$refs = $this->Reference->find('list', ['fields' => ['doi', 'id']]);
			$refid = null;
			if (!isset($refs[$doi])) {
				$ref = $this->Reference->addbydoi($doi);
				// padded so it can be used to get reference title from indexed array below
				$refid = str_pad($ref['id'],5,'0',STR_PAD_LEFT);
				// add title field from the XML file as issues with crossref title data
				$this->Reference->id = $refid;
				$this->Reference->saveField('titlexml',$title);
			} else {
				$refid = $refs[$doi];
			}

			// add file if not already present in the 'files' database table
			$meta = [];$cite = $trc['Citation'];
			if(isset($cite['TRCRefID'])) {
				$meta['trcid'] = $this->Reference->trcid($cite['TRCRefID']);
			} else {
				$meta['trcid'] = null;
			}
			$meta['title'] = $cite['sTitle'];
			$meta['abstract'] = $cite['sAbstract'];
			$meta['date'] = $cite['dateCit'];
			$meta['year'] = $cite['yrPubYr'];
			$meta['reference_id'] = $refid;
			$meta['filename'] = $filename;
			$journal =  $trc['Citation']['sPubName'];
			if($journal=='J.Chem.Eng.Data') { $journal='J. Chem. Eng. Data'; }
			$meta['journal'] = $journal;
			$meta['journal_id'] = $this->Journal->getfield('id', $journal);
			$fid = $this->File->add($meta);

			// add substances and/or get substance ids ($subs variable updated by reference)
			// https://www.php.net/manual/en/language.references.pass.php
			$subids = $names = [];
			foreach ($subs as $orgnum => $sub) {
				$found=$this->Substance->find('first',['conditions'=>['inchikey'=>$sub['inchikey']]]);
				if($found) {
					$subid=$found['Substance']['id'];
				} else {
					// get substance info from PubChem
					$cid=$this->Compound->cid('inchikey',$sub['inchikey']);
					if($cid) {
						$data=$this->Compound->allcid($cid);
						$data['pubchemid']=$cid;$casrn=null;
						if(is_null($sub['casrn'])) {
							// search common chemistry
							$casrn=$this->Cas->search($sub['inchikey']);
							if(!$casrn) {
								// search pubchem

						$casrn=$this->Compound->getcas($sub['inchikey']);
								if(!$casrn) { $casrn=null; }
							}
							$sub['casrn']=$casrn;
						}
					}
					else {
						$data=['pubchemid'=>null,'formula'=>null,'mw'=>null,'csmiles'=>null,'ismiles'=>null,'iupacname'=>null];
					}
					$cnds=['name'=>$sub['name'],'formula'=>$sub['formula'],
						'mw'=>$data['mw'],'casrn'=>$sub['casrn'],'inchikey'=>$sub['inchikey']];
					$subid=$this->Substance->add($cnds);

					// add identifiers
					$idents=['inchi'=>$sub['inchi'],'inchikey'=>$sub['inchikey'],'casrn'=>$sub['casrn'],
						'csmiles'=>$data['csmiles'],'ismiles'=>$data['ismiles'],'pubchemId'=>$data['pubchemid'],
						'iupacname'=>$data['iupacname']];
					foreach($idents as $type=>$value) {
						if(!empty($value)) {
							$cnds=['substance_id'=>$subid,'type'=>$type,'value'=>$value];
							$this->Identifier->add($cnds);
						}
					}
				}
				$subids[$orgnum] = str_pad($subid,5,'0', STR_PAD_LEFT);
				$names[$orgnum] = $sub['name'];
			}

			// add chemicals (samples of substances) if not present
			$chmids = [];
			foreach($chems as $orgnum => $chem) {
				$chem['file_id'] = $fid;
				$chem['substance_id'] = $subids[$chem['orgnum']];
				$chem['orgnum']=$orgnum;
				$chem['sourcetype']=$chem['source'];unset($chem['source']);
				if(!empty($chem['purity'])) {
					$chem['purity'] = json_encode($chem['purity']);
				} else {
					$chem['purity'] = null;
				}
				$chmid = $this->Chemical->add($chem);
				$chmids[$orgnum]=$chmid;
			}

			// add report to the 'reports' database table
			$compcnt=count($chems);$props=[];$trcdatacnt=0;
			$psetcnt=count($trc['PureOrMixtureData']);
			if(!isset($trc['PureOrMixtureData'][0])) {
				$psets=[0=>$trc['PureOrMixtureData']];
			} else {
				$psets=$trc['PureOrMixtureData'];
			}
			foreach($psets as $pset) {
				if(!isset($pset['Quantity'][0])) { $pset['Quantity']=[0=>$pset['Quantity']]; }
				foreach($pset['Quantity'] as $prop) {
					$info=$this->getpropinfo($prop);
					if(stristr($info['name'],', ')) {
						list($prp,) = explode(', ',$info['name']);
					} else {
						$prp=$info['name'];
					}
					if(stristr($prp,'Henry')) {
						$props[]=$prp;
					} else {
						$props[]=lcfirst($prp);
					}
				}
			}
			$props=array_unique($props);
			// use the array below to identify that the data is a solubility measurement
			$solprops=['molality','mass concentration','ratio of amount of solute to mass of solution',
				'mass ratio of solute to solvent','amount concentration (molarity)','mole fraction',
				'Amount ratio of solute to solvent','Henry\'s Law constant (mole fraction scale)',
				'Henry\'s Law constant (molality scale)','Henry\'s Law constant (amount concentration scale)',
				'Bunsen coefficient','Ostwald coefficient'];
			foreach($props as $pidx=>$prop) {
				if(in_array($prop,$solprops)) { $props[$pidx]='solubility ('.$prop.')'; }
			}
			$propstr="";
			foreach($props as $pidx=>$prop) {
				if($pidx>0) {
					if($pidx==count($props)-1) {
						$propstr.=' and ';
					} else {
						$propstr.=', ';
					}
				}
				$propstr.=$prop;
			}

			$ref=$this->Reference->find('list',['fields'=>['id','title'],'conditions'=>['id'=>$refid]]);
			$psetstr=""; if($psetcnt>0) { $psetstr=$psetcnt." datasets of compound/mixture data"; }
			$desc='Paper containing '.$psetstr.' about '.$compcnt.' compounds determining '.$propstr;
			$conds=['title'=>'Report on paper "'.$ref[$refid].'"','description'=>$desc,'file_id'=>$fid,'reference_id'=>$refid];
			$repid=$this->Report->add($conds);

			// add experimental data (from the <PureOrMixtureData> XML elements)
			$repdatacnt=0;
			$sets = $trc['PureOrMixtureData'];
			if (!isset($sets[0])) { $sets = [0 => $sets]; }
			foreach($sets as $set) {
				$setnum = $set['nPureOrMixtureDataNumber'];
				//debug($set);exit;
				// get components
				$coms = $set['Component'];$comids = [];$cnames = [];
				if (!isset($coms[0])) { $coms = ['0' => $coms]; }
				foreach ($coms as $idx=>$com) {
					$comnum = (int)$idx+1;
					$orgnum = $com['RegNum']['nOrgNum'];
					$comids[$comnum] = $subids[$orgnum];
					$cnames[$comnum] = $names[$orgnum];
				}

				// get phase
				$phases = null;
				if (isset($set['PhaseID'])) {
					if (isset($set['PhaseID'][0])) {
						foreach ($set['PhaseID'] as $p) {
							$phases[] = $p['ePhase'];
						}
					} else {
						$phases[] = $set['PhaseID']['ePhase'];
					}
				}

				// add system
				asort($comids); // sort by lowest first
				if (count($comids) == 1) {
					$idstr = $comids[1];
				} else {
					$idstr = implode(":", $comids);
				}
				$sysid = $this->getsysid($idstr,$cnames,$phases);

				// add dataset
				$cnds=['title'=>'Dataset '.$setnum.' in paper '.$doi,'setnum'=>$setnum,
					'file_id'=>$fid,'report_id'=>$repid, 'system_id'=>$sysid, 'reference_id'=>$refid,'trcidset_id'=>$meta['trcid'].'-'.$setnum];
				$setcnt=null;
				$setid=$this->Dataset->add($cnds,$setcnt); // updated by the function and returned by reference
				// assume that the dataset is complete if point count > 0
				if($setcnt>0) {
					echo 'Dataset '.$setnum.' already complete<br/>';
					$trcdatacnt += $setcnt;
					$repdatacnt += $setcnt;continue;
				}


				// add mixture
				$cnds=['system_id'=>$sysid,'dataset_id'=>$setid];
				$mixid=$this->Mixture->add($cnds);

				// add phases
				$phsids=[];
				foreach($phases as $p) {
					$ptid=$this->Phasetype->find('list',['fields'=>['name','id'],'conditions'=>['name'=>$p]]);
					$orgnum=null; if(isset($p['RegNum'])) { $orgnum=$p['RegNum']['nOrgNum']; }
					$cnds=['mixture_id'=>$mixid,'phasetype_id'=>$ptid[$p],'orgnum'=>$orgnum];
					//debug($ptid);exit;
					$phsid=$this->Phase->add($cnds);
					$phsids[$p]=$phsid;
				}

				// add components
				$tmpcomids=$comids;
				ksort($comids); $cmpids=[]; // re-sort so components are added in numerical order
				foreach ($comids as $comnum=>$subid) {
					$orgnum=array_search($subid,$subids); // get the orgnum for this subid
					$cnds=['chemical_id'=>$chmids[$orgnum],'mixture_id'=>$mixid,'compnum'=>$comnum];
					$cmpid=$this->Compohnent->add($cnds);
					$cmpids[$orgnum]=$cmpid;
				}

				// get the sample properties
				$props = $set['Quantity'];$phasearray = [];
				if (!isset($props[0])) { $props = ['0' => $props]; }
				foreach ($props as $prop) {
					$orgnum = $solorgnum = $uncnum = $unceval = $uncconf = null;
					$propnum = $prop['nPropNumber'];
					if (isset($prop['Property-MethodID']['RegNum']['nOrgNum'])) {
						$orgnum = $prop['Property-MethodID']['RegNum']['nOrgNum'];
					}
					$pinfo=$this->getpropinfo($prop);
					$propname = $pinfo['name'];
					$propgroup = $pinfo['group'];
					$pu = $this->getpropunit($propname);
					list($propid,$unitid)=explode(':',$pu);
					$methname = $pinfo['method'];
					$phase = $prop['PropPhaseID']['ePropPhase'];
					$phasearray[] = $phase;
					$pres = $prop['ePresentation'];
					if (isset($prop['Solvent'])) {
						$solvents = $prop['Solvent']['RegNum'];
						if (isset($solvents[0])) {
							$temp = [];
							foreach ($solvents as $s) {
								$temp[] = $s['nOrgNum'];
							}
							$solorgnum = implode(':',$temp);
						} else {
							$solorgnum = $solvents['nOrgNum'];
						}
					}
					// these should have been from Combined Uncertainty, not PropUncertainty
					// see admin function adduncert
					if (isset($prop['PropUncertainty']['nUncertAssessNum'])) {
						$uncnum = $prop['PropUncertainty']['nUncertAssessNum'];
					}
					if (isset($prop['PropUncertainty']['sUncertEvaluator'])) {
						$unceval = $prop['PropUncertainty']['sUncertEvaluator'];
					}
					if (isset($prop['PropUncertainty']['nUncertLevOfConfid'])) {
						$uncconf = $prop['PropUncertainty']['nUncertLevOfConfid'];
					}
					$cnds = ['dataset_id' => $setid, 'propnum' => $propnum, 'orgnum' => $orgnum,
						'quantity_group' => $propgroup, 'quantity_name' => $propname,
						'quantity_id' => $propid, 'unit_id' => $unitid, 'method_name' => $methname,
						'phase' => $phase, 'presentation' => $pres, 'solventorgnum' => $solorgnum,
						'uncnum' => $uncnum, 'unceval' => $unceval, 'uncconf' => $uncconf];
					$this->Sampleprop->add($cnds);
					// $sprpids[$propnum]=$sprpid;
					// padding the string as the ids do not come zerofill from the code above...
					// $proparray[$propnum] = str_pad($propid, 5, '0', STR_PAD_LEFT).":".str_pad(str_pad($unitid, 5, '0', STR_PAD_LEFT), 5, '0');
				}

				// update the system based on phase data (if needed)
				$this->getsysid($idstr, $cnames, $phasearray);

				// create series condition (constraints) data arrays (saved to add to series later)
				$sconds = [];
				if (isset($set['Constraint'])) {
					$serconds = $set['Constraint'];
					if (!isset($serconds[0])) { $serconds = [0 => $serconds]; }
					foreach ($serconds as $scidx => $sercond) {
						$propname=null;$unitid=null;$comid=null;$cmpid=null;$phsid=null;
						$ctype = $sercond['ConstraintID']['ConstraintType'];
						$propname = $this->getpropname($ctype);
						$pu = $this->getpropunit($propname);
						list($propid, $unitid) = explode(":", $pu);
						if(is_null($unitid)) { echo "Unitid is 'null'";exit; }
						$number = $sercond['nConstraintValue'];
						$sf = $sercond['nConstrDigits'];
						if(isset($sercond['ConstraintID']['RegNum'])) {
							$orgnum=$sercond['ConstraintID']['RegNum']['nOrgNum'];
							if(isset($cmpids[$orgnum])) {
								$cmpid=$cmpids[$orgnum];
							} else {
								echo "could not find comid from orgnum";debug($comids);debug($cmpids);debug($tmpcomids);exit;
							}
						}
						if(isset($sercond['ConstraintPhaseID'])) {
							$phase=$sercond['ConstraintPhaseID']['eConstraintPhase'];
							$phsid=$phsids[$phase];
						}

						// Get sci notation data for value
						$e = $this->Dataset->exponentialGen($number);
						// create data to save - series_id placeholder for later
						$sconds[$scidx] = ['dataset_id'=>$setid, 'dataseries_id'=>null,'datapoint_id'=>null,
							'quantity_id'=>$propid,'system_id'=>$sysid,'quantity_name'=>$propname,
							'number'=>$number,'component_id'=>$cmpid,'phase_id'=>$phsid,
							'significand'=>$e['significand'],'exponent'=>$e['exponent'],'error'=>$e['error'],
							'error_type'=>'absolute','unit_id'=>$unitid,'accuracy'=>$sf,'text'=>$number];
					}
				}

				// analyze data for series to pull out (max) one additional series condition
				$vals = $digits = $counts = [];$scondnum = $scondvals = null;
				if (isset($set['NumValues'])) {
					if (!isset($set['NumValues'][0])) { $set['NumValues'] = [0 => $set['NumValues']]; }
					foreach ($set['NumValues'] as $point) {
						if(isset($point['VariableValue'])) {
							if (!isset($point['VariableValue'][0])) {
								$point['VariableValue'] = [0 => $point['VariableValue']];
							}
							foreach ($point['VariableValue'] as $var) {
								$cidx = $var['nVarNumber'];
								$digits[$cidx][] = $var['nVarDigits'];
								$vals[$cidx][] = $var['nVarValue'];
							}
						}
					}
				}
				if (!empty($vals)) {
					foreach ($vals as $cidx => $all) { $vals[$cidx] = array_unique($all); }
					foreach ($vals as $cidx => $unique) { $counts[$cidx] = count($unique); }
					$min = min($counts);
					$max = max($counts);
					if ($min != $max) {
						$scondnum = array_search($min, $counts);
						$scondvals = array_values($vals[$scondnum]);
					}
				}

				// create conditions templates (number values and dataseries will be added later)
				$conds = [];
				if (isset($set['Variable'])) {
					$cnds = $set['Variable'];
					if (!isset($cnds[0])) { $cnds = [0 => $cnds]; }
					foreach ($cnds as $cnd) {
						$comid = $phsid = null;
						$ctype = $cnd['VariableID']['VariableType'];
						$propname = $this->getpropname($ctype);
						$pu = $this->getpropunit($propname);
						list($propid, $unitid) = explode(":", $pu);
						// ERR1: error in name of condition variable (was $cond instead of $cnd)
						if (isset($cnd['VariableID']['RegNum']['nOrgNum'])) {
							$comid = $cmpids[$cnd['VariableID']['RegNum']['nOrgNum']];
						}
						// ERR1: error in name of condition variable (was $cond instead of $cnd)
						if (isset($cnd['VarPhaseID'])) { // only has one if present
							$phsid = $phsids[$cnd['VarPhaseID']['eVarPhase']];
						}
						$conds[$cnd['nVarNumber']]=['dataset_id'=>$setid, 'dataseries_id'=>null,'datapoint_id'=>null,
							'quantity_id'=>$propid,'system_id'=>$sysid,'quantity_name'=>$propname,
							'component_id'=>$comid,'phase_id'=>$phsid,'unit_id'=>$unitid];
					}
				}

				// create series and add series conditions
				$serids = [];
				if (is_null($scondnum)) {
					// add dataseries
					$cnds = ['dataset_id'=>$setid,'idx'=>1];
					$serid=$this->Dataseries->add($cnds);
					// add 'constraint' series conditions
					foreach ($sconds as $scond) {
						$scond['dataseries_id'] = $serid;
						$this->Condition->add($scond);
					}
					$serids[1]=$serid;
				} else {
					$sercnt=1;
					// if there are series conditions derived from 'variable' conditions, create all series
					foreach ($scondvals as $scondval) {
						// add dataseries
						$cnds = ['dataset_id'=>$setid,'idx'=>$sercnt];
						$serid=$this->Dataseries->add($cnds);

						// add 'constraint' series conditions
						foreach ($sconds as $scond) {
							$scond['dataseries_id'] = $serid;
							$this->Condition->add($scond);
						}

						// add 'variable' series condition
						$scond = $conds[$scondnum];
						$e = $this->Dataset->exponentialGen($scondval);
						$scond['dataseries_id']=$serid;
						$scond['accuracy']=$e['dp'];
						$scond['significand']=$e['significand'];
						$scond['number']=$e['scinot'];
						$scond['error']=$e['error'];
						$scond['error_type']='absolute';
						$scond['exponent']=$e['exponent'];
						$scond['text']=$scondval;
						$this->Condition->add($scond);

						$serids[$sercnt] = $serid;
						$sercnt++;
					}
				}
				$setdatcnt=0;
				foreach ($serids as $scidx => $serid) {
					// get and process the data
					$data = $set['NumValues'];$serdatcnt=0;$dataidx=1;
					if (!isset($data[0])) { $data = [0 => $data]; }
					foreach ($data as $datum) {
						// only add data to this series that has the correct scond value...
						// assumes scondnums are always in numeric sequence
						if (!is_null($scondnum)) {
							if ($datum['VariableValue'][($scondnum - 1)]['nVarValue'] != $scondvals[($scidx-1)]) {
								continue;
							}
						}

						// Add datapoint
						$cnds = ['dataset_id' => $setid, 'dataseries_id' => $serid, 'row_index' =>$dataidx];
						$pntid=$this->Datapoint->add($cnds);$trcdatacnt++;

						// add remaining condition(s) ($vars)
						if (isset($datum['VariableValue'])) {
							$vars = $datum['VariableValue'];
							if (!isset($vars[0])) { $vars = [0 => $vars]; }
							foreach ($vars as $var) {
								// $scondnum contains the 'Variable' that has been added as a series condition
								if ($var['nVarNumber'] == $scondnum) { continue; }

								// regular condition(s)
								// get the prefilled data from the $conds array created above
								$cond = $conds[$var['nVarNumber']];

								// format the error based on the difference in sig figs in number and digits
								$value=$var['nVarValue'];$sf=(int) $var['nVarDigits'];$tmp=null;
								if($value<1) {
									$tmp=preg_replace('/0\.0*/','',$value);
								} else {
									$tmp=str_replace('.','',$value);
								}
								$dgs=strlen($tmp);$diff=$sf-$dgs;
								//debug($value);debug($diff);exit;

								// Get sci notation data for value
								$e = $this->Dataset->exponentialGen($value);
								// $cond['dataseries_id']=$serid; dont link datapoint conditions to the series
								$cond['datapoint_id']=$pntid;
								$cond['number']=$e['scinot'];
								$cond['significand']=$e['significand'];
								$cond['exponent']=$e['exponent'];
								$cond['error']=$e['error']/pow(10,$diff);
								$cond['error_type']='absolute';
								$cond['accuracy']=$var['nVarDigits'];
								$cond['text']=$var['nVarValue'];
								$this->Condition->add($cond);
							}
						}

						// add data
						$edata = $datum['PropertyValue'];
						if (!isset($edata[0])) { $edata = [0 => $edata]; }
						foreach ($edata as $edatum) {
							$propnum = $edatum['nPropNumber'];
							$value = $edatum['nPropValue'];
							$acc = $edatum['nPropDigits'];
							$err = $this->getuncert($edatum);

							// get property from sampleprop
							$cnds = ['dataset_id'=>$setid, 'propnum' => $propnum];
							$sprop = $this->Sampleprop->find('first', ['conditions' => $cnds, 'recursive' => -1]);
							if(!$sprop) { echo 'Sampleprop not found';debug($edatum);exit; }
							$sprop = $sprop['Sampleprop'];
							$spropid = $sprop['id'];
							$propname = $sprop['quantity_name'];
							$propid = $sprop['quantity_id'];
							$unitid = $sprop['unit_id'];
							$orgnum = $sprop['orgnum'];
							$phase = $sprop['phase'];

							// get component, phase from mixture
							$cptid = $phsid = null;$c=['Phase'=>['Phasetype'],'Compohnent'=>['Chemical']];
							$mix=$this->Mixture->find('first',['conditions'=>['id'=>$mixid],'contain'=>$c,'recursive'=>-1]);
							foreach($mix['Compohnent'] as $cpt) {
								if($cpt['Chemical']['orgnum']==$orgnum) { $cptid=$cpt['id']; }
							}
							foreach($mix['Phase'] as $phs) {
								if($phs['Phasetype']['name']==$phase) { $phsid=$phs['id']; }
							}
							//debug($mix);exit;
							// get sci notation data for value
							$e = $this->Dataset->exponentialGen($value);
							$cnds = ['dataset_id' => $setid, 'dataseries_id' => $serid, 'datapoint_id' => $pntid,
								'quantity_id' => $propid,'quantity_name'=>$propname, 'sampleprop_id' => $spropid,
								'number'=>$e['scinot'],'component_id'=>$cptid,'phase_id'=>$phsid,
								'significand'=>$e['significand'],'exponent'=>$e['exponent'],
								'error'=>$err,'unit_id'=>$unitid,'accuracy'=>$acc,'text'=>$value];
							$this->Data->add($cnds);
						}
						$serdatcnt++;$dataidx++;
					}

					// update series with # datapoints
					$update=['Dataseries'=>['id'=>$serid,'points'=>$serdatcnt]];
					$this->Dataseries->save($update);

					$setdatcnt+=$serdatcnt;
				}

				// update dataset with # datapoints
				$update=['Dataset'=>['id'=>$setid,'points'=>$setdatcnt]];
				$this->Dataset->save($update);

				$repdatacnt+=$setdatcnt;

				echo '<a href="/trc/newsets/view/'.$setid.'">'.$setnum.'</a>...';
			}

			// update report with # datapoints
			$update=['Report'=>['id'=>$repid,'points'=>$repdatacnt]];
			$this->Report->save($update);

			// Add datapoint stats to files table
			$pntcnts=$this->Dataset->find('list',['fields'=>['id','points'],'conditions'=>['file_id' => $fid],'recursive'=>-1]);
			if(array_sum($pntcnts)!=$trcdatacnt) {
				echo "mismatch on number of datapoints from file<br/>";
				echo "sum of dataseries counts: ".array_sum($pntcnts)."; TRC count: ".$trcdatacnt;exit;
			}
			$this->File->id=$fid;
			$this->File->saveField('points',$trcdatacnt);

			// data to data_systems table
			$c=['Dataseries'=>['Datapoint'=>['Data']]];$datums=[];
			$data=$this->Dataset->find('all',['conditions'=>['file_id' => $fid],'contain'=>$c,'recursive'=>-1]);
			foreach($data as $set) {
				foreach($set['Dataseries'] as $ser) {
					foreach($ser['Datapoint'] as $pnt) {
						foreach($pnt['Data'] as $datum) {
							$datums[]=$datum['id'];
						}
					}
				}
			}
			foreach($datums as $did) { $this->Data->joinsys('id',$did); }
			echo "File " . $filename . " added (".$trcdatacnt." points)<br />";

			// indicate that the file is done
			$this->File->id=$fid;
			$this->File->saveField('comments','done');

			// check for problems
			$conperr=$this->Condition->find('list',['fields'=>['id','datapoint_id'],'conditions'=>['quantity_id'=>null]]);
			if(!empty($conperr)) { echo 'Conditions property error(s)';debug($conperr); }
			$conuerr=$this->Condition->find('list',['fields'=>['id','datapoint_id'],'conditions'=>['unit_id'=>null]]);
			if(!empty($conuerr)) { echo 'Conditions unit error(s)';debug($conuerr); }
			$datperr=$this->Data->find('list',['fields'=>['id','datapoint_id'],'conditions'=>['quantity_id'=>null]]);
			if(!empty($datperr)) { echo 'Data property error(s)';debug($datperr); }
			$datuerr=$this->Data->find('list',['fields'=>['id','datapoint_id'],'conditions'=>['unit_id'=>null]]);
			if(!empty($datuerr)) { echo 'Data unit error(s)';debug($datuerr); }
			if(!empty($conperr)||!empty($conuerr)||!empty($datperr)||!empty($datuerr)) { exit; }

			// check if we have processed enough files...
			if($count==$maxfiles) { exit; }
		}
		exit;
	}

	/**
	 * delete a file (and all data derived from it)
	 * uses model associations and dependent=true
	 * @param int $id
	 * @return void
	 */
	public function delete(int $id)
	{
		if($this->File->delete($id)) {
			$this->Flash->set('File '.$id.' deleted!');
		} else {
			$this->Flash->set('File '.$id.' could not be deleted!');
		}
		$this->redirect('/files/index');
	}

	/**
	 * check entries added to the database
	 */
	public function check()
	{
		$path = WWW_ROOT . 'files' . DS . 'trc'. DS . 'jced'. DS . 'aadone';
		$maindir = new Folder($path);
		$files = $maindir->find('^.*\.xml$',true);
		// check files in aadone...
		echo "Files in aadone<br/>";
		foreach ($files as $file) {
			if(!$this->File->find('first',['conditions'=>['filename'=>$file],'recursive'=>-1])) {
				echo 'Filename '.$file.' not found!<br/>';
			}
		}
		// check entries in files DB
		$done=$this->File->find('list',['fields'=>['id','filename']]);
		echo "Files in database<br/>";
		foreach($done as $filename) {
			if(!in_array($filename,$files)) {
				echo 'Filename '.$filename.' not found!<br/>';
			}
		}
		exit;
	}

	/**
	 * function to fix missing component_id and phase_id values in conditions
	 * this was due to a coding error in ingest function -> ERR1 above
	 * run once
	 * @param int $maxfiles
	 */
	public function fixcond(int $maxfiles=1)
	{
		$path = WWW_ROOT.'files'.DS.'trc'.DS.'jct'.DS;
		$maindir = new Folder($path);$count=0;
		$xfiles = $maindir->find('^.*\.xml$',true);
		$files = $this->File->find('list', ['fields' => ['id','filename']]);

		$done = $this->File->find('list', ['fields' => ['id','filename'],'conditions'=>['cndchk'=>'yes']]);
		$phts = $this->Phasetype->find('list',['fields'=>['name','id']]);
		foreach($xfiles as $filename) {
			if(in_array($filename,$done)) { continue; }
			$fid = array_search($filename,$files);
			// get data from XML
			$filepath = $path.$filename;
			$xml = simplexml_load_file($filepath);
			$trc = json_decode(json_encode($xml), true);

			// get the orgnum(s) of variables/constraints if available
			$sdone = $this->Dataset->find('list',['fields'=>['id','setnum'],'conditions'=>['file_id'=>$fid,'cndchk'=>'yes']]);
			if(!isset($trc['PureOrMixtureData'])) { continue; }
			if(!isset($trc['PureOrMixtureData'][0])) { $trc['PureOrMixtureData']=[0=>$trc['PureOrMixtureData']]; }
			foreach($trc['PureOrMixtureData'] as $set) {
				// to update the correct rows in conditions we need
				// dataset_id, quantity_id, and component_id and optionally phase_id
				$sconds=[];$conds=[];
				$setnum = $set['nPureOrMixtureDataNumber'];
				// comment out the next line for alternate
				if(in_array($setnum,$sdone)) { echo "Dataset ".$filename.":".$setnum." already completed<br/>";continue; }
				// get dataset id
				$dset = $this->Dataset->find('first',['conditions'=>['file_id'=>$fid,'setnum'=>$setnum],'recursive'=>-1]);
				$dsid = $dset['Dataset']['id'];
				//debug($filename);debug($dsid);//exit;
				// search constraints for regnum
				if(isset($set['Constraint'])) {
					if(!isset($set['Constraint'][0])) { $set['Constraint'] = [0=>$set['Constraint']]; }
					foreach($set['Constraint'] as $cst) {
						if(isset($cst['ConstraintID']['RegNum'])) {
							$scond = [];
							$scond['num'] = $cst['nConstraintNumber'];
							// use implode to get single property description because array key is variable
							$scond['prop'] = implode(',',$cst['ConstraintID']['ConstraintType']);
							$scond['orgnum'] = $cst['ConstraintID']['RegNum']['nOrgNum'];
							$scond['phase'] = null;
							if(isset($cst['ConstraintPhaseID'])) {
								$scond['phase'] = $cst['ConstraintPhaseID']['eConstraintPhase'];
							}
							$sconds[]=$scond;
						}
					}
					foreach($sconds as $scond) {
						$phsid=$phstype=null;
						// get quantity_id
						$prop = $this->Quantity->find('first',['conditions'=>['field like'=>'%"'.$scond['prop'].'"%'],'recursive'=>-1]);
						if(!empty($prop)) { $prpid = $prop['Quantity']['id']; } else { echo "Quantity not found!";debug($prop);exit; }
						// get conditions that need to be updated...
						$cnds = $this->Condition->find('all',['conditions'=>['dataset_id'=>$dsid,'quantity_id'=>$prpid],'recursive'=>-1]);
						foreach($cnds as $cnd) {
							$issues=0;$cnd=$cnd['Condition'];
							if(is_null($cnd['component_id'])) {
								echo "Missing component_id<br/>";$issues++;
							}
							if(!is_null($scond['phase'])&&is_null($cnd['phase_id'])) {
								echo "Missing phase_id<br/>";$issues++;
							}
							if($issues>0) {
								// get mixture
								$mix = $this->Mixture->find('first',['conditions'=>['dataset_id'=>$dsid],'recursive'=>-1]);
								if(!empty($mix)) { $mixid = $mix['Mixture']['id']; } else { echo "Mixture not found!";exit; }
								// get components
								$cmps = $this->Compohnent->find('list',['fields'=>['id','chemical_id'],'conditions'=>['mixture_id'=>$mixid]]);
								if(empty($cmps)) { echo "Components not found!";exit; }
								// get all chemicals for those components
								$chms = $this->Chemical->find('list',['fields'=>['orgnum','id'],'conditions'=>['id'=>$cmps]]);
								// get phase_id if required
								if(!is_null($scond['phase'])) {
									$phstype=$phts[$scond['phase']];
									// get phases for this mixture
									$phss = $this->Phase->find('list',['fields'=>['phasetype_id','id'],'conditions'=>['mixture_id'=>$mixid]]);
									if(isset($phss[$phstype])) { $phsid=$phss[$phstype]; } else { echo "Phase ".$scond['phase']." not found!";exit; }
								}
								// get component_id - gets chmid from chms variable using the orgnum
								// and then searches the cmps array for the key (component_id) that matches that chmid
								$cmpid = array_search($chms[$scond['orgnum']],$cmps);
								//echo "Correct?";debug($cnd);debug($cmpid);debug($phsid);exit;
								$this->Condition->id=$cnd['id'];
								$this->Condition->saveField('component_id',$cmpid);
								if(!is_null($phsid)) { $this->Condition->saveField('phase_id',$phsid); }
								//echo "Work on this code!";debug($cnd);exit;
							}
						}
						//echo "OK, everything looks good";debug($scond);debug($cnds);exit;
					}
				}
				// search variables for regnum and phase
				if(isset($set['Variable'])) {
					if(!isset($set['Variable'][0])) { $set['Variable'] = [0=>$set['Variable']]; }
					foreach($set['Variable'] as $var) {
						if(isset($var['VariableID']['RegNum'])) {
							$cond = [];
							$cond['num'] = $var['nVarNumber'];
							// use implode to get single quantity description because array key is variable
							$cond['prop'] = implode(',',$var['VariableID']['VariableType']);
							$cond['orgnum'] = $var['VariableID']['RegNum']['nOrgNum'];
							if(isset($var['VarPhaseID'])) {
								$cond['phase'] = $var['VarPhaseID']['eVarPhase'];
							}
							$conds[]=$cond;
						} elseif(isset($var['VarPhaseID'])) {
							$cond = [];
							$cond['num'] = $var['nVarNumber'];
							// use implode to get single quantity description because array key has varying name
							$cond['prop'] = implode(',',$var['VariableID']['VariableType']);
							$cond['phase'] = $var['VarPhaseID']['eVarPhase'];
							$conds[]=$cond;
						}
					}
					//if(count($conds)>2) { echo "Just checking larger # of conditions with compounds identified";debug($conds);exit; }
					$cndscs=[]; // variable cndscs is for conditions that have been turned into series conditions
					//debug($conds);
					foreach($conds as $cidx=>$cond) {
						$prpid=$phsid=$cmpid=null;$prop=[];
						// get quantity_id
						if(isset($cond['prop'])) {
							$prop = $this->Quantity->find('first',['conditions'=>['field like'=>'%"'.$cond['prop'].'"%'],'recursive'=>-1]);
							if(!empty($prop)) { $prpid = $prop['Quantity']['id']; } else { echo "Quantity not found!";debug($set);exit; }
						}
						// get mixture
						$mix = $this->Mixture->find('first',['conditions'=>['dataset_id'=>$dsid],'recursive'=>-1]);
						if(!empty($mix)) { $mixid = $mix['Mixture']['id']; } else { echo "Mixture not found!";exit; }
						// get component_id if needed
						if(isset($cond['prop'])&&isset($cond['orgnum'])) {
							// get component
							// get all components of the mixture
							$cmps = $this->Compohnent->find('list',['fields'=>['id','chemical_id'],'conditions'=>['mixture_id'=>$mixid]]);
							if(empty($cmps)) { echo "Components not found!";exit; }
							// get all chemicals for those components
							$chms = $this->Chemical->find('list',['fields'=>['orgnum','id'],'conditions'=>['id'=>$cmps]]);
							// get component_id - gets chmid from chms variable using the orgnum
							// and then searches the cmps array for the key (component_id) that matches that chmid
							$cmpid = array_search($chms[$cond['orgnum']],$cmps);
						}
						//debug($cond);debug($phsid);
						// get phase_id if needed
						if(!is_null($cond['phase'])) {
							$phstype=$phts[$cond['phase']];
							// get phases for this mixture
							$phss = $this->Phase->find('list',['fields'=>['phasetype_id','id'],'conditions'=>['mixture_id'=>$mixid]]);
							if(isset($phss[$phstype])) { $phsid=$phss[$phstype]; } else { echo "Phase ".$cond['phase']." not found!";debug($phss);exit; }
						}
						//debug($cond);debug($phsid);
						// get conditions that need to be updated...
						$cndids = $this->Condition->find('list',['fields'=>['id','quantity_id','datapoint_id'],'conditions'=>['dataset_id'=>$dsid,'quantity_id'=>$prpid]]);
						// only update one condition per datapoint - the number of conditions should match the #rows/#datapoints
						// if there are multiple conditions of the same quantity (e.g. mole fraction)
						// then pick the right one from the sequence ($cidx starts at zero)
						if(isset($cndids[0])) {
							// rows found are series conditions (constriants) created from variables
							$scnds = $this->Condition->find('all',['conditions'=>['dataset_id'=>$dsid,'quantity_id'=>$prpid],'recursive'=>-1]);
							//debug($scnds);exit;
							unset($cndids[0]);$cndscs[]=$cidx;
							foreach($scnds as $scnd) {
								$issues=0;$scnd=$scnd['Condition'];
								if(is_null($scnd['component_id'])) {
									echo "Missing component_id<br/>";$issues++;
								}
								if(!is_null($cond['phase'])&&is_null($scnd['phase_id'])) {
									echo "Missing phase_id<br/>";$issues++;
								}
								if($issues>0) {
									//echo "Work on this code!";debug($scnd);debug($cmpid);debug($phsid);exit;
									$this->Condition->id=$scnd['id'];
									$this->Condition->saveField('component_id',$cmpid);
									if(!is_null($phsid)) { $this->Condition->saveField('phase_id',$phsid); }
								}
							}
						}
						if(isset($cndids[1])) { echo "Something else went wrong!";debug($dsid);debug($cndids);exit; }
						foreach($cndids as $cndida) {
							//debug($cndida);
							$cndid=null;
							if(count($cndida)==1) {
								$cndid=implode('',array_keys($cndida));
							} else {
								// multiple conditions of the same type - remove any that have been converted
								// to series conditions and then find the right one by index
								$tmpcnds=$conds;
								foreach($cndscs as $cndsc) { unset($tmpcnds[$cndsc]); }
								$tmpcnds=array_values($tmpcnds);
								// get properties that of this type and then assign based on relative order
								$prptyps=json_decode($prop['Quantity']['field']);
								$fndcnds=[];
								foreach($tmpcnds as $tmpcnd) {
									if(in_array($tmpcnd['prop'],$prptyps)) { $fndcnds[]=$tmpcnd; }
								}
								$csccnt=count($cndscs);
								if(count($fndcnds)==count($cndida)) {
									//debug($cidx);debug($prptyps);debug($fndcnds);
									if(!in_array($cidx,$cndscs)) {
										$keys=array_keys($cndida);
										//debug($keys);
										if(isset($keys[($cidx-$csccnt)])) {
											$cndid=$keys[($cidx-$csccnt)];
										} else {
											echo "Can't disambiguate conditions (mutiple of same type?)";debug($fndcnds);
										}
									}
								} else {
									echo 'Mismatch in # of conditions per datapoint with the same quantity';
									debug($prop);debug($cndida);debug($cndids);debug($conds);debug($tmpcnds);exit;
								}
							}
							//debug($cond);debug($cndid);//debug($cmpid);debug($phsid);exit;
							$this->Condition->id=$cndid;
							if(!is_null($cmpid)) { $this->Condition->saveField('component_id',$cmpid); }
							if(!is_null($phsid)) { $this->Condition->saveField('phase_id',$phsid); }
						}
					}
					//debug($cndscs);exit;
				}
				// indicate that the dataset has been updated
				$this->Dataset->id=$dsid;
				$this->Dataset->saveField('cndchk','yes');
				echo "Dataset ".$filename.':'.$setnum.' ('.$dsid.') done<br/>';
			}

			// indicate that the file has been updated
			$this->File->id=$fid;
			$this->File->saveField('cndchk','yes');
			echo "File ".$filename.' done<br/>';$count++;

			// check if we have processed enough files...
			if($count==$maxfiles) { exit; }
		}
		exit;
	}

	// private functions called by the functions above (not exposed)

	/**
	 * get the quantity group, quantity, and method
	 * @param array $prop
	 * @return array
	 */
	private function getpropinfo(array $prop): array
	{
		$group = $prop['Property-MethodID']['PropertyGroup'];
		$proptype=$propgroup=null;
		if (isset($group['Criticals'])) {
			$proptype = $group['Criticals'];
			$propgroup = 'Criticals';
		} elseif (isset($group['VaporPBoilingTAzeotropTandP'])) {
			$proptype = $group['VaporPBoilingTAzeotropTandP'];
			$propgroup = 'VaporPBoilingTAzeotropTandP';
		} elseif (isset($group['PhaseTransition'])) {
			$proptype = $group['PhaseTransition'];
			$propgroup = 'PhaseTransition';
		} elseif (isset($group['CompositionAtPhaseEquilibrium'])) {
			$proptype = $group['CompositionAtPhaseEquilibrium'];
			$propgroup = 'CompositionAtPhaseEquilibrium';
		} elseif (isset($group['ActivityFugacityOsmoticProp'])) {
			$proptype = $group['ActivityFugacityOsmoticProp'];
			$propgroup = 'ActivityFugacityOsmoticProp';
		} elseif (isset($group['VolumetricProp'])) {
			$proptype = $group['VolumetricProp'];
			$propgroup = 'VolumetricProp';
		} elseif (isset($group['HeatCapacityAndDerivedProp'])) {
			$proptype = $group['HeatCapacityAndDerivedProp'];
			$propgroup = 'HeatCapacityAndDerivedProp';
		} elseif (isset($group['ExcessPartialApparentEnergyProp'])) {
			$proptype = $group['ExcessPartialApparentEnergyProp'];
			$propgroup = 'ExcessPartialApparentEnergyProp';
		} elseif (isset($group['TransportProp'])) {
			$proptype = $group['TransportProp'];
			$propgroup = 'TransportProp';
		} elseif (isset($group['RefractionSurfaceTensionSoundSpeed'])) {
			$proptype = $group['RefractionSurfaceTensionSoundSpeed'];
			$propgroup = 'RefractionSurfaceTensionSoundSpeed';
		} elseif (isset($group['BioProperties'])) {
			$proptype = $group['BioProperties'];
			$propgroup = 'BioProperties';
		}
		$method=null;
		if(isset($proptype['eMethodName'])) { $method=$proptype['eMethodName']; }
		if(isset($proptype['sMethodName'])) { $method=$proptype['sMethodName']; }
		return ['group'=>$propgroup,'name'=>$proptype['ePropName'],'method'=>$method];
	}

	/**
	 * get the quantity string
	 * @param $ctype
	 * @return string
	 */
	private function getpropname($ctype): string
	{
		$propname=null;
		if (isset($ctype['eTemperature'])) {
			$propname = $ctype['eTemperature'];
		} elseif (isset($ctype['ePressure'])) {
			$propname = $ctype['ePressure'];
		} elseif (isset($ctype['eComponentComposition'])) {
			$propname = $ctype['eComponentComposition'];
		} elseif (isset($ctype['eSolventComposition'])) {
			$propname = $ctype['eSolventComposition'];
		} elseif (isset($ctype['eMiscellaneous'])) {
			$propname = $ctype['eMiscellaneous'];
		} elseif (isset($ctype['eBioVariables'])) {
			$propname = $ctype['eBioVariables'];
		}
		return $propname;
	}

	/**
	 * get the uncertainty string
	 * @param array $uncert
	 * @return string|null
	 */
	private function getuncert(array $uncert): string
	{
		$output=null;
		if (isset($uncert['PropUncertainty'])) {
			if(isset($uncert['PropUncertainty']['nStdUncertValue'])) {
				$output = $uncert['PropUncertainty']['nStdUncertValue'];
			} elseif(isset($uncert['PropUncertainty']['nExpandUncertValue'])) {
				$output = $uncert['PropUncertainty']['nExpandUncertValue'];
			} elseif(isset($uncert['PropUncertainty']['AsymStdUncert'])) {
				$pos = $uncert['PropUncertainty']['AsymStdUncert']['nPositiveValue'];
				$neg = $uncert['PropUncertainty']['AsymStdUncert']['nNegativeValue'];
				$output = $pos.' - '.$neg;
			} elseif(isset($uncert['PropUncertainty']['AsymExpandUncert'])) {
				$pos = $uncert['PropUncertainty']['AsymExpandUncert']['nPositiveValue'];
				$neg = $uncert['PropUncertainty']['AsymExpandUncert']['nNegativeValue'];
				$output = $pos.' - '.$neg;
			}
		} elseif (isset($uncert['CombinedUncertainty'])) {
			if(isset($uncert['CombinedUncertainty']['nCombStdUncertValue'])) {
				$output = $uncert['CombinedUncertainty']['nCombStdUncertValue'];
			} elseif(isset($uncert['CombinedUncertainty']['nCombExpandUncertValue'])) {
				$output = $uncert['CombinedUncertainty']['nCombExpandUncertValue'];
			} elseif(isset($uncert['CombinedUncertainty']['AsymCombStdUncert'])) {
				$pos = $uncert['CombinedUncertainty']['AsymCombStdUncert']['nPositiveValue'];
				$neg = $uncert['CombinedUncertainty']['AsymCombStdUncert']['nNegativeValue'];
				$output = $pos.' - '.$neg;
			} elseif(isset($uncert['CombinedUncertainty']['AsymCombExpandUncert'])) {
				$pos = $uncert['CombinedUncertainty']['AsymCombExpandUncert']['nPositiveValue'];
				$neg = $uncert['CombinedUncertainty']['AsymCombExpandUncert']['nNegativeValue'];
				$output = $pos.' - '.$neg;
			}
		}
		return $output;
	}

	/**
	 * get the quantity and unit
	 * @param string $propunit
	 * @return string
	 */
	private function getpropunit(string $propunit): string
	{
		$propid = $this->Quantity->getfield('id', '%"'.$propunit.'"%', 'field like');
		if (stristr($propunit, ', ')) {
			$pu = explode(", ", $propunit);
			$unit = $pu[1];
			$unitid = $this->Unit->getfield('id', '%"'.$unit.'"%', 'header like');
		} else {
			if(stristr($propunit,'mass fraction ')) {
				$unitid=88;
			} elseif(stristr($propunit,'mole fraction ')) {
				$unitid=87;
			} else {
				$unitid=17;
			}
		}
		if(is_null($unitid)) { echo "no unit found for '".$propunit."'";exit;}
		return $propid.":".$unitid;
	}

	/**
	 * get the systemid
	 * @param string $idstr
	 * @param array $cnames
	 * @param array|null $phases
	 * @return mixed
	 */
	private function getsysid(string $idstr,array $cnames,array $phases = null)
	{
		$res = $this->System->find('list', ['fields' => ['id', 'phase'], 'conditions' => ['identifier' => $idstr]]);
		$scount = count($cnames);
		if(empty($res)) {
			$cnds=[];

			// composition
			switch ($scount) {
				case 1:
					$comp = 'pure substance';
					break;
				case 2:
					$comp = 'binary mixture';
					break;
				case 3:
					$comp = 'ternary mixture';
					break;
				case 4:
					$comp = 'quaternary mixture';
					break;
				case 5:
					$comp = 'quinternary mixture';
					break;
				default:
					$comp = null;
			}
			$cnds['composition']=$comp;

			// phase
			$cnds['name']=implode(" + ", $cnames);
			$phase=null;
			if (!is_null($phases)) {
				$phases = array_unique($phases);
				if (count($phases) == 1) {
					if ($scount == 1) {
						if (stristr($phases[0], 'liquid')) {
							$phase = 'liquid';
						} elseif (stristr($phases[0], 'solid')) {
							$phase = 'solid';
						} elseif (stristr($phases[0], 'gas')) {
							$phase = 'gas';
						} else {
							$phase = $phases[0];
						}
					} else {
						$phase = 'solution';
					}
				} elseif (count($phases) == 2) {
					$phase = 'two phase system';
				} elseif (count($phases) > 2) {
					$phase = 'multiphase system';
				}
			}
			$cnds['phase']=$phase;
			$cnds['identifier']=$idstr;
			if($scount==1) {
				$key = $this->Identifier->getfield('value', ['substance_id' => $idstr, 'type' => 'inchikey']);
				$headers = get_headers('https://classyfire.wishartlab.com/entities/' . $key . '.json');
				if (stristr($headers[0], 'OK')) {
					$json = file_get_contents('https://classyfire.wishartlab.com/entities/' . $key . '.json');
					$classy = json_decode($json, true);
					if(!empty($classy)) {
						$kingdom = $classy['kingdom']['name'];
						if($kingdom == 'Inorganic compounds') {
							$superclass = $classy['superclass']['name'];
							if ($superclass == 'Homogeneous metal compounds') {
								$cnds['type'] = 'element';
								$cnds['subtype'] = null;  // elements!
							} else {
								$cnds['type'] = 'compound';
								$cnds['subtype'] = 'inorganic compound';
							}
						} elseif ($kingdom == 'Organic compounds') {
							$cnds['type'] = 'compound';
							$cnds['subtype'] = 'organic compound';
						}
					} else {
						$cnds['type']='compound';
						$cnds['subtype']='not found on classyfire';
					}
				} else {
					$cnds['type'] = 'compound';
					$cnds['subtype'] = 'organic compound*';
				}
			}
			$sysid=$this->System->add($cnds);

			// add substances_systems entries
			$subids = explode(":", $idstr);
			foreach ($subids as $subid) {
				$cnds=['substance_id'=>$subid,'system_id'=>$sysid];
				$this->SubstancesSystem->add($cnds);
			}
			//echo "check that susbtances_system entries worked OK";exit;
		} else {
			if(count($res)>1) {
				foreach ($res as $sysid => $phase) {
					if ($phase == null && $phases != null) {
						$phases = array_unique($phases);
						if (count($phases) == 1) {
							if ($scount == 1) {
								$phase = $phases[0];
							} else {
								$phase = 'Solution';
							}
						} elseif (count($phases) == 2) {
							$phase = 'Two phase system';
						} elseif (count($phases) > 2) {
							$phase = 'Multiphase system';
						}
						$this->System->id = $sysid;
						$this->System->savefield('phase', $phase);
					}
				}
			}
			$sysid=array_key_first($res);
		}
		return $sysid;
	}
}
