<?php
/**
 * Created by PhpStorm.
 * User: n00002621
 * Date: 5/28/15
 * Time: 9:59 AM
 */

class SystemsController extends AppController {

    public $uses=['System','SubstancesSystem'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * View a system
     */
    public function view($id)
    {
        $contain=[
            'Substance',
            'Dataset'=>['conditions'=>['Dataset.propertytype_id'=>5],
                'Propertytype','Reference','Dataseries'=>['Datapoint'=>['Condition'=>['Unit'],'Data'=>['Unit'],'Setting'=>['Unit']]]
            ]

        ];
        $temp=$this->System->find('first',['conditions'=>['System.id'=>$id],'contain'=>$contain]);
        $data=[];
        $sets=$temp['Dataset'];
        foreach($sets as $set) {
            $ref=$set['Reference']['doi'];
            foreach($set['Dataseries'] as $ser) {
                foreach($ser['Datapoint'] as $point) {
                    foreach($point['Condition'] as $cond) {
                        $cnum=$cond['number'];
                        $cunit=$cond['Unit']['symbol'];
                    }
                    foreach($point['Data'] as $dpoint) {
                        $dnum=$dpoint['number'];
                        $dunit=$dpoint['Unit']['symbol'];
                    }
                    foreach($point['Setting'] as $spoint) {
                        $snum=$spoint['number'];
                        $sunit=$spoint['Unit']['symbol'];
                    }
                    $data['Data'][]=['ref'=>$ref,'condition'=>['n'=>$cnum,'u'=>$cunit],
                                'data'=>['n'=>$dnum,'u'=>$dunit],'setting'=>['n'=>$snum,'u'=>$sunit]];
                }
            }
        }
        $data['System']=$temp['System'];
        $data['Substance']=$temp['Substance'];
        //debug($data);exit;
        $this->set('data',$data);
    }

    /**
     * View index of systems
     */
    public function index()
    {
        $data=$this->System->find('list', ['fields'=>['id','name'],'order'=>['name']]);
        $this->set('data',$data);
    }

    /**
     * Check for unique and duplicate systems
     * @param int $offset
     * @param int $limit
     */
    public function duplicates($offset=0,$limit=100)
    {
        $syss=$this->System->find('all',['order'=>['name'],'contain'=>['Substance'=>['order'=>'Substance.id','fields'=>['id','name']]],'limit'=>$limit,'offset'=>$offset,'recursive'=>-1]);
        //debug($syss);exit;
        $unique=[];
        foreach($syss as $s) {
            $sys=$s['System'];
            $sub=$s['Substance'];
            if(count($sub)==1) {
                $unique[$sub[0]['id']][]=$sys['id'];
            } elseif(count($sub)==2) {
                $unique[$sub[0]['id'].':'.$sub[1]['id']][]=$sys['id'];
            }
        }
        echo "Unique: ".count($unique)."<br />";
        $probs=[];
        foreach($unique as $ids=>$systems) {
            if(count($systems)>1) {
                $probs[$ids]=$systems;
                foreach($systems as $system) {
                    $this->System->id=$system;
                    $this->System->saveField('identifier',$ids);
                    $this->System->clear();
                }
            }
        }
        echo "Probs: ".count($probs)."<br />";
        //foreach($probs as $prob) {

        //}
        debug($probs);
        exit;
    }

    public function clean($offset=0,$limit=100)
    {
        $syss=$this->System->find('all',['order'=>['System.id'],'contain'=>['Dataset'=>['fields'=>['Dataset.id']]],'limit'=>$limit,'offset'=>$offset,'recursive'=>-1]);
        //debug($syss);exit;
        $orphans=[];
        foreach($syss as $s) {
            $sys=$s['System'];
            $set=$s['Dataset'];
            if(empty($set)) {
                //$orphans[]=$sys;
                debug($s);
                $this->System->delete($sys['id'],false);
                $this->SubstancesSystem->deleteAll(['system_id'=>$sys['id']],false);
            } else {
                //debug($sys);
            }
        }
        //echo count($orphans);
        //debug($orphans);
        exit;
    }

    public function rename($offset=0,$limit=5)
    {
        $syss=$this->System->find('all',['order'=>['name'],'contain'=>['Substance'=>['fields'=>['id','name'],'order'=>['name']]],'limit'=>$limit,'offset'=>$offset,'recursive'=>-1]);
        //debug($syss);exit;
        foreach($syss as $s) {
            $sys=$s['System'];
            $sub=$s['Substance'];
            if(count($sub)==1) {
                $name=$sub[0]['name']." (pure)";
                $this->System->id=$sys['id'];
                $this->System->saveField('name',$name);
                if($sys['description']=='')     { $this->System->saveField('description','Single substance'); }
                if($sys['type']=='')            { $this->System->saveField('type','Single phase fluid'); }
                $this->System->saveField('identifier',str_pad($sub[0]['id'],5,'0',STR_PAD_LEFT));
                $this->System->clear();
            } elseif(count($sub)==2) {
                $name=$sub[0]['name']." and ".$sub[1]['name'];
                $this->System->id=$sys['id'];
                $this->System->saveField('name',$name);
                if($sys['description']=='')     { $this->System->saveField('description','Mixture of two substances'); }
                if($sys['type']=='')            { $this->System->saveField('type','Single phase fluid'); }
                $this->System->saveField('identifier',str_pad($sub[0]['id'],5,'0',STR_PAD_LEFT).":".str_pad($sub[1]['id'],5,'0',STR_PAD_LEFT));
                $this->System->clear();
            }
            echo $name."<br />";
        }
        exit;
    }
}