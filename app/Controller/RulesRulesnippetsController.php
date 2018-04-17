<?php

/**
 * Class RulesnippetsController
 *
 */
class RulesRulesnippetsController extends AppController
{
    public $uses = ['RulesRulesnippet'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    public function view($id)
    {
        $rrs=$this->RulesRulesnippet->find('first',['conditions'=>['RulesRulesnippet.id'=>$id]]);
        debug ($rrs);exit;
    }

}