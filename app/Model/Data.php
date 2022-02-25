<?php

/**
 * Class Data
 * model for the data table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Data extends AppModel
{
	// relationships to other tables
	public $belongsTo = [
		'Compohnent'=>['foreignKey' => 'component_id'],
		'Datapoint','Dataseries','Dataset','Phase','Quantity','Sampleprop','Unit'];

	/**
	 * function to add a new datum if it does not already exist
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Data',$data);
	}

	/**
     * function to create rows in the data_systems table
	 * data_systems table does not exist in the trcv2_clean DB
	 * @param string $type
     * @param int $start
	 * @param int $limit
	 * @throws
     */
    public function joinsys(string $type='id',int $start=0,int $limit=1)
    {
        $ds = ClassRegistry::init('DataSystem');

        $c=['Datapoint'=>['fields'=>['id','dataseries_id'],
                'Dataseries'=>['fields'=>['id','dataset_id'],
                    'Dataset'=>['fields'=>['id','system_id','file_id','reference_id'],
                        'System'=>['fields'=>['id','name']
                        ]
                    ]
                ]
            ]];
        $f=['Data.id','property_id','datapoint_id','sampleprop_id'];
        if($type=='id') {
			$data=$this->find('all',['fields'=>$f,'contain'=>$c,'recursive'=> -1,'conditions'=>['Data.id'=>$start]]);
		} else {
			$data=$this->find('all',['fields'=>$f,'contain'=>$c,'recursive'=> -1,'order'=>'Data.id','offset'=>$start,'limit'=>$limit]);
		}
		foreach ($data as $datum) {
        	$did = $datum['Data']['id'];
			$pid = $datum['Data']['property_id'];
			$spid = $datum['Data']['sampleprop_id'];
			$dsid = $datum['Datapoint']['Dataseries']['Dataset']['id'];
			$fid = $datum['Datapoint']['Dataseries']['Dataset']['file_id'];
			$rid = $datum['Datapoint']['Dataseries']['Dataset']['reference_id'];
			$sid = $datum['Datapoint']['Dataseries']['Dataset']['System']['id'];
			$conds=['data_id'=>$did,'system_id'=>$sid,'sampleprop_id'=>$spid,'dataset_id'=>$dsid,'property_id'=>$pid,'file_id'=>$fid,'reference_id'=>$rid];
            $done = $ds->find('first',["conditions"=>$conds,"recursive"=>-1]);
            if(!$done) {
				// check if data_id is present - if so entries are inconsistent
				$gotdid=$ds->find('first',["conditions"=>['data_id'=>$did],"recursive"=>-1]);
				if($gotdid) {
					// update missing/inconsistent issues
					$fields=['file_id','dataset_id','system_id','property_id','sampleprop_id','reference_id'];
					foreach($fields as $field) {
						if($gotdid['DataSystem'][$field]!=$conds[$field]) {
							echo "Missing field ".$field."<br/>";
							if(is_null($gotdid['DataSystem'][$field])) {
								// update empty field
								$ds->id=$gotdid['DataSystem']['id'];
								$ds->save([$field=>$conds[$field]]);
								$prevcom=$gotdid['DataSystem']['comments'];
								if(!is_null($prevcom)) { $prevcom.="; "; }
								$cmmt=$prevcom.'added '.$field.' ('.$conds[$field].') on '.date("mdy");
								$ds->save(['comments'=>$cmmt]);
							} else {
								echo "Inconsistency in ".$field."<br/>";
								debug($gotdid['DataSystem']);debug($conds);exit;
							}
						}
					}
				} else {
					$conds['comments']='added on '.date("mdy");
					$ds->create();
					$ds->save(['DataSystem'=>$conds]);
					$ds->clear();
					echo 'Added datapoint '.$did.'</br>';
				}
			} else {
				echo 'Datapoint '.$did.' aleady added</br>';
			}
		}
    }
}
