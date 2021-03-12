<?php

/**
 * Class Dataset
 * Parameter model
 */
class NewDataset extends AppModel
{

	public $useDbConfig='new';
	public $useTable='datasets';

	public $hasOne = [
		'NewMixture'=> [
			'foreignKey' => 'dataset_id',
			'dependent' => true
		]
	];

	public $hasMany = [
		'NewAnnotation'=> [
			'foreignKey' => 'dataset_id',
			'dependent' => true
		],
		'NewDataseries'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
		'NewDataSystem'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'NewSampleprop'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'NewReactionprop'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ]
    ];

    public $belongsTo = [
    	'NewSystem'=> [
			'foreignKey' => 'system_id'
		],
		'NewReference'=> [
			'foreignKey' => 'reference_id'
		],
		'NewFile'=> [
			'foreignKey' => 'file_id'
		],
		'NewReport'=> [
			'foreignKey' => 'report_id'
		]
	];

}
