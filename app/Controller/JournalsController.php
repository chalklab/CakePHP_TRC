<?php

/**
 * Class JournalsController
 * Journals Controller
 */
class JournalsController extends AppController {

    public $uses=array('Journal','System','Identifier','SubstancesSystem','File','Report','Dataset','Dataseries',
		'Datapoint','Condition','Data','Setting','Annotation');

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }
    /**
     * View a list of the Journals
     */
    public function index()
    {
        $data=$this->Journal->find('list', ['fields'=>['id','name'],'order'=>['id']]);
        $this->set('data',$data);

        $propCount=[];
        foreach ($data as $id => $prop) {
            $propCount[$id] = $this->File->find('list', array('fields'=>['id']));
        }
        $this->set('propCount',$propCount);
    }
    /**
     * Journal add function
     */
    public function add()
    {
        if($this->request->is('post')) {
            $this->Journal->create();
            if($this->Journal->save($this->request->data)) {
                $this->Flash->set('The journal has been added');
                $this->redirect(array('action' => 'index'));
            } else {
                $this->Flash->set('The journal could not be added.');
            }
        }
    }
    /**
     * View a journal
     * @param $id
     * @param $type
     */
    public function view($id,$type=null)
    {

        $data=$this->Journal->find('first',['conditions'=>['Journal.id'=>$id]]);
        if($this->request->is('ajax')) {
            header('Content-Type: application/json');
            echo "[".json_encode($data)."]";
            exit;
        }
        $this->set('data',$data);
    }
}

