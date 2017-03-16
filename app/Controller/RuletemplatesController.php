<?php

/**
 * Class RuletemplatesController
 *
 */
class RuletemplatesController extends AppController
{
    public $uses=['Ruletemplate'];

    /**
     * Add ruletemplate
     */
    public function add()
    {
        if(!empty($this->data)) {
            $this->Ruletemplate->create();
            $this->Ruletemplate->save($this->request->data);
            return $this->redirect('/ruletemplates/view/'.$this->Ruletemplate->id);
        }
    }

    /**
     * Get an list of the currently defined ruletemplates
     */
    public function index()
    {
        $tmpls=$this->Ruletemplate->find('all');
        $this->set('actions',$this->actions); //load the actions the be useable during the view
        $this->set('tmpls',$tmpls);
    }

    /**
     * View a ruletemplate
     * @param $id
     */
    public function view($id)
    {
        $data=$this->Ruletemplate->find('first',['conditions'=>['id'=>$id]]);
        if($this->request->is('ajax')) {
            header('Content-Type: application/json');
            echo "[".json_encode($data['Ruletemplate'])."]";
            exit;
        }
        $this->set('data',$data);
    }

    /** Updated */
    public function updatetmpl($id=0) {

        $regex=$this->data['regex'];
        if($id==0||$regex=="") {
            echo false;exit;
        } else {
            $this->Ruletemplate->id=$id;
            if($this->Ruletemplate->saveField('regex',$regex)) {
                echo true;exit;
            } else {
                echo false;exit;
            }
        }
    }
}