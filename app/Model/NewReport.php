<?php

/**
 * Class Report
 * Report model
 */
class NewReport extends AppModel
{
	public $useDbConfig='new';
	public $useTable='reports';

	public $hasOne = [
        'NewDataset'=> [
            'foreignKey' => 'report_id',
            'dependent' => true
		]
    ];

    public $belongsTo = [
    	'NewFile'=> [
    		'foreignKey' => 'file_id'
		],
		'NewReference'=> [
			'foreignKey' => 'reference_id'
		]
	];

	/**
	 * function to add a new report if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add(array $data): int
	{
		$model='NewReport';
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
