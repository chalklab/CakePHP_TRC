<?php

/**
 * Class RdfsController for the ChemSpider Plugin
 */
class RdfsController extends AppController
{

    /**
     * function beforeFilter
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * Get metadata of a chemical from ChemSpider
     * @param $cmpd
     */
    public function search($cmpd)
    {
        $data=$this->Rdf->search($cmpd);
        $this->set('data',$data);
        $this->render('display');
    }

}