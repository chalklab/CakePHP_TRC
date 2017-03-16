<?php

/**
 * Class Rule
 * Rule model
 */
class RulesRulesnippet extends AppModel
{

    public $belongsTo = ['Rule','Rulesnippet','Property','Unit'];

}
