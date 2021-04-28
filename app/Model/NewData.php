<?php

/**
 * Class Data
 * Data model
 */
class NewData extends AppModel
{
	public $useDbConfig='new';
	public $useTable='data';

	public $hasAndBelongsToMany = ['NewSystem'];

	public $belongsTo = [
		'NewDataset'=> [
			'foreignKey' => 'datapoint_id'
		],
		'NewDataseries'=> [
			'foreignKey' => 'dataseries_id'
		],
		'NewDatapoint'=> [
			'foreignKey' => 'datapoint_id'
		],
		'NewUnit'=> [
			'foreignKey' => 'unit_id'
		],
		'NewProperty'=> [
			'foreignKey' => 'property_id'
		],
		'NewComponent'=> [
			'foreignKey' => 'component_id'
		],
		'NewSampleprop'=> [
			'foreignKey' => 'sampleprop_id'
		],
		'NewReactionprop'=> [
			'foreignKey' => 'reactionprop_id'
		]
	];

	/**
	 * function to add a new datum if it does not already exist
	 * @param array $data
	 * @param $setcnt
	 * @return integer
	 * @throws Exception
	 */
	public function add(array $data): int
	{
		$model='NewData';
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

	/**
	 * create rows in data_systems table
	 * @param $type
	 * @param $start
	 * @param $limit
	 * @throws Exception
	 */
    public function joinsys($type='id',$start=0,$limit=1)
    {
        $ds = ClassRegistry::init('NewDataSystem');

        $c=['NewDatapoint'=>['fields'=>['id','dataseries_id'],
                'NewDataseries'=>['fields'=>['id','dataset_id'],
                    'NewDataset'=>['fields'=>['id','system_id','file_id','reference_id'],
                        'NewSystem'=>['fields'=>['id','name']
                        ]
                    ]
                ]
            ]
        ];
        $f=['NewData.id','property_id','datapoint_id','sampleprop_id'];
        if($type=='id') {
			$data=$this->find('all',['fields'=>$f,'contain'=>$c,'recursive'=>-1,'conditions'=>['NewData.id'=>$start]]);
		} else {
			$data=$this->find('all',['fields'=>$f,'contain'=>$c,'recursive'=>-1,'order'=>'NewData.id','offset'=>$start,'limit'=>$limit]);
		}
        foreach ($data as $idx=>$datum) {
        	//debug($datum);exit;
            $did = $datum['NewData']['id'];
			$pid = $datum['NewData']['property_id'];
			$spid = $datum['NewData']['sampleprop_id'];
			$dsid = $datum['NewDatapoint']['NewDataseries']['NewDataset']['id'];
			$fid = $datum['NewDatapoint']['NewDataseries']['NewDataset']['file_id'];
			$rid = $datum['NewDatapoint']['NewDataseries']['NewDataset']['reference_id'];
			if(isset($datum['NewDatapoint']['NewDataseries']['NewDataset']['NewSystem'])) {
                $sid = $datum['NewDatapoint']['NewDataseries']['NewDataset']['NewSystem']['id'];
                $name = $datum['NewDatapoint']['NewDataseries']['NewDataset']['NewSystem']['name'];
            } else {
                $sid = null;
                $name = 'NA';
            }

            $cnds=['NewDataSystem'=>['data_id'=>$did,'system_id'=>$sid,'sampleprop_id'=>$spid,'dataset_id'=>$dsid,'property_id'=>$pid,'file_id'=>$fid,'reference_id'=>$rid]];
			$ds->create();
            $ds->save($cnds);
            $ds->clear();
        }
    }

}
