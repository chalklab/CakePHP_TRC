<?php

/**
 * Class QuadsController
 * Actions related to dealing with quads stored in SciFlow
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class QuadsController extends AppController
{
	public $uses = ['Quad','Data','Dataset','Reference'];

	/**
	 * beforeFilter function
	 */
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Auth->allow('index','view');
	}

	/**
	 * list the current keywords grouped together
	 * @return void
	 */
	public function index()
	{
		$data=$this->Quad->find('list',['fields'=>['id','id','gph'],'recursive'=>-1,
			'conditions'=>['prd'=>'<https://stuchalk.github.io/scidata/ontology/scidata.owl#hasDatum>']]);
		$counts=[];
		foreach($data as $gph=>$rows) {
			$counts[$gph]=count($rows);
		}
		foreach($counts as $gph=>$cnt) {
			preg_match('/_(\d{4}[a-z]+\d+-\d+)/',$gph,$m);
			$datid=$this->Dataset->find('list',['fields'=>['trcidset_id','id'],'conditions'=>['trcidset_id'=>$m[1]],'recursive'=>-1]);
			$dbcnt=$this->Data->find('count',['conditions'=>['Data.dataset_id'=>$datid]]);
			if($dbcnt!=$cnt) {
				list($trcid,$junk)=explode('-',$m[1]);
				$doi=$this->Reference->File->find('first',['conditions'=>['File.trcid'=>$trcid],'recursive'=>-1]);
				debug($doi);debug($trcid);debug($dbcnt);debug($cnt);
			}
			echo $m[1]." data count is correct<br/>";
		}
		debug($counts);exit;
	}

}
