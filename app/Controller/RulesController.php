<?php

/**
 * Class RulesController
 */
class RulesController extends AppController
{
    public $uses=['Rule','Metadata','RulesRulesnippet','Rulesnippet','Ruletemplate',"Property"];

    public $actions=[ //Usable actions both here and in the form
        "NEXTLINE",
        "USELAST",
        "USELASTLINE",
        "END",
        "SKIP",
        "STORE",
        "EXCEPTION",
        "STOREASHEADER",
        "USELASTLINEUNTIL",
        "STOREALL",
        "STOREALLASHEADER",
        "CONTINUE",
        "STOREALLASDATA",
        "NEXTRULE",
        "PREVIOUSLINE",
        "INCREASEMULTIPLIER"];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * Get an list of the currently defined rules
     */
    public function index()
    {
        $c=['Ruleset',
            'Ruletemplate',
            'RulesRulesnippet'=>[
                'Rulesnippet'
            ],
            'User'
        ];
        $rule=$this->Rule->find('all',['contain'=>$c,'recursive'=>-1,'order'=>'Rule.name']);
        $this->set('actions',$this->actions); //load the actions the be useable during the view
        $this->set('rules',$rule);
    }

    /**
     * View a rule
     * @param $id
     */
    public function view($id)
    {
        $c=['Ruleset', 'Ruletemplate','RulesRulesnippet'=>['Rulesnippet'],'User'];
        $rule=$this->Rule->find('first',['conditions'=>['Rule.id'=>$id],'contain'=>$c,'recursive'=>-1]); //get the first rule with id=$id
        if($this->request->is('ajax')) {
            header('Content-Type: application/json');
            echo "[".json_encode($rule)."]";
            exit;
        }
        $this->set('rule',$rule);
        $this->set('actions',$this->actions); //load the actions the be useable during the view
    }

    /**
     * Add a new rule
     * @return mixed
     */
    public function add()
    {
        if (!empty($this->request->data)) {
            //debug($this->request->data);exit;
            $data=$this->request->data['Rule'];
            $this->Rule->create();
            $snips=$data['snippet'];unset($data['snippet']);
            $props=$data['property'];$scidata=$data['scidata'];$units=$data['unit'];$opts=$data['optional'];
            if ($this->Rule->save($this->request->data)) {
                $rid=$this->Rule->id;
                foreach($snips as $block=>$rsid) {
                    $prop=$sci=$unit=$opt=null;
                    if($props[$block]!="") { $prop=$props[$block];}
                    if($scidata[$block]!="") { $sci=$scidata[$block];}
                    if($units[$block]!="") { $unit=$units[$block];}
                    if($opts[$block]!="") { $opt=$opts[$block];}
                    $fields=['rule_id'=>$rid,'rulesnippet_id'=>$rsid,'block'=>$block,
                        'property_id'=>$prop,'scidata'=>$sci,'unit_id'=>$unit,'optional'=>$opt];
                    $this->RulesRulesnippet->create();
                    $this->RulesRulesnippet->save(['RulesRulesnippet'=>$fields]);
                    $this->RulesRulesnippet->clear();
                }
                if(isset($this->request->data['json'])) {
                    die('{"id":'.$this->Rule->id.',"result":"success"}');
                }
                return $this->redirect('/rules/view/'.$this->Rule->id);
            }
        } else {
            // Get existing rules
            $rules = $this->Rule->find('all');
            $this->set('rules', $rules);
            // Get templates
            $templates = $this->Ruletemplate->find('list', ['fields' => ['id', 'name']]);
            $this->set('templates', $templates);
            // Get snippets
            $snippets = $this->Rulesnippet->find('list', ['fields' => ['id', 'name','mode'],'order'=>'name']);
            $this->set('snippets', $snippets);
            // ... and which of the snippets are data (which need a property assigned)
            $sci=['condition','data','suppdata','chemprop','error','seriescond','to_be_set','setting','eqnvariable','eqnterm','eqnprop','eqnvariablelimit'];
            $dataids = $this->Rulesnippet->find('list', ['fields' => ['id'],'conditions'=>['scidata'=>$sci]]);
            foreach($dataids as $key=>$id) { $dataids[$key]=(integer)$id; }
            $temp=implode(",",$dataids);
            $this->set('dataids',$temp);
            // Get properties
            $props = $this->Property->find('list', ['fields' => ['id', 'name'],'order'=>'name']);
            $this->set('props', $props);
            // Set other stuff
            $this->set('userid',$this->Auth->user('id'));
            $path=Configure::read('path');
            $this->set('path',$path);
            $this->set('actions',$this->actions);
        }
    }

    /**
     * Update a rule
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        if (!empty($this->request->data)) {
            $this->Rule->id=$id;
            if ($this->Rule->save($this->request->data)) {
                die('{"result":"success"}');
            }
        } else {
            return $this->redirect('/rules/view/'.$id);
        }
    }

    /**
     * Add a rule template
     */
    public function addtmpl()
    {
        $tmpls=$this->Ruletemplate->find('list',['fields'=>['id','name']]);
        $this->set('tmpls',$tmpls);
    }

    /**
     * Add a rule snippet
     */
    public function addsnip()
    {
        $snips=$this->Rulesnippet->find('list',['fields'=>['id','name']]);
        $meta=$this->Metadata->find('list',['fields'=>['id','name']]);
        $this->set('snips',$snips);
        $this->set('meta',$meta);
    }

    /**
     * Delete Rule
     * @param int $id
     * @return CakeResponse
     */
    public function delete($id=0)
    {
        if(!empty($id)&&$id!=0) {
            $this->Rule->delete($id);
        }
        return $this->render('/rules/index');
    }
}