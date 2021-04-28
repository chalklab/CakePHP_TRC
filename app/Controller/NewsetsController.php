<?php

/**
 * Class NewsetsController
 */
class NewsetsController extends AppController
{
	public $uses=['NewDataset','NewSubstance','NewSystem','NewSubstancesSystem','NewFile'];

	/**
	 * beforeFilter function
	 */
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Auth->allow();
	}

	/**
	 * view a dataset
	 * @param integer $id
	 * @param integer $serid
	 * @return mixed
	 */
	public function view(int $id,$serid=null)
	{
		$ref =['id','journal','authors','year','volume','issue','startpage','endpage','title','url'];
		$con =['id','datapoint_id','system_id','property_name','number','significand','exponent','unit_id','accuracy'];
		$prop =['id','name','phase','field','label','symbol','definition','updated'];
		$chmf=['formula','orgnum','source','substance_id'];
		$c=['NewAnnotation',
			'NewDataseries'=>[
				'NewCondition'=>['NewUnit','NewProperty','NewAnnotation'],
				'NewSetting'=>['NewUnit','NewProperty'],
				'NewDatapoint'=>[
					'NewAnnotation',
					'NewCondition'=>['fields'=>$con,'NewUnit','NewComponent','NewProperty'=>['fields'=>$prop]],
					'NewData'=>['NewUnit','NewSampleprop','NewComponent','NewProperty'=>['fields'=>$prop]],
					'NewSetting'=>['NewUnit','NewProperty']
				],
				'NewAnnotation'
			],
			'NewFile'=>['NewChemical'],
			'NewSampleprop',
			'NewReactionprop',
			'NewReport',
			'NewReference'=>['fields'=>$ref,'NewJournal'],
			'NewSystem'=>[
				'NewSubstance'=>[
					'NewIdentifier'=>['fields'=>['type','value']]
				]
			],
			'NewMixture'=>[
				'NewComponent'=>[
					'NewChemical'=>[
						'NewSubstance'=>[
							'NewIdentifier'=>['fields'=>['type','value']]]]]]
		];

		$data=$this->NewDataset->find('first',['conditions'=>['NewDataset.id'=>$id],'contain'=>$c,'recursive'=>-1]);
		//debug($data);exit;

		$graph=false;
		if($graph) {
			 $dpt=$data["NewDataseries"][0]["NewDatapoint"][0];
			 $xname=$dpt["NewCondition"][0]["NewProperty"]["name"];
			 $xunit=$dpt["NewCondition"][0]["NewUnit"]["label"];
			 $xlabel=$xname.", ".$xunit;$conds=[];
			 $sers=$data["NewDataseries"];
			 foreach($sers as $ser) {
				 if(!empty($ser['NewCondition'])) {
					 foreach($ser['NewCondition'] as $scidx=>$scond) {
						 $val=$scond['number']+0;
						 $unit=$scond["NewUnit"]["symbol"];
						 $conds[$scidx]=$val." ".$unit;
					 }
				 }
			 }

			 $yunit=$dpt["NewData"][0]["NewUnit"]["header"];
			 $samprop= $dpt["NewData"][0]["NewSampleprop"]=["property_name"];
			 $ylabel=$samprop;
			 $sub=$data["NewSystem"]["name"];
			 $formula=$data["NewSystem"]["NewSubstance"][0]["formula"];

			 $xs=[];$ys=[];

			 // loop through the datapoints
			 $test=$data['NewDataseries'];
			 $xy[0]=[];
			 $num=0;
			 if(empty($conds)) {
				 $con="";
			 } else {
				 $con=$conds[$num];
			 }
			 foreach($test as $tt){
				 $count=1;
				 $xy[0][]=["label"=>$xlabel,"role"=>"domain","type"=>"number"];
				 $xy[0][]=["label"=>$con,"role"=>"data","type"=>"number"];
				 $xy[0][]=["label"=>"Min Error","type"=>"number","role"=>"interval"];
				 $xy[0][]=["label"=>"Max Error","type"=>"number","role"=>"interval"];

				 $points=$tt['NewDatapoint'];
				 $num++;
				 foreach($points as $pnt) {
					 $x=$pnt['NewCondition'][0]['number']+0;
					 $y=$pnt['NewData'][0]['number']+0;
					 $error=$pnt['NewData'][0]['error']+0;
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
				 }}

			 // send variables to the view
			 $this->set('xy',$xy);
			 $this->set('maxx',$maxx); $this->set('maxy',$maxy);
			 $this->set('minx',$minx); $this->set('miny',$miny);
			 $this->set('errormin',$errormin);$this->set('errormax',$errormax);
			 $this->set('ylabel',$ylabel);
		}
		$this->set('xlabel',"");
		$this->set('dump',$data);
		$this->set('title',"title");
		$fid=$data['NewDataset']['file_id'];

		/// Get a list of datsets that come from the same file
		$related=$this->NewDataset->find('list',['conditions'=>['NewDataset.file_id'=>$fid,'NOT'=>['NewDataset.id'=>$id]],'recursive'=>1]);
		if(!is_null($serid)) {
			$this->set('serid',$serid);
			$this->render('data');
		}
		$this->set('related',$related);
		$this->set('dsid',$id);
		if($this->request->is('ajax')) {
			$title=$data['NewDataset']['title'];
			echo '{ "title" : "'.$title.'" }';exit;
		}
	}

	/**
	 * delete a dataset and all associated data
	 * @param $id
	 */
	public function delete($id)
	{
		$this->NewDataset->delete($id);
		echo "Dataset ".$id." deleted";exit;
	}

	public function chksubs()
	{
		// load jced inchikeys
		$path = WWW_ROOT.'files'.DS.'trc'.DS;
		$filepath = $path.'inchikey.xml';

		$xml = simplexml_load_file($filepath);
		$trc = json_decode(json_encode($xml), true);
		$keys=[];
		foreach($trc['incident'] as $i) { $keys[]=$i['type']; }

		// deduplicate inchikeys
		$keycnts=array_count_values($keys);
		$ukeys=array_keys($keycnts);
		debug(count($ukeys));
		// check for keys in table (missing ones (9) are for reaction data)
		// update table to confirm in jced dataset, add count to table
		$allsubs=$this->NewSubstance->find('list',['fields'=>['id','inchikey']]);
		foreach($ukeys as $uid=>$ukey) {
			if(in_array($ukey,$allsubs)) {
				unset($ukeys[$uid]);
				$this->NewSubstance->id=array_search($ukey,$allsubs);
				$this->NewSubstance->saveField('jced','yes');
				$this->NewSubstance->saveField('count',$keycnts[$ukey]);
			}
		}
		debug($ukeys);exit;

		// check common chemistry for casrns given inchikeys


	}

	public function chksyss()
	{
		$syss=$this->NewSystem->find('list',['fields'=>['id','identifier']]);
		$sets=$this->NewDataset->find('list',['fields'=>['id','system_id']]);
		$joins=$this->NewSubstancesSystem->find('list',['fields'=>['id','substance_id','system_id']]);
		foreach($syss as $sysid=>$ident) {
			$subids=explode(":",$ident);
			if(isset($joins[$sysid])) {
				foreach($subids as $subid) {
					if(!in_array($subid,$joins[$sysid])) {
						echo "Substance: ".$subid." not found in System: ".$sysid."<br/>";
						if(in_array($sysid,$sets)) { echo $sysid." has datasets<br/>"; }
					}
				}
			} else {
				echo "System: ".$sysid." not found<br/>";
				if(in_array($sysid,$sets)) { echo $sysid." has datasets<br/>"; }
			}
			//echo "System: ".$sysid." confirmed<br/>";
		}
		exit;
	}

	public function chksyss2()
	{
		// recheck each XML file for the valid substances
		// then recheck each prop dataset for system
		$path = WWW_ROOT.'files'.DS.'trc'.DS.'jced'.DS;
		$maindir = new Folder($path);
		$files = $maindir->find('.*\.xml',true);
		$done = $this->NewFile->find('list', ['fields' => ['id','filename'],'conditions'=>['syschk'=>'yes']]);
		foreach ($files as $filename) {
			if (in_array($filename, $done)) { continue; }  // echo $filename." already processed<br/>";
			$filepath = $path . $filename;
			$xml = simplexml_load_file($filepath);
			$trc = json_decode(json_encode($xml), true);
			// get substances
			$subs=$trc['Compound'];
			if(!isset($subs[0])) { $subs=[0=>$subs]; }
			$keys=[]; // indexed by ['nOrgNum']
			foreach($subs as $sub) { $keys[$sub['RegNum']['nOrgNum']]=$sub['sStandardInChIKey']; }
			//debug($keys);
			// get systems
			$sets=$trc['PureOrMixtureData'];
			if(!isset($sets[0])) { $sets=[0=>$sets]; }
			$fsyss=[]; // indexed by 'nPureOrMixtureDataNumber'
			foreach($sets as $set) {
				$comps=$set['Component'];
				if(!isset($comps[0])) { $comps=[0=>$comps]; }
				$sys=[]; // list of inchikeys
				foreach($comps as $cidx=>$comp) {
					$cnum=$cidx+1;
					$sys[$cnum]=$keys[$comp['RegNum']['nOrgNum']];
				}
				$fsyss[$set['nPureOrMixtureDataNumber']]=$sys;
			}
			//debug($fsyss);
			// get file
			$fid=$this->NewFile->find('list',['fields'=>['filename','id'],'conditions'=>['filename'=>$filename]]);
			// get datasets
			$sets=$this->NewDataset->find('list',['fields'=>['id','system_id'],'conditions'=>['file_id'=>$fid],'order'=>'setnum']);
			// TODO: if(count(!$fsyss)!=count($sets)) { echo "Dataset count incorrect in ".$filename."!";exit; }
			// get database systems and check
			$setnum=1; // can use as $sets is ordered by setnum...
			$dsdone=$this->NewDataset->find('list',['fields'=>['id'],'conditions'=>['syschk'=>'yes'],'order'=>'setnum']);
			foreach($sets as $setid=>$sysid) {
				if(in_array($setid,$dsdone)) { echo "System in dataset ".$setid." already matched<br/>";$setnum++;continue; }
				// get system
				$sys=$this->NewSystem->find('first',['conditions'=>['id'=>$sysid],'contain'=>['NewSubstance'],'recursive'=>-1]);
				$dsys=[];
				foreach($sys['NewSubstance'] as $sub) { $dsys[]=$sub['inchikey']; }
				if(!empty(array_diff($fsyss[$setnum],$dsys))) {
					echo "System in dataset ".$setid." does not match file ".$filename;
					debug($fsyss[$setnum]);debug($dsys);exit;
				} else {
					echo "System in dataset ".$setid." matches file<br/>";
					$this->NewDataset->id=$setid;
					$this->NewDataset->saveField('syschk','yes');
				}
				$setnum++;
			}
		}
		exit;
	}
}
