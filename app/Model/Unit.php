<?php

/**
 * Class Unit
 * Unit model
 */
class Unit extends AppModel
{

	public $hasAndBelongsToMany = ['Quantity','Parameter','Variable'];

    public $hasMany=['Rulesnippet'];

}