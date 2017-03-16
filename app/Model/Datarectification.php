<?php

/**
 * Class Datarectification
 */
class Datarectification extends AppModel {

    /**
     * Organize the equation data
     * @param $lines
     * @param $addtolines
     * @param $eqndata
     * @param $serieslines
     * @return array
     */
    public function getEquations($lines,$addtolines,$eqndata,$serieslines)
    {
        $anns=$eqndata['anns'];$sups=$eqndata['sups'];
        $terms=$eqndata['terms'];$vars=$eqndata['vars'];$limits=$eqndata['limits'];
        $ops=$eqndata['ops'];$props=$eqndata['props'];$units=$eqndata['units'];
        $eqns=[];$series=null;

        //debug($lines);debug($terms);exit;
        // Split out data into separate equations (datalines and lines are correlated on the key)
        foreach($lines as $line) {
            // Define the series
            foreach($serieslines as $serid=>$sline) {
                if($line>$sline) {
                    $series=$serid;
                } elseif ($line==$sline) {
                    $series=null;
                    break;
                }
            }

            // Aggregate equation data (terms, etc.) on multiple lines
            $dataline=null;
            if(!empty($addtolines)) {
                $keys=array_keys($addtolines);
                if(in_array($line,$keys)) {
                    $dataline=$line;
                } else {
                    foreach($addtolines as $l=>$otherlines) {
                        if(in_array($line,$otherlines)) {
                            $dataline=$l;break;
                        }
                    }
                }
            } else {
                $dataline=$line;
            }
            // For lines where there are no continuation lines and thus not in the $addtolines array
            if(is_null($dataline)) {
                $dataline=$line;
            }
            // Organize data
            $eqns[$dataline]['series']=$series;

            if(!empty($terms)) { // Ignore lines that are not equations (as defined by the prescence of a term)
                foreach($terms as $term) {
                    if($term['location']['line']==$line) {
                        unset($term['location']);
                        $eqns[$dataline]['terms'][]=$term;
                    }
                }
                foreach($vars as $var) {
                    if($var['location']['line']==$line) {
                        unset($var['location']);
                        $eqns[$dataline]['vars'][]=$var;
                    }
                }
                foreach($limits as $limit) {
                    if($limit['location']['line']==$line) {
                        unset($limit['location']);
                        $eqns[$dataline]['limits'][]=$limit;
                    }
                }
                foreach($ops as $op) {
                    if($op['location']['line']==$line) {
                        unset($op['location']);
                        $eqns[$dataline]['ops'][]=$op;
                    }
                }
                foreach($props as $prop) {
                    if($prop['location']['line']==$line) {
                        unset($prop['location']);
                        $eqns[$dataline]['props'][]=$prop;
                    }
                }
                foreach($units as $unit) {
                    if($unit['location']['line']==$line) {
                        unset($unit['location']);
                        $eqns[$dataline]['units'][]=$unit;
                    }
                }
                foreach($anns as $ann) {
                    if($ann['location']['line']==$line) {
                        unset($ann['location']);
                        $eqns[$dataline]['anns'][]=$ann;
                    }
                }
                foreach($sups as $sup) {
                    if($sup['location']['line']==$line) {
                        unset($sup['location']);
                        $eqns[$dataline]['sups'][]=$sup;
                    }
                }
            }
        }
        return $eqns;
    }

    /**
     * Check and/or add substances
     * @param array $chemicals
     * @param boolean $ajax
     * @param integer $id
     */
    public function checkAndAddSubstances(&$chemicals,$ajax,$id)
    {
        $Substance=ClassRegistry::init('Substance');
        $Identifier=ClassRegistry::init('Identifier');
        foreach ($chemicals as &$chemical) {

            if(isset($chemical['othernames'])) {
                foreach($chemical['othernames'] as $part) {
                    if(substr($chemical['name'],-1)=="-") {
                        $chemical['name'].=$part;
                    } else {
                        $chemical['name'].=" ".$part;
                    }
                }
            }

            $ident=$Identifier->find('all',['conditions' => ['Identifier.value'=>$chemical['casrn']]]);
            if (empty($ident)) {
                // We don't see this one in the database by CAS number (so add it)
                $Substance->create();
                $chemInfo=['Substance'=>['name'=>$chemical['name'],'formula'=>$chemical['formula'],'casrn'=>$chemical['casrn']]];
                if(!$Substance->save($chemInfo)) {
                    $message='Substance not saved (drmodel: '.__LINE__.')';
                    if(!$ajax) {
                        $this->Flash->set($message);
                        return $this->redirect('/textfiles/view/'.$id);
                    } else {
                        die('{"status": "error","message":'.$message.'}');
                    }
                }
                $sid=$Substance->id;
                $Substance->clear();

                // Create and save the CAS identifier
                $Identifier->create();
                $identInfo=['Identifier'=>['type'=>'casrn','value'=>$chemical['casrn'],'substance_id'=>$sid]];
                if(!$Identifier->save($identInfo)) {
                    $message='Identifier not save correctly (drmodel: '.__LINE__.')';
                    if(!$ajax) {
                        $this->Flash->set($message);
                        return $this->redirect('/textfiles/view/'.$id);
                    } else {
                        die('{"status": "error","message":'.$message.'}');
                    }
                }
                $Identifier->clear();

                // Get other identifiers for this chemical from PubChem/ChemSpider (SJC)
                $c=$Substance->find('first',['recursive'=>1,'conditions'=>['id'=>$sid],'contain'=>['Identifier'=>['conditions'=>['type'=>'casrn']]]]);
                $Substance->meta($c);
                $chemical['id']=$sid;
            } else {
                // Compound is already in the database
                $ident[0]['Substance']['name']=$chemical['name']; // Why update the chemical name if its already in the system?
                $Identifier->save($ident[0]);
                $chemical['id']=$ident[0]['Substance']['id']; // We already have it in the database save id by reference
            }

            // Add other names if the are available
            if(isset($chemical['othernames'])) {
                $ident2=$Identifier->find('first',['conditions' => ['Identifier.type'=>'othername','substance_id'=>$chemical['id']]]);
                if(empty($ident2)) {
                    $names=json_encode($chemical['othernames']);
                    $Identifier->create();
                    $identInfo=['Identifier'=>['type'=>'othername','value'=>$names,'substance_id'=>$chemical['id']]];
                    $Identifier->save($identInfo);
                    $Identifier->clear();
                }
            }
        }
    }

    /**
     * Check and/or add system
     * @param $chemicals
     * @param $ajax
     * @return mixed
     */
    public function checkAndAddSystem(&$chemicals,$ajax)
    {
        // Chemicals array must start from key 1
        $System=ClassRegistry::init('System');
        $Sub=ClassRegistry::init('Substance');
        $substances_systems=ClassRegistry::init('substances_systems');
        $joins=$chemicalArray=[];$result=0;$cnt=count($chemicals);

        foreach($chemicals as $i=>$chemical) {
            $chemicalArray[]=$chemical['id'];
        }
        $joins[]=[
            'table' => 'substances_systems',
            'alias' => 'SubstanceSystem',
            'type' => 'inner',
            'conditions' => [
                'System.id = SubstanceSystem.system_id',
                'SubstanceSystem.substance_id'=>$chemicalArray
            ]
        ];
        $systems=$System->find('all',[
            'joins' => $joins,
            'fields' => ['System.id','System.name'],
            'contain' => ['Substance'],
            'group'=> 'SubstanceSystem.system_id'
        ]);
        foreach ($systems as $system) {
            if (count($system['Substance'])!=$cnt) {
                continue;
            }
            $match=0;
            foreach ($chemicals as $i=>$chemical) {
                foreach ($system['Substance'] as $sub) {
                    if ($sub['id']==$chemical['id']) {
                        $match++;
                        continue 2;
                    }
                }
            }
            if($match==$cnt){
                $result=$system['System']['id'];
            }
        }

        if(empty($result)) {
            $name="";$desc="";
            $identifiers=[]; // Array to hold chemical ids to create identifier to add to systems table

            //debug($chemicals);exit;

            foreach ($chemicals as $i=>$chem) {
                if(!isset($chem['name'])) {
                    $cname=$Sub->find('list',['fields'=>['id','name'],'conditions'=>['id'=>$chem['id']]]);
                    $chem['name']=$cname[$chem['id']];
                }
                $cname=ucfirst($chem['name']);
                if ($cnt>1) {
                    // Name of system expects chemicals to be indexed starting at zero
                    if ($i==($cnt-1)){
                        $name.="and ".$cname;
                    } elseif ($i==($cnt-2)) {
                        $name.=$cname." ";
                    } else {
                        $name.=$cname.", ";
                    }
                    if (count($chemicals)==2) {
                        $num="two";
                    } elseif(count($chemicals)==3) {
                        $num="three";
                    }
                    $desc="Mixture of ".$num." substances";
                    $comp="binary mixture";
                    $identifiers[]=(string) $chem['id'];
                } else {
                    $name=ucfirst($cname)." (pure)";
                    $desc="Pure substance";
                    $comp="pure compound";
                    $identifiers[]=(string) $chem['id'];
                }
            }

            $i=implode(":",$identifiers);
            $data=['System'=>['name'=>$name,'description'=>$desc,'composition'=>$comp,'type'=>'Single phase fluid','identifier'=>$i]];
            $System->create();
            $System->save($data);
            foreach ($chemicals as $chem) {
                $substances_systems->create();
                $data=['substances_systems'=>['substance_id'=>$chem['id'],'system_id'=>$System->id]];
                $substances_systems->save($data);
            }
            return $System->id;
        } else {
            return $result;
        }
    }

    /**
     * Check and/or add reference
     * @param $ref
     * @param $fid
     * @return int
     */
    public function checkAndAddRef(&$ref,$fid)
    {
        $Ref=ClassRegistry::init('Reference');
        $Cod=ClassRegistry::init('Refcode');
        $Err=ClassRegistry::init('Error');

        $rid="";
        if($ref['type']=='refcode') {
            $result = $Cod->find('first',['conditions'=>['code'=>str_replace(" ","",$ref['value']),'publication_id'=>$ref['publication_id']]]);
            if(empty($result)) {
                $rid="000000";  // No reference found
            } else {
                $rid=$result['Refcode']['reference_id'];
            }
        } elseif($ref['type']=='cite') {
            if(!empty($ref['code'])) {
                $result = $Cod->find('first',['conditions'=>['code'=>str_replace(" ","",$ref['code']),'publication_id'=>$ref['publication_id']]]);
                if(!empty($result)) {
                    $rid=$result['Refcode']['reference_id'];
                } else {
                    // refcode present but not added yet
                    if(!stristr($ref['value'],'thesis')&&!stristr($ref['value'],'dissertation')) {
                        if(empty($ref['doi'])) {
                            // No DOI so see if we can get a hit at crossref
                            $found=$Ref->crossrefsearch($ref['value']);
                            if(!empty($found['doi'])) {
                                $ref['doi']=$found['doi'];
                            }
                        }
                    }
                    if(!empty($ref['doi'])) {
                        // Find reference if it is already in DB
                        $hit=$Ref->find('first',['conditions'=>['url like'=>'%'.$ref['doi'].'%']]);
                        if(!empty($hit)) {
                            // In DB so get reference_id
                            $rid=$hit['Reference']['id'];
                        } else {
                            // Not in DB, so add
                            $meta=$Ref->addbydoi($ref['doi']);
                            $rid=$meta['Reference']['id'];
                        }
                    } else {
                        // Try and find (match bibliography field)
                        $hit=$Ref->find('first',['conditions'=>['bibliography'=>$ref['value']]]);
                        if(!empty($hit)) {
                            $rid=$hit['Reference']['id'];
                        } else {
                            // OK add it as is
                            $r=['title'=>'Unknown reference','bibliography'=>$ref['value']];
                            $Ref->create();
                            $Ref->save(["Reference"=>$r]);
                            $rid=$Ref->id;
                        }
                    }
                    // OK add refcode
                    $cite=['reference_id'=>$rid,'publication_id'=>$ref['publication_id'],'code'=>$ref['code']];
                    $Cod->create();
                    $Cod->save(['Refcode'=>$cite]);
                    $Cod->clear();
                }
            } else {
                // Create refcode?
            }
        } else {
            $found=$Ref->crossrefsearch($ref['value']);
            if(!empty($found)) {
                $hit="";
                // Find based on doi
                if(isset($found['doi'])) {
                    $hit=$Ref->find('first',['conditions'=>['url like'=>'%'.$found['doi'].'%']]);
                }
                // Find based on url
                if(empty($hit)&&isset($found['url'])) {
                    $hit=$Ref->find('first',['conditions'=>['url'=>$found['url']]]);
                }
                // Find based on metadata
                if(empty($hit)) {
                    (isset($found['journal'])) ? $j=$found['journal'] : $j="";
                    (isset($found['year'])) ? $y=$found['year'] : $y="";
                    (isset($found['volume'])) ? $v=$found['volume'] : $v="";
                    (isset($found['startpage'])) ? $s=$found['startpage'] : $s="";
                    $meta=['journal'=>$j,"year"=>$y,"volume"=>$v,"startpage"=>$s];
                    $hit=$Ref->find('first',['conditions'=>$meta]);
                }
                // OK not found so add it
                if(empty($hit)) {
                    if(isset($found['doi'])) {
                        $hit=$Ref->addbydoi($found['doi']);
                    } elseif(isset($ref['value'])) {
                        $r=['title'=>'Unknown reference','bibliography'=>$ref['value']];
                        $Ref->create();
                        $hit=$Ref->save(["Reference"=>$r]);
                        $Ref->clear();
                    } else {
                        (isset($found['endpage'])) ? $meta['endpage']=$found['endpage'] : $meta['endpage']="";
                        (isset($found['authors'])) ? $meta['authors']=$found['endpage'] : $meta['authors']="";
                        (isset($found['title']))   ? $meta['title']=$found['title'] : $meta['title']="";
                        $Ref->create();
                        $hit=$Ref->save(["Reference"=>$meta]);
                        $Ref->clear();
                    }
                }
                if($ref['type']=='cite') {
                    if(!empty($ref['code'])) {
                        // Citation from XML file
                        $cite=['reference_id'=>$hit['Reference']['id'],'publication_id'=>$ref['publication_id'],'code'=>$ref['code']];
                        $Cod->create();
                        $Cod->save(['Refcode'=>$cite]);
                        $Cod->clear();
                    }
                }
                // Set the rid if found
                if(!empty($hit)) {
                    $rid=$hit['Reference']['id'];
                } else {
                    $rid="000000";  // No reference found
                }
            } else {
                $Ref->create();
                $Ref->save(["Reference"=>['title'=>'Unknown reference','bibliography'=>$ref['value']]]);
                $rid=$Ref->id;
                $error=['Error'=>['errorType'=>1,'errorText'=>'Reference Not Found','value'=>$ref['value'],'file'=>$fid]];
                $Err->create();
                $Err->save($error);
                if(isset($ref['code'])) {
                    // need publication_id,
                    $ref['reference_id']=$rid;
                    unset($ref['doi']);
                    $Cod->create();
                    $Cod->save(['Refcode'=>$ref]);
                }
            }
        }
        return $rid;
    }

    /**
     * Check and/or add dataset and report
     * @param $data
     * @param $ajax
     */
    public function checkAndAddDatasetAndReport(&$data,$ajax)
    {
        // Load the models
        $Report=ClassRegistry::init('Report');
        $Dataset=ClassRegistry::init('Dataset');
        $System=ClassRegistry::init('System');
        $Ref=ClassRegistry::init('Reference');
        $Publication=ClassRegistry::init('Publication');
        $Property=ClassRegistry::init('Property');

        //debug($data);exit;

        // Replace existing report (and all related data) based on the file_id and reference_id
        if($set=$Dataset->find('first',['conditions'=>['text_file_id'=>$data['text_file_id'],'reference_id'=>$data['reference_id']]])) {
            $Report->delete($set['Dataset']['report_id']);
        }

        // If there is no filenum add an null
        if(!isset($data['fileNum'])) {
            $data['fileNum']=null;
        }

        // Get the system data
        $sysid=$data['system_id'];
        $system=$System->find('list',['conditions' => ['id'=>$sysid],'fields'=>['id','name']]);

        // Get the property data
        if(!empty($data['property_id'])) {
            $propid=$data['property_id'];
            $property=$Property->find('list',['conditions' => ['id'=>$propid],'fields'=>['id','symbol']]);
        } else {
            $property="";
        }

        // Get refcode for this pub to add to title
        $refcode=" (Ref: ".$data['refcode'].")";

        // Create the report title
        if(!empty($property)) {
            $rtitle=$system[$sysid]." (".strip_tags($property[$propid]).")".$refcode;
        } else {
            $rtitle=$system[$sysid]." (?)".$refcode;
        }

        // Add report
        $reportArray=["Report"=>['title'=>$rtitle,"file_code"=>$data['fileNum'],'publication_id'=>$data['publication_id']]];
        $Report->create();
        $Report->save($reportArray);

        // Save report id
        $data['report_id']=$Report->id;

        // Create the dataset title
        $pub=$Publication->find('first',['conditions'=>['Publication.id'=>$data['publication_id']]]);
        $ptitle=$pub['Publication']['abbrev'].": ".$rtitle;

        // Add dataset
        $dataArray=['Dataset'=>['title'=>$ptitle,'file_id'=>$data['file_id'],"text_file_id"=>$data['text_file_id'],'propertytype_id'=>$data['propertytype_id'],
            'system_id'=>$data['system_id'],'reference_id'=>$data['reference_id'],'report_id'=>$Report->id,'comments'=>$data['refcode']]];
        $Dataset->create();
        $Dataset->save($dataArray);

        // Save dataset id
        $data['dataset_id']=$Dataset->id;

    }

    /**
     * Get Reference (from file)
     * @param $file
     * @param $ajax
     * @return array|bool
     */
    private function getReference($file,$ajax)
    {
        //$File=ClassRegistry::init('File');//load the Identifier model
        //$file=$File->find('first',['conditions'=>['File.id'=>$fileID],'recursive'=>-1]); //get the file of interest
        //debug($file);exit;
        $file['filename']=substr($file['filename'],0,strpos($file['filename'],"."));
        $fileToExtract=WWW_ROOT.'files'.DS.'refs'.DS.$file['publication_id'].DS.$file['filename'].".xml";// find the path to the file name
        if(!function_exists('simplexml_load_file')) {
            return false;
        }
        if(!file_exists($fileToExtract)){
            return false;
        }
        $xml=simplexml_load_file($fileToExtract);
        if(!$xml){
            return null;
        }
        $ref=$xml->Series->Book->Chapter->ChapterBackmatter->Bibliography->Citation->BibArticle; //get to the citation
        if (!isset($ref->Year)){
            if (!$ajax) {
                trigger_error('File Reference Missing Information',E_USER_ERROR);
            } else {
                die('{"error":"File Reference Missing Information"}');
            }
        }
        $reference=['Reference'=>[]];
        $reference['Reference']['journal']=(string)$ref->JournalTitle;
        $reference['Reference']['title']=(string)$ref->ArticleTitle;
        $reference['Reference']['year']=(string)$ref->Year;
        $reference['Reference']['volume']=(string)$ref->VolumeID;
        $reference['Reference']['startpage']=(string)$ref->FirstPage;
        $reference['Reference']['endpage']=(string)$ref->LastPage;
        $reference['Reference']['issue']="";
        if(isset($ref->Issue)){
            $reference['Reference']['issue']=(string)$ref->Issue;
        }
        $reference['Reference']['authors']="";
        foreach($ref->BibAuthorName as $author){
            $reference['Reference']['authors'].=(string)$author->Initials;
            $reference['Reference']['authors'].=" ".(string)$author->FamilyName.", ";
        }
        $reference['Reference']['authors']=substr($reference['Reference']['authors'],0,strlen($reference['Reference']['authors'])-2); //remove trailing comma
        return $reference;
    }

    /**
     * Add equation data
     * @param $data
     * @param $propertyType
     * @param $ajax
     * @return mixed
     */
    public function addEquations(&$data,$propertyType,$ajax)
    {
        // Load models
        $Dataseries=ClassRegistry::init('Dataseries');
        $Annotation=ClassRegistry::init('Annotation');
        $SuppData=ClassRegistry::init('SupplementalData');
        $Set=ClassRegistry::init('Dataset');
        $Equation=ClassRegistry::init('Equation');
        $Eqnops=ClassRegistry::init('Eqnops');
        $Eqnterm=ClassRegistry::init('Eqnterm');
        $Eqnvar=ClassRegistry::init('Eqnvar');
        $Eqntype=ClassRegistry::init('Eqntype');
        $System=ClassRegistry::init('System');
        $Property=ClassRegistry::init('Property');
        $DataSys=ClassRegistry::init('DataSystem');
        $Unit=ClassRegistry::init('Unit');

        $setid=$data['dataset_id'];
        $sysid=$data['system_id'];
        $system=$System->find('first',['conditions'=>['System.id'=>$sysid],'recursive'=>0]);
        $sys=$system['System'];

        // Create point(s)
        foreach ($data['equations'] as $row => $eqn) {
            // One equation per series
            $Dataseries->create();
            $Dataseries->save(['Dataseries' => ['dataset_id' => $setid, 'type' => 'equation']]);
            $dsid = $Dataseries->id;
            $Dataseries->clear();

            // Get the eqntype_id, property_id, and unit_id
            $propids=[];

            foreach($eqn['terms'] as $term) {
                $propids[]=$term['property'];
            }

            $c=['Eqntype'];
            $eqntypes = $Property->find('list', ['fields'=>['Eqntype.id','Eqntype.level'],'conditions'=>['Property.id'=>$propids], 'contain' => $c, 'recursive' => -1]);

            $eqnid=array_search(max($eqntypes),$eqntypes);
            $temp=$Eqntype->find('first',['conditions'=>['id'=>$eqnid],'recursive'=>0]);
            $eqntype = $temp['Eqntype'];
            $eqntypeid = $eqntype['id'];
            $rpid = $eqntype['resultprop_id'];
            $ruid = $eqntype['resultunit_id'];
            $vpid = $eqntype['varprop_id'];
            $vuid = $eqntype['varunit_id'];

            // Create an equation for each one in this set
            $title = $eqntype['name'] . ' (' . $sys['name'] . ')';
            $eqnArray = ['Equation' => ['title' => $title, 'eqntype_id' => $eqntypeid, 'dataseries_id' => $dsid, 'property_id' => $rpid, 'unit_id' => $ruid]];
            $Equation->create();
            $Equation->save($eqnArray);
            $eqn['equation_id'] = $Equation->id;
            $Equation->clear();

            // Add equation info
            if(!empty($eqn['terms'])) {
                foreach($eqn['terms'] as $i=>$term) {
                    if(stristr($term['value']," x 10")) {
                        if(preg_match("/ x 10$/",$term['value'])) {
                            $term['value']=str_replace(" x 10","",$term['value']);
                            $term['value']=str_replace("- ","-",$term['value']);
                            $term['value']=str_replace("+ ","",$term['value']);
                            $term['value']=(10*$term['value']);
                        } else {
                            $term['value']=str_replace(" x 10","e",$term['value']);
                        }
                    }
                    $term['value']=str_replace("- ","-",$term['value']); // For data that is negative and has an extra space in it
                    $term['value']=str_replace("+ ","",$term['value']); // For data that is negative and has an extra space in it
                    $termArray=['Eqnterm'=>['index'=>($i+1),'code'=>'term'.($i+1),'type'=>'constant','equation_id'=>$eqn['equation_id'],'value'=>$term['value'],'property_id'=>$term['property'],'unit_id'=>$term['unit']]];
                    $Eqnterm->create();
                    $Eqnterm->save($termArray);
                    $eqn['eqnterm_id'][] = $Eqnterm->id;
                    $Eqnterm->clear();
                }
            }
            if(!empty($eqn['limits'])) {
                $varArray=['Eqnvar'=>['index'=>1,'code'=>'var1','equation_id'=>$eqn['equation_id'],'property_id'=>$vpid,'unit_id'=>$vuid]];
                foreach($eqn['limits'] as $limit) {
                    if(stristr($limit['label'],'min')) {
                        $varArray['Eqnvar']['min']=$limit['value'];
                    } elseif(stristr($limit['label'],'max')) {
                        $varArray['Eqnvar']['max']=$limit['value'];
                    }
                }
                $Eqnvar->create();
                $Eqnvar->save($varArray);
                $eqn['eqnvar_id'][] = $Eqnvar->id;
                $Eqnvar->clear();
            }
            if(!empty($eqn['anns'])) {
                foreach($eqn['anns'] as $ann) {
                    $annArray=['Annotation'=>['equation_id'=>$eqn['equation_id'],'text'=>$ann['value'],'type'=>$ann['label']]];
                    $Annotation->create();
                    $Annotation->save($annArray);
                    $eqn['annotation_id'][] = $Annotation->id;
                    $Annotation->clear();
                }
            }
            if(!empty($eqn['sups'])) {
                foreach($eqn['sups'] as $sup) {
                    if($sup['value']!='') {
                        $value=$sup['value'];
                        if(is_numeric($value)) {
                            if(is_array($value)) {
                                $e=$this->exponentialGen($value[0]);
                                $stype="array";
                            } else {
                                $e=$this->exponentialGen($value);
                                $stype="datum";
                            }
                            ($sup['datatype']=="integer") ? $exact=1 : $exact=0;
                            (isset($sup['error'])) ? $error=$sup['error'] : $error=$e['error'];
                            $suppArray=['SupplementalData'=>['equation_id'=>$eqn['equation_id'],'property_id'=>$sup['property'],'dataformat'=>$stype,
                                'number'=>$e['scinot'],'significand'=>$e['significand'],'exponent'=>$e['exponent'],'error'=>$error,
                                'metadata_id'=>$sup['metadata'],'error_type'=>'absolute','unit_id'=>$sup['unit'],'accuracy'=>$e['dp'],'exact'=>$exact]];
                        } elseif($sup['datatype']=="string"||is_string($value)) {
                            $suppArray=['SupplementalData'=>['equation_id'=>$eqn['equation_id'],'property_id'=>$sup['property'],'unit_id'=>$sup['unit'],
                                'metadata_id'=>$sup['metadata'], 'datatype'=>$sup['datatype'],'dataformat'=>'datum','text'=>$value]];
                        }
                        $SuppData->create();
                        $SuppData->save($suppArray);
                        $eqn['suppdata_id'][] = $SuppData->id;
                        $SuppData->clear();
                    }
                }
            }

            // Add reference to equation in data_systems table
            $DataSys->create();
            $DataSys->save(['DataSystem'=>['dataset_id'=>$data['dataset_id'],'equation_id'=>$eqn['equation_id'],'system_id'=>$sysid]]);
            $DataSys->clear();
        }

        return true;
    }

    /**
     * Add data and conditions
     * @param $data
     * @param $propertyType
     * @param $ajax
     * @return boolean
     */
    public function addDataAndConditions(&$data,$propertyType,$ajax)
    {
        // Load models
        $Dataseries=ClassRegistry::init('Dataseries');
        $Condition=ClassRegistry::init('Condition');
        $Datapoint=ClassRegistry::init('Datapoint');
        $Annotation=ClassRegistry::init('Annotation');
        $Setting=ClassRegistry::init('Setting');
        $Data=ClassRegistry::init('Data');
        $SuppData=ClassRegistry::init('SupplementalData');
        $errors=ClassRegistry::init('Error');
        $Set=ClassRegistry::init('Dataset');
        $DataSys=ClassRegistry::init('DataSystem');
        $Prop=ClassRegistry::init('Properties');
        $Qunit=ClassRegistry::init('QuantitiesUnit');

        // Create dataseries
        // dataseries: dataset_id, type
        // Work out type
        $type="";
        if(!empty($data['type'])) {
            if(count($data['type'])==1) {
                $type=$data['type'][0];
            } else {
                // TODO: Add code to deal with situation where data can be one of a number of types e.g. absorbance
            }
        }

        //if(AuthComponent::user('type') == 'superadmin') { debug($type);debug($data); }

        // Split up data into series based on series conditions
        // TODO: Does this work for more than one series condition?
        $seriesArray=[];
        if(isset($data['series'])&&!empty($data['series'])) {
            foreach($data['series'] as $series) {
                if(!empty($data['points'])) {
                    foreach($data['points'] as $p) {
                        if($p['series']==$series) {
                            $seriesArray[$series]['points'][]=$p;
                        }
                    }
                }
                if(!empty($data['seriesconds'])) {
                    foreach($data['seriesconds'] as $c) {
                        if($c['series']==$series) {
                            $seriesArray[$series]['seriesconds'][]=$c;
                        }
                    }
                }
                if(!empty($data['seriesanns'])) {
                    foreach($data['seriesanns'] as $a) {
                        if($a['series']==$series) {
                            $seriesArray[$series]['seriesanns'][]=$a;
                        }
                    }
                }
                if(!empty($data['settings'])) {
                    foreach($data['settings'] as $s) {
                        if($s['series']==$series) {
                            $seriesArray[$series]['settings'][]=$s;
                        }
                    }
                }
                if(!empty($data['propheaders'])) {
                    foreach($data['propheaders'] as $s) {
                        if($s['series']==$series) {
                            $seriesArray[$series]['propheaders'][]=$s;
                        }
                    }
                }
            }
        } else {
            // No series or not set...
            $seriesArray[1]['points']=$data['points'];
            if(!empty($data['seriesconds'])) {
                $seriesArray[1]['seriesconds']=$data['seriesconds'];
            } else {
                $seriesArray[1]['seriesconds']=[];
            }
            if(!empty($data['settings'])) {
                $seriesArray[1]['settings']=$data['settings'];
            } else {
                $seriesArray[1]['settings'] = [];
            }
            if(!empty($data['propheaders'])) {
                $seriesArray[1]['propheaders']=$data['propheaders'];
            } else {
                $seriesArray[1]['propheaders'] = [];
            }
        }

        //if(AuthComponent::user('type') == 'superadmin') { debug($seriesArray); }

        // Add all series
        foreach($seriesArray as $series) {

            //if(AuthComponent::user('type') == 'superadmin') { debug($type); }

            //debug($series);
            $Dataseries->create();
            $Dataseries->save(['Dataseries'=>['dataset_id'=>$data['dataset_id'],'type'=>$type]]);
            $series['dataseries_id']=$Dataseries->id;
            $Dataseries->clear();

            // Get system id so that a reference to the data can be added to data_systems
            $set=$Set->find('list',['fields'=>['id','system_id'],'conditions'=>['id'=>$data['dataset_id']]]);
            $sysid=$set[str_pad($data['dataset_id'],6,"0",STR_PAD_LEFT)];

            // Configure column header (property) data if present
            $condhdrs=$datahdrs=[];
            if(isset($series['propheaders'])&&!empty($series['propheaders'])) {
                $hcount=count($series['propheaders']);
                $ccount=count($series['points'][0]['conditions']);
                $dcount=count($series['points'][0]['data']);
                if($hcount==($ccount+$dcount)) {
                    foreach($series['propheaders'] as $idx=>$hdr) {
                        // Find property using header text
                        $prop=$Prop->find('list',['fields'=>['id','quantity_id'],'conditions'=>['field like'=> '%"'.$hdr['value'].'"%']]);
                        if(!empty($prop)) {
                            if(count($prop)==1) {
                                $propid=key($prop);
                                $quanid=$prop[$propid];
                                $row=$Qunit->find('list',['fields'=>['quantity_id','unit_id'],'conditions'=>['header like'=>'%"'.$hdr['value'].'"%']]);
                                //debug($quanid);debug($row);

                                if($idx<$ccount) {
                                    $condhdrs[]=["property_id"=>$propid,"unit_id"=>$row[$quanid]];
                                } else {
                                    $datahdrs[]=["property_id"=>$propid,"unit_id"=>$row[$quanid]];
                                }
                            } else {
                                die('{"error":"multiple proprerties matching header",'.json_encode($prop).'}');
                            }
                        } else {
                            die('{"error":"headers not found in DB",'.json_encode($hdr).'}');
                        }
                    }
                } else {
                    debug($series);
                    die('{"error":"# column headers does not match # conditions + # data"}');
                }
            }

            //if(AuthComponent::user('type') == 'superadmin') { pr($series); }

            // Create point(s)
            foreach($series['points'] as $row=>$datum) {
                // Create a datapoint for each data value in this set
                // datapoints: dataseries_id, dataset_id (needed?), row_index (line # from text file)
                // Note row_index is set here using the $data['data'] from the text file
                $pointArray=['Datapoint'=>['dataseries_id'=>$series['dataseries_id'],'row_index'=>$row]];
                $Datapoint->create();
                $Datapoint->save($pointArray);
                $datum['datapoint_id']=$Datapoint->id;
                $Datapoint->clear();

                // Now add the data, condition(s), supplementaldata, annotations
                // data/conditions/suppdata:  dataset_id (needed?), dataseries_id (needed?), datapoint_id, property_id, datatype,
                //                   number (remove?), significand, exponent, error, error_type, unit_id, accuracy, exact, text
                // annotations: datapoint_id, text, comment?

                $eindex=0;$com=null;
                // Conditions
                foreach($datum['conditions'] as $cidx=>$c) {
                    // Add property and unit id if availble from table header
                    if(isset($condhdrs[$cidx])) {
                        $c['property']=$condhdrs[$cidx]['property_id'];
                        $c['unit']=$condhdrs[$cidx]['unit_id'];
                    }
                    $value=$c['value'];
                    if(is_array($value)) {
                        $ctype="array";
                    } else {
                        $ctype="datum";
                    }
                    if(is_numeric($value)) {
                        if(is_array($value)) {
                            $e=$this->exponentialGen($value[0]);
                        } else {
                            $e=$this->exponentialGen($value);
                        }
                        ($c['datatype']=="integer") ? $exact=1 : $exact=0;
                        if(isset($datum['errors'][$eindex])&&$datum['errors'][$eindex]['label']==$c['label']) {
                            $error=$datum['errors'][$eindex]['value'];$eindex++;
                        } elseif(isset($datum['errors'][$eindex])&&$datum['errors'][$eindex]['label']!=$c['label']) {
                            $com='Error set ('.$datum['errors'][$eindex]['value'].') but labels dont match';
                            $error=$e['error'];
                        } else {
                            $error=$e['error'];
                        }
                        $condArray=['Condition'=>['datapoint_id'=>$datum['datapoint_id'],'property_id'=>$c['property'],'datatype'=>$ctype,
                            'number'=>$e['scinot'],'significand'=>$e['significand'],'exponent'=>$e['exponent'],'error'=>$error,
                            'error_type'=>'absolute','unit_id'=>$c['unit'],'accuracy'=>$e['dp'],'exact'=>$exact,'comments'=>$com]];
                        $Condition->create();
                        $Condition->save($condArray);
                        $data['conditions'][$cidx]=$Condition->id;
                        $Condition->clear();
                    } elseif(is_string($value)) {
                        $number=$exponent=$significand=$comments=$error=$accuracy=null;$exact=0;
                        if(preg_match("/^([0-9]\.[0-9]*)\h10([0-9]*)$/",$value,$matches)) {
                            // Matches x.xxx 10y format (no +|-)
                            $number=(float) $matches[1]."e".$matches[2];
                            $significand=$matches[1];
                            $exponent=$matches[2];
                            $e=$this->exponentialGen($matches[1]);
                            $error=$e['error']."e".$matches[2];
                            $accuracy=strlen($matches[1])-1;
                            $comments=$value;$value=null;
                        }
                        $condArray=['Condition'=>['datapoint_id'=>$datum['datapoint_id'],'property_id'=>$c['property'],'exact'=>$exact,
                            'number'=>$number,'significand'=>$significand,'exponent'=>$exponent,'unit_id'=>$c['unit'],'error'=>$error,
                            'metadata_id'=>$c['metadata'],'datatype'=>$ctype,'text'=>$value,'comments'=>$comments,'accuracy'=>$accuracy]];
                        $Condition->create();
                        $Condition->save($condArray);
                        $data['conditions'][$cidx]=$Condition->id;
                        $Condition->clear();
                    }
                }

                // Data
                foreach($datum['data'] as $didx=>$d) {
                    // Add property and unit id if availble from table header
                    if(isset($datahdrs[$didx])) {
                        $d['property']=$datahdrs[$didx]['property_id'];
                        $d['unit']=$datahdrs[$didx]['unit_id'];
                    }
                    $value=$d['value'];
                    if(is_array($d['value'])) {
                        $dtype="array";
                    } else {
                        $dtype="datum";
                    }
                    if(is_numeric($value)) {
                        if(is_array($d['value'])) {
                            $e=$this->exponentialGen($value[0]);
                        } else {
                            $e=$this->exponentialGen($value);
                        }
                        ($d['datatype']=="integer") ? $exact=1 : $exact=0;
                        if(isset($datum['errors'][$eindex])&&$datum['errors'][$eindex]['label']==$d['label']) {
                            $error=$datum['errors'][$eindex]['value'];$eindex++;
                        } elseif(isset($datum['errors'][$eindex])&&$datum['errors'][$eindex]['label']!=$d['label']) {
                            $com='Error set ('.$datum['errors'][$eindex]['value'].') but labels dont match';
                            $error=$e['error'];
                        } else {
                            $error=$e['error'];
                        }
                        $dataArray=['Data'=>['datapoint_id'=>$datum['datapoint_id'],'property_id'=>$d['property'],'datatype'=>$dtype,
                            'number'=>$e['scinot'],'significand'=>$e['significand'],'exponent'=>$e['exponent'],'error'=>$error,
                            'error_type'=>'absolute','unit_id'=>$d['unit'],'accuracy'=>$e['dp'],'exact'=>$exact,'comments'=>$com]];
                    } elseif(is_string($value)) {
                        $number=$exponent=$significand=$comments=$error=$accuracy=null;$exact=0;
                        if(preg_match("/^([0-9]\.[0-9]*)\h10([0-9]*)$/",$value,$matches)) {
                            // Matches x.xxx 10y format (no +|-)
                            $number=(float) $matches[1]."e".$matches[2];
                            $significand=$matches[1];
                            $exponent=$matches[2];
                            $e=$this->exponentialGen($matches[1]);
                            $error=$e['error']."e".$matches[2];
                            $accuracy=strlen($matches[1])-1;
                            $comments=$value;$value=null;
                        }
                        $dataArray=['Data'=>['datapoint_id'=>$datum['datapoint_id'],'property_id'=>$d['property'],'unit_id'=>$d['unit'],
                            'number'=>$number,'significand'=>$significand,'exponent'=>$exponent,'error'=>$error,'accuracy'=>$accuracy,
                            'exact'=>$exact,'metadata_id'=>$d['metadata'],'datatype'=>$dtype,'text'=>$value,'comments'=>$comments]];
                    }
                    $Data->create();
                    $Data->save($dataArray);
                    $data['data'][$didx]=$Data->id;
                    $Data->clear();
                    // Add reference to data in data_systems table
                    $DataSys->create();
                    $DataSys->save(['DataSystem'=>['dataset_id'=>$data['dataset_id'],'data_id'=>$data['data'][$didx],'system_id'=>$sysid]]);
                    $DataSys->clear();
                }

                // Supplemental Data
                if(isset($datum['suppdata'])&&!empty($datum['suppdata'])) {
                    foreach($datum['suppdata'] as $sidx=>$s) {
                        $value=$s['value'];
                        if(is_array($s['value'])) {
                            $stype="array";
                        } else {
                            $stype="datum";
                        }
                        if(is_numeric($value)) {
                            if(is_array($s['value'])) {
                                $e=$this->exponentialGen($value[0]);
                            } else {
                                $e=$this->exponentialGen($value);
                            }
                            ($s['datatype']=="integer") ? $exact=1 : $exact=0;
                            if(isset($datum['errors'][$eindex])&&$datum['errors'][$eindex]['label']==$s['label']) {
                                $error=$datum['errors'][$eindex]['value'];$eindex++;
                            } elseif(isset($datum['errors'][$eindex])&&$datum['errors'][$eindex]['label']!=$s['label']) {
                                $com='Error set ('.$datum['errors'][$eindex]['value'].') but labels dont match';
                                $error=$e['error'];
                            } else {
                                $error=$e['error'];
                            }
                            $suppArray=['SupplementalData'=>['datapoint_id'=>$datum['datapoint_id'],'property_id'=>$s['property'],'dataformat'=>$stype,
                                'number'=>$e['scinot'],'significand'=>$e['significand'],'exponent'=>$e['exponent'],'error'=>$error,
                                'error_type'=>'absolute','unit_id'=>$s['unit'],'accuracy'=>$e['dp'],'exact'=>$exact]];
                        } elseif($s['datatype']=="string"||is_string($value)) {
                            $suppArray=['SupplementalData'=>['datapoint_id'=>$datum['datapoint_id'],'property_id'=>$s['property'],'unit_id'=>$s['unit'],
                                'metadata_id'=>$s['metadata'], 'datatype'=>$s['datatype'],'dataformat'=>$stype,'text'=>$value]];
                        }
                        $SuppData->create();
                        $SuppData->save($suppArray);
                        $data['suppdata'][$sidx]=$SuppData->id;
                        $SuppData->clear();
                    }
                }

                // Annotations
                if(isset($datum['annotations'])&&!empty($datum['annotations'])) {
                    foreach ($datum['annotations'] as $aidx => $a) {
                        $value = $a['value'];
                        $label = $a['label'];
                        if (empty($value)) {
                            $value = '(empty)';
                        }
                        $annArray = ['Annotation' => ['datapoint_id' => $datum['datapoint_id'], 'text' => $value, 'type' => $label]];
                        $Annotation->create();
                        $Annotation->save($annArray);
                        $data['annotations'][$aidx] = $Annotation->id;
                        $Annotation->clear();
                    }
                }

            }

            // Add any dataseries conditions
            if(isset($series['seriesconds'])&&!empty($series['seriesconds'])) {
                foreach($series['seriesconds'] as $sidx=>$c) {
                    $value=$c['value'];
                    if(is_array($c['value'])) {
                        $e=$this->exponentialGen($value[0]);
                        $ctype="array";
                    } else {
                        $e=$this->exponentialGen($value);
                        $ctype="datum";
                    }
                    ($c['datatype']=="integer") ? $exact=1 : $exact=0;
                    (isset($c['error'])) ? $error=$c['error'] : $error=$e['error'];
                    $condArray=['Condition'=>['dataseries_id'=>$series['dataseries_id'],'property_id'=>$c['property'],'datatype'=>$ctype,
                        'number'=>$e['scinot'],'significand'=>$e['significand'],'exponent'=>$e['exponent'],'error'=>$error,
                        'error_type'=>'absolute','unit_id'=>$c['unit'],'accuracy'=>$e['dp'],'exact'=>$exact]];
                    $Condition->create();
                    $Condition->save($condArray);
                    $data['conditions'][$sidx]=$Condition->id;
                    $Condition->clear();
                }
            }

            // Add any dataseries annotations
            if(isset($series['seriesanns'])&&!empty($series['seriesanns'])) {
                foreach($series['seriesanns'] as $sidx=>$a) {
                    $annArray=['dataseries_id'=>$series['dataseries_id'],'type'=>$a['label'],'text'=>$a['value']];
                    $Annotation->create();
                    $Annotation->save($annArray);
                    $data['annotations'][$sidx]=$Annotation->id;
                    $Annotation->clear();
                }
            }

            // Add any dataseries settings
            if(isset($series['settings'])&&!empty($series['settings'])) {
                foreach($series['settings'] as $sidx=>$c) {
                    $value=$c['value'];
                    if(is_array($c['value'])) {
                        $e=$this->exponentialGen($value[0]);
                        $ctype="array";
                    } else {
                        $e=$this->exponentialGen($value);
                        $ctype="datum";
                    }
                    ($c['datatype']=="integer") ? $exact=1 : $exact=0;
                    (isset($c['error'])) ? $error=$c['error'] : $error=$e['error'];
                    $settArray=['Setting'=>['dataseries_id'=>$series['dataseries_id'],'property_id'=>$c['property'],'datatype'=>$ctype,
                        'number'=>$e['scinot'],'significand'=>$e['significand'],'exponent'=>$e['exponent'],'error'=>$error,
                        'error_type'=>'absolute','unit_id'=>$c['unit'],'accuracy'=>$e['dp'],'exact'=>$exact]];
                    //debug($settArray);exit;
                    $Setting->create();
                    $Setting->save($settArray);
                    $data['settings'][$sidx]=$Setting->id;
                    $Setting->clear();
                }
            }

        }

        //if(AuthComponent::user('type') == 'superadmin') { exit; }

        return true;
    }

    /**
     * Assemble datapoints from row format data (all data on one line)
     * @param $lines
     * @param $data
     * @param $serieslines
     * @return array
     */
    public function rpoints($lines,&$data,$serieslines) {

        //if(AuthComponent::user('type') == 'superadmin') { echo "LINES:";debug($lines);echo "SERIESLINES:";debug($serieslines); }

        // Assign a series # to each point so they can be separated later
        $series=null;
        // Aggregate points
        $points=[];
        foreach($lines as $line) {
            // Define the series
            foreach($serieslines as $serid=>$sline) {
                if($line>=$sline) {
                    $series=$serid;
                }
            }

            // Add the conditions
            $points[$line]['conditions'] = [];
            foreach($data['conditions'] as $c=>$cond) {
                if($cond['location']['line']==$line) {
                    unset($cond['location']); // Not needed any more...
                    $points[$line]['series']=$series;
                    $points[$line]['conditions'][]=$cond;
                }
            }
            // Add the data and the series
            $points[$line]['data'] = [];
            foreach($data['data'] as $d=>$datum) {
                if($datum['location']['line']==$line) {
                    unset($datum['location']); // Not needed any more...
                    $points[$line]['series']=$series;
                    $points[$line]['data'][]=$datum;
                }
            }

            // Add the errors
            $points[$line]['errors'] = [];
            foreach($data['errors'] as $e=>$err) {
                if($err['location']['line']==$line) {
                    unset($err['location']); // Not needed any more...
                    $points[$line]['series']=$series;
                    $points[$line]['errors'][]=$err;
                }
            }

            // Add the suppdata
            $points[$line]['suppdata'] = [];
            foreach($data['suppdata'] as $s=>$supp) {
                if($supp['location']['line']==$line) {
                    unset($supp['location']); // Not needed any more...
                    $points[$line]['series']=$series;
                    $points[$line]['suppdata'][]=$supp;
                }
            }

            // Add the annotations
            $points[$line]['annotations'] = [];
            foreach($data['annotations'] as $a=>$ann) {
                if($ann['location']['line']==$line) {
                    unset($ann['location']); // Not needed any more...
                    $points[$line]['series']=$series;
                    $points[$line]['annotations'][]=$ann;
                    unset($data['annotations'][$a]); // Remove so that its eay to identify series annotations
                }
            }
        }

        // Remove any points that are empty
        $lastpointline=0;
        foreach($points as $p=>$point) {
            if(empty($point['conditions'])&&empty($point['data'])&&empty($point['errors'])&&empty($point['suppdata'])&&empty($point['annotations'])) {
                unset($points[$p]);
            } elseif(!empty($point['annotations'])&&empty($point['data'])&&$lastpointline!=0) {
                $lastanns=$points[$lastpointline]['annotations'];
                foreach($point['annotations'] as $idx1=>$currann) {
                    // See if there is an annotation with the same label in the preceeding
                    foreach($lastanns as $idx2=>$lastann) {
                        if($lastann['label']==$currann['label']) {
                            $points[$lastpointline]['annotations'][$idx2]['value'].=$point['annotations'][$idx1]['value'];
                            unset($points[$p]['annotations'][$idx1]);
                        }
                    }
                }
                if(empty($points[$p]['conditions'])&&empty($points[$p]['data'])&&empty($points[$p]['errors'])&&empty($points[$p]['suppdata'])&&empty($points[$p]['annotations'])) {
                    unset($points[$p]);
                }
            } elseif(!empty($point['annotations'])&&!empty($point['data'])) {
                $lastpointline=$p;
            }
        }

        //if(AuthComponent::user('type') == 'superadmin') { echo "POINTS:";debug($points); }

        return $points;
    }

    /**
     * Assemble datapoints from column format data (data on multiple lines)
     * @param $lines
     * @param $data
     * @param $serieslines
     * @return array
     */
    public function cpoints($lines,$data,$serieslines)
    {
        $series=[];
        $serieslines[]=max($lines)+1;
        for($y=1;$y<count($serieslines);$y++) {
            for($x=0;$x<count($lines);$x++) {
                if($lines[$x]>$serieslines[$y]&&$lines[$x]<$serieslines[$y+1]) {
                    $series[$y][]=$lines[$x];
                }
            }
        }

        $smap=[];$format="";$repeats=0;
        $points=[];
        foreach ($series as $serid=>$slines) {
            $smapstr="";
            foreach($slines as $idx=>$sline) {
                foreach($data['conditions'] as $cond) {
                    if($cond['location']['line']==$sline) {
                        $smap[$sline]='c';
                        $smapstr.='c';
                        continue 2;
                    }
                }
                foreach($data['data'] as $exptdata) {
                    if($exptdata['location']['line']==$sline) {
                        $smap[$sline]='d';
                        $smapstr.='d';
                        continue 2;
                    }
                }
                foreach($data['suppdata'] as $supp) {
                    if($supp['location']['line']==$sline) {
                        $smap[$sline]='s';
                        $smapstr.='s';
                        continue 2;
                    }
                }
                foreach($data['annotations'] as $ann) {
                    if($ann['location']['line']==$sline) {
                        $smap[$sline]='a';
                        $smapstr.='a';
                        continue 2;
                    }
                }
            }
            if(strlen($smapstr)<count($slines)) {
                $lcount=strlen($smapstr);
            } else {
                $lcount=count($slines);
            }
            if(preg_match('/^(cd)+$/',$smapstr)) {
                $format='cd';$repeats=$lcount/2;
            } elseif(preg_match('/^(ccd)+$/',$smapstr)) {
                $format='ccd';$repeats=$lcount/3;
            } elseif(preg_match('/^(cdc)+$/',$smapstr)) {
                $format='cdc';$repeats=$lcount/3;
            }
            if(!is_integer($repeats)) {
                debug($smapstr);debug($repeats);debug($slines);
                echo "Tell Dr. Chalk to look at error code 'MC' to fix this bug!";exit;
            }
            $conds2=[];
            if($format=='ccd') {
                //debug($format);debug($repeats);debug($slines);debug($smap);
                // Split out second condition so the code below works
                // Move lines that are indexed at 1+(nx3) in $slines
                for($r=0;$r<$repeats;$r++) {
                    foreach($data['conditions'] as $idx=>$cond) {
                        if($cond['location']['line']==$slines[(1+($r*3))]) {
                            $conds2[]=$cond;
                            unset($data['conditions'][$idx]);
                        }
                    }
                }
                //debug($conds2);
            } elseif($format=='cdc') {
                debug($format);debug($repeats);debug($slines);debug($smap);
                // Split out second condition so the code below works
                // Move lines that are indexed at 2+(nx3) in $slines
                for($r=0;$r<$repeats;$r++) {
                    foreach($data['conditions'] as $idx=>$cond) {
                        if($cond['location']['line']==$slines[(2+($r*3))]) {
                            $conds2[]=$cond;
                            unset($data['conditions'][$idx]);
                        }
                    }
                }
                // debug($conds2);
            }


            $conds=$exptdata=$anns=$supps=[];
            foreach($data['conditions'] as $cond) {
                // Get all the conditions for this ref
                if(in_array($cond['location']['line'],$slines)) {
                    unset($cond['location']);
                    $conds[]=$cond;
                }
            }
            foreach($data['data'] as $datum) {
                // Get all the conditions for this ref
                if(in_array($datum['location']['line'],$slines)) {
                    unset($datum['location']);
                    $exptdata[]=$datum;
                }
            }
            foreach($data['suppdata'] as $supp) {
                // Get all the conditions for this ref
                if (in_array($supp['location']['line'],$slines)) {
                    unset($supp['location']);
                    $supps[] = $supp;
                }
            }
            foreach($data['annotations'] as $ann) {
                // Get all the conditions for this ref
                if(in_array($ann['location']['line'],$slines)) {
                    unset($ann['location']);
                    $anns[]=$ann;
                }
            }
            //debug($slines);debug($conds);
            // Aggregate points (could use conds or exptdata in foreach)
            foreach($conds as $idx=>$cond) {
                $point=['conditions'=>[],'data'=>[],'suppdata'=>[],'annotations'=>[]];
                if($cond['value']!=""&&isset($exptdata[$idx])) {
                    $point['conditions'][]=$cond;
                    if(!empty($conds2)) {
                        $point['conditions'][]=$conds2[$idx];
                    }
                    $point['data'][]=$exptdata[$idx];
                    $point['series']=$serid;
                    if(isset($supps[$idx])) {
                        $point['suppdata'][]=$supps[$idx];
                    }
                    if(isset($anns[$idx])) {
                        $point['annotations'][]=$anns[$idx];
                    }
                    $points[]=$point;
                } elseif($cond['value']!=""&&!isset($exptdata[$idx])) {
                    debug($conds);debug($exptdata);
                    die('{"error":"Condition but no data!"}');
                } elseif($cond['value']==""&&isset($exptdata[$idx])) {
                    debug($conds);debug($exptdata);
                    die('{"error":"Data but no condition!","}');
                }
            }
        }
        return $points;
    }

    /**
     * Generates a exponential number removing any zeros at the end not needed
     * @param $string
     * @return string
     */
    private function exponentialGen($string)
    {
        $return=[];
        if($string==0) {
            $return=['dp'=>0,'scinot'=>'0e+0','exponent'=>0,'significand'=>0,'error'=>null];
        } else {
            $string=str_replace(",","",$string);
            $num=explode(".",$string);
            // If there is something after the decimal
            if(isset($num[1])){
                if($num[0]!=""&&$num[0]!=0) {
                    // All digits count (-1 for period)
                    if($string<0||stristr($string,"-")) {
                        // ... add -1 for the minus sign
                        $return['dp']=strlen($string)-2;
                    } else {
                        $return['dp']=strlen($string)-1;
                    }
                    // Exponent is based on digital before the decimal -1
                    $return['exponent']=strlen($num[0])-1;
                } else {
                    // Remove any leading zeroes and count string length
                    $t=ltrim($num[1],'0');
                    if($t<0||stristr($t,"-")) {
                        $return['dp']=strlen($t)-1;
                    } else {
                        $return['dp']=strlen($t);
                    }
                    $return['exponent']=strlen($t)-strlen($num[1])-1;
                }
                $return['scinot']=sprintf("%." .($return['dp']-1). "e", $string);
                $s=explode("e",$return['scinot']);
                $return['significand']=$s[0];
                $return['error']=pow(10,$return['exponent']-$return['dp']+1);
            } else {
                $return['dp']=0;
                $return['scinot']=sprintf("%." .(strlen($string)-1). "e", $string);
                $return['exponent']=strlen($string)-1;
                $s=explode("e",$return['scinot']);
                $return['significand']=$s[0];
                $z=explode(".",$return['significand']);
                if(isset($z[1])) {
                    $return['error']=pow(10,strlen($z[1])-$s[1]); // # SF after decimal - exponent
                } else {
                    $return['error']=pow(10,0-$s[1]); // # SF after decimal - exponent
                }
            }
        }

        return $return;
    }

    /**
     * Calculate accuracy
     * @param $float
     * @param int $error
     * @return int
     */
    private function calculateAccuracy($float,$error=0)
    {
        $accuracy=0;
        $errorAcc=0;

        $float=str_replace(",","",$float);
        $errorArr = explode(".", (float)$error);
        $decimals = 0;
        if(isset($errorArr[1])) {
            $errorArr[1] = str_split($errorArr[1]);
            $decimals = 0;
            foreach ($errorArr[1] as $p => $char) { //calculate the correct number of decimal places
                if ($char != "0") {
                    $decimals = $p + 1;
                    break;
                }
            }
        }
        if(is_string($float)){
            $float = (double)$float;
        }
        if($error) {
            $num = explode(".", number_format($float, $decimals));
        }else{
            $num = explode(".", number_format($float,10));
        }
        if($num[0]!=0){
            $num[0]=str_split($num[0]);
            foreach($num[0] as $char){
                if(is_numeric($char)){
                    $accuracy++;
                }
            }
        }
        if(isset($num[1])){
            $num[1]=str_split($num[1]);
            if($error==0){
                $num[1]=array_reverse($num[1]);
                $count=false;
                foreach($num[1] as $char) {
                    if(is_numeric($char)&&($char!=0||$count)){
                        $accuracy++;
                        $count=true;
                    }
                }

            }else {
                foreach ($num[1] as $char) {
                    if (is_numeric($char)) {
                        $accuracy++;
                    }
                }
            }
        }
        if($decimals==0&&$error!=0) {
            $errorAcc = $this->calculateAccuracy($error);
        }
        $accuracy-=$errorAcc;
        if($accuracy<1)
            $accuracy=1;
        return $accuracy;
    }

    /**
     * Get unit and property
     * @param $key
     * @param $value
     * @param $properties
     * @return array
     */
    private function getUnitAndProperty($key,$value,$properties)
    {
        $return = [];
        if(isset($properties['Parameter'])) {
            foreach ($properties['Parameter'] as $prop) {
                if ($key == $prop['parameter_num']) {
                    $return['type'] = $prop['identifier'];
                    if (count($prop['Unit']) === 1) {
                        $return['unit'] = $prop['Unit'][0]['id'];
                        $return['property'] = $prop['property_id'];
                        return $return;
                    } elseif (count($prop['Unit']) > 1) {
                        $found = false;
                        foreach ($prop['Unit'] as $unit) {
                            if ($unit['ParametersUnit']['header'] == $value) {
                                $return['unit'] = $unit['id'];
                                $return['property'] = $prop['property_id'];
                                return $return;
                            }
                        }
                    }
                }
            }
        }
        foreach ($properties['Variable'] as $prop) {
            if($key == $prop['column #']) {
                $return['type'] = $prop['identifier'];
                if (count($prop['Unit']) === 1) {
                    $return['unit'] = $prop['Unit'][0]['id'];
                    $return['property'] = $prop['property_id'];
                    return $return;
                } elseif (count($prop['Unit']) > 1) {
                    $found = false;
                    foreach ($prop['Unit'] as $unit) {
                        if ($unit['UnitsVariable']['header'] == $value) {
                            $return['unit'] = $unit['id'];
                            $return['property'] = $prop['property_id'];
                            return $return;
                        }
                    }
                }
            }
        }
        if(isset($properties['SuppParameter'])) {
            foreach ($properties['SuppParameter'] as $prop) {
                if ($key == $prop['parameter_num']) {
                    $return['type'] = $prop['identifier'];
                    if (count($prop['Unit']) === 1) {
                        $return['unit'] = $prop['Unit'][0]['id'];
                        $return['property'] = $prop['property_id'];
                        return $return;
                    } elseif (count($prop['Unit']) > 1) {
                        $found = false;
                        foreach ($prop['Unit'] as $unit) {
                            if ($unit['ParametersUnit']['header'] == $value) {
                                $return['unit'] = $unit['id'];
                                $return['property'] = $prop['property_id'];
                                return $return;
                            }
                        }
                    }
                }
            }
        }
    }

}