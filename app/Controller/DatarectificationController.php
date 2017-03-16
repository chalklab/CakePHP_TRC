<?php

/**
 * Class DatarectificationController
 * DatarectificationController
 */
class DatarectificationController extends AppController {

    public $uses=['Annotation','Datarectification','Dataset','Report','File','Property','Equation',
        'TextFile','Activity','Reference','QuantitiesUnit','Parameter','Propertytype'];

    /**
     * Ingest data from textfile
     * @param null $id
     * @return mixed
     */
    public function ingest($id=null)
    {
        set_time_limit(0);
        if (!empty($this->request->data)||$id!=null) {
            $ajax=false;
            if(!empty($this->request->data)&&!$this->request->is('ajax')) {
                $id=$this->request->data['TextFile']['inputFile'];
            }
            if($this->request->is('ajax')) {
                $ajax=true;
            }

            // Get textfile
            $textfile = $this->TextFile->find('first',['conditions'=>['TextFile.id'=>$id],'recursive' => 1]);

            // Check that the textfile referenced is in the DB
            if(!isset($textfile['TextFile'])) {
                if(!isset($this->data['ajax'])) { //check if we display the error or send back a json error
                    $this->Flash->set('File Not Yet Extracted');
                    return $this->redirect('/Datarectification/ingest/');
                } else {
                    die('{"error":"File improperly converted, text empty"}');
                }
            }
            $data=json_decode($textfile['TextFile']['captured'],true);

            // Find and delete an existing data from this textfile
            // Get all current versions of the textfile
            $vers=$this->TextFile->find('list',['conditions'=>['TextFile.file_id'=>$textfile['TextFile']['file_id'],'TextFile.sysnum'=>$textfile['TextFile']['sysnum']],'fields'=>['id'],'order'=>'id','recursive'=>0]);
            $c=['Report'];
            foreach($vers as $ver) {
                $rpts=$this->Dataset->find('list',['conditions'=>['Dataset.text_file_id'=>$ver],'fields'=>['id','report_id'],'contain'=>$c,'recursive'=>-1]);
                if(!empty($rpts)) {
                    foreach($rpts as $rpt) {
                        $this->Report->delete($rpt);
                    }
                }
            }

            // Get file, publication, and propertyType information for this textfile
            $c=['File'=>['Publication','Propertytype'=>['Property','Variable','Parameter','SuppParameter']]];
            $fpp = $this->TextFile->find('first', ['conditions' => ['TextFile.id' => $id], 'order' =>['TextFile.id DESC'], 'recursive' => 3, 'contain' => $c]);
            $file = $fpp['File'];
            $pub = $fpp['File']['Publication'];
            $ptype = $fpp['File']['Propertytype'];

            // Check for data
            if(!isset($data['data'])) {
                $message='There is no data! (drcont: '.__LINE__.')';
                if(!$ajax) {
                    $this->Flash->set($message);
                    return $this->redirect('/textfiles/view/'.$id);
                } else {
                    die('{"status": "error","message": "'.$message.'"}');
                }
            }

            // Check for chemical information
            if(empty($data['compounds'])){
                $message='Missing chemical information! (drcont: '.__LINE__.')';
                if(!$ajax) {
                    $this->Flash->set($message);
                    return $this->redirect('/textfiles/view/'.$id);
                } else {
                    die('{"status": "error","message": "'.$message.'"}');
                }
            }

            // Gather chemical information
            $labels=$clines=[];
            foreach($data['compounds'] as $cmpd) {
                $labels[]=$cmpd['label'];
                $clines[]=$cmpd['location']['line'];
            }
            $lcounts=array_count_values($labels);
            $ccounts=array_count_values($clines);
            $chemicals=[];
            if((isset($lcounts['formula'])&&$lcounts['formula']==1)&&(isset($lcounts['casrn'])&&$lcounts['casrn']==1)) {
                // All the data is from one compound/element
                foreach($data['compounds'] as $cmeta) {
                    if($cmeta['label']=='name'&&isset($chemicals[1]['name'])) {
                        $chemicals[1]['othernames'][]=$cmeta['value'];

                    } else {
                        $chemicals[1][$cmeta['label']]=$cmeta['value'];
                    }
                }
            } else {
                // Multiple chemicals - by row or by line?
                $compdcount=0;
                if(isset($lcounts['formula'])) {
                    $compdcount=$lcounts['formula'];
                } elseif (isset($lcounts['casrn'])) {
                    $compdcount=$lcounts['casrn'];
                }
                if(count($ccounts)==$compdcount) {
                    // OK compounds on individual lines
                    foreach($data['compounds'] as $cmeta) {
                        $line=$cmeta['location']['line'];
                        if($cmeta['label']=='name'&&isset($chemicals[$line]['name'])) {
                            $chemicals[$line]['othernames'][]=$cmeta['value'];

                        } else {
                            $chemicals[$line][$cmeta['label']]=$cmeta['value'];
                        }
                    }
                } else {
                    // TODO: Multiple compounds, each on multiple lines...
                }
                // Reset chemicals array to start from zero (for layouts where the chemicals are not on the first two lines)
                $chemicals=array_values($chemicals);
            }

            // Add (or find existing id for) each chemical
            $this->Datarectification->checkAndAddSubstances($chemicals,$ajax,$id);

            // Capture component #'s for mixtures so they can be added to annotations later
            $components=[];
            if(count($chemicals)>1) {
                foreach($chemicals as $seqnum=>$chem) {
                    if(isset($chem['component'])) {
                        $components[$chem['id']]=$chem['component'];
                    } else {
                        // Assume that the order of capture of the chemical information is indicative of component
                        $components[$chem['id']]=$seqnum+1;
                    }
                }
            }

            // Add solvent if indicated in publication
            if(!is_null($pub['solvent'])) {
                $index=count($chemicals)+1;
                $chemicals[$index]['id']=$pub['solvent'];
            }

            // Add chemical properties if present
            foreach($data['properties'] as $p=>$prop) {
                unset($data['properties'][$p]['location']); // Not needed any more...
                // TODO: Add chemical property data
            }

            // Check and add system
            $system_id=$this->Datarectification->checkAndAddSystem($chemicals,$ajax);

            // Add new references if present
            if(isset($data['citations'])) {
                foreach($data['citations'] as $idx=>$cite) {
                    $cite['publication_id']=$pub['id'];
                    $cite['type']='cite';
                    $data['citations'][$idx]['rid']=$this->Datarectification->checkAndAddRef($cite,$file['id']);
                }
            }

            // Organize references by code/citation
            $references=[];$uniquerefs=[];$rindex=0;
            if(count($data['references'])>0) {
                // Multiple references so need to organize datasets per reference possibly from different lines
                foreach($data['references'] as $ref) {
                    if(!in_array($ref['value'],$uniquerefs)) {
                        $uniquerefs[]=$ref['value'];
                        $rindex++;
                        $references[$rindex]['type']=$ref['label'];
                        $references[$rindex]['value']=$ref['value'];
                    }
                }
                foreach($data['references'] as $ref) {
                    foreach($references as $i=>$r) {
                        if($ref['value']==$r['value']) {
                            $references[$i]['lines'][]=$ref['location']['line'];
                        }
                        if(isset($ref['addtoline'])) {
                            $references[$i]['addtolines'][$ref['addtoline']][]=$ref['location']['line'];
                        }
                    }
                }
                foreach($references as $i=>$ref) {
                    $references[$i]['publication_id']=$pub['id'];
                }
            } else {
                // Only one reference for this textfile so all data is associated
                $ref=$data['references'][0];
                $references[1]=['type'=>$ref['label'],'value'=>$ref['value'],'lines'=>[],'publication_id'=>$pub['id']];
            }

            // Retrieve from or add refs to the DB
            foreach($references as $i=>$ref) {
                // Remove certain characters that show up at the end of the recode string
                $ref['value']=str_replace(",","",$ref['value']);

                // Deal with references in V18 that are generic
                if(stristr($ref['value'],"trchc")) {
                    $refcode=$ref['value'];$ref['value']="xx-trchc";
                } elseif(stristr($ref['value'],"trcnh")) {
                    $refcode=$ref['value'];$ref['value']="xx-trcnh";
                } else {
                    $refcode=$ref['value'];
                }
                if(empty($data['citations'])) {
                    $references[$i]['id']=$this->Datarectification->checkAndAddRef($ref,$file['id']);
                } else {
                    // Get refid from citations array
                    foreach($data['citations'] as $cite) {
                        if($cite['code']==$refcode) {
                            $references[$i]['id']=$cite['rid'];
                        }
                    }
                }
                $references[$i]['refcode']=str_replace(" ","",$refcode);
                sort($references[$i]['lines']);
            }

            // Get seriesline info
            $serieslines=[];
            if(isset($data['series'])&&!empty($data['series'])) {
                foreach($data['series'] as $ser) {
                    $serieslines[$ser['value']]=$ser['location']['line'];
                }
            } elseif(isset($data['seriesconds'])&&!empty($data['seriesconds'])) {
                $indx=1;
                foreach($data['seriesconds'] as $ser) {
                    // Substract 0.5 to allow seriesconds that are on the start data line
                    // to correctly indicate the start of new series (bit of hack)
                    $serieslines[$indx]=$ser['location']['line']-0.5;
                    $indx++;
                }
            } else {
                $serieslines[1]=0;
            }

            // OK build datasets based on unique references
            $dataseriesArray=[];
            $currentIndex=0;
            foreach($references as $i=>$ref) {
                // Add the general information
                $dataseriesArray[$i]['text_file_id'] = $id;
                $dataseriesArray[$i]['system_id'] = $system_id;
                $dataseriesArray[$i]['reference_id'] = $ref['id'];
                $dataseriesArray[$i]['refcode'] = $ref['refcode'];
                $dataseriesArray[$i]['publication_id'] = $pub['id'];
                $dataseriesArray[$i]['publication_title'] = $pub['title'];
                $dataseriesArray[$i]['file_id'] = $file['id'];
                if (!empty($ptype['id'])) {
                    $dataseriesArray[$i]['propertytype_id'] = $ptype['id'];
                } else {
                    $dataseriesArray[$i]['propertytype_id'] = [];
                }
                $dataseriesArray[$i]['propertytype_id'] = $ptype['id'];
                if (!empty($ptype['Property']['type'])) {
                    $dataseriesArray[$i]['type'] = json_decode($ptype['Property']['type']);
                } else {
                    $dataseriesArray[$i]['type'] = [];
                }
                if (!empty($ptype['Property']['id'])) {
                    $dataseriesArray[$i]['property_id'] = $ptype['Property']['id'];
                } else {
                    $dataseriesArray[$i]['property_id'] = "";
                }
                $lines = $ref['lines'];
                sort($lines);
                $addtolines = [];
                if (isset($ref['addtolines'])) {
                    $addtolines = $ref['addtolines'];
                }

                // Add series to references
                $dataseriesArray[$i]['series'] = [];$rlines=$tempseries=[];
                // For lines that are xx_y format extract out the line #
                foreach ($ref['lines'] as $rline) {
                    if (stristr($rline, "_")) {
                        list($t,) = explode("_", $rline);
                    } else {
                        $t = $rline;
                    }
                    $rlines[] = $t;
                }
                $rlines = array_unique($rlines);

                foreach ($rlines as $rline) {
                    foreach($serieslines as $ser=>$serline) {
                        if (isset($serieslines[($ser + 1)])) {
                            if ($rline>=$serieslines[$ser]&&$rline<$serieslines[($ser + 1)]) {
                                $tempseries[]=$ser;
                            }
                        } else {
                            if ($rline>=$serieslines[$ser]) {
                                $tempseries[]=$ser;
                            }
                        }
                    }
                }
                $dataseriesArray[$i]['series']=array_unique($tempseries);

                //debug($dataseriesArray[$i]['series']);

                // Add the property headers if they are present (condition/data will not have property or unit ids set)
                if(isset($data['propheaders'])&&!empty($data['propheaders'])) {
                    $dataseriesArray[$i]['propheaders'] = [];$series=null;
                    foreach($data['propheaders'] as $phead) {
                        if(in_array($phead['location']['line'],$lines)) {
                            foreach($serieslines as $ser=>$serline) {
                                if($phead['location']['line']>=$serline) {
                                    $series=$ser;
                                }
                            }
                            unset($phead['location']);
                            $phead['series']=$series;
                            $dataseriesArray[$i]['propheaders'][]=$phead;
                        }
                    }
                }

                // Add the equation data if they are present
                $dataseriesArray[$i]['equations']=[];
                if(isset($data['eqnterms'])&&!empty($data['eqnterms'])) {
                    $anns=$data['annotations'];$sups=$data['suppdata'];
                    $terms=$data['eqnterms'];$vars=$data['eqnvariables'];$limits=$data['eqnvariablelimits'];
                    $ops=$data['eqnoperators'];$props=$data['eqnprops'];$propunits=$data['eqnpropunits'];

                    $eqns=[];
                    $eqns['terms'] = [];
                    foreach($terms as $term) {
                        if(in_array($term['location']['line'],$lines)) {
                            foreach($serieslines as $ser=>$serline) {
                                if($term['location']['line']>=$serline) {
                                    $series=$ser;
                                }
                            }
                            $term['series']=$series;
                            $eqns['terms'][]=$term;
                        }
                    }
                    $eqns['vars'] = [];
                    if(!empty($vars)) {
                        foreach($vars as $var) {
                            if(in_array($var['location']['line'],$lines)) {
                                foreach($serieslines as $ser=>$serline) {
                                    if($var['location']['line']>=$serline) {
                                        $series=$ser;
                                    }
                                }
                                $var['series']=$series;
                                $dataseriesArray[$i]['equations']['vars'][]=$var;
                            }
                        }
                    }
                    $eqns['limits'] = [];
                    if(!empty($limits)) {
                        foreach($limits as $limit) {
                            if(in_array($limit['location']['line'],$lines)) {
                                foreach($serieslines as $ser=>$serline) {
                                    if($limit['location']['line']>=$serline) {
                                        $series=$ser;
                                    }
                                }
                                $limit['series']=$series;
                                $eqns['limits'][]=$limit;
                            }
                        }
                    }
                    $eqns['ops'] = [];
                    if(!empty($ops)) {
                        foreach($ops as $op) {
                            if(in_array($op['location']['line'],$lines)) {
                                foreach($serieslines as $ser=>$serline) {
                                    if($op['location']['line']>=$serline) {
                                        $series=$ser;
                                    }
                                }
                                $limit['series']=$series;
                                $eqns['ops'][]=$op;
                            }
                        }
                    }
                    $eqns['props'] = [];
                    if(!empty($props)) {
                        foreach($props as $prop) {
                            if(in_array($prop['location']['line'],$lines)) {
                                foreach($serieslines as $ser=>$serline) {
                                    if($prop['location']['line']>=$serline) {
                                        $series=$ser;
                                    }
                                }
                                $limit['series']=$series;
                                $eqns['props'][]=$prop;
                            }
                        }
                    }
                    $eqns['units'] = [];
                    if(!empty($propunits)) {
                        foreach($propunits as $unit) {
                            if(in_array($unit['location']['line'],$lines)) {
                                foreach($serieslines as $ser=>$serline) {
                                    if($unit['location']['line']>=$serline) {
                                        $series=$ser;
                                    }
                                }
                                $limit['series']=$series;
                                $eqns['units'][]=$unit;
                            }
                        }
                    }
                    $eqns['anns'] = [];
                    if(!empty($anns)) {
                        foreach($anns as $a=>$ann) {
                            if(in_array($ann['location']['line'],$lines)) {
                                foreach($serieslines as $ser=>$serline) {
                                    if($ann['location']['line']>=$serline) {
                                        $series=$ser;
                                    }
                                }
                                $limit['series']=$series;
                                $eqns['anns'][]=$ann;
                                unset($data['annotations'][$a]); // Remove so not added with data points
                            }
                        }
                    }
                    $eqns['sups'] = [];
                    if(!empty($sups)) {
                        foreach($sups as $s=>$sup) {
                            if(in_array($sup['location']['line'],$lines)) {
                                foreach($serieslines as $ser=>$serline) {
                                    if($sup['location']['line']>=$serline) {
                                        $series=$ser;
                                    }
                                }
                                $limit['series']=$series;
                                $eqns['sups'][]=$sup;
                                unset($data['suppdata'][$s]); // Remove so not added with data points
                            }
                        }
                    }

                    if(!empty($eqns['terms'])) {
                        // Create array of lines with equation data (based on the prescence of terms)
                        $eqnlines=[];
                        foreach($eqns['terms'] as $term) {
                            if(!in_array($term['location']['line'],$eqnlines)) {
                                $eqnlines[]=$term['location']['line'];
                            }
                        }
                        $dataseriesArray[$i]['equations']=$this->Datarectification->getEquations($eqnlines,$addtolines,$eqns,$serieslines);
                    }
                }

                // Aggregate the data for this reference
                if($file['layout']=="rows") {
                    $dataseriesArray[$i]['points']=$this->Datarectification->rpoints($lines,$data,$serieslines);
                } elseif($file['layout']=="columns") {
                    $dataseriesArray[$i]['points']=$this->Datarectification->cpoints($lines,$data,$serieslines);
                } elseif($file['layout']=="mixed") {
                    // Data alignment varies on different rows (sigh)
                    // Separate data out by layout (rdata for row data and data for column)
                    $firstline=$lines[0];$layout="column";
                    foreach($data['data'] as $exptdata) {
                        if($exptdata['location']['line']==$firstline&&$exptdata['layout']=='row') {
                            $layout="row";break;
                        }
                    }
                    if($layout=="row") {
                        $dataseriesArray[$i]['points']=$this->Datarectification->rpoints($lines,$data,$serieslines);
                    } elseif($layout=="column") {
                        $dataseriesArray[$i]['points']=$this->Datarectification->cpoints($lines,$data,$serieslines);
                    }
                }

                //debug($dataseriesArray[$i]['points']);

                // Add the dataseries conditions
                if(isset($data['seriesconds'])&&!empty($data['seriesconds'])) {
                    $dataseriesArray[$i]['seriesconds'] = [];$series=null;
                    foreach($data['seriesconds'] as $scond) {
                        if(in_array($scond['location']['line'],$lines)) {
                            foreach($serieslines as $ser=>$serline) {
                                if($scond['location']['line']>=$serline) {
                                    $series=$ser;
                                }
                            }
                            unset($scond['location']);
                            $scond['series']=$series;
                            $dataseriesArray[$i]['seriesconds'][]=$scond;
                        }
                    }
                }
                // Add the dataseries annotations
                if(isset($data['seriesanns'])&&!empty($data['seriesanns'])) {
                    $dataseriesArray[$i]['seriesanns'] = [];$series=null;
                    foreach($data['seriesanns'] as $sann) {
                            foreach ($serieslines as $ser => $serline) {
                                if ($sann['location']['line'] >= $serline) {
                                    $series = $ser;
                                }
                            }
                            unset($sann['location']);
                            $sann['series'] = $series;
                            $dataseriesArray[$i]['seriesanns'][] = $sann;

                    }
                }
                // Add the dataseries settings
                if(isset($data['settings'])&&!empty($data['settings'])) {
                    $dataseriesArray[$i]['settings'] = [];$series=null;
                    foreach($data['settings'] as $ssett) {
                        if(in_array($ssett['location']['line'],$lines)) {
                            foreach($serieslines as $ser=>$serline) {
                                if($ssett['location']['line']>=$serline) {
                                    $series=$ser;
                                }
                            }
                            unset($ssett['location']);
                            $ssett['series']=$series;
                            $dataseriesArray[$i]['settings'][]=$ssett;
                        }
                    }
                }
            }

            //if(AuthComponent::user('type') == 'superadmin') { debug($dataseriesArray);exit; }


            // If there are extra annotations and to each series
            foreach($dataseriesArray as $i=>$ser) {
                // Is there any data left? i.e. data that should be added to all dataseries
                if(!empty($data['annotations'])) {
                    foreach($data['annotations'] as $a=>$ann) {
                        unset($ann['location']); // Not needed
                        $dataseriesArray[$i]['annotations'][]=$ann;
                    }
                }
            }

            // report: publication_id, title, file_code, page, comment
            // dataset: title, report_id, file_id, system_id, propertytype_id, reference_id, comments
            // dataseries: dataset_id, type
            // datapoints: dataseries_id, dataset_id (needed?), row_index
            // data/conditions:  dataset_id (needed?), dataseries_id (needed?), datapoint_id, property_id, datatype,
            //                   number (remove?), significand, exponent, error, error_type, unit_id, accuracy, exact, text

            //debug($dataseriesArray);exit;

            // Add report and dataset
            $datasets=[];
            foreach($dataseriesArray as &$dataInput) {
                //debug($dataInput);
                $this->Datarectification->checkAndAddDatasetAndReport($dataInput, $ajax);
                // $dataInput now contains the report_id and dataset_id
                // Create dataset array for ajax calls
                $datasets[]=$dataInput['dataset_id'];
                // Add any substance component annotations
                if(!empty($components)) {
                    foreach($components as $subid=>$compnum) {
                        $this->Annotation->create();
                        $ann=['Annotation'=>['dataset_id'=>$dataInput['dataset_id'],'substance_id'=>$subid,'text'=>"Component ".$compnum]];
                        $this->Annotation->save($ann);
                        $this->Annotation->clear();
                    }
                }
                // Save equations if present
                if(!empty($dataInput['equations'])) {
                    $this->Datarectification->addEquations($dataInput, $ptype, $ajax);
                }
                // Save points if present
                if(!empty($dataInput['points'])) {
                    $this->Datarectification->addDataAndConditions($dataInput, $ptype, $ajax);
                }
            }

            //if(AuthComponent::user('type') == 'superadmin') { exit; }

            // Update the file status
            $this->TextFile->save(['TextFile'=>['id'=>$id,'status'=>"ingested"]]);

            // Return
            if(!$ajax) {
                $this->Flash->set('Data Extracted');
                return $this->redirect('/datasets/view/'.$dataInput['dataset_id']);
            } else {
                die('{ "status": "success", "datasets": '.json_encode($datasets).' }');
            }
        } else {
            $file = $this->File->find('list', ['fields'=>['id','filename']]);
            $this->set('file', $file);
        }
        return false;
    }
}
