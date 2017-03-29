<?php

/**
 * Class AdminController
 */
class AdminController extends AppController
{
    public $uses=['Material','Manufacturer','Method','Sample','Apparatus','Trademark','Data'];

    public function ingest()
    {
            $xml=simplexml_load_file('/Users/tatumschumann/Desktop/UNF/Chalk Lab/Dropbox/Tatum Poster/NIST SRD 81/AllData.xml');
            $trc=json_decode(json_encode($xml),true);$count=1;
            foreach($trc['TableAllData'] as $dataset) {
                $res=$this->Material->find('first',['conditions'=>['id'=>$dataset['InsulMaterialID']]]);
                if(empty($res)) {
                    $material=$dataset['Material'];
                    $materialid=$dataset['InsulMaterialID'];
                    $temp=['Material'=>['id'=>$materialid,'material'=>$material]];
                    $this->Material->create();
                    $this->Material->save($temp);
                }
                //debug($dataset);exit;
                $res=$this->Manufacturer->find('first',['conditions'=>['id'=>$dataset['InsulManufacturerID']]]);
                if(empty($res)) {
                    $manufacturer=$dataset['Manufacturer'];
                    if(!empty($manufacturer)) {
                        $manufacturerid=$dataset['InsulManufacturerID'];
                        $temp=['Manufacturer'=>['id'=>$manufacturerid,'manufacturer'=>$manufacturer]];
                        $this->Manufacturer->create();
                        $this->Manufacturer->save($temp);
                    }
                }
                $res=$this->Method->find('first',['conditions'=>['dataset_id'=>$dataset['ID']]]);
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
                $res=$this->Sample->find('first',['conditions'=>['dataset_id'=>$dataset['ID']]]);
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
                $res=$this->Apparatus->find('first',['conditions'=>['id'=>$dataset['ID']]]);
                if(empty($res)) {
                    $apparatus=$dataset['Apparatus'];
                    $temp=['Apparatus'=>['apparatus'=>$apparatus]];
                    $this->Apparatus->create();
                    $this->Apparatus->save($temp);

                }
                $res=$this->Data->find('first',['conditions'=>['Data.datapoint_id'=>$dataset['ID'],'Data.property_id'=>83]]);
                if(empty($res)) {
                    $k=$dataset['k'];
                    $datasetid=$dataset['ID'];
                    $property=83;
                    $unit=69;
                    $temp=['Data'=>['datapoint_id'=>$datasetid,'number'=>$k,'property_id'=>$property,'unit_id'=>$unit]];
                    $this->Data->create();
                    $this->Data->save($temp);
                }
                $res=$this->Data->find('first',['conditions'=>['Data.datapoint_id'=>$dataset['ID'],'Data.property_id'=>84]]);
                if(empty($res)) {
                    $deltat=$dataset['Delta_T'];
                    $datasetid=$dataset['ID'];
                    $property=84;
                    $unit=67;
                    $temp=['Data'=>['datapoint_id'=>$datasetid,'number'=>$deltat,'property_id'=>$property,'unit_id'=>$unit]];
                    $this->Data->create();
                    $this->Data->save($temp);
                }
                $res=$this->Data->find('first',['conditions'=>['Data.datapoint_id'=>$dataset['ID'],'Data.property_id'=>86]]);
                if(empty($res)) {
                    $meantemp=$dataset['Mean_Temp'];
                    $datasetid=$dataset['ID'];
                    $property=86;
                    $unit=67;
                    $temp=['Data'=>['datapoint_id'=>$datasetid,'number'=>$meantemp,'property_id'=>$property,'unit_id'=>$unit]];
                    $this->Data->create();
                    $this->Data->save($temp);
                }
//                $xml=simplexml_load_file('/Users/tatumschumann/Desktop/UNF/Chalk Lab/Dropbox/Tatum Poster/NIST SRD 81/Trademarks.xml');
//                $trc=json_decode(json_encode($xml),true);
//                foreach($trc['Trademark'] as $trademark) {
//                    $res=$this->Trademark->find('first',['conditions'=>['id'=>$dataset['ID']]]);
//                    if(empty($res)) {
//                        $author=$trademark['Author'];
//                        $comment=$trademark['Com1'];
//                        $date=$trademark['Date'];
//                        $id=$trademark['ID'];
//                        $manufacturer=$trademark['Manufacturer'];
//                        $material=$trademark['Material'];
//                        $nist=$trademark['NistCatalogue'];
//                        $page=$trademark['Page'];
//                        $source=$trademark['Source'];
//                        $tradename=$trademark['TradeName'];
//                        $tradenameid=$trademark['TradeNameID'];
//                        $temp=['Trademark'=>['author'=>$author,'comment'=>$comment,'date'=>$date,'tm_id'=>$id,'manufacturer'=>$manufacturer,'material'=>$material,'nist_catalogue'=>$nist,'page'=>$page,'source'=>$source,'tradename'=>$tradename,'tradename_id'=>$tradenameid]];
//                        $this->Trademark->create();
//                        $this->Trademark->save($temp);
//                    }
//                }
                echo "Dataset ".$count." added<br/>";$count++;
            }
        exit;
    }
}