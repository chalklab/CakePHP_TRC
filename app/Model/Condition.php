<?php

/**
 * Class Condition
 * model for the conditions table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Condition extends AppModel
{
	// relationships to other tables
	public $belongsTo = ['Compohnent','Datapoint','Dataseries','Dataset',
		'Phase','Quantity','Quantitykind','System','Unit'
	];

	/**
	 * function to add a new condition if it does not already exist
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Condition',$data);
	}

	/**
     * function to create rows in conditions_systems table
	 * the conditions_systems table was not created in any DB
	 * run once
     * @param int $offset
	 * @throws
     */
    public function joinsys(int $offset)
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
        ];$f=['Condition.id','quantity_id','datapoint_id','dataseries_id'];
        $data=$this->find('all',['fields'=>$f,'contain'=>$c,'order'=>'Condition.id','offset'=>$offset,'recursive'=>-1]);
		$dsid=$sid=null;
        echo "<table><tr><th>ID</th><th>Name</th><th>System ID</th><th>Condition ID</th><th>Dataset ID</th><th>Type</th></tr>";
        foreach ($data as $datum) {
            $cid = $datum['Condition']['id'];
            //debug($datum);
            $name="";$table=null;
            if(isset($datum['Condition']['datapoint_id'])) {
				$dsid = $datum['Datapoint']['Dataseries']['Dataset']['id'] ?? null;
                if(isset($datum['Datapoint']['Dataseries']['Dataset']['System'])) {
                    $sid = $datum['Datapoint']['Dataseries']['Dataset']['System']['id'];
                    $name = $datum['Datapoint']['Dataseries']['Dataset']['System']['name'];
                } else {
                    $sid = null;
                    $name = 'NA';
                }
                $table="datapoints";
            }
            if(isset($datum['Condition']['dataseries_id'])) {
				$dsid = $datum['Dataseries']['Dataset']['id'] ?? null;
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
        }
        echo "</table>";
        exit;
    }

}
