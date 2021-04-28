<?php

/**
 * Newsubs Controller
 * Class Newsubs
 */
class NewsubsController extends AppController
{
	public $uses=['NewSubstance','NewSystem','CommonChem.Cas'];

	/**
	 * beforeFilter function
	 */
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Auth->allow();
	}

	/**
	 * View a substance
	 * @param $id
	 * @param $debug
	 */
	public function view($id,$debug=false)
	{
		$contain=['NewSystem'];
		$data=$this->NewSubstance->find('first',['conditions'=>['NewSubstance.id'=>$id],'contain'=>$contain]);
		debug($data);
		$contain=['NewSubstance'];
		$data=$this->NewSystem->find('first',['conditions'=>['NewSystem.id'=>$id],'contain'=>$contain]);
		debug($data);exit;
		$this->set('data',$data);
	}

	public function chkcc()
	{
		$cks=$this->NewSubstance->find('list',['fields'=>['id','caskey']]);
		foreach($cks as $subid=>$ck) {
			list($cas,$key)=explode(":",$ck);
			$hit=$this->Cas->search($key);
			$this->NewSubstance->id=$subid;
			if(!$hit) {
				$this->NewSubstance->saveField('incc','no');
				echo $key." not in CC<br/>";
			} elseif($cas=='NULL') {
				$this->NewSubstance->saveField('casrn',$cas);
				$this->NewSubstance->saveField('incc','samecas');
				echo $key." added to CC<br/>";
			} elseif($hit==$cas) {
				$this->NewSubstance->saveField('incc','samecas');
				echo $key." same CAS as CC<br/>";
			} else {
				$this->NewSubstance->saveField('incc','diffcas');
				echo $key." different CAS to CC<br/>";
			}
		}
		exit;
	}

	public function chkcaskey()
	{
		$diffs=$this->NewSubstance->find('list',['fields'=>['id','caskey'],'conditions'=>['incc'=>'no']]);
		foreach($diffs as $subid=>$ck) {
			list($cas,$key)=explode(":",$ck);
			$hit=$this->Cas->detail($cas);
			if(!isset($hit['message'])) {
				$this->NewSubstance->id=$subid;
				if($hit['inchiKey']==$key) {
					echo "Key ".$key." matched<br/>";
					$this->NewSubstance->saveField('incc','samecas');
				} else {
					echo "Key ".$key." not matched<br/>";
					$this->NewSubstance->saveField('incc','diffkey');
				}
			} else {
				echo $cas." not found on Common Chemistry<br/>";
			}
		}
		exit;
	}
}
