<?php

/**
 * Class Propertytype
 * PropertyType model Testing
 */
class Propertytype extends AppModel
{

    public $belongsTo = ['Property','Propertygroup','Ruleset'];

    public $hasMany = ['Parameter','Variable','SuppParameter','Dataset'];

    public $virtualFields=['namecount' => 'CONCAT(method," (",code,")")'];

}
