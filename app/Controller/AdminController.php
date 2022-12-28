<?php /** @noinspection ALL */

/**
 * Class AdminController
 * actions for admin (special) functions
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 12/14/22
 */
class AdminController extends AppController
{
	public $uses=['Chemical','Compohnent','Condition','Data','Datapoint','Dataseries','Dataset','File','Identifier'
		,'Journal','Phase','Phasetype','Quantity','Reference','Sampleprop','Substance','SubstancesSystem','Stat'];

    /**
     * function beforeFilter
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('keylist','jldlist');
    }

	/**
	 * spit out a json encoded list of inchikeys in the substances table
	 */
	public function keylist()
	{
		$keys = $this->Substance->find('list',['fields'=>['inchikey'],'recursive'=>-1]);
		$keystr= "[";
		foreach($keys as $kidx=>$key) {
			if($kidx > 1) { $keystr.=","; }
			$keystr.='"'.$key.'"';
		}
		$keystr.="]";
		header('Content-Type: application/json');
		echo $keystr;exit;

	}

	public function jldlist($code)
	{
		$refs = $this->Reference->find('list',['fields'=>['id'],'conditions'=>['Journal.set'=>$code],'contain'=>['Journal'],'recursive'=>-1]);
		$sets = $this->Dataset->find('list',['fields'=>['setnum','title','reference_id'],'conditions'=>['reference_id'=>$refs],'recursive'=>-1]);
		$json = ['files'=>[],'errors'=>[]];
		foreach($sets as $setlist) {
			foreach($setlist as $setnum=>$title) {
				$parts=explode("/", $title);
				$path='https://scidata.unf.edu/tranche/trc/'.$code.'/'.$parts[1].'_'.$setnum.'.jsonld';
				$chk = get_headers($path, true);
				if(!stristr($chk[0],'200 OK')) {
					$json['errors'][] = "problem with ".$path;
				} else {
					$json['files'][] = $path;
				}
			}
		}
		header('Content-Type: application/json');
		echo json_encode($json);exit;
	}

	// functions requiring login (not in Auth::allow)

	/**
	 * make index files for tranche dir on https://scidata.unf.edu
	 */
	public function trcidx()
	{
		// for each journal based dataset create an index file to search the TRC DB and present files that match
		// also create an overall index file over all the TRC datasets
		$jurnls = $this->Journal->find('list',['fields'=>['id','name'],'conditions'=>['id'=>1],'recursive'=>-1]);
		foreach($jurnls as $jid=>$name) {
			$j=$this->Journal->find('first',['conditions'=>['id'=>$jid],'recursive'=>-1]);$j=$j['Journal'];
			$refs=$this->Reference->find('list',['fields'=>['id','title'],'conditions'=>['id'=>$jid],'recursive'=>-1]);
			$refids=$this->Reference->find('list',['fields'=>['id'],'conditions'=>['journal_id'=>$jid],'recursive'=>-1]);
			$sets=$this->Dataset->find('list',['fields'=>['id','title'],'conditions'=>['reference_id'=>$refids],'recursive'=>-1]);
			$dois=$this->Dataset->find('list',['fields'=>['id','Reference.doi'],'conditions'=>['reference_id'=>$refids],'contain'=>['Reference'],'recursive'=>-1]);
			$syss=$this->Dataset->find('list',['fields'=>['System.id','System.name','id'],'conditions'=>['reference_id'=>$refids],'contain'=>['System'],'recursive'=>-1]);
			$sysids=$this->Dataset->find('list',['fields'=>['id','System.id'],'conditions'=>['reference_id'=>$refids],'contain'=>['System'],'recursive'=>-1]);
			$setids=$this->Dataset->find('list',['fields'=>['id'],'conditions'=>['reference_id'=>$refids],'recursive'=>-1]);
			$props=$this->Sampleprop->find('list',['fields'=>['id','quantity_name','dataset_id'],'conditions'=>['dataset_id'=>$setids],'recursive'=>-1]);
			$meths=$this->Sampleprop->find('list',['fields'=>['dataset_id','method_name'],'conditions'=>['dataset_id'=>$setids],'recursive'=>-1]);
			$conds=$this->Condition->find('list',['fields'=>['Quantity.id','Quantity.name','dataset_id'],'conditions'=>['dataset_id'=>$setids],'contain'=>['Quantity'],'recursive'=>-1]);
			// create the Google Datasets JSON-LD header
			$gdsld=['@context'=>'http://schema.org/','@type'=>'Dataset'];
			$gdsld['name']='SciData Framework JSON-LD Conversion of the set of '.$j['name'].' files in the NIST TRC ThermoML Dataset at https://trc.nist.gov/ThermoML/';
			$gdsld['description']='This JSON-LD documents were created using code in the GitHub repository at https://github.com/ChalkLab/scidata_trc';
			$gdsld['url']='https://scidata.unf.edu/tranche/trc/'.$j['set'];
			$gdsld['license']='https://www.nist.gov/open/license';
			$gdsld['creator']=[
				'@type'=>'Organization',
				'name'=>'Department of Chemistry',
				'contactPoint'=>[
					'@type'=>'Contactpoint',
					'name'=>'Stuart Chalk',
					'email'=>'schalk@unf.edu'
					],
				'parentOrganization'=>[
					'@type'=>'Organization',
					'name'=>'University of North Florida',
					'alternateName'=>'UNF'
				]
			];
			$gdsld['hasPart']=[];
			$data=[];
			// generate variable to send to page and populate the list of files
			foreach($sets as $setid=>$title) {
				//debug($setid);
				$d=$this->Dataset->find('first',['conditions'=>['id'=>$setid],'recursive'=>-1]);$d=$d['Dataset'];
				$r=$this->Reference->find('first',['conditions'=>['id'=>$d['reference_id']],'recursive'=>-1]);$r=$r['Reference'];
				// populate the data for the list of files
				$set=[];
				$set['title']=$title;
				$set['paper']=$r['title'];
				$set['doi']=$dois[$setid];
				$set['pnts']=$d['points'];
				$parts=explode('/',$dois[$setid]);
				$set['path']='https://scidata.unf.edu/tranche/trc/'.$j['set'].'/'.$parts[1].'_'.$d['setnum'].'.jsonld';
				$set['trc']='https://trc.nist.gov/ThermoML/'.$set['doi'].'.html';
				if(empty($conds[$setid])) {
					$set['conds']='';
				} else {
					$set['conds']=implode(',',$conds[$setid]);
				}
				$set['props']=implode(',',$props[$setid]);
				$set['subs']=implode(',',$syss[$setid]);
				$data[]=$set;
				// get substance information from sysid
				$sysid=$sysids[$setid];
				$subids=$this->SubstancesSystem->find('list',['fields'=>['substance_id'],'conditions'=>['system_id'=>$sysid],'recursive'=>-1]);
				$subs=[];
				$forms=$this->Substance->find('list',['fields'=>['id','formula'],'conditions'=>['id'=>$subids],'recursive'=>-1]);
				$names=$this->Substance->find('list',['fields'=>['id','name'],'conditions'=>['id'=>$subids],'recursive'=>-1]);
				$sids=$this->Identifier->find('list',['fields'=>['type','value','substance_id'],'conditions'=>['substance_id'=>$subids],'recursive'=>-1]);
				foreach($subids as $subid) {
					$sub=[];
					if(!empty($sids[$subid]['iupacname'])) {
						$sub['iupacname']=$sids[$subid]['iupacname'];
					} else {
						$sub['name']=$names[$subid];
					}
					$sub['formula']=$forms[$subid];
					$sub['inchikey']=$sids[$subid]['inchikey'];
					$subs[]=implode(" ",$sub);
				}
				// populate the JSON-LD for Google Dataset Search for this set
				if(!isset($gdsld['hasPart'][$dois[$setid]])) {
					$paper=[];
					$paper=['@type'=>'Dataset'];
					$paper['name']='Datasets from paper doi:'.$r['doi'].' as SciData JSON-LD, converted from NIST ThermoML file \''.$set['trc'].'\'';
					$paper['description']='SciData Framework JSON-LD conversion of the PureOrMixtureData datasets from file \''.$set['trc'].'\', derived from paper doi:'.$r['doi'].'.';
					$paper['license']='https://www.nist.gov/open/license';
					$paper['creator']=['@type'=>'Person','name'=>'Stuart Chalk'];
					$paper['citation']=$set['doi'];
					$paper['contentUrl']=[];
					$paper['material']=[];
					$paper['variableMeasured']=[];
					$paper['measurementTechnique']=[];
					$gdsld['hasPart'][$r['doi']]=$paper;
				}
				// path to file
				$gdsld['hasPart'][$dois[$setid]]['contentUrl'][]=$set['path'];
				// add substances (materials)
				foreach($subs as $sub) { $gdsld['hasPart'][$dois[$setid]]['material'][]=$sub; }
				$tmpmat=array_values(array_unique($gdsld['hasPart'][$dois[$setid]]['material']));
				$gdsld['hasPart'][$dois[$setid]]['material']=$tmpmat;
				// add props (not adding the condition props here)
				foreach($props[$setid] as $prop) { $gdsld['hasPart'][$dois[$setid]]['variableMeasured'][]=$prop; }
				$tmpvar=array_values(array_unique($gdsld['hasPart'][$dois[$setid]]['variableMeasured']));
				$gdsld['hasPart'][$dois[$setid]]['variableMeasured']=$tmpvar;
				$gdsld['hasPart'][$dois[$setid]]['measurementTechnique'][]=$meths[$setid];
				$tmptec=array_values(array_unique($gdsld['hasPart'][$dois[$setid]]['measurementTechnique']));
				$gdsld['hasPart'][$dois[$setid]]['measurementTechnique']=$tmptec;
			}
			// remove doi's from the hasPart section
			$tmpsets=array_values($gdsld['hasPart']);
			$gdsld['hasPart']=$tmpsets;
			// header('Content-Type: application/json');
			// header('Content-Disposition: attachment; filename="'.$j['set'].'.json"');
			// echo "[".json_encode($gdsld)."]";exit;
			$this->set('jld',json_encode($gdsld));
			$this->set('journal',$j);
			$this->set('data',$data);
			$this->layout = 'ajax';
		}
	}

	/**
	 * update entries in the data_systems join table
	 * run once
	 * @param int $start
	 * @param int $limit
	 * @return void
	 */
	public function datasys(int $start=0, int $limit=5000)
	{
		// function is defined in the 'Model/Data.php' file
		$this->Data->joinsys('bulk',$start,$limit);exit;
	}

	/**
	 * check that all data has been added to the data_systems table
	 * script takes time and thus data is chunked and the timing monitored
	 * run once
	 * @param int $from
	 * @return void
	 */
	public function chkdatasys(int $from=0)
	{
		$allcount=$this->DataSystem->find('count',['recursive'=>-1]);
		$alldids=$this->Data->find('list',['fields'=>['id'],'contain'=>['Dataset'],'order'=>'id','recursive'=>-1]);
		$chunk=10000;
		$chunks=ceil($allcount/$chunk);

		for($x=$from;$x<$chunks;$x++) {
			$start = microtime(true);
			$dsids=$this->DataSystem->find('list',['fields'=>['id','data_id'],'start'=>($x*$chunk),'limit'=>$chunk,'order'=>'id']);
			foreach($dsids as $did) {
				if(!in_array($did,$alldids)) { debug($did); }
			}
			$elapsed=microtime(true) - $start;
			echo "Processed chunk #".($x+1)." (".ceil($elapsed)." s)<br/>";
		}
		exit;
	}

	/**
	 * generate stats from DB and store in stats table
	 * run as needed
	 * @return void
	 */
	public function dbstats()
	{
		// stats about the following
		// for a file, how many datasets (PureOrMixtureData sections of an XML document)
		// for a file, how many datapoints (NumValues across all PureOrMixtureData sections of an XML document)
		// for a dataset, how many datapoints (NumValues in a PureOrMixtureData section)
		// for a system, how many components (Component(s) in a PureOrMixtureData section)
		// for a system, systems (concat InChIKeys in Compound in XML)
		// for a system, substances (InChIKey in Compound in XML)
		// for a dataset, how many conditions (Constraint(s) + Variable(s) in a PureOrMixture section)
		// for a dataset, how many data (Properties in a PureOrMixture section)

		// DB
		$fids=$this->File->find('list',['fields'=>['id','filename'],'order'=>['id'],'recursive'=>-1]);
		$dsdone=$this->Stat->find('list',['fields'=>['file_id'],'conditions'=>['metric'=>'datasets'],'recursive'=>-1]);
		$dscdone=$this->Stat->find('list',['fields'=>['file_id'],'conditions'=>['metric'=>'chemicals'],'recursive'=>-1]);
		$ddpdone=$this->Stat->find('list',['fields'=>['dataset_id','dbt'],'conditions'=>['metric'=>'points (dataset)'],'recursive'=>-1]);
		$fdpdone=$this->Stat->find('list',['fields'=>['file_id'],'conditions'=>['metric'=>'points (file)'],'recursive'=>-1]);
		$cdpdone=$this->Stat->find('list',['fields'=>['dataset_id'],'conditions'=>['metric'=>'components'],'recursive'=>-1]);
		$sdpdone=$this->Stat->find('list',['fields'=>['dataset_id'],'conditions'=>['metric'=>'substances'],'recursive'=>-1]);

		// XML
		$dsxdone=$this->Stat->find('list',['fields'=>['file_id'],'conditions'=>['NOT'=>['xml'=>null],'metric'=>'datasets'],'recursive'=>-1]);
		$dscxdone=$this->Stat->find('list',['fields'=>['file_id'],'conditions'=>['NOT'=>['xml'=>null],'metric'=>'chemicals'],'recursive'=>-1]);
		$ddpxdone=$this->Stat->find('list',['fields'=>['dataset_id','xml'],'conditions'=>['NOT'=>['xml'=>null],'metric'=>'points (dataset)'],'recursive'=>-1]);
		$fdpxdone=$this->Stat->find('list',['fields'=>['file_id'],'conditions'=>['NOT'=>['xml'=>null],'metric'=>'points (file)'],'recursive'=>-1]);
		$cdpxdone=$this->Stat->find('list',['fields'=>['dataset_id'],'conditions'=>['NOT'=>['xml'=>null],'metric'=>'components'],'recursive'=>-1]);
		$sdpxdone=$this->Stat->find('list',['fields'=>['dataset_id'],'conditions'=>['NOT'=>['xml'=>null],'metric'=>'substances'],'recursive'=>-1]);

		foreach($fids as $fid=>$fname) {
			// dataset count
			if(!in_array($fid,$dsdone)) {
				$dscnt=$this->Dataset->find('count',['conditions'=>['Dataset.file_id'=>$fid]]);
				$statid=0;$conds=['file_id'=>$fid,'metric'=>'datasets','dbt'=>$dscnt];
				$this->Stat->add($conds,$statid);
				echo 'Added dataset stat for file '.$fid.'<br/>';
			}
			// chemical count
			if(!in_array($fid,$dscdone)) {
				$chcnt=$this->Chemical->find('count',['conditions'=>['Chemical.file_id'=>$fid]]);
				$statid=0;$conds=['file_id'=>$fid,'metric'=>'chemicals','dbt'=>$chcnt];
				$this->Stat->add($conds,$statid);
				echo 'Added chemical stat for file '.$fid.'<br/>';
			}
			// datapoint count (dataset)
			$c=['System'=>['Substance'],'Mixture'=>['Compohnent'],'Dataseries'=>['Datapoint'],'Chemical'];
			$dsets=$this->Dataset->find('all',['conditions'=>['Dataset.file_id'=>$fid],'contain'=>$c,'recursive'=>-1]);
			$fdpcnt=0;
			foreach($dsets as $dset) {
				$dsid=$dset['Dataset']['id'];$dpcnt=0;
				if(!in_array($dsid,array_keys($ddpdone))) {
					foreach($dset['Dataseries'] as $ser) { $dpcnt+=count($ser['Datapoint']); }
					$statid=0;$conds=['file_id'=>$fid,'dataset_id'=>$dsid,'metric'=>'points (dataset)','dbt'=>$dpcnt];
					$this->Stat->add($conds,$statid);
					echo 'Added datapoints stat for dataset '.$dsid.'<br/>';
				} else {
					$dpcnt+=$ddpdone[$dsid];
				}
				$fdpcnt+=$dpcnt;
			}
			// datapoint count (file)
			if(!in_array($fid,$fdpdone)) {
				$statid=0;$conds=['file_id'=>$fid,'metric'=>'points (file)','dbt'=>$fdpcnt];
				$this->Stat->add($conds,$statid);
				echo 'Added datapoints stat for file '.$fid.'<br/>';
			}
			// systems in datasets (check chemicals along the way)
			foreach ($dsets as $dset) {
				$sys=$dset['System'];$keys=[];
				$subcnt[0]=count($sys['Substance']);
				$subcnt[1]=count($dset['Chemical']);
				$subcnt[2]=count($dset['Mixture']['Compohnent']);
				$subcnt=array_unique($subcnt);
				if(count($subcnt)==1) {
					$dsid=$dset['Dataset']['id'];
					if(!in_array($dsid,$cdpdone)) {
						$statid=0;$conds=['file_id'=>$fid,'dataset_id'=>$dset['Dataset']['id'],'metric'=>'components','dbt'=>$subcnt[0]];
						$this->Stat->add($conds,$statid);
						echo 'Added component stat for system '.$sys['id'].'<br/>';
					}
					// inchikey identifier
					if(!in_array($dsid,$sdpdone)) {
						foreach($sys['Substance'] as $sub) { $keys[]=$sub['inchikey']; }
						sort($keys);
						$keystr=implode(':',$keys);
						$statid=0;$conds=['file_id'=>$fid,'dataset_id'=>$dset['Dataset']['id'],'metric'=>'substances','dbt'=>$keystr];
						$this->Stat->add($conds,$statid);
						echo 'Added substance keys for system '.$sys['id'].'<br/>';
					}
				} else {
					echo "# system components inconsistent";
					debug($sys['Substance']);debug($dset['Chemical']);debug($dset['Mixture']);exit;
				}
			}

			// OK now check the XML
			$fldr="";
			if(substr($fname,0,2)=="ac"||substr($fname,0,2)=="je") {
				$fldr="jced";
			} elseif(substr($fname,0,3)=="j.f") {
				$fldr="fpe";
			} elseif(substr($fname,0,3)=="j.j") {
				$fldr="jct";
			} elseif(substr($fname,0,3)=="j.t") {
				$fldr="tca";
			} elseif(substr($fname,0,3)=="s10") {
				$fldr="ijt";
			}
			$path = WWW_ROOT.'files'.DS.'trc'.DS.$fldr.DS;
			if (file_exists($path)) {
				$xml = simplexml_load_file($path.$fname);
				$trc = json_decode(json_encode($xml), true);
				// dataset count (PureOrMixtureData)
				if(!isset($trc['PureOrMixtureData'][0])) { $trc['PureOrMixtureData']=[0=>$trc['PureOrMixtureData']]; }
				if(!in_array($fid,$dsxdone)) {
					$stat=$this->Stat->find('first',['conditions'=>['Stat.file_id'=>$fid,'metric'=>'datasets'],'recursive'=>-1]);
					$xcnt=count($trc['PureOrMixtureData']);
					$conds=['id'=>$stat['Stat']['id'],'file_id'=>$fid,'metric'=>'datasets','xml'=>$xcnt];
					$this->Stat->save($conds);
					echo 'Added dataset stat for XML '.$fid.'<br/>';
				}
				// chemicals count (Compound)
				if(!isset($trc['Compound'][0])) { $trc['Compound']=[0=>$trc['Compound']]; }
				if(!in_array($fid,$dscxdone)) {
					$stat=$this->Stat->find('first',['conditions'=>['Stat.file_id'=>$fid,'metric'=>'chemicals'],'recursive'=>-1]);
					$xcnt=count($trc['Compound']);
					$conds=['id'=>$stat['Stat']['id'],'file_id'=>$fid,'metric'=>'chemicals','xml'=>$xcnt];
					$this->Stat->save($conds);
					echo 'Added chemical stat for XML '.$fid.'<br/>';
				}
				$fdpxcnt=0;
				foreach($trc['PureOrMixtureData'] as $set) {
					$setnum=$set['nPureOrMixtureDataNumber'];$dpxcnt=0;
					$conds=['file_id'=>$fid,'setnum'=>$setnum];
					$dsid=$this->Dataset->find('list',['fields'=>['setnum','id'],'conditions'=>$conds,'recursive'=>-1]);
					if(!in_array($dsid[$setnum],array_keys($ddpxdone))) {
						// datapoint count (NumValues)
						$stat=$this->Stat->find('first',['conditions'=>['Stat.file_id'=>$fid,'Stat.dataset_id'=>$dsid[$setnum],'metric'=>'points (dataset)'],'recursive'=>-1]);
						if(!isset($set['NumValues'][0])) { $set['NumValues']=[0=>$set['NumValues']]; }
						$conds=['id'=>$stat['Stat']['id'],'file_id'=>$fid,'dataset_id'=>$dsid[$setnum],'xml'=>count($set['NumValues'])];
						$this->Stat->save($conds);
						echo 'Added points (dataset) stat for XML '.$dsid[$setnum].'<br/>';
						$dpxcnt+=count($set['NumValues']);
					} else {
						$dpxcnt+=$ddpxdone[$dsid[$setnum]];
					}
					$fdpxcnt+=$dpxcnt;
					// components (in datasets Component)
					if(!in_array($dsid[$setnum],$cdpxdone)) {
						// datapoint count (NumValues)
						$stat=$this->Stat->find('first',['conditions'=>['Stat.file_id'=>$fid,'Stat.dataset_id'=>$dsid[$setnum],'metric'=>'components'],'recursive'=>-1]);
						if(!isset($set['Component'][0])) { $set['Component']=[0=>$set['Component']]; }
						$conds=['id'=>$stat['Stat']['id'],'file_id'=>$fid,'dataset_id'=>$dsid[$setnum],'xml'=>count($set['Component'])];
						$this->Stat->save($conds);
						echo 'Added components stat for XML '.$dsid[$setnum].'<br/>';
					}
					// substances (via Component -> Compound)
					if(!in_array($dsid[$setnum],$sdpxdone)) {
						// datapoint count (NumValues)
						$stat=$this->Stat->find('first',['conditions'=>['Stat.file_id'=>$fid,'Stat.dataset_id'=>$dsid[$setnum],'metric'=>'substances'],'recursive'=>-1]);
						if(!isset($set['Component'][0])) { $set['Component']=[0=>$set['Component']]; }
						$keys=[];
						foreach($set['Component'] as $cmpt) {
							$cmpnum=$cmpt['RegNum']['nOrgNum'];
							$keys[]=$trc['Compound'][($cmpnum-1)]['sStandardInChIKey'];  // indexing starts at 0
						}
						sort($keys);
						$keystr=implode(":",$keys);
						$conds=['id'=>$stat['Stat']['id'],'file_id'=>$fid,'dataset_id'=>$dsid[$setnum],'xml'=>$keystr];
						$this->Stat->save($conds);
						echo 'Added substances stat for XML '.$dsid[$setnum].'<br/>';
					}
				}
				// datapoint count (for the file)
				if(!in_array($fid,$fdpxdone)) {
					$stat=$this->Stat->find('first',['conditions'=>['Stat.file_id'=>$fid,'metric'=>'points (file)'],'recursive'=>-1]);
					$conds=['id'=>$stat['Stat']['id'],'file_id'=>$fid,'metric'=>'points (file)','xml'=>$fdpxcnt];
					$this->Stat->save($conds);
					echo 'Added point stat for file '.$fid.'<br/>';
				}
			} else {
				echo "File not found";debug($fldr);debug($fname);exit;
			}
		}
		exit;
	}

	/**
	 * check the assigned components against original XML files
	 * (noticed that pressure conditions associated with specific components (chemicals) which seems odd)
	 * run once
	 * @param int|null $qntyid
	 * @param int|null $ver
	 * @return void
	 */
	public function chkqtycomps(int $qntyid=null,int $ver=null)
	{
		if(is_null($qntyid)) { echo "Please add a quantity ID!";exit; }
		$jids=['jced','jct','fpe','tca','ijt'];
		foreach($jids as $jid) {
			$path = WWW_ROOT . 'files' . DS . 'trc' . DS . $jid . DS;
			$maindir = new Folder($path);
			$files = $maindir->find("^.+\.xml$", true);
			$c = ['Compohnent', 'Quantity'];
			$allds = $this->Dataset->find('list', ['fields' => ['trcidset_id', 'id']]);
			$done = $this->Dataset->find('list', ['fields' => ['id', 'trcidset_id'], 'conditions' => ['comments' => 'compchk']]);
			// define property to check
			$q=$this->Quantity->find('first',['conditions'=>['id'=>$qntyid]]);
			$qtype = $q['Quantity']['vartype'];
			$qntystrs=json_decode($q['Quantity']['field']);
			if(count($qntystrs)==1||is_null($ver)) {
				// default to the first entry if $ver not set
				$qnty = $qntystrs[0];
			} else {
				// $ver starts at 1 whereas idx of array starts at 0
				$qnty = $qntystrs[($ver-1)];
			}
			foreach ($files as $filename) {
				$filepath = $path . $filename;
				$xml = simplexml_load_file($filepath);
				$trc = json_decode(json_encode($xml), true);

				// get trcid (if present)
				$trcid = null;$cite = $trc['Citation'];
				if (isset($cite['TRCRefID'])) { $trcid = $this->Reference->trcid($cite['TRCRefID']); }

				// get datasets
				if (!isset($trc['PureOrMixtureData'][0])) {
					// needed as PHP XML to JSON using json_decode(json_encode($xml), true)
					// does not add [0] when only one subarray present
					$trc['PureOrMixtureData'] = [0 => $trc['PureOrMixtureData']];
				}
				$sets = $trc['PureOrMixtureData'];
				foreach ($sets as $set) {
					$setnum = $set['nPureOrMixtureDataNumber'];
					$trcsetid = $trcid . '-' . $setnum;
					$setid = $allds[$trcsetid];
					if (in_array($trcsetid, $done)) { echo "Dataset '" . $trcsetid . "' already done<br/>";continue; }
					// check for presence of conditions for this quantity
					$srch = ['dataset_id' => $setid, 'phasechk' => null, 'quantity_id' => $qntyid];
					$conds = $this->Condition->find('all', ['conditions' => $srch, 'contain' => $c, 'recursive' => -1]);
					if (empty($conds)) {
						echo "No unchecked conditions of type '" . $qnty . "' in '" . $trcsetid . "'<br/>";
						goto updatebd;
					}

					// check for quantity as constraint
					$cqnty=$vqnty=$cqntycomp=$vqntycomp=$condid=null;
					if (empty($set['Constraint'])) {
						echo "No constraints present<br/>";
					} else {
						// iterate over constraints
						if (!isset($set['Constraint'][0])) {
							$set['Constraint'] = [0 => $set['Constraint']];
						}
						// is constraint the property we are checking?
						foreach ($set['Constraint'] as $con) {
							//if($setnum==2) { debug($con); }
							if (empty($con['ConstraintID']['ConstraintType'][$qtype])) {
								echo "No '" . $qnty . "' constraint present<br/>";
							} else {
								if ($con['ConstraintID']['ConstraintType'][$qtype] != $qnty) {
									echo "Different constraint type present<br/>";
								} else {
									// contraint is set and is the property
									// component present or not?
									$cqnty = "yes";
									echo "Constraint '" . $qnty . "' present in '" . $trcsetid . "'<br/>";
									if (!empty($con['ConstraintID']['RegNum']['nOrgNum'])) {
										echo "Constraint compound present<br/>";
										$cqntycomp = $con['ConstraintID']['RegNum']['nOrgNum'];
									} else {
										echo "Constraint compound not present in '" . $trcsetid . "'<br/>";
									}
								}
							}
						}
					}
					if ($cqnty == "yes") {
						if (is_null($cqntycomp)) {
							foreach ($conds as $cidx => $cond) {
								$cond = $cond['Condition'];
								$condid = $cond['id'];
								$this->Condition->id = $condid;
								if (!empty($cond['dataseries_id'])) {
									if (!is_null($cond['component_id'])) {
										$this->Condition->saveField('phasechk', 'compnotsetinfile');
									} else {
										$this->Condition->saveField('phasechk', 'verified');
									}
									echo "Condition (Constraint) '" . $cond['id'] . "' updated (no compound)<br/>";
									unset($conds[$cidx]);
								}
							}
						} else {
							foreach ($conds as $cidx => $cond) {
								$cond = $cond['Condition'];
								$condid = $cond['id'];
								$this->Condition->id = $condid;
								if (!empty($cond['dataseries_id'])) {
									if (!is_null($cond['component_id'])) {
										$this->Condition->saveField('phasechk', 'verified');
									} else {
										$this->Condition->saveField('phasechk', 'compsetinfile');
									}
									echo "Condition (Constraint) '" . $cond['id'] . "' updated (with compound)<br/>";
									unset($conds[$cidx]);
								}
							}
						}
						echo "Condition '" . $condid . "' updated<br/>";
					}
					if (empty($conds)) { goto updatebd; }

					// check for quantity as variable
					if (empty($set['Variable'])) {
						echo "No variables present<br/>";
						exit;
					} else {
						// iterate over variables
						if (!isset($set['Variable'][0])) {
							$set['Variable'] = [0 => $set['Variable']];
						}
						foreach ($set['Variable'] as $var) {
							// is variable the property we are checking?
							if (empty($var['VariableID']['VariableType'][$qtype])) {
								echo "No '" . $qnty . "' variable present<br/>";
							} else {
								if ($var['VariableID']['VariableType'][$qtype] != $qnty) {
									echo "Different variable type present<br/>";
								} else {
									echo "Found '" . $qnty . "' variable present<br/>";
									// contraint is set and is the property
									// component present or not?
									$vqnty = "yes";
									if (!empty($var['VariableID']['RegNum']['nOrgNum'])) {
										$vqntycomp = $var['VariableID']['RegNum']['nOrgNum'];
									}
								}
							}
						}
					}
					if ($vqnty == "yes") {
						if (is_null($vqntycomp)) {
							echo "Found component with type '" . $qnty . "'<br/>";//debug($conds);exit;
							foreach ($conds as $cidx => $cond) {
								$cond = $cond['Condition'];
								$condid = $cond['id'];
								$this->Condition->id = $condid;
								if (!empty($cond['dataseries_id'])) {
									if (!is_null($cond['component_id'])) {
										$this->Condition->saveField('phasechk', 'compnotsetinfile');
									} else {
										$this->Condition->saveField('phasechk', 'verified');
									}
									echo "Condition (Variable) '" . $cond['id'] . "' updated (no compound)<br/>";
								}
								if (!empty($cond['datapoint_id'])) {
									if (!is_null($cond['component_id'])) {
										$this->Condition->saveField('phasechk', 'compnotsetinfile');
									} else {
										$this->Condition->saveField('phasechk', 'verified');
									}
									echo "Condition (Variable) '" . $cond['id'] . "' updated (no compound)<br/>";
								}
								unset($conds[$cidx]);
							}
						} else {
							foreach ($conds as $cidx => $cond) {
								$cond = $cond['Condition'];
								$condid = $cond['id'];
								$this->Condition->id = $condid;
								if (!empty($cond['dataseries_id'])) {
									if (!is_null($cond['component_id'])) {
										$this->Condition->saveField('phasechk', 'verified');
									} else {
										$this->Condition->saveField('phasechk', 'compsetinfile');
									}
									echo "Condition (Variable) '" . $cond['id'] . "' updated (with compound)<br/>";
								}
								if (!empty($cond['datapoint_id'])) {
									if (!is_null($cond['component_id'])) {
										$this->Condition->saveField('phasechk', 'verified');
									} else {
										$this->Condition->saveField('phasechk', 'compsetinfile');
									}
									echo "Condition (Variable) '" . $cond['id'] . "' updated (with compound)<br/>";
								}
								unset($conds[$cidx]);
							}
						}
					} else {
						echo "No variable quantity set (name variant?)<br/>";
						debug($set['Constraint']);debug($set['Variable']);
					}
					//debug($conds);exit;
					if (empty($conds)) { goto updatebd; }
					echo "There are unchecked conditions...<br/>";

					// goto link
					updatebd:
					// update dataset table to indicate check has already been done...
					$this->Dataset->id = $setid;
					$this->Dataset->saveField('comments', 'compchk');
					echo "Dataset " . $setid . " updated<br/>";
				}
				echo "File '" . $filename . "' complete<br/>";
			}
		}
		exit;
	}

	/**
	 * check that values in each row are consistent and match against original XML files
	 * run once
	 * @return void
	 */
	public function chkvalues()
	{
		$jids=['jced','jct','fpe','tca','ijt'];
		foreach($jids as $jid) {
			$path = WWW_ROOT.'files'.DS.'trc'.DS.$jid.DS;
			$maindir = new Folder($path);
			$files = $maindir->find("^.+\.xml$", true);
			$allsets = $this->Dataset->find('list',['fields'=>['refsetnum','id']]);
			$allrefs = $this->Reference->find('list',['fields'=>['doi','id']]);
			//$done = $this->Condition->find('list',['fields'=>['id'],'conditions'=>['NOT'=>['valuechk'=>null]]]);
			foreach ($files as $filename) {
				$filepath = $path . $filename;
				$xml = simplexml_load_file($filepath);
				$trc = json_decode(json_encode($xml), true);

				// get reference id from database
				$cite = $trc['Citation'];$doi=$cite['sDOI'];$refid=$allrefs[$doi];

				// get datasets
				if (!isset($trc['PureOrMixtureData'][0])) {
					// needed as PHP XML to JSON using json_decode(json_encode($xml), true)
					// does not add [0] when only one subarray present
					$trc['PureOrMixtureData'] = [0 => $trc['PureOrMixtureData']];
				}
				$sets = $trc['PureOrMixtureData'];
				foreach($sets as $set) {
					$cons=$nvals=null;
					$setnum = $set['nPureOrMixtureDataNumber'];
					// get constraints (series conditions) data
					if(!isset($set['Constraint'][0])) {
						$cons=[0=>$set['Constraint']];
					} elseif(isset($set['Constraint'])) {
						$cons=$set['Constraint'];
					}
					// get datapoints (in NumValues)
					if(!isset($set['NumValues'][0])) {
						$nvals=[0=>$set['NumValues']];
					} elseif(isset($set['NumValues'])) {
						$nvals=$set['NumValues'];
					}
					//debug($cons);debug($nvals);exit;
					// get database conditions for this dataset
					$setid = $allsets[$refid.':'.$setnum];
					$conds = $this->Condition->find('all',['conditions'=>['dataset_id'=>$setid],'recursive'=>-1]);
					// check constraints
					$constats=[]; // captures how many times a constraint is matched (can be multiple)
					foreach($conds as $condidx=>$cond) {
						$cond=$cond['Condition'];
						if(!empty($cond['dataseries_id'])) {
							foreach($cons as $con) {
								$chkval=0;
								if($con['nConstraintValue']==$cond['text']&&$con['nConstrDigits']==$cond['accuracy']) {
									$chkval+=4;
								}
								if($cond['number']==$cond['text']) {
									$chkval+=2;
								}
								if($cond['number']==$cond['significand']*pow(10,$cond['exponent'])) {
									$chkval+=1;
								}
								$this->Condition->id = $cond['id'];
								switch($chkval) {
									case 0:
									case 1:
									case 2:
									case 3:
										$this->Condition->saveField('valuechk', 'text != XML value');break;
									case 4:
										$this->Condition->saveField('valuechk', 'number inconsistent');break;
									case 5:
										$this->Condition->saveField('valuechk', 'number != text');break;
									case 6:
										$this->Condition->saveField('valuechk', 'number != significand * exponent');break;
									case 7:
										$this->Condition->saveField('valuechk', 'OK');break;
								}
								if(!isset($constats[$condidx])) {
									$constats[$condidx]=1;
								} else {
									$constats[$condidx]++;
								}
								unset($conds[$condidx]);
							}
						}
					}
					foreach($cons as $conidx=>$con) {
						if(empty($constats[$conidx])) {
							echo "Did not match all constraints!";debug($cons);exit;
						}
					}
					debug($constats);
					// check variables
					foreach($conds as $condidx=>$cond) {
						$cond=$cond['Condition'];
						if(!empty($cond['datapoint_id'])) {
							foreach($nvals as $nvalidx=>$nval) {
								$vals=$nval['VariableValue'];
								if(!isset($vals[0])) { $vals=[0=>$vals]; }
								foreach($vals as $val) {
									$chkval=0;
									if($val['nVarValue']==$cond['text']&&$val['nVarDigits']==$cond['accuracy']) {
										$chkval+=4;
									}
									if($cond['number']==$cond['text']) {
										$chkval+=2;
									}
									if($cond['number']==$cond['significand']*pow(10,$cond['exponent'])) {
										$chkval+=1;
									}
									$this->Condition->id = $cond['id'];
									switch($chkval) {
										case 0:
										case 1:
										case 2:
										case 3:
											$this->Condition->saveField('valuechk', 'text != XML value');break;
										case 4:
											$this->Condition->saveField('valuechk', 'number inconsistent');break;
										case 5:
											$this->Condition->saveField('valuechk', 'number != text');break;
										case 6:
											$this->Condition->saveField('valuechk', 'number != significand * exponent');break;
										case 7:
											$this->Condition->saveField('valuechk', 'OK');break;
									}
									unset($nvals[$nvalidx]);
									unset($conds[$condidx]);
								}
							}
						}
					}
					debug($nvals);debug($conds);
					if(!empty($conds)) {
						echo "Did not match all conditions!";debug($conds);exit;
					} else {
						$this->Dataset->id = $setid;
						$this->Dataset->saveField('cndvalschk', 'yes');
					}
					exit;
				}
				exit;
			}
		}
	}

	/**
	 * check that condition values in each row match against in the text field
	 * (the text field should contain the original value as written in the XML file)
	 * run once
	 * @return void
	 */
	public function chkconds()
	{
		$conds = $this->Condition->find('all',['conditions'=>['valuechk'=>null],'limit'=>500000,'recursive'=>-1]);
		foreach($conds as $cond) {
			$cond=$cond['Condition'];
			$chkval=0;
			if ($cond['number'] == $cond['text']) { $chkval += 1; }
			$result1 = (float) $cond['significand'] * pow(10, $cond['exponent']);
			$result2 = (float) $cond['number'];
			echo $cond['id'].": ".$result1." - ".$result2." = ".($result1-$result2).'<br/>';
			$diff=abs($result1-$result2);
			if ($diff==0||$diff < $cond['text']/10000) {
				$chkval += 2;
			} else {
				echo "large non-zero diff"; debug($diff);exit;
			}

			// update entry in the conditions table
			$this->Condition->id = $cond['id'];
			switch($chkval) {
				case 0:
					$this->Condition->saveField('valuechk', 'nothing matches');break;
				case 1:
					$this->Condition->saveField('valuechk', 'number != sgf*exp');break;
				case 2:
					$this->Condition->saveField('valuechk', 'number != text');break;
				case 3:
					$this->Condition->saveField('valuechk', 'OK');break;
			}
			echo "Completed condition '".$cond['id']."'<br/>";
		}
		exit;
	}

	/**
	 * check that data values in each row match against in the text field
	 * (the text field should contain the original value as written in the XML file)
	 * run once
	 * @return void
	 */
	public function chkdata()
	{
		$data = $this->Data->find('all',['conditions'=>['valuechk'=>null],'limit'=>250000,'recursive'=>-1]);
		foreach($data as $datum) {
			$datum=$datum['Data'];
			$chkval=0;
			if ($datum['number'] == $datum['text']) { $chkval += 1; }
			$result1 = (float) $datum['significand'] * pow(10, $datum['exponent']);
			$result2 = (float) $datum['number'];
			echo $datum['id'].': '.$result1.' - '.$result2.' = '.($result1-$result2).'<br/>';
			$diff=abs($result1-$result2);
			if ($diff < $datum['error']/1000) { $chkval += 2; }
			$this->Data->id = $datum['id'];

			// update entry in the data table
			switch($chkval) {
				case 0:
					$this->Data->saveField('valuechk', 'nothing matches');break;
				case 1:
					$this->Data->saveField('valuechk', 'number != sgf*exp');break;
				case 2:
					$this->Data->saveField('valuechk', 'number != text');break;
				case 3:
					$this->Data->saveField('valuechk', 'OK');break;
			}
			echo "Completed data '".$datum['id']."'<br/>";
		}
		exit;
	}

	/**
	 * check that the values in number and significand are correctly quoted per the accuracy
	 * @param int $acc
	 * @return void
	 */
	public function chkdataacc(int $acc=0)
	{
		// NOTE: $acc variable allows the script to be run on data with all the same accuracy (easier to compare)
		$issues = $this->Data->find('all',['conditions'=>['issue'=>'sigfigs','accuracy'=>$acc],'recursive'=>-1]);
		foreach($issues as $issue) {
			// process issue
			$row=$issue['Data'];
			$n=$row['number'];$s=$row['significand'];$e=$row['exponent'];$a=$row['accuracy'];
			$slen=strlen($s);
			if(stristr($s,'.')) { $slen--; } // -1 for decimal point
			if($s[0]=='-') { $slen--; } // -1 for negative number
			$diff=$a-$slen;
			$newn=$n;$news=$s;
			if($e<1) {
				if(!stristr($newn,'.')) {
					// rewrite number string by splicing in the required # of zeros before the 'e'
					$newn=str_replace('e','.e',$newn);
					$news.=".";
				}
			}

			// update new number ($n) and significand ($s) if needed
			$newn=str_replace('e',str_pad('e',$diff+1,'0',STR_PAD_LEFT),$newn);
			$news.=str_pad('',$diff,'0',STR_PAD_LEFT);

			// update entry in the data table
			$this->Data->id=$row['id'];
			$this->Data->saveField('number',$newn);
			$this->Data->saveField('significand',$news);
			$this->Data->saveField('issue',null);
			$this->Data->saveField('comments',$row['comments'].'; corrected sigfigs SJC');
			echo "Updated row '".$row['id']."'<br/>";//exit;
		}
		exit;
	}

	/**
	 * reprocess XML files and add values, errors and accuracy data again
	 * @param int $max
	 * @return void
	 */
	public function chkvalerracc(int $max=1)
	{
		// fields for data in DB - errors -> trcerr, accuracy -> trcacc, values -> trctxt and check -> trcchk
		$path = WWW_ROOT.'files'.DS.'trc'.DS.'jced'.DS;
		$maindir = new Folder($path);
		$files = $maindir->find("^.+\.xml$",true);
		//debug($files);exit;

		$count=0;
		$done = $this->File->find('list', ['fields' => ['id','filename'],'conditions'=>['trcchk'=>'yes']]);
		$fids = $this->File->find('list', ['fields' => ['id','filename']]);
		foreach ($files as $filename) {
			// already done? echo "File '".$filename."' already completed<br/>";
			if (in_array($filename, $done)) { continue; }
			//debug($filename);exit;
			$count++;

			// load file
			$filepath = $path . $filename;
			$xml = simplexml_load_file($filepath);
			$trc = json_decode(json_encode($xml), true);

			// check for data
			if (!isset($trc['PureOrMixtureData'])) { echo "No data in '" . $filename . "'<br/>";continue; }

			// find reference
			$doi = $trc['Citation']['sDOI'];
			$ref = $this->Reference->find('list',['fields'=>['doi','id'],'conditions'=>['doi'=>$doi]]);

			$sets = $trc['PureOrMixtureData'];
			if (!isset($sets[0])) { $sets = [0 => $sets]; }
			foreach($sets as $set) {
				// normalize set data (make arrays when XML -> PHP array conversion has not)
				if(!isset($set['Component'][0])) { $set['Component']=[0=>$set['Component']]; }
				if(!empty($set['Variable'])) {
					if(!isset($set['Variable'][0])) { $set['Variable']=[0=>$set['Variable']]; }
				}
				if(!isset($set['Property'][0])) { $set['Property']=[0=>$set['Property']]; }
				$setnum=$set['nPureOrMixtureDataNumber'];

				// get dataset
				$dset=$this->Dataset->find('first',['conditions'=>['setnum'=>$setnum,'reference_id'=>$ref[$doi]],'recursive'=>-1]);
				if($dset['Dataset']['trcchk']=='yes') { echo "Dataset '".$dset['Dataset']['id']."' already updated<br/>";continue; }
				$setid=$dset['Dataset']['id'];

				// get dataseries
				// $sers=$this->Dataseries->find('all',['conditions'=>['dataset_id'=>$setid],'recursive'=>-1]);

				// get datapoints
				$pdone=$this->Datapoint->find('list',['fields'=>['id'],'conditions'=>['dataset_id'=>$setid,'trcchk'=>'yes']]);
				// $pnts=$this->Datapoint->find('list',['fields'=>['row_index','id'],'conditions'=>['dataset_id'=>$setid]]);

				// get series conditions
				$sconds=$this->Condition->find('all',['conditions'=>['dataset_id'=>$setid,'datapoint_id'=>null],'contain'=>['Compohnent','Phase'=>['Phasetype']],'recursive'=>-1]);

				// get conditions
				$conds=$this->Condition->find('all',['conditions'=>['dataset_id'=>$setid,'dataseries_id'=>null],'contain'=>['Compohnent','Phase'=>['Phasetype']],'recursive'=>-1]);

				// get data
				$data=$this->Data->find('all',['conditions'=>['dataset_id'=>$setid],'contain'=>['Compohnent','Phase'=>['Phasetype']],'recursive'=>-1]);

				// get components, condition quantities (Variables) and data quantities (Properties)
				$vars=[];$prps=[];$cmps=[];
				// in ThermoML Components are the 'constituents' of the chemical system under stufy
				foreach($set['Component'] as $cidx=>$cmp) { $cmps[$cidx+1]=$cmp['RegNum']['nOrgNum']; }
				// in ThermoML Variables are 'conditions' and Properties are 'data'
				// if present add the component # to the end of the conditions|data to distinguish them
				if(!empty($set['Variable'])) {
					foreach($set['Variable'] as $var) {
						$tmp=$var['VariableID']['VariableType'];
						$q=array_values($tmp);
						// component?
						$cmp="";$phs="";
						if(!empty($var['VariableID']['RegNum'])) {
							$cmp='|'.array_search($var['VariableID']['RegNum']['nOrgNum'],$cmps);
						}
						if(!empty($var['VarPhaseID']['eVarPhase'])) {
							$phs='|'.$var['VarPhaseID']['eVarPhase'];
						}
						$vars[$var['nVarNumber']]=$q[0].$cmp.$phs;
					}
				}
				foreach($set['Property'] as $prp) {
					$tmp=$prp['Property-MethodID']['PropertyGroup'];
					$tmp2=array_values($tmp);
					$tmp3=array_values($tmp2[0]);
					// component?
					$cmp="";$phs="";
					if(!empty($prp['Property-MethodID']['RegNum'])) {
						$cmp='|'.array_search($prp['Property-MethodID']['RegNum']['nOrgNum'],$cmps);
						$prps[$prp['nPropNumber']]=$tmp3[0].'|'.array_search($prp['Property-MethodID']['RegNum']['nOrgNum'],$cmps);
					}
					if(!empty($prp['PropPhaseID']['ePropPhase'])) {
						$phs='|'.$prp['PropPhaseID']['ePropPhase'];
					}
					$prps[$prp['nPropNumber']]=$tmp3[0].$cmp.$phs;
				}
				//debug($setid);debug($cmps);debug($vars);debug($prps);debug($sconds);exit;

				// check for series conditions
				$scqs=[];$uscqs=[];
				if(!empty($sconds)) {
					if(!empty($set['Constraint'])) {
						// some series conditions may come from regular conditions (ThermoML Variables)
						//echo "series conditions from XML variables and/or constraints<br/>";
						if(!isset($set['Constraint'][0])) { $set['Constraint']=[0=>$set['Constraint']]; }
						foreach($set['Constraint'] as $con) {
							//debug($con);debug($cmps);exit;
							$type=$con['ConstraintID']['ConstraintType'];$cmp=null;$phs=null;
							if(!empty($con['ConstraintID']['RegNum']['nOrgNum'])) { $cmp=array_search($con['ConstraintID']['RegNum']['nOrgNum'],$cmps); }
							if(!empty($con['ConstraintPhaseID']['eConstraintPhase'])) { $phs=$con['ConstraintPhaseID']['eConstraintPhase']; }
							sort($type); // resets key to zero
							foreach($sconds as $scid=>$scond) {
								$scmp=$scond['Compohnent'];$sphs=$scond['Phase']['Phasetype'];$scond=$scond['Condition'];$comp=null;$phase=null;
								if(!is_null($scmp['compnum'])) { $comp=$scmp['compnum']; }
								if(!is_null($sphs['name'])) { $phase=$sphs['name']; }
								//debug($type);debug($scond);debug($cmp);debug($comp);debug($phs);debug($phase);exit;
								if($type[0]==$scond['quantity_name']&&$cmp==$comp&&$phs==$phase) {
									if($scond['trcchk']=='yes') {
										//echo "Series condition (constraint) '".$scond['id']."' already updated<br/>";
										unset($sconds[$scid]);continue;
									}
									$save=['id'=>$scond['id'],'trcacc'=>$con['nConstrDigits'],'trctxt'=>$con['nConstraintValue'],'trcchk'=>'yes'];
									//debug($scond);debug($save);exit;
									$this->Condition->save($save);
									//echo "Series condition (constraint) '".$scond['id']."' updated<br/>";
									unset($sconds[$scid]); // remove from sconds array so any remainder (from conditions) can be processed below
								}
							}
						}
					}
					//debug($sconds);exit;
					if(!empty($sconds)) {
						//echo "series conditions from XML variables!<br/>";
						// get the quantity(ies) from the series conditions
						foreach($sconds as $scond) {
							$scmp=$scond['Compohnent'];$sphs=$scond['Phase']['Phasetype'];$scond=$scond['Condition'];$comp=null;$phase=null;
							$prop=$scond['quantity_name'];
							if(!is_null($scmp['compnum'])) { $comp='|'.$scmp['compnum']; }
							if(!is_null($sphs['name'])) { $phase='|'.$sphs['name']; }
							$scqs[]=$prop.$comp.$phase;
						}
						$uscqs=array_unique($scqs);

						//debug($uscqs);debug($sconds);debug($uscqs);debug($vars);debug($set['NumValues']);exit;
						// get the condition from each data point (NumValue) and get unique values
						foreach($uscqs as $uscq) {
							// use quantity name to get the nVarNumber to then search the NumValues
							$varnum=array_search($uscq,$vars);
							// get unique values for the quantity in $uscq
							$vals=[];
							//debug($set['NumValues']);
							foreach($set['NumValues'] as $nval) {
								$cndval=$nval['VariableValue'][($varnum-1)]['nVarValue'];
								$cnddig=$nval['VariableValue'][($varnum-1)]['nVarDigits'];
								$vals[]=$cndval.'::'.$cnddig;
							}
							$uvals=array_values(array_unique($vals));
							//debug($uvals);debug($sconds);exit;
							if(count($uvals)==count($sconds)) {
								foreach($uvals as $uval) {
									list($value,$digs)=explode('::',$uval);
									foreach($sconds as $scond) {
										$scond=$scond['Condition'];
										if($scond['text']==$value) {
											if($scond['trcchk']=='yes') {
												//echo "Series condition (variable) '".$scond['id']."' already updated<br/>";
												continue 2;
											}
											$save=['id'=>$scond['id'],'trcacc'=>$digs,'trctxt'=>$value,'trcchk'=>'yes'];
											//debug($scond);debug($save);exit;
											$this->Condition->save($save);
											//echo "Series condition (variable) '".$scond['id']."' updated<br/>";
											continue 2;
										}
									}
								}
							} else {
								echo "mismatch in number of unique series condition values!";
								sort($uvals);debug($uvals);debug($sconds);exit;
							}
						}
						//debug($setid);debug($vars);debug($prps);debug($sconds);debug($set['NumValues']);exit;
					}
				} elseif(!empty($set['Constraint'])) {
					echo "series condition(s) not captured!";debug($set['Constraint']);exit;
				}
				//debug($uscqs);debug($sconds);debug($vars);exit;

				// organize conditions data for proccessing NumValues foreach loop
				$pntlist=[];$upntlist=[];
				// add all remaining sconds (variable data turned into sconds) to complete unique upntlist strings
				// (removed in 'update condition' code below)
				foreach($sconds as $scond) {
					$cmp=$scond['Compohnent'];$phs=$scond['Phase']['Phasetype'];$scond=$scond['Condition'];
					$prop=$scond['quantity_name'];$comp='';$phase='';$value=$scond['text'];
					if(!is_null($cmp['compnum'])) { $comp='|'.$cmp['compnum']; }
					if(!is_null($phs['name'])) { $phase='|'.$phs['name']; }
					$qcpvstr=$prop.$comp.$phase.'::'.$value;
					// get all datapoints of this series and add this condition to them (recreate XML file data)
					$serpnts=$this->Datapoint->find('list',['conditions'=>['dataseries_id'=>$scond['dataseries_id']]]);
					foreach($serpnts as $serpnt) { $pntlist[$serpnt]['c'][$scond['id']]=$qcpvstr; }
				}
				foreach($conds as $cond) {
					$cmp=$cond['Compohnent'];$phs=$cond['Phase']['Phasetype'];$cond=$cond['Condition'];$dpid=$cond['datapoint_id'];
					$prop=$cond['quantity_name'];$comp='';$phase='';$value=$cond['text'];
					if(!is_null($cmp['compnum'])) { $comp='|'.$cmp['compnum']; }
					if(!is_null($phs['name'])) { $phase='|'.$phs['name']; }
					$pntlist[$dpid]['c'][$cond['id']]=$prop.$comp.$phase.'::'.$value;
				}
				//debug($pntlist);exit;
				// organize expt data for proccessing NumValues foreach loop
				foreach($data as $datum) {
					$cmp=$datum['Compohnent'];$phs=$datum['Phase']['Phasetype'];$datum=$datum['Data'];$dpid=$datum['datapoint_id'];
					$prop=$datum['quantity_name'];$comp='';$phase='';$value=$datum['text'];
					if(!is_null($cmp['compnum'])) { $comp='|'.$cmp['compnum']; }
					if(!is_null($phs['name'])) { $phase='|'.$phs['name']; }
					$pntlist[$dpid]['d'][$datum['id']]=$prop.$comp.$phase.'::'.$value;
				}
				// create unique pntlist for matching
				foreach($pntlist as $dpid=>$cd) {
					$cs=[];$ds=[];
					if(isset($cd['c'])) { $cs=$cd['c']; }  // not every datapoint has conditions...
					if(isset($cd['d'])) { $ds=$cd['d']; }  // every datapoint should have data...
					asort($cs); // asort used to keep condition ids as keys
					asort($ds); // asort used to keep data ids as keys
					$upntlist[$dpid]=implode(';',$cs).';'.implode(';',$ds);
				}
				//debug($upntlist);exit;

				// process conditions and data
				if(!isset($set['NumValues'][0])) { $set['NumValues']=[0=>$set['NumValues']]; }
				foreach($set['NumValues'] as $nidx=>$pnt) {
					// normalize point data (make arrays when XML -> PHP array conversion has not)
					if(isset($pnt['VariableValue'])) {
						if(!isset($pnt['VariableValue'][0])) { $pnt['VariableValue']=[0=>$pnt['VariableValue']]; }
					} else {
						$pnt['VariableValue']=[];
					}
					if(isset($pnt['PropertyValue'])) {
						if(!isset($pnt['PropertyValue'][0])) { $pnt['PropertyValue']=[0=>$pnt['PropertyValue']]; }
					} else {
						echo "no property in datapoint!";debug($pnt);exit;
					}

					// organize condition data
					$cvals=[];$pvals=[];
					foreach($pnt['VariableValue'] as $val) {
						$var=$vars[$val['nVarNumber']];
						$cvals[$var]=[];
						$cvals[$var]['quantity']=$vars[$val['nVarNumber']];
						$cvals[$var]['value']=$val['nVarValue'];
						$cvals[$var]['digits']=$val['nVarDigits'];
					}
					foreach($pnt['PropertyValue'] as $pval) {
						$prp=$prps[$pval['nPropNumber']];
						$pvals[$prp]=[];
						$pvals[$prp]['quantity']=$prps[$pval['nPropNumber']];
						$pvals[$prp]['value']=$pval['nPropValue'];
						$pvals[$prp]['digits']=$pval['nPropDigits'];
						if(!empty($pval['CombinedUncertainty'])) {
							$tmp=array_values($pval['CombinedUncertainty']);
							$pvals[$prp]['error']=$tmp[1];
						} else {
							$pvals[$prp]['error']=null;
						}
					}
					//debug($uscqs);debug($cvals);debug($pvals);exit;

					// remove conditions that were series conditions and make qvstr
					$cqvs=[];$dqvs=[];
					foreach($cvals as $val) {
						//if(in_array($val['quantity'],$uscqs)) { unset($cvals[$vidx]);continue; }
						$cqvs[]=$val['quantity'].'::'.$val['value'];
					}
					foreach($pvals as $pval) {
						$dqvs[]=$pval['quantity'].'::'.$pval['value'];
					}
					sort($cqvs);sort($dqvs);
					$qvstr=implode(";",$cqvs).';'.implode(";",$dqvs);
					//debug($qvstr);debug($upntlist);exit;

					// match datapoint (all condition values) in clist
					if(!in_array($qvstr,$upntlist)) {
						echo "can't find matching datapoint!";
						debug($qvstr);debug($upntlist);exit;
					}
					if(count(array_keys($upntlist,$qvstr))>1) {
						echo "Multiple points with same qvstr found<br/>";
						$dupes=array_keys($upntlist,$qvstr);
						$dupecs=$this->Condition->find('list',['fields'=>['id','comments'],'conditions'=>['datapoint_id'=>$dupes]]);
						foreach($dupecs as $cid=>$cmt) {
							if(stristr($cmt,'duplicate')) { continue; }
							if(is_null($cmt)) {
								$cmt='';
							} else {
								$cmt.=';';
							}
							$this->Condition->save(['Condition'=>['id'=>$cid,'comments'=>$cmt.'duplicate datapoint SJC']]);
						}
						$dupeds=$this->Data->find('list',['fields'=>['id','comments'],'conditions'=>['datapoint_id'=>$dupes]]);
						foreach($dupeds as $did=>$cmt) {
							if(stristr($cmt,'duplicate')) { continue; }
							if(is_null($cmt)) {
								$cmt='';
							} else {
								$cmt.=';';
							}
							$this->Data->save(['Data'=>['id'=>$did,'comments'=>$cmt.'duplicate datapoint SJC']]);
						}
					}
					$dpid=array_search($qvstr,$upntlist);
					if(in_array($dpid,$pdone)) { echo "Datapoint '".$dpid."' already updated<br/>";continue; }
					//debug($uscqs);debug($pntlist[$dpid]);exit;

					// update conditions
					if(isset($pntlist[$dpid]['c'])) {
						foreach($pntlist[$dpid]['c'] as $cid=>$valstr) {
							//debug($cid);debug($valstr);exit;
							list($q,)=explode("::",$valstr);
							if(in_array($q,$uscqs)) { continue; }  // don't process any series condition quantities
							$cond=$cvals[$q];
							$save=['id'=>$cid,'trcacc'=>$cond['digits'],'trctxt'=>$cond['value'],'trcchk'=>'yes'];
							//debug($pnt);debug($save);exit;
							$this->Condition->save($save);
							//echo "Condition '".$cid."' updated<br/>";
						}
					}

					// update data
					foreach($pntlist[$dpid]['d'] as $did=>$valstr) {
						list($q,)=explode("::",$valstr);
						$datum=$pvals[$q];
						$save=['id'=>$did,'trcacc'=>$datum['digits'],'trctxt'=>$datum['value'],'trcerr'=>$datum['error'],'trcchk'=>'yes'];
						//debug($pnt);debug($save);exit;
						$this->Data->save($save);
						//echo "Data '".$did."' updated<br/>";
					}

					// mark datapoint as done (and add trcidx)
					$this->Datapoint->save(['id'=>$dpid,'trcidx'=>($nidx+1),'trcchk'=>'yes']);
					//echo "Datapoint '".$dpid."' complete<br/>";exit;
				}

				// mark dataset as done
				$this->Dataset->save(['id'=>$setid,'trcchk'=>'yes']);
				//echo "Dataset '".$setid."' complete<br/>";exit;
			}

			// mark file as done
			$fid=array_search($filename,$fids);
			//debug($fid);exit;
			$this->File->save(['id'=>$fid,'trcchk'=>'yes']);
			echo "File '".$fid."' complete<br/>";
			if($count==$max) { exit; }
		}
		exit;
	}

	/**
	 * update duplicate condition data identified by chkvalerracc script (19236 entries)
	 * (some conditions in a dataset are identical (quantity|component|phase|value) and the code could not update info)
	 * these are regular conditions (not series conditions) and are duplicate within a dataset (i.e., across dataseries)
	 * [after starting updates decided to normalize accuracy for 'fraction' values of 1 and 0 to make this processing easier]
	 * run once
	 * @param int $max
	 * @return void
	 */
	public function fixmissingcvea(int $max=1)
	{
		// conditions organized by dataset
		$count=0;$f=['id','txtaccchk','dataset_id'];$c=['comments like'=>'%duplicate%'];
		$sets=$this->Condition->find('list',['fields'=>$f,'conditions'=>$c,'recursive'=>-1]);
		foreach($sets as $setid=>$conds) {

			// check for already done
			$chkd='yes';
			foreach($conds as $cond) { if(stristr($cond,'no')) { $chkd='no'; } }
			if($chkd=="yes") { echo "conditions for set '".$setid."' already updated<br/>";continue; }
			$count++;

			// organize data
			$done=[];$not=[];$allcids=[];
			foreach($conds as $cid=>$txtaccchk) {
				list($text,$acc,$chk)=explode(":",$txtaccchk);
				$txtacc=$text.':'.$acc;
				if($chk=="yes") {
					$done[$cid]=$txtacc;
					$allcids[$txtacc][]=$cid;
				} else {
					$not[$text][]=$cid;
				}
			}
			$udone=array_unique($done);

			// update conditions
			foreach($udone as $txtacc) {
				list($text,$acc)=explode(":",$txtacc);
				foreach($not[$text] as $cid) {
					$save=['id'=>$cid,'trctxt'=>$text,'trcacc'=>$acc,'trcchk'=>'yes'];
					$this->Condition->save($save);
					$allcids[$txtacc][]=$cid;
				}
			}

			// check that all conditions that should not be the same are the same
			foreach($allcids as $cids) {
				$tacs=$this->Condition->find('list',['fields'=>['id','txtaccchk'],'conditions'=>['id'=>$cids],'recursive'=>-1]);
				if(count(array_unique($tacs))==1) {
					echo "conditions successfully updated<br/>";
				} else {
					echo "conditions not the same!";debug($setid);debug($tacs);exit;
				}
			}

			// stop loop per max count
			if($count==$max) { 	debug($setid);exit; }
		}
	}

	/**
	 * update duplicate experimental data identified by chkvalerracc script (8971 entries)
	 * (some data in a dataset are identical (quantity|component|phase|value) and the code could not update info)
	 * these are duplicate within a dataset
	 * run once
	 * @param int $max
	 * @return void
	 */
	public function fixmissingdvea(int $max=1)
	{
		// data organized by dataset
		$count=0;$f=['id','txterraccchk','dataset_id'];$c=['comments like'=>'%duplicate%'];
		$sets=$this->Data->find('list',['fields'=>$f,'conditions'=>$c,'recursive'=>-1]);
		foreach($sets as $setid=>$datums) {

			// check for already done
			$chkd='yes';
			foreach($datums as $datum) { if(stristr($datum,'no')) { $chkd='no'; } }
			if($chkd=="yes") { echo "data for set '".$setid."' already updated<br/>";continue; }
			$count++;

			// organize data
			$done=[];$not=[];$allcids=[];
			foreach($datums as $cid=>$txterraccchk) {
				list($text,$err,$acc,$chk)=explode(":",$txterraccchk);
				$txterracc=$text.':'.$err.':'.$acc;
				if($chk=="yes") {
					$done[$cid]=$txterracc;
					$allcids[$txterracc][]=$cid;
				} else {
					$not[$text][]=$cid;
				}
			}
			$udone=array_unique($done);

			// update data
			foreach($udone as $txterracc) {
				list($text,$err,$acc)=explode(":",$txterracc);
				$txtacc=$text.':'.$acc;
				foreach($not[$text] as $cid) {
					$save=['id'=>$cid,'trcerr'=>$err,'trctxt'=>$text,'trcacc'=>$acc,'trcchk'=>'yes'];
					//debug($save);exit;
					$this->Data->save($save);
					$allcids[$txtacc][]=$cid;
				}
			}

			// check that all data that should not be the same are
			foreach($allcids as $cids) {
				$tacs=$this->Data->find('list',['fields'=>['id','txterraccchk'],'conditions'=>['id'=>$cids],'recursive'=>-1]);
				if(count(array_unique($tacs))==1) {
					echo "conditions successfully updated<br/>";
				} else {
					echo "conditions not the same!";debug($setid);debug($tacs);exit;
				}
			}

			// stop loop per max count
			if($count==$max) { 	debug($setid);exit; }
		}
	}

	/**
	 * check component consistency
	 * the linked components to a condition or data row all need to be from the mixture that is defined for that dataset
	 * @return void
	 */
	public function chkcompcon()
	{
		$sets=$this->Condition->find('list',['fields'=>['id','component_id','dataset_id'],'conditions'=>['NOT'=>['component_id'=>null]],'order'=>'dataset_id']);
		$cmps=$this->Compohnent->find('list',['fields'=>['Compohnent.compnum','Compohnent.id','Mixture.dataset_id'],'contain'=>['Mixture']]);
		foreach($sets as $setid=>$set) {
			if(array_diff($set,$cmps[$setid])) {
				echo "dataset '".$setid."' inconsistent: ";debug($set);debug($cmps[$setid]);exit;
			} else {
				echo "dataset '".$setid."' OK<br/>";
			}
		}
		exit;
	}

	/**
	 * check that the condition values in number and significand are correctly quoted per the accuracy
	 * (errors all set to null as TRC XML data does not have errors associated with conditions (only data))
	 * run once
	 * @param int $acc
	 */
	public function chkconacc(int $acc=0)
	{
		$issues = $this->Condition->find('all',['conditions'=>['issue'=>'sigfigs','accuracy'=>$acc],'order'=>['exponent','text'],'limit'=>10,'recursive'=>-1]);
		foreach($issues as $issue) {  // 'NOT'=>['text like'=>'%.%'],
			$row=$issue['Condition'];
			$n=$row['number'];$s=$row['significand'];$a=$row['accuracy'];$t=$row['text'];
			// check text for # non-zero digits and compare to accuracy: if greater then continue
			if(stristr($t,'e')) { list($t,)=explode('e',$t); }
			$t=str_replace('.','',$t);
			$t=trim($t, "0");
			$tlen=strlen($t);
			if($t[0]=='-') { $tlen--; } // -1 for negative number
			if($tlen>$a) { echo "value more SF than accuracy... ".$row['text']."(".$a.")<br/>";continue; }
			// process value
			$slen=strlen($s);
			if(stristr($s,'.')) { $slen--; } // -1 for decimal point
			if($s[0]=='-') { $slen--; } // -1 for negative number
			$diff=$a-$slen;
			$newn=$n;$news=$s;
			if(!stristr($newn,'.')) {
				// rewrite number string by splicing in the required # of zeros before the 'e'
				$newn=str_replace('e','.e',$newn);
				$news.=".";
			}
			// based on the difference between values updata the $newn and $news variables
			if($diff>0) {
				$newn=str_replace('e',str_pad('e',$diff+1,'0',STR_PAD_LEFT),$newn);
				$news.=str_pad('',$diff,'0',STR_PAD_LEFT);
			} elseif($diff<0) {
				$newn=str_replace(str_pad('e',abs($diff)+1,'0',STR_PAD_LEFT),'e',$newn);
				$news=substr($news,0,$diff);
			}
			// update condition data in conditions table
			$this->Condition->id=$row['id'];
			$this->Condition->saveField('number',$newn);
			$this->Condition->saveField('significand',$news);
			$this->Condition->saveField('issue',null);
			$this->Condition->saveField('comments',$row['comments'].';updated number/significand SJC');
			echo "Updated condition row '".$row['id']."' (".$row['text'].")<br/>";//exit;
		}
		exit;
	}

	/**
	 * correct the representation of dataseries condition values where the number field is not in scientific notation
	 * (number field does not contain 'e')
	 * @return void
	 */
	public function fixscinot()
	{
		// all values are good with sig figs so value just needs to get converted to scientific notation
		$f=['id','number','text'];$c=['NOT'=>['number like'=>'%e%']];
		$vals=$this->Condition->find('list',['fields'=>$f,'conditions'=>$c,'order'=>'text','recursive'=>-1]);
		foreach($vals as $val=>$rows) {
			$scinot=$this->Dataset->exponentialGen($val);
			$cids=array_keys($rows);
			foreach($cids as $cid) {
				$save=['id'=>$cid,'number'=>$scinot['scinot'],'significand'=>$scinot['significand']];
				$this->Condition->save($save);
				echo "Condition '".$cid."' updated<br/>";
			}
		}
		exit;
	}

	/**
	 * add missing uncertainty information for sampleprops (XML: <PureOrMixtureData><Property>)
	 * run once
	 * @return void
	 */
	public function fixunc()
	{
		$jids=['ijt','tca','fpe','jct','jced'];
		foreach($jids as $jid) {
			// get files for a journal
			$path = WWW_ROOT.'files'.DS.'trc'.DS.$jid.DS;
			$maindir = new Folder($path);
			$files = $maindir->find("^.+\.xml$", true);

			// preload variables with data needed to minimize calls to DB
			$done = $this->Sampleprop->find('list', ['fields' => ['id', 'propcode'], 'conditions' => ['uncchk' => 'yes']]);
			$sprps = $this->Sampleprop->find('list', ['fields' => ['propcode', 'id']]);
			$fids = $this->File->find('list', ['fields' => ['id', 'filename']]);
			$sids = $this->Dataset->find('list', ['fields' => ['id', 'fileset']]);

			// process each file
			foreach ($files as $filename) {
				// load file
				$filepath = $path . $filename;
				$xml = simplexml_load_file($filepath);
				$trc = json_decode(json_encode($xml), true);
				$fid = array_search($filename, $fids);

				// ensure datasets are an array (if only one present XML->json conversion above does not create array of that one)
				if (!isset($trc['PureOrMixtureData'][0])) {
					$trc['PureOrMixtureData'] = [0 => $trc['PureOrMixtureData']];
				}

				// process each dataset (PureOrMixtureData section in XML)
				foreach ($trc['PureOrMixtureData'] as $set) {
					$setnum = $set['nPureOrMixtureDataNumber'];
					$sid = array_search($fid . ':' . $setnum, $sids);

					// ensure properties are an array (if only one present XML->json conversion above does not create array of that one)
					if (!isset($set['Property'][0])) {
						$set['Property'] = [0 => $set['Property']];
					}

					// process each property (# properties varies from 1 to 6 across all files)
					foreach ($set['Property'] as $prop) {
						$propcode = $sid . ':' . $prop['nPropNumber'];

						// has property has been checked already?
						if (!in_array($propcode, $done)) {
							$spid = $sprps[$propcode];

							// check if property has uncertainty section
							if (isset($prop['CombinedUncertainty'])) {
								// add uncertainty data
								$unc = $prop['CombinedUncertainty'];
								$data = ['id' => $spid, 'uncnum' => $unc['nCombUncertAssessNum'], 'unceval' => $unc['eCombUncertEvalMethod'], 'uncconf' => $unc['nCombUncertLevOfConfid'], 'uncchk' => 'yes'];
							} else {
								// add that data has been checked
								$data = ['id' => $spid, 'uncchk' => 'yes'];
							}
							// update sampleprops DB table
							$this->Sampleprop->save($data);
						}
					}
				}
				echo "File '" . $filename . "' done<br/>";
			}
		}
		exit;
	}

	/**
	 * check that the phase(s) (and if present nOrgNum) are correct for all datasets (XML: <PureOrMixtureData><PhaseID>)
	 * run once
	 * @return void
	 */
	public function chkphaseorgnum()
	{
		// this code was run on the 'trcv2' DB when the code had been rewritten for the 'trcv2_clean' DB
		// the next four lines show how to change DB to the 'trcv2' DB config (in Config/database.php)
		$this->Phase->setDataSource('full');
		$this->Phasetype->setDataSource('full');
		$this->File->setDataSource('full');
		$this->Dataset->setDataSource('full');
		$jids=['ijt','tca','fpe','jct','jced'];
		foreach($jids as $jid) {
			// get a list of files in the folder of a particular journal
			// (abbrevs in the $jids variable from 'journals table)
			$path = WWW_ROOT.'files'.DS.'trc'.DS.$jid.DS;
			$maindir = new Folder($path);
			$files = $maindir->find("^.+\.xml$",true);

			// get a list of all files already completed and filenames to all easy retrieval of file ids from filename
			$done = $this->File->find('list', ['fields' => ['id'],'conditions'=>['phschk'=>'yes']]);
			$fids = $this->File->find('list', ['fields' => ['id','filename']]);
			foreach ($files as $filename) {
				// load file
				$filepath = $path.$filename;
				$xml = simplexml_load_file($filepath);
				$trc = json_decode(json_encode($xml), true);
				$fid = array_search($filename, $fids);

				// check if completed already
				if(in_array($fid,$done)) { echo "File '".$fid."' already complete<br/>";continue; }

				// ensure datasets are an array (if only one present XML->json conversion above does not create array of that one)
				if (!isset($trc['PureOrMixtureData'][0])) { $trc['PureOrMixtureData'] = [0 => $trc['PureOrMixtureData']]; }

				foreach ($trc['PureOrMixtureData'] as $set) {
					$setnum = $set['nPureOrMixtureDataNumber'];
					// db content
					$c=['Mixture' => ['Phase' => ['Phasetype'],'Compohnent']];
					$dbset=$this->Dataset->find('first',['conditions'=>['file_id'=>$fid,'setnum'=>$setnum],'contain'=>$c,'recursive'=>-1]);
					// file content
					if (!isset($set['PhaseID'][0])) { $set['PhaseID'] = [0 => $set['PhaseID']]; }
					$phases=$set['PhaseID'];
					$dbphss=$dbset['Mixture']['Phase'];
					$orgnumset=0;
					foreach($phases as $phase) {
						if(isset($phase['RegNum'])) { $orgnumset=1; }
						if($orgnumset==1) { break; }
					}
					if($orgnumset==0) {
						echo "No nOrgNums for file '".$fid."' dataset '".$setnum."'<br/>";
						// check for correct # of phases
						if(count($phases)==count($dbphss)) {
							foreach($phases as $pidx=>$phase) {
								if($phase['ePhase']!=$dbphss[$pidx]['Phasetype']['name']) {
									echo "Phase mismatch...<br/>";debug($dbset);debug($phases);exit;
								}
							}
							echo "Phases verified...<br/>";
						} else {
							echo "Phase count wrong...<br/>";debug($dbset);debug($phases);exit;
						}
						continue;
					}
					// nOrgNum is set for at least one component
					// entries align between the db and the file as they were added based on the file order
					// check counts are correct
					if(count($phases)>count($dbphss)) {
						echo "Phase counts not the same..."; //debug($dbset);debug($phases);
						// realign existing phase table entries ... and add new ones
						$mixid=$dbset['Mixture']['id'];
						foreach($phases as $pidx=>$phase) {
							$pname=$phase['ePhase'];
							$ptype=$this->Phasetype->find('list',['fields'=>['name','id'],'conditions'=>['name'=>$pname],'recursive'=>-1]);
							debug($ptype);
							if(isset($phase['RegNum'])) { $orgnum=$phase['RegNum']['nOrgNum']; } else { $orgnum=null; }
							if(isset($dbphss[$pidx])) {
								$phsid=$dbphss[$pidx]['id'];
								$data=['id'=>$phsid,'phasetype_id'=>$ptype[$pname],'orgnum'=>$orgnum];
								$this->Phase->save($data);
								echo "Phase '".$phsid."' updated...<br/>";
							} else {
								// add new entry in phases
								$data=['mixture_id'=>$mixid,'phasetype_id'=>$ptype[$pname],'orgnum'=>$orgnum];
								$this->Phase->create();
								$this->Phase->save(['Phase'=>$data]);
								$phsid=$this->Phase->id;
								echo "Phase '".$phsid."' added...<br/>";
							}
						}
					} elseif(count($phases)<count($dbphss)) {
						echo "Phase counts in file less than DB :( ...";debug($dbset);debug($phases);exit;
					} else {
						foreach($phases as $pidx=>$phase) {
							if(!isset($phase['RegNum'])) { continue; }
							// check 'ePhase' against Phasetype['name']
							if($phase['ePhase']==$dbphss[$pidx]['Phasetype']['name']) {
								// found match
								if(is_null($dbphss[$pidx]['orgnum'])) {
									$this->Phase->save(['id'=>$dbphss[$pidx]['id'],'orgnum'=>$phase['RegNum']['nOrgNum']]);
									echo "Phase ".$dbphss[$pidx]['id']." updated...<br/>";
								} else {
									if($phase['RegNum']['nOrgNum']==$dbphss[$pidx]['orgnum']) {
										echo "Phase and orgnum ".$dbphss[$pidx]['id']." verified...<br/>";
									} else {
										echo "Orgnums mismatched...<br/>";debug($phase['RegNum']['nOrgNum']);debug($dbphss[$pidx]['orgnum']);exit;
									}
								}
							} else {
								echo "Phase info not aligned...";debug($dbset);debug($phases);exit;
							}
						}
					}
				}
				$this->File->save(['id'=>$fid,'phschk'=>'yes']);
				echo "File '".$fid."' complete<br/>";
			}
		}
		exit;
	}
}
