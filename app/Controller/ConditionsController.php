<?php

/**
 * Class ConditionsController
 */
class ConditionsController extends AppController
{
    public $uses=['Condition'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * Index
     */
    public function index(){
        $data=$this->conditions->find('list', ['fields'=>['id','property_name','number','unit_id'],'order'=>['property_name']]);
        $this->set('data',$data);

        $propCount=[];
        foreach ($data as $id => $prop) {
            $propCount[$id] = $this->Units->find('list', ['fields'=>['unit_id']]);
        }
        $this->set('propCount',$propCount);
    }

    /**
     * Add new condition
     */
    public function add()
    {
        if($this->request->is('post'))
        {
            $this->Condition->create();
            if($this->Condition->save($this->request->data))
            {
                $this->Flash->set('Condition created.');
                $this->redirect(['action'=>'add']);
            } else {
                $this->Flash->set('Condition could not be created.');
            }
        }
    }

    /**
     * View a condition
     * @param $id
     * @param $uid
     */
    public function view($id,$uid)
    {
        $c=['Datapoint'=>['fields'=>['id'],
            'Dataseries'=>[
                'Dataset'=>[
                    'System'
                ]
            ]
        ],
            'Property',
            "Unit"
        ];
        $cond=['Condition.property_id'=>$id,'Condition.unit_id'=>$uid];
        $data=$this->Condition->find('all',['conditions'=>$cond,'contain'=>$c,'recursive'=>-1]);
        $sets=[];
        foreach($data as $datum) {
            $dsid=$datum['Datapoint']['Dataset']['id'];
            $sets[$dsid]=$dsid;
        }
        $this->set('data',$sets);
    }

    /**
     * Update a condition
     * @param $id
     */
    public function update($id)
    {
        if(!empty($this->request->data))
        {
            $this->Condition->id=$id;
            $this->Condition->save($this->request->data);
            $this->redirect('/conditions/view'.$id);
        } else {
            $data=$this->Condition->find('first',['conditions'=>['Condition.id'=>$id]]);
            $this->set('data',$data);
        }
    }

    /**
     * Delete a condition
     * @param $id
     */
    public function delete($id)
    {
        $this->Condition->delete($id);
        $this->redirect(['action'=>'add']);
    }

}
