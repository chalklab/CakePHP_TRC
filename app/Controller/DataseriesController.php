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
        $this->Auth->allow('totalfiles');
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
     * Count all the dataseries
     * @return mixed
     */
    public function totalfiles()
    {
        $data=$this->Dataseries->find('count');
        return $data;
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

}
