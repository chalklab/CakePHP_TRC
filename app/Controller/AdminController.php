<?php

/**
 * Class AdminController
 */
class AdminController extends AppController
{
    public $uses=['Material','Manufacturer','Method','Sample','Apparatus','Trademark',
        'Data','Dataset','Dataseries','Datapoint','Srddata','File'];

    /**
     * function beforeFilter
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }
	
	/**
	 * Update entries in the data_systems table
	 * @param int $start
	 */
	public function datasys($start=0)
	{
		// Update the data_systems join table
		$limit=5000;
		$this->Data->joinsys('bulk',$start,$limit);
		exit;
	}
	
	public function view($id)
    {
        $c=['Material',
            'Manufacturer',
            'Trademark',
            'Method'=>['Apparatus'],
            'Sample',
            'Dataset'=>[
                'Dataseries' => [
                    'Datapoint' => [
                        'Data'=>['Unit',
                            'Property'=>['fields'=>['name'],
                                'Quantity'=>['fields'=>['name']]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $data=$this->Srddata->find('first',['conditions'=>['Srddata.id'=>$id],'contain'=>$c,'recursive'=>-1]);

        $file=$data['Srddata'];
        $man=$data['Manufacturer'];
        $tmk=$data['Trademark'];
        $mat=$data['Material'];
        $met=$data['Method'];
        $sam=$data['Sample'];
        $set=$data['Dataset'];
        //debug($set);exit;

        // Base
        $base="https://chalk.coas.unf.edu/datasets/srd81/scidata/".$id."/";

        // Build the PHP array that will then be converted to JSON
        $json['@context']=['https://stuchalk.github.io/scidata/contexts/scidata.jsonld',
            ['sci'=>'http://stuchalk.github.io/scidata/ontology/scidata.owl#',
                'meas'=>'http://stuchalk.github.io/scidata/ontology/scidata_measurement.owl#',
                'qudt'=>'http://www.qudt.org/qudt/owl/1.0.0/unit.owl#',
                'dc'=>'http://purl.org/dc/terms/',
                'xsd'=>'http://www.w3.org/2001/XMLSchema#'],
            ['@base'=>$base]];

        // Main metadata
        $json['@id']="";
        $json['uid']="srd81:dataset:".$id;
        $json['title']=$file['title'];
        $json['date']=$file['date'];
        $json['publisher']='National Institute of Standards and Technology (NIST)';
        $json['permalink']="http://chalk.coas.unf.edu/tatum/admin/view/".$id;
        $json['toc']=[];

        // SciData
        $setj['@id']="scidata";
        $setj['@type']="sci:scientificData";
        $json['scidata']=$setj;

        // Methodology
        $metj['@id']="methodology/";
        $json['toc'][]=$metj['@id'];
        $metj['@type']="sci:methodology";
        $metj['evaluation']="experimental";
        $metj['aspects']=[];

        // Method
        $methj['@id']="method/1/";
        $methj['@type']="sci:method";
        unset($met['id']);unset($met['apparatus_id']);unset($met['srddata_id']);
        $app=$met['Apparatus']['apparatus'];unset($met['Apparatus']);
        foreach($met as $label=>$value) {
            if(!is_null($value)) {
                $methj[$label]=$value;
            }
        }
        $metj['aspects']['method']=$methj;

        // Apparatus
        $appj['@id']="apparatus/1/";
        $appj['@type']="sci:apparatus";
        $appj['type']=$app;
        $metj['aspects']['apparatus']=$appj;

        // System
        $sysj['@id']="system/";
        $json['toc'][]=$sysj['@id'];
        $sysj['@type']="sci:system";
        $sysj['discipline']="material science";
        $sysj['facets']=[];

        // Material
        $matj['@id']="material/1/";
        $matj['@type'] = "sci:material";
        $matj['type'] = $mat['material'];
        $matj['source'] = $man['manufacturer'];
        if(!is_null($sam['comments'])) { $matj['comments'] = $sam['comments']; }
        if(!is_null($sam['form'])) { $matj['form'] = $sam['form']; }
        if(!is_null($sam['sample_number'])) { $matj['sample_number'] = $sam['sample_number']; }
        if(!is_null($sam['initial_mc'])) { $matj['initial_mc'] = $sam['initial_mc']; }
        $matj['properties'] = [];
        $propj['@id']="property";
        $propj['@type'] = "sci:property";
        if(!is_null($sam['density'])) {
            $propj['@id']="property/1";
            $propj['@type']="prop:density";
            $propj['datatype']="xs:float";
            $propj['value']=$sam['density'];
            $propj['unitref']="qudt:g-PER-MilliL";
        }
        $matj['properties'][]=$propj;
        if(!is_null($sam['delta_x'])) {
            $propj['@id']="property/2";
            $propj['@type']="prop:thickness";
            $propj['datatype']="xs:float";
            $propj['value']=$sam['delta_x'];
            $propj['unitref']="qudt:MilliM";
        }
        $matj['properties'][]=$propj;

        $sysj['facets']['material']=$matj;

        // Sample
        $samj['@id']="sample/1/";
        $samj['@type'] = "sci:sample";
        $samj['type'] = $mat['material'];

        $sysj['facets']['sample']=$samj;


        // Data
        $resj['@id']="dataset/";
        $resj['@type'] = 'sci:dataset';
        $resj['source'] = 'method/1';
        $resj['datagroup'] = [];

        $grpj['@id']='datagroup/1/';
        $json['toc'][] = $grpj['@id'];
        $grpj['@type'] = 'sci:datagroup';
        $datas=$set['Dataseries'][0]['Datapoint'][0]['Data'];
        //debug($datas);exit;

        foreach($datas as $dtm) {
            $dtmj=[];
            $dtmj['@id'] = 'datagroup/1/datapoint/1/';
            $dtmj['@type'] = 'sci:datapoint';
            // Value
            $v=[];
            if(!is_null($dtm['number'])) {
                $unit="";
                if(isset($dtm['Unit']['qudt'])&&!empty($dtm['Unit']['qudt'])) {
                    $unit='qudt:'.$dtm['Unit']['qudt'];
                } elseif(isset($dtm['Unit']['symbol'])&&!empty($dtm['Unit']['symbol'])) {
                    $unit=$this->Dataset->qudt($dtm['Unit']['symbol']);
                }
                if($dtm['datatype']=="datum") {
                    $v['@id']=$dtmj['@id']."value/";
                    $v['@type']="sci:value";
                    $v['number']=$dtm['number'];
                    if($unit!="") {
                        if(stristr($unit,'qudt')) {
                            $v['unitref'] = $unit;
                        } else {
                            $v['unitstr'] = $unit;
                        }
                    }
                    $dtmj['value']=$v;
                } else {
                    $v['@id']=$dtmj['@id']."valuearray/";
                    $v['@type']="sci:valuearray";
                    $v['numberarray']=json_decode($dtm['number'],true);
                    if($unit!="") { $v['unitref']=$unit; }
                    $dtmj['valuearray']=$v;
                }
            }
            $grpj['datapoint'][]=$dtmj;
        }
        $resj['datagroup']=$grpj;

        // Add methodology section to main array
        $json['scidata']['methodology']=$metj;
        $json['scidata']['system']=$sysj;
        $json['scidata']['dataset']=$resj;

        // Source
        $srd=['@id'=>'reference/1/','@type'=>'dc:source'];
        $srd['citation'] = "NIST SRD 81 - NIST Heat Transmission Properties of Insulating and Building Materials";
        $srd['url']='https://srdata.nist.gov/insulation';
        $json['references'][]=$srd;

        // Rights
        $json['rights']=['@id'=>'rights','@type'=>'dc:rights'];
        $json['rights']['holder']='NIST - SRD Program, Gaithersburg, MD';
        $json['rights']['license']='http://creativecommons.org/publicdomain/zero/1.0/';


        header("Content-Type: application/ld+json");
        echo json_encode($json,JSON_UNESCAPED_UNICODE);exit;
    }

    public function merge()
    {
        $c=['Material',
            'Manufacturer',
            'Trademark',
            'Method'=>['Apparatus'],
            'Sample',
            'Dataset'=>[
                'Dataseries' => [
                    'Datapoint' => [
                        'Data'=>['Unit',
                            'Property'=>['fields'=>['name'],
                                'Quantity'=>['fields'=>['name']]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $mat=$this->Srddata->find('first',['conditions'=>['Srddata.id'=>762],'contain'=>$c,'recursive'=>-1]);

        $c2=['Trcchemical'=>[
            'Substance'=>['fields'=>['name','formula','molweight'],
                'Identifier'=>['fields'=>['type','value'],'conditions'=>['type'=>['inchi','inchikey','iupacname']]]],
            'Unit'
        ],
            'Reference',
            'Dataset' => [
                'Dataseries' => [
                    'Condition'=>['Unit',
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]],
                    'Datapoint' => [
                        'Condition'=>['Unit',
                            'Property'=>['fields'=>['name'],
                                'Quantity'=>['fields'=>['name']]]],
                        'Data'=>['Unit',
                            'Property'=>['fields'=>['name'],
                                'Quantity'=>['fields'=>['name']]]]
                    ]
                ],
                'Trcsampleprop',
                'Trcreactionprop',
                'System'=>['fields'=>['id','name','description','type'],
                    'Substance'=>['fields'=>['name','formula','molweight'],'conditions'=>['Substance.id'=>20196],
                        'Identifier'=>['fields'=>['type','value'],'conditions'=>['type'=>['inchi','inchikey','iupacname']]]]
                ]
            ]
        ];
        $trc=$this->File->find('first',['conditions'=>['File.id'=>1874],'contain'=>$c2,'recursive'=>-1]);
        $sets=$trc['Dataset'];
        $data=[];
        foreach($sets as $set) {
            if($set['system_id']=7190) {
                $data[]=$set;
            }
        }
        //debug($data);exit;
        $points=[];
        foreach($data as $set) {
            foreach($set['Dataseries'][0]['Datapoint'] as $point) {
                $prop=$point['Data'][0]['Unit']['field'];
                $cond=$point['Condition'][0]['number']." ".$point['Condition'][0]['Unit']['label'];
                $val=$point['Data'][0]['number']." ".$point['Data'][0]['Unit']['label'];
                $pnt=['condition'=>$cond,'value'=>$val];
                $points[$prop][]=$pnt;
            }
        }

        debug($points);exit;
    }

    public function ingest()
    {
            $xml=simplexml_load_file(WWW_ROOT.'files/alldata.xml');
            $trc=json_decode(json_encode($xml),true);$count=1;
            foreach($trc['TableAllData'] as $record=>$dataset) {
                $res=$this->Srddata->find('first',['conditions'=>['Srddata.id'=>$dataset['ID']]]);
                if(empty($res)) {
                    $id=$dataset['ID'];
                    if(!is_array($dataset['Description'])) {
                        $title=$dataset['Description'];
                    } else {
                        $title='No description given';
                    }
                    //debug($dataset);debug($title);exit;
                    $date=$dataset['Date'];
                    $mat=$dataset['InsulMaterialID'];
                    $man=$dataset['InsulManufacturerID'];
                    $tmk=$dataset['InsulTradenameID'];
                    $temp=['Srddata'=>['id'=>$id,'title'=>$title,'date'=>$date,'record'=>$record,'material_id'=>$mat,'manufacturer_id'=>$man,'trademark_id'=>$tmk]];
                    $this->Srddata->create();
                    $this->Srddata->save($temp);
                    $srdid=$this->Srddata->id;
                    $res=$this->Method->find('first',['conditions'=>['srddata_id'=>$srdid]]);
                    if(empty($res)) {
                        $datasetid=$dataset['ID'];
                        $testmet=$dataset['TestMethod'];
                        $spectc=$dataset['SpecimenTC'];
                        $desctc=$dataset['DescTC'];
                        $mode=$dataset['Mode'];
                        $interposed=$dataset['Interposed'];
                        $otherspec=$dataset['OtherSpec'];
                        $sheetmat=$dataset['SheetMaterial'];
                        $temp=['Method'=>['dataset_id'=>$datasetid,'test_method'=>$testmet,'specimen_thermocouples'=>$spectc,'thermocouple_description'=>$desctc,'mode'=>$mode,'interposed'=>$interposed,'other_specimen'=>$otherspec,'sheet_material'=>$sheetmat]];
                        $this->Method->create();
                        $this->Method->save($temp);
                    }
                    $res=$this->Sample->find('first',['conditions'=>['srddata_id'=>$srdid]]);
                    if(empty($res)) {
                        $datasetid=$dataset['ID'];
                        $description=$dataset['Description'];
                        $comments=$dataset['Comments'];
                        $form=$dataset['Form'];
                        $sampleno=$dataset['SampleNo'];
                        $initmc=$dataset['InitMC'];
                        $finalmc=$dataset['FinalMC'];
                        $density=$dataset['Density'];
                        $deltax=$dataset['Delta_X'];
                        $temp=['Sample'=>['dataset_id'=>$datasetid,'description'=>$description,'comments'=>$comments,'form'=>$form,'sample_number'=>$sampleno,'initial_mc'=>$initmc,'final_mc'=>$finalmc,'density'=>$density,'delta_x'=>$deltax]];
                        $this->Sample->create();
                        $this->Sample->save($temp);
                    }
                    // Create dataset
                    $temp=['Dataset'=>['title'=>'Data from SRD 81 ID:'.$srdid,'srddata_id'=>$srdid]];
                    $this->Dataset->create();
                    $this->Dataset->save($temp);
                    $dsid=$this->Dataset->id;
                    // Create dataseries
                    $temp=['Dataseries'=>['dataset_id'=>$dsid,'type'=>'independent value']];
                    $this->Dataseries->create();
                    $this->Dataseries->save($temp);
                    $dserid=$this->Dataseries->id;
                    // Create Datapoint
                    $temp=['Datapoint'=>['dataseries_id'=>$dserid,'row_index'=>1]];
                    $this->Datapoint->create();
                    $this->Datapoint->save($temp);
                    $dpntid=$this->Datapoint->id;
                    $res=$this->Data->find('first',['conditions'=>['Data.datapoint_id'=>$dpntid,'Data.property_id'=>95]]);
                    if(empty($res)) {
                        $k=$dataset['k'];
                        $datasetid=$dataset['ID'];
                        $property=95;
                        $unit=80;
                        $temp=['Data'=>['datapoint_id'=>$dpntid,'number'=>$k,'property_id'=>$property,'unit_id'=>$unit]];
                        $this->Data->create();
                        $this->Data->save($temp);
                    }
                    $res=$this->Data->find('first',['conditions'=>['Data.datapoint_id'=>$dpntid,'Data.property_id'=>96]]);
                    if(empty($res)) {
                        $deltat=$dataset['Delta_T'];
                        $datasetid=$dataset['ID'];
                        $property=96;
                        $unit=78;
                        $temp=['Data'=>['datapoint_id'=>$dpntid,'number'=>$deltat,'property_id'=>$property,'unit_id'=>$unit]];
                        $this->Data->create();
                        $this->Data->save($temp);
                    }
                    $res=$this->Data->find('first',['conditions'=>['Data.datapoint_id'=>$dpntid,'Data.property_id'=>98]]);
                    if(empty($res)) {
                        $meantemp=$dataset['Mean_Temp'];
                        $datasetid=$dataset['ID'];
                        $property=98;
                        $unit=78;
                        $temp=['Data'=>['datapoint_id'=>$dpntid,'number'=>$meantemp,'property_id'=>$property,'unit_id'=>$unit]];
                        $this->Data->create();
                        $this->Data->save($temp);
                    }
                }
                echo "Dataset ".$count." added<br/>";$count++;
            }
        exit;
    }
}