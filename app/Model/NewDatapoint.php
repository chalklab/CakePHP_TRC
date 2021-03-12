<?php

/**
 * Class Dataseries
 * Dataseries model
 */
class NewDatapoint extends AppModel
{

	public $useDbConfig='new';
	public $useTable='datapoints';

	/**
     * Link annotations, conditions, data, and setting as dependent so they get deleted when the datapoint does
     * @var array
     */
    public $hasMany = [
        'NewAnnotation'=> [
            'foreignKey' => 'datapoint_id',
            'dependent' => true,
        ],
        'NewCondition'=> [
            'foreignKey' => 'datapoint_id',
            'dependent' => true,
        ],
        'NewData'=> [
            'foreignKey' => 'datapoint_id',
            'dependent' => true,
        ],
        'NewSetting'=> [
            'foreignKey' => 'datapoint_id',
            'dependent' => true,
        ]
	];

    public $belongsTo = [
    	'NewDataseries'=> [
			'foreignKey' => 'dataseries_id'
		],
		'NewDataset'=> [
			'foreignKey' => 'dataset_id'
		]
	];

}
