<?php

/**
 * Class Rule
 * Rulesnippet model
 */
class Rulesnippet extends AppModel
{

    //public $hasAndBelongsToMany = ['Rule'];

    public $belongsTo=['Metadata','Property','Unit'];

    public $hasMany=['RulesRulesnippet'];

}