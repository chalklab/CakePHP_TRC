<?php

/**
 * Class DatasetsController
 * controller of actions for the datasets table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 12/17/22
 */
class DatasetsController extends AppController
{
	public $uses = ['Chemical','ChemicalsDataset','Condition','Crosswalk','Data','Dataset','Dataseries','File',
		'Journal','Quantity','Reference','Report','Sampleprop','Scidata','Substance','System','Unit'];

	public array $sdmodel = [
		'Chemical' => ['fields' => ['id', 'orgnum', 'sourcetype'],
			'Purificationstep','Substance'=>['fields'=>['name']]],
		'Dataseries' => [
			'Condition' => [
				'Unit',
				'Phase',
				'Compohnent',
				'Quantity' => ['fields' => ['id', 'name', 'kind'],
					'Quantitykind' => ['fields' => ['id', 'name']]]],
			'Datapoint' => [
				'Condition' => ['fields' => ['id', 'datapoint_id', 'quantity_id', 'system_id',
					'number', 'significand', 'exponent', 'unit_id','phase_id', 'accuracy', 'exact'],
					'Unit',
					'Phase' => [
						'Phasetype'],
					'Compohnent',
					'Quantity' => ['fields' => ['id', 'name', 'kind', 'phase'],
						'Quantitykind' => ['fields' => ['id', 'name']]]],
				'Data' => ['fields' => ['id', 'datapoint_id', 'quantity_id', 'sampleprop_id','component_id', 'number',
					'significand', 'exponent', 'error', 'error_type', 'unit_id', 'accuracy', 'exact'],
					'Unit',
					'Phase' => [
						'Phasetype'],
					'Sampleprop',
					'Compohnent',
					'Quantity' => ['fields' => ['id', 'name', 'kind','phase'],
						'Quantitykind' => ['fields' => ['id', 'name']]]]
			]
		],
		'File',
		'Mixture' => [
			'Phase' => [
				'Phasetype'],
			'Compohnent'],
		'Report',
		'Reference' => [
			'Journal'],
		'Sampleprop',
		'System' => [
			'Substance' => ['fields' => ['name', 'formula', 'mw', 'type'],
				'Identifier' => ['fields' => ['type', 'value'], 'conditions' => ['type' => ['inchi', 'inchikey', 'iupacname','casrn']]],
			]
		],
	];

	public array $sdfmodel = [
		'Chemical' => [
			'Purificationstep'],
		'Dataseries' => [
			'Condition' => [
				'Unit',
				'Phase',
				'Compohnent',
				'Quantity' => [
					'Quantitykind']],
			'Datapoint' => [
				'Condition' => [
					'Unit',
					'Phase' => [
						'Phasetype'],
					'Compohnent',
					'Quantity' => [
						'Quantitykind']],
				'Data' => [
					'Unit',
					'Phase' => [
						'Phasetype'],
					'Sampleprop',
					'Compohnent',
					'Quantity' => [
						'Quantitykind']]]],
		'File',
		'Mixture' => [
			'Phase' => [
				'Phasetype'],
			'Compohnent'],
		'Report',
		'Reference' => [
			'Journal'],
		'Sampleprop',
		'System' => [
			'Substance' => [
				'Identifier'
			]
		],
	];

	/**
	 * beforeFilter function
	 */
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Auth->allow('index','view','scidata','recent','sddslist','exportjld');
	}

	/**
	 * view list of datasets
	 * @return void
	 */
	public function index()
	{
		//$data = $this->Reference->find('list', ['fields' => ['Reference.id','Reference.title','Journal.name'], 'contain' => ['Journal'],'order'=>['Journal.name','Reference.title']]);
		//$data = $this->Reference->find('list', ['fields' => ['Reference.id','Reference.title','Quantity.name'], 'contain' => ['Dataset'=>['Data'=>['Quantity']]],'order'=>['Quantity.name','Reference.title']]);
		// get references sorted by quantity of experimental data
		// Dataset.reference_id needed as both key and value in order to speed view rendering (see ** in Datasets/indx.ctp)
		$qr=$this->Data->find('list',['fields'=>['Dataset.reference_id','Dataset.reference_id','Data.quantity_id'],'contain'=>['Dataset'],'group'=>['Data.quantity_id','Dataset.reference_id'],'recursive'=>-1]);
		// get lists for quantities and references...
		$rs=$this->Reference->find('list',['fields'=>['id','title'],'order'=>'title','recursive'=>-1]);
		$qs=$this->Quantity->find('list',['fields'=>['id','name'],'order'=>'name','recursive'=>-1]);
		$this->set('qr', $qr);
		$this->set('rs', $rs);
		$this->set('qs', $qs);
	}

	/**
	 * view a dataset
	 * @param string|int $id
	 * @param int|null $serid
	 * @param string|null $layout
	 * @return void
	 */
	public function view($id, int $serid=null, string $layout=null)
	{
		$ref =['id','authors','year','volume','issue','startpage','endpage','title','doi'];
		$con =['id','datapoint_id','system_id','number','significand','exponent','unit_id','accuracy'];
		$qty =['id','name','phase','field','label','symbol','definition','updated'];
		$c=['Dataseries'=>[
				'Condition'=>['Compohnent','Unit','Phase','Quantity'],
				'Datapoint'=>[
					'Condition'=>['fields'=>$con,'Unit','Compohnent','Phase','Quantity'=>['fields'=>$qty,'Quantitykind']],
					'Data'=>['Unit','Sampleprop','Compohnent','Phase','Quantity'=>['fields'=>$qty,'Quantitykind']]
				]
			],
			'File',
			'Sampleprop',
			'Report',
			'Reference'=>['fields'=>$ref,'Journal'=>['fields'=>['id','name']]],
			'System'=>[
				'Substance'=>[
					'Identifier'=>['fields'=>['type','value']]
				]
			],
			'Mixture'=>[
				'Compohnent'=>[
					'Chemical'=>[
						'Substance'=>[
							'Identifier'=>['fields'=>['type','value']]]]],
				"Phase"=>['Phasetype']
			]
		];

		// get data by id or trcidset
		if(stristr($id,'-')) {
			$data=$this->Dataset->find('first',['conditions'=>['Dataset.trcidset_id'=>$id],'recursive'=>-1]);
			$id = $data['Dataset']['id']; // reset $id to dataset id field
		}
		$dump = $this->Dataset->find('first', ['conditions'=>['Dataset.id' => $id],'contain'=>$c,'recursive'=>-1]);
		if($layout=='dump') { debug($dump);exit; }

		// get first datum of first dataseries (if set) to generate labels for plot
		$datum = $dump["Dataseries"][0]["Datapoint"][0];$xlabel = "";
		if(isset($datum["Condition"][0])) {
			$xname = $datum["Condition"][0]["Quantity"]["name"];
			$xunit = $datum["Condition"][0]["Unit"]["symbol"];
			$xlabel = $xname . ", " . $xunit;
		}
		$ylabel = $datum["Data"][0]["Sampleprop"]["quantity_name"];

		// loop through the dataseries to get data to plot
		$sers = $dump['Dataseries'];
		$xy[0] = $xs = $ys = [];
		$num=$minx=$miny=$maxx=$maxy=$errormin=$errormax=0;
		foreach ($sers as $idx => $ser) {
			$count = 1;
			$xy[0][] = ["label" => $xlabel, "role" => "domain", "type" => "number"];
			$xy[0][] = ["label" => 'Series ' . ($idx + 1), "role" => "data", "type" => "number"];
			$xy[0][] = ["label" => "Min Error", "type" => "number", "role" => "interval"];
			$xy[0][] = ["label" => "Max Error", "type" => "number", "role" => "interval"];
			$points = $ser['Datapoint'];
			$num++;
			foreach ($points as $pnt) {
				if (isset($pnt['Condition'][0])) {
					$x = $pnt['Condition'][0]['number'] + 0;
				} else {
					$x = 0;
				}
				$y = $pnt['Data'][0]['number'] + 0;
				$error = $pnt['Data'][0]['error'] + 0;
				$errormin = $y - $error;
				$errormax = $y + $error;

				$xs[] = $x;
				$minx = min($xs) - (0.02 * (min($xs)));
				$maxx = max($xs) + (0.02 * (min($xs)));
				$ys[] = $y;
				$miny = min($ys) - (0.02 * (min($ys)));
				$maxy = max($ys) + (0.02 * (min($ys)));

				$xy[$count][] = $x;
				$xy[$count][] = $y;
				$xy[$count][] = $errormin;
				$xy[$count][] = $errormax;
				$count++;
			}
		}

		//debug($dump);exit;
		// send variable to the view
		$this->set('xy', $xy);
		$this->set('maxx', $maxx);
		$this->set('maxy', $maxy);
		$this->set('minx', $minx);
		$this->set('miny', $miny);
		$this->set('errormin', $errormin);
		$this->set('errormax', $errormax);
		$this->set('name', 'title');
		$this->set('xlabel', $xlabel);
		$this->set('ylabel', $ylabel);
		$this->set('dump', $dump);
		$fid = $dump['Dataset']['file_id'];

		// get a list of datsets that come from the same XML file (paper)
		$c=['Dataset.file_id'=>$fid,'NOT'=>['Dataset.id'=>$id]];
		$related = $this->Dataset->find('list', ['conditions'=>$c,'recursive'=>-1]);
		if (!is_null($layout)) {
			$this->set('serid', $serid);
			$this->render('data');
		}
		$this->set('related', $related);
		$this->set('dsid', $id);
		$title = $dump['Dataset']['title'];
		$this->set('title', $title);
		if($this->request->is('ajax')) { echo '{ "title": "'.$title.'" }';exit; }
	}

	/**
	 * function to find the most recent datasets
	 * normally accessed via a requestAction for insertion into a page
	 * no view file
	 * @return void
	 */
	public function recent()
	{
		$data = $this->Dataset->find('list', ['order' => ['updated' => 'desc'], 'limit' => 6]);
		if ($this->request->params['requested']) { return $data; }
		$this->set('data', $data);
	}

	// functions requiring login (not in Auth::allow)

	/**
	 * delete a dataset (and all data underneath)
	 * @param $id
	 */
	public function delete($id)
	{
		if ($this->Dataset->delete($id)) {
			$this->Flash->set('Dataset ' . $id . ' deleted!');
		} else {
			$this->Flash->set('Dataset ' . $id . ' could not be deleted!');
		}
		$this->redirect('/datasets/index');
	}

	/**
	 * create json load of all dataset endpoints
	 * used to process jsonld -> N-Quads in python
	 * @param int $jid
	 * @param int $off
	 */
	public function sddslist(int $jid=1,int $off=1)
	{
		$sets=$this->Dataset->find('list',['fields'=>['id','trcidset_id'],'conditions'=>['Reference.journal_id'=>$jid],
			'contain'=>['Reference'],'order'=>'id','offset'=>$off,'recursive'=>-1]);  // TODO: offset not working :(
		$output=[];
		foreach($sets as $setid=>$trcid) {
			$output[$trcid]='https://sds.coas.unf.edu/trc/datasets/scidata/'.$setid;
		}
		$json=json_encode($output);
		header("Content-Type: application/json");
		echo $json;exit;
	}

	/**
	 * export jsonld files
	 * create a zip archive of all SciData JSON-LD files for a journal
	 * @param int $jid
	 * @param int $limit
	 * @param int $offset
	 * @throws
	 */
	public function exportjld(int $jid=1, int $limit=150000, int $offset=0)
	{
		$sets=$this->Dataset->find('list',['fields'=>['id','refsetnum'],
			'conditions'=>['Reference.journal_id'=>$jid],'contain'=>['Reference'],
			'order'=>'points','offset'=>$offset,'limit'=>$limit,'recursive'=>-1]);
		$setcnt=count($sets);//debug($sets);exit;
		$start=str_pad($offset,5,'0',STR_PAD_LEFT);
		$end=str_pad($setcnt,5,'0',STR_PAD_LEFT);
		$folder='files_'.$jid.'_'.$start.'_'.$end;
		$zip = new ZipArchive;
		$zip->open($folder.'.zip', ZipArchive::CREATE);
		foreach($sets as $setid=>$refsetnum) {
			list($refid,$setnum)=explode(':', $refsetnum);
			$ref=$this->Reference->find('list',['fields'=>['id','doi'],'conditions'=>['id'=>$refid]]);
			$parts=explode("/",$ref[$refid]);
			$filename=$parts[1].'_'.$setnum;
			$url='https://sds.coas.unf.edu/trc/datasets/scidata/'.$setid;
			$hdrs=get_headers($url,true);
			if(stristr($hdrs[0],'200')) {
				$json = file_get_contents($url);
				if(stristr($json,'<span')) {
					$zip->close();
					sleep(5);
					chmod($folder.".zip", 0777);
					echo "Error in set ".$setid."<br/>";exit; //detects code error
				}
				echo "File '".$filename."' processed<br/>";
				$zip->addFromString($filename.".jsonld", $json);
			} else {
				echo "Download not valid ".$setid."<br/>";exit; //detects code error
			}
		}
		$zip->close();
		sleep(5);  # wait till the file is saved
		chmod($folder.".zip", 0777);
		exit;
	}

	/**
	 * identify JSON-LD files that have errors when accessed via the API
	 * @param int $jid
	 * @return void
	 */
	public function jlderrors(int $jid=1)
	{
		$sets=$this->Dataset->find('list',['fields'=>['id'],'conditions'=>['Reference.journal_id'=>$jid],'contain'=>['Reference'],'order'=>'id','recursive'=>-1]);
		foreach($sets as $setid) {
			$url='https://sds.coas.unf.edu/trc/datasets/scidata/'.$setid;
			$hdrs=get_headers($url,true);
			echo "> Processing set ".$setid."<br/>";
			if(stristr($hdrs[0],'200')) {
				$json = file_get_contents($url);
				if(stristr($json,'<span')) {
					echo "<br/>Error in set ".$setid."<br/>";
				}
			}
		}
		exit;
	}

	/**
	 * scidata function that uses the SciData class to create the file
	 * @param string $id
	 * @param string $down
	 * @return void
	 */
	public function scidata(string $id, string $down="")
	{
		// Note: there is an issue with the retrival of substances under system if id is not requested as a field
		// This is a bug in CakePHP as it works without id if it's at the top level...
		// model is defined above in $sdmodel class variable

		// get data for this dataset
		if(stristr($id,'_')) {
			$data=$this->Dataset->find('first',['conditions'=>['Dataset.trcidset_id'=>$id],'contain'=>$this->sdmodel,'recursive'=>-1]);
			$id = $data['Dataset']['trcidset_id']; // reset $id to dataset id field
		} else {
			$data=$this->Dataset->find('first',['conditions'=>['Dataset.id'=>$id],'contain'=>$this->sdmodel,'recursive'=>-1]);
		}

		// split data into different contextual variables
		$file = $data['File'];
		$chmf = $data['Chemical'];
		$mix = $data['Mixture'];
		$ref = $data['Reference'];
		$jnl = $ref['Journal'];
		$sers = $data['Dataseries'];
		$sys = $data['System'];
		$sprops = $data['Sampleprop'];

		// get the metadata crosswalk data
		$fields = $nspaces = $ontlinks = [];
		$this->getcw('metadata', $fields, $nspaces, $ontlinks);
		$this->getcw('conditions', $fields, $nspaces, $ontlinks);
		$this->getcw('exptdata', $fields, $nspaces, $ontlinks);
		$this->getcw('deriveddata', $fields, $nspaces, $ontlinks);
		$this->getcw('suppdata', $fields, $nspaces, $ontlinks);

		// create an instance of the Scidata class
		$sdpath = "https://scidata.unf.edu/";
		$doi = explode("/",$ref['doi']);
		$filid = $doi[1].'_'.$data['Dataset']['setnum'];
		$setuid = "trc_".$jnl['set']."_".$filid;
		$upath = $sdpath."tranche/trc/".$jnl['set'].'/'.$filid."/";
		$trc = new $this->Scidata;
		$trc->setcontexts([
			"https://stuchalk.github.io/scidata/contexts/crg_mixture.jsonld",
			"https://stuchalk.github.io/scidata/contexts/crg_chemical.jsonld",
			"https://stuchalk.github.io/scidata/contexts/crg_substance.jsonld"
		]);
		$trc->setnspaces($nspaces);
		$trc->setid($upath);
		$trc->setgenat(date("Y-m-d H:i:s"));
		$trc->setversion(1);
		$trc->setbase($upath);
		$trc->setuid($setuid);
		$trc->setgraphid($upath);
		$trc->settitle("SciData JSON-LD file of data and metadata from paper '".$ref['title']."'");
		$trc->setpublisher("Chalk Research Group, University of North Florida");
		$trc->setdescription('SciData JSON-LD file of data extracted from a ThermoML format XML file (see source section) available from the NIST TRC website https://trc.nist.gov/ThermoML/');
		$aus = [
			['name'=>'Montana Sloan','orcid'=>'0000-0003-2127-9752','role'=>'developer','gender'=>'female'],
			['name'=>'Stuart J. Chalk','orcid'=>'0000-0002-0703-7776','organization'=>'University of North Florida','role'=>'developer','email'=>'schalk@unf.edu']];
		$trc->setauthors($aus);
		//$trc->setstarttime($file['date']);  // removed by SJC in favor of adding created date to source file below
		$trc->setpermalink($sdpath."tranche/trc/".$jnl['set']."/".$filid.'.jsonld');
		$trc->setdiscipline("w3i:Chemistry");
		$trc->setsubdiscipline("w3i:PhysicalChemistry");

		// other datsets from same paper are added as 'related' data
		$cnds = ['system_id' => $sys['id'], 'file_id' => $file['id'], 'NOT' => ['Dataset.id' => $id]];
		$reldata = $this->Dataset->find('list', ['fields' => ['setnum'], 'conditions' => $cnds]);
		$related=[];
		foreach($reldata as $relset) { $related[]=$sdpath."tranche/trc/".$ref['Journal']['set']."/".$doi[1]."_".$relset.'.jsonld'; }
		$trc->setrelated($related);

		// nothing in the XML about the methodology so not methodology section

		// system section (add data to $facets)

		// mixture/substance/chemical
		$facets=$systems=$substances=$chemicals=[];
		if (is_array($sys) && !empty($sys)) {
			// get substances
			$subs = $sys['Substance'];
			foreach ($subs as $subidx => $sub) {
				$s = [];
				$s['name']=$sub['name'];
				$s['formula']=$sub['formula'];
				$s['molweight']=$sub['mw'];
				foreach ($sub['Identifier'] as $subid) {
					$s[$subid['type']] = $subid['value'];
				}
				$substances[($subidx + 1)] = $s;
			}
			// get chemicals
			foreach ($chmf as $chmidx => $chm) {
				$c = [];
				$c['substance#'] = 'substance/' . ($chmidx + 1) . '/';
				$c['name'] = $chm['Substance']['name'];
				$c['sourcetype'] = $chm['sourcetype'];
				//debug($chm);exit;
				if (!is_null($chm['Purificationstep'])) {
					$p=[];
					$p['@id']='chemical/'.($chmidx+1).'/purity/';
					$p["@type"]="sdo:purity";
					// removed after addition of purificationstep table
					// $purstep = json_decode($chm['purity'], true);
					$purstep = $chm['Purificationstep'];
					foreach ($purstep as $sidx => $step) {
						$s = [];
						$s["@id"]='chemical/'.($chmidx+1).'/purity/step/'.($sidx+1).'/';
						$s["@type"]="sdo:step";
						if(!is_null($step['type'])) {
							$s['part'] = $step['type'];
						}
						if (!is_null($step['analmeth'])) {
							$s['analysis'] = $step['analmeth'];
						}
						if (!is_null($step['purimeth'])) {
							$s['purification'] = $step['purimeth'];
						}
						if (!is_null($step['purity'])) {
							$val=$this->Dataset->exponentialGen($step['purity']);
							if($val['isint']) {
								$s['number'] = (int) $val['scinot'];
							} else {
								$s['number'] = (float) $val['scinot'];
							}
						}
						if (!is_null($step['puritysf'])) {
							$s['sigfigs'] = (int) $step['puritysf'];
						}
						if (!is_null($step['purityunit_id'])) {
							$uname = $this->Unit->getfield('name', $step['purityunit_id']);
							$s['unit'] = $uname;
							$qudtid = $this->Unit->getfield('qudt', $step['purityunit_id']);
							$s['unit#'] = 'qudt:' . $qudtid;
						}
						$p['steps'][$sidx] = $s;
					}
					$c['purity'][]=$p;
				}
				$chemicals[($chmidx+1)] = $c;
			}
		}

		# create the chemical system (mixture)
		$s = [];
		if (count($sys['Substance']) == 1) {
			// augment the chemical description rather than have a separate section of a 'pure substance'
			$chemicals[1]['composition'] = $sys['composition'];
			// assume that phase stated with a pure substance (but not a component) is about the pure substance
			$phases=$mix['Phase'];
			$ptypes=[];
			foreach($phases as $phase) {
				$ptypes[]=lcfirst($phase['Phasetype']['name']);
				$ptypes[]=lcfirst($phase['Phasetype']['type']);
			}
			$ptypes=array_values(array_unique($ptypes));
			$chemicals[1]['phase'] = $ptypes;
			//debug($chemicals[1]);exit;
		} else {
			// organize chemical <=> compohnent ids
			$cmp2org=[];
			foreach($chmf as $chm) { $cmp2org[$chm['id']] = ['orgnum'=>$chm['orgnum']]; }
			foreach($mix['Compohnent'] as $cmp) { $cmp2org[$cmp['chemical_id']]['compnum'] = $cmp['compnum']; }
			$type = "mixture";
			$sid = $type."/1/";
			$s['@id'] = $sid;
			$s['@type'] = "sdo:" . $type;
			$s['name']=$sys['name'];
			$s['composition'] = $sys['composition'];
			$phases=$mix['Phase'];$s['phase']=[];
			foreach($phases as $phase) {
				// add phases not specifically assigned to a compohnent
				if(is_null($phase['orgnum'])) {
					$s['phase'][]=lcfirst($phase['Phasetype']['name']);
					$s['phase'][]=$phase['Phasetype']['type'];
				}
			}
			if(empty($s['phase'])) {
				unset($s['phase']);
			} else {
				$s['phase']=array_values(array_unique($s['phase']));
			}
			// add mixture compohnents
			foreach($mix['Compohnent'] as $cmp) {
				$const=[];
				$const['chemical#']="chemical/".$cmp['compnum'].'/';
				$const['constituentNumber']=$cmp['compnum'];
				$s['constituents'][]=$const;
			}
			$systems[1] = $s;
		}
		//debug($systems);exit;

		# update facets
		$facets['sdo:substance'] = $substances;
		$facets['sdo:chemical'] = $chemicals;
		$facets['sdo:mixture'] = $systems;

		// conditions - separate in properties and conditions
		// write out properties and then reference them in each condition
		$srconds=[];$sigfigs=[];$props=[];$values=[];
		foreach($sers as $seridx=>$ser) {
			//debug($ser);exit;
			$sernum=$seridx+1;$cndcnt=0;
			$sconds=$ser['Condition']; // series conditions
			$pnts=$ser['Datapoint']; // all datapoints in a series
			foreach($pnts as $pntidx=>$pnt) {
				$pntnum = $pntidx+1;
				$conds = $pnt['Condition']; // conditions for datapoints (0 -> n)
				$cndcnt = count($conds);
				foreach($conds as $cndidx=>$cond) {
					//debug($cond);debug($ontlinks);
					// update number to correctly reflect the # sig figs
					$dp=$cond['accuracy']-($cond['exponent']+1);
					$cond['number']=number_format($cond['number'],$dp,'.','');
					$idxprop=($cndidx+1).':'.$cond['quantity_id'];
					$cphase = $cond['Phase']['Phasetype']['type'];
					if(!isset($props[$idxprop]['quantitykind'])) {
						$props[$idxprop]['quantitykind']=$cond['Quantity']['Quantitykind']['name'];
					}
					if(!empty($cond['Quantity']['kind'])) {
						$kind=$cond['Quantity']['kind'];
						$props[$idxprop]['quantitykind#'] = $ontlinks['conditions'][$kind];
					}
					if(!isset($props[$idxprop]['quantity'])) {
						if(($cond['component_id'])===NULL) {
							$props[$idxprop]['quantity'] = $cond['Quantity']['name'] . ' (' . $cphase . ')';
						} else {
							$ccomp=$cond['Compohnent']['compnum'];
							$props[$idxprop]['quantity'] = $cond['Quantity']['name']." of Component ".$ccomp." (". $cphase . ")";
							$props[$idxprop]['constituent'] = "constituent/".$ccomp."/";
						}
					}
					if(!isset($props[$idxprop]['phase'])) { $props[$idxprop]['phase']=$cphase; }
					if(!isset($props[$idxprop]['unit'])) {
						$props[$idxprop]['unit']=$cond['Unit']['name'];
					}
					if(!empty($cond['Unit']['qudt'])) {
						$props[$idxprop]['unit#']="qudt:".$cond['Unit']['qudt'];
					}
					//$values[$idxprop][]=$cond['number'];
					$srconds[$idxprop][$cond['number']][] = $sernum.":".$pntnum;
					$sigfigs[$idxprop][$cond['number']] = $cond['accuracy'];
					//debug($props);exit;
				}
			}
			foreach($sconds as $scndidx=>$scond) {
				// update number to correctly reflect the # sig figs
				//debug($scond);debug($ontlinks);exit;
				$dp=$scond['accuracy']-($scond['exponent']+1);
				$scond['number']=number_format($scond['number'],$dp,'.','');
				$idxprop=($scndidx+$cndcnt+1).':'.$scond['quantity_id'];
				if(!isset($props[$idxprop])) {
					$scphase = $scond['Phase']['Phasetype']['type'];
					if(!isset($props[$idxprop]['quantitykind'])) {
						$props[$idxprop]['quantitykind']=$scond['Quantity']['Quantitykind']['name'];
					}
					if(!empty($scond['Quantity']['kind'])) {
						$kind=$scond['Quantity']['kind'];
						$props[$idxprop]['quantitykind#'] = $ontlinks['conditions'][$kind];
					}
					$props[$idxprop]['quantity']=$scond['Quantity']['name'];
					$props[$idxprop]['phase']=$scphase;
					if(($scond['component_id'])===NULL) {
						$props[$idxprop]['quantity'] = $scond['Quantity']['name'].' ('.$scphase.')';
					} else {
						$sccomp=$scond['Compohnent']['compnum'];
						$props[$idxprop]['quantity'] = $scond['Quantity']['name']." of Component ".$sccomp." (".$scphase.")";
						$props[$idxprop]['constituent'] = "constituent/".$sccomp."/";
					}
					$props[$idxprop]['unit']=$scond['Unit']['name'];
					if(!empty($scond['Unit']['qudt'])) {
						$props[$idxprop]['unit#']="qudt:".$scond['Unit']['qudt'];
					}
				}

				// iterate over all datapoints to add condition to each
				foreach($pnts as $pntidx=>$pnt) {
					$pntnum=$pntidx+1;
					//$values[$idxprop][]=$scond['number'];
					$srconds[$idxprop][$scond['number']][] = $sernum.":".$pntnum;
					$sigfigs[$idxprop][$scond['number']] = $scond['accuracy'];
				}
			}
		}
		//debug($srconds);debug($sigfigs);exit;

		// generate condition values array from srconds
		foreach($srconds as $propid=>$valarr) {
			foreach($valarr as $val=>$locs) {
				$values[$propid][]=$val;
			}
		}
		//debug($values);debug($sigfigs);//exit;

		// reindex conditions array and sort values
		$tmp1=$values;$tmp2=$sigfigs;$values=[];$sigfigs=[];$cidx=1;
		foreach($tmp1 as $oldidx=>$con) {
			$values[$cidx] = $con;
			$sigfigs[$cidx]= $tmp2[$oldidx];
			$cidx++;
		}
		//debug($values);debug($sigfigs);exit;

		// add conditions properties to $facets
		$condpropidx=[];
		foreach($props as $condprop=>$data) {
			list($condidx,$propid) = explode(':',$condprop);
			$condpropidx[$condidx]=$propid;
		}
		foreach($condpropidx as $condidx=>$propid) {
			foreach($props as $condprop=>$data) {
				if($condprop==$condidx.":".$propid) {
					$props[$condidx]=$data;
					unset($props[$condidx.":".$propid]);
				}
			}
		}
		$facets['sdo:quantity'] = $props;

		// add condition values
		$condvals=[];$cidx=1;
		foreach($values as $qidx=>$valarr) {
			foreach($valarr as $val) {
				$condval=[];
				$condval['quantity#']='quantity/'.$qidx.'/';
				$condval['value']=$val;
				$condval['sf']=$sigfigs[$qidx][$val];
				$condvals[$cidx]=$condval;
				$cidx++;
			}
		}
		//debug($condvals);debug($srconds);//exit;
		// add row information to condvals
		foreach($srconds as $qidpid=>$srcond) {
			list($qid,)=explode(":",$qidpid);
			foreach($srcond as $value=>$rows) {
				foreach($condvals as $cvid=>$condval) {
					// internal links are indicated by @ as the last character of the label
					if($condval['quantity#']=='quantity/'.$qid.'/'&&$condval['value']==$value) {
						$condvals[$cvid]['rows']=$rows;continue 2;
					}
				}
			}
		}
		//debug($condvals);exit;
		$facets['sdo:condition'] = $condvals;
		//debug($facets);exit;

		// add facets data to instance
		$trc->setfacets($facets);
		//debug($trc->asarray(true));exit;
		//goto out;


		// Data
		// add data to $group
		$groups=[];
		foreach($sers as $seridx=>$ser) {
			$group=[];$sernum=$seridx+1;
			$group['title']='Series '.$sernum;
			if(count($sys['Substance'])==1) {
				$group['chemical#']="chemical/1/";
			} else {
				$group['mixture#']="mixture/1/";
			}
			$group['data']=[];
			foreach ($ser['Datapoint'] as $pntidx => $pnt) {
				$pntnum=$pntidx+1;
				foreach($pnt['Data'] as $datidx=>$datum) {
					//debug($datum);debug($ontlinks);exit;
					$val=[];
					if(!empty($sprops[$datidx])) {
						$datphase=strtolower($sprops[$datidx]['phase']);
					} else {
						$datphase='';
					}
					// TODO: add constituent link
					// TODO: fix issue with same component in multiple phases

					// phase
					$val['phase']=$datphase;
					// quantitykind
					$val['quantitykind']=$datum['Quantity']['Quantitykind']['name'];
					// quantity# (lookup property in $ontlinks and assign)
					if(!empty($datum['Quantity']['kind'])) {
						$kind=$datum['Quantity']['kind'];
						$val['quantitykind#'] = $ontlinks['exptdata'][$kind];
					}
					// quantity
					if(($datum['component_id'])===NULL) {
						$val['quantity'] = $datum['Quantity']['name']." (".$datphase.")";
					} else {
						$dpcomp=$datum['Compohnent']['compnum'];
						$val['quantity'] = $datum['Quantity']['name']." of component ". $dpcomp. " (".$datphase.")" ;
						$val['related'] = "constituent/".$dpcomp."/";
					}
					$quantphase=$val['quantity'];

					// number (and sigfigs if set)
					if(!empty($datum['accuracy'])) {
						// check that the number is correctly represented based on accuracy
						$dp=$datum['accuracy']-($datum['exponent']+1);
						$val['number']=number_format($datum['number'],$dp,'.','');
						$val['sigfigs']=$datum['accuracy'];
					} else {
						$val['number']=$datum['number'];
					}
					// unit
					if(!empty($datum['Unit']['name'])) {
						$val['unit']=$datum['Unit']['name'];
					}
					// unit#
					if(!empty($datum['Unit']['qudt'])) {
						$val['unit#']="qudt:".$datum['Unit']['qudt'];
					}
					// error
					if(!empty($datum['error'])) {
						$val['error']=(float) $datum['error'];
						$val['errortype']=$datum['error_type'];
					}
					if(!isset($group['data'][$quantphase])) { $group['data'][$quantphase]=[]; }
					$group['data'][$quantphase][$pntnum]=$val;
				}
			}
			$groups[$sernum]=$group;
		}
		//debug($groups);exit;
		$trc->setdatagroup($groups);

		// Sources
		// Get the DOI
		$bib="'".$ref['title']."' ".$ref['aulist']." ".$ref['Journal']['abbrev']." ".$ref['year']." ".$ref['volume']." ".$ref['startpage'];
		$src=[];
		$src['id']="source/".$ref['id'].'/';
		$src['type']="paper";
		$src['citation']=$bib;
		$src['url']='https://doi.org/'.$ref['doi'];
		$sources[]=$src;
		// add TRC dataset
		$src=[];
		$src['id']="source/2/";
		$src['citation']="NIST TRC ThermoML Archive";
		$src['url']="https://trc.nist.gov/ThermoML/".$ref['doi'].".xml";
		$src['type']="dataset";
		$src['created']=$file['date'];
		$sources[]=$src;
		$trc->setsources($sources);
		//debug($src);exit;

		// Rights
		$right=['@id'=>'rights/1/','@type'=>'dc:rights'];
		$right['holder']='NIST Thermodynamics Research Center (Boulder, CO)';
		$right['license']='https://www.nist.gov/open/license';
		$rights[]=$right;
		$trc->setrights($rights);

		// debug: spit out data to view as an array
		// $sd=$trc->asarray();
		// $sd=$trc->rawout();
		// debug($sd);exit;

		// output as JSON-LD
		out:
		$json=$trc->asjsonld(true);
		header("Content-Type: application/json");
		if($down=="download") { header('Content-Disposition: attachment; filename="'.$filid.'.json"'); }
		echo $json;exit;
	}

	/**
	 * test if jsonld files are valid
	 * @param int $max
	 * @return void
	 */
	public function test(int $max=100)
	{
		$grps=$this->Dataset->find('list',['fields'=>['id','title','points'],'order'=>'points','conditions'=>['points >'=>$max]]);
		// data group by number of points in dataset first then list of id:title
		//debug($grps);exit;
		foreach($grps as $points=>$grp) {
			$text="Datasets with ".$points." datapoint";
			if($points>1) { $text.="s<br/>"; } else { $text.="<br/>"; }
			echo $text;
			foreach($grp as $setid=>$title) {
				$url='https://sds.coas.unf.edu/trc/datasets/scidata/'.$setid;
				$hdrs=get_headers($url,true);
				if(stristr($hdrs[0],'200')) {
					$json=file_get_contents($url);
					if(stristr($json,'<pre class="cake-error">')) {
						echo "Dataset <a href='".$url."' target='_blank'>".$setid."</a> has error(s) (".$points." points)<br/>";
					}
				} else {
					echo "Dataset <a href='".$url."' target='_blank'>".$setid."</a> not found<br/>";
				}
			}
			//debug($grp);exit;
		}
		exit;
	}

	/**
	 * link chemicals and datasets
	 * a convenience join table (not required for the data model)
	 * @return void
	 */
	public function chemlinks()
	{
		$dsids=$this->Dataset->find('list',['fields'=>['id']]);
		$c=['System','Mixture'=>['Compohnent'],'File'=>['Chemical']];
		foreach($dsids as $dsid) {
			$found=$this->ChemicalsDataset->find('list',['conditions'=>['dataset_id'=>$dsid]]);
			if(!$found) {
				$dset=$this->Dataset->find('first',['conditions'=>['Dataset.id'=>$dsid],'contain'=>$c,'recursive'=>-1]);
				$components=$dset['Mixture']['Compohnent'];
				$chemicals=$dset['File']['Chemical'];
				$system=$dset['System'];
				$cmpcnt=substr_count($system['identifier'],':')+1;
				if($cmpcnt==count($components)) {
					$chmids=[];
					foreach($chemicals as $chem) { $chmids[]=$chem['id']; }
					foreach($components as $comp) {
						if(in_array($comp['chemical_id'],$chmids)) {
							// add to table
							$this->ChemicalsDataset->create();
							$data=['ChemicalsDataset'=>['chemical_id'=>$comp['chemical_id'],'dataset_id'=>$dsid]];
							$added=$this->ChemicalsDataset->save($data);
							if($added) {
								echo "Added '".$comp['chemical_id']."' to table!<br/>";
							} else {
								echo "Not added to DB!";debug($data);exit;
							}
						} else {
							echo 'Chemcial not found in file!';
							debug($components);debug($chemicals);debug($system);debug($cmpcnt);exit;
						}
					}
				} else {
					echo "Component mismatch!";
					debug($components);debug($chemicals);debug($system);debug($cmpcnt);exit;
				}
			}
		}
		exit;
	}

    // private functions

	/**
	 * get crosswalk info for fields that are a specific $type
	 * @param $type
	 * @param $fields
	 * @param $nspaces
	 * @param $ontlinks
	 * @return void
	 */
	private function getcw($type,&$fields,&$nspaces,&$ontlinks) {
		$c=['Ontterm'=>['Nspace']];$table="Crosswalk";
		$metas = $this->$table->find('all',['contain'=>$c,'recursive'=>-1]);
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
	}

}
