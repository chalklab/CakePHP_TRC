<?php

/**
 * Class RulesnippetsController
 *
 */
class RulesnippetsController extends AppController
{
    public $uses=['Rulesnippet','Metadata','Property','Unit'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * Get an list of the currently defined rule snippets
     */
    public function index()
    {
        $snips=$this->Rulesnippet->find('all',['order'=>'Rulesnippet.name']);
        $this->set('snips',$snips);
    }

    /**
     * Add a new snippet
     * @return null
     */
    public function add()
    {
        if(!empty($this->request->data)) {
            //debug($this->request->data);exit;
            $data=$this->request->data;
            if($data['Rulesnippet']['metadata_id']=="") { unset($data['Rulesnippet']['metadata_id']); }
            if($data['Rulesnippet']['property_id']=="") { unset($data['Rulesnippet']['property_id']); }
            if(isset($data['Rulesnippet']['unit_id'])&&$data['Rulesnippet']['unit_id']=="") { unset($data['Rulesnippet']['unit_id']); }
            if($data['Rulesnippet']['url']=="") { unset($data['Rulesnippet']['url']); }
            $this->Rulesnippet->create();
            $this->Rulesnippet->save($data);
            return $this->redirect('/rulesnippets/view/'.$this->Rulesnippet->id);
        } else {
            $units=$this->Unit->find('list',['fields'=>['id','name'],'order'=>'name asc']);
            $props=$this->Property->find('list',['fields'=>['id','name'],'order'=>'name asc']);
            $meta=$this->Metadata->find('list',['fields'=>['id','name'],'order'=>'name asc']);
            $this->set('units',$units);
            $this->set('props',$props);
            $this->set('meta',$meta);
        }
    }

    /**
     * View a snippet
     * @param integer $id
     * @param string $json
     */
    public function view($id,$json="no")
    {
        $data=$this->Rulesnippet->find('first',['conditions'=>['Rulesnippet.id'=>$id]]);
        if($this->request->is('ajax') || $json=="yes") {
            header('Content-Type: application/json');
            echo "[".json_encode($data['Rulesnippet'])."]";
            exit;
        }
        $this->set('data',$data);
    }

    /** Updated */
    public function updatesnip($id=0) {

        $regex=$this->data['regex'];
        if($id==0||$regex=="") {
            echo false;exit;
        } else {
            $this->Rulesnippet->id=$id;
            if($this->Rulesnippet->saveField('regex',$regex)) {
                echo true;exit;
            } else {
                echo false;exit;
            }
        }
    }
}