<?php

/**
 * Class Dataset
 * Parameter model
 */
class Dataset extends AppModel
{

    // Removed datapoint from hasMany 7/2/16 SJC (no data in table)
    // Added datasystem as it is a useful table for aggregating data
    public $hasMany = [
        'Dataseries'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'DataSystem'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'Annotation'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'Sampleprop'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'Reactionprop'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'Chemical'=> [
            'foreignKey'=> 'orgnum','source',
            'dependent' => true
        ]
    ];

    public $belongsTo = ['System','Reference','File'];

}
