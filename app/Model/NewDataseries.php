<?php

/**
 * Class Dataseries
 * Dataseries model
 */
class NewDataseries extends AppModel
{
	public $useDbConfig='new';
	public $useTable='dataseries';

	// Data as not linked SJC 7/2/16
    public $hasMany = [
        'NewDatapoint'=> [
            'foreignKey' => 'dataseries_id',
            'dependent' => true,
        ],
        'NewCondition'=> [
            'foreignKey' => 'dataseries_id',
            'dependent' => true,
        ],
        'NewSetting'=> [
            'foreignKey' => 'dataseries_id',
            'dependent' => true,
        ],
        'NewAnnotation'=> [
            'foreignKey' => 'dataseries_id',
            'dependent' => true,
        ]
    ];

    public $belongsTo = [
    	'NewDataset'=> [
			'foreignKey' => 'dataset_id'
		]
	];
}
