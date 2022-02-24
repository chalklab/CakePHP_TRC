<?php
# include these classes to enable easy access to files
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

/**
 * Class ReferencesController
 * Actions related to references
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class ReferencesController extends AppController
{
	public $uses = ['Reference'];

	/**
	 * beforeFilter function
	 */
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Auth->allow('index','view');
	}

	/**
	 * view a list of references
	 * filter/search option in the page
	 * @return void
	 */
	public function index()
	{
		$f = ['id','title','year']; $o =['year'=>'desc','title'];
		$data = $this->Reference->find('list',['fields'=>$f, 'order'=>$o, 'recursive'=>-1]);
		$this->set('data',$data);
	}

    /**
     * view an entry for a reference
     * @param int $id
	 * @return void
	 */
	public function view(int $id)
    {
    	$c = ['File','Journal','Dataset'=>['System','Dataseries'=>['Datapoint'=>['Condition'=>['Quantity'],'Data'=>['Quantity']]]]];
        $data = $this->Reference->find('first', ['conditions'=>['Reference.id'=>$id], 'contain'=>$c, 'recursive'=>-1]);
        foreach($data['Dataset'] as $sidx=>$set) {
        	$sers=$set['Dataseries'];unset($data['Dataset'][$sidx]['Dataseries']);

			// summarize available data for viewing
			$data['Dataset'][$sidx]['sercnt']=count($sers);
			$cprps=$dprps=[];
			foreach($sers[0]['Datapoint'][0]['Condition'] as $cnd) {
				$cprps[]=$cnd['Quantity']['name'];
			}
			foreach($sers[0]['Datapoint'][0]['Data'] as $dat) {
				$dprps[]=$dat['Quantity']['name'];
			}
			$data['Dataset'][$sidx]['cprps']=implode(', ',array_unique($cprps));
			$data['Dataset'][$sidx]['dprps']=implode(', ',array_unique($dprps));
		}
        $this->set('data',$data);
    }

}
