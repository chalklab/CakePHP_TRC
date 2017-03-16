<?php

/**
 * Class Dataseries
 * Dataseries model
 */
class Dataseries extends AppModel
{
    // Data as not linked SJC 7/2/16
    public $hasMany = [
        'Datapoint'=> [
            'foreignKey' => 'dataseries_id',
            'dependent' => true,
        ],
        'Condition'=> [
            'foreignKey' => 'dataseries_id',
            'dependent' => true,
        ],
        'Setting'=> [
            'foreignKey' => 'dataseries_id',
            'dependent' => true,
        ],
        'Annotation'=> [
            'foreignKey' => 'dataseries_id',
            'dependent' => true,
        ]
    ];

    public $belongsTo = ['Dataset'];
}