<?php

/**
 * Class Condition
 * Condition model
 */
class NewCondition extends AppModel
{

	public $useDbConfig='new';
	public $useTable='conditions';

	public $belongsTo = [
		'NewDataset'=> [
			'foreignKey' => 'dataset_id',
			'dependent' => true
		],
		'NewDataseries'=> [
			'foreignKey' => 'dataseries_id',
			'dependent' => true
		],
		'NewDatapoint'=> [
			'foreignKey' => 'datapoint_id',
			'dependent' => true
		],
		'NewProperty'=> [
			'foreignKey' => 'property_id',
			'dependent' => true
		],
		'NewSampleprop'=> [
			'foreignKey' => 'sampleprop_id',
			'dependent' => true
		],
		'NewPhase'=> [
			'foreignKey' => 'phase_id',
			'dependent' => true
		],
		'NewComponent'=> [
			'foreignKey' => 'component_id',
			'dependent' => true
		],
		'NewUnit'=> [
			'foreignKey' => 'unit_id',
			'dependent' => true
		]
	];


	public $hasOne = [
    	'NewAnnotation'=> [
			'foreignKey' => 'condition_id',
			'dependent' => true
		]
	];

    /**
     * Function to create rows in conditions_systems table
     * @param $s
     */
    public function joinsys($s)
    {
        $cs = ClassRegistry::init('ConditionsSystem');

        $c=['Datapoint'=>['fields'=>['id','dataseries_id'],
                'Dataseries'=>['fields'=>['id','dataset_id'],
                    'Dataset'=>['fields'=>['id','system_id'],
                        'System'=>['fields'=>['id','name']
                        ]
                    ]
                ]
            ],
            'Dataseries'=>['fields'=>['id','dataset_id'],
                'Dataset'=>['fields'=>['id','system_id'],
                    'System'=>['fields'=>['id','name']
                    ]
                ]
            ]
        ];
        $data=$this->find('all',['fields'=>['Condition.id','property_id','datapoint_id','dataseries_id'],'contain'=>$c,'recursive'=> -1,'order'=>'Condition.id','offset'=>$s,'limit'=>50000]);

        echo "<table border='1px solid midnightblue'><tr><th>ID</th><th>Name</th><th>System ID</th><th>Condition ID</th><th>Dataset ID</th><th>Type</th></tr>";
        foreach ($data as $datum) {
            $cid = $datum['Condition']['id'];
            //debug($datum);
            $name="";$table=null;
            if(isset($datum['Condition']['datapoint_id'])&&!is_null($datum['Condition']['datapoint_id'])) {
                if(isset($datum['Datapoint']['Dataseries']['Dataset']['id'])) {
                    $dsid = $datum['Datapoint']['Dataseries']['Dataset']['id'];
                } else {
                    $dsid = null;
                }
                if(isset($datum['Datapoint']['Dataseries']['Dataset']['System'])) {
                    $sid = $datum['Datapoint']['Dataseries']['Dataset']['System']['id'];
                    $name = $datum['Datapoint']['Dataseries']['Dataset']['System']['name'];
                } else {
                    $sid = null;
                    $name = 'NA';
                }
                $table="datapoints";
            }
            if(isset($datum['Condition']['dataseries_id'])&&!is_null($datum['Condition']['dataseries_id'])) {
                if(isset($datum['Dataseries']['Dataset']['id'])) {
                    $dsid = $datum['Dataseries']['Dataset']['id'];
                } else {
                    $dsid = null;
                }
                if(isset($datum['Dataseries']['Dataset']['System'])) {
                    $sid = $datum['Dataseries']['Dataset']['System']['id'];
                    $name = $datum['Dataseries']['Dataset']['System']['name'];
                } else {
                    $sid = null;
                    $name = 'NA';
                }
                $table="dataseries";
            }
            $data=['ConditionsSystem'=>['condition_id'=>$cid,'system_id'=>$sid,'dataset_id'=>$dsid,'ontable'=>$table]];
            //debug($data);exit;
            $cs->create();
            $cs->save($data);
            $id=$cs->id;
            $cs->clear();
            echo "<tr><td>".$id."</td><td>".$name."</td><td>".$sid."</td><td>".$cid."</td><td>".$dsid."</td><td>".$table."</td></tr>";
            //debug($did);debug($sid);debug($name);exit;
        }
        echo "</table>";
        exit;
    }

}
