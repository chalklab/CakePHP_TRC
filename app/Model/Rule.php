<?php

/**
 * Class Rule
 * Rule model
 */
class Rule extends AppModel
{

    public $belongsTo=['Ruletemplate','User'];

    public $hasAndBelongsToMany=['Ruleset'];

    public $hasMany=[
        'RulesRulesnippet'=>[
            'foreignKey' => 'rule_id',
            'dependent' => true
        ]
    ];

    // Create the regex for a rule from its template and snippets
    public function regex($id)
    {
        $c=['Ruletemplate',
            'RulesRulesnippet'=>[
                'Rulesnippet'
            ]
        ];
        $rule=$this->find('first',['conditions'=>['Rule.id'=>$id],'contain'=>$c,'recursive'=>-1]);
        $tregex=$rule['Ruletemplate']['regex'];
        foreach($rule['RulesRulesnippet'] as $rulesnip) {
            $x=$rulesnip['Rulesnippet']['regex'];
            if($rulesnip['optional']=="yes") { $x.="?"; }
            $b="@B".$rulesnip['block']."@";
            $tregex=str_replace($b,$x,$tregex);
        }
        return $tregex;
    }

    // Process rules in a set to generate the arrays needed for processing a textfile
    public function setdata($rules)
    {
        // Get regexes, actions, rows, blocks, types, fields, datatypes etc...
        $regexes=$actions=$rows=$layouts=$blocks=$rmodes=$ropts=$sopts=$types=$fields=[];
        $datatypes=$rmodes=$smodes=$scidata=$properties=$units=$cmpdnums=$cols=[];
        foreach($rules as $rule) {
            $step=$rule['RulesRuleset']['step'];
            // Generate the regexes (from the template and the snippets)
            $regexes[$step]=$this->regex($rule['id']);
            $actions[$step]=$rule['RulesRuleset']['action'];
            $rows[$step]=$rule['RulesRuleset']['rows'];
            $layouts[$step]=$rule['RulesRuleset']['layout'];
            $blocks[$step]=$rule['Ruletemplate']['blocks'];
            if($rule['RulesRuleset']['action']=="OPTIONALSTEP") {
                $ropts[$step]="yes";
            } else {
                $ropts[$step]="no";
            }
            $rmodes[$step]=$rule['mode'];
            $cols[$step]=$rule['cols'];
            $types[$step]=$fields[$step]=$datatypes[$step]=$units[$step]=[];
            foreach($rule['RulesRulesnippet'] as $rulesnip) {
                $block=$rulesnip['block'];
                $snip=$rulesnip['Rulesnippet'];
                $smodes[$step][$block]=$snip['mode'];
                $scidata[$step][$block]=$rulesnip['scidata'];
                $sopts[$step][$block]=$rulesnip['optional'];
                if(!empty($snip['Metadata'])) {
                    $types[$step][$block]="metadata";
                    $fields[$step][$block]=$snip['Metadata']['label'];
                    $metadata[$step][$block]=$snip['Metadata']['id'];
                    $datatypes[$step][$block]=$snip['Metadata']['datatype'];
                }
                if(!empty($snip['Property'])) {
                    $types[$step][$block]="data";
                    $fields[$step][$block]=$snip['Property']['label'];
                    $properties[$step][$block]=$snip['Property']['id'];
                    $datatypes[$step][$block]='float';
                } elseif(!empty($rulesnip['Property'])) {
                    $types[$step][$block]="data";
                    $fields[$step][$block]=$rulesnip['Property']['label'];
                    $properties[$step][$block]=$rulesnip['Property']['id'];
                    $datatypes[$step][$block]='float';
                }
                if(!empty($snip['Unit'])) {
                    $units[$step][$block]=$snip['Unit']['id'];
                } elseif(!empty($rulesnip['Unit'])) {
                    $units[$step][$block]=$rulesnip['Unit']['id'];
                }
            }
        }

        // Sort the arrays
        ksort($actions);ksort($blocks);ksort($cmpdnums);ksort($datatypes);ksort($fields);
        ksort($metadata);ksort($regexes);ksort($rmodes);ksort($rows);ksort($properties);
        ksort($scidata);ksort($smodes);ksort($types);ksort($units);ksort($layouts);ksort($ropts);ksort($sopts);

        // Aggregate the data into one array
        $setdata=['regexes'=>$regexes,'actions'=>$actions,'rows'=>$rows,'blocks'=>$blocks,'types'=>$types,
            'fields'=>$fields,'datatypes'=>$datatypes,'rmodes'=>$rmodes,'smodes'=>$smodes,'units'=>$units,
            'properties'=>$properties,'metadata'=>$metadata,'scidata'=>$scidata,'cmpdnums'=>$cmpdnums,
            'layouts'=>$layouts,'ropts'=>$ropts,'sopts'=>$sopts,'cols'=>$cols];

        return $setdata;
    }

}