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
     * Total files
     * @return mixed
     */
    public function totalfiles()
    {
        $data=$this->Data->find('count');
        return $data;
    }

}