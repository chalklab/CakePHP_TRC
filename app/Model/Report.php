<?php

/**
 * Class Report
 * Report model
 */
class Report extends AppModel
{
    public $hasOne = [
        'Dataset'=> [
            'foreignKey' => 'report_id',
            'dependent' => true]
    ];

    public $belongsTo = ['Publication'];

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
            'Publication'=>['fields'=>['id','title'],
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
                        'Unit'=>['fields'=>['name']]],
                    'SuppParameter'=>['fields'=>['identifier','symbol'],
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
        //$joins=[['table'=>'propertygroups_publications','alias'=>'PropertygroupsPublication','type'=>'left','conditions'=>['Publication.id = PropertygroupsPublication.publication_id']],
        //       ['table'=>'propertygroups','alias'=>'Propertygroup','type'=>'left','conditions'=>['PropertygroupsPublication.propertygroup_id = Propertygroup.id']]];,'joins'=>$joins

        $data=$this->find('first',['conditions'=>['Report.id'=>$id],'contain'=>$contain]);
        return $data;
    }

}