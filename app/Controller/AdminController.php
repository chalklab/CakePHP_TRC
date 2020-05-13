<?php

/**
 * Class AdminController
 */
class AdminController extends AppController
{
    public $uses=['Data','DataSystem'];

    /**
     * function beforeFilter
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }
	
	/**
	 * Update entries in the data_systems table
	 * @param int $start
	 */
	public function datasys($start=0)
	{
		// Update the data_systems join table
		$limit=5000;
		$this->Data->joinsys('bulk',$start,$limit);
		exit;
	}
	
	public function checkdatasys($from=0)
	{
		$allcount=$this->DataSystem->find('count');
		$alldids=$this->Data->find('list');
		$chunk=40000;
		$chunks=ceil($allcount/$chunk);
		for($x=$from;$x<$chunks;$x++) {
			$start = microtime(true);
			$dsids=$this->DataSystem->find('list',['fields'=>['id','data_id'],'start'=>($x*$chunk),'limit'=>$chunk,'order'=>'id']);
			foreach($dsids as $dsid=>$did) {
				if(!in_array($did,$alldids)) {
					debug($did);exit;
				}
			}
			$elapsed=microtime(true) - $start;
			echo "Processed chunk #".($x+1)." (".ceil($elapsed)." s)<br/>";exit;
		}
		debug($allcount);exit;
	}
}