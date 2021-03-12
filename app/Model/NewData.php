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
     * Function to create rows in data_systems table
	 * @param $type
     * @param $start
	 * @param $limit
     */
    public function joinsys($type='id',$start=0,$limit=1)
    {
        $ds = ClassRegistry::init('DataSystem');

        $c=['Datapoint'=>['fields'=>['id','dataseries_id'],
                'Dataseries'=>['fields'=>['id','dataset_id'],
                    'Dataset'=>['fields'=>['id','system_id','file_id','reference_id'],
                        'System'=>['fields'=>['id','name']
                        ]
                    ]
                ]
            ]
        ];
        $f=['Data.id','property_id','datapoint_id','sampleprop_id'];
        if($type=='id') {
			$data=$this->find('all',['fields'=>$f,'contain'=>$c,'recursive'=> -1,'conditions'=>['Data.id'=>$start]]);
		} else {
			$data=$this->find('all',['fields'=>$f,'contain'=>$c,'recursive'=> -1,'order'=>'Data.id','offset'=>$start,'limit'=>$limit]);
		}

        foreach ($data as $idx=>$datum) {
        	//debug($datum);exit;
            $did = $datum['Data']['id'];
			$pid = $datum['Data']['property_id'];
			$spid = $datum['Data']['sampleprop_id'];
			$dsid = $datum['Datapoint']['Dataseries']['Dataset']['id'];
			$fid = $datum['Datapoint']['Dataseries']['Dataset']['file_id'];
			$rid = $datum['Datapoint']['Dataseries']['Dataset']['reference_id'];
			if(isset($datum['Datapoint']['Dataseries']['Dataset']['System'])) {
                $sid = $datum['Datapoint']['Dataseries']['Dataset']['System']['id'];
                $name = $datum['Datapoint']['Dataseries']['Dataset']['System']['name'];
            } else {
                $sid = null;
                $name = 'NA';
            }

            $data=['DataSystem'=>['data_id'=>$did,'system_id'=>$sid,'sampleprop_id'=>$spid,'dataset_id'=>$dsid,'property_id'=>$pid,'file_id'=>$fid,'reference_id'=>$rid]];
            $ds->create();
            $ds->save($data);
            $id=$ds->id;
            $ds->clear();
        	// echo 'Added datapoint '.$idx.'</br>';
        }
        return;
    }

}
