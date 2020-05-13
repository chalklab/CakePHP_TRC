<?php

/**
 * Class DataController
 */
class DataController extends AppController
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
     * View a property type
     */
    public function view($id)
    {
        $data=$this->Data->find('first',['conditions'=>['Data.id'=>$id],'recursive'=>5]);
        $this->set('Data',$data);
    }
    /**
     * View a list of the Data
     */
    public function index()
    {
        $data=$this->Data->find('list',['fields'=>['id','datapoint_id'],'order'=>['id'], "limit"=>20]);
        $this->set('data',$data);

        $propCount=[];
        foreach ($data as $id => $prop) {
            {
                $propCount[$id]= $this->Data->find('list',array('fields'=>['id']));
            }

        }
        $this->set('propCount',$propCount);
    }
}