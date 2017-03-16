<?php

/**
 * Class AdminController
 */
class AdminController extends AppController
{
    public $uses = ['Annotation','Condition','Dataset','Dataseries','Datapoint','Data','Equation','Eqnvar','Eqnterm',
        'File','Parameter','Publication','Property','Propertytype','Quantity','Reference','Report','Ruleset','Setting',
        'SupplementalData','Substance','System','TextFile','QuantitiesUnit','Variable','Phase1SupplementalData',
        'Migration','Phase1Ref','Phase1Dataset','Phase1Condition','Phase1Datapoint','Phase1Equation','Phase1Eqnterm',
        'Phase1Eqnvar','Phase1Dataseries','Phase1Ruleset','Phase1Report','Phase1File','Phase1TextFile','Phase1Data',
        'Phase1Setting','Phase1Annotation','Phase1DataSystem','DataSystem','MigrationsDeleted'];

    /**
     * Update entries in the data_systems table
     * @param int $s
     */
    public function datasys($s=1)
    {
        // Update the data_systems join table
        $this->Data->joinsys($s);
    }

    /**
     * Update entries in the data_systems table
     * @param int $s
     */
    public function condsys($s=1)
    {
        // Update the data_systems join table
        $this->Condition->joinsys($s);
    }

    /**
     * Administrator Dashboard
     */
    public function dashboard()
    {
        // Get dataseries with deleted datasets
        $c=['Dataset'=>['fields'=>['id']]];
        $noset=$this->Dataseries->find('all',['conditions'=>['Dataset.id'=>null],'contain'=>$c,'recursive'=>-1]);
        // Get datapoints with deleted dataseries
        $c=['Dataseries'=>['fields'=>['id']]];
        $noser=$this->Datapoint->find('all',['conditions'=>['Dataseries.id'=>null],'contain'=>$c,'recursive'=>-1]);
        // Get data with deleted datapoint
        $c=['Datapoint'=>['fields'=>['id']]];
        $dnopnt=$this->Data->find('all',['conditions'=>['Datapoint.id'=>null],'contain'=>$c,'recursive'=>-1]);
        $cnopnt=$this->Condition->find('all',['conditions'=>['Datapoint.id'=>null],'contain'=>$c,'recursive'=>-1]);
        $snopnt=$this->Setting->find('all',['conditions'=>['Datapoint.id'=>null],'contain'=>$c,'recursive'=>-1]);
        $unopnt=$this->SupplementalData->find('all',['conditions'=>['Datapoint.id'=>null],'contain'=>$c,'recursive'=>-1]);

        debug($noset);debug($noser);debug($dnopnt);debug($cnopnt);debug($snopnt);debug($unopnt);exit;
    }

    /**
     * Check a volume for the integrity of its data
     * @param null $id
     */
    public function checkvol($id=null) {
        if(!is_null($id)) {
            // Perform a series of tests to verify the integrity of the data in a publication

            // PUBLICATION
            $results=[];
            $pub=$this->Publication->find('first',['conditions'=>['Publication.id'=>$id],'recursive'=>-1]);
            $results['stats']=[];
            $p=$pub['Publication'];
            $results['Publication']['info']=['id'=>$p['id'],'ruleset_id'=>$p['ruleset_id'],'abbrev'=>$p['abbrev']];
            // Check if abbreviation is set
            if(is_null($p['abbrev'])) {
                $results['Publication']['has_abbrev']=false;
            } else {
                $results['Publication']['has_abbrev']=true;
            }
            // Check if ruleset is set
            if(is_null($p['ruleset_id'])) {
                $results['Publication']['has_ruleset']=false;
            } else {
                $results['Publication']['has_ruleset']=true;
                $rset=$this->Ruleset->find('first',['conditions'=>['Ruleset.id'=>$p['ruleset_id']]]);
                if(empty($rset)) {
                    $results['Publication']['ruleset_exists']=false;
                } else {
                    $results['Publication']['ruleset_exists']=true;
                }
            }

            debug($results['Publication']);

            // FILES
            $files=$this->File->find('all',['conditions'=>['File.publication_id'=>$id],'recursive'=>-1]);
            // Add file count
            $results['stats']['files']=count($files);
            // Check the files
            $fids=[];
            foreach($files as $fidx=>$file) {
                $f=$file['File'];$fids[$fidx]=$f['id'];$results['File'][$fidx]['stats']=[];$results['File'][$fidx]['info']=[];
                $results['File'][$fidx]=['id'=>$f['id'],'num_systems'=>$f['num_systems'],'num_subs'=>$f['numsubs']];
                // Check if propertytype is set
                if(is_null($f['propertytype_id'])) {
                    $results['File'][$fidx]['has_proptype']=false;
                } else {
                    $results['File'][$fidx]['has_proptype']=true;
                    $proptype=$this->Propertytype->find('first',['conditions'=>['Propertytype.id'=>$f['propertytype_id']]]);
                    if(empty($proptype)) {
                        $results['File'][$fidx]['proptype_exists']=false;
                    } else {
                        $results['File'][$fidx]['proptype_exists']=true;
                    }
                }
                // Check if property is set
                if(is_null($file['File']['property_id'])) {
                    $results['File'][$fidx]['has_prop']=false;
                } else {
                    $results['File'][$fidx]['has_prop']=true;
                    $prop=$this->Property->find('first',['conditions'=>['Property.id'=>$f['property_id']]]);
                    if(empty($prop)) {
                        $results['File'][$fidx]['prop_exists']=false;
                    } else {
                        $results['File'][$fidx]['prop_exists']=true;
                    }
                }
                // Check if ruleset is set
                if(is_null($file['File']['ruleset_id'])) {
                    $results['File'][$fidx]['has_ruleset']=false;
                } else {
                    $results['File'][$fidx]['has_ruleset']=true;
                    $rset=$this->Ruleset->find('first',['conditions'=>['Ruleset.id'=>$f['ruleset_id']]]);
                    if(empty($rset)) {
                        $results['File'][$fidx]['ruleset_exists']=false;
                    } else {
                        $results['File'][$fidx]['ruleset_exists']=true;
                        if(isset($results['Publication']['ruleset_exists'])) {
                            if($results['Publication']['ruleset_exists']==true&&$results['File'][$fidx]['ruleset_exists']==true) {
                                if($results['Publication']['info']['ruleset_id']==$f['ruleset_id']) {
                                    $results['File'][$fidx]['ruleset_match']=true;
                                } else {
                                    $results['File'][$fidx]['ruleset_match']=false;
                                }
                            }
                        }
                    }
                }
                $counts=$this->TextFile->find('list',['fields'=>['id','file_id','status'],'conditions'=>['TextFile.file_id'=>$f['id']]]);
                (isset($counts['added'])) ? $acount=count($counts['added']) : $acount=0;
                (isset($counts['retired'])) ? $rcount=count($counts['retired']) : $rcount=0;
                (isset($counts['ingested'])) ? $icount=count($counts['ingested']) : $icount=0;
                $results['File'][$fidx]['stats']['textfiles']['status']['added']=$acount;
                $results['File'][$fidx]['stats']['textfiles']['status']['retired']=$rcount;
                $results['File'][$fidx]['stats']['textfiles']['status']['ingested']=$icount;
                $tfcount=$results['File'][$fidx]['stats']['textfiles']['status']['retired']+$results['File'][$fidx]['stats']['textfiles']['status']['ingested'];
                if($tfcount==$results['File'][$fidx]['num_systems']) {
                    $results['File'][$fidx]['numsys_match']=true;
                } else {
                    $results['File'][$fidx]['numsys_match']=false;
                }
                echo "File ".$fidx." done!";
                debug($results['File'][$fidx]);
            }
            exit;

            // TEXTFILES
            $tfiles=$this->TextFile->find('all',['fields'=>['id','file_id','errors','sysnum','version','status'],'conditions'=>['file_id'=>$fids],'order'=>['file_id','sysnum'],'recursive'=>-1]);
            $terrs=$this->TextFile->find('list',['fields'=>['id','errors','status'],'conditions'=>['file_id'=>$fids],'order'=>['file_id','id']]);
            $tsyss=$this->TextFile->find('list',['fields'=>['version','status','sysnum'],'conditions'=>['file_id'=>$fids],'order'=>['sysnum','version']]);
            // Add file count
            $results['stats']['textfiles']=count($tfiles);
            // Check ingested for errors
            if(count(array_count_values($terrs['ingested']))>1) {
                $results['TextFile']['info']['has_ingested_errors']=true;
            } else {
                $results['TextFile']['info']['has_ingested_errors']=false;
            }
            // Check the files
            $tfids=[];
            foreach($tfiles as $tidx=>$tfile) {
                $t=$tfile['TextFile'];
                // Check for retired status
                if($t['status']=="retired") {
                    $results['TextFile']['info']['retired'][$t['id']]=$t['sysnum'];
                } else {
                    // Add the id array for searching in datasets (indexed by sysnum)
                    $tfids[$t['sysnum']]=$t['id'];
                    // Get the number of datasets for this texfile
                    $results['TextFile'][$t['sysnum']]['datasets']=$this->Dataset->find('count',['conditions'=>['Dataset.text_file_id'=>$t['id']]]);
                    if($results['TextFile'][$t['sysnum']]['datasets']==0) {
                        $results['TextFile']['info']['no_dataset'][$t['id']]=$t['sysnum'];
                    }
                    // Check version
                    $results['TextFile'][$t['sysnum']]['has_version']=!is_null($t['version']);
                    // Check for ingested errors
                    if($results['TextFile']['info']['has_ingested_errors']&&$t['errors']=="ingested") {
                        if($t['errors']!="[]") {
                            $results['TextFile'][$tidx]['has_ingest_error']=true;
                        } else {
                            $results['TextFile'][$tidx]['has_ingest_error']=false;
                        }
                    }
                    // Check for version consistency
                    if(count($tsyss[$t['sysnum']])>1&$t['status']!="retired") {
                        $scount=array_count_values($tsyss[$t['sysnum']]);
                        if($scount['retired']==(count($tsyss[$t['sysnum']])-1)) {
                            $results['TextFile'][$t['sysnum']]['has_status_errors']=false;
                        } else {
                            $results['TextFile'][$t['sysnum']]['has_status_errors']=true;
                        }
                    } else {
                        $results['TextFile'][$t['sysnum']]['has_status_errors']=false;
                    }
                    // Add file_id (for consistency check in dataset code below)
                    $results['TextFile'][$t['sysnum']]['file_id']=$t['file_id'];
                }
            }

            // DATASETS
            $sets=$this->Dataset->find('all',['conditions'=>['text_file_id'=>$tfids],'recursive'=>-1]);
            // Add dataset count
            $results['stats']['datasets']=count($sets);
            // Check the datasets
            $dsids=[];$sysids=[];$results['Dataset']['info']=[];
            foreach($sets as $sidx=>$set) {
                $s=$set['Dataset'];
                // Add the id array for searching in dataseries
                $dsids[$sidx]=$s['id'];
                // Get the number of dataseries for this dataset
                $results['Dataset'][$sidx]['dataseries']=$this->Dataseries->find('count',['conditions'=>['Dataseries.dataset_id'=>$s['id']]]);
                if($results['Dataset'][$sidx]['dataseries']==0) {
                    $results['Dataset']['info']['no_dataseries'][$s['id']]=$s['title'];
                }
                // Check there is a file and that the file_id matches whats in textfile table
                $sysnum=array_search($s['text_file_id'],$tfids);
                if($results['TextFile'][$sysnum]['file_id']==$s['file_id']) {
                    $results['Dataset'][$sidx]['fileid_match'] = true;
                } else {
                    $results['Dataset'][$sidx]['fileid_match'] = false;
                    $results['Dataset']['info']['fileid_mismatch'][$s['id']]=$s['title'];
                }
                // Check report_id
                $results['Dataset'][$sidx]['has_report']=!is_null($s['report_id']);
                if(!is_null($s['report_id'])) {
                    $resp=$this->Report->find('first',['conditions'=>['id'=>$s['report_id']],'recursive'=>-1]);
                    $results['Dataset'][$sidx]['valid_report']=!empty($resp);
                    if(empty($resp)) {
                        $results['Dataset']['info']['no_report'][$s['id']]=$s['title'];
                    }
                } else {
                    $results['Dataset']['info']['no_report'][$s['id']]=$s['title'];
                }
                // Check system_id
                $results['Dataset'][$sidx]['has_system']=!is_null($s['system_id']);
                if(!is_null($s['system_id'])) {
                    $resp=$this->System->find('first',['conditions'=>['id'=>$s['system_id']],'recursive'=>-1]);
                    $results['Dataset'][$sidx]['valid_system']=!empty($resp);
                    if(empty($resp)) {
                        $results['Dataset']['info']['no_system'][$s['id']]=$s['title'];
                    } else {
                        $sysids[$s['file_id']][]=$s['system_id'];
                    }
                } else {
                    $results['Dataset']['info']['no_system'][$s['id']]=$s['title'];
                }
                // Check reference_id
                $results['Dataset'][$sidx]['has_reference']=!is_null($s['reference_id']);
                if(!is_null($s['reference_id'])) {
                    $resp=$this->Reference->find('first',['conditions'=>['id'=>$s['reference_id']],'recursive'=>-1]);
                    $results['Dataset'][$sidx]['valid_reference']=!empty($resp);
                    if(empty($resp)) {
                        $results['Dataset']['info']['no_reference'][$s['id']]=$s['title'];
                    }
                } else {
                    $results['Dataset']['info']['no_reference'][$s['id']]=$s['title'];
                }
                // Check propertytype_id
                $results['Dataset'][$sidx]['has_proptype']=!is_null($s['propertytype_id']);
                if(!is_null($s['propertytype_id'])) {
                    $resp=$this->Propertytype->find('first',['conditions'=>['id'=>$s['propertytype_id']],'recursive'=>-1]);
                    $results['Dataset'][$sidx]['valid_proptype']=!empty($resp);
                    if(empty($resp)) {
                        $results['Dataset']['info']['no_proptype'][$s['id']]=$s['title'];
                    }
                } else {
                    $results['Dataset']['info']['no_proptype'][$s['id']]=$s['title'];
                }
            }

            // SYSTEMS
            foreach($sysids as $fileid=>$fsyss) {
                $syss=$this->System->find('all',['conditions'=>['System.id'=>$fsyss],'contain'=>['Substance'],'recursive'=>-1]);
                // Add system count
                $results['stats']['systems'][$fileid]=count($syss);
                // Check the systems (organized by fileid)
                $results['System'][$fileid]['info']=[];$numsubs=0;
                // Get the number of components for this file
                foreach($results['File'] as $file) {
                    if($file['id']==$fileid) {
                        $numsubs=$file['num_subs'];
                        break;
                    }
                }
                // Check the systems of this file
                foreach($syss as $sidx=>$system) {
                    $sys=$system['System'];$subs=$system['Substance'];
                    if($sys['composition']=='pure compound'&&count($subs)==1) {
                        $results['System'][$fileid][$sidx]['system_selfconsistent']=true;
                    } elseif($sys['composition']=='binary mixture'&&count($subs)==2) {
                        $results['System'][$fileid][$sidx]['system_selfconsistent']=true;
                    } else {
                        $results['System'][$fileid][$sidx]['system_selfconsistent']=false;
                        $results['System'][$fileid]['inconsistent_systems'][$sys['id']]=$sys['name'];
                    }
                    if(count($subs)==$numsubs) {
                        $results['System'][$fileid][$sidx]['subs_file_consistent']=true;
                    } else {
                        $results['System'][$fileid][$sidx]['subs_file_consistent']=false;
                        $results['System'][$fileid]['inconsistent_subfile'][$sys['id']]=$sys['name'];
                    }
                }

            }

            // DATASERIES
            $sers=$this->Dataseries->find('all',['conditions'=>['dataset_id'=>$dsids],'recursive'=>-1]);
            // Add dataset count
            $results['stats']['dataseries']=count($sers);
            // Check the dataseries
            $serids=[];$results['Dataseries']['info']=['pnt_count'=>0,'eqn_count'=>0];
            foreach($sers as $sidx=>$ser) {
                $s = $ser['Dataseries'];
                // Add the id array for searching in dataseries
                $serids[$sidx] = $s['id'];
                // Get the number of datapoints and equations for this dataseries
                $results['Dataseries'][$sidx]['datapoints']=$this->Datapoint->find('count',['conditions'=>['Datapoint.dataseries_id'=>$s['id']]]);
                $results['Dataseries'][$sidx]['equations']=$this->Equation->find('count',['conditions'=>['Equation.dataseries_id'=>$s['id']]]);
                if($results['Dataseries'][$sidx]['datapoints']==0&&$results['Dataseries'][$sidx]['equations']==0) {
                    $results['Dataseries']['info']['no_pntsoreqns'][$s['id']]=$s['title'];
                }
                $results['Dataseries']['info']['pnt_count']+=$results['Dataseries'][$sidx]['datapoints'];
                $results['Dataseries']['info']['eqn_count']+=$results['Dataseries'][$sidx]['equations'];
            }
            $results['stats']['datapoints']=$results['Dataseries']['info']['pnt_count'];
            $results['stats']['equations']=$results['Dataseries']['info']['eqn_count'];

            // EQUATIONS
            if($results['Dataseries']['info']['eqn_count']>0) {
                foreach($results['Dataseries'] as $sidx=>$ser) {
                    if($sidx=='info') { continue; }
                    $eqns=$this->Equation->find('all',['conditions'=>['dataseries_id'=>$serids[$sidx]],'contain'=>['Eqntype','Eqnterm','Eqnvar'],'recursive'=>-1]);
                    foreach($eqns as $eidx=>$eqn) {
                        $type=$eqn['Eqntype'];$terms=$eqn['Eqnterm'];$vars=$eqn['Eqnvar'];$results['Equations']['info']=[];
                        // Check terms
                        if($type['numterms']==count($terms)) {
                            $results['Equations'][$sidx][$eidx]['all_terms']=true;
                        } else {
                            $results['Equations'][$sidx][$eidx]['all_terms']=false;
                            $results['Equations']['info']['wrong_num_terms'][$eidx]=$eqn['Equation']['id'];
                        }
                        // Check vars
                        if($type['numvars']==count($vars)) {
                            $results['Equations'][$sidx][$eidx]['all_vars']=true;
                        } else {
                            $results['Equations'][$sidx][$eidx]['all_vars']=false;
                            $results['Equations']['info']['wrong_num_vars'][$eidx]=$eqn['Equation']['id'];
                        }
                        // Check var property and unit
                        if($type['varprop_id']==$vars[0]['property_id']) {
                            $results['Equations'][$sidx][$eidx]['correct_var_prop']=true;
                        } else {
                            $results['Equations'][$sidx][$eidx]['correct_var_prop']=false;
                            $results['Equations']['info']['wrong_var_prop'][$eidx]=$eqn['Equation']['id'];
                        }
                        if($type['varunit_id']==$vars[0]['unit_id']) {
                            $results['Equations'][$sidx][$eidx]['correct_var_unit']=true;
                        } else {
                            $results['Equations'][$sidx][$eidx]['correct_var_unit']=false;
                            $results['Equations']['info']['wrong_var_unit'][$eidx]=$eqn['Equation']['id'];
                        }
                    }
                }
            }

            // DATAPOINTS
            if($results['Dataseries']['info']['pnt_count']>0) {
                foreach($results['Dataseries'] as $sidx=>$ser) {
                    if ($sidx == 'info') { continue; }
                    $pnts = $this->Datapoint->find('all', ['conditions' => ['dataseries_id' => $serids[$sidx]], 'contain' => ['Condition', 'Data', 'SupplementalData','Annotation'], 'recursive' => -1]);
                    debug($pnts);exit;
                }
            }


            //debug($tfids);debug($sets);
            debug($results);exit;
        }
    }

    public function checkunits()
    {
        // Get Units
        $units=$this->QuantitiesUnit->find('list',['fields'=>['id','unit_id','quantity_id'],'order'=>'quantity_id']);
        // Check Conditions
        $conds=$this->Condition->find('all',['fields'=>['id','property_id','unit_id'],'contain'=>['Property'=>['fields'=>['id','name','quantity_id']]],'recursive'=>-1]);
        echo "<h2>Conditions</h2>";
        foreach($conds as $cond) {
            $c=$cond['Condition'];$p=$cond['Property'];
            if(!in_array($c['unit_id'],$units[$p['quantity_id']])) {
                echo 'Condition: ID='.$c['id'].' has inconsistent unit ('.$c['unit_id'].') for property '.$p['name']."<br />";
            }
        }
        // Check Data
        $data=$this->Data->find('all',['fields'=>['id','property_id','unit_id'],'contain'=>['Property'=>['fields'=>['id','name','quantity_id']]],'recursive'=>-1]);
        echo "<h2>Data</h2>";
        foreach($data as $datum) {
            $c=$datum['Data'];$p=$datum['Property'];
            if(!in_array($c['unit_id'],$units[$p['quantity_id']])) {
                echo 'Data: ID='.$c['id'].' has inconsistent unit ('.$c['unit_id'].') for property '.$p['name']."<br />";
            }
        }
        // Check Supplemental Data
        $sdata=$this->SupplementalData->find('all',['fields'=>['id','property_id','unit_id'],'contain'=>['Property'=>['fields'=>['id','name','quantity_id']]],'recursive'=>-1]);
        echo "<h2>Supplemental Data</h2>";
        foreach($sdata as $datum) {
            $c=$datum['SupplementalData'];$p=$datum['Property'];
            if(!in_array($c['unit_id'],$units[$p['quantity_id']])) {
                echo 'Supplemental Data: ID='.$c['id'].' has inconsistent unit ('.$c['unit_id'].') for property '.$p['name']."<br />";
            }
        }
        exit;
    }

    public function migrate($id=null,$phase=1)
    {
        // Migrate a volume of data to Phase1 DB
        if(is_null($id)) {
            echo "Needs a volume ID!";exit;
        }

        // References
        $refs=$this->Reference->find('all',['conditions'=>['count >'=>0],'order'=>'id','recursive'=>-1]);
        // Check to see of the # of references matches the # already migrated
        $refsdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'references']]);
        if(count($refsdone)!=count($refs)) {
            echo "References completed: ".count($refsdone)."<br />";
            // Remove completed datapoints from the $pnts array
            if(!empty($refsdone)) {
                foreach($refs as $idx=>$ref) {
                    if(in_array($ref['Reference']['id'],$refsdone)) { unset($refs[$idx]); }
                }
            }
            foreach($refs as $ref) {
                $r=$ref['Reference'];
                $oldid=$r['id'];unset($r['id']); // So a new ID is assigned...
                // Add to Phase1
                if(stristr($r['url'],'http://dx.doi.org/')) {
                    $r['doi']=str_replace("http://dx.doi.org/","",$r['url']);
                }
                //debug($r);
                $this->Phase1Ref->create();
                $this->Phase1Ref->save(['Phase1Ref'=>$r]);
                $newid=$this->Phase1Ref->id;
                //debug($newid);
                // Add to Migration table
                $m=['Migration'=>['from_table'=>'references','from_id'=>$oldid,'to_table'=>'references','to_id'=>str_pad($newid,6,'0',STR_PAD_LEFT),'publication_id'=>null]];
                $this->Migration->create();
                $this->Migration->save($m);
                //debug($m);
                echo "Reference ".$oldid." saved<br />";
            }
        } else {
            echo "References already migrated<br />";
        }

        // Rulesets
        $sets=$this->Ruleset->find('all',['conditions'=>['id >'=>1],'fields'=>['id','name','version','comment'],'order'=>'id','recursive'=>-1]);
        // Check to see of the # of rulesets matches the # already migrated
        $setsdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'rulesets']]);
        if(count($setsdone)!=count($sets)) {
            echo "Rulesets completed: ".count($setsdone)."<br />";
            // Remove completed datapoints from the $pnts array
            if(!empty($setsdone)) {
                foreach($sets as $idx=>$set) {
                    if(in_array($set['Ruleset']['id'],$setsdone)) { unset($sets[$idx]); }
                }
            }
            foreach($sets as $set) {
                $s=$set['Ruleset'];
                $oldid=$s['id'];unset($s['id']); // So a new ID is assigned...
                // Add to Phase1
                $this->Phase1Ruleset->create();
                $this->Phase1Ruleset->save(['Phase1Ruleset'=>$s]);
                $newid=$this->Phase1Ruleset->id;
                // Add to Migration table
                $m=['Migration'=>['from_table'=>'rulesets','from_id'=>$oldid,'to_table'=>'rulesets','to_id'=>str_pad($newid,5,'0',STR_PAD_LEFT),'publication_id'=>null]];
                $this->Migration->create();
                $this->Migration->save($m);
                echo "Ruleset ".$oldid." saved<br />";
            }
        } else {
            echo "Rulesets already migrated<br />";
        }

        // Reports
        $rpts=$this->Report->find('all',['order'=>'id','recursive'=>-1]);
        // Check to see of the # of reports matches the # already migrated
        $rptsdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'reports']]);
        if(count($rptsdone)!=count($rpts)) {
            echo "Reports completed: ".count($rptsdone)."<br />";
            // Remove completed datapoints from the $pnts array
            if(!empty($rptsdone)) {
                foreach($rpts as $idx=>$rpt) {
                    if(in_array($rpt['Report']['id'],$rptsdone)) { unset($rpts[$idx]); }
                }
            }
            echo "Reports remaining: ".count($rpts)."<br />";
            foreach($rpts as $rpt) {
                $r=$rpt['Report'];
                $oldid=$r['id'];unset($r['id']); // So a new ID is assigned...
                // Add to Phase1
                $this->Phase1Report->create();
                $this->Phase1Report->save(['Phase1Report'=>$r]);
                $newid=$this->Phase1Report->id;
                // Add to Migration table
                $m=['Migration'=>['from_table'=>'reports','from_id'=>$oldid,'to_table'=>'reports','to_id'=>str_pad($newid,6,'0',STR_PAD_LEFT),'publication_id'=>null]];
                $this->Migration->create();
                $this->Migration->save($m);
                echo "Report ".$oldid." saved<br />";
            }
        } else {
            echo "Reports already migrated<br />";
        }
        // Check for reports that no longer exist...
        $rptsfrom=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'reports'],'order'=>'id','recursive'=>-1]);
        $rptsto=$this->Migration->find('list',['fields'=>['id','to_id'],'conditions'=>['from_table'=>'reports'],'order'=>'id','recursive'=>-1]);
        $current=$this->Report->find('list',['fields'=>['id'],'conditions'=>['id'=>$rptsfrom],'order'=>'id','recursive'=>-1]);
        // Filter out the reports still current
        foreach($current as $cid) {
            $key=array_search($cid,$rptsfrom);
            unset($rptsfrom[$key]);
        }
        // Remove the reports that have been deleted
        foreach($rptsfrom as $migid=>$oldid) {
            // Get the 'newid' from the migration table
            $newid=$rptsto[$migid];
            // Deleted the deleted & migrated report
            $this->Phase1Report->delete($newid);
            // Move migration to deleted table
            $mig=$this->Migration->find('first',['conditions'=>['id'=>$migid],'recursive'=>-1]);
            $this->MigrationsDeleted->save(['MigrationsDeleted'=>$mig['Migration']]);
            // Remove the entry in the migrations table as well
            $this->Migration->delete($migid);
            echo "Removed report:  from_id: ".$oldid.", migration_id: ".$migid.", to_id:".$newid."<br />";
        }

        // For specific volume...

        // Files - needs ruleset ID update
        $files=$this->File->find('all',['conditions'=>['title like'=>'Lb-%','publication_id'=>$id],'order'=>'id','recursive'=>-1]);
        // Check to see of the # of files matches the # already migrated (for this volume)
        $filesdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'files','publication_id'=>$id]]);
        $newrids=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['from_table'=>'rulesets']]);
        $fids=[];
        if(count($filesdone)!=count($files)) {
            echo "Files completed: ".count($filesdone)."<br />";
            // Remove completed datapoints from the $pnts array
            if(!empty($filesdone)) {
                foreach($files as $idx=>$file) {
                    if(in_array($file['File']['id'],$filesdone)) { unset($files[$idx]); }
                }
            }
            foreach($files as $file) {
                $f=$file['File'];$fids[]=$f['id'];
                $oldid=$f['id'];unset($f['id']); // So a new ID is assigned...
                // Update ruleset ID
                $f['ruleset_id']=$newrids[$f['ruleset_id']];
                // Add to Phase1
                $this->Phase1File->create();
                $this->Phase1File->save(['Phase1File'=>$f]);
                $newid=$this->Phase1File->id;
                // Add to Migration table
                $m=['Migration'=>['from_table'=>'files','from_id'=>$oldid,'to_table'=>'files','to_id'=>str_pad($newid,8,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                $this->Migration->create();
                $this->Migration->save($m);
                echo "File ".$oldid." saved<br />";
            }
        } else {
            echo "Files already migrated<br />";
            $fids=$this->Migration->find('list',['fields'=>['from_id'],'conditions'=>['from_table'=>'files','publication_id'=>$id]]);
        }
        if($phase<2) { exit; }

        // Text Files - needs file ID update
        $tfiles=$this->TextFile->find('all',['conditions'=>['file_id'=>$fids],'order'=>'id','recursive'=>-1]);
        // Check to see of the # of files matches the # already migrated (for this volume)
        $tfilesdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'text_files','publication_id'=>$id]]);
        $newtfids=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['from_table'=>'files']]);
        if(count($tfilesdone)!=count($tfiles)) {
            echo "Text files completed: ".count($tfilesdone)."<br />";
            // Remove completed datapoints from the $pnts array
            if(!empty($tfilesdone)) {
                foreach($tfiles as $idx=>$tfile) {
                    if(in_array($tfile['TextFile']['id'],$tfilesdone)) { unset($tfiles[$idx]); }
                }
            }
            foreach($tfiles as $tfile) {
                $t=$tfile['TextFile'];
                $oldid=$t['id'];unset($t['id']); // So a new ID is assigned...
                // Update ruleset ID
                $t['file_id']=$newtfids[$t['file_id']];
                // Add to Phase1
                $this->Phase1TextFile->create();
                $this->Phase1TextFile->save(['Phase1TextFile'=>$t]);
                $newid=$this->Phase1TextFile->id;
                // Add to Migration table
                $m=['Migration'=>['from_table'=>'text_files','from_id'=>$oldid,'to_table'=>'text_files','to_id'=>str_pad($newid,8,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                $this->Migration->create();
                $this->Migration->save($m);
                echo "Text file ".$oldid." saved<br />";
            }
        } else {
            echo "Text files already migrated<br />";
        }
        if($phase<3) { exit; }

        // Datasets - needs report ID, file ID, textfile ID, reference ID update
        $sets=$this->Dataset->find('all',['conditions'=>['file_id'=>$fids],'order'=>'id','recursive'=>-1]);
        // Check to see of the # of files matches the # already migrated (for this volume)
        $setsdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'datasets','publication_id'=>$id]]);
        $newrids=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['from_table'=>'reports']]);
        $newfids=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['from_table'=>'files','publication_id'=>$id]]);
        $newtids=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['from_table'=>'text_files','publication_id'=>$id]]);
        $newcids=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['from_table'=>'references']]);
        $dsids=[];
        if(count($setsdone)!=count($sets)) {
            echo "Datasets completed: ".count($setsdone)."<br />";
            // Remove completed datasets from the $sets array
            if(!empty($setsdone)) {
                foreach($sets as $idx=>$set) {
                    if(in_array($set['Dataset']['id'],$setsdone)) { unset($sets[$idx]); }
                }
            }
            foreach($sets as $set) {
                $s=$set['Dataset'];$dsids[]=$s['id'];
                $oldid=$s['id'];unset($s['id']); // So a new ID is assigned...
                // Update report ID
                $s['report_id']=$newrids[$s['report_id']];
                // Update file ID
                $s['file_id']=$newfids[$s['file_id']];
                // Update textfile ID
                $s['text_file_id']=$newtids[$s['text_file_id']];
                // Update reference ID
                $s['reference_id']=$newcids[$s['reference_id']];
                // Add to Phase1
                $this->Phase1Dataset->create();
                $this->Phase1Dataset->save(['Phase1Dataset'=>$s]);
                $newid=$this->Phase1Dataset->id;
                // Add to Migration table
                $m=['Migration'=>['from_table'=>'datasets','from_id'=>$oldid,'to_table'=>'datasets','to_id'=>str_pad($newid,6,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                $this->Migration->create();
                $this->Migration->save($m);
                echo "Dataset ".$oldid." saved<br />";
            }
        } else {
            echo "Datasets already migrated<br />";
            $dsids=$this->Migration->find('list',['fields'=>['from_id'],'conditions'=>['from_table'=>'datasets','publication_id'=>$id]]);
        }
        if($phase<4) { exit; }

        // Dataseries - needs dataset ID update
        $sers=$this->Dataseries->find('all',['conditions'=>['dataset_id'=>$dsids],'order'=>'id','recursive'=>-1]);
        // Check to see of the # of dataseries matches the # already migrated (for this volume)
        $sersdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'dataseries','publication_id'=>$id]]);
        $newdsids=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['from_table'=>'datasets']]);
        $serids=[];
        if(count($sersdone)!=count($sers)) {
            echo "Dataseries completed: ".count($sersdone)."<br />";
            // Remove completed datasets from the $sets array
            if(!empty($sersdone)) {
                foreach($sers as $idx=>$ser) {
                    if(in_array($ser['Dataseries']['id'],$sersdone)) { unset($sers[$idx]); }
                }
            }
            foreach($sers as $ser) {
                $s=$ser['Dataseries'];$serids[]=$s['id'];
                $oldid=$s['id'];unset($s['id']); // So a new ID is assigned...
                // Update report ID
                $s['dataset_id']=$newdsids[$s['dataset_id']];
                // Add to Phase1
                $this->Phase1Dataseries->create();
                $this->Phase1Dataseries->save(['Phase1Dataseries'=>$s]);
                $newid=$this->Phase1Dataseries->id;
                // Add to Migration table
                $m=['Migration'=>['from_table'=>'dataseries','from_id'=>$oldid,'to_table'=>'dataseries','to_id'=>str_pad($newid,6,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                $this->Migration->create();
                $this->Migration->save($m);
                echo "Dataseries ".$oldid." saved<br />";
            }
        } else {
            echo "Dataseries already migrated<br />";
            $serids=$this->Migration->find('list',['fields'=>['from_id'],'conditions'=>['from_table'=>'dataseries','publication_id'=>$id]]);
        }
        if($phase<5) { exit; }

        // Dataseries conditions, annotations, settings - need dataseries ID updates
        if(!empty($serids)) {
            // Get the datapoint ids for this volume
            $newsids=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['from_table'=>'dataseries','publication_id'=>$id]]);

            // Annotations (on dataseries)
            $anns=$this->Annotation->find('all',['conditions'=>['dataseries_id'=>$serids],'order'=>'id','recursive'=>-1]);
            if(!empty($anns)) {
                // Check to see of the # of equations matches the # already migrated (for this volume)
                $annsdone=$this->Migration->find('all',['conditions'=>['from_table'=>'annotations','publication_id'=>$id]]);
                if(count($annsdone)!=count($anns)) {
                    echo "Annontations completed: ".count($annsdone)."<br />";
                    // Remove completed datapoints from the $pnts array
                    if(!empty($annsdone)) {
                        foreach($anns as $idx=>$ann) {
                            if(in_array($ann['Annotation']['id'],$annsdone)) { unset($anns[$idx]); }
                        }
                    }
                    foreach($anns as $ann) {
                        $a=$ann['Annotation'];
                        $oldid=$a['id'];unset($a['id']); // So a new ID is assigned...
                        // Update dataseries ID
                        $a['dataseries_id']=$newsids[$a['dataseries_id']];
                        // Add to Phase1
                        $this->Phase1Annotation->create();
                        $this->Phase1Annotation->save(['Phase1Annotation'=>$a]);
                        $newid=$this->Phase1Annotation->id;
                        // Add to Migration table
                        $m=['Migration'=>['from_table'=>'annotations','from_id'=>$oldid,'to_table'=>'annotations','to_id'=>str_pad($newid,9,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                        $this->Migration->create();
                        $this->Migration->save($m);
                        echo "Annotation ".$oldid." saved<br />";
                        exit;
                    }
                } else {
                    echo "Annotations already migrated<br />";
                }
            } else {
                echo "No annotations in this volume<br />";
            }

            // Conditions (on dataseries)
            $cons=$this->Condition->find('all',['conditions'=>['dataseries_id'=>$serids],'order'=>'id','recursive'=>-1]);
            if(!empty($cons)) {
                // Check to see of the # of conditions matches the # already migrated (for this volume)
                $consdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'conditions','publication_id'=>$id]]);
                if(count($consdone)!=count($cons)) {
                    echo "Conditions completed: ".count($consdone)."<br />";
                    // Remove completed datapoints from the $pnts array
                    if(!empty($consdone)) {
                        foreach($cons as $idx=>$con) {
                            if(in_array($con['Condition']['id'],$consdone)) { unset($cons[$idx]); }
                        }
                    }
                    foreach($cons as $con) {
                        // Prep for transfer
                        $c=$con['Condition'];
                        $oldid=$c['id'];unset($c['id']); // So a new ID is assigned...
                        // Update datapoint ID
                        $c['dataseries_id']=$newsids[$c['dataseries_id']];
                        // Add to Phase1
                        $this->Phase1Condition->create();
                        $this->Phase1Condition->save(['Phase1Condition'=>$c]);
                        $newid=$this->Phase1Condition->id;
                        // Add to Migration table
                        $m=['Migration'=>['from_table'=>'conditions','from_id'=>$oldid,'to_table'=>'conditions','to_id'=>str_pad($newid,7,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                        $this->Migration->create();
                        $this->Migration->save($m);
                        echo "Condition ".$oldid." saved<br />";
                    }
                } else {
                    echo "Conditions already migrated<br />";
                }
            } else {
                echo "No conditions in this volume<br />";
            }

            // Settings (on dataseries)
            $stgs=$this->Setting->find('all',['conditions'=>['dataseries_id'=>$serids],'order'=>'id','recursive'=>-1]);
            if(!empty($stgs)) {
                // Check to see of the # of conditions matches the # already migrated (for this volume)
                $stgsdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'settings','publication_id'=>$id]]);
                if(count($stgsdone)!=count($stgs)) {
                    echo "Settings completed: ".count($stgsdone)."<br />";
                    // Remove completed datapoints from the $pnts array
                    if(!empty($stgsdone)) {
                        foreach($stgs as $idx=>$stg) {
                            if(in_array($stg['Setting']['id'],$stgsdone)) { unset($stgs[$idx]); }
                        }
                    }
                    foreach($stgs as $stg) {
                        // Prep for transfer
                        $s=$stg['Setting'];
                        $oldid=$s['id'];unset($s['id']); // So a new ID is assigned...
                        // Update datapoint ID
                        $s['dataseries_id']=$newsids[$s['dataseries_id']];
                        // Add to Phase1
                        $this->Phase1Setting->create();
                        $this->Phase1Setting->save(['Phase1Setting'=>$s]);
                        $newid=$this->Phase1Setting->id;
                        // Add to Migration table
                        $m=['Migration'=>['from_table'=>'settings','from_id'=>$oldid,'to_table'=>'settings','to_id'=>str_pad($newid,7,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                        $this->Migration->create();
                        $this->Migration->save($m);
                        echo "Setting ".$oldid." saved<br />";
                    }
                } else {
                    echo "Settings already migrated<br />";
                }
            } else {
                echo "No settings in this volume<br />";
            }
        }
        if($phase<6) { exit; }

        // Datapoints - needs dataseries ID update
        $pnts=$this->Datapoint->find('all',['conditions'=>['dataseries_id'=>$serids],'order'=>'id','recursive'=>-1]);
        $pntids=[];
        if(!empty($pnts)) {
            // Check to see of the # of datapoints matches the # already migrated (for this volume)
            $pntsdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'datapoints','publication_id'=>$id]]);
            $newdsids=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['from_table'=>'dataseries','publication_id'=>$id]]);
            if(count($pntsdone)!=count($pnts)) {
                echo "Datapoints completed: ".count($pntsdone)."<br />";
                // Remove completed datapoints from the $pnts array
                foreach($pnts as $idx=>$pnt) {
                    if(in_array($pnt['Datapoint']['id'],$pntsdone)) { $pntids[]=$pnt['Datapoint']['id'];unset($pnts[$idx]); }
                }
                foreach($pnts as $pnt) {
                    // Prep for transfer
                    $p=$pnt['Datapoint'];$pntids[]=$p['id'];
                    $oldid=$p['id'];unset($p['id']); // So a new ID is assigned...
                    // Update dataseries ID
                    $p['dataseries_id']=$newdsids[$p['dataseries_id']];
                    // Add to Phase1
                    $this->Phase1Datapoint->create();
                    $this->Phase1Datapoint->save(['Phase1Datapoint'=>$p]);
                    $newid=$this->Phase1Datapoint->id;
                    // Add to Migration table
                    $m=['Migration'=>['from_table'=>'datapoints','from_id'=>$oldid,'to_table'=>'datapoints','to_id'=>str_pad($newid,7,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                    $this->Migration->create();
                    $this->Migration->save($m);
                    echo "Datapoint ".$oldid." saved<br />";
                }
            } else {
                echo "Datapoints already migrated<br />";
                $pntids=$this->Migration->find('list',['fields'=>['from_id'],'conditions'=>['from_table'=>'datapoints','publication_id'=>$id]]);
            }
        } else {
            echo "No datapoints in this volume<br />";
        }
        if($phase<7) { exit; }

        // Datapoint conditions, data, supplemental data, annotations - need datapoint ID updates
        if(!empty($pntids)) {
            // Get the datapoint ids for this volume
            $newdsids=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['from_table'=>'datapoints','publication_id'=>$id]]);

            // Conditions
            $cons=$this->Condition->find('all',['conditions'=>['datapoint_id'=>$pntids],'order'=>'id','recursive'=>-1]);
            if(!empty($cons)) {
                // Check to see of the # of conditions matches the # already migrated (for this volume)
                $consdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'conditions','publication_id'=>$id]]);
                if(count($consdone)!=count($cons)) {
                    echo "Conditions completed: ".count($consdone)."<br />";
                    // Remove completed datapoints from the $pnts array
                    if(!empty($consdone)) {
                        foreach($cons as $idx=>$con) {
                            if(in_array($con['Condition']['id'],$consdone)) { unset($cons[$idx]); }
                        }
                    }
                    foreach($cons as $con) {
                        // Prep for transfer
                        $c=$con['Condition'];
                        $oldid=$c['id'];unset($c['id']); // So a new ID is assigned...
                        // Update datapoint ID
                        $c['datapoint_id']=$newdsids[$c['datapoint_id']];
                        // Add to Phase1
                        $this->Phase1Condition->create();
                        $this->Phase1Condition->save(['Phase1Condition'=>$c]);
                        $newid=$this->Phase1Condition->id;
                        // Add to Migration table
                        $m=['Migration'=>['from_table'=>'conditions','from_id'=>$oldid,'to_table'=>'conditions','to_id'=>str_pad($newid,7,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                        $this->Migration->create();
                        $this->Migration->save($m);
                        echo "Condition ".$oldid." saved<br />";
                    }
                } else {
                    echo "Conditions already migrated<br />";
                }
            } else {
                echo "No conditions in this volume<br />";
            }

            // Data
            $data=$this->Data->find('all',['conditions'=>['datapoint_id'=>$pntids],'order'=>'id','recursive'=>-1]);
            if(!empty($data)) {
                // Check to see of the # of equations matches the # already migrated (for this volume)
                $datadone=$this->Migration->find('all',['conditions'=>['from_table'=>'data','publication_id'=>$id]]);
                if(count($datadone)!=count($data)) {
                    echo "Data completed: ".count($datadone)."<br />";
                    // Remove completed datapoints from the $pnts array
                    if(!empty($datadone)) {
                        foreach($data as $idx=>$datum) {
                            if(in_array($datum['Data']['id'],$datadone)) { unset($data[$idx]); }
                        }
                    }
                    foreach($data as $datum) {
                        $d=$datum['Data'];
                        $oldid=$d['id'];unset($d['id']); // So a new ID is assigned...
                        // Update datapoint ID
                        $d['datapoint_id']=$newdsids[$d['datapoint_id']];
                        // Add to Phase1
                        $this->Phase1Data->create();
                        $this->Phase1Data->save(['Phase1Data'=>$d]);
                        $newid=$this->Phase1Data->id;
                        // Add to Migration table
                        $m=['Migration'=>['from_table'=>'data','from_id'=>$oldid,'to_table'=>'data','to_id'=>str_pad($newid,7,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                        $this->Migration->create();
                        $this->Migration->save($m);
                        echo "Data ".$oldid." saved<br />";
                    }
                } else {
                    echo "Data already migrated<br />";
                }
            } else {
                echo "No data in this volume<br />";
            }

            // Supplemental data
            $sdata=$this->SupplementalData->find('all',['conditions'=>['datapoint_id'=>$pntids],'order'=>'id','recursive'=>-1]);
            if(!empty($sdata)) {
                // Check to see of the # of equations matches the # already migrated (for this volume)
                $sdatadone=$this->Migration->find('all',['conditions'=>['from_table'=>'data','publication_id'=>$id]]);
                if(count($sdatadone)!=count($sdata)) {
                    echo "Data completed: ".count($sdatadone)."<br />";
                    // Remove completed datapoints from the $pnts array
                    if(!empty($sdatadone)) {
                        foreach($sdata as $idx=>$datum) {
                            if(in_array($datum['SupplementalData']['id'],$sdatadone)) { unset($sdata[$idx]); }
                        }
                    }
                    foreach($sdata as $datum) {
                        $d=$datum['SupplementalData'];
                        $oldid=$d['id'];unset($d['id']); // So a new ID is assigned...
                        // Update datapoint ID
                        $d['datapoint_id']=$newdsids[$d['datapoint_id']];
                        // Add to Phase1
                        $this->Phase1SupplementalData->create();
                        $this->Phase1SupplementalData->save(['Phase1SupplementalData'=>$d]);
                        $newid=$this->Phase1SupplementalData->id;
                        // Add to Migration table
                        $m=['Migration'=>['from_table'=>'supplemental_data','from_id'=>$oldid,'to_table'=>'supplemental_data','to_id'=>str_pad($newid,7,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                        $this->Migration->create();
                        $this->Migration->save($m);
                        echo "Supplemental Data ".$oldid." saved<br />";
                    }
                } else {
                    echo "Supplemental Data already migrated<br />";
                }
            } else {
                echo "No supplemental data in this volume<br />";
            }

            // Annotations
            $anns=$this->Annotation->find('all',['conditions'=>['datapoint_id'=>$pntids],'order'=>'id','recursive'=>-1]);
            if(!empty($anns)) {
                // Check to see of the # of equations matches the # already migrated (for this volume)
                $annsdone=$this->Migration->find('all',['conditions'=>['from_table'=>'annotations','publication_id'=>$id]]);
                if(count($annsdone)!=count($anns)) {
                    echo "Annontations completed: ".count($annsdone)."<br />";
                    // Remove completed datapoints from the $pnts array
                    if(!empty($annsdone)) {
                        foreach($anns as $idx=>$ann) {
                            if(in_array($ann['Annotation']['id'],$annsdone)) { unset($anns[$idx]); }
                        }
                    }
                    foreach($anns as $ann) {
                        $a=$ann['Annotation'];
                        $oldid=$a['id'];unset($a['id']); // So a new ID is assigned...
                        // Update datapoint ID
                        $a['datapoint_id']=$newdsids[$a['datapoint_id']];
                        // Add to Phase1
                        $this->Phase1Annotation->create();
                        $this->Phase1Annotation->save(['Phase1Annotation'=>$a]);
                        $newid=$this->Phase1Annotation->id;
                        // Add to Migration table
                        $m=['Migration'=>['from_table'=>'annotations','from_id'=>$oldid,'to_table'=>'annotations','to_id'=>str_pad($newid,9,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                        $this->Migration->create();
                        $this->Migration->save($m);
                        echo "Annotation ".$oldid." saved<br />";
                    }
                } else {
                    echo "Annotations already migrated<br />";
                }
            } else {
                echo "No annotations in this volume<br />";
            }
        }
        if($phase<8) { exit; }

        // Equations - needs dataseries ID update  (eqntypes has just been copied over)
        $eqns=$this->Equation->find('all',['conditions'=>['dataseries_id'=>$serids],'order'=>'id','recursive'=>-1]);
        $eqnids=[];
        if(!empty($eqns)) {
            // Check to see of the # of equations matches the # already migrated (for this volume)
            $eqnsdone=$this->Migration->find('all',['conditions'=>['from_table'=>'equations','publication_id'=>$id]]);
            $newdsids=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['from_table'=>'dataseries']]);
            if(count($eqnsdone)!=count($eqns)) {
                echo "Equations completed: ".count($eqnsdone)."<br />";
                // Remove completed datapoints from the $pnts array
                foreach($eqns as $idx=>$eqn) {
                    if(in_array($eqn['Equation']['id'],$eqnsdone)) { $eqnids[]=$eqn['Equation']['id'];unset($eqns[$idx]); }
                }
                foreach($eqns as $eqn) {
                    $e=$eqn['Equation'];$eqnids[]=$e['id'];
                    $oldid=$e['id'];unset($e['id']); // So a new ID is assigned...
                    // Update dataseries ID
                    $e['dataseries_id']=$newdsids[$e['dataseries_id']];
                    // Add to Phase1
                    $this->Phase1Equation->create();
                    $this->Phase1Equation->save(['Phase1Equation'=>$e]);
                    $newid=$this->Phase1Equation->id;
                    // Add to Migration table
                    $m=['Migration'=>['from_table'=>'equations','from_id'=>$oldid,'to_table'=>'equations','to_id'=>str_pad($newid,5,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                    $this->Migration->create();
                    $this->Migration->save($m);
                    echo "Equation ".$oldid." saved<br />";
                }
            } else {
                echo "Equations already migrated<br />";
                $eqnids=$this->Migration->find('list',['fields'=>['from_id'],'conditions'=>['from_table'=>'equations','publication_id'=>$id]]);
            }
        } else {
            echo "No equations in this volume<br />";
        }
        if($phase<9) { exit; }

        // Equation variables and terms - need equation ID updates
        if(!empty($eqnids)) {
            // Get the equation ids for this volume
            $neweids=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['from_table'=>'equations','publication_id'=>$id]]);

            // Variables
            $vars=$this->Eqnvar->find('all',['conditions'=>['equation_id'=>$eqnids],'order'=>'id','recursive'=>-1]);
            if(!empty($vars)) {
                // Check to see of the # of equations matches the # already migrated (for this volume)
                $varsdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'eqnvars','publication_id'=>$id]]);
                if(count($varsdone)!=count($vars)) {
                    echo "Variables completed: ".count($varsdone)."<br />";
                    // Remove completed datapoints from the $pnts array
                    if(!empty($varsdone)) {
                        foreach($vars as $idx=>$var) {
                            if(in_array($var['Eqnvar']['id'],$varsdone)) { unset($vars[$idx]); }
                        }
                    }
                    echo "Variables remaining: ".count($vars)."<br />";
                    foreach($vars as $var) {
                        $v=$var['Eqnvar'];
                        $oldid=$v['id'];unset($v['id']); // So a new ID is assigned...
                        // Update dataseries ID
                        $v['equation_id']=$neweids[$v['equation_id']];
                        // Add to Phase1
                        $this->Phase1Eqnvar->create();
                        $this->Phase1Eqnvar->save(['Phase1Eqnvar'=>$v]);
                        $newid=$this->Phase1Eqnvar->id;
                        // Add to Migration table
                        $m=['Migration'=>['from_table'=>'eqnvars','from_id'=>$oldid,'to_table'=>'eqnvars','to_id'=>str_pad($newid,8,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                        $this->Migration->create();
                        $this->Migration->save($m);
                        echo "Equation variable ".$oldid." saved<br />";
                    }
                } else {
                    echo "Equation variables already migrated<br />";
                }
            } else {
                echo "No equation variables in this volume<br />";
            }

            // Terms
            $terms=$this->Eqnterm->find('all',['conditions'=>['equation_id'=>$eqnids],'order'=>'id','recursive'=>-1]);
            if(!empty($terms)) {
                // Check to see of the # of equations matches the # already migrated (for this volume)
                $termsdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'eqnterms','publication_id'=>$id]]);
                if(count($termsdone)!=count($terms)) {
                    echo "Terms completed: ".count($termsdone)."<br />";
                    // Remove completed datapoints from the $pnts array
                    if(!empty($termsdone)) {
                        foreach($terms as $idx=>$term) {
                            if(in_array($term['Eqnterm']['id'],$termsdone)) { unset($terms[$idx]); }
                        }
                    }
                    echo "Variables remaining: ".count($vars)."<br />";
                    foreach($terms as $term) {
                        $t=$term['Eqnterm'];
                        $oldid=$t['id'];unset($t['id']); // So a new ID is assigned...
                        // Update dataseries ID
                        $t['equation_id']=$neweids[$t['equation_id']];
                        // Add to Phase1
                        $this->Phase1Eqnterm->create();
                        $this->Phase1Eqnterm->save(['Phase1Eqnterm'=>$t]);
                        $newid=$this->Phase1Eqnterm->id;
                        // Add to Migration table
                        $m=['Migration'=>['from_table'=>'eqnterms','from_id'=>$oldid,'to_table'=>'eqnterms','to_id'=>str_pad($newid,6,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                        $this->Migration->create();
                        $this->Migration->save($m);
                        echo "Equation term ".$oldid." saved<br />";
                    }
                } else {
                    echo "Equation terms already migrated<br />";
                }
            } else {
                echo "No equation terms in this volume<br />";
            }

            // Annotations (on equations)
            $anns=$this->Annotation->find('all',['conditions'=>['equation_id'=>$eqnids],'order'=>'id','recursive'=>-1]);
            if(!empty($anns)) {
                // Check to see of the # of equations matches the # already migrated (for this volume)
                $annsdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'annotations','publication_id'=>$id]]);
                if(count($annsdone)!=count($anns)) {
                    echo "Annotations completed: ".count($annsdone)."<br />";
                    // Remove completed annotations from the $anns array
                    if(!empty($annsdone)) {
                        foreach($anns as $idx=>$ann) {
                            if(in_array($ann['Annotation']['id'],$annsdone)) { unset($anns[$idx]); }
                        }
                    }
                    echo "Annotations remaining: ".count($anns)."<br />";
                    foreach($anns as $ann) {
                        $a=$ann['Annotation'];
                        $oldid=$a['id'];unset($a['id']); // So a new ID is assigned...
                        // Update dataseries ID
                        $a['equation_id']=$neweids[$a['equation_id']];
                        // Add to Phase1
                        $this->Phase1Annotation->create();
                        $this->Phase1Annotation->save(['Phase1Annotation'=>$a]);
                        $newid=$this->Phase1Annotation->id;
                        // Add to Migration table
                        $m=['Migration'=>['from_table'=>'annotations','from_id'=>$oldid,'to_table'=>'annotations','to_id'=>str_pad($newid,9,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                        $this->Migration->create();
                        $this->Migration->save($m);
                        echo "Annotation ".$oldid." saved<br />";
                    }
                } else {
                    echo "Annotations (on equations) already migrated<br />";
                }
            } else {
                echo "No annotations (on equations) in this volume<br />";
            }

            // Supplemental data (on equations)
            $sdata=$this->SupplementalData->find('all',['conditions'=>['equation_id'=>$eqnids],'order'=>'id','recursive'=>-1]);
            if(!empty($sdata)) {
                // Check to see of the # of equations matches the # already migrated (for this volume)
                $sdatadone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'supplemental_data','publication_id'=>$id]]);
                if(count($sdatadone)!=count($sdata)) {
                    echo "Supplemental data completed: ".count($sdatadone)."<br />";
                    // Remove completed datapoints from the $pnts array
                    if(!empty($sdatadone)) {
                        foreach($sdata as $idx=>$datum) {
                            if(in_array($datum['SupplementalData']['id'],$sdatadone)) { unset($sdata[$idx]); }
                        }
                    }
                    echo "Supplemental data remaining: ".count($sdata)."<br />";
                    foreach($sdata as $datum) {
                        $d=$datum['SupplementalData'];
                        $oldid=$d['id'];unset($d['id']); // So a new ID is assigned...
                        // Update datapoint ID
                        $d['equation_id']=$neweids[$d['equation_id']];
                        // Add to Phase1
                        $this->Phase1SupplementalData->create();
                        $this->Phase1SupplementalData->save(['Phase1SupplementalData'=>$d]);
                        $newid=$this->Phase1SupplementalData->id;
                        // Add to Migration table
                        $m=['Migration'=>['from_table'=>'supplemental_data','from_id'=>$oldid,'to_table'=>'supplemental_data','to_id'=>str_pad($newid,7,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                        $this->Migration->create();
                        $this->Migration->save($m);
                        echo "Supplemental Data ".$oldid." saved<br />";
                    }
                } else {
                    echo "Supplemental Data already migrated<br />";
                }
            } else {
                echo "No supplemental data in this volume<br />";
            }

            // Settings (on equations)
            $sdata=$this->Setting->find('all',['conditions'=>['equation_id'=>$eqnids],'order'=>'id','recursive'=>-1]);
            if(!empty($sdata)) {
                // Check to see of the # of equations matches the # already migrated (for this volume)
                $sdatadone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'supplemental_data','publication_id'=>$id]]);
                if(count($sdatadone)!=count($sdata)) {
                    echo "Settings completed: ".count($sdatadone)."<br />";
                    // Remove completed datapoints from the $pnts array
                    if(!empty($sdatadone)) {
                        foreach($sdata as $idx=>$datum) {
                            if(in_array($datum['SupplementalData']['id'],$sdatadone)) { unset($sdata[$idx]); }
                        }
                    }
                    echo "Settings remaining: ".count($sdata)."<br />";
                    foreach($sdata as $datum) {
                        $d=$datum['Setting'];
                        $oldid=$d['id'];unset($d['id']); // So a new ID is assigned...
                        // Update equation ID
                        $d['equation_id']=$neweids[$d['equation_id']];
                        // Add to Phase1
                        $this->Phase1Setting->create();
                        $this->Phase1Setting->save(['Phase1Setting'=>$d]);
                        $newid=$this->Phase1Setting->id;
                        // Add to Migration table
                        $m=['Migration'=>['from_table'=>'settings','from_id'=>$oldid,'to_table'=>'settings','to_id'=>str_pad($newid,7,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                        $this->Migration->create();
                        $this->Migration->save($m);
                        echo "Setting ".$oldid." saved<br />";
                    }
                } else {
                    echo "Settings already migrated<br />";
                }
            } else {
                echo "No settings in this volume<br />";
            }
        }
        if($phase<10) { exit; }

        // Update the data_systems table (dataset_id and data_id/equation_id get updated, system_id and property_id do not)
        $setlist=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['publication_id'=>$id,'from_table'=>'datasets'],'order'=>'id']);
        $transset=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['publication_id'=>$id,'from_table'=>'datasets'],'order'=>'from_id']);
        $transdat=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['publication_id'=>$id,'from_table'=>'data'],'order'=>'from_id']);
        $transeqn=$this->Migration->find('list',['fields'=>['from_id','to_id'],'conditions'=>['publication_id'=>$id,'from_table'=>'equations'],'order'=>'from_id']);
        $datasyss=$this->DataSystem->find('all',['conditions'=>['dataset_id'=>$setlist],'order'=>'id','recursive'=>-1]);
        $dsdone=$this->Migration->find('list',['fields'=>['id','from_id'],'conditions'=>['from_table'=>'data_systems','publication_id'=>$id]]);
        if(count($dsdone)!=count($datasyss)) {
            echo "Datapoints completed: " . count($dsdone) . "<br />";
            if(!empty($dsdone)) {
                // Remove completed data_systems from the $datasyss array
                foreach ($datasyss as $idx => $datasys) {
                    if (in_array($datasys['DataSystem']['id'], $dsdone)) {
                        unset($datasyss[$idx]);
                    }
                }
            }
            foreach($datasyss as $datasys) {
                $ds=$datasys['DataSystem'];
                $oldid=$ds['id'];unset($ds['id']);
                // Convert dataset_id
                $ds['dataset_id']=$transset[$ds['dataset_id']];
                // Convert data_id (if not null)
                if(!is_null($ds['data_id'])) { $ds['data_id']=$transdat[$ds['data_id']]; }
                // Convert equation_id (if not null)
                if(!is_null($ds['equation_id'])) { $ds['equation_id']=$transeqn[$ds['equation_id']]; }
                // Add to Phase1
                $this->Phase1DataSystem->create();
                $this->Phase1DataSystem->save(['Phase1DataSystem'=>$ds]);
                $newid=$this->Phase1DataSystem->id;
                // Add to Migration table
                $m=['Migration'=>['from_table'=>'data_systems','from_id'=>$oldid,'to_table'=>'data_systems','to_id'=>str_pad($newid,7,'0',STR_PAD_LEFT),'publication_id'=>$id]];
                $this->Migration->create();
                $this->Migration->save($m);
                echo "DataSystem entry ".$oldid." saved<br />";
            }
        }  else {
            echo "DataSystem entries already migrated<br />";
        }
        exit;
    }

}