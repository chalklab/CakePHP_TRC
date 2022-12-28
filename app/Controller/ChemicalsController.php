<?php

/**
 * Class ChemicalsController
 * controller actions for chemical functions
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/24/22
 */
class ChemicalsController extends AppController {

	public $uses = ['Chemical','File','Purificationstep'];

	/**
	 * beforeFilter function
	 */
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Auth->allow();
	}

	// functions requiring login (not in Auth::allow)

	/**
	 * migrate purity information in purity field to purificationsteps table
	 * run once
	 * @return void
	 */
	public function purity()
	{
		$chems = $this->Chemical->find('list',['fields'=>['id','purity'],'conditions'=>['NOT'=>['purity'=>null]]]);
		$fields = ['step','type','purity','puritysf','purityunit_id','analmeth','purimeth'];
		$done = $this->Purificationstep->find('list',['fields'=>['chemstep']]);
		//debug($done);exit;
		foreach($chems as $cid=>$json) {
			$steps=json_decode($json,true);
			foreach($steps as $step) {
				if(in_array($cid.':'.$step['step'],$done)) { continue; }
				if(!is_null($step['analmeth'])) { $step['analmeth']=implode(', ',$step['analmeth']); }
				if(!is_null($step['purimeth'])) { $step['purimeth']=implode(', ',$step['purimeth']); }
				// check for unknown fields
				$temp=$step;
				foreach($fields as $field) { unset($temp[$field]); }
				if(!empty($temp)) { echo "Additional fields?";debug($temp);exit; }
				// add to db
				$step['chemical_id']=$cid;
				$this->Purificationstep->create();
				$this->Purificationstep->save(['Purificationstep'=>$step]);
			}
			echo "Done chemical ".$cid."<br/>";
		}
		exit;
	}

	/**
	 * check that the 'chemicals' are present in all files
	 * run once
	 * @return void
	 */
	public function checkchems()
	{
		$jids=['jced','jct','fpe','tca','ijt'];
		foreach($jids as $jid) {
			$path = WWW_ROOT.'files'.DS.'trc'.DS.$jid.DS;
			$maindir = new Folder($path);
			$files = $maindir->find('^.*\.xml$', true);
			$done = $this->File->find('list', ['fields' => ['id', 'filename'], 'conditions' => ['comments' => 'chemchk']]);
			foreach ($files as $filename) {
				if (in_array($filename, $done)) { echo "File: " . $filename . " already done<br/>"; continue; }
				$filepath = $path . $filename;
				$xml = simplexml_load_file($filepath);
				$trc = json_decode(json_encode($xml), true);
				$cmpds = $trc['Compound'];
				if (isset($cmpds['RegNum'])) { $cmpds = [0 => $cmpds]; }
				$file = $this->File->find('first', ['conditions' => ['filename' => $filename], 'contain' => ['Chemical'], 'recursive' => -1]);
				if (count($cmpds) == count($file['Chemical'])) {
					// update file
					$this->File->id = $file['File']['id'];
					$this->File->saveField('comments', 'chemchk');
					// update chemicals
					foreach ($file['Chemical'] as $chem) {
						$this->Chemical->id = $chem['id'];
						$this->Chemical->saveField('comments', 'found');
					}
					echo 'File: ' . $filename . ' match!<br/>';
				}
			}
		}
		exit;
	}


}




