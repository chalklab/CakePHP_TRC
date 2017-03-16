<?php

/**
 * Class PropertytypesController
 */
class PropertytypesController extends AppController
{
    public $uses=['Property','Publication','Propertytype','Dataset','Propertygroup'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * Add a new property type
     */
    public function add()
    {
        if($this->request->is('post'))
        {
            $states=implode(",",$this->request->data['PropertyType']['states']);
            $phases=implode(",",$this->request->data['PropertyType']['phases']);
            $this->request->data['PropertyType']['states']=$states;
            $this->request->data['PropertyType']['phases']=$phases;
            $this->PropertyType->create();
            if($this->PropertyType->save($this->request->data))
            {
                $this->Session->setFlash('Property type created.');
                $this->redirect(['action' => 'index']);
            }
            else
            {
                $this->Session->setFlash('Property type could not be created.');
            }
        }
        else
        {
            $temp=$this->PropertyType->getColumnType('states');
            preg_match_all("/'(.*?)'/", $temp, $sets);
            $states=$this->Utils->ucfarray($sets[1]);
            $this->set('states',$states);

            $temp=$this->PropertyType->getColumnType('phases');
            preg_match_all("/'(.*?)'/", $temp, $sets);
            $phases=$this->Utils->ucfarray($sets[1]);
            $this->set('phases',$phases);

            $properties=$this->Property->find('list',['fields'=>['id','name']]);
            $this->set('properties',$properties);


        }
    }

    /**
     * View a property type
     */
    public function view($id)
    {
        $c=[
            'Parameter'=>
                ['Unit'],
            'Variable'=>
                ['Unit'],
            'SuppParameter'=>
                ['Unit'],
            'Dataset'=>[
                'System',
                'Report'
            ],
            'Ruleset','Property'
        ];
        $data=$this->Propertytype->find('first',['conditions'=>['Propertytype.id'=>$id],'contain'=>$c]);
        $systems=[];
        foreach ($data['Dataset'] as $set){
            if(!isset($systems[$set['System']['name']]))
                $systems[$set['System']['name']]=$set['System'];
            $systems[$set['System']['name']]['Report'][]=$set['Report'];
            $systems[$set['System']['name']]['Dataset'][]=$set;
        }
        ksort($systems,SORT_NATURAL);
        $data['System']=$systems;
        //echo "<pre>".print_r($data)."</pre>";exit;
        $this->set('data',$data);
    }

    /**
     * Update a property type
     */
    public function update($id)
    {
        if(!empty($this->request->data))
        {
            $states=implode(",",$this->request->data['Propertytype']['states']);
            $phases=implode(",",$this->request->data['Propertytype']['phases']);
            $this->request->data['Propertytype']['states']=$states;
            $this->request->data['Propertytype']['phases']=$phases;
            $this->Propertytype->id=$id;
            $this->Propertytype->save($this->request->data);
            $this->redirect('/propertytypes/view/'.$id);
        } else {
            $data=$this->Propertytype->find('first',['conditions'=>['Propertytype.id'=>$id],'recursive'=>3]);
            $this->set('data',$data);

            $temp=$this->Propertytype->getColumnType('states');
            preg_match_all("/'(.*?)'/", $temp, $sets);
            $states=$this->Utils->ucfarray($sets[1]);
            $this->set('states',$states);

            $temp=$this->Propertytype->getColumnType('phases');
            preg_match_all("/'(.*?)'/", $temp, $sets);
            $phases=$this->Utils->ucfarray($sets[1]);
            $this->set('phases',$phases);

            $properties=$this->Property->find('list',['fields'=>['id','name']]);
            $this->set('properties',$properties);

            $this->set('id',$id);
        }
    }

    /**
     * Delete a property type
     */
    public function delete($id)
    {
        $this->Propertytype->delete($id);
        $this->redirect(['action' => 'index']);
    }

    /**
     * View index of property types
     */
    public function index()
    {
        $data=$this->Propertytype->find('list',['fields'=>['id','namecount','propertygroup_id'],'order'=>['propertygroup_id']]);
        $groups=$this->Propertygroup->find('list',['fields'=>['id','description']]);
        $propCount=[];
        foreach($data as $group=>$type) {
            foreach ($type as $id => $prop) {
                $propCount[$id] = $this->Dataset->find('count', ['conditions' => ['Dataset.propertytype_id' => $id]]);
            }
        }
        $this->set('data',$data);
        $this->set('groups',$groups);
        $this->set('propCount',$propCount);
    }
}

?>
