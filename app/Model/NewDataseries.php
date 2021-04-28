<?php

/**
 * Class Dataseries
 * Dataseries model
 */
class NewDataseries extends AppModel
{
	public $useDbConfig='new';
	public $useTable='dataseries';

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

	/**
	 * function to add a new dataseries if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add(array $data): int
	{
		$model='NewDataseries';
		$found=$this->find('first',['conditions'=>$data,'recursive'=>-1]);
		if(!$found) {
			$this->create();
			$this->save([$model=>$data]);
			$id=$this->id;
			$this->clear();
		} else {
			$id=$found[$model]['id'];
		}
		return $id;
	}

}
