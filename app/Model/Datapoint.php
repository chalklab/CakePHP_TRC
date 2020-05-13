<?php

/**
 * Class Dataseries
 * Dataseries model
 */
class Datapoint extends AppModel
{

    /**
     * Link annotations, conditions, data, and setting as dependent so they get deleted when the datapoint does
     * @var array
     */
    public $hasMany = [
        'Annotation'=> [
            'foreignKey' => 'datapoint_id',
            'dependent' => true,
        ],
        'Condition'=> [
            'foreignKey' => 'datapoint_id',
            'dependent' => true,
        ],
        'Data'=> [
            'foreignKey' => 'datapoint_id',
            'dependent' => true,
        ],
        'Setting'=> [
            'foreignKey' => 'datapoint_id',
            'dependent' => true,
        ]];

    public $belongsTo = ['Dataseries','Dataset'];

}