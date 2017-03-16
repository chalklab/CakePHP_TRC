<?php

/**
 * Class Parameter
 * Parameter model
 */
class Parameter extends AppModel
{

    public $belongsTo = ['Propertytype','Property'];

    public $hasAndBelongsToMany = ['Unit'];

}