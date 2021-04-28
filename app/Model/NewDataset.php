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

	/**
	 * function to add a new dataset if it does not already exist
	 * @param array $data
	 * @param $setcnt
	 * @return integer
	 * @throws Exception
	 */
	public function add(array $data,&$setcnt): int
	{
		$model='NewDataset';
		$found=$this->find('first',['conditions'=>$data,'recursive'=>-1]);
		if(!$found) {
			$this->create();
			$this->save([$model=>$data]);
			$id=$this->id;
			$this->clear();
		} else {
			$set=$found[$model];
			$id=$set['id'];
			if(!is_null($set['points'])) {
				$setcnt=$set['points'];
			}
		}
		return $id;
	}
}
