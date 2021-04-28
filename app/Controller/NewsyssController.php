<?php

/**
 * Newsyss Controller
 * Class Newsyss
 */
class NewsyssController extends AppController
{
	public $uses=['NewSystem','NewSubstance','NewDataset'];

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
	 */
	public function delete($id)
	{
		$this->NewSystem->delete($id);
		echo "System ".$id." deleted";exit;
	}

	/**
	 * check that a system has the composition the name says...
	 */
	public function checksys()
	{
		$syss=$this->NewSystem->find('all',['contain'=>['NewSubstance'],'recursive'=>-1]);
		foreach($syss as $sys) {
			//debug($sys);
			$sysname=$sys['NewSystem']['name'];$bad=0;
			$names=explode(" + ",$sysname);
			$newname="";
			foreach($sys['NewSubstance'] as $sidx=>$sub) {
				if(!in_array($sub['name'],$names)) { $bad=1; }
				if($sidx>0) { $newname.=" - "; }
				$newname.=$sub['name'];
			}
			if($bad) {
				$sysid=$sys['NewSystem']['id'];
				$sets=$this->NewDataset->find('count',['conditions'=>['system_id'=>$sysid],'recursive'=>-1]);
				echo "No match '".$sysname."' and '".$newname."' in system ".$sysid." (".$sets.")<br/>";
			}
		}
		exit;
	}
}
