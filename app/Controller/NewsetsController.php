<?php

/**
 * Class NewsetsController
 */
class NewsetsController extends AppController
{
	public $uses=['Dataset','Journal','Report','Quantity','Dataseries',
		'Parameter','Variable','Scidata','System','Substance','File',
		'Reference','Unit','Sampleprop','Trc','NewDataset'];

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
	 * @param string $layout
	 * @return mixed
	 */
	public function view(int $id,$layout=null)
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


		$name=$data["NewDataseries"][0]["NewDatapoint"][0];
		$xname=$name["NewCondition"][0]["NewProperty"]["name"];
		$xunit=$name["NewCondition"][0]["NewUnit"]["label"];
		$xlabel=$xname.", ".$xunit;
		$condition=$data["NewDataseries"];
		foreach($condition as $con){
			if(isset($con["NewCondition"][1])) {
				$cont=$con["NewCondition"][1]["number"]+0;
				$conditionunit=$con["NewCondition"][1]["NewUnit"]["symbol"];
			} else {
				$cont=$con["NewCondition"][0]["number"]+0;
				$conditionunit=$con["NewCondition"][0]["NewUnit"]["symbol"];
			}
			$cond=$cont." ".$conditionunit;
			$conds[]=$cond;
		}
		// debug($cond);exit;
		$yunit=$name["NewData"][0]["NewUnit"]["header"];
		$samprop= $name["NewData"][0]["NewSampleprop"]=["property_name"];
		$ylabel=$samprop;
		$sub=$data["NewSystem"]["name"];
		$formula=$data["NewSystem"]["NewSubstance"][0]["formula"];


		$xs=[];$ys=[];


		// loop through the datapoints
		$test=$data['NewDataseries'];
		$xy[0]=[];
		$num=0;
		foreach($test as $tt){
			$count=1;
			$xy[0][]=["label"=>$xlabel,"role"=>"domain","type"=>"number"];
			$xy[0][]=["label"=>$conds[$num],"role"=>"data","type"=>"number"];
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
		//debug($xy);exit;
		// send variable to the view
		$this->set('xy',$xy);
		$this->set('maxx',$maxx); $this->set('maxy',$maxy);
		$this->set('minx',$minx); $this->set('miny',$miny);
		$this->set('errormin',$errormin);$this->set('errormax',$errormax);
		$this->set('name', $name);
		$this->set('xlabel',$xlabel); $this->set('ylabel',$ylabel);
		$this->set('dump',$data); $this->set('test', $test);
		$fid=$data['NewDataset']['file_id'];
		// Get a list of datsets that come from the same file
		$related=$this->NewDataset->find('list',['conditions'=>['NewDataset.file_id'=>$fid,'NOT'=>['NewDataset.id'=>$id]],'recursive'=>1]);
		if(!is_null($layout)) {
			$this->set('serid',$serid);
			$this->render('data');
		}
		$this->set('related',$related);
		$this->set('dsid',$id);
		if($this->request->is('ajax')) {
			$title=$data['NewDataset']['title'];
			echo '{ "title" : "'.$title.'" }';exit;
		}

		// debug($xy);exit;
	}



}
