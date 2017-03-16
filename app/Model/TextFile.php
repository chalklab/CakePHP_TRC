<?php

/**
 * Class TextFile
 * TextFile model
 */
class TextFile extends AppModel
{

    public $belongsTo = ['File'];

    public $hasMany = ['Dataset'];

    /**
     * General function to add a new textfile
     * @param array $data
     * @return integer
     */
    public function insert($data)
    {
        $model='TextFile';
        $this->create();
        $ret=$this->save([$model=>$data]);
        $this->clear();
        return $ret[$model];
    }

    /**
     * Clean a textfile of all related data (via Reports)
     * @param $id
     * @return bool
     */
    public function clean($id)
    {
        $Report=ClassRegistry::init('Report');
        $c=['Dataset'=>['Report']];
        $tfiles=$this->find('all',['conditions'=>['TextFile.id'=>$id],'contain'=>$c,'recursive'=>-1]);
        foreach($tfiles as $tfile) {
            foreach($tfile['Dataset'] as $set) {
                $Report->delete($set['Report']['id']);
            }
        }
        return true;
    }

    /**
     * Take the text file, recognize the data and organize it
     * @param array $tfile
     * @param array $setdata
     * @return array
     */
    public function process($tfile=[],$setdata=[]) {
        // Setup
        $regexes=$setdata['regexes'];$actions=$setdata['actions'];$blocks=$setdata['blocks'];
        $rmodes=$setdata['rmodes'];$smodes=$setdata['smodes']; // $ropts not needed as can be processed in switch
        // Unused in this function (passed to getInfo and dedug via setdata)
        //$rows=$setdata['rows'];$types=$setdata['types'];$fields=$setdata['fields'];
        //$datatypes=$setdata['datatypes'];$units=$setdata['units'];$properties=$setdata['properties'];
        //$metadata=$setdata['metadata'];$scidata=$setdata['scidata'];$cmpdnums=$setdata['cmpdnums'];

        // Process text
        $currrefs=[];$currcond=null;$currcmpd=null;$group=[];$debug=[];$line=0;$start=0;$repeat=0;$repeat2=0;$tid=null;$prevline=-1;$done=[];
        $trash=[];$errors=[];$current=[];$alt=1; // Used for repeateveryotherline to copy over refcodes
        $db=['annotations'=>[],'compounds'=>[],'properties'=>[],'conditions'=>[],'data'=>[],'datafactors'=>[],'errors'=>[],'eqnterms'=>[],
            'eqnoperators'=>[],'eqnprops'=>[],'eqnpropunits'=>[],'eqnvariables'=>[],'eqnvariablelimits'=>[],
            'suppdata'=>[],'propheaders'=>[],'references'=>[],'series'=>[],'seriesconds'=>[],'settings'=>[]
        ];
        //debug($actions);debug($regexes);debug($tfile);debug($setdata);exit;
        //if(AuthComponent::user('type') == 'superadmin') { debug($tfile); }
        if(!is_null($tid)) { debug($tfile); }
        while($line<=(count($tfile)-1)) { // Used for repeat of data in file (i.e. multiple tables) - $line in switch is key...
            if($line!=0) {
                // Unset regexes that are before a line that indicates a loop action
                foreach($actions as $step=>$act) {
                    if(!stristr($act,"LOOP")) {
                        unset($regexes[$step]);
                    } else {
                        break;
                    }
                }
                if(empty($regexes)) {
                    $errors['rules'][]=['id'=>'R004','step'=>$step,'line'=>$line,'issue'=>"Extra lines to process (no LOOP action)"];
                }
            }
            if($line!=$prevline) {
                $prevline=$line;
            } else {
                // Processing is stuck on a line in an infinite loop...
                $errors['rules'][]=['id'=>'R008','step'=>$step,'line'=>$line,'issue'=>"Infinite loop in process while"];
                break;
            }
            if(!is_null($tid)) { debug($regexes);debug($actions); }
            foreach($regexes as $step=>$regex) {
                //if(AuthComponent::user('type') == 'superadmin') { echo "Line: ".$line.", Step: ".$step.", Action: ".$actions[$step]."<br />"; }
                switch($actions[$step]) {
                    case "NEXTLINE";
                    case "NEXTLINESTARTLOOP";
                        if(!is_null($tid)) {  echo "Line: ".$line.", Step: ".$step.", Action: ".$actions[$step]."<br />"; }
                        if(!isset($tfile[$line])) { break 2; }
                        if(preg_match('/'.$regex.'/mu',$tfile[$line],$matches)) {
                            $match=$matches[0];unset($matches[0]);$done[]=$line;
                            if($rmodes[$step]=="match") {
                                $trash[$step]=$match;$line++;continue 2; // Go to next regex
                            } else {
                                $this->getmatch($db,$debug,$errors,$step,$matches,$setdata,$rmodes[$step],$smodes,$blocks,$line);
                            }
                            // Check if a reference is identified on this line and if so add to other lines
                            if(in_array('reference',$setdata['scidata'][$step])) {
                                $rcount=count($db['references']);
                                $lastref=$db['references'][($rcount-1)];
                                if($lastref['location']['line']==$line) {
                                    // OK we are on a line where a ref was added
                                    $currrefs[$line] = $lastref;
                                } else {
                                    // OK we are on a line that is a repeat for a dataset and does not contain a ref
                                    $lastref['location']['line']=$line;
                                    $db['references'][]=$lastref;
                                }

                            } else {
                                if(in_array('series',$setdata['scidata'][$step])) {
                                    // If this line is the start of new series we dont need prev ref
                                } elseif(!empty($currrefs)) {
                                    // Find the ref to add to this line
                                    $foundline=0;
                                    foreach($currrefs as $nline=>$nextref) {
                                        if($line>$nline) {
                                            $foundline=$nline;
                                        } else {
                                            break;
                                        }
                                    }
                                    $temp=$currrefs[$foundline];
                                    if($setdata['cols'][$step]>1) {
                                        for($x=0;$x<$setdata['cols'][$step];$x++) {
                                            $temp['location']['line']=$line."_".($x+1);
                                            $db['references'][]=$temp;
                                        }
                                    } else {
                                        $temp['location']['line']=$line;
                                        $db['references'][]=$temp;
                                    }
                                }
                            }
                            $line++;
                        } else {
                            // Expecting to match line but its not found
                            $errors['rules'][]=['id'=>'R003','step'=>$step,'line'=>$line,'issue'=>"No line found (Action: NEXTLINE)"];
                        }
                        break;
                    case "NEXTLINECHOICE";
                    case "NEXTLINESTARTLOOPCHOICE";
                        if(!is_null($tid)) { echo "Line: ".$line.", Step: ".$step.", Action: ".$actions[$step]."<br />"; }
                        // Assumes there are multiple rules from which one should match
                        if(empty($group)) {
                            for($s=$step;$s<count($actions)+1;$s++) {
                                if($actions[$s]=="NEXTLINECHOICE"||$actions[$s]=="NEXTLINESTARTLOOPCHOICE") {
                                    if(preg_match('/'.$regexes[$s].'/mu',$tfile[$line])) {
                                        $group[$s]=1;
                                    } else {
                                        $group[$s]=0;
                                    }
                                } else {
                                    break;
                                }
                            }
                            $laststep=max(array_keys($group));
                        }
                        if(!is_null($tid)) { debug($group); }
                        if($group[$step]) {
                            if(preg_match('/'.$regex.'/mu',$tfile[$line],$matches)) {
                                $match=$matches[0];unset($matches[0]);$done[]=$line;
                                if($rmodes[$step]=="match") {
                                    $trash[$step]=$match;$line++;continue 2; // Go to next regex
                                } else {
                                    $this->getmatch($db,$debug,$errors,$step,$matches,$setdata,$rmodes[$step],$smodes,$blocks,$line);
                                }
                                // Check if a reference is identified on this line and if so add to other lines
                                if(in_array('reference',$setdata['scidata'][$step])) {
                                    $rcount=count($db['references']);
                                    $lastref=$db['references'][($rcount-1)];
                                    if($lastref['location']['line']==$line) {
                                        // OK we are on a line where a ref was added
                                        $currrefs[$line] = $lastref;
                                    } else {
                                        // OK we are on a line that is a repeat for a dataset and does not contain a ref
                                        $lastref['location']['line']=$line;
                                        $db['references'][]=$lastref;
                                    }
                                } else {
                                    if(!empty($currrefs)) {
                                        // Find the ref to add to this line
                                        $foundline=0;
                                        foreach($currrefs as $nline=>$nextref) {
                                            if($line>$nline) {
                                                $foundline=$nline;
                                            } else {
                                                break;
                                            }
                                        }
                                        $temp=$currrefs[$foundline];
                                        $temp['location']['line']=$line;
                                        $db['references'][]=$temp;
                                    }
                                }
                                $line++;
                            } else {
                                // Expecting to match line but its not found
                                $errors['rules'][]=['id'=>'R003','step'=>$step,'line'=>$line,'issue'=>"No line found (Action: NEXTLINECHOICE)"];
                            }
                        }
                        if($step==$laststep) {
                            $group=[];
                        }
                        break;
                    case "OPTIONALSTEP";
                    case "OPTIONALSTEPSTARTLOOP";
                    if(!is_null($tid)) {  echo "Line: ".$line.", Step: ".$step.", Action: ".$actions[$step]."<br />"; }
                        if(!isset($tfile[$line])) { break 2; }
                        if(preg_match('/'.$regex.'/mu',$tfile[$line],$matches)) {
                            //echo "In optional step";exit;
                            $match=$matches[0];unset($matches[0]);$done[]=$line;
                            if($rmodes[$step]=="match") {
                                $trash[$step]=$match;$line++;continue 2; // Go to next regex
                            } else {
                                $this->getmatch($db,$debug,$errors,$step,$matches,$setdata,$rmodes[$step],$smodes,$blocks,$line);
                            }
                            if(in_array('reference',$setdata['scidata'][$step])) {
                                $rcount=count($db['references']);
                                $lastref=$db['references'][($rcount-1)];
                                if($lastref['location']['line']==$line) {
                                    // OK we are on a line where a ref was added
                                    $currrefs[$line] = $lastref;
                                } else {
                                    // OK we are on a line that is a repeat for a dataset and does not contain a ref
                                    $temp=$lastref;
                                    $temp['location']['line']=$line;
                                    $db['references'][]=$temp;
                                }
                            } elseif(in_array('condition',$setdata['scidata'][$step])||in_array('data',$setdata['scidata'][$step])||in_array('suppdata',$setdata['scidata'][$step])) {
                                if(!empty($currrefs)) {
                                    // Find the ref to add to this line
                                    $foundline=0;
                                    foreach($currrefs as $nline=>$nextref) {
                                        if($line>$nline) {
                                            $foundline=$nline;
                                        } else {
                                            break;
                                        }
                                    }
                                    $temp=$currrefs[$foundline];
                                    $temp['location']['line']=$line;
                                    $db['references'][]=$temp;
                                }
                            } else {
                                // Dont add a reference
                            }
                            $line++;
                            if(!isset($tfile[$line])) { break 2; }
                        }
                        break;
                    case "REPEATUNTILNEXTSTEP";
                    case "OPTIONALREPEATUNTILNEXTSTEP";
                    case "OPTIONALREPEATUNTILNEXTSTEPSTARTLOOP";
                        if(!is_null($tid)) {  echo "Line: ".$line.", Step: ".$step.", Action: ".$actions[$step]."<br />"; }
                        if(!isset($tfile[$line])) { break 2; }
                        while(preg_match('/'.$regex.'/mu',$tfile[$line],$matches)) {
                            //if(AuthComponent::user('type') == 'superadmin') { debug($matches);debug($tfile); }
                            $match=$matches[0];unset($matches[0]);$done[]=$line; // Remove the full match line
                            if($rmodes[$step]=="match") {
                                $trash[$step]=$match;$line++;continue 2; // Go to next regex
                            } else {
                                $this->getmatch($db,$debug,$errors,$step,$matches,$setdata,$rmodes[$step],$smodes,$blocks,$line);
                            }
                            // Check if a (single) condition is identified on this line and if so add to other lines
                            // TODO: Multiple conditions on one line
                            if(in_array('condition',$setdata['scidata'][$step])) {
                                $ccount=count($db['conditions']);
                                $lastcond=$db['conditions'][($ccount-1)];
                                if($lastcond['location']['line']==$line) {
                                    // Found value on this line but is it empty?
                                    if(empty($lastcond['value'])) {
                                        $db['conditions'][($ccount-1)]['value']=$currcond;
                                    } else {
                                        // OK save the value of the condition to add to other lines
                                        $currcond=$lastcond['value'];
                                    }
                                } else {
                                    // OK we are on a line where a condition was not found (e.g. end of line)
                                    $temp=$lastcond;
                                    $temp['location']['line']=$line;
                                    $db['conditions'][]=$temp;
                                }
                            }
                            // Check if a reference is identified on this line and if so add to other lines
                            if(in_array('reference',$setdata['scidata'][$step])) {
                                $rcount=count($db['references']);
                                $lastref=$db['references'][($rcount-1)];
                                if($lastref['location']['line']==$line) {
                                    // OK we are on a line where a ref was added
                                    $currrefs[$line] = $lastref;
                                } else {
                                    // OK we are on a line that is a repeat for a dataset and does not contain a ref
                                    $lastref['location']['line']=$line;
                                    $db['references'][]=$lastref;
                                }
                            } elseif(in_array('condition',$setdata['scidata'][$step])||in_array('data',$setdata['scidata'][$step])||in_array('suppdata',$setdata['scidata'][$step])) {
                                if(!empty($currrefs)) {
                                    // Find the ref to add to this line
                                    $foundline=0;
                                    foreach($currrefs as $nline=>$nextref) {
                                        if($line>$nline) {
                                            $foundline=$nline;
                                        } else {
                                            break;
                                        }
                                    }
                                    if($setdata['cols'][$step]>1) {
                                        for($x=0;$x<$setdata['cols'][$step];$x++) {
                                            $temp=$currrefs[$foundline];
                                            $temp['location']['line']=str_pad($line,2,'0',STR_PAD_LEFT)."_".($x+1);
                                            $db['references'][]=$temp;
                                        }
                                    } else {
                                        $temp=$currrefs[$foundline];
                                        $temp['location']['line']=str_pad($line,2,'0',STR_PAD_LEFT);
                                        $db['references'][]=$temp;
                                    }
                                }
                            } else {
                                // Don't add a reference
                            }
                            $line++;$repeat++;
                            if(!isset($tfile[$line])) { break 2; }
                            // Look ahead to see if there is a rogue column header line (from table split across pages)
                            if($step!==1&&preg_match('/'.$regexes[$step-1].'/mu',$tfile[$line])) {
                                // Check to see if the next regex (if there is one) matches the next line
                                $line++; //Jump over next line
                            }
                        }
                        if($repeat==0&&$actions[$step]=="REPEATUNTILNEXTSTEP") {
                            $errors['rules'][]=['id'=>'R005','step'=>$step,'line'=>$line,'issue'=>"No line found (Action: REPEATUNTILNEXTSTEP)"];
                        } else {
                            $repeat=0;// Reset for other repeats
                        }
                        break;
                    case "REPEATEVERYOTHERLINE";
                    case "OPTIONALREPEATEVERYOTHERLINE";
                        if(!is_null($tid)) {  echo "Line: ".$line.", Step: ".$step.", Action: ".$actions[$step]."<br />"; }
                        if(!isset($tfile[$line])) { break 2; }
                        if($start==0) { $start=$line; }
                        while(preg_match('/'.$regex.'/mu',$tfile[$line],$matches)) {
                            $match=$matches[0];unset($matches[0]);$done[]=$line;
                            if($rmodes[$step]=="match") {
                                $trash[$step]=$match;$line++;continue 2; // Go to next regex
                            } else {
                                $this->getmatch($db,$debug,$errors,$step,$matches,$setdata,$rmodes[$step],$smodes,$blocks,$line);
                            }
                            // Check if a reference is identified on this line and if so add to other lines
                            if(in_array('reference',$setdata['scidata'][$step])) {
                                $rcount=count($db['references']);
                                $lastref=$db['references'][($rcount-1)];
                                if($lastref['location']['line']==$line) {
                                    // OK we are on a line where a ref was added
                                    $currrefs[$line] = $lastref;
                                } else {
                                    // OK we are on a line that is a repeat for a dataset and does not contain a ref
                                    $lastref['location']['line']=$line;
                                    $db['references'][]=$lastref;
                                }

                            } else {
                                // OK this is a step that does not detect a refcode
                                if(!empty($currrefs)) {
                                    // Find the ref to add to this line
                                    $foundline=0;
                                    foreach($currrefs as $nline=>$nextref) {
                                        if($line>$nline) {
                                            $foundline=$nline;
                                        } else {
                                            break;
                                        }
                                    }
                                    $temp=$currrefs[$foundline];
                                    $temp['location']['line']=$line;
                                    $db['references'][]=$temp;
                                }
                            }
                            $repeat++;
                            // Look ahead
                            if(!isset($tfile[$line+2])) { $line++;break; }
                            // Increment for next match
                            $line++;$line++;
                        }
                        if($repeat==0&&$actions[$step]=="REPEATEVERYOTHERLINE") {
                            $errors['rules'][]=['id'=>'R006','step'=>$step,'line'=>$line,'issue'=>"No line found (Action: REPEATEVERYOTHERLINE)"];
                        } else {
                            $repeat=0;// Reset for other repeats
                        }
                        if($alt==2) {
                            $alt=1;
                            $start=0;
                        } else {
                            $alt++;
                            $line=$start+1;
                        }
                        break;
                    case "REPEATEVERYOTHERLINECHOICE";
                    case "OPTIONALREPEATEVERYOTHERLINECHOICE";
                        if(!is_null($tid)) {  echo "Line: ".$line.", Step: ".$step.", Action: ".$actions[$step]."<br />"; }
                        if(!isset($tfile[$line])) { break 2; }
                        // Assumes there are multiple rules from which one should match
                        if(empty($group)) {
                            for($s=$step;$s<count($actions)+1;$s++) {
                                if(!is_null($tid)) {  echo "GROUP Step: ".$s."<br />"; }
                                if($actions[$s]=="REPEATEVERYOTHERLINECHOICE"||$actions[$s]=="OPTIONALREPEATEVERYOTHERLINECHOICE") {
                                    if(preg_match('/'.$regexes[$s].'/mu',$tfile[$line])) {
                                        $group[$s]=1;
                                    } else {
                                        $group[$s]=0;
                                    }
                                } else {
                                    break;
                                }
                            }
                            $laststep=max(array_keys($group));
                        }
                        //debug($laststep);debug($group);
                        if(!is_null($tid)) { debug($group); }
                        if($group[$step]) {
                            if($start==0) { $start=$line; }
                            $linecount=0;
                            while(preg_match('/'.$regex.'/mu',$tfile[$line],$matches)) {
                                //echo "STARTLINE: ".$line.", STEP: ".$step."<br/>";
                                $match=$matches[0];unset($matches[0]);
                                if(in_array($line,$done)) {
                                    if(!isset($tfile[$line+2])) { $line++;break; }
                                    $line++;$line++;continue;
                                } else {
                                    $done[]=$line;
                                }
                                if($rmodes[$step]=="match") {
                                    $trash[$step]=$match;$line++;continue 2; // Go to next regex
                                } else {
                                    $this->getmatch($db,$debug,$errors,$step,$matches,$setdata,$rmodes[$step],$smodes,$blocks,$line);
                                }
                                //echo "CURRENTLINE: ".$line.", STEP: ".$step."<br/>";
                                // Check if a reference is identified on this line and if so add to other lines
                                if(in_array('reference',$setdata['scidata'][$step])) {
                                    $rcount=count($db['references']);
                                    $lastref=$db['references'][($rcount-1)];
                                    if($lastref['location']['line']==$line) {
                                        // OK we are on a line where a ref was added
                                        $currrefs[$line] = $lastref;
                                    } else {
                                        // OK we are on a line that is a repeat for a series and does not contain a ref
                                        $temp=$lastref;
                                        $temp['location']['line']=$line;
                                        $db['references'][]=$temp;
                                    }

                                } else {
                                    if(!empty($currrefs)) {
                                        // Find the ref to add to this line
                                        $foundline=0;
                                        foreach($currrefs as $nline=>$nextref) {
                                            if($line>$nline) {
                                                $foundline=$nline;
                                            } else {
                                                break;
                                            }
                                        }
                                        $temp=$currrefs[$foundline];

                                        $temp['location']['line']=$line;
                                        $db['references'][]=$temp;
                                    }
                                    // Look ahead one line and see if a previous step has captured data on that line
                                    // Ensures that data match line does not skip over condition line for different dataset
                                    $nextline=$line+1;
                                    if(!in_array($nextline,$done)&&$nextline!=(count($tfile)-1)&&$alt==2) {
                                        //echo "breakout?";
                                        //debug($done);debug($line);
                                        $group=[];$line++;break;
                                    }
                                }
                                $repeat++;
                                // Look ahead two lines
                                if(!isset($tfile[$line+2])||!preg_match('/'.$regex.'/mu',$tfile[$line+2],$matches)) { $line++;break; }
                                // Increment for next match
                                $line++;$line++;
                                //echo "ENDLINE: ".$line.", STEP: ".$step."<br/>";
                            }
                            // Check to see if we have inadvertently skipped a line
                            $prev=$line-1;
                            //echo "PREV: ".$prev."<br/>";
                            if(!in_array($prev,$done)) {
                                $line--;
                            }
                            if($repeat==0&&$actions[$step]=="REPEATEVERYOTHERLINECHOICE") {
                                $errors['rules'][]=['id'=>'R007','step'=>$step,'line'=>$line,'issue'=>"No line found (Action: REPEATEVERYOTHERLINECHOICE)"];
                            } else {
                                $repeat=0;// Reset for other repeats
                            }
                            if($alt==2) {
                                $alt=1;
                                $start=0;
                            } else {
                                $alt++;
                                $line=$start+1;
                            }
                        }
                        if($step==$laststep) {
                            $group=[];
                        }
                        //debug($line);
                        break;
                    case "REPEATWITHOPTIONAL";
                    case "REPEATWITHOPTIONALREPEAT";
                        if(!is_null($tid)) {  echo "Line: ".$line.", Step: ".$step.", Action: ".$actions[$step]."<br />"; }
                        // Assumes repeated single lines with an optional line after each one
                        if(!isset($tfile[$line])) { break 2; }
                        //echo "Line: ".$line.", Step: ".$step.", Action: ".$actions[$step]."<br />";
                        while(preg_match('/'.$regex.'/mu',$tfile[$line],$matches)) {
                            //echo "OUTERLOOP: Line ".$line."<br />";
                            $match=$matches[0];unset($matches[0]);$done[]=$line;
                            if($rmodes[$step]=="match") {
                                $trash[$step]=$match;$line++;continue 2; // Go to next regex
                            } else {
                                $this->getmatch($db,$debug,$errors,$step,$matches,$setdata,$rmodes[$step],$smodes,$blocks,$line);
                            }
                            // Check if a reference is identified on this line and if so add to other lines
                            if(in_array('reference',$setdata['scidata'][$step])) {
                                $rcount=count($db['references']);
                                $lastref=$db['references'][($rcount-1)];
                                if($lastref['location']['line']==$line) {
                                    // OK we are on a line where a ref was added
                                    $currrefs[$line] = $lastref;
                                } else {
                                    // OK we are on a line that is a repeat for a dataset and does not contain a ref
                                    $db['references'][]=$lastref;
                                }

                            } else {
                                if(!empty($currrefs)) {
                                    // Find the ref to add to this line
                                    $foundline=0;
                                    foreach($currrefs as $nline=>$nextref) {
                                        if($line>$nline) {
                                            $foundline=$nline;
                                        } else {
                                            break;
                                        }
                                    }
                                    $temp=$currrefs[$foundline];
                                    $temp['location']['line']=$line;
                                    $db['references'][]=$temp;
                                }
                            }
                            // Check for optional next line
                            // Normally in ruleset as OPTIONALSTEP
                            if($actions[$step]=="REPEATWITHOPTIONAL") {
                                if(!isset($tfile[($line+1)])) { break 2; }
                                if(preg_match('/'.$regexes[($step+1)].'/mu',$tfile[++$line],$matches)) {
                                    //echo "INNERLOOP: Line ".$line.", Step ".$step."<br />";
                                    //echo "<pre>".$tfile[$line]."</pre><br />";
                                    //debug($currrefs);debug($line);debug($setdata['scidata'][$step]);
                                    $match=$matches[0];unset($matches[0]);$done[]=$line;
                                    if($rmodes[$step]=="match") {
                                        $trash[$step]=$match;$line++;continue 2; // Go to next regex
                                    } else {
                                        $this->getmatch($db,$debug,$errors,($step+1),$matches,$setdata,$rmodes[($step+1)],$smodes,$blocks,$line);
                                    }
                                    // Check if a reference is identified on this line and if so add to other lines
                                    if(in_array('reference',$setdata['scidata'][($step+1)])) {
                                        $rcount=count($db['references']);
                                        $lastref=$db['references'][($rcount-1)];
                                        if($lastref['location']['line']==$line) {
                                            // OK we are on a line where a ref was added
                                            $currrefs[$line] = $lastref;
                                        } else {
                                            // OK we are on a line that is a repeat for a dataset and does not contain a ref
                                            $db['references'][]=$lastref;
                                        }

                                    } else {
                                        if(!empty($currrefs)) {
                                            // Find the ref to add to this line
                                            $foundline=0;
                                            foreach($currrefs as $nline=>$nextref) {
                                                if($line>$nline) {
                                                    $foundline=$nline;
                                                } else {
                                                    break;
                                                }
                                            }
                                            $temp=$currrefs[$foundline];
                                            $temp['location']['line']=$line;
                                            $temp['addtoline']=$foundline;
                                            $db['references'][]=$temp;
                                        }
                                    }
                                    $line++; // Move to next line
                                    if(!isset($tfile[$line])) { break 3; }
                                }
                            } elseif($actions[$step]=="REPEATWITHOPTIONALREPEAT") {
                                if(!isset($tfile[($line+1)])) { break 2; }
                                while(preg_match('/'.$regexes[($step+1)].'/mu',$tfile[++$line],$matches)) {
                                    //echo "In REPEATWITHOPTIONALREPEAT: Line ".$line."<br />";
                                    $match=$matches[0];unset($matches[0]); // Remove the full match line
                                    if($rmodes[($step+1)]=="match") {
                                        $trash[($step+1)]=$match;$line++;continue 2; // Go to next regex
                                    } else {
                                        $this->getmatch($db,$debug,$errors,($step+1),$matches,$setdata,$rmodes[($step+1)],$smodes,$blocks,$line);
                                    }
                                    // Check if a (single) condition is identified on this line and if so add to other lines
                                    // TODO: Multiple conditions on one line
                                    if(in_array('condition',$setdata['scidata'][($step+1)])) {
                                        $ccount=count($db['conditions']);
                                        $lastcond=$db['conditions'][($ccount-1)];
                                        if($lastcond['location']['line']==$line) {
                                            // Found value on this line but is it empty?
                                            if(empty($lastcond['value'])) {
                                                $db['conditions'][($ccount-1)]['value']=$currcond;
                                            } else {
                                                // OK save the value of the condition to add to other lines
                                                $currcond=$lastcond['value'];
                                            }
                                        } else {
                                            // OK we are on a line where a condition was not found (e.g. end of line)
                                            $temp=$lastcond;
                                            $temp['location']['line']=$line;
                                            $db['conditions'][]=$temp;
                                        }
                                    }
                                    // Check if a reference is identified on this line and if so add to other lines
                                    if(in_array('reference',$setdata['scidata'][($step+1)])) {
                                        $rcount=count($db['references']);
                                        $lastref=$db['references'][($rcount-1)];
                                        if($lastref['location']['line']==$line) {
                                            // OK we are on a line where a ref was added
                                            $currrefs[$line] = $lastref;
                                        } else {
                                            // OK we are on a line that is a repeat for a dataset and does not contain a ref
                                            if(isset($lastref['addtoline'])) {
                                                $foundline=$lastref['addtoline'];
                                            } else {
                                                $foundline=$lastref['location']['line'];
                                            }
                                            $lastref['location']['line']=$line;
                                            $lastref['addtoline']=$foundline;
                                            $db['references'][]=$lastref;
                                        }

                                    } else {
                                        if(!empty($currrefs)) {
                                            // Find the ref to add to this line
                                            $foundline=0;
                                            foreach($currrefs as $nline=>$nextref) {
                                                if($line>$nline) {
                                                    $foundline=$nline;
                                                } else {
                                                    break;
                                                }
                                            }
                                            $temp=$currrefs[$foundline];
                                            if(isset($lastref['addtoline'])) {
                                                $foundline=$temp['addtoline'];
                                            } else {
                                                $foundline=$temp['location']['line'];
                                            }
                                            $temp['location']['line']=$line;
                                            $temp['addtoline']=$foundline;
                                            $db['references'][]=$temp;
                                        }
                                    }
                                    $repeat2++;
                                    if(!isset($tfile[($line+1)])) { break 4; }
                                }
                                if(!isset($tfile[$line])) { break 3; }
                            }
                            $repeat++;
                            if(!isset($tfile[$line])) { break 2; }
                        }
                        if($repeat==0) {
                            $errors['rules'][]=['id'=>'R009','step'=>$step,'line'=>$line,'issue'=>"No line found (Action: REPEATWITHOPTIONAL)"];
                        } else {
                            $repeat=0;$repeat2=0;// Reset for other repeats
                        }
                        break;
                    case "CHOICEBREAK";
                        break;
                    case "STOP";
                        break 2;
                }
                // If the script skips a line then find it and start from there...
                if((max($done)+1)!=count($done)) {
                    for ($x = 0; $x < max($done); $x++) {
                        if (!in_array($x, $done)) {
                            $line = $x;
                        }
                    }
                }
            }
            if(count($done)==count($tfile)) {
                break;
            } else {
                //debug($done);debug($tfile);
            }
            if(!is_null($tid)) { debug($line);debug($done);debug($db);debug($errors); }
        }
        if(!is_null($tid)) { exit; }
        //if(AuthComponent::user('type') == 'superadmin') { exit; }

        // Return
        $return=['db'=>$db,'debug'=>$debug,'trash'=>$trash,'errors'=>$errors];
        return $return;
    }

    /**
     * Match data
     * @param $db
     * @param $debug
     * @param $errors
     * @param $step
     * @param $matches
     * @param $setdata
     * @param $rmode
     * @param $smodes
     * @param $blocks
     * @param $line
     */
    private function getmatch(&$db,&$debug,&$errors,$step,$matches,$setdata,$rmode,$smodes,$blocks,$line)
    {
        if($rmode=="capture") {
            // Check if the right number of blocks have been captured
            if(count($matches)!=$blocks[$step]) {
                $sopts=$setdata['sopts'][$step];$scounts=array_count_values($sopts);
                if(isset($scounts['no'])&&count($matches)<$scounts['no']) {
                    $errors['rules'][]=['id'=>'R001','step'=>$step,'line'=>$line,'issue'=>"Wrong number of required blocks captured (capture)"];
                }
            }
            foreach($matches as $block=>$capture) {
                // Ignore empty captures where the data is optional
                if($setdata['sopts'][$step][$block]=='yes'&&$capture=='') { continue; }
                // $db/errors is updated by reference in this function (data organized by db type)
                $this->getInfo($db,$errors,$setdata,trim($capture),$step,$line,$block);
                // $debug is updated by reference in this function (data organized by step/line/block
                $this->debug($debug,$setdata,trim($capture),$step,$line,$block);
            }
        } elseif($rmode=="mixed") {
            // Check if the right number of blocks have been captured
            $mcounts=array_count_values($smodes[$step]);
            if(count($matches)!=$mcounts['capture']) {
                // Check # captures is what it should be base on required or not
                // Remove any snippets that are matches
                $sopts=$setdata['sopts'][$step];
                foreach($smodes[$step] as $block=>$mode) {
                    if($mode=='match') {
                        unset($sopts[$block]);
                    }
                }
                $scounts=array_count_values($sopts);
                //debug($sopts);debug($scounts);exit;
                if(isset($scounts['no'])&&count($matches)<$scounts['no']) {
                    $errors['rules'][]=['id'=>'R002','step'=>$step,'line'=>$line,'issue'=>"Wrong number of required blocks captured (mixed)"];
                }
            }
            $mindex=0;
            foreach($smodes[$step] as $block=>$mode) {
                if($mode=="capture") {
                    $mindex++;
                    if(isset($matches[$mindex])) { // If template says to capture but their are empty blocks on the end of the line
                        $capture=$matches[$mindex];
                        // Ignore empty captures where the data is optional
                        if($setdata['sopts'][$step][$block]=='yes'&&$capture=='') { continue; }
                        // $db/errors is updated by reference in this function (data organized by db type)
                        $this->getInfo($db,$errors,$setdata,trim($capture),$step,$line,$block);
                        // $debug is updated by reference in this function (data organized by step/line/block
                        $this->debug($debug,$setdata,trim($capture),$step,$line,$block);
                    }
                }
            }
        }

        // Resort refs as lines may not captured in order
        //echo "BEFORE";
        //debug($db['references']);$temprefs=[];
        //foreach($db['references'] as $r) {
        //    $temprefs[$r['location']['line']-1]=$r;
        //}
        //ksort($temprefs);
        //$db['references']=array_values($temprefs);
        //echo "AFTER";
        //debug($db['references']);

    }

    /**
     * Assemble all the data/metadata for conversion to JSON
     * ($db and $errors updated by reference)
     * @param $db
     * @param $errors
     * @param $setdata
     * @param $capture
     * @param $step
     * @param $line
     * @param $block
     */
    private function getInfo(&$db,&$errors,$setdata,$capture,$step,$line,$block)
    {
        $info=[];
        // Work out column of data if there are multiple on one line...
        if($setdata['cols'][$step]>1) {
            // Get # blocks on this line so we can work out which column they are in
            $bcount=count($setdata['fields'][$step]); // Has to be set for all captured data
            $blockspercol=$bcount/$setdata['cols'][$step];
            if(is_int($blockspercol)) {
                $cols=array_chunk($setdata['fields'][$step],$blockspercol,true);
                foreach($cols as $idx=>$c) {
                    $keys=array_keys($c);
                    if(in_array($block,$keys)) {
                        $col=$idx+1;
                    }
                }
            }
            $info['location']=['step'=>$step,'line'=>str_pad($line,2,'0',STR_PAD_LEFT)."_".$col,'block'=>$block];
        } else {
            $info['location']=['step'=>$step,'line'=>str_pad($line,2,'0',STR_PAD_LEFT),'block'=>$block];
        }
        $info['layout']=$setdata['layouts'][$step];
        if(isset($setdata['fields'][$step][$block])) {
            $info['label']=$setdata['fields'][$step][$block];
        } else {
            debug($step);debug($block);debug($setdata['fields']);exit;
        }
        $info['datatype']=$setdata['datatypes'][$step][$block];
        $info['value']=(string) $capture;
        $optional=$setdata['sopts'][$step][$block];
        if(empty($capture)&&$capture===0) {
            if($optional=="no") {
                $errors['snippets'][]=['id'=>'S001','step'=>$step,'line'=>($line+1),'block'=>$block,'issue'=>"No capture for required '".$info['label']."'"];
            }
        }
        if(isset($setdata['cmpdnums'][$step][$block])) {
            $info['cmpdnum']=(integer) $setdata['cmpdnums'][$step][$block]; // so 1 does not get interpretted as true
        } else {
            $info['cmpdnum']=null;
        }
        if(isset($setdata['properties'][$step][$block])) {
            $info['property']=$setdata['properties'][$step][$block];
        } else {
            $info['property']=null;
        }
        if(isset($setdata['metadata'][$step][$block])) {
            $info['metadata']=$setdata['metadata'][$step][$block];
        } else {
            $info['metadata']=null;
        }
        if(isset($setdata['units'][$step][$block])) {
            $info['unit']=$setdata['units'][$step][$block];
        } else {
            $info['unit']=null;
        }
        $scidata=$setdata['scidata'];
        if($scidata[$step][$block]=="annotation") {
            $db['annotations'][]=$info;
        } elseif($scidata[$step][$block]=="chemical") {
            $db['compounds'][]=$info;
        } elseif($scidata[$step][$block]=="chemprop") {
            $db['properties'][]=$info;
        } elseif($scidata[$step][$block]=="condition") {
            $db['conditions'][]=$info;
        } elseif($scidata[$step][$block]=="data") {
            $db['data'][]=$info;
        } elseif($scidata[$step][$block]=="datafactor") {
            $db['datafactors'][]=$info;
        } elseif($scidata[$step][$block]=="error") {
            $db['errors'][]=$info;
        } elseif($scidata[$step][$block]=="eqndiff") {
            $db['eqndiffs'][]=$info;
        } elseif($scidata[$step][$block]=="eqnoperator") {
            $db['eqnoperators'][]=$info;
        } elseif($scidata[$step][$block]=="eqnprop") {
            $db['eqnprops'][]=$info;
        } elseif($scidata[$step][$block]=="eqnpropunit") {
            $db['eqnpropunits'][]=$info;
        } elseif($scidata[$step][$block]=="eqnterm") {
            $db['eqnterms'][]=$info;
        } elseif($scidata[$step][$block]=="eqnvariable") {
            $db['eqnvariables'][]=$info;
        } elseif($scidata[$step][$block]=="eqnvariablelimit") {
            $db['eqnvariablelimits'][]=$info;
        } elseif($scidata[$step][$block]=="propheader") {
            $db['propheaders'][]=$info;
        } elseif($scidata[$step][$block]=="reference") {
            $db['references'][]=$info;
        } elseif($scidata[$step][$block]=="series") {
            $db['series'][]=$info;
        } elseif($scidata[$step][$block]=="serann") {
            $db['seriesanns'][]=$info;
        } elseif($scidata[$step][$block]=="seriescond") {
            $db['seriesconds'][]=$info;
        } elseif($scidata[$step][$block]=="setting") {
            $db['settings'][]=$info;
        } elseif($scidata[$step][$block]=="suppdata") {
            $db['suppdata'][] = $info;
        }
    }

    /**
     * Organize the info by step/line/block for debugging
     * ($debug is updated by reference)
     * @param $debug
     * @param $setdata
     * @param $capture
     * @param $step
     * @param $line
     * @param $block
     */
    private function debug(&$debug,$setdata,$capture,$step,$line,$block)
    {
        $debug[$step][$line][$block]['label']=$setdata['fields'][$step][$block];
        $debug[$step][$line][$block]['datatype']=$setdata['datatypes'][$step][$block];
        $debug[$step][$line][$block]['scidata']=$setdata['scidata'][$step][$block];
        if(isset($setdata['properties'][$step][$block])) {
            $debug[$step][$line][$block]['property']=$setdata['properties'][$step][$block];
        }
        if(isset($setdata['metadata'][$step][$block])) {
            $debug[$step][$line][$block]['metadata']=$setdata['metadata'][$step][$block];
        }
        $debug[$step][$line][$block]['value']=$capture;
        if(isset($setdata['units'][$step][$block])) {
            $debug[$step][$line][$block]['unit']=$setdata['units'][$step][$block];
        }
    }

}
