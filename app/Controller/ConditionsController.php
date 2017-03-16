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
        $c=['Datapoint'=>['fields'=>['id','dataseries_id'],
            'Dataseries'=>[
                'Dataset'=>[
                    'System'
                ]
            ]
        ],
            'Property',
            "Unit"
        ];
        $data=$this->Condition->find('all',['conditions'=>['Condition.property_id'=>$id,'Condition.unit_id'=>$uid],'contain'=>$c,'recursive'=>-1]);
        $sets=[];
        foreach($data as $datum) {
            $dsid=$datum['Datapoint']['Dataseries']['Dataset']['id'];
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