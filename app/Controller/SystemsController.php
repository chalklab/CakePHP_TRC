<?php

/**
 * Class SystemsController
 * Actions related to dealing with chemical systems
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class SystemsController extends AppController {

    public $uses=['System','Dataset','Journal'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('index','view');
    }

    /**
     * view a list of systems
	 * @return void
	 */
    public function index()
    {
        $data=$this->System->find('list', ['fields'=>['id','namercnt','first'],'order'=>['name']]);
        $this->set('data',$data);
    }

	/**
	 * view a system
	 * @param int $id
	 * @return void
	 */
	public function view(int $id)
	{
		$contain=[
			'Substance'=>['fields'=>['id','name'],'Identifier'=>['type','value']],
			'Dataset'=>['fields'=>['id','points'],
				'Reference'=>['fields'=>['id','citation']],
				'Dataseries'=>['id','points'],
				'Sampleprop'=>['id','quantity_name']
			]
		];
		$data=$this->System->find('first',['conditions'=>['System.id'=>$id],'contain'=>$contain,'recursive'=>-1]);
		$jrnls = $this->Journal->find('list',['fields'=>['id','abbrev']]);
		// reorganize data to make view code more efficient
		$sets = $data['Dataset'];unset($data['Dataset']);
		//debug($sets);exit;
		$refs = [];$totalpnts=0;
		foreach($sets as $set) {
			$ref = $set['Reference'];
			$sers = $set['Dataseries'];
			$prps = $set['Sampleprop'];
			if(!isset($refs[$ref['id']])) {
				// replace journal code with abbreviation in citation (see virtualfields in Journal.php model)
				preg_match('/\*(\d{3})\*/',$ref['citation'],$m);
				if(!isset($m[1])) { debug($set);exit; }
				$refs[$ref['id']] = ['cite'=>str_replace("*".$m[1]."*",$jrnls[$m[1]],$ref['citation']),'sets'=>[]];
			}
			$s=[];
			$s['points']=$set['points'];
			$totalpnts+=$set['points'];
			$s['sers']=count($sers);
			$s['props']="";
			foreach($prps as $pidx=>$prp) {
				if($pidx>0) { $s['props'].="; "; }
				$s['props'].=$prp['quantity_name'];
			}
			$refs[$ref['id']]['sets'][$set['id']] = $s;
		}
		$data['System']['pntcnt'] = $totalpnts;
		$data['Reference'] = $refs;
		//debug($data);exit;
		$this->set('data',$data);
	}

	// functions requiring login (not in Auth::allow)

	/**
	 * check that a system has the composition the name says...
	 * @return void
	 */
	public function chksys()
	{
		$syss=$this->System->find('all',['contain'=>['Substance'],'recursive'=>-1]);
		foreach($syss as $sys) {
			//debug($sys);
			$sysname=$sys['System']['name'];$bad=0;
			$names=explode(" + ",$sysname);
			$newname="";
			foreach($sys['Substance'] as $sidx=>$sub) {
				if(!in_array($sub['name'],$names)) { $bad=1; }
				if($sidx>0) { $newname.=" - "; }
				$newname.=$sub['name'];
			}
			if($bad) {
				$sysid=$sys['System']['id'];
				$sets=$this->Dataset->find('count',['conditions'=>['system_id'=>$sysid],'recursive'=>-1]);
				echo "No match '".$sysname."' and '".$newname."' in system ".$sysid." (".$sets.")<br/>";
			}
		}
		exit;
	}

	/**
	 * update refcnt field
	 * @return void
	 */
	public function refcnt()
	{
		$sysids=$this->System->find('list',['fields'=>['id']]);
		$refcnts=$this->Dataset->find('list',['fields'=>['reference_id','reference_id','system_id'],'group'=>['system_id','reference_id'],'recursive'=>-1]);
		foreach($sysids as $sysid) {
			$save=['id'=>$sysid,'refcnt'=>count($refcnts[$sysid])];
			$this->System->save($save);
		}
		exit;
	}
}
