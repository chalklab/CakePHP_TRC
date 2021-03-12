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
            'dependent' => true]
    ];

    public $belongsTo = [
    	'NewFile'=> [
    		'foreignKey' => 'file_id'
		],'NewReference'=> [
			'foreignKey' => 'reference_id'
		]
	];

    /**
     * Returns DB data so that it can be used to generate scidata json
     * @param $id
     * @return mixed
     */
    public function scidata($id)
    {
        // Note: there is an issue with the retrival of susbtances under system if id is not requested as a field
        // This is a bug in CakePHP as it works without id if its at the top level...
        $contain=[
            'Report'=>['fields'=>['id','title'],
                'Propertygroup'=>['fields'=>['id','description']]],
            'Dataset'=>[
                'File'=>['fields'=>['filename','url','num_systems']],
                'Reference'=>['fields'=>['sid','journal','authors','year','volume','issue','startpage','endpage']],
                'Propertytype'=>['fields'=>['code','num_components','phases','states','method'],
                    'Property'=>['fields'=>['name']],
                    'Parameter'=>['fields'=>['identifier','symbol'],
                        'Property'=>['fields'=>['name']],
                        'Unit'=>['fields'=>['name']]],
                    'Variable'=>['fields'=>['identifier','symbol'],
                        'Property'=>['fields'=>['name']],
                        'Unit'=>['fields'=>['name']]]],
                'System'=>['fields'=>['id','name','description','type'],
                    'Substance'=>['fields'=>['name','formula','molweight'],
                        'Identifier'=>['fields'=>['type','value'],'conditions'=>['type'=>['inchikey']]]]],
                'Dataseries'=>['fields'=>['type'],
                    'Condition'=>['fields'=>['number','error'],
                        'Property'=>['fields'=>['name']],
                        'Unit'=>['fields'=>['name','symbol']]],
                    'Datapoint'=>['fields'=>['row_index'],
                        'Data'=>['fields'=>['datatype','number','error'],
                            'Property'=>['fields'=>['name']],
                            'Unit'=>['fields'=>['name','symbol']]],
                        'Condition'=>['fields'=>['datatype','number','error'],
                            'Property'=>['fields'=>['name']],
                            'Unit'=>['fields'=>['name','symbol']]],
                        'Setting'=>['fields'=>['datatype','text','number','error'],
                            'Property'=>['fields'=>['name']],
                            'Unit'=>['fields'=>['name','symbol']]]]]]];
        //$joins=[['table'=>'propertygroups_Reports','alias'=>'PropertygroupsReport','type'=>'left','conditions'=>['Report.id = PropertygroupsReport.report_id']],
        //       ['table'=>'propertygroups','alias'=>'Propertygroup','type'=>'left','conditions'=>['PropertygroupsJournal.propertygroup_id = Propertygroup.id']]];,'joins'=>$joins

        $data=$this->find('first',['conditions'=>['Report.id'=>$id],'contain'=>$contain]);
        return $data;
    }

}
