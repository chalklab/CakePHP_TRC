<?php

/**
 * Class IdentifiersController
 * Actions related to dealing with compound identifiers
 * @author Stuart Chalk <schalk@unf.edu>
 */
class IdentifiersController extends AppController
{

    public $uses=['Identifier','Substance'];

    /**
     * function beforeFilter
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * Remove abandoned identifiers (no substance)
     */
    public function clean()
    {
        $subs=$this->Identifier->find('list',['fields'=>['substance_id'],'group'=>['substance_id']]);
        foreach($subs as $sub) {
            $res=$this->Substance->find('first',['conditions'=>['id'=>$sub],'recursive'=>-1]);
            if(empty($res)) {
                $this->Identifier->deleteAll(['substance_id'=>$sub],false);
                echo "Deleted: ".$sub."<br />";
            } else {
                echo "Retained: ".$sub."<br />";
            }
        }
        echo count($subs)."<br />";exit;
    }
    /**
     * View a list of the Identifiers
     */
    public function index()
    {
        $data=$this->Identifier->find('list',['fields'=>['id','type','value'],'order'=>['id'], "limit"=>50]);
        $this->set('data',$data);

        $propCount=[];
        foreach ($data as $id => $prop) {
            {
                $propCount[$id]= $this->Substance->find('list',array('fields'=>['id']));
            }

        }
        $this->set('propCount',$propCount);
    }
}