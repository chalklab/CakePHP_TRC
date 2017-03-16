<?php

/**
 * Class RulesetsController
 * Ruleset Controller
 */
class RulesetsController extends AppController
{
    public $uses=['Ruleset','Rule','Property','RulesRuleset','Metadata','Rulesnippet','Ruletemplate'];

    public $actions=[ //Usable actions both here and in the form
        "NEXTLINE"=>"Next line",
        "NEXTLINECHOICE"=>"Next line choice",
        "NEXTLINESTARTLOOP"=>"Next line (with loop)",
        "NEXTLINESTARTLOOPCHOICE"=>"Next line choice (with loop)",
        "OPTIONALSTEP"=>"Optional line",
        "REPEATEVERYOTHERLINE"=>"Repeat every other line",
        "REPEATEVERYOTHERLINECHOICE"=>"Repeat every other line (choice)",
        "OPTIONALREPEATEVERYOTHERLINECHOICE"=>"Optional repeat every other line (choice)",
        "REPEATUNTILNEXTSTEP"=>"Repeat until match of next step/end",
        "OPTIONALREPEATUNTILNEXTSTEP"=>"Repeat until match of next step/end (optional)",
        "REPEATWITHOPTIONAL"=>"Repeat with optional line"
    ];

    /**
     * List the properties
     */
    public function index()
    {
        $data=$this->Ruleset->find('list',['fields'=>['id','name'],'order'=>['name'],'recursive'=>1]);
        if($this->request->is('requested')) {
            return $data;
        }
        $this->set('data',$data);
    }

    /**
     * Ruleset editor
     * @param string $id
     */
    public function edit($id="")
    {
        if (!empty($this->data)) {
            $data = [];
            //var_dump($data);
            // die();
            //set the version in code
            $data['Ruleset']['version'] = 1;
            $data['Ruleset']['name'] = $this->data['name'];
            $data['Ruleset']['comment'] = $this->data['comment'];
            //create and save the ruleset
            $ruleset = json_decode($this->data['ruleset'], true);
            if ($id != "") {
                $this->Ruleset->id = $id;
            } else {
                $this->Ruleset->create();
            }
            if ($this->Ruleset->save($data)) {
                if($id != ""){
                    $this->rules_rulesets->deleteAll(['rules_rulesets.ruleset_id' => $id],false);
                }
                var_dump($ruleset);
                //loop through lines
                foreach($ruleset as $i => $line){
                    //loop through rules
                    foreach($line as $p=>$rule){
                        $rules_rulesets = array();
                        $rules_rulesets['rules_rulesets']['rule_id'] = $rule['id']; //take all the rules, and assign their values to a new array so that we can save this in the join table
                        $rules_rulesets['rules_rulesets']['line'] = $i;
                        $rules_rulesets['rules_rulesets']['step'] = $p;
                        $rules_rulesets['rules_rulesets']['ruleset_id'] = $this->Ruleset->id;
                        if(isset($rule['options']))
                            $rules_rulesets['rules_rulesets']['options'] = json_encode($rule['options']);
                        $this->rules_rulesets->create();
                        $this->rules_rulesets->save($rules_rulesets['rules_rulesets']);
                    }
                }
                die("{'result':'success','id':'".$this->Ruleset->id."'}");
            }
        }else {
            $rules = $this->Rule->find('all');
            $ruleset = [];
            if($id != ""){
                $ruleset = $ruleset=$this->Ruleset->find('first',['conditions'=>['Ruleset.id'=>$id],'recursive'=>2]);
                $rData= $ruleset['Ruleset'];
                $pData= $ruleset['Propertytype'];
                $ruleset = $this->Ruleset->generateRulesetArray($ruleset,false);
                $ruleset['Ruleset']= $rData;
                $ruleset['Propertytype'] = $pData;
            }

            $this->set('ruleset', $ruleset);
            $this->set('rules', $rules);
            $properties = $this->Property->find('list', ['fields' => ['id', 'name']]);
            $this->set('properties', $properties);
            $path=Configure::read('path');
            $this->set('path',$path);
            $this->set('actions',$this->actionsNiceView); //load the actions the be useable during the view
        }
    }

    /**
     * Add a ruleset
     */
    public function add($id="")
    {
        if (!empty($this->request->data)) {
            //debug($this->request->data);exit;
            // Split out data
            $data['Ruleset']=$this->request->data['Ruleset'];
            $rules=$this->request->data['Rule'];
            if($data['Ruleset']['xslt']==0||$data['Ruleset']['xslt']=='') {
                $data['Ruleset']['xslt']=null;
            }
            // Add ruleset
            $this->Ruleset->create();
            if ($this->Ruleset->save($data)) {
                $rsid=$this->Ruleset->id;
                foreach($rules as $rule) {
                    $rule['ruleset_id']=$rsid;
                    $this->RulesRuleset->create();
                    $this->RulesRuleset->save(['RulesRuleset'=>$rule]);
                    $this->RulesRuleset->clear();
                }
            }
            $this->redirect('/rulesets/view/'.$rsid);
        } else {
            // Get existing rules
            $rules = $this->Rule->find('list',['fields'=>['id','name'],'order'=>['name']]);
            $this->set('rules', $rules);
            // Get properties
            $properties = $this->Property->find('list', ['fields' => ['id', 'name'],'order'=>['name']]);
            $this->set('properties', $properties);
            $path=Configure::read('path');
            $this->set('path',$path);
            $this->set('actions',$this->actions);
            $this->set('userid',$this->Auth->user('id'));
        }
    }

    /**
     * View a Ruleset
     * @param $id
     */
    public function view($id)
    {
        $c=['Rule'=>['Ruletemplate','RulesRulesnippet'=>['Rulesnippet']]];
        $data=$this->Ruleset->find('first',['conditions'=>['Ruleset.id'=>$id],'contain'=>$c,'recursive'=>-1]);

        $this->set('data',$data);
        $this->set('actions',$this->actions);
    }

    /**
     * Add a ruleset (old version)
     */
    public function addold()
    {
        if (!empty($this->data)) {

            //I start abusing cake php here
            $data=$this->data;
            //var_dump($data);
           // die();
            //set the version in code
            $data['Ruleset']['version']=1;

            //create and save the ruleset
            $this->Ruleset->create();
            if ($this->Ruleset->save($data)) {
                $i=0;

                //loop through the rules and add them to the join table properly
                while($i<count($data['rules_rulesets']['rule_id'])){

                    //more abuse begins here
                    $rules_rulesetsData=array();
                    $rules_rulesetsData['rules_rulesets']['rule_id']=$data['rules_rulesets']['rule_id'][$i]; //take all the rules, and assign their values to a new array so that we can save this in the join table
                    $rules_rulesetsData['rules_rulesets']['line']=$data['rules_rulesets']['line'][$i];
                    $rules_rulesetsData['rules_rulesets']['step']=$data['rules_rulesets']['step'][$i];
                    $rules_rulesetsData['rules_rulesets']['ruleset_id']=$this->Ruleset->id;
                    $this->rules_rulesets->create();
                    $this->rules_rulesets->save($rules_rulesetsData['rules_rulesets']);
                    $i++;
                }
                // Set a session flash message and redirect.
                $this->Session->setFlash('Ruleset '.$this->Ruleset->id.' Created!');
                return $this->redirect('/rulesets/view/'.$this->Ruleset->id);
            }
        } else {
            $rules=$this->Rule->find('list',['fields'=>['id','name']]);
            $this->set('rules',$rules);
            $properties=$this->Property->find('list',['fields'=>['id','name']]);
            $this->set('properties',$properties);
        }
    }

    /**
     * Update Ruleset
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        if (!empty($this->data)) {

            //I start abusing cake php here
            $data=$this->data;

            //set the version in code
            $data['Ruleset']['version']=1;

            //create and save the ruleset
            $this->Ruleset->id=$id;
            $correction = 0;
            if ($this->Ruleset->save($data)) {

                $i=0;
                $this->rules_rulesets->deleteAll(['rules_rulesets.ruleset_id' => $id],false);
                //loop through the rules and add them to the join table properly
                while($i<count($data['rules_rulesets']['rule_id'])){
                    if($data['rules_rulesets']['line'][$i] == 0)
                        $correction = 1;
                    //more abuse begins here
                    $rules_rulesetsData=array();
                    $rules_rulesetsData['rules_rulesets']['rule_id']=$data['rules_rulesets']['rule_id'][$i]; //take all the rules, and assign their values to a new array so that we can save this in the join table
                    $rules_rulesetsData['rules_rulesets']['line']=$data['rules_rulesets']['line'][$i] + $correction;
                    $rules_rulesetsData['rules_rulesets']['step']=$data['rules_rulesets']['step'][$i];
                    $rules_rulesetsData['rules_rulesets']['ruleset_id']=$this->Ruleset->id;
                    $this->rules_rulesets->create();
                    $this->rules_rulesets->save($rules_rulesetsData['rules_rulesets']);
                    $i++;
                }
                // Set a session flash message and redirect.
                $this->Session->setFlash('Ruleset '.$this->Ruleset->id.' Updated!');
                return $this->redirect('/rulesets/view/'.$this->Ruleset->id);
            }
        } else {
            $ruleset=$this->Ruleset->find('first',['conditions'=>['Ruleset.id'=>$id],'recursive'=>2]);
            $this->set('actions',$this->actionsNiceView); //load the actions the be useable during the view


            $newRules=array();
            //Correct rules to allow easy display
            foreach($ruleset['Rule'] as $rule){
                $i=$rule['RulesRuleset']['line'];
                if(!isset($newRules[$i])||!is_array($newRules[$i])){
                    $newRules[$i]=ARRAY(); //make sure we have an array to iterate here
                }
                $newRules[$i][]=$rule;
            }
            $ruleset['NewRules']=$newRules;

            $properties=$this->Property->find('list',['fields'=>['id','name']]);
            $this->set('properties',$properties);
            //  echo "<pre>";print_r($ruleset);echo "</pre>";
            $this->set('ruleset',$ruleset);
            $rules=$this->Rule->find('list',['fields'=>['id','name']]);
            $this->set('rules',$rules);
        }
    }

    /**
     * Delete a ruleset
     * @param string $id
     * @return mixed
     */
    public function delete($id="")
    {
        if($id!="") {
            $rules=$this->RulesRuleset->find('list',['fields'=>['id'],'conditions'=>['ruleset_id'=>$id]]);
            foreach($rules as $rid) {
                $this->RulesRuleset->delete($rid);
            }
            $this->Ruleset->delete($id);
        }
        return $this->redirect('/rulesets/index');
    }
}