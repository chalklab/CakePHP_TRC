<?php

/**
 * Class Property
 * Property model
 */
class Property extends AppModel
{

    public $belongsTo = ['Quantity'];

    public $hasMany = ['Data','Propertytype','Rulesnippet'];

    public function getfield($field,$conds)
    {
        $j=$this->find('first',['conditions'=>$conds,'recursive'=>-1]);
        if(!empty($j)) {
            return $j['Property'][$field];
        } else {
            return false;
        }
    }

}