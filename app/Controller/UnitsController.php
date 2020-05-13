<?php

/**
 * Class UnitsController
 * Actions related to dealing with units
 * @author Stuart Chalk <schalk@unf.edu>
 *
 */
class UnitsController extends AppController
{

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * List the quantities
     * @param $qid - quantityID
     */
    public function index($qid="",$format="")
    {
        if($qid=="") {
            $data=$this->Unit->find('list',['fields'=>['id','name'],'order'=>['name']]);
        } else {
            $data=$this->Unit->find('list',['fields'=>['id','name'],'conditions'=>['quantity_id'=>$qid],'order'=>['name']]);
        }
        //echo "<pre>";print_r($data);echo "</pre>";exit;
        if($format=="json") { echo json_encode($data); exit; }
        $this->set('data',$data);
    }

    /**
     * Get id for term and send out as XML
     * @param $term
     */
    public function xml($term)
    {
        $meta=$this->Unit->find("first",['fields'=>['id','label'],'conditions'=>['header like'=>'%"'.$term.'"%'],'recursive'=>1]);
        header("Content-Type: application/xml");
        echo "<m><id>".$meta['Unit']['id']."</id><label>".$meta['Unit']['label']."</label></m>";
        exit;
    }
	
	public function qudtunits() {
		$qudt=$this->Unit->find('list',['fields'=>['qudt','symbol'],'recursive'=>-1]);
		return $qudt;
	}
    public function view($id)
    {
        $data=$this->Unit->find('first',['conditions'=>['Unit.id'=>$id],'recursive'=>3]);
        echo "<pre>";print_r($data);echo "</pre>";exit;
        $this->set('data',$data);
    }
}