<?php

/**
 * Class QuantitiesController
 * Actions related to dealing with quantities
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class QuantitiesController extends AppController
{
	public $uses=['Condition','Data','Dataset','Quantity','Sampleprop','System'];

    /**
     * function beforeFilter
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('index','view');
    }

    /**
     * list the quantities
	 * @return void
	 */
    public function index()
    {
		$f=['id','name','first'];
		$c=['OR'=>['condcnt >'=>0,'datacnt >'=>0]];
        $data=$this->Quantity->find('list',['fields'=>$f,'conditions'=>$c,'order'=>['name'],'recursive'=>-1]);
        $this->set('data',$data);
    }

    /**
     * view a quantity
     * @param $id
	 * @return void
     */
    public function view($id)
    {
		$c=['Unit','Quantitykind'];
        $data=$this->Quantity->find('first',['conditions'=>['Quantity.id'=>$id],'contain'=>$c,'recursive'=>-1]);
		$data['counts']['ccount']=$this->Condition->find('count',['conditions'=>['quantity_id'=>$id],'recursive'=>-1]);
		$data['counts']['dcount']=$this->Data->find('count',['conditions'=>['quantity_id'=>$id],'recursive'=>-1]);
		$setids=array_unique($this->Sampleprop->find('list',['fields'=>['dataset_id'],'conditions'=>['quantity_id'=>$id],'recursive'=>-1]));
		$sysids=array_unique($this->Dataset->find('list',['fields'=>['system_id'],'conditions'=>['id'=>$setids],'recursive'=>-1]));
		$syss=array_unique($this->System->find('list',['fields'=>['id','name'],'conditions'=>['id'=>$sysids],'order'=>'name','recursive'=>-1]));
		$this->set('data',$data);
		$this->set('syss',$syss);
	}

	// admin functions

	/**
	 * update the counts of a quantity used as a condition or data
	 * @return void
	 */
	public function updcnts()
	{
		$qs = $this->Quantity->find('list',['fields'=>['id'],'order'=>'id']);
		$temp = $this->Condition->query('SELECT quantity_id,count(*) FROM conditions group by quantity_id');
		$ccnts = [];
		foreach($temp as $q) { $ccnts[$q['conditions']['quantity_id']] = $q[0]['count(*)'];}
		$temp = $this->Data->query('SELECT quantity_id,count(*) FROM `data` group by quantity_id');
		$dcnts = [];
		foreach($temp as $q) { $dcnts[$q['data']['quantity_id']] = $q[0]['count(*)'];}
		//debug($dcnts);exit;
		foreach($qs as $qid) {
			$save=['id'=>$qid,'condcnt'=>0,'datacnt'=>0];
			if(isset($ccnts[$qid])) { $save['condcnt']=$ccnts[$qid]; }
			if(isset($dcnts[$qid])) { $save['datacnt']=$dcnts[$qid]; }
			$this->Quantity->save($save);
		}
		exit;
	}

	// special functions

	/**
	 * code to process files where there are multiple conditions of the same quantity and also the same component
	 * this is commonly where there is ternary or higher system and two of the components are specified in terms
	 * of mass fraction or mol fraction.  Analysis done on 092521 indicated 109 datasets where this is present
	 * (see Excel spreadsheet... 'stats on datapoints with multiple conds of the same quantities.xslx')
	 * @param int $dsid
	 * @return void
	 */
	public function dupequants(int $dsid)
	{
		$c=['File'=>['Journal'],'Mixture'=>['Compohnent'],'Dataseries'=>['Datapoint'=>['Condition'=>['Quantity'],'Data']]];
		$data = $this->Dataset->find('first',['conditions'=>['Dataset.id'=>$dsid],'contain'=>$c,'recursive'=>-1]);
		$fname=$data['File']['filename'];
		$fldr=$data['File']['Journal']['set'];
		$path = WWW_ROOT.'files'.DS.'trc'.DS.$fldr.DS;
		$xml = simplexml_load_file($path.$fname);
		$trc = json_decode(json_encode($xml), true);
		$sets = [];
		if(!isset($trc['PureOrMixtureData'][0])) {
			$sets[0]=$trc['PureOrMixtureData'];
		} else {
			$sets=$trc['PureOrMixtureData'];
		}

		// select the PureOrMixtureData set that this dataset is about
		$set=$sets[($data['Dataset']['setnum'] - 1)];

		// get the database compohnents (mispelling is deliberate)
		$rows = $data['Mixture']['Compohnent'];$cohs=[];
		foreach($rows as $row) { $cohs[$row['compnum']]=$row['id']; }

		// get component -> nOrgNum mapping
		$components=[];
		if(!isset($set['Component'][0])) {
			$cpnts[0]=$set['Component'];
		} else {
			$cpnts=$set['Component'];
		}
		// $components => compound idx (key) : component number in mixture (value)
		foreach($cpnts as $cidx=>$cpnt) { $components[($cpnt['RegNum']['nOrgNum']+0)] = $cidx+1; }

		// get variables assigned to nOrgNum
		$variables=[];$prop='';
		if(!isset($set['Variable'][0])) {
			$vars[0]=$set['Variable'];
		} else {
			$vars=$set['Variable'];
		}
		foreach($vars as $vidx=>$var) {
			$proptype=$var['VariableID']['VariableType'];
			if(isset($proptype['eComponentComposition'])) {
				$prop=$proptype['eComponentComposition'];
			} elseif(isset($proptype['ePressure'])) {
				$prop=$proptype['ePressure'];
			} elseif(isset($proptype['eTemperature'])) {
				$prop=$proptype['eTemperature'];
			}
			if(isset($var['VariableID']['RegNum'])) {
				$rnum=$var['VariableID']['RegNum']['nOrgNum'];
				$variables[($vidx+1)] = $prop.":".$rnum;
			} else {
				$variables[($vidx+1)] = $prop;
			}
		}

		// get datapoints
		$xmlpnts=[];
		if(!isset($set['NumValues'][0])) {
			$xmlpnts[0]=$set['NumValues'];
		} else {
			$xmlpnts=$set['NumValues'];
		}

		// iterate over the points
		foreach($data['Dataseries'] as $sidx=>$series) {
			if($series['comments']=='done') { continue; }
			foreach($series['Datapoint'] as $dbpnt) {
				foreach($xmlpnts as $xmlpnt) {
					// verify points match
					$good=1;$conds=$vars=$datums=$props=[];

					// verify conditions
					foreach($dbpnt['Condition'] as $cond) {
						$conds[]=$cond['text'];
					}
					if(isset($xmlpnt['VariableValue']['nVarNumber'])) {
						$xmlpnt['VariableValue']=[0=>$xmlpnt['VariableValue']];
					}
					foreach($xmlpnt['VariableValue'] as $var) {
						$vars[]=$var['nVarValue'];
					}
					foreach($conds as $cond) {
						if(!in_array($cond,$vars)) { $good=0; }
					}

					// verify data
					foreach($dbpnt['Data'] as $datum) {
						$datums[]=$datum['text'];
					}
					if(isset($xmlpnt['PropertyValue']['nPropNumber'])) { $xmlpnt['PropertyValue']=[0=>$xmlpnt['PropertyValue']]; }
					foreach($xmlpnt['PropertyValue'] as $prop) {
						$props[]=$prop['nPropValue'];
					}
					foreach($datums as $datum) {
						if(!in_array($datum,$props)) {
							$good=0;
						}
					}

					// points match or not...
					if($good) {
						echo "Perfect match...<br/>";
						// find the variables that have the issue (they have the orgnum with the prop)
						foreach($variables as $vidx=>$propcom) {
							if(!stristr($propcom,':')) {
								unset($variables[$vidx]);
							}
						}
						// find the conditions that have a component_id assigned
						foreach($dbpnt['Condition'] as $cidx=>$cond) {
							if(is_null($cond['component_id'])) {
								unset($dbpnt['Condition'][$cidx]);
							}
						}
						// correlate the data
						foreach($variables as $varnum=>$propcnum) {
							list($prop,$compnum)=explode(':',$propcnum);
							$varpnt=$xmlpnt['VariableValue'][$varnum-1];
							foreach($dbpnt['Condition'] as $cond) {
								//debug($cond);debug($prop);debug($varpnt);
								if($cond['quantity_name']==$prop&&$varpnt['nVarValue']==$cond['text']) {
									if($components[$compnum]) {
										// get component_id

										$compid=$cohs[$components[$compnum]];
										// check if it matches the condition value
										if($compid==$cond['component_id']) {
											echo 'Component IDs match ('.$compid.')<br/>';
										} else {
											echo 'Component IDs don\'t match '.$compid.'|'.$cond['component_id'].'<br/>';
											// update the component_id
											$this->Condition->id=$cond['id'];
											$this->Condition->saveField('component_id',$compid);
										}
										//debug($varpnt);debug($xmlpnt);debug($datums);debug($components);debug($cohs);debug($compnum);exit;
									}
								}
							}
						}
					}
				}
			}
			// update the component_id
			$this->Dataseries->id=$series['id'];
			$this->Dataseries->saveField('comments','done');
			echo "End of dataset ".($sidx+1)."<br/>";exit;
		}
		exit;
	}

}
