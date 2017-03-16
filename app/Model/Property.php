<?php

/**
 * Class Property
 * Property model
 */
class Property extends AppModel
{

    public $belongsTo = ['Quantity','Eqntype'];

    public $hasMany = ['Data','Propertytype','Rulesnippet'];

}