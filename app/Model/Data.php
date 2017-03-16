<?php

/**
 * Class Data
 * Data model
 */
class Data extends AppModel
{
    public $hasAndBelongsToMany=['System'];

    public $belongsTo = ['Datapoint','Unit','Property'];

    /**
     * Function to create rows in data_systems table
     * @param $start
     */
    public function joinsys($start)
    {
        $ds = ClassRegistry::init('DataSystem');

        $c=['Datapoint'=>['fields'=>['id','dataseries_id'],
                'Dataseries'=>['fields'=>['id','dataset_id'],
                    'Dataset'=>['fields'=>['id','system_id'],
                        'System'=>['fields'=>['id','name']
                        ]
                    ]
                ]
            ]
        ];
        $f=['Data.id','property_id','datapoint_id'];
        $data=$this->find('all',['fields'=>$f,'contain'=>$c,'recursive'=> -1,'order'=>'Data.id','offset'=>$start,'limit'=>50000]);

        foreach ($data as $datum) {
            $did = $datum['Data']['id'];
            $dsid = $datum['Datapoint']['Dataseries']['Dataset']['id'];
            if(isset($datum['Datapoint']['Dataseries']['Dataset']['System'])) {
                $sid = $datum['Datapoint']['Dataseries']['Dataset']['System']['id'];
                $name = $datum['Datapoint']['Dataseries']['Dataset']['System']['name'];
            } else {
                $sid = null;
                $name = 'NA';
            }
            $data=['DataSystem'=>['data_id'=>$did,'system_id'=>$sid,'dataset_id'=>$dsid]];
            $ds->create();
            $ds->save($data);
            $id=$ds->id;
            $ds->clear();
        }
        return;
    }

}