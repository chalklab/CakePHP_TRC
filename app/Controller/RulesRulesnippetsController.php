<?php

/**
 * Class RulesnippetsController
 *
 */
class RulesRulesnippetsController extends AppController
{
    public $uses = ['RulesRulesnippet'];

    public function view($id)
    {
        $rrs=$this->RulesRulesnippet->find('first',['conditions'=>['RulesRulesnippet.id'=>$id]]);
        debug ($rrs);exit;
    }

}