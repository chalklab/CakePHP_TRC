<?php

/**
 * Class DataseriesController
 */
class DataseriesController extends AppController
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
        $data=$this->Dataseries->find('first',['conditions'=>['Dataseries.id'=>$id],'recursive'=>4]);
        $this->set('Dataseries',$data);
    }

    /**
     * Delete a dataseries
     * @param integer $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->Dataseries->delete($id);
        return $this->redirect('/files/index');
    }
    /**
     * View a list of the Dataseries
     */
    public function index()
    {
        $data=$this->Dataseries->find('list',['fields'=>['id','dataset_id'],'order'=>['id'], "limit"=>50]);
        $this->set('data',$data);

        $propCount=[];
        foreach ($data as $id => $prop) {
            {
                $propCount[$id]= $this->Dataseries->find('list',array('fields'=>['id']));
            }

        }
        $this->set('propCount',$propCount);
    }
}