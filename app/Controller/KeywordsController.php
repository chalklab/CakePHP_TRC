<?php

/**
 * Class KeywordsController
 * Actions related to dealing with keywords
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class KeywordsController extends AppController
{
	public $uses = ['Keyword','File','Report','Dataset','Reference'];

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
		$data=$this->Keyword->find('list',['fields'=>['term','termcnt','tfirst'],'group'=>'term','conditions'=>['check'=>0],'recursive'=>-1]);
		$this->set('data',$data);
	}

	/**
	 * view a list of papers with a specific keyword
	 * @param string $term
	 * @return void
	 */
	public function view(string $term='')
	{
		$term=str_replace("**","/",$term);
		$rptids=$this->Keyword->find('list',['fields'=>['report_id'],'conditions'=>['term'=>$term],'recursive'=>-1]);
		$refids=$this->Dataset->find('list',['fields'=>['reference_id'],'conditions'=>['report_id'=>$rptids],'recursive'=>-1]);
		$f = ['id','title','year']; $o =['year'=>'desc','title'];$c=['id'=>$refids];
		$data = $this->Reference->find('list',['fields'=>$f,'conditions'=>$c, 'order'=>$o, 'recursive'=>-1]);
		$this->set('data',$data);
		$this->set('term',$term);
	}

	/**
	 * add keywords by reprocessing the XML files
	 * @return void
	 */
	public function ingest()
	{
		$jids=['jced','jct','fpe','tca','ijt'];
		foreach($jids as $jid) {
			$path = WWW_ROOT . 'files' . DS . 'trc' . DS . $jid . DS;
			$maindir = new Folder($path);
			$files = $maindir->find("^.+\.xml$", true);
			$fids = $this->File->find('list',['fields'=>['id','filename']]);
			$rids = $this->Report->find('list',['fields'=>['id','file_id']]);
			foreach ($files as $filename) {
				$filepath = $path . $filename;
				$xml = simplexml_load_file($filepath);
				$trc = json_decode(json_encode($xml), true);

				// get keywords
				$cite=$trc['Citation'];
				if(empty($cite['sKeyword'])) { continue; }
				if(!isset($cite['sKeyword'][0])) {
					// needed as PHP XML to JSON using json_decode(json_encode($xml), true)
					// does not add [0] when only one subarray present
					$cite['sKeyword'] = [0 => $cite['sKeyword']];
				}
				$fid=array_search($filename,$fids);
				$rid=array_search($fid,$rids);

				// add keywords
				foreach($cite['sKeyword'] as $keyword) {
					if(empty($keyword)) { continue; }
					$data=['report_id'=>$rid,'term'=>lcfirst($keyword)];
					$this->Keyword->create();
					$this->Keyword->save(['Keyword'=>$data]);
				}
			}
		}
		exit;
	}

}
