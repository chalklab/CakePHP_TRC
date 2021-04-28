<?php
// Load Composer autoload.
require_once realpath(__DIR__.'/..')."/vendor/autoload.php";

/**
 * Class DatasetsController
 */
class DatasetsController extends AppController
{
	public $uses = ['Dataset', 'Journal', 'Report', 'Quantity', 'Dataseries',
		'Parameter', 'Variable', 'Scidata', 'System', 'Substance', 'File',
		'Reference', 'Unit', 'Sampleprop', 'Trc', 'Chemical', 'Condition'];

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
		$c = ['File' => ['fields' => ['id', 'title'], 'order' => ['title'], 'limit' => 20,
			'Dataset' => ['fields' => ['id', 'title'], 'order' => 'title',
				'System' => ['fields' => ['id', 'name']],
				'Sampleprop' => ['fields' => ['id', 'property_name']],
				'Dataseries' => ['fields' => ['id']]],
			'Chemical']
		];
		$data = $this->Journal->find('all', ['fields' => ['id', 'name'], 'order' => ['name'], 'contain' => $c, 'recursive' => 1]);
		//debug($data);exit;
		$this->set('data', $data);
	}

	/**
	 * View a data set
	 * @param integer $id
	 * @return mixed
	 */
	public function view($id, $serid = null, $layout = null)
	{
		$ref = ['id', 'journal', 'authors', 'year', 'volume', 'issue', 'startpage', 'endpage', 'title', 'url'];
		$con = ['id', 'datapoint_id', 'system_id', 'property_name', 'number', 'significand', 'exponent', 'unit_id', 'accuracy'];
		$prop = ['id', 'name', 'phase', 'field', 'label', 'symbol', 'definition', 'updated'];
		$c = ['Annotation',
			'File' => [
				'Chemical' => ['Substance']],
			'Dataseries' => [
				'Condition' => ['Unit', 'Property', 'Annotation'],
				'Setting' => ['Unit', 'Property'],
				'Datapoint' => [
					'Annotation',
					'Condition' => ['fields' => $con, 'Unit', 'Property' => ['fields' => $prop]],
					'Data' => ['Unit', 'Sampleprop', 'Property' => ['fields' => $prop]],
					'Setting' => ['Unit', 'Property']
				],
				'Annotation'
			],
			'Sampleprop',
			'Reactionprop',
			'Reference' => ['fields' => $ref, 'Journal'],
			'System' => [
				'Substance' => [
					'Identifier' => ['fields' => ['type', 'value']]
				]]];

		$dump = $this->Dataset->find('first', ['conditions' => ['Dataset.id' => $id], 'contain' => $c, 'recursive' => -1]);
		$datum = $dump["Dataseries"][0]["Datapoint"][0];

		if (isset($datum["Condition"][0])) {
			$xname = $datum["Condition"][0]["Property"]["name"];
			$xunit = $datum["Condition"][0]["Unit"]["label"];
			$xlabel = $xname . ", " . $xunit;
		} else {
			$xlabel = "";
		}
		$sers = $dump["Dataseries"];
		$serconds = [];
		foreach ($sers as $ser) {
			if (!empty($ser['Condition'])) {
				foreach ($ser['Condition'] as $sc) {
					$scont = $sc["number"] + 0;
					$scondunit = $sc["Unit"]["symbol"];
					$serconds[] = $scont . " " . $scondunit;
				}
			}
		}
		$yunit = $datum["Data"][0]["Unit"]["header"];
		$samprop = $datum["Data"][0]["Sampleprop"]["property_name"];
		$ylabel = $samprop;
		$sub = $dump["System"]["name"];
		$formula = $dump["System"]["Substance"][0]["formula"];

		$xs = [];
		$ys = [];

		// loop through the datapoints
		$test = $dump['Dataseries'];
		$xy[0] = [];
		$num = 0;
		foreach ($test as $idx => $tt) {
			$count = 1;
			$xy[0][] = ["label" => $xlabel, "role" => "domain", "type" => "number"];
			$xy[0][] = ["label" => 'Series ' . ($idx + 1), "role" => "data", "type" => "number"];
			$xy[0][] = ["label" => "Min Error", "type" => "number", "role" => "interval"];
			$xy[0][] = ["label" => "Max Error", "type" => "number", "role" => "interval"];

			$points = $tt['Datapoint'];
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
				$errormins[] = $errormin;
				$errormaxs[] = $errormax;

				$xy[$count][] = $x;
				$xy[$count][] = $y;
				$xy[$count][] = $errormin;
				$xy[$count][] = $errormax;
				$count++;
			}
		}

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
		$this->set('test', $test);
		$fid = $dump['Dataset']['file_id'];
		// Get a list of datsets that come from the same file
		$related = $this->Dataset->find('list', ['conditions' => ['Dataset.file_id' => $fid, 'NOT' => ['Dataset.id' => $id]], 'recursive' => 1]);
		if (!is_null($layout)) {
			$this->set('serid', $serid);
			$this->render('data');
		}
		$this->set('related', $related);
		$this->set('dsid', $id);
		$title = $dump['Dataset']['title'];
		$this->set('title', $title);
		if ($this->request->is('ajax')) {
			echo '{ "title" : "' . $title . '" }';
			exit;
		}
	}

	/**
	 * Delete a dataset (and all data underneath)
	 * @param $id
	 */
	public function delete($id)
	{
		if ($this->Dataset->delete($id)) {
			$this->Flash->deleted('Dataset ' . $id . ' deleted!');
		} else {
			$this->Flash->deleted('Dataset ' . $id . ' could not be deleted!');
		}
		$this->redirect('/files/index');
	}

	// other actions

	/**
	 * create json load of all dataset endpoints
	 * used to process jsonld -> N-Quads in python
	 */
	public function sddslist()
	{
		$sets=$this->Dataset->find('list',['order'=>'id','conditions'=>['points'=>1]]);
		$output=[];
		foreach($sets as $setid=>$title) {
			preg_match('/Dataset (\d+) in paper 10.1021\/(.+)$/',$title,$m);
			$filename=$m[2]."_".$m[1];
			$output[$filename]='https://sds.coas.unf.edu/trc/datasets/scidata/'.$setid;
		}
		$json=json_encode($output);
		header("Content-Type: application/json");
		echo $json;exit;
	}

	/**
	 * test if jsonld files are valid
	 * @param int $max
	 */
	public function test($max=100)
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
					} else {
						//echo "Dataset <a href='".$url."' target='_blank'>".$setid."</a> valid<br/>";
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
	 * export jsonld files
	 * @param int $limit
	 * @param int $offset
	 * @throws
	 */
	public function exportjld($limit=100, $offset=0)
	{
		$sets=$this->Dataset->find('list',['fields'=>['id','title'],'order'=>'points','offset'=>$offset,'limit'=>$limit]);
		$folder='files_'.str_pad($offset,5,'0',STR_PAD_LEFT).'_'.str_pad(($offset+$limit-1),5,'0',STR_PAD_LEFT);
		$zip = new ZipArchive;
		$res = $zip->open($folder.'.zip', ZipArchive::CREATE);
		//debug($res);exit;
		foreach($sets as $setid=>$title) {
			$url='https://sds.coas.unf.edu/trc/datasets/scidata/'.$setid;
			$hdrs=get_headers($url,true);
			if(stristr($hdrs[0],'200')) {
				$json = file_get_contents($url);
				preg_match('/^Dataset ([0-9]+) in paper 10\.1021\/(.+)$/', $title, $m);
				$filename = $m[2] . '_' . $m[1] . '.jsonld';
				$zip->addFromString($filename, $json);
			}
		}
		$zip->close();
		chmod($folder.".zip", 0777);
		header("Content-type: application/zip");
		header("Content-Disposition: attachment; filename=".$folder.".zip");
		sleep(5);
		readfile($folder.'.zip');
		exit;
	}

	/**
	 * Function to find the most recent datasets
	 * @return mixed
	 */
	public function recent()
	{
		$data = $this->Dataset->find('list', ['order' => ['updated' => 'desc'], 'limit' => 6]);
		if ($this->request->params['requested']) {
			return $data;
		}
		$this->set('data', $data);
	}

	/**
	 * New SciData creation script that uses the class to create the file
	 * @param int $id
	 * @param string $down
	 * @param array $sclink
	 */
	public function scidata(int $id,$down="",$sclink=[])  // the $sclink variable was not set to a default value
	{
		// Note: there is an issue with the retrival of substances under system if id is not requested as a field
		// This is a bug in CakePHP as it works without id if its at the top level...
		$c = [
			'Annotation',
			'Dataseries' => [
				'Condition' => ['Unit',
					'Property' => ['fields' => ['id', 'name', 'kind'],
						'Quantity' => ['fields' => ['name']]]],
				'Setting' => ['Unit', 'Property' => ['fields' => ['name'],
					'Quantity' => ['fields' => ['name']]]],
				'Datapoint' => [
					'Condition' => ['fields' => ['id', 'datapoint_id', 'property_id', 'system_id',
						'property_name', 'datatype', 'number', 'significand', 'exponent', 'unit_id',
						'accuracy', 'exact'], 'Unit',
						'Property' => ['fields' => ['id', 'name', 'kind'],
							'Quantity' => ['fields' => ['id', 'name']]]],
					'Data' => ['fields' => ['id', 'datapoint_id', 'property_id', 'sampleprop_id',
						'datatype', 'number', 'significand', 'exponent', 'error', 'error_type',
						'unit_id', 'accuracy', 'exact'], 'Unit',
						'Property' => ['fields' => ['id', 'name', 'kind'],
							'Quantity' => ['fields' => ['id', 'name']]]],
					'Setting' => ['Unit',
						'Property' => ['fields' => ['id', 'name', 'kind'],
							'Quantity' => ['fields' => ['id', 'name']]]]],
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

		// get data for this dataset
		$data = $this->Dataset->find('first', ['conditions' => ['Dataset.id' => $id], 'contain' => $c, 'recursive' => -1]);
		//debug($data);exit;
		$set = $data['Dataset'];
		$file = $data['File'];
		$ref = $data['Reference'];
		$doi = $ref['doi'];
		$jnl = $ref['Journal'];
		$sers = $data['Dataseries'];
		$sys = $data['System'];
		$anns = $data['Annotation'];
		$sprops = $data['Sampleprop'];

		// get the metadata crosswalk data
		$fields = $nspaces = $ontlinks = [];
		$this->getcw('metadata', $fields, $nspaces, $ontlinks);
		$this->getcw('conditions', $fields, $nspaces, $ontlinks);
		$this->getcw('exptdata', $fields, $nspaces, $ontlinks);
		$this->getcw('deriveddata', $fields, $nspaces, $ontlinks);
		$this->getcw('suppdata', $fields, $nspaces, $ontlinks);

		// create an instance of the Scidata class
		$json['toc'] = [];
		$json['ids'] = [];
		$sdpath="https://scidata.unf.edu/";
		$setid=str_pad($id,6,'0',STR_PAD_LEFT);
		$uid = "trc_jced_".$setid;
		$upath = $sdpath.$uid."/";
		$trc = new $this->Scidata;
		$trc->setnspaces($nspaces);
		$trc->setid($sdpath."tranche/trc/jced/".$setid);
		$trc->setgenat(date("Y-m-d H:i:s"));
		$trc->setversion(1);
		$trc->setbase($upath);
		$trc->setuid($uid);
		$trc->setgraphid($upath);
		$trc->settitle($ref['title']);
		$trc->setpublisher($jnl['publisher']);
		$trc->setdescription('Report of thermochemical data in ThermoML format from the NIST TRC website http://www.trc.nist.gov/ThermoML/');
		$aus = explode('; ', $ref['aulist']);
		$trc->setauthors($aus);
		$trc->setstarttime($file['date']);
		$trc->setpermalink($sdpath."trc/datasets/view/".$setid);
		$trc->setdiscipline("w3i:Chemistry");
		$trc->setsubdiscipline("w3i:PhysicalChemistry");

		// other datsets from same paper are added as 'related' data
		$reldata = $this->Dataset->find('list', ['fields' => ['id'], 'conditions' => ['system_id' => $sys['id'], 'file_id' => $file['id'], 'NOT' => ['Dataset.id' => $id]]]);
		$related=[];
		foreach($reldata as $relset) {
			$related[]=$sdpath."trc/datasets/view/".$relset;
		}
		$trc->setrelated($related);
		// process data series to split out conditions, settings, and parameters
		$serdata = [];
		foreach ($sers as $s => $pnts) {
			$datas = $conds = $setts = [];
			foreach ($pnts['Datapoint'] as $p => $pnt) {
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
			$serdata[$s]['datas'] = $datas;
			$serdata[$s]['conds'] = $conds;
			$serdata[$s]['setts'] = $setts;
		}

		// Methodology sections (add data to $aspects)

		// nothing in the XML about the methodology?

		// System sections (add data to $facets)

		// System (general info)
		$sysj = [];
		if (is_array($sys) && !empty($sys) || is_array($conds) && !empty($conds)) {
			$sysj['@id'] = 'system/';
			$sysj['@type'] = 'sdo:system';
			$json['toc'][] = $sysj['@type'];
			$sysj['facets'] = [];
		}

		// Mixture/Substance/Chemical
		$facets = [];$systems = [];
		if (is_array($sys) && !empty($sys)) {
			// get substances
			$subs = $sys['Substance'];
			$substances = [];
			$chemicals = [];
			foreach ($subs as $subidx => $sub) {
				$s = [];
				$opts = ['name', 'formula', 'molweight'];
				foreach ($opts as $opt) {
					$s[$opt] = $sub[$opt];
				}
				foreach ($sub['Identifier'] as $subid) {
					$s[$subid['type']] = $subid['value'];
				}
				$substances[($subidx + 1)] = $s;

				// get chemicals
				$chmf = $sub['Chemical'];
				$purstep = json_decode($chmf['purity'], true);
				$c = [];$opts = ['name', 'sourcetype', 'purity'];
				foreach ($opts as $opt) {
					if ($opt == 'purity') {
						$c[$opt] = [];
						if (!is_null($chmf['purity'])) {
							$p=[];
							$p['@id']='chemical/'.($subidx + 1).'/purity/';
							$p["@type"]="sdo:purity";
							foreach ($purstep as $sidx => $step) {
								$s = [];
								$s["@id"]='chemical/'.($subidx + 1).'/purity/step/'.($sidx+1).'/';
								$s["@type"]="sdo:step";
								$s['part'] = $step['type'];
								if (!is_null($step['analmeth'])) {
									if (count($step['analmeth']) == 1) {
										$s['analysis'] = $step['analmeth'][0];
									} else {
										$s['analysis'] = $step['analmeth'];
									}
								}
								if (!is_null($step['purimeth'])) {
									$s['purification'] = $step['purimeth'];
								} else {
									$s['purification'] = null;
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
									$s['unitref'] = 'qudt:' . $qudtid;
								}
								$p['steps'][$sidx] = $s;
							}
							$c[$opt][]=$p;
						}
					} else {
						$c[$opt] = $chmf[$opt];
					}
				}

				$c['compound'] = 'compound/' . ($subidx + 1) . '/';
				$chemicals[($subidx + 1)] = $c;
			}

			$facets['sdo:compound'] = $substances;
			$facets['sdo:chemical'] = $chemicals;

			# create the the substance (chemical system)
			if (count($sys['Substance']) == 1) {
				$type = "substance";
				$s = [];
				$sid = "substance/1/";
				$s['@id'] = $sid;
				$s['@type'] = "sdo:" . $type;
				$json['toc'][] = $s['@type'];
				$s['composition'] = $sys['composition'];
				$s['phase'] = $sys['phase'];
				$s['name']=$sys['name'];
				$s['source']="chemical/".++$subidx."/";
			} else {
				$s = [];
				$type = "mixture";
				$sid="substance/1/";
				$s['@id'] = $sid;
				$s['@type'] = "sdo:" . $type;
				$json['toc'][] = $s['@type'];
				$s['composition'] = $sys['composition'];
				$phases=json_decode($set['phase']);
				if(!is_array($phases)) {
					$phases=[0=>$phases];
				}
				foreach($phases as $phase) {
					$phase=strtolower($phase);
					if(in_array($phase,['liquid','solid','gas'])) {
						$s['phase'][]="sub:".$phase;
					} elseif(stristr($phase,'liquid')) {
						$s['phase'][]='sub:liquid';
					} elseif(stristr($phase,'solution')) {
						$s['phase'][]='sub:liquid';
					} elseif(stristr($phase,'glass')) {
						$s['phase'][]='sub:liquid';
					} elseif(stristr($phase,'crystal')) {
						$s['phase'][]='sub:solid';
					} elseif(stristr($phase,'gas')) {
						$s['phase'][]='sub:gas';
					} elseif(stristr($phase,'air')) {
						$s['phase'][]='sub:gas';
					} elseif(stristr($phase,'supercritical')) {
						$s['phase'][]='sub:fluid';
					}
					$s['phase']=array_unique($s['phase']);
				}
				$s['phasetype'] = $sys['phase'];
				$s['name']=$sys['name'];
				foreach($chemicals as $cidx => $c) {
					$s['constituents'][]=['source'=>"chemical/".$cidx.'/'];
				}
			}
			$systems[1] = $s;
		}

		$facets['sdo:substance'] = $systems;

		// conditions (organize first then write to a variable to send to the model)
		// here we need to process both series conditions and regular conditions
		// In a dataseries ($ser) $ser['Condition'] is where the series conditions are (1 -> n)
		// ... and the regular conditions are in datapoints ($pnt) under $pnt['Condition']
		$conditions = [];$srconds=[];
		foreach($sers as $seridx=>$ser) {
			$sernum=$seridx+1;
			$sconds = $ser['Condition']; // series conditions
			$pnts = $ser['Datapoint']; // all datapoints in a series
			foreach($pnts as $pntidx=>$pnt) {
				$pntnum=$pntidx+1;
				$conds = $pnt['Condition']; // conditions for datapoints (0 -> n)
				foreach ($conds as $conidx=>$cond) {
					// update number to correctly reflect the # sig figs
					$dp=$cond['accuracy']-($cond['exponent']+1);
					$cond['number']=(string) number_format($cond['number'],$dp,'.','');
					if(!isset($conditions[$cond['property_id']]['property'])) {
						$conditions[$cond['property_id']]['property']=$cond['Property']['name'];
					}
					if(!empty($cond['Property']['kind'])) {
						$kind=$cond['Property']['kind'];
						$conditions[$cond['property_id']]['propertyref'] = $ontlinks['conditions'][$kind];
						if(!in_array($conditions[$cond['property_id']]['propertyref'],$json['ids'])) {
							$json['ids'][]=$conditions[$cond['property_id']]['propertyref'];
						}
					}
					if(!isset($conditions[$cond['property_id']]['unit'])) {
						$conditions[$cond['property_id']]['unit']=$cond['Unit']['name'];
					}
					if(!empty($cond['Unit']['qudt'])) {
						$unit="qudt:".$cond['Unit']['qudt'];
						$conditions[$cond['property_id']]['unitref']=$unit;
						if(!in_array($unit,$json['ids'])) {
							$json['ids'][]=$unit;
						}
					}
					$srconds[$cond['property_id']][$cond['number']][]=$sernum.":".$pntnum;
					$conditions[$cond['property_id']]['value'][]=$cond['number'];
				}
			}
			foreach($sconds as $scidx=>$scond) {
				// update number to correctly reflect the # sig figs
				$dp=$scond['accuracy']-($scond['exponent']+1);
				$scond['number']=(string) number_format($scond['number'],$dp,'.','');
				if(!isset($conditions[$scond['property_id']])) {
					$scon=[];
					$scon['property']=$scond['Property']['name'];
					if(!empty($scond['Property']['kind'])) {
						$kind=$scond['Property']['kind'];
						$scon['propertyref'] = $ontlinks['conditions'][$kind];
						if(!in_array($scon['propertyref'],$json['ids'])) {
							$json['ids'][]=$scon['propertyref'];
						}
					}
					$scon['unit']=$scond['Unit']['name'];
					if(!empty($scond['Unit']['qudt'])) {
						$unit="qudt:".$scond['Unit']['qudt'];
						$scon['unitref']=$unit;
						if(!in_array($unit,$json['ids'])) {
							$json['ids'][]=$unit;
						}
					}
					$conditions[$scond['property_id']]=$scon;
				}

				// iterate over all datapoints to add condition to each
				foreach($pnts as $pntidx=>$pnt) {
					$pntnum=$pntidx+1;
					$srconds[$scond['property_id']][$scond['number']][]=$sernum.":".$pntnum;
					$conditions[$scond['property_id']]['value'][]=$scond['number'];
				}
			}
		}

		// deduplicate condition values and record series and datapoint index
		foreach($conditions as $propid=>$values) {
			$uvals=array_unique($conditions[$propid]['value']); // SORT_NUMERIC not working...
			sort($uvals);
			$conditions[$propid]['value']=$uvals;
			$varray=[];
			foreach($conditions[$propid]['value'] as $vidx=>$val) {
				$v['value']=$val;
				// find original rows for this value
				$v['rows']=$srconds[$propid][$val];
				$varray[]=$v;
			}
			$conditions[$propid]['value']=$varray;
		}

		// reindex conditions array and sort values
		$tmp=$conditions;$conditions=[];$cidx=1;
		foreach($tmp as $con) {
			$conditions[$cidx]=$con;$cidx++;
		}

		// add conditions facet to $facets
		$facets['sdo:condition'] = $conditions;

		// add facets data to instance
		$trc->setfacets($facets);

		// Data (add data to $group)
		$groups=[];
		foreach($sers as $seridx=>$ser) {
			$group=[];$sernum=$seridx+1;
			$group['title']='Series '.$sernum;
			if(count($sys['Substance'])==1) {
				$group['system']="compound/1/";
			} else {
				$group['system']="system/1/";
			}
			$group['data']=[];
			foreach ($ser['Datapoint'] as $pntidx => $pnt) {
				$pntnum=$pntidx+1;$unit=$qudt="";$point=[];
				foreach($pnt['Data'] as $datidx=>$datum) {
					$datnum=$datidx+1;
					$val=[];
					$propphase=$datum['Property']['name']." (".$sprops[$datidx]['phase'].")";
					if(!isset($group['data'][$propphase])) {
						$group['data'][$propphase]=[];
					}
					$val['property']=$propphase;
					$val['quantity']=$datum['Property']['name'];
					if(!empty($datum['accuracy'])) {
						// check that the number is correctly represented based on accuracy
						$dp=$datum['accuracy']-($datum['exponent']+1);
						$datum['number']=number_format($datum['number'],$dp,'.','');
						$val['number']=$datum['number'];
					} else {
						$val['number']=$datum['number'];
					}
					if(!empty($datum['error'])) { $val['error']=(float) $datum['error'];$val['errortype']=$datum['error_type']; }
					// lookup property in $ontlinks and assign to 'propertyref'
					if(!empty($datum['Property']['kind'])) {
						$kind=$datum['Property']['kind'];
						$val['propertyref'] = $ontlinks['exptdata'][$kind];
						if(!in_array($val['propertyref'],$json['ids'])) {
							$json['ids'][]=$val['propertyref'];
						}
					}
					if(!empty($datum['Unit']['name'])) {
						$val['unit']=$datum['Unit']['name'];
					}
					if(!empty($datum['Unit']['qudt'])) {
						$unitref="qudt:".$datum['Unit']['qudt'];
						$val['unitref']=$unitref;
						if(!in_array($unitref,$json['ids'])) {
							$json['ids'][]=$unitref;
						}
					}
					$group['data'][$propphase][$pntnum]=$val;
				}
			}
			$groups[$sernum]=$group;
		}
		//exit;//debug($groups);

		$trc->setdatagroup($groups);


		// Sources
		// Go get the DOI
		$bib=$ref['title']." ".$ref['aulist']." ".$ref['journal']." ".$ref['year']." ".$ref['volume']." ".$ref['startpage'];
		$src=[];
		$src['id']="source/".$ref['id'].'/';
		$src['type']="paper";
		$src['citation']=$bib;
		$src['url']=$ref['url'];
		$sources[]=$src;
		// add TRC dataset
		$src=[];
		$src['id']="source/2/";
		$src['citation']="NIST TRC ThermoML Archive";
		$src['url']="https://www.trc.nist.gov/ThermoML/".$ref['doi'].".xml";
		$src['type']="dataset";
		$sources[]=$src;
		$trc->setsources($sources);
		//debug($src);exit;

		// Rights
		$right=['@id'=>'rights/1/','@type'=>'dc:rights'];
		$right['holder']='NIST Thermodynamics Research Center (Boulder, CO)';
		$right['license']='https://creativecommons.org/licenses/by-nc/4.0/';
		$rights[]=$right;
		$trc->setrights($rights);
		//debug($json);exit;

		// spit out data to view as an array
		//$sd=$trc->asarray();
		//$sd=$trc->rawout();
		//debug($sd);exit;

		// output as JSON-LD
		$json=$trc->asjsonld();
		header("Content-Type: application/json");
		if($down=="download") { header('Content-Disposition: attachment; filename="'.$id.'.json"'); }
		echo $json;exit;

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
                    'Property'=>['fields'=>['name','kind'],
                        'Quantity'=>['fields'=>['name']]]],
                'Setting'=>['Unit', 'Property'=>['fields'=>['name'],
                    'Quantity'=>['fields'=>['name']]]],
                'Datapoint'=>[
                    'Condition'=>['Unit','Sampleprop',
                        'Property'=>['fields'=>['name','kind'],
                            'Quantity'=>['fields'=>['name']]]],
                    'Data'=>['Unit','Sampleprop',
                        'Property'=>['fields'=>['name','kind'],
                            'Quantity'=>['fields'=>['name']]]],
                    'Setting'=>['Unit',
                        'Property'=>['fields'=>['name','kind'],
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
        $sers=$data['Dataseries'];
        $sys=$data['System'];

        // Other systems -> related
        $othersys=$this->Dataset->find('list',['fields'=>['id'],'conditions'=>['system_id'=>$sys['id'],'file_id'=>$file['id'],'NOT'=>['Dataset.id'=>$id]]]);

        // get the links from the crosswalk table...
		$this->getcw('conditions', $fields, $nspaces, $ontlinks);
		$this->getcw('exptdata', $fields, $nspaces, $ontlinks);
		$this->getcw('deriveddata', $fields, $nspaces, $ontlinks);
		$this->getcw('suppdata', $fields, $nspaces, $ontlinks);
		//debug($fields);debug($nspaces);debug($ontlinks);exit;

		// Base
		list($pre,$code)=explode('/',$ref['doi']);
		$uid="trc:jced:".$code;
        $base="https://scidata.unf.edu/".$uid."/";

        // Build the PHP array that will then be converted to JSON
		$json['@context']=['https://stuchalk.github.io/scidata/contexts/scidata.jsonld',
			['sdo'=>'http://stuchalk.github.io/scidata/ontology/scidata.owl#',
				'sub'=>'http://stuchalk.github.io/scidata/ontology/substance.owl#',
				'so'=>'http://stuchalk.github.io/scidata/ontology/solubility.owl#',
				'ito'=>'http://stuchalk.github.io/scidata/ontology/thermo.owl#',
				'qudt'=>'http://qudt.org/vocab/unit/',
				'obo'=>'http://purl.obolibrary.org/obo/',
				'w3i'=>'https://w3id.org/skgo/modsci#',
                'dc'=>'http://purl.org/dc/terms/',
                'xsd'=>'http://www.w3.org/2001/XMLSchema#',
			   	'gb'=>'http://semanticscience.org/resource/',
				'ss'=>'https://goldbook.iupac.org/'
			   ],
            ['@base'=>$base]];

        // Main metadata
        $json['@id']="";
		$json['generatedAt']=date("Y-m-d H:i:s");
		$json['version']=1;
		$graph['@id']=$base;
		$graph['@type']="sdo:scidataFramework";
		$graph['uid']=$uid;
        $graph['title']=$ref['title'];
        $graph['author']=[];
        if($ref['authors']!=null) {
            if(stristr($ref['authors'],'[{')) {
                $authors=json_decode($ref['authors'],true);
            } else {
                $authors=explode(", ",$ref['authors']);
            }
            $acount=1;
            foreach ($authors as $au) {
                $name=$au['firstname']." ".$au['lastname'];
                $graph['author'][]=['@id'=>'author/'.$acount.'/','@type'=>'dc:creator','name'=>$name];
                $acount++;
            }
        }
        $graph['description']="Report of thermochemical data in ThermoML format from the NIST TRC website http://www.trc.nist.gov/ThermoML/";
        $graph['publisher']=$jnl['publisher'];
        $graph['starttime']=$file['date'];
        $graph['permalink']="https://scidata.unf.edu/trc/datasets/view/".$id;
        foreach($othersys as $os) {
            $graph['related'][]="https://scidata.unf.edu/trc/datasets/view/".$os;
        }
        $graph['toc']=[];
		$graph['ids']=[];

        // Process data series to split out conditions, settings, and parameters
		// Series translate to datagroups below
        $datas=$conds=$setts=[];
        foreach($sers as $r=>$ser) {
			foreach($ser['Datapoint'] as $p=>$point) {
				foreach($point['Data'] as $d=>$dval) {
					$datas[$r][$p][$d]=$dval;
				}
				foreach($point['Condition'] as $c=>$cval) {
					$conds[$r][$c][$p]=$cval;
				}
				foreach($point['Setting'] as $s=>$sval) {
					$setts[$r][$s][$p]=$sval;
				}
			}
		}

        // SciData
        $setj['@id']="scidata/";
        $setj['@type']="sdo:scientificData";
        $graph['scidata']=$setj;

        // Settings
        $metj=[];
        if(!empty($setts)) {
            // Methodology
            $metj['@id']='methodology';
            $metj['@type']='sdo:methodology';
            $metj['evaluation']='experimental';
            $metj['aspects']=[];
            $graph['toc'][] = $metj['@type'];
            $meaj['@id'] = 'measurement/1/';
            $meaj['@type'] = 'meas:measurement';
            $graph['toc'][] = $meaj['@type'];
            $meaj['settings'] = [];
            foreach($setts as $sid=>$sett) {
                //debug($sett);exit;
                $setgj = [];
                $setgj['@id'] = "setting/".($sid + 1);
                $setgj['@type'] = "sdo:setting";
                $setgj['quantity'] = strtolower($sett[0]['Property']['Quantity']['name']);
                $setgj['property'] = $sett[0]['Property']['name'];
                foreach ($sett as $sidx => $s) {
                    $v=$vs=[];
                    if(!in_array($s['number'],$vs)) {
                        $vs[]=$s['number'];
                        $v['@id'] = "setting/" . ($sid + 1) . "/value/".(array_search($s['number'],$vs)+1);
                        $v['@type'] = "sdo:value";
                        if (!is_null($s['number'])) {
                            $v['number'] = $s['number'];
							if(!empty($s['Unit']['qudt'])) {
								$unit=$s['Unit']['qudt'];
								$v['unitref']=$unit;
								$graph['ids'][]=$unit;
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

        // add methodology
        if(!empty($metj)) {
			$graph['scidata']['methodology']=$metj;
		}

        // System
        $sysj=[];
        if(is_array($sys)&&!empty($sys)||is_array($conds)&&!empty($conds)) {
            $sysj['@id']='system/';
            $sysj['@type']='sdo:system';
			$graph['toc'][]=$sysj['@type'];
			$sysj['discipline']='w3i:Chemistry';
            $sysj['subdiscipline']='w3i:PhysicalChemistry';
            $sysj['facets']=[];
        }

        // Conditions code in scidata2

        // System sections
        // Mixture/Substance/Chemical
        $type='';
        if(is_array($sys)&&!empty($sys)) {
            // System
            if (count($sys['Substance']) == 1) {
                $type = "substance";
            } else {
                $type = "mixture";
            }
            $sid = "substance/1/";
            $mixj['@id'] = $sid;
            $mixj['@type'] = "sdo:".$type;
			$graph['toc'][] = $mixj['@type'];
			$mixj['composition']=$sys['composition'];
			$phases=json_decode($set['phase']);
			foreach($phases as $phase) {
				$phase=strtolower($phase);
				if(in_array($phase,['liquid','solid','gas'])) {
					$mixj['phase'][]="sub:".$phase;
				} elseif(stristr($phase,'liquid')) {
					$mixj['phase'][]='sub:liquid';
				} elseif(stristr($phase,'solution')) {
					$mixj['phase'][]='sub:liquid';
				} elseif(stristr($phase,'glass')) {
					$mixj['phase'][]='sub:liquid';
				} elseif(stristr($phase,'crystal')) {
					$mixj['phase'][]='sub:solid';
				} elseif(stristr($phase,'gas')) {
					$mixj['phase'][]='sub:gas';
				} elseif(stristr($phase,'air')) {
					$mixj['phase'][]='sub:gas';
				}
				$mixj['phase']=array_unique($mixj['phase']);
			}
            $mixj['phasetype']=$sys['phase'];
            $opts = ['name', 'description', 'type'];
            foreach ($opts as $opt) {
                if (isset($sys[$opt]) && $sys[$opt] != "") {
                    $mixj[$opt] = $sys[$opt];
                }
            }
            if (isset($sys['Substance'])) {
                for ($j = 0; $j < count($sys['Substance']); $j++) {
                    // constituents
                    unset($subj);
                    $subj['@id'] = $sid."constituent/".($j + 1)."/";
                    $subj['@type'] = "sdo:chemical";
                    $subj['source'] = "chemical/".($j + 1).'/';
                    $mixj['constituents'][] = $subj;
                    // Substances
                    unset($subj);$sub = $sys['Substance'][$j];
                    $subj['@id'] = "compound/".($j + 1).'/';
                    $subj['@type'] = "sdo:".$sub['type'];
					$graph['toc'][] = $subj['@type'];
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
                    $chmj['@id'] = "chemical/".($j + 1).'/';
                    $chmj['@type'] = "sdo:chemical";
					$graph['toc'][] = $chmj['@type'];
					$chmj['source'] = "compound/".($j + 1).'/';
                    $chmj['acquired'] = $chem['source'];
                    if(!is_null($chem['purity'])) {
                        $purj['@id'] = "purity/".($j + 2).'/';
                        $purj['@type'] = "sdo:purity";
                        $purity=json_decode($chem['purity'],true);
                        $purj['steps']=[];
                        foreach($purity as $step) {
							$stepsj=[];
                            $stepsj['@id'] = "step/".$step['step'].'/';
                            $stepsj['@type'] = "sdo:value";
                            $stepsj['part'] = $step['type'];
                            if(!is_null($step['analmeth'])) {
                            	if(count($step['analmeth'])==1) {
									$stepsj['analysis']=$step['analmeth'][0];
								} else {
									$stepsj['analysis']=$step['analmeth'];
								}}
                            if(!is_null($step['purimeth'])) {
                                $stepsj['purification']=$step['purimeth'];
                            } else {
                                $stepsj['purification']=null;
                            }

                            if(!is_null($step['purity'])) {
                                $stepsj['number']=(float) $step['purity'];
                            }
                            if(!is_null($step['puritysf'])) {
                                $stepsj['sigfigs']=(int) $step['puritysf'];
                            }
							if(!is_null($step['purityunit_id'])) {
                                $unit=$this->Unit->getfield("qudt",$step['purityunit_id']);
                                $stepsj['unitref']=$unit;
								if(!in_array($unit,$graph['ids'])) {
									$graph['ids'][]=$unit;
								}
							}
                            $purj['steps'][]=$stepsj;
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
		$allcons=[];
        if(is_array($conds)&&!empty($conds)) {
        	foreach($conds as $sidx=>$byser) {
        		//debug($sidx);
				foreach($byser as $cidx=>$bycon) {
					if(isset($allcons[$cidx])) {
						$firstprop=$bycon[0]['Property']['name'];
						$firstprop2=$firstprop." (".strtolower($bycon[0]['Sampleprop']['phase']).")";
						if($allcons[$cidx]['property']==$firstprop||$allcons[$cidx]['property']==$firstprop2) {
							// check for value already existing
							foreach($bycon as $pidx=>$cond) {
								$value=$cond['number'];$found=0;
								foreach($allcons[$cidx]['valuearray'] as $val) {
									if($val['number']==$value) {
										$conds[$sidx][$cidx][$pidx]['clink']=$val['@id'];
										$found++;break;
									}
								}
								// if not present add
								if(!$found) {
									$v['@id'] = "condition/".($cidx + 1)."/value/".count($allcons[$cidx]['valuearray']).'/';
									$v['@type'] = "sdo:value";
									if(!is_null($cond['number'])) {
										$v['number'] = $cond['number'];
									} else {
										$v['text'] = $cond['text'];
									}
									$allcons[$cidx]['valuearray'][] = $v;
									$conds[$sidx][$cidx][$pidx]['clink']="condition/".($cidx+1)."/value/".count($allcons[$cidx]['valuearray']).'/';
								}
							}
						} else {
							echo("Same property?");
							debug($allcons[$cidx]['property']);debug($bycon[0]);exit;
						}
					} else {
						$v=$vs=$condj = [];
						$condj['@id'] = "condition/".($cidx + 1)."/";
						$condj['@type'] = "sdo:condition";
						$graph['toc'][] = $condj['@type'];
						$first=$bycon[0]; // get first cond value
						$condj['quantity'] = strtolower($first['Property']['Quantity']['name']);
						$condj['property'] = $first['Property']['name'];
						if(!empty($first['Sampleprop']['phase'])) {
							$condj['property'].=" (".strtolower($first['Sampleprop']['phase']).")";
						}
						if(!empty($first['Property']['kind'])) {
							$kind=$first['Property']['kind'];
							$condj['propertyref'] = $ontlinks['conditions'][$kind]; // get from first cond value (same for all)
							if(!in_array($condj['propertyref'],$graph['ids'])) {
								$graph['ids'][]=$condj['propertyref'];
							}
						}
						if(!empty($first['Unit']['qudt'])) {
							$unit="qudt:".$first['Unit']['qudt'];
							$condj['unitref']=$unit;
							if(!in_array($unit,$graph['ids'])) {
								$graph['ids'][]=$unit;
							}
						}
						foreach ($bycon as $pidx => $cond) {
							if(!in_array($cond['number'],$vs)) {
								$vs[]=$cond['number'];
								$v['@id'] = "condition/" . ($cidx + 1) . "/value/".(array_search($cond['number'],$vs)+1).'/';
								$v['@type'] = "sdo:value";
								if (!is_null($cond['number'])) {
									$v['number'] = $cond['number'];
								} else {
									$v['text'] = $cond['text'];
								}
								$condj['valuearray'][] = $v;
							}
							$conds[$sidx][$cidx][$pidx]['clink']="condition/".($cidx+1)."/value/".(array_search($cond['number'],$vs)+1).'/';
						}
						$allcons[$cidx] = $condj;
					}
				}
			}
        }

        // Dataseries conditions
		$numconds=count($allcons);
		if(!empty($sers)) {
			foreach($sers as $sidx=>$ser) {
				foreach($ser['Condition'] as $scidx=>$scond) {
					$allidx=$scidx+$numconds+1;
					if(isset($allcons[($allidx-1)])) {
						//debug($allidx);//debug($scond);
						if($allcons[($allidx-1)]['property']==$scond['Property']['name']) {
							$value=$scond['number'];$found=0;
							foreach($allcons[($allidx-1)]['valuearray'] as $val) {
								if($val['number']==$value) {
									$sers[$sidx]['Condition'][$scidx]['sclink']=$val['@id'];
									$found++;break;
								}
							}
							// if not present add
							if(!$found) {
								$v['@id'] = "condition/".$allidx."/value/".(count($allcons[($allidx-1)]['valuearray'])+1).'/';
								$v['@type'] = "sdo:value";
								if(!is_null($scond['number'])) {
									$v['number'] = $scond['number'];
								} else {
									$v['text'] = $scond['text'];
								}
								$sers[$sidx]['Condition'][$scidx]['sclink']="condition/".$allidx."/value/".(count($allcons[($allidx-1)]['valuearray'])+1).'/';
								$allcons[($allidx-1)]['valuearray'][] = $v;
							}
						} else {
							echo("Same property?");
							debug($allcons[($allidx-1)]['property']);debug($scond['property']);exit;
						}
					} else {
						// adding the first entry in series condition
						$scondj=[];
						$scondj['@id'] = "condition/".$allidx."/";
						$scondj['@type'] = "sdo:seriescondition";
						$graph['toc'][] = $scondj['@type'];
						$scondj['quantity'] = strtolower($scond['Property']['Quantity']['name']);
						$scondj['property'] = $scond['Property']['name'];
						//debug($scond);
						if(!is_null($scond['Property']['kind'])) {
							$kind=$scond['Property']['kind'];
							$scondj['propertyref'] = $ontlinks['conditions'][$kind];
							if(!in_array($scondj['propertyref'],$graph['ids'])) {
								$graph['ids'][]=$scondj['propertyref'];
							}
						}
						$v['@id'] = "condition/".$allidx."/value/1/";
						$v['@type'] = "sdo:value";
						if (!is_null($scond['number'])) {
							$v['number'] = $scond['number'];
							if (!empty($scond['Unit']['qudt'])) {
								$unit="qudt:".$scond['Unit']['qudt'];
								$scondj['unitref']=$unit;
								if(!in_array($unit,$graph['ids'])) {
									$graph['ids'][]=$unit;
								}
							}
						} else {
							$v['text'] = $scond['text'];
						}
						$scondj['valuearray'][] = $v;
						$sers[$sidx]['Condition'][$scidx]['sclink']=$v['@id'];
						$allcons[] = $scondj;
					}
				}
			}
		}

		//debug($ontlinks);exit;

		// order condition values numerically
		$conidxalign=[];
		foreach($allcons as $cidx=>$con) {
			$connum=$cidx+1;
			$allvals=[];
			foreach($con['valuearray'] as $vidx=>$value) {
				$allvals[($vidx+1)]=$value['number'];
			}
			asort($allvals);
			// create alignment array and new value array
			$newidx=1;
			foreach($allvals as $oldidx=>$val) {
				$conidxalign[$connum][$oldidx]=$newidx;
				$newidx++;
			}
			// replace in allcons
			$allvals=array_values($allvals);
			foreach($allvals as $vidx=>$val) {
				$allcons[$cidx]['valuearray'][$vidx]['number']=$val;
			}
		}
		//debug($conidxalign);debug($allcons);debug($conds);

		// reassign condition @ ids
		if(is_array($conds)&&!empty($conds)) {
			foreach($conds as $sidx=>$byser) {
				foreach($byser as $cidx=>$bycon) {
					foreach($bycon as $pidx=>$cond) {
						list($c,$connum,$v,$valnum)=explode("/",$cond['clink']);
						$conds[$sidx][$cidx][$pidx]['clink']='condition/'.$connum.'/value/'.$conidxalign[$connum][$valnum].'/';
					}
				}
			}
		}

		// add conditions to system
		foreach($allcons as $cond) {
			$sysj['facets'][]=$cond;
		}

		$graph['scidata']['system']=$sysj;

		// Data
        $resj=[];$pnts=[];$pntidx=1;
        if(is_array($datas)&&!empty($datas)) {
            $resj['@id']='dataset/';
            $resj['@type']='sdo:dataset';
			$graph['toc'][]=$resj['@type'];
			$resj['scope']= $type.'/1/';
            $resj['datagroup']=[];

            // Group
            foreach($datas as $seridx=>$serdata) {
            	$grpj=[];
            	$grpj['@id']='datagroup/'.($seridx+1).'/';
                $grpj['@type'] = 'sdo:datagroup';
				$graph['toc'][] = $grpj['@type'];
				if(!is_null($sers[$seridx]['Condition'])) {
					foreach($sers[$seridx]['Condition'] as $scidx=>$scond) {
						$grpj['conditions'][]=$scond['sclink'];//.":".$scond['number'];
					}
				}
				foreach($serdata as $pidx=>$pnt) {
					$grpj['datapoints'][] = 'datapoint/'.$pntidx.'/';
					$dtms=[];
                    $dtms['@id'] = 'datapoint/'.$pntidx.'/';
                    $dtms['@type'] = 'sdo:datapoint';

					// conditions
					$dtms['conditions']=[];
					if(!empty($conds[$seridx])) {
						foreach ($conds[$seridx] as $cidx=>$cond) {
							$dtms['conditions'][]=$cond[$pidx]['clink'];//.":".$cond[$pidx]['number'];
                        }
                    }
                    if(!is_null($sers[$seridx]['Condition'])) {
                        foreach($sers[$seridx]['Condition'] as $scidx=>$scond) {
                            $dtms['conditions'][]=$scond['sclink'];//.":".$scond['number'];
                        }
                    }
                    if(!empty($setts)) {
                        foreach ($setts as $sett) {
                            $dtms['settings'][] = $sett[$pidx]['slink'];
                        }
                    }
                    if(empty($dtms['conditions'])) { unset($dtms['conditions']); }

                    // Value
                    $v=[];
					foreach($pnt as $didx=>$dtm) {
						//debug($dtm);exit;
						$dtmj=[];
						$dtmj['@id'] = 'datapoint/'.$pntidx.'/datum/'.($didx+1).'/';
						$dtmj['@type'] = 'sdo:datum';
						$dtmj['quantity'] = $dtm['Property']['name'];
						$dtmj['property'] = $dtm['Property']['name']." (".$dtm['Sampleprop']['phase'].")";
						if(!is_null($dtm['Property']['kind'])) {
							$kind=$dtm['Property']['kind'];
							$dtmj['propertyref'] = $ontlinks['exptdata'][$kind]; // get from first cond value (same for all)
							if(!in_array($dtmj['propertyref'],$graph['ids'])) {
								$graph['ids'][]=$dtmj['propertyref'];
							}
						}
						if(!is_null($dtm['number'])) {
							$unit=$qudt="";
							if(!empty($dtm['Unit']['label'])) {
								$unit=$dtm['Unit']['label'];
							}
							if(!empty($dtm['Unit']['qudt'])) {
								$qudt="qudt:".$dtm['Unit']['qudt'];
							}
							if($dtm['datatype']=="datum") {
								$v['@id']=$dtmj['@id']."value/";
								$v['@type']="sdo:value";
								$v['number']=$dtm['number'];
								if(!empty($dtm['error'])) {
									$v['error']=$dtm['error'];
								}
								if($unit!="") { $v['unit']=$unit; }
								if($qudt!="") {
									$v['unitref']=$qudt;
									if(!in_array($qudt,$graph['ids'])) {
										$graph['ids'][]=$unit;
									}
								}
								$dtmj['value']=$v;
							} else {
								$v['@id']=$dtmj['@id']."valuearray/";
								$v['@type']="sdo:valuearray";
								$v['numberarray']=json_decode($dtm['number'],true);
								if(!empty($dtm['error'])) {
									$v['errors']=json_decode($dtm['error'],true);
								}
								if($unit!="") { $v['unit']=$unit; }
								if($qudt!="") {
									$v['unitref']=$qudt;
									if(!in_array($qudt,$graph['ids'])) {
										$graph['ids'][]=$unit;
									}
								}
								$dtmj['valuearray']=$v;
							}
						}
                    	$dtms['data'][]=$dtmj;
					}
					$pnts[]=$dtms;$pntidx++;
				}
				$resj['datagroup'][]=$grpj;
            }

            $resj['datapoint']=$pnts;
		}
		$graph['scidata']['dataset']=$resj;

		//debug($graph);exit;

		// Sources
        // Original Paper
        $paper=['@id'=>'source/1/','@type'=>'dc:source'];
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
        $trc=['@id'=>'source/2/','@type'=>'dc:source'];
        $trc['citation'] = "Original data file from NIST TRC Archive at http://www.trc.nist.gov/ThermoML/";
        $trc['url']="http://www.trc.nist.gov/ThermoML/".$ref['doi'];

        $graph['sources'][]=$paper;
        $graph['sources'][]=$trc;

        // Rights
        $graph['rights']=['@id'=>'rights/1/','@type'=>'dc:rights'];
        $graph['rights']['holder']='NIST Thermodynamics Research Center (Boulder, CO)';
        $graph['rights']['license']='https://creativecommons.org/licenses/by-nc/4.0/';

        // deduplicate TOC
		$toc=array_values(array_unique($graph['toc']));
		sort($toc);
		sort($graph['ids']);
		$graph['toc']=$toc;
        $json['@graph']=$graph;
        //debug($json);exit;

        // OK turn it back into JSON-LD
        header("Content-Type: application/json");
        if($down=="download") { header('Content-Disposition: attachment; filename="'.str_replace(":","_",$uid).'.jsonld"'); }
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
					'Data'=>['Unit','Sampleprop',
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
			$json['toc'][] = $metj['@id'];
			$meaj['@id'] = 'measurement/1';
			$meaj['@type'] = 'meas:measurement';
			$json['toc'][] = $meaj['@type'];
			$meaj['settings'] = [];
			foreach($setts as $sid=>$sett) {
				// debug($sett);exit;
				$setgj = [];
				$setgj['@id'] = "setting/".($sid + 1);
				$setgj['@type'] = "sdo:setting";
				$setgj['quantity'] = strtolower($sett[0]['Property']['Quantity']['name']);
				$setgj['property'] = $sett[0]['Property']['name'];
				foreach ($sett as $sidx => $s) {
					$v=$vs=[];
					if(!in_array($s['number'],$vs)) {
						$vs[]=$s['number'];
						$v['@id'] = "setting/" . ($sid + 1) . "/value/".(array_search($s['number'],$vs)+1);
						$v['@type'] = "sdo:value";
						if (!is_null($s['number'])) {
							$v['number'] = $s['number'];
							if (!empty($s['Unit']['qudt'])) {
								$unit=$s['Unit']['qudt'];
								$v['unitref'] = "qudt".$unit;
								if(!in_array($unit,$graph['ids'])) {
									$graph['ids'][]=$unit;
								}
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
			$sysj['@type']='sdo:system';
			$json['toc'][]=$sysj['@type'];
			$sysj['discipline']='w3i:Chemistry';
			$sysj['subdiscipline']='w3i:PhysicalChemistry';
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
			$mixj['@id'] = $sid;
			$mixj['@type'] = "sdo:".$type;
			$json['toc'][] = $mixj['@type'];
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
					// constituents
					unset($subj);
					$subj['@id'] = $sid."/constituent/".($j + 1)."/";
					$subj['@type'] = "sdo:chemical";
					$subj['source'] = "chemical/".($j + 2).'/';
					$mixj['constituents'][] = $subj;
					// Substances
					unset($subj);$sub = $sys['Substance'][$j];
					$subj['@id'] = "substance/".($j + 2).'/';
					$subj['@type'] = "sdo:".$sub['type'];
					$json['toc'][] = $subj['@type'];
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
					$chmj['@type'] = "sdo:chemical";
					$json['toc'][] = $chmj['@type'];
					$chmj['source'] = "substance/".($j + 2).'/';
					$chmj['acquired'] = $chem['source'];
					if(!is_null($chem['purity'])) {
						$purj['@id'] = "purity/";
						$purj['@type'] = "sdo:purity";
						$purity=json_decode($chem['purity'],true);
						foreach($purity as $step) {
							$stepsj[$step['step']]['@id'] = "step/".$step['step'].'/';
							$stepsj[$step['step']]['@type'] = "sdo:value";
							$stepsj[$step['step']]['part'] = $step['type'];
							if(!is_null($step['analmeth'])) {
								if(count($step['analmeth'])==1) {
									$stepsj[$step['step']]['analysis']=$step['analmeth'][0];
								} else {
									$stepsj[$step['step']]['analysis']=$step['analmeth'];
								}
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
								$uname=$this->Unit->getfield('name',$step['purityunit_id']);
								$stepsj[$step['step']]['unit']=$uname;
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
				$condj['@type'] = "sdo:condition";
				$json['toc'][] = $condj['@type'];
				$condj['quantity'] = strtolower($cond[0]['Property']['Quantity']['name']);
				$condj['property'] = $cond[0]['Property']['name'];
				foreach ($cond as $cidx => $c) {
					if(!in_array($c['number'],$vs)) {
						$vs[]=$c['number'];
						$v['@id'] = "condition/" . ($cid + 1) . "/value/".(array_search($c['number'],$vs)+1).'/';
						$v['@type'] = "sdo:value";
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

		$cid++;
		// Dataseries conditions
		if(!is_null($ser[0]['Condition'])) {
			foreach($ser[0]['Condition'] as $scidx=>$scond) {
				$scondj=[];$cid++;
				$scondj['@id'] = "condition/".$cid."/";
				$scondj['@type'] = "sdo:condition";
				$json['toc'][] = $scondj['@type'];
				$scondj['quantity'] = strtolower($scond['Property']['Quantity']['name']);
				$scondj['property'] = $scond['Property']['name'];
				$v['@id'] = "condition/".$cid."/value/1/";
				$v['@type'] = "sdo:value";
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

		// Data
		$resj=[];$pnts=[];$pntidx=1;
		if(is_array($datas)&&!empty($datas)) {
			$resj['@id']='dataset/';
			$resj['@type']='sdo:dataset';
			$json['toc'][]=$resj['@type'];
			$resj['source']='measurement/1/';
			$resj['scope']= $type.'/1/';
			$resj['datagroup']=[];

			// groups
			foreach($datas as $did=>$data) {
				$grpj['@id']='datagroup/'.($did+1).'/';
				$grpj['@type'] = 'sdo:datagroup';
				$json['toc'][] = $grpj['@type'];
				$grpj['quantity']=strtolower($data[0]['Property']['Quantity']['name']);
				$grpj['property']=$data[0]['Property']['name'];
				foreach($data as $d=>$dtm) {
					$grpj['datapoint'][]='datapoint/'.$pntidx.'/';
					$dtmj=[];
					$dtmj['@id'] = '/datapoint/'.$pntidx.'/';
					$dtmj['@type'] = 'sdo:datapoint';
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
							$unit=$dtm['Unit']['symbol'];
						}
						if($dtm['datatype']=="datum") {
							$v['@id']=$dtmj['@id']."value/";
							$v['@type']="sdo:value";
							$v['number']=$dtm['number'];
							if($unit!="") { $v['unitref']=$unit; }
							$dtmj['value']=$v;
						} else {
							$v['@id']=$dtmj['@id']."valuearray/";
							$v['@type']="sdo:valuearray";
							$v['numberarray']=json_decode($dtm['number'],true);
							if($unit!="") { $v['unitref']=$unit; }
							$dtmj['valuearray']=$v;
						}
					}
					$pnts[]=$dtmj;$pntidx++;
				}
				$resj['datagroup'][]=$grpj;
			}

			// datapoints
			$resj['datapoints']=$pnts;
		}
		$json['scidata']['dataset']=$resj;

		// Sources
		// Original Paper
		$paper=['@id'=>'source/1/','@type'=>'dc:source'];
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
		$trc=['@id'=>'source/2/','@type'=>'dc:source'];
		$trc['citation'] = "Original data file from NIST TRC Archive at http://www.trc.nist.gov/ThermoML/";
		$trc['url']="http://www.trc.nist.gov/ThermoML/".$ref['doi'];

		$json['sources'][]=$paper;
		$json['sources'][]=$trc;

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

