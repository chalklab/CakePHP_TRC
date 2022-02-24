<?php

/**
 * Class JournalsController
 * controller actions for journal functions
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class JournalsController extends AppController {

    public $uses = ['Journal','File'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('index','view');
    }

    /**
     * get a list of the journals
	 * @return void
	 */
    public function index()
    {
        $data=$this->Journal->find('list', ['fields'=>['id','name'],'order'=>['id']]);
        $this->set('data',$data);

        $propCount=[];
        foreach ($data as $id => $prop) {
            $propCount[$id] = $this->File->find('list', ['fields'=>['id']]);
        }
        $this->set('propCount',$propCount);
    }

    /**
     * view a journal
     * @param int $id
	 * @return void
	 */
    public function view(int $id)
    {
        $data=$this->Journal->find('first',['conditions'=>['Journal.id'=>$id]]);
        if($this->request->is('ajax')) {
            header('Content-Type: application/json');
            echo "[".json_encode($data)."]";exit;
        }
        $this->set('data',$data);
    }

	// functions requiring login (not in Auth::allow)

	/**
	 * add journal function
	 * @return void
	 */
	public function add()
	{
		if($this->request->is('post')) {
			if($this->Journal->add($this->request->data)) {
				$this->Flash->set('The journal has been added');
				$this->redirect(['action' => 'index']);
			} else {
				$this->Flash->set('The journal could not be added.');
			}
		}
	}

}
