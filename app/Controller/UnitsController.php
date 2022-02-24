<?php

/**
 * Class UnitsController
 * Actions related to dealing with units
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class UnitsController extends AppController
{
	public $uses=['Unit'];

	/**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow(['index','view','qudtunits']);
    }

    /**
     * list the all units OR just those for a quantity
     * @param int $qid - quantityID
	 * @return void
	 */
    public function index($qid="",$format="")
    {
        if($qid=="") {
            $data=$this->Unit->find('list',['fields'=>['id','name'],'order'=>['name']]);
        } else {
            $data=$this->Unit->find('list',['fields'=>['id','name'],'conditions'=>['quantity_id'=>$qid],'order'=>['name']]);
        }
        if($format=="json") { echo json_encode($data); exit; }
        $this->set('data',$data);
    }

	/**
	 * view a unit
	 * @param $id
	 * @return void
	 */
	public function view($id)
	{
		$data=$this->Unit->find('first',['conditions'=>['Unit.id'=>$id],'contain'=>['Quantity','Quantitykind'],'recursive'=>-1]);
		$this->set('data',$data);
	}

	/**
	 * get a list of QUDT units
	 * @return void
	 */
	public function qudt()
	{
		return $this->Unit->find('list',['fields'=>['qudt','symbol'],'recursive'=>-1]);
	}
}
