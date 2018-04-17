<?php

/**
 * Class PropertiesController
 * Actions related to dealing with chemical properties
 * @author Stuart Chalk <schalk@unf.edu>
 */
class PropertiesController extends AppController
{
    public $uses=['Property','Quantity','Dataset','Data','System','Unit'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * List the properties
     */
    public function index()
    {
        $c=['Property'=>['fields'=>['name'],'order'=>['name']]];
        $data=$this->Data->find('all',['fields'=>['id','property_id','COUNT(property_id) as pcount'],'group'=>['property_id'],'contain'=>$c,'recursive'=>-1]);
        $this->set('data',$data);
    }

    /**
     * Add a new property
     */
    public function add()
    {
        if(!empty($this->request->data)) {
            $this->Property->create();
            $this->Property->save($this->request->data);
            $this->redirect('/properties');
        } else {
            $data['ps']=$this->Property->find('list',['fields'=>['id','name'],'order'=>['name']]);
            $data['qs']=$this->Quantity->find('list',['fields'=>['id','name'],'order'=>['name']]);
            $this->set('userid',$this->Auth->user('id'));
            $this->set('data',$data);
        }
    }

    /**
     * View a property
     * @param $id
     */
    public function view($id)
    {
        $c=['Datapoint'=>['fields'=>['id','dataseries_id'],
                'Dataseries'=>[
                    'Dataset'=>[
                        'System'
                    ]
                ]
            ],
            'Property'
        ];
        $data=$this->Data->find('all',['conditions'=>['Data.property_id'=>$id],'fields'=>['id','number','property_id','datapoint_id'],'contain'=>$c,'recursive'=> -1]);
        //debug($data);exit;

        $systems = [];
        $counts =[];
        foreach ($data as $datum) {
            $sid = $datum['Datapoint']['Dataseries']['Dataset']['System']['id'];
            $name = $datum['Datapoint']['Dataseries']['Dataset']['System']['name'];
            $keys = array_keys($systems);

            if (in_array($sid, $keys)) {
                $counts[$sid]++;
            } else {
                $systems[$sid]=$name;
                $counts[$sid] = 1;
            }
        }
        asort($systems);
        $data=$this->Property->find('first',['conditions'=>['Property.id'=>$id],'recursive'=>0]);
        $this->set('property',$data['Property']);
        $this->set('systems', $systems);
        $this->set('counts', $counts);
        $this->set('id',$id);
    }

    /**
     * Update a property
     * @param $id
     */
    public function update($id)
    {
        if(!empty($this->request->data)) {
            //echo "<pre>";print_r($this->request->data);echo "</pre>";exit;
            $this->Property->id=$id;
            $this->Property->save($this->request->data);
            $this->redirect('/properties/view/'.$id);
        } else {
            $data=$this->Property->find('first',['conditions'=>['Property.id'=>$id]]);
            $this->set('data',$data);
        }

    }

    /**
     * Delete a property
     * @param $id
     */
    public function delete($id)
    {
        $this->Property->delete($id);
        $this->redirect('/properties');
    }

    public function getunit($id=0)
    {
        if($id==0) {
            echo false;exit;
        } else {
            $prop=$this->Property->find('list',['fields'=>['id','quantity_id'],'conditions'=>['id'=>$id]]);
            $units=$this->Unit->find('list',['fields'=>['id','name'],'conditions'=>['quantity_id'=>$prop[str_pad($id,5,"0",STR_PAD_LEFT)]]]);
            echo json_encode($units);exit;
        }
    }

    /**
     * View an individual property system
     * @param integer $sid
     * @param integer $pid
     */
    public function system($sid,$pid)
    {
        $c=['Data'=>['conditions'=>['Data.property_id'=>$pid],'fields'=>['number','error'],
                'Unit'
            ],
            'Dataset'=>['fields'=>['id','reference_id'],'Reference'=>['fields'=>['citation']]]
        ];
        $j=[
            ['table'=>'data_systems',
                'alias'=>'DataSystem',
                'type'=>'inner',
                'conditions'=> [
                    'System.id = DataSystem.system_id'
                ]
            ],
            ['table'=>'data',
                'alias'=>'Data',
                'type'=>'inner',
                'conditions'=> [
                    'DataSystem.data_id = Data.id'
                ]
            ],
            ['table'=>'datasets',
                'alias'=>'Dataset',
                'type'=>'inner',
                'conditions'=> [
                    'DataSystem.dataset_id = Dataset.id'
                ]
            ]
        ];
        $data = $this->System->find('first',['conditions'=>['System.id'=>$sid],'contain'=>$c,'joins'=>$j,'recursive'=>-1]);
        $this->set('data', $data);
    }

    /**
     * Get id for term and send out as XML
     * @param $term
     */
    public function xml($term)
    {
        $meta=$this->Property->find("first",['fields'=>['id','label'],'conditions'=>['field like'=>'%"'.$term.'"%']]);
        header("Content-Type: application/xml");
        echo "<m><id>".$meta['Property']['id']."</id><label>".$meta['Property']['label']."</label></m>";
        exit;
    }

    public function test()
	{
		$prop['propstr']='Mole fraction 1';
		$propid=$this->Property->getfield('id','%"'.trim($prop['propstr']).'"%','field like');
		debug($propid);exit;
		
	}
}
