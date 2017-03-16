<?php

/**
 * Class Ruleset
 * Ruleset model
 */
class Ruleset extends AppModel
{

    public $hasAndBelongsToMany=['Rule'=>['unique'=>"keepExisting",'Order'=>'rules_rulesets.step DESC']];

    public $hasMany=['Propertytype','File'];

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
        "INCREASEMULTIPLIER",
        "STORELINE"
    ];

    /**
     * function generateRulesetArray
     * @param array $query an array returned by a find request for a ruleset
     * @param boolean $clean
     * @return array $config = an array that can be easily inserted into the Reader to parse a text file
     */
    public function generateRulesetArray($query,$clean = true){
        $neededFields=array(
            "action",
            "failure",
            "pattern",
            "valueName",
            "errorText",
            "required",
            "matchIndex",
            "matchMethod",
            "headerIndex",
            "skip",
            "options"
        );
        $newRules=array();
        $i=0;
        $correction = 0;
        //Correct rules to mimic the original config format
        foreach($query['Rule'] as $rule){
            $i=$rule['RulesRuleset']['line'];
            if($i == 0){
                $correction = 1;
            }
            $i += $correction;
            if(!isset($newRules[$i])||!is_array($newRules[$i])){
                $newRules[$i]=ARRAY(); //make sure we have an array to iterate here
                $newRules[$i][]=0;
            }
            $newRules[$i][]=$rule;
        }
        //usort($newRules, "sortRules");
        //remove unneeded information
        foreach($newRules as &$line){
              unset($line[0]);  
            foreach($line as &$rule){
                if(isset($rule['RulesRuleset']['options'])){
                    $rule['options'] = $rule['RulesRuleset']['options'];
                    $options = json_decode($rule['RulesRuleset']['options'],true);
                    if(!is_array($options))
                        $options = json_decode($options,true);
                    if(isset($options['a'])){
                        $rule['pattern'] = $options['a'].$rule['pattern'];
                    }
                    if(isset($options['b'])){
                        $rule['pattern'] = $rule['pattern'].$options['b'];
                    }
                }
                foreach($rule as $index=>&$field){
                    if($clean &&($index=="action"||$index=="failure")){
                        if($field!==null)
                            $field=$this->actions[$field]; //make action and failure have the right text
                    }
                    if($index=="matchMethod"){
                        if($field==0)
                            $field="preg_match"; //fix matchMethod
                        else
                            $field="preg_match_all";
                    }
                    if($clean &&(!in_array($index,$neededFields)||$field==''||$field==null)){
                        unset($rule[$index]);
                    }
                }
                unset($rule['RulesRuleset']); // Save memory
            }
        }

        $config=array();
        $config['Rules']=$newRules;
        return $config;
    }
}

function sortRules($a,$b)
{
    if (is_array($a) && is_array($b) && isset($a['RulesRuleset']) && isset($b['RulesRuleset']) && $a['RulesRuleset']['step'] >= $b['RulesRuleset']['step']) {
        if ($a['RulesRuleset']['step'] == $b['RulesRuleset']['step']) {
            return 0;
        } else {
            return 1;
        }
    } else {
        return -1;
    }
}