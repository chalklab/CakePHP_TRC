<?php

/**
 * Class ReportController
 * Actions related to reports
 * @author Stuart Chalk <schalk@unf.edu>
 *
 */
class ReportsController extends AppController
{
    public $uses=['Report'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * List the reports
     */
    public function index()
    {
        $c=['Publication'];$f=['Report.id','Report.title','Publication.title'];
        $data=$this->Report->find('list',['fields'=>$f,'contain'=>$c,'order'=>['Report.title'],'recursive'=>2]);
        $this->set('data',$data);
    }

    /**
     * Add a new report
     */
    public function add()
    {
        if(!empty($this->request->data)) {
            $this->Report->create();
            $this->Report->save($this->request->data);
            $this->redirect('/properties');
        } else {
            $data=$this->Publication->find('list',['fields'=>['id','name'],'order'=>['name']]);
            $this->set('data',$data);
        }
    }

    /**
     * View a property
     * @param $id
     */
    public function view($id)
    {
        $c=['Publication'=>['Propertygroup'],'Dataset'=>['System','Reference','Propertytype'=>['Property']]];
        $data=$this->Report->find('first',['conditions'=>['Report.id'=>$id],'contain'=>$c,'recursive'=> -1]);
        $this->set('data',$data);
    }

    /**
     * Update a property
     * @param $id
     */
    public function update($id)
    {
        if(!empty($this->request->data)) {
            $this->Report->id=$id;
            $this->Report->save($this->request->data);
            $this->redirect('/properties/view/'.$id);
        } else {
            $data=$this->Report->find('first',['conditions'=>['Report.id'=>$id]]);
            $this->set('data',$data);
        }

    }

    /**
     * Recent Reports
     * @param integer $l
     * @return mixed
     */
    public function recent($l=6)
    {
        $data=$this->Report->find('list',['order'=>['updated'=>'desc'],'limit'=>$l]);
        $this->set('data',$data);
        if($this->request->params['requested']) { return $data; }
    }

    /**
     * Delete a property
     * @param $id
     */
    public function delete($id)
    {
        $c=['Dataset'=>['fields'=>['id'],
                'Dataseries'=>['fields'=>['id'],
                    'Condition'=>['fields'=>['id']],
                    'Datapoint'=>['fields'=>['id'],
                        'Data'=>['fields'=>['id']],
                        'Condition'=>['fields'=>['id']],
                        'Setting'=>['fields'=>['id']]],
                    'Setting']]];
        $data=$this->Report->find('first',['conditions'=>['Report.id'=>$id],'contain'=>$c,'recursive'=>-1]);
        if($this->Report->delete($id)) {
            $this->Flash->deleted('Report '.ltrim($id,"0").' deleted!');
        } else {
            $this->Flash->deleted('Report '.ltrim($id,"0").' could not be deleted!');
        }
        $this->redirect('/reports');
    }

    /**
     * Generates the data in SciData JSON-LD
     * @param $id
     */
    public function export($id)
    {
        $data=$this->Report->scidata($id);
        $id="s".str_pad($id,9,"0",STR_PAD_LEFT);
        $json=[];
        $json['report']=$data['Report'];
        unset($json['report']['page']);
        unset($json['report']['comment']);
        $pub=$data['Publication'];
        unset($pub['Propertygroup']);
        $json['publication']=$pub;
        $set=$data['Dataset'];
        $json['dataseries']=$set['Dataseries'];
        $json['datafile']=$set['File'];
        $json['reference']=$set['Reference'];
        $json['propertytype']=$set['Propertytype'];
        unset($json['system']['description']);
        unset($json['system']['type']);
        $json['system']=$set['System'];
        unset($json['propertytype']['Property']['Quantity']);
        header('Content-Type: application/json');
        echo json_encode($json);exit;
    }

    /**
     * Rename the system based on its substances
     * @param int $offset
     * @param int $limit
     */
    public function rename($offset=0,$limit=5)
    {
        $c=['Dataset'=>['System','Propertytype'=>['Variable'=>['conditions'=>['identifier'=>'Data'],'Property']]]];
        $reps=$this->Report->find('all',['fields'=>['Report.id','Report.title'],'conditions'=>['Report.title'=>null],'contain'=>$c,'limit'=>$limit,'offset'=>$offset,'recursive'=> -1]);
        //debug($reps);exit;
        foreach($reps as $r) {
            $rep=$r['Report'];
            $vars=$r['Dataset']['Propertytype']['Variable'];
            $sys=$r['Dataset']['System'];
            if(count($vars)==2) {
                $title=$vars[0]['Property']['name']." and ".$vars[1]['Property']['name'].": ".$sys['name'];
            } else {
                $title=$vars[0]['Property']['name'].": ".$sys['name'];
            }
            $this->Report->id=$rep['id'];
            $this->Report->saveField('title',$title);
            $this->Report->clear;
            echo $title."<br />";
        }
        exit;
    }

    /**
     * Find and remove (via Report) all datasets that are not linked to a file
     * @param $l
     */
    public function nofile($l)
    {
        $query="SELECT d.*,f.id FROM datasets as d left join files as f on d.file_id=f.id where f.id is null limit ".$l;
        $res=$this->Report->query($query);
        foreach($res as $r) {
            $rid=$r['d']['report_id'];
            if($this->Report->delete($rid)) {
                echo $rid." Deleted!<br />";
            };
        }
        echo count($res);debug($res);exit;
    }
}
