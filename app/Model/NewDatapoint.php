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

	/**
	 * function to add a new dataset if it does not already exist
	 * @param array $data
	 * @param $setcnt
	 * @return integer
	 * @throws Exception
	 */
	public function add(array $data): int
	{
		$model='NewDatapoint';
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
