<?php

/**
 * Class FilesController
 * @author Stuart Chalk <schalk@unf.edu>
 */
class FilesController extends AppController
{

	public $uses = ['File', 'Error', 'Chemical', 'Sampleprop', 'Identifier', 'Datarectification',
		'System', 'Dataset', 'Condition', 'Dataseries', 'Datapoint', 'Data', 'Reference',
		'Reactionprop', 'Pubchem.Compound', 'Substance', 'SubstancesSystem', 'Crossref.Api',
		'Journal', 'Property', 'Unit', 'Annotation', 'Scidata','NewFile','NewChemical','NewReference',
		'NewSubstance','NewDataset','NewReport','NewPhase','NewDatasetsPhase','NewSampleprop',
		'NewProperty','NewMixture','NewComponent','NewPhasetype','NewDataseries','NewCondition','NewUnit',
		'NewDatapoint','NewData'];

	public $c = [
		'Chemical' => [
			'Substance'],
		'Reference',
		'Dataset' => [
			'Dataseries' => [
				'Condition' => ['Unit',
					'Property' => ['fields' => ['name'],
						'Quantity' => ['fields' => ['name']]]],
				'Datapoint' => [
					'Condition' => ['Unit',
						'Property' => ['fields' => ['name'],
							'Quantity' => ['fields' => ['name']]]],
					'Data' => ['Unit',
						'Property' => ['fields' => ['name'],
							'Quantity' => ['fields' => ['name']]]]
				]
			],
			'Sampleprop',
			'Reactionprop',
			'System' => [
				'Substance' => ['fields' => ['name', 'casrn', 'formula', 'molweight', 'type'],
					'Identifier' => ['fields' => ['type', 'value'], 'conditions' => ['type' => ['inchi', 'inchikey', 'iupacname']]]
				]
			]
		]
	];

	public $ccapi='https://commonchemistry.cas.org/api/search?q=';

	/**
	 * function beforeFilter
	 */
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Auth->allow('index','view');
	}

	// CRUD operations

	/**
	 * show a list of ThermoML files
	 */
	public function index()
	{
		$f=['id','title','year'];$o=['year','title'];
		$data = $this->File->find('list',['fields'=>$f,'order'=>$o,'recursive' => -1]);
		$this->set('data', $data);
	}

	/**
	 * view general information about a TRC XML file
	 * @param int $id
	 */
	public function view($id)
	{
		$data = $this->File->find('first', ['conditions' => ['File.id' => $id], 'contain' => $this->c, 'recursive' => -1]);
		//debug($data);exit;
		$ref = $data['Reference'];
		$sets = $data['Dataset'];
		$this->set('ref',$ref);
		$this->set('sets',$sets);
	}

	/**
	 * delete a file (and all data derived from it)
	 * @param $id
	 * @param $return
	 * @return bool
	 */
	public function delete($id,$return=null)
	{
		if($this->File->delete($id)) {
			$this->Flash->set('File '.$id.' deleted!');
			if($return==null) {
				$this->redirect('/files/index');
			} else {
				$return=1;
			}
		} else {
			$this->Flash->set('File '.$id.' could not be deleted!');
			if($return==null) {
				$this->redirect('/files/index');
			} else {
				$return=0;
			}
		}
		return $return;
	}

	// file ingestion, checking and testing

	/**
	 * Adds ThermoML files to the database to organize them...
	 */
	public function loadfiles()
	{
		$path = WWW_ROOT . 'files' . DS . 'trc';
		$maindir = new Folder($path);
		$files = $maindir->tree();
		foreach ($files[1] as $file) {
			$xml = simplexml_load_file($file);
			$trc = json_decode(json_encode($xml), true);

			// Grab the chemical info
			$compds = $trc['Compound'];
			if (!isset($trc['Compound'][0])) {
				// Make a single compound into an array
				$trc['Compound'] = ['0' => $trc['Compound']];
			}
			foreach ($compds as $comp) {
				$name = $comp['sCommonName'];
				$formula = $comp['sFormulaMolec'];
				$purity = $comp['Sample']['purity']['nPurityMass'];
				$puritysf = $comp['Sample']['purity']['nPurityMassDigits'];
				// Get substance id
				// Get CASRN to send to datarefticiation code
				$url = "https://cactus.nci.nih.gov/chemical/structure/" . $name . "/cas/xml";
				$xml = simplexml_load_file($url);
				$cir = json_decode(json_encode($xml), true);
				if (!empty($cir['data'])) {
					$cas = $cir['data'][0]['item'][0];
					$search = [0 => ['casrn' => $cas, 'name' => $name, 'formula' => $formula]];
					$this->Datarectification->checkAndAddSubstances($search, true);
					$sid = $search[0]['id'];
				} else {
					$sid = 0;
				}

				$data = ['Chemical' => ['name' => $name, 'formula' => $formula, 'substance_id' => $sid, 'purity' => $purity, 'puritysf' => $puritysf]];
				$this->Chemical->create();
				$this->Chemical->save($data);
				debug($comp);
				exit;
			}

			// Get the properties

			// Grab the data
			//debug($trc);exit;

			// Grab the general info
			if (isset($trc['Citation']['sDOI'])) {
				$url = 'http://dx.doi.org/' . $trc['Citation']['sDOI'];
			} else {
				$id = $trc['Citation']['TRCRefID'];
				if (is_array($id['sAuthor2'])) {
					$url = $id['yrYrPub'] . $id['sAuthor1'] . $id['nAuthorn'];
				} else {
					$url = $id['yrYrPub'] . $id['sAuthor1'] . $id['sAuthor2'] . $id['nAuthorn'];
				}
			}
			$result = $this->File->find('first', ['conditions' => ['url' => $url]]);
			if (empty($result)) {
				$title = $trc['Citation']['sTitle'];
				$journal = $trc['Citation']['sPubName'];
				if($journal=='J.Chem.Eng.Data') { $journal='J. Chem. Eng. Data'; }
				$parts = explode("/", $file);
				$filename = $parts[(count($parts) - 1)];
				$props = [];
				$pnts = 0;
				if (isset($trc['PureOrMixtureData'])) {
					if (!isset($trc['PureOrMixtureData'][0])) {
						$trc['PureOrMixtureData'] = [0 => $trc['PureOrMixtureData']];
					}
					foreach ($trc['PureOrMixtureData'] as $set) {
						//debug($set);
						if (!isset($set['Property'][0])) {
							$set['Property'] = [0 => $set['Property']];
						}
						foreach ($set['Property'] as $prop) {
							$group = $prop['Property-MethodID']['PropertyGroup'];
							if (isset($group['Criticals'])) {
								$props[] = $group['Criticals']['ePropName'];
							} elseif (isset($group['VaporPBoilingTAzeotropTandP'])) {
								$props[] = $group['VaporPBoilingTAzeotropTandP']['ePropName'];
							} elseif (isset($group['PhaseTransition'])) {
								$props[] = $group['PhaseTransition']['ePropName'];
							} elseif (isset($group['CompositionAtPhaseEquilibrium'])) {
								$props[] = $group['CompositionAtPhaseEquilibrium']['ePropName'];
							} elseif (isset($group['ActivityFugacityOsmoticProp'])) {
								$props[] = $group['ActivityFugacityOsmoticProp']['ePropName'];
							} elseif (isset($group['VolumetricProp'])) {
								$props[] = $group['VolumetricProp']['ePropName'];
							} elseif (isset($group['HeatCapacityAndDerivedProp'])) {
								$props[] = $group['HeatCapacityAndDerivedProp']['ePropName'];
							} elseif (isset($group['ExcessPartialApparentEnergyProp'])) {
								$props[] = $group['ExcessPartialApparentEnergyProp']['ePropName'];
							} elseif (isset($group['TransportProp'])) {
								$props[] = $group['TransportProp']['ePropName'];
							} elseif (isset($group['RefractionSurfaceTensionSoundSpeed'])) {
								$props[] = $group['RefractionSurfaceTensionSoundSpeed']['ePropName'];
							} elseif (isset($group['BioProperties'])) {
								$props[] = $group['BioProperties']['ePropName'];
							}
						}
						$pnts += count($set['NumValues']);
					}
				} elseif (isset($trc['ReactionData'])) {
					if (!isset($trc['ReactionData'][0])) {
						$trc['ReactionData'] = [0 => $trc['ReactionData']];
					}
					foreach ($trc['ReactionData'] as $set) {
						//debug($set);
						if (!isset($set['Property'][0])) {
							$set['Property'] = [0 => $set['Property']];
						}
						foreach ($set['Property'] as $prop) {
							$group = $prop['Property-MethodID']['PropertyGroup'];
							if (isset($group['ReactionStateChangeProp'])) {
								$props[] = $group['ReactionStateChangeProp']['ePropName'];
							} elseif (isset($group['ReactionEquilibriumProp'])) {
								$props[] = $group['ReactionEquilibriumProp']['ePropName'];
							}
						}
						$pnts += count($set['NumValues']);
					}
				}
				$props = array_unique($props);
				$props = array_values($props);
				$data = ['File' => ['title' => $title, 'url' => $url, 'filename' => $filename, 'properties' => json_encode($props), 'datapoints' => $pnts, 'journal' => $journal]];
				$this->File->create();
				$this->File->save($data);
				echo "Added: " . $url . "<br />";
			} else {
				echo "Already added: " . $url . "<br />";
			}
		}
		exit;
	}

	/**
	 * Add TRC data
	 * @param $max
	 * @param $test
	 */
	public function ingest($max=10,$test=0)
	{
		$path = WWW_ROOT.'files'.DS.'trc'.DS.'jced'.DS;
		//debug($path);exit;
		$maindir = new Folder($path);
		$files = $maindir->find('.*\.xml',true);
		//debug($files);exit;
		if ($test) {
			$files[1] = [
				0 => WWW_ROOT . 'files/trc/jced/acs.jced.5b00619.xml',
				1 => WWW_ROOT . 'files/trc/jced/acs.jced.5b00623.xml',
				2 => WWW_ROOT . 'files/trc/jced/acs.jced.5b00624.xml',
				3 => WWW_ROOT . 'files/trc/jced/acs.jced.5b00625.xml',
				5 => WWW_ROOT . 'files/trc/jced/acs.jced.5b00632.xml'];
		}
		$count=0;
		$done = $this->NewFile->find('list', ['fields' => ['doi', 'id']]);
		foreach ($files as $filename) {
			$filepath = $path.$filename;
			$xml = simplexml_load_file($filepath);$count++;$errors = [];
			$trc = json_decode(json_encode($xml), true);

			//debug($trc);exit;

			// Get doi
			$doi = null;
			if (isset($trc['Citation']['sDOI'])) {
				$doi = $trc['Citation']['sDOI'];
			} else {
				// Get DOI from crossref
				$pages = str_replace('  ', ' ', $trc['Citation']['sPage']);
				$journal =  $trc['Citation']['sPubName'];
				if($journal=='J.Chem.Eng.Data') { $journal='J. Chem. Eng. Data'; }
				$issn = $this->Journal->getfield('issn',$journal);
				$year = $trc['Citation']['yrPubYr'];
                $volume = $trc['Citation']['sVol'];
                $filter = ['issn' => $issn, 'date' => $year];
				$found = $this->Api->works($pages, $filter);
                //debug($file);debug($pages);debug($filter);debug($found);exit;
				if ($found) {
					if (count($found['items']) == 1) {
						$doi = $found['items'][0]['DOI'];
					} else {
					    foreach($found['items'] as $idx=>$hit) {
					        if($issn==$hit['ISSN'][0]||$issn==$hit['ISSN'][1]) {
					            //debug($volume);debug($pages);debug($hit);
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

			//debug($done);exit;

			// check for errors
			if(!empty($errors)) {
				foreach ($errors as $error) {
					$this->Error->add(['file' => $filename, 'error' => $error]);
				}
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

			// add reference if not already present
			$refs = $this->NewReference->find('list', ['fields' => ['doi', 'id']]);
			$refid = null;
			if (!isset($refs[$doi])) {
				$ref = $this->NewReference->addbydoi($doi);
				$refid = $ref['id'];
			} else {
				$refid = $refs[$doi];
			}
			//debug($refid);

			// add file if not already present
			if (isset($done[$doi])) {
				$fid = $done[$doi];
			} else {
				$meta = [];$cite = $trc['Citation'];
				// trcid (if present)
				if (isset($cite['TRCRefID'])) {
					$id = $cite['TRCRefID'];
					if (is_array($id['sAuthor2'])) {
						$meta['trcid'] = $id['yrYrPub'] . $id['sAuthor1'] . $id['nAuthorn'];
					} else {
						$meta['trcid'] = $id['yrYrPub'] . $id['sAuthor1'] . $id['sAuthor2'] . $id['nAuthorn'];
					}
				} else {
					$meta['trcid'] = null;
				}
				$meta['title'] = $cite['sTitle'];
				$meta['abstract'] = $cite['sAbstract'];
				$meta['date'] = $cite['dateCit'];
				$meta['year'] = $cite['yrPubYr'];
				$meta['url'] = 'https://doi.org/' . $doi;
				$meta['doi'] = $doi;
				$meta['reference_id'] = $refid;
				$meta['filename'] = $filename;
				$journal =  $trc['Citation']['sPubName'];
				if($journal=='J.Chem.Eng.Data') { $journal='J. Chem. Eng. Data'; }
				$meta['journal'] = $journal;
				$meta['journal_id'] = $this->Journal->getfield('id', $journal);

				$f = $this->NewFile->add($meta);
				$fid = $f['id'];
			}

			// add substances and/or get substance ids ($subs var updated by reference)
			$subids = $names = [];
			foreach ($subs as $orgnum => $sub) {
				$found=$this->NewSubstance->find('list',['fields'=>['inchikey','id']]);
				if($found) {
					$subid=$found[$sub['inchikey']];
				} else {
					echo "Compound no found";debug($sub);exit;
				}
				$subids[$orgnum] = $subid;
				$names[$orgnum] = $sub['name'];
			}

			// add chemicals if not present
			$chmids = [];
			foreach($chems as $orgnum => $chem) {
				$found = $this->NewChemical->find('first',['conditions'=>['file_id'=>$fid,'orgnum'=>$chem['orgnum']]]);
				if(!$found) {
					$chem['file_id'] = $fid;
					$chem['substance_id'] = $subids[$chem['orgnum']];
					if (isset($chem['purity'])) {
						if (!is_null($chem['purity'])) {
							$chem['purity'] = json_encode($chem['purity']);
						} else {
							$chem['purity'] = null;
						}
					}
					$this->NewChemical->create();
					$this->NewChemical->save(['NewChemical' => $chem]);
					$chmid=$this->NewChemical->id;
					$this->NewChemical->clear();
				} else {
					$chmid=$found['NewChemical']['id'];
				}
				$chmids[$orgnum]=$chmid;
			}

			// add report
			$compcnt=count($chems);$psetcnt=$rsetcnt=0;$props=[];
			if(isset($trc['PureOrMixtureData'])) { $psetcnt=count($trc['PureOrMixtureData']); }
			if(isset($trc['ReactionData'])) { $rsetcnt=count($trc['ReactionData']); }
			foreach($trc['PureOrMixtureData'] as $pset) {
				if(!isset($pset['Property'][0])) { $pset['Property']=[0=>$pset['Property']]; }
				foreach($pset['Property'] as $prop) {
					$info=$this->getpropinfo($prop);
					list($prp,$junk) = explode(', ',$info['name']);
					if(stristr($prp,'Henry')) {
						$props[]=$prp;
					} else {
						$props[]=lcfirst($prp);
					}
				}
			}
			$props=array_unique($props);
			$solprops=['molality','mass concentration','ratio of amount of solute to mass of solution',
				'mass ratio of solute to solvent','smount concentration (molarity)','mole fraction',
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

			$ref=$this->NewReference->find('list',['fields'=>['id','title'],'conditions'=>['id'=>$refid]]);
			$psetstr=""; if($psetcnt>0) { $psetstr=$psetcnt." datasets of compound/mixture data"; }
			$rsetstr=""; if($rsetcnt>0) { $rsetstr=$rsetcnt." datasets of reaction data"; }
			$and=""; if($psetcnt>0&&$rsetcnt>0) { $and=" and "; }
			$desc='Paper containing '.$psetstr.$and.$rsetstr.' about '.$compcnt.' compounds determining '.$propstr;
			$conds=['title'=>'Report on paper "'.$ref[$refid].'"','description'=>$desc,'file_id'=>$fid,'reference_id'=>$refid];
			$found=$this->NewReport->find('first',['conditions'=>$conds,'recursive'=>-1]);
			if(!$found) {
				$this->NewReport->create();
				$this->NewReport->save(['NewReport'=>$conds]);
				$repid = $this->NewReport->id;
			} else {
				$repid=$found['NewReport']['id'];
			}

			// add property data
			$repdatacnt=0;
			if (isset($trc['PureOrMixtureData'])) {
				$sets = $trc['PureOrMixtureData'];
				if (!isset($sets[0])) { $sets = [0 => $sets]; }
				foreach ($sets as $setidx => $set) {
					$setnum = $set['nPureOrMixtureDataNumber'];

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
								$phases[] = $p;
							}
						} else {
							$phases[] = $set['PhaseID'];
						}
					}

					// add system
					asort($comids); // sort lowest first
					if (count($comids) == 1) {
						$idstr = $comids[0];
					} else {
						$idstr = implode(":", $comids);
					}
					$sysid = $this->getsysid($idstr, $cnames);

					// add dataset
					$conds=['title'=>'Dataset '.$setnum.' in paper '.$doi,'setnum'=>$setnum,
						'file_id'=>$fid,'report_id'=>$repid, 'system_id'=>$sysid, 'reference_id'=>$refid];
					$found=$this->NewDataset->find('first',['conditions'=>$conds,'recursive'=>-1]);
					if(!$found) {
						$this->NewDataset->create();
						$this->NewDataset->save(['NewDataset'=>$conds]);
						$setid = $this->NewDataset->id;
					} else {
						if(!is_null($found['NewDataset']['points'])) { // assume complete if point count
							$repdatacnt+=$found['NewDataset']['points'];continue;
						}
						$setid = $found['NewDataset']['id'];
					}


					// add mixture
					$conds=['system_id'=>$sysid,'dataset_id'=>$setid];
					$found=$this->NewMixture->find('first',['conditions'=>$conds,'recursive'=>-1]);
					if(!$found) {
						$this->NewMixture->create();
						$this->NewMixture->save(['NewMixture'=>$conds]);
						$mixid = $this->NewMixture->id;
					} else {
						$mixid = $found['NewMixture']['id'];
					}

					// add phases
					$phsids=[];
					foreach($phases as $p) {
						$ptid=$this->NewPhasetype->find('list',['fields'=>['name','id'],'conditions'=>['name'=>$p['ePhase']]]);
						$orgnum=null; if(isset($p['RegNum'])) { $orgnum=$p['RegNum']['nOrgNum']; }
						$cnds=['mixture_id'=>$mixid,'phasetype_id'=>$ptid[$p['ePhase']],'orgnum'=>$orgnum];
						$found=$this->NewPhase->find('first',['conditions'=>$cnds,'recursive'=>-1]);
						if(!$found) {
							$this->NewPhase->create();
							$this->NewPhase->save(['NewPhase'=>$cnds]);
							$phsid=$this->NewPhase->id;
							$this->NewPhase->clear();
						} else {
							$phsid=$found['NewPhase']['id'];
						}
						$phsids[$p['ePhase']]=$phsid;
					}

					// add components
					ksort($comids); $cmpids=[]; // resort so components are added in numerical order
					foreach ($comids as $comnum=>$subid) {
						$orgnum=array_search($subid,$subids); // get the orgnum for this subid
						$conds=['chemical_id'=>$chmids[$orgnum],'mixture_id'=>$mixid,'compnum'=>$comnum];
						$found=$this->NewComponent->find('first',['conditions'=>$conds,'recursive'=>-1]);
						if(!$found) {
							$this->NewComponent->create();
							$this->NewComponent->save(['NewComponent'=>$conds]);
							$cmpid = $this->NewComponent->id;
						} else {
							$cmpid = $found['NewComponent']['id'];
						}
						$cmpids[$orgnum]=$cmpid;
					}

					// get the sample properties
					$props = $set['Property'];$proparray = [];$phasearray = [];$sprpids = [];
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
						if (isset($prop['PropUncertainty']['nUncertAssessNum'])) {
							$uncnum = $prop['PropUncertainty']['nUncertAssessNum'];
						}
						if (isset($prop['PropUncertainty']['sUncertEvaluator'])) {
							$unceval = $prop['PropUncertainty']['sUncertEvaluator'];
						}
						if (isset($prop['PropUncertainty']['nUncertLevOfConfid'])) {
							$uncconf = $prop['PropUncertainty']['nUncertLevOfConfid'];
						}
						$conds = ['dataset_id' => $setid, 'propnum' => $propnum, 'orgnum' => $orgnum,
							'property_group' => $propgroup, 'property_name' => $propname,
							'property_id' => $propid, 'unit_id' => $unitid, 'method_name' => $methname,
							'phase' => $phase, 'presentation' => $pres, 'solventorgnum' => $solorgnum,
							'uncnum' => $uncnum, 'unceval' => $unceval, 'uncconf' => $uncconf];
						$found=$this->NewSampleprop->find('first',['conditions'=>$conds,'recursive'=>-1]);
						if(!$found) {
							$this->NewSampleprop->create();
							$this->NewSampleprop->save(['NewSampleprop'=>$conds]);
							$sprpid = $this->NewSampleprop->id;
							$this->NewSampleprop->clear();
						} else {
							$sprpid=$found['NewSampleprop']['id'];
						}
						$sprpids[$propnum]=$sprpid;
						// padding the string as the ids do not come zerofill from the code above...
						$proparray[$propnum] = str_pad($propid, 5, '0', STR_PAD_LEFT).":".str_pad(str_pad($unitid, 5, '0', STR_PAD_LEFT), 5, '0');
					}

					// update the system based on phase data
					$this->getsysid($idstr, $cnames, $phasearray);

					// create series condition (Constraints) data arrays (saved to add to series later)
					$sconds = [];
					if (isset($set['Constraint'])) {
						$serconds = $set['Constraint'];
						if (!isset($serconds[0])) { $serconds = [0 => $serconds]; }
						foreach ($serconds as $scidx => $sercond) {
							$propname=null;$unitid=null;$comid=null;$phsid=null;
							$ctype = $sercond['ConstraintID']['ConstraintType'];
							$propname = $this->getpropname($ctype);
							$pu = $this->getpropunit($propname);
							list($propid, $unitid) = explode(":", $pu);
							if(is_null($unitid)) { echo "Unitid is 'null'";exit; }
							$number = $sercond['nConstraintValue'];
							$sf = $sercond['nConstrDigits'];
							if(isset($sercond['ConstraintID']['RegNum'])) {
								$orgnum=$sercond['ConstraintID']['RegNum']['nOrgNum'];
								$comid=$comids[$orgnum];
							}
							if(isset($sercond['ConstraintPhaseID'])) {
								$phase=$sercond['ConstraintPhaseID']['eConstraintPhase'];
								$phsid=$phsids[$phase];
							}

							// Get sci notation data for value
							$e = $this->exponentialGen($number);
							// create data to save - series_id placeholder for later
							$sconds[$scidx] = ['dataset_id'=>$setid, 'dataseries_id'=>null,'datapoint_id'=>null,
								'property_id'=>$propid,'system_id'=>$sysid,'property_name'=>$propname,
								'number'=>$number,'component_id'=>$comid,'phase_id'=>$phsid,
								'significand'=>$e['significand'],'exponent'=>$e['exponent'],'error'=>$e['error'],
								'error_type'=>'absolute','unit_id'=>$unitid,'accuracy'=>$sf,'text'=>$number];
						}
					}

					// analyze data for series to pull out (max) one additional series condition
					$vals = $digits = $counts = [];$scondnum = $scondvals = $sconddigs = $sconddigits = null;
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
							if (isset($cond['VariableID']['RegNum']['nOrgNum'])) {
								$comid = $cmpids[$cnd['VariableID']['RegNum']['nOrgNum']];
							}
							if (isset($cond['VarPhaseID'])) { // only has one if present
								$phsid = $phsids[$cnd['VarPhaseID']['eVarPhase']];
							}
							$conds[$cnd['nVarNumber']]=['dataset_id'=>$setid, 'dataseries_id'=>null,'datapoint_id'=>null,
								'property_id'=>$propid,'system_id'=>$sysid,'property_name'=>$propname,
								'component_id'=>$comid,'phase_id'=>$phsid,'unit_id'=>$unitid];
						}
					}

					// create series and add series conditions
					$serids = [];
					if (is_null($scondnum)) {
						// add dataseries
						$cnds = ['dataset_id'=>$setid,'type'=>'independent set','idx'=>1];
						$found=$this->NewDataseries->find('first',['conditions'=>$cnds,'recursive'=>-1]);
						if(!$found) {
							$this->NewDataseries->create();
							$this->NewDataseries->save(['NewDataseries'=>$cnds]);
							$serids[0] = $this->NewDataseries->id;
						} else {
							$serids[0] = $found['NewDataseries']['id'];
						}

						// Add 'constraint' series conditions
						foreach ($sconds as $scond) {
							$scond['dataseries_id'] = $serids[0];
							$found=$this->NewCondition->find('first',['conditions'=>$scond,'recursive'=>-1]);
							if(!$found) {
								$this->NewCondition->create();
								$this->NewCondition->save(['NewCondition' => $scond]);
								$this->NewCondition->clear();
							}
						}
					} else {
						$sercnt=1;
						// if there are series conditions derived from 'variable' conditions, create all series
						foreach ($scondvals as $scondval) {
							// add dataseries
							$cnds = ['dataset_id'=>$setid,'type'=>'independent set','idx'=>$sercnt];
							$found=$this->NewDataseries->find('first',['conditions'=>$cnds,'recursive'=>-1]);
							if(!$found) {
								$this->NewDataseries->create();
								$this->NewDataseries->save(['NewDataseries'=>$cnds]);
								$serids[$sercnt] = $this->NewDataseries->id;
							} else {
								$serids[$sercnt] = $found['NewDataseries']['id'];
							}

							// add 'constraint' series conditions
							foreach ($sconds as $scond) {
								$scond['dataseries_id'] = $serids[$sercnt];
								$found=$this->NewCondition->find('first',['conditions'=>$scond,'recursive'=>-1]);
								if(!$found) {
									$this->NewCondition->create();
									$this->NewCondition->save(['NewCondition'=>$scond]);
									$this->NewCondition->clear();
								}
							}

							// add 'variable' series condition
							$scond = $conds[$scondnum];
							$e = $this->exponentialGen($scondval);
							$scond['dataseries_id']=$serids[$sercnt];
							$scond['accuracy']=$e['dp'];
							$scond['significand']=$e['significand'];
							$scond['number']=$e['scinot'];
							$scond['error']=$e['error'];
							$scond['error_type']='absolute';
							$scond['exponent']=$e['exponent'];
							$scond['text']=$scondval;
							$found=$this->NewCondition->find('first',['conditions'=>$scond,'recursive'=>-1]);
							if(!$found) {
								$this->NewCondition->create();
								$this->NewCondition->save(['NewCondition'=>$scond]);
								$this->NewCondition->clear();
							}

							$sercnt++;
						}
					}

					$setdatcnt=0;
					foreach ($serids as $scidx => $serid) {
						// get and process the data
						$data = $set['NumValues'];$serdatcnt=0;
						if (!isset($data[0])) { $data = [0 => $data]; }
						foreach ($data as $idx => $datum) {
							// only add data to this series that has the correct scond value...
							// assumes scondnums are always in numeric sequence
							if (!is_null($scondnum)) {
								if ($datum['VariableValue'][($scondnum - 1)]['nVarValue'] != $scondvals[($scidx-1)]) {
									//debug($datum['VariableValue'][($scondnum - 1)]['nVarValue']);
									//debug($scondvals[$scidx]);
									continue;
								}
							}

							// Add datapoint
							$cnds = ['dataset_id' => $setid, 'dataseries_id' => $serid, 'row_index' => ($idx + 1)];
							$found=$this->NewDatapoint->find('first',['conditions'=>$cnds,'recursive'=>-1]);
							if(!$found) {
								$this->NewDatapoint->create();
								$this->NewDatapoint->save(['NewDatapoint'=>$cnds]);
								$pntid = $this->NewDatapoint->id;
							} else {
								$pntid = $found['NewDatapoint']['id'];
							}

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
									$e = $this->exponentialGen($value);
									// $cond['dataseries_id']=$serid; dont link datapoint conditions to the series
									$cond['datapoint_id']=$pntid;
									$cond['number']=$e['scinot'];
									$cond['significand']=$e['significand'];
									$cond['exponent']=$e['exponent'];
									$cond['error']=$e['error']/pow(10,$diff);
									$cond['error_type']='absolute';
									$cond['accuracy']=$var['nVarDigits'];
									$cond['text']=$var['nVarValue'];
									$found=$this->NewCondition->find('first',['conditions'=>$cond,'recursive'=>-1]);
									if(!$found) {
										$this->NewCondition->create();
										$this->NewCondition->save(['NewCondition'=>$cond]);
										$this->NewCondition->clear();
									}
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
								$sprop = $this->NewSampleprop->find('first', ['conditions' => $cnds, 'recursive' => -1]);
								if(!$sprop) { echo 'Sampleprop not found';debug($edatum);exit; }
								$sprop = $sprop['NewSampleprop'];
								$spropid = $sprop['id'];
								$propname = $sprop['property_name'];
								$propid = $sprop['property_id'];
								$unitid = $sprop['unit_id'];
								$orgnum = $sprop['orgnum'];
								$phase = $sprop['phase'];

								// get component,phase from mixture
								$cptid = $phsid = null;$c=['NewPhase'=>['NewPhasetype'],'NewComponent'=>['NewChemical']];
								$mix=$this->NewMixture->find('first',['conditions'=>['id'=>$mixid],'contain'=>$c,'recursive'=>-1]);
								debug($mix);exit;
								foreach($mix['NewMixture']['NewComponent'] as $cpt) {
									if($cpt['NewChemical']['orgnum']==$orgnum) {
										$cptid=$cpt['id'];
									}
								}
								foreach($mix['NewMixture']['NewPhase'] as $phs) {
									if($phs['NewPhasetype']['name']==$phase) {
										$phsid=$phs['id'];
									}
								}

								// get sci notation data for value
								$e = $this->exponentialGen($value);
								$cnds = ['dataset_id' => $setid, 'dataseries_id' => $serid, 'datapoint_id' => $pntid,
									'property_id' => $propid,'property_name'=>$propname, 'sampleprop_id' => $spropid,
									'number'=>$e['scinot'],'component_id'=>$cptid,'phase_id'=>$phsid,
									'significand'=>$e['significand'],'exponent'=>$e['exponent'],
									'error'=>$err,'unit_id'=>$unitid,'accuracy'=>$acc,'text'=>$value];
								$found=$this->NewData->find('first',['conditions'=>$cnds,'recursive'=>-1]);
								if(!$found) {
									$this->NewData->create();
									$this->NewData->save(['NewData'=>$cnds]);
									$this->NewData->clear();
								}
							}

							$serdatcnt++;
						}

						// update series with # datapoints
						$update=['NewDataseries'=>['id'=>$serid,'points'=>$serdatcnt]];
						$this->NewDataseries->save($update);

						$setdatcnt+=$serdatcnt;
					}

					// update dataset with # datapoints
					$update=['NewDataset'=>['id'=>$setid,'points'=>$setdatcnt]];
					$this->NewDataset->save($update);

					$repdatacnt+=$setdatcnt;

					echo 'Completed dataset '.$setnum.'<br/>';
				}

				// update report with # datapoints
				// update dataset with # datapoints
				$update=['NewReport'=>['id'=>$repid,'points'=>$repdatacnt]];
				$this->NewReport->save($update);
			}
			debug($repdatacnt);exit;

			// Reaction data
			if (isset($trc['ReactionData'])) {
				$datasets = $trc['ReactionData'];
				if (!isset($datasets[0])) {
					$datasets = [0 => $datasets];
				}
				foreach ($datasets as $setidx => $set) {
					// Components
					$coms = $set['Participant'];
					if (!isset($coms[0])) {
						$coms = ['0' => $coms];
					}
					$comids = [];
					$cnames = [];
					$reaction = [];
					foreach ($coms as $com) {
						$orgnum = $com['RegNum']['nOrgNum'];
						$comids[] = $subids[$orgnum];
						$cnames[] = $names[$orgnum];
						if (isset($com['nSampleNm'])) {
							$number = $com['nSampleNm'];
						} else {
							$number = null;
						}
						$coef = $com['nStoichiometricCoef'];
						$phase = $com['ePhase'];
						$temp = ['orgnum' => $orgnum, 'number' => $number, 'stoichcoef' => $coef, 'phase' => $phase];
						$reaction[] = $temp;
					}

					// Add system
					sort($comids);
					if (count($comids) == 1) {
						$idstr = $subids[0];
					} else {
						$idstr = implode(":", $comids);
					}
					$sysid = $this->getsysid($idstr, $cnames);

					// Create dataset
					$temp = ['Dataset' => ['title' => 'Dataset ' . ($setidx + 1) . ' in paper ' . $doi, 'file_id' => $fid,
						'system_id' => $sysid, 'reference_id' => $refid, 'phase' => json_encode($phase)]];
					$this->Dataset->create();
					$this->Dataset->save($temp);
					$setid = $this->Dataset->id;

					// Create dataseries
					$temp = ['Dataseries' => ['dataset_id' => $setid, 'type' => 'independent set']];
					$this->Dataseries->create();
					$this->Dataseries->save($temp);
					$serid = $this->Dataseries->id;

					// Get the properties
					$type = $set['eReactionType'];
					$props = $set['Property'];
					$condarray = [];
					$proparray = [];
					if (!isset($props[0])) {
						$props = ['0' => $props];
					}
					foreach ($props as $prop) {
						$number = $prop['nPropNumber'];
						$group = $prop['Property-MethodID']['PropertyGroup'];
						if (isset($group['ReactionStateChangeProp'])) {
							$proptype = $group['ReactionStateChangeProp'];
							$propgroup = 'ReactionStateChangeProp';
						} elseif (isset($group['ReactionEquilibriumProp'])) {
							$proptype = $group['ReactionEquilibriumProp'];
							$propgroup = 'ReactionEquilibriumProp';
						}
						$propname = $proptype['ePropName'];
						$unitid = $this->getunit($propname);
						if (isset($proptype['sMethodName'])) {
							$methname = $proptype['sMethodName'];
						} else {
							$methname = null;
						}
						$conditions = []; // Reaction conditions...
						if (isset($prop['Solvent'])) {
							if (is_array($prop['Solvent'])) {
								$solvent = json_encode($prop['Solvent']);
							} else {
								$solvent = $prop['Solvent'];
							}
						} else {
							$solvent = null;
						}
						if (isset($prop['Catalyst'])) {
							if (is_array($prop['Catalyst'])) {
								$catalyst = json_encode($prop['Catalyst']);
							} else {
								$catalyst = $prop['Catalyst'];
							}
						} else {
							$catalyst = null;
						}
						if (isset($prop['eStandardState'])) {
							$standardstate = $prop['eStandardState'];
						} else {
							$standardstate = null;
						}
						if (isset($prop['nTemperature-K'])) {
							$e = $this->exponentialGen($prop['nTemperature-K']);
							$temp = ['Condition' => ['datapoint_id' => null, 'property_name' => 'temperature', 'property_id' => 3,
								'number' => $prop['nTemperature-K'], 'unit_id' => 5,
								'accuracy' => $prop['nTemperatureDigits'], 'significand' => $e['significand'],
								'exponent' => $e['exponent']]];
							$condarray[] = $temp;
						}
						if (isset($prop['nPressure-kPa'])) {
							$e = $this->exponentialGen($prop['nPressure-kPa']);
							$temp = ['Condition' => ['datapoint_id' => null, 'property_name' => 'pressure', 'property_id' => 2,
								'number' => $prop['nPressure-kPa'], 'unit_id' => 25,
								'accuracy' => $prop['nPressureDigits'], 'significand' => $e['significand'],
								'exponent' => $e['exponent']]];
							$condarray[] = $temp;
						}
						if (isset($prop['PropDeviceSpec']['eDeviceSpecMethod'])) {
							$specmethod = $prop['PropDeviceSpec']['eDeviceSpecMethod'];
						} else {
							$specmethod = null;
						}
						// Get Property from sampleprop
						$propid = $this->Property->getfield('id', ['field like' => '%"' . $propname . '"%']);
						$temp = ["Reactionprop" => ['dataset_id' => $setid, 'number' => $number, 'type' => $type,
							'property_id' => $propid, 'property_group' => $propgroup, 'property_name' => $propname,
							'method_name' => $methname, 'reaction' => json_encode($reaction), 'solvent' => $solvent,
							'catalyst' => $catalyst, 'standardstate' => $standardstate, 'devicespecmethod' => $specmethod]];
						$this->Reactionprop->create();
						$this->Reactionprop->save($temp);
						$propid = $this->Reactionprop->id;
						//$propid=0;
						$proparray[$number] = $propid . ":" . $unitid;
					}

					// Series conditions
					if (isset($set['Constraint'])) {
						$serconds = $set['Constraint'];
						if (!isset($serconds[0])) {
							$serconds = [0 => $serconds];
						}
						foreach ($serconds as $sercond) {
							$ctype = $sercond['ConstraintID']['ConstraintType'];
							$res = $this->getpropunit($ctype);
							list($propname, $unitid) = explode(":", $res);
							$number = $sercond['nConstraintValue'];
							$sf = $sercond['nConstrDigits'];
							$temp = ['Condition' => ['dataseries_id' => $serid, 'property_name' => $propname, 'number' => $number, 'unit_id' => $unitid, 'accuracy' => $sf]];
							$this->Condition->create();
							$this->Condition->save($temp);
						}
					}

					// Grab the data
					$data = $set['NumValues'];
					if (!isset($data[0])) {
						$data = [0 => $data];
					}
					foreach ($data as $idx => $datum) {
						// Add datapoint
						$temp = ['Datapoint' => ['dataseries_id' => $serid, 'row_index' => ($idx + 1)]];
						$this->Datapoint->create();
						$this->Datapoint->save($temp);
						$pntid = $this->Datapoint->id;

						// Add conditions
						foreach ($condarray as $cond) {
							$cond['Condition']['datapoint_id'] = $pntid;
							$this->Condition->create();
							$this->Condition->save($cond);
							$this->Condition->clear();
						}

						// Add data
						$edata = $datum['PropertyValue'];
						if (!isset($edata[0])) {
							$edata = [0 => $edata];
						}
						foreach ($edata as $edatum) {
							$propunit = $proparray[$edatum['nPropNumber']];
							list($rpropid, $unitid) = explode(":", $propunit);
							$number = $edatum['nPropValue'];
							$acc = $edatum['nPropDigits'];
							if (isset($edatum['PropRepeatability'])) {
								$err = $edatum['PropRepeatability']['nPropRepeatValue'];
							} else {
								$err = null;
							}
							$rprop = $this->Reactionprop->find('first', ['conditions' => ['id' => $rpropid], 'recursive' => -1]);
							$propid = $rprop['Reactionprop']['property_id'];
							$e = $this->exponentialGen($number);
							$temp = ['Data' => ['datapoint_id' => $pntid, 'property_id' => $propid,
								'reactionprop_id' => $rpropid, 'number' => $number, 'unit_id' => $unitid, 'error' => $err,
								'accuracy' => $acc, 'significand' => $e['significand'], 'exponent' => $e['exponent']]];
							$this->Data->create();
							$this->Data->save($temp);
							$this->Data->clear();
						}
					}
				}
			}

			// Add datapoint stats to files table
			$c = ['Dataset' => ['Dataseries' => ['Datapoint' => ['Data']]]];
			$data = $this->File->find('first', ['conditions' => ['File.id' => $fid], 'contain' => $c, 'recursive' => -1]);
			$points=[];$datums=[];
			foreach($data['Dataset'] as $set) {
				foreach($set['Dataseries'] as $ser) {
					foreach($ser['Datapoint'] as $pnt) {
						$points[]=$pnt;
						foreach($pnt['Data'] as $datum) {
							$datums[]=$datum['id'];
						}
					}
				}
			}
			$this->File->id=$fid;
			$this->File->saveField('datapoints',count($points));
			$this->File->clear();
			// data to data_systems table
			foreach($datums as $did) {
				$this->Data->joinsys('id',$did);
			}
			echo "File " . $filename . " added (".count($points)." points)<br />";
			if($count==$max) { exit; }

		}
		exit;
	}

	/**
	 * get the property group, property, and method
	 * @param $prop
	 */
	private function getpropinfo($prop)
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
		} elseif (isset($group['ReactionStateChangeProp'])) {  //rxns
			$proptype = $group['ReactionStateChangeProp'];
			$propgroup = 'ReactionStateChangeProp';
		} elseif (isset($group['ReactionEquilibriumProp'])) {  //rxns
			$proptype = $group['ReactionEquilibriumProp'];
			$propgroup = 'ReactionEquilibriumProp';
		}
		$method=null;
		if(isset($proptype['eMethodName'])) { $method=$proptype['eMethodName']; }
		if(isset($proptype['sMethodName'])) { $method=$proptype['sMethodName']; }
		return ['group'=>$propgroup,'name'=>$proptype['ePropName'],'method'=>$method];
	}

	/**
	 * check entries added to the database
	 */
	public function check()
	{
		$path = WWW_ROOT . 'files' . DS . 'trc'. DS . 'jced'. DS . 'aadone';
		$maindir = new Folder($path);
		$files = $maindir->find('.*\.xml',true);
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
		debug($files);exit;
	}

	/**
	 * Test function
	 */
	public function test()
	{
		$search = [0 => ['casrn' => '18923-20-1', 'name' => 'carbon dioxide', 'formula' => 'CO2']];
		debug($search);
		$this->Datarectification->checkAndAddSubstances($search, true);
		debug($search);
		exit;
	}

	/**
	 * Get the refs for the TRC files using call to the Crossref API
	 */
	public function getrefs()
	{
		$refs = $this->File->find('list', ['fields' => ['id', 'doi'], 'conditions' => ['reference_id' => 0]]);
		foreach ($refs as $trcid => $doi) {
			$meta = $this->Reference->addbydoi($doi);
			debug($meta);
			$this->File->id = $trcid;
			$this->File->savefield('reference_id', $meta['id']);
		}
		exit;
	}

	/**
	 * get a list of the most recently added files
	 * @param integer $l
	 * @return mixed
	 */
	public function recent($l=6)
	{
		$data=$this->File->find('list',['fields'=>['id','title'],'order'=>['updated'=>'desc'],'limit'=>$l]);
		$this->set('data',$data);
		if($this->request->params['requested']) { return $data; }
	}

	/**
	 * count the number of files
	 * @return mixed
	 */
	public function totalfiles()
	{
		$data=$this->File->find('count');

		echo $data;exit;
	}

	// private functions called by the functions above (not exposed)

	/**
	 * Get the property string
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
		} elseif (isset($ctype['eParticipantAmount'])) {
			$propname = $ctype['eParticipantAmount'];
		}
		return $propname;
	}


	/**
	 * Get the uncertainty string
	 * @param array $uncert
	 * @return string
	 */
	private function getuncert(array $uncert)
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
	 * Get the property and unit
	 * @param $propunit
	 * @return string
	 */
	private function getpropunit($propunit): string
	{
		$propid = $this->NewProperty->getfield('id', '%"'.$propunit.'"%', 'field like');$unitid=17;
		if (stristr($propunit, ', ')) {
			list($prop, $unit) = explode(", ", $propunit);
			$unitid = $this->NewUnit->getfield('id', '%"'.$unit.'"%', 'header like');
		}
		return $propid.":".$unitid;
	}

	/**
	 * Get the unit
	 * @param $str
	 * @return int
	 */
	private function getunit($str)
	{
		//debug($str);
		if (preg_match('/, mol\/kg$/', $str)) {
			$unitid = 53;
		} elseif (preg_match('/, mol\/dm3$/', $str)) {
			$unitid = 21;
		} elseif (preg_match('/, kPa$/', $str)) {
			$unitid = 25;
		} elseif (preg_match('/, K$/', $str)) {
			$unitid = 5;
		} elseif (preg_match('/, kg\/m3$/', $str)) {
			$unitid = 32;
		} elseif (preg_match('/, nm$/', $str)) {
			$unitid = 26;
		} elseif (preg_match('/, MHz$/', $str)) {
			$unitid = 67;
		} elseif (preg_match('/, m3\/mol$/', $str)) {
			$unitid = 30;
		} elseif (preg_match('/, m3\/kg$/', $str)) {
			$unitid = 68;
		} elseif (preg_match('/, mol\/m3$/', $str)) {
			$unitid = 69;
		} elseif (preg_match('/, J\/K\/mol$/', $str)) {
			$unitid = 70;
		} elseif (preg_match('/, mol$/', $str)) {
			$unitid = 6;
		} elseif (preg_match('/, kg$/', $str)) {
			$unitid = 71;
		} elseif (preg_match('/, kJ\/mol$/', $str)) {
			$unitid = 35;
		} elseif (preg_match('/, kJ$/', $str)) {
			$unitid = 72;
		} elseif (preg_match('/, J\/g$/', $str)) {
			$unitid = 73;
		} elseif (preg_match('/, V$/', $str)) {
			$unitid = 74;
		} elseif (preg_match('/, \(mol\/kg\)$/', $str)) {
			$unitid = 53;
		} elseif (preg_match('/, \(mol\/dm3\)$/', $str)) {
			$unitid = 21;
		} elseif (preg_match('/, kPa$/', $str)) {
			$unitid = 25;
		} elseif (preg_match('/, m\/s$/', $str)) {
			$unitid = 31;
		} elseif (preg_match('/, N\/m$/', $str)) {
			$unitid = 44;
		} else {
			$unitid = 17;
		}

		return $unitid;
	}

	/**
	 * Get the systemid
	 * @param $idstr
	 * @param $cnames
	 * @param $phases
	 * @return mixed
	 */
	private function getsysid($idstr, $cnames, $phases = null)
	{
		$res = $this->System->find('list', ['fields' => ['id', 'phase'], 'conditions' => ['identifier' => $idstr]]);
		$scount = count($cnames);
		if (empty($res)) {
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
			$name = implode(" + ", $cnames);
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
			} else {
				$phase = null;
			}
			if ($scount > 1) {
				$temp = ['System' => ['name' => $name, 'phase' => $phase, 'composition' => $comp, 'identifier' => $idstr]];
			} else {
				$key = $this->Identifier->getfield('value', ['substance_id' => $idstr, 'type' => 'inchikey']);
				$headers = get_headers('http://classyfire.wishartlab.com/entities/' . $key . '.json');
				if (stristr($headers[0], 'OK')) {
					$json = file_get_contents('http://classyfire.wishartlab.com/entities/' . $key . '.json');
					$classy = json_decode($json, true);
					if(!empty($classy)) {
						$kingdom = $classy['kingdom']['name'];
						$type = null;
						$subtype = null;
						if ($kingdom == 'Inorganic compounds') {
							$superclass = $classy['superclass']['name'];
							if ($superclass == 'Homogeneous metal compounds') {
								$type = 'element';
								$subtype = '';// elements!
							} else {
								$type = 'compound';
								$subtype = 'inorganic compound';
							}
						} elseif ($kingdom == 'Organic compounds') {
							$type = 'compound';
							$subtype = 'organic compound';
						}
					} else {
						$type='compound';$subtype='not found on classyfire';
					}
				} else {
					$type = 'compound';
					$subtype = 'organic compound*';
				}

				$temp = ['System' => ['name' => $name, 'phase' => $phase, 'type' => $type, 'subtype' => $subtype, 'composition' => $comp, 'identifier' => $idstr]];
			}
			$this->System->create();
			$this->System->save($temp);
			$sysid = $this->System->id;
			// Add substances/system entries
			if (stristr($idstr, ":")) {
				$subids = explode(":", $idstr);
			} else {
				$subids = [0 => $idstr];
			}
			foreach ($subids as $sid) {
				$this->SubstancesSystem->create();
				$this->SubstancesSystem->save(['SubstancesSystem' => ['substance_id' => $sid, 'system_id' => $sysid]]);
			}
		} else {
			foreach ($res as $sysid => $phase) {
			}
			if ($phase == null && $phases != null) {
				if (!is_null($phases)) {
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
				}
				$this->System->id = $sysid;
				$this->System->savefield('phase', $phase);
			}
		}
		return $sysid;
	}

	/**
	 * Generates a exponential number removing any zeros at the end not needed
	 * @param $string
	 * @return array
	 */
	private function exponentialGen($string)
	{
		$return = [];
		if ($string == 0) {
			$return = ['dp' => 0, 'scinot' => '0e+0', 'exponent' => 0, 'significand' => 0, 'error' => null];
		} elseif (stristr($string, 'E')) {
			list($man, $exp) = explode('E', $string);
			if ($man > 0) {
				$sf = strlen($man) - 1;
			} else {
				$sf = strlen($man) - 2;
			}
			$return['scinot'] = $string;
			$return['error'] = pow(10, $exp - $sf + 1);
			$return['exponent'] = $exp;
			$return['significand'] = $man;
			$return['dp'] = $sf;
		} else {
			$string = str_replace(",", "", $string);
			$num = explode(".", $string);
			// If there is something after the decimal
			if (isset($num[1])) {
				if ($num[0] != "" && $num[0] != 0) {
					// All digits count (-1 for period)
					if ($string < 0 || stristr($string, "-")) {
						// ... add -1 for the minus sign
						$return['dp'] = strlen($string) - 2;
					} else {
						$return['dp'] = strlen($string) - 1;
					}
					// Exponent is based on digit before the decimal -1
					$return['exponent'] = strlen($num[0]) - 1;
				} else {
					// Remove any leading zeroes and count string length
					$t = ltrim($num[1], '0');
					if ($t < 0 || stristr($t, "-")) {
						$return['dp'] = strlen($t) - 1;
					} else {
						$return['dp'] = strlen($t);
					}
					$return['exponent'] = strlen($t) - strlen($num[1]) - 1;
				}
				$return['scinot'] = sprintf("%." . ($return['dp'] - 1) . "e", $string);
				$s = explode("e", $return['scinot']);
				$return['significand'] = $s[0];
				$return['error'] = pow(10, $return['exponent'] - $return['dp'] + 1);
			} else {
				$return['dp'] = 0;
				$return['scinot'] = sprintf("%." . (strlen($string) - 1) . "e", $string);
				$return['exponent'] = strlen($string) - 1;
				$s = explode("e", $return['scinot']);
				$return['significand'] = $s[0];
				$z = explode(".", $return['significand']);
				// Check for negative
				$neg = 0;
				if (stristr($z[0], '-')) {
					$neg = 1;
				}
				if (isset($z[1])) {
					$return['error'] = pow(10, strlen($z[1]) - $s[1] - $neg); // # SF after decimal - exponent
				} else {
					$return['error'] = pow(10, 0 - $s[1]); // # SF after decimal - exponent
				}
			}
		}

		return $return;
	}

	// temporary/used functions

	/**
	 * View file (in scidata format)
	 * @param int $id
	 * @param string $format
	 */
	public function view2($id,$format="html")
	{
		$data = $this->File->find('first', ['conditions' => ['File.id' => $id], 'contain' => $this->c, 'recursive' => -1]);
		//debug($data);exit;
		$raw = $data;
		$file = $data['File'];
		$ref = $data['Reference'];
		$chems = $data['Chemical'];
		$sets = $data['Dataset'];

		// Main metadata
		$trc = new $this->Scidata;
		$trc->setpath("https://chalk.coas.unf.edu/trc/");
		$trc->setbase("files/scidata/" . $id . "/");
		$trc->setpid("trc:file:" . $id);

		$meta = [];
		$meta['title'] = $file['title'];
		$meta['description'] = "Report of thermochemical data in ThermoML format from the NIST TRC website http://www.trc.nist.gov/ThermoML/";
		if (stristr($file['doi'], '10.1007')) {
			$meta['publisher'] = 'Springer Nature';
		} elseif (stristr($file['doi'], '10.1016')) {
			$meta['publisher'] = 'Elsevier B.V.';
		} elseif (stristr($file['doi'], '10.1021')) {
			$meta['publisher'] = 'ACS Publications';
		}
		$trc->setmeta($meta);

		// authors
		$trc->setauthors($ref['authors']);

		// startdate
		$trc->setstartdate($file['date']);

		// permalink
		$trc->setpermalink("files/view/" . $id);

		// discipline
		$trc->setdiscipline("chemistry");

		// subdiscipline
		$trc->setsubdiscipline("physical chemistry");

		// related
		//$trc->setrelated();

		// setup variables for code below
		$measidx = $sysidx = $chmidx = $subidx = 0;
		$aspects = $facets = $datasets = [];
		$meths = $syss = $subs = $chms = [];
		$methunq = $sysunq = $subunq = $chmunq = [];
		$syslinks=[];

		// chemical facets
		// $subs => [chem orgnum] => sub id
		foreach ($chems as $chem) {
			if (!array_key_exists($chem['name'], $chmunq)) {
				$chmtmp = [];
				if (!is_null($chem['name'])) {
					$chmtmp['name'] = $chem['name'];
				}
				if (!is_null($chem['source'])) {
					$chmtmp['source'] = $chem['source'];
				}
				if (!is_null($chem['purity'])) {
					$purity = json_decode($chem['purity'], true);
					$steps = [];
					foreach ($purity as $step) {
						$steptmp = [];
						$steptmp['part'] = $step['type'];
						if (!is_null($step['analmeth'])) {
							$steptmp['analysis'] = $step['analmeth'];
						}
						if (!is_null($step['purimeth'])) {
							$steptmp['purification'] = $step['purimeth'];
						}
						if (!is_null($step['purity'])) {
							$steptmp['number'] = $step['purity'];
						}
						if (!is_null($step['puritysf'])) {
							$steptmp['sigfigs'] = $step['puritysf'];
						}
						if (!is_null($step['purityunit_id'])) {
							$qudtid = $this->Unit->getfield('qudt', $step['purityunit_id']);
							$steptmp['unit'] = 'qudt:' . $qudtid;
						}
						$steps[] = $steptmp;
					}
					$chmtmp['purity'] = $steps;
				}
				// substance info
				$sub = $chem['Substance'];
				$subidx++;
				if (!array_key_exists($sub['name'], $subunq)) {
					$subtmp = [];
					if (!is_null($sub['name'])) {
						$subtmp['name'] = $sub['name'];
					}
					if (!is_null($sub['subtype'])) {
						$subtmp['type'] = $sub['subtype'];
					}
					if (!is_null($sub['formula'])) {
						$subtmp['formula'] = $sub['formula'];
					}
					if (!is_null($sub['casrn'])) {
						$subtmp['casrn'] = $sub['casrn'];
					}
					if (isset($sub['Identifier'])) {
						$opts = ['inchi', 'inchikey', 'iupacname'];
						foreach ($sub['Identifier'] as $idn) {
							foreach ($opts as $opt) {
								if ($idn['type'] == $opt) {
									$subtmp[$opt] = $idn['value'];
								}
							}
						}
					}
					$facets['sci:compound'][$chem['orgnum']] = $subtmp;
					$subunq[$sub['name']] = $sub['id'];
					$subs[$chem['orgnum']] = $sub['id'];
				} else {
					$subs[$chem['orgnum']] = $subunq[$sub['name']];
				}
				$chmtmp['substance'] = '/substance/' . $chem['orgnum'] . '/';
				$chms[$chem['orgnum']] = $chem['id'];
				$chmunq[$chem['name']] = $chem['id'];
				$facets['sci:chemical'][$chem['orgnum']] = $chmtmp;
			} else {
				// link to existing system
				$chms[$chmidx] = $chmunq[$chem['name']];
			}
		}

		// iterate over each dataset as data, systems, methodology all related
		foreach ($sets as $setidx => $set) {

			// split out data
			$sers = $set['Dataseries'];
			$sprops = $set['Sampleprop'];
			$rprops = $set['Reactionprop'];
			$sys = $set['System'];

			// methodology aspects
			// $meths => [dataset idx][sampleprop idx][method idx]
			if (!empty($sprops)) {
				foreach ($sprops as $spidx => $sprop) {
					if (!array_key_exists($sprop['method_name'], $methunq)) {
						// add new methodology
						$measidx++;
						$mtmp = [];
						$mtmp['technique'] = $sprop['method_name'];
						$mtmp['property'] = $sprop['property_name'];
						$mtmp['phase'] = $sprop['phase'];
						$notes = "";
						if (!is_null($sprop['presentation'])) {
							$notes .= 'Presentation: ' . $sprop['presentation'];
						}
						if (!is_null($sprop['unceval'])) {
							if ($notes != "") {
								$notes .= ", ";
							}
							$notes .= 'Uncertainty evaluation: ' . $sprop['unceval'];
						}
						if (!is_null($sprop['uncconf'])) {
							if ($notes != "") {
								$notes .= ", ";
							}
							$notes .= 'Uncertainty confidence level: ' . $sprop['uncconf'];
						}
						$mtmp['notes'] = $notes;
						$aspects['sci:measurement'][$measidx] = $mtmp;
						$methunq[$sprop['method_name']] = $measidx;
						$meths[$setidx][$spidx] = $measidx;
					} else {
						// link to existing methodology
						$meths[$setidx][$spidx] = $methunq[$sprop['method_name']];
					}
				}
			}

			// system facets
			// substances
			if (!array_key_exists($sys['name'], $sysunq)) {
				// add new system
				$sysidx++;
				$systmp = [];
				if (!is_null($sys['name'])) {
					$systmp['name'] = $sys['name'];
				}
				if (!is_null($sys['phase'])) {
					$systmp['phase'] = $sys['phase'];
				}
				if (!is_null($sys['subtype'])) {
					$systmp['type'] = $sys['subtype'];
				}
				if (!is_null($sys['composition'])) {
					$systmp['composition'] = $sys['composition'];
				}

				// constituent...
				$rsubs = array_flip($subs);
				if (count($sys['Substance']) == 1) {
					$systmp['source'] = 'compound/' . $rsubs[$sys['Substance'][0]['id']] . '/';
				} else {
					$systmp['constituents'] = [];
					foreach ($sys['Substance'] as $const) {
						$systmp['constituents'][] = 'compound/' . $rsubs[$const['id']] . '/';
					}
				}

				$facets['sci:chemicalsystem'][$sysidx] = $systmp;
				$sysunq[$sys['name']] = $sysidx;
				$syss[$setidx] = $sysidx;
			} else {
				// link to existing system
				$syss[$setidx] = $sysunq[$sys['name']];
			}

			// group dataset data for subsequent processing
			$datasets[$setidx] = $sers;

			// ad syslink
			$syslinks[$setidx]='chemicalsystem/'.$sysidx.'/';
		}

		$conds = $sers = $datas = [];
		$condsj = $sersj = $datasj = [];
		$condunq = $scondlinks = $condlinks = $condmap = [];

		// create unique conditions array
		foreach ($datasets as $setidx => $series) {
			foreach ($series as $seridx => $ser) {
				foreach ($ser['Condition'] as $scondidx => $scval) {
					$propid = $scval['property_id'];
					if (!isset($condunq[$propid]) || !in_array($scval['number'], $condunq[$propid])) {
						$condunq[$propid][] = $scval['number'];
					}
				}
				foreach ($ser['Datapoint'] as $pntidx => $point) {
					foreach ($point['Condition'] as $condidx => $cval) {
						$propid = $cval['property_id'];
						if (!isset($condunq[$propid]) || !in_array($cval['number'], $condunq[$propid])) {
							$condunq[$propid][] = $cval['number'];
						}
					}
				}
			}

		}

		// sort condition values and create condition map
		$mapidx = 1;
		foreach ($condunq as $i => $prop) {
			$condmap[$i] = $mapidx;
			$mapidx++;
			sort($condunq[$i]);
		}

		// get conditions (series and regular)
		foreach ($datasets as $setidx => $series) {
			foreach ($series as $seridx => $ser) {
				if(!empty($ser['Condition'])) {
					foreach ($ser['Condition'] as $scondidx => $scond) {
						$propid = $scond['property_id'];
						$condunqidx = array_search($scond['number'], $condunq[$propid]);
						if (!isset($conds[$propid][$condunqidx])) {
							$v = [];
							if (empty($conds[$propid])) {
								$conds[$propid]['quantity'] = strtolower($scond['Property']['Quantity']['name']);
								$conds[$propid]['property'] = $scond['Property']['name'];
								$conds[$propid]['value'] = [];
							}
							if (!is_null($scond['number'])) {
								$v['number'] = $scond['number'];
								$v['sigfigs'] = $scond['accuracy'];
								if (isset($scond['Unit']['qudt']) && !empty($scond['Unit']['qudt'])) {
									$v['unitref'] = 'qudt:' . $scond['Unit']['qudt'];
								} elseif (isset($scond['Unit']['symbol']) && !empty($scond['Unit']['symbol'])) {
									$v['unitstr'] = $this->Dataset->qudt($scond['Unit']['symbol']);
								}
							} else {
								$v['text'] = $scond['text'];
							}
							$conds[$propid]['value'][$condunqidx] = $v;
						}
						$scondlinks[$setidx][$seridx][$scondidx] = 'condition/' . $condmap[$propid] . '/value/' . ($condunqidx + 1) . '/';
					}
				} else {
					$scondlinks[$setidx][$seridx] = null;
				}

				foreach ($ser['Datapoint'] as $pntidx => $point) {
					if(!empty($point['Condition'])) {
						foreach ($point['Condition'] as $condidx => $cond) {
							$propid = $cond['property_id'];
							$condunqidx = array_search($cond['number'], $condunq[$propid]);
							if (!isset($conds[$propid][$condunqidx])) {
								$v = [];
								if (empty($conds[$propid])) {
									$conds[$propid]['quantity'] = strtolower($cond['Property']['Quantity']['name']);
									$conds[$propid]['property'] = $cond['Property']['name'];
									$conds[$propid]['value'] = [];
								}
								if (!is_null($cond['number'])) {
									$v['number'] = $cond['number'];
									$v['sigfigs'] = $cond['accuracy'];
									if (isset($cond['Unit']['qudt']) && !empty($cond['Unit']['qudt'])) {
										$v['unitref'] = 'qudt:' . $cond['Unit']['qudt'];
									} elseif (isset($cond['Unit']['symbol']) && !empty($cond['Unit']['symbol'])) {
										$v['unitstr'] = $this->Dataset->qudt($cond['Unit']['symbol']);
									}
								} else {
									$v['text'] = $cond['text'];
								}
								$conds[$propid]['value'][$condunqidx] = $v;
							}
							$condlinks[$setidx][$seridx][$pntidx][$condidx] = 'condition/' . $condmap[$propid] . '/value/' . ($condunqidx + 1) . '/';
						}
					} else {
						$condlinks[$setidx][$seridx][$pntidx] = null;
					}

					foreach ($point['Data'] as $datidx => $datum) {
						$datas[$setidx][$seridx][$pntidx][$datidx] = $datum;
					}
				}
			}
		}

		// sort conditions
		foreach ($conds as $i => $prop) {
			sort($conds[$i]['value']);
		}
		// add conditions to facets
		foreach ($conds as $propid => $values) {
			$facets['sci:condition'][$condmap[$propid]] = $values;
		}

		// get data
		foreach ($datas as $setidx => $dataset) {
			$datums['dataset'][$setidx] = [];
			$datums['dataset'][$setidx]['system'] = $syslinks[$setidx];
			foreach ($dataset as $seridx => $series) {
				$datums['dataset'][$setidx]['dataseries'][$seridx] = [];
				if(!is_null($scondlinks[$setidx][$seridx])) {
					$datums['dataset'][$setidx]['dataseries'][$seridx]['conditions'] = $scondlinks[$setidx][$seridx];
				}
				foreach ($series as $pntidx => $point) {
					$datums['dataset'][$setidx]['dataseries'][$seridx]['datapoints'][$pntidx] = [];
					foreach ($point as $datidx => $datum) {
						$v = [];
						//$v['quantity'] = strtolower($datum['Property']['Quantity']['name']);
						$v['property'] = $datum['Property']['name'];
						if(!is_null($condlinks[$setidx][$seridx][$pntidx])) {
							$v['conditions'] = $condlinks[$setidx][$seridx][$pntidx];
						}
						$v['value'] = [];
						if (!is_null($datum['number'])) {
							$v['value']['number'] = $datum['number'];
							$v['value']['sigfigs'] = $datum['accuracy'];
							if (isset($datum['Unit']['qudt']) && !empty($datum['Unit']['qudt'])) {
								$v['value']['unitref'] = 'qudt:' . $datum['Unit']['qudt'];
							} elseif (isset($datum['Unit']['symbol']) && !empty($datum['Unit']['symbol'])) {
								$v['value']['unitstr'] = $this->Dataset->qudt($datum['Unit']['symbol']);
							}
						} else {
							$v['text'] = $datum['text'];
						}
						$datums['dataset'][$setidx]['dataseries'][$seridx]['datapoints'][$pntidx] = $v;
					}
				}
			}
		}

		// aspects
		$trc->setaspects($aspects);

		// facets
		$trc->setfacets($facets);


		$data=$trc->asarray();
		debug($data);exit;

		// facets
		$trc->setdata($datums);

		// sources
		$sources = [];
		// Original Paper
		$paper = [];
		$paper['journal']=$ref['journal'];
		if ($ref['bibliography'] != null) {
			$paper['citation'] = $ref['bibliography'];
		} elseif ($ref['citation'] != null) {
			if (stristr($ref['citation'], '[{')) {
				preg_match('/\[{.+}\]/', $ref['citation'], $match);
				$aus = json_decode($match[0], true);
				$austr = "";
				$aucnt = count($aus);
				foreach ($aus as $idx => $au) {
					if ($idx == $aucnt - 1) {
						$austr .= " and ";
					}
					$austr .= $au['firstname'] . ' ' . $au['lastname'];
					if ($idx < ($aucnt - 2)) {
						$austr .= ", ";
					}
				}
				$ref['citation'] = preg_replace('/\[{.+}\]/', $austr, $ref['citation']);
			}
			$paper['citation'] = $ref['citation'];
		}
		if (isset($ref['doi']) && $ref['doi'] != null) {
			$paper['url'] = "http://dx.doi.org/" . $ref['doi'];
		}
		if (isset($ref['url']) && $ref['url'] != null) {
			$paper['url'] = $ref['url'];
		}
		$paper['type'] = 'text';
		$sources[] = $paper;
		// ThermoML
		$thermoml = [];
		$thermoml['citation'] = "TRC Group ThermoML Archive, NIST - http://www.trc.nist.gov/ThermoML/";
		$thermoml['url'] = 'https://trc.nist.gov/ThermoML/10.1021/' . $file['filename'];
		$thermoml['type'] = 'dataset';
		$sources[] = $thermoml;
		$trc->setsources($sources);

		// rights
		$rights = [];
		$rights['holder'] = 'NIST - TRC Group, Boulder CO';
		$rights['license'] = 'http://creativecommons.org/publicdomain/zero/1.0/';
		$rights['url'] = 'https://trc.nist.gov/ThermoML/';
		$trc->setrights($rights);

		if($format=="jsonld") {
			header("Content-Type: application/ld+json");
			echo $trc->asjsonld();exit;
		} else {
			$data=$trc->asarray();
			//debug($data);exit;
			$this->set("raw",$raw);
			$this->set("data",$data);
		}
	}

	/**
     * **Deprecated - See view** Generate SciData
     * @param $id
     * @param $down
     */
    public function scidata($id,$down="")
    {
        // Note: there is an issue with the retrival of substances under system if id is not requested as a field
        // This is a bug in CakePHP as it works without id if its at the top level...
        $contains=[
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
            'Propertytype'=> [
                'Property'=>['fields'=>['name'],
                    'Quantity'=>['fields'=>['name']]]
            ],
            'System'=>['fields'=>['id','name','description','type'],
                'Substance'=>['fields'=>['name','formula','molweight'],
                    'Identifier'=>['fields'=>['type','value'],'conditions'=>['type'=>['inchi','inchikey','iupacname']]]]
            ],
            'Report',
            'File'=>['Reference']
        ];
        $data=$this->Dataset->find('first',['conditions'=>['Dataset.id'=>$id],'contain'=>$contains,'recursive'=>-1]);
        debug($data);exit;

        $rpt=$data['Report'];
        $ptype=$data['Propertytype'];
        $set=$data['Dataset'];
        $file=$data['File'];
        $pub=$file['Publication'];
        $ref=$data['Reference'];
        $ser=$data['Dataseries'];
        $sys=$data['System'];
        //debug($ser);exit;

        // Other systems -> related
        $othersys=$this->Dataset->find('list',['fields'=>['id'],'conditions'=>['system_id'=>$sys['id'],'propertytype_id'=>$ptype['id'],'NOT'=>['Dataset.id'=>$id]]]);
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
        $json['title']=$rpt['title'];
        $json['author']=[];
        if($ref['authors']!=null) {
            if(stristr($ref['authors'],'[{')) {
                $authors=json_decode($ref['authors'],true);
            } else {
                $authors=explode(", ",$ref['authors']);
            }
            $acount=1;
            foreach ($authors as $au) {
                $json['author'][]=['@id'=>'author/'.$acount,'@type'=>'dc:creator','name'=>$au];
                $acount++;
            }
        }
        $json['description']=$rpt['title'];
        $json['publisher']='Springer Nature';
        $json['startdate']=$set['updated'];
        $json['permalink']="http://chalk.coas.unf.edu/trc/datasets/view/".$id;
        foreach($othersys as $os) {
            $json['related'][]="http://chalk.coas.unf.edu/trc/datasets/view/".$os;
        }
        $json['toc']=['@id'=>'toc','@type'=>'dc:tableOfContents','sections'=>[]];

        // Process data series to split out conditions, and settings
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
                                $v['unitref'] = $this->Dataset->qudt($s['Unit']['symbol']);
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
            $json['toc']['sections'][]="system";
            $sysj['@id']='system';
            $sysj['@type']='sci:system';
            $sysj['discipline']='chemistry';
            $sysj['subdiscipline']='physical chemistry';
            $sysj['facets']=[];
        }


        // Data
        $resj=[];
        if(is_array($datas)&&!empty($datas)) {
            $json['toc']['sections'][] = "dataset";
            $resj['@id'] = 'dataset';        // System sections
            // Mixture/Substance
            $type='';
            if(is_array($sys)&&!empty($sys)) {
                // System
                if (count($sys['Substance']) == 1) {
                    $type = "substance";
                } else {
                    $type = "mixture";
                }
                $sid = $type . "/1";
                $json['toc']['sections'][] = $sid;
                $mixj['@id'] = $sid;
                $mixj['@type'] = "sci:" . $type;
                $opts = ['name', 'description', 'type'];
                foreach ($opts as $opt) {
                    if (isset($sys[$opt]) && $sys[$opt] != "") {
                        $mixj[$opt] = $sys[$opt];
                    }
                }
                if (isset($sys['Substance'])) {
                    for ($j = 0; $j < count($sys['Substance']); $j++) {
                        // Components
                        $subj['@id'] = $sid . "/component/" . ($j + 1);
                        $subj['@type'] = "sci:chemical";
                        $subj['source'] = "compound/" . ($j + 1);
                        $mixj['components'][] = $subj;
                        // Chemicals
                        $sub = $sys['Substance'][$j];
                        $chmj['@id'] = "compound/" . ($j + 1);
                        $json['toc']['sections'][] = $chmj['@id'];
                        $chmj['@type'] = "sci:compound";
                        $opts = ['name', 'formula', 'molweight'];
                        foreach ($opts as $opt) {
                            if (isset($sub[$opt]) && $sub[$opt] != "") {
                                $chmj[$opt] = $sub[$opt];
                            }
                        }
                        if (isset($sub['Identifier'])) {
                            $opts = ['inchi', 'inchikey', 'iupacname'];
                            foreach ($sub['Identifier'] as $idn) {
                                foreach ($opts as $opt) {
                                    if ($idn['type'] == $opt) {
                                        $chmj[$opt] = $idn['value'];
                                    }
                                }
                            }
                        }
                        $sysj['facets'][] = $chmj;
                    }
                }
                $sysj['facets'][] = $mixj;
            }
            // Conditions
            if(is_array($conds)&&!empty($conds)) {
                foreach($conds as $cid=>$cond) {
                    //debug($cond);exit;
                    $v=$vs=$condj = [];
                    $condj['@id'] = "condition/".($cid + 1);
                    $json['toc']['sections'][] = $condj['@id'];
                    $condj['@type'] = "sci:condition";
                    $condj['quantity'] = strtolower($cond[0]['Property']['Quantity']['name']);
                    $condj['property'] = $cond[0]['Property']['name'];
                    foreach ($cond as $cidx => $c) {
                        if(!in_array($c['number'],$vs)) {
                            $vs[]=$c['number'];
                            $v['@id'] = "condition/" . ($cid + 1) . "/value/".(array_search($c['number'],$vs)+1);
                            $v['@type'] = "sci:value";
                            if (!is_null($c['number'])) {
                                $v['number'] = $c['number'];
                                if (isset($c['Unit']['symbol']) && !empty($c['Unit']['symbol'])) {
                                    $v['unitref'] = $this->Dataset->qudt($c['Unit']['symbol']);
                                }
                            } else {
                                $v['text'] = $c['text'];
                            }
                            $condj['value'][] = $v;
                        }
                        $conds[$cid][$cidx]['clink'][]="condition/".($cid+1)."/value/".(array_search($c['number'],$vs)+1);
                    }
                    $sysj['facets'][] = $condj;
                }
            }
            $json['scidata']['system']=$sysj;

            $resj['@type'] = 'sci:dataset';
            $resj['source'] = 'measurement/1';
            $resj['scope'] = $type . '/1';
            $resj['datagroup'] = [];
            // Group
            foreach($datas as $did=>$data) {
                $grpj['@id']='datagroup/'.($did+1);
                $json['toc']['sections'][] = $grpj['@id'];
                $grpj['@type'] = 'sci:datagroup';
                $grpj['quantity']=strtolower($data[0]['Property']['Quantity']['name']);
                $grpj['property']=$data[0]['Property']['name'];
                foreach($data as $d=>$dtm) {
                    $dtmj=[];
                    $dtmj['@id'] = 'datagroup/'.($did+1).'/datapoint/'.($d+1);
                    $dtmj['@type'] = 'sci:datapoint';
                    $dtmj['conditions']=$conds[$did][$d]['clink'];
                    if(!empty($setts)) {
                        $dtmj['settings']=$setts[$did][$d]['slink'];
                    } else {
                        $dtmj['settings']=[];
                    }
                    // Value
                    $v=[];
                    if(!is_null($dtm['number'])) {
                        $unit="";
                        if(isset($dtm['Unit']['symbol'])&&!empty($dtm['Unit']['symbol'])) {
                            $unit=$this->Dataset->qudt($dtm['Unit']['symbol']);
                        }
                        if($dtm['datatype']=="datum") {
                            $v['@id']=$dtmj['@id']."/value";
                            $v['@type']="sci:value";
                            $v['number']=$dtm['number'];
                            if($unit!="") { $v['unitref']=$unit; }
                            $dtmj['value']=$v;
                        } else {
                            $v['@id']=$dtmj['@id']."/valuearray";
                            $v['@type']="sci:valuearray";
                            $v['numberarray']=json_decode($dtm['number'],true);
                            if($unit!="") { $v['unitref']=$unit; }
                            $dtmj['value']=$v;
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
        // Publication
        $volume=['@id'=>'reference/2','@type'=>'dc:source'];
        $volume['citation'] = $pub['citation'];
        if(isset($pub['doi'])&&$pub['doi']!=null) {
            $volume['url']="http://dx.doi.org/".$pub['doi'];
        }
        if(isset($pub['url'])&&$pub['url']!=null) {
            $volume['url']=$pub['url'];
        }
        if(isset($pub['eisbn'])&&$pub['eisbn']!=null) {
            $volume['eisbn'] = $pub['eisbn'];
        }
        $json['references'][]=$paper;
        $json['references'][]=$volume;

        // Rights
        $json['rights']=['@id'=>'rights','@type'=>'dc:rights'];
        $json['rights']['holder']='NIST - TRC Group, Boulder CO';
        $json['rights']['license']='http://creativecommons.org/publicdomain/zero/1.0/';

        // OK turn it back into JSON-LD
        header("Content-Type: application/ld+json");
        if($down=="download") { header('Content-Disposition: attachment; filename="'.$id.'.jsonld"'); }
        echo json_encode($json,JSON_UNESCAPED_UNICODE);exit;

    }

}
