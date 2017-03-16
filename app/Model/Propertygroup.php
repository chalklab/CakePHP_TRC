<?php

/**
 * Class Propertygroup
 * Propertygroup model Testing
 */
class Propertygroup extends AppModel
{

    public $hasMany = ['Propertytype'];

    public $hasAndBelongsToMany = ['Publication'];

}
