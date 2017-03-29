<?php

/**
 * Class TrcfilesController
 * @author Stuart Chalk <schalk@unf.edu>
 *
 */
class TrcfilesController extends AppController
{

    public $uses=['Trcfile','Trcchemical','Trcsampleprop','Identifier','Datarectification','System','Dataset','Condition',
        'Dataseries','Datapoint','Data','Reference','Trcreactionprop','Pubchem.Chemical','Substance','SubstancesSystem'];

    /**
     * function beforeFilter
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * Get current ThermoML files...
     */
    public function load()
    {
        $path=WWW_ROOT.'files'.DS.'trc';
        $maindir=new Folder($path);
        $files=$maindir->tree();
        foreach($files[1] as $file) {
            $xml=simplexml_load_file($file);
            $trc=json_decode(json_encode($xml),true);

            // Grab the chemical info
            $compds=$trc['Compound'];
            if(!isset($trc['Compound'][0])) {
                // Make a single compound into an array
                $trc['Compound']=['0'=>$trc['Compound']];
            }
            foreach($compds as $comp) {
                $name=$comp['sCommonName'];
                $formula=$comp['sFormulaMolec'];
                $purity=$comp['Sample']['purity']['nPurityMass'];
                $puritysf=$comp['Sample']['purity']['nPurityMassDigits'];
                // Get substance id
                // Get CASRN to send to datarefticiation code
                $url="https://cactus.nci.nih.gov/chemical/structure/".$name."/cas/xml";
                $xml=simplexml_load_file($url);
                $cir=json_decode(json_encode($xml),true);
                if(!empty($cir['data'])) {
                    $cas=$cir['data'][0]['item'][0];
                    $search=[0=>['casrn'=>$cas,'name'=>$name,'formula'=>$formula]];
                    $this->Datarectification->checkAndAddSubstances($search,true);
                    $sid=$search[0]['id'];
                } else {
                    $sid=0;
                }

                $data=['Trcchemical'=>['name'=>$name,'formula'=>$formula,'substance_id'=>$sid,'purity'=>$purity,'puritysf'=>$puritysf]];
                $this->Trcchemical->create();
                $this->Trcchemical->save($data);
                debug($comp);exit;
            }

            // Get the properties

            // Grab the data
            //debug($trc);exit;

            // Grab the general info
            if(isset($trc['Citation']['sDOI'])) {
                $url='http://dx.doi.org/'.$trc['Citation']['sDOI'];
            } else {
                $id=$trc['Citation']['TRCRefID'];
                if(is_array($id['sAuthor2'])) {
                    $url=$id['yrYrPub'].$id['sAuthor1'].$id['nAuthorn'];
                } else {
                    $url=$id['yrYrPub'].$id['sAuthor1'].$id['sAuthor2'].$id['nAuthorn'];
                }
            }
            $result=$this->Trcfile->find('first',['conditions'=>['url'=>$url]]);
            if(empty($result)) {
                $title=$trc['Citation']['sTitle'];
                $journal=$trc['Citation']['sPubName'];
                $parts=explode("/",$file);
                $filename=$parts[(count($parts)-1)];
                $props=[];$pnts=0;
                if(isset($trc['PureOrMixtureData'])) {
                    if(!isset($trc['PureOrMixtureData'][0])) {
                        $trc['PureOrMixtureData']=[0=>$trc['PureOrMixtureData']];
                    }
                    foreach($trc['PureOrMixtureData'] as $set) {
                        //debug($set);
                        if(!isset($set['Property'][0])) {
                            $set['Property']=[0=>$set['Property']];
                        }
                        foreach($set['Property'] as $prop) {
                            $group=$prop['Property-MethodID']['PropertyGroup'];
                            if(isset($group['Criticals'])) {
                                $props[]=$group['Criticals']['ePropName'];
                            } elseif(isset($group['VaporPBoilingTAzeotropTandP'])) {
                                $props[]=$group['VaporPBoilingTAzeotropTandP']['ePropName'];
                            } elseif(isset($group['PhaseTransition'])) {
                                $props[]=$group['PhaseTransition']['ePropName'];
                            } elseif(isset($group['CompositionAtPhaseEquilibrium'])) {
                                $props[]=$group['CompositionAtPhaseEquilibrium']['ePropName'];
                            } elseif(isset($group['ActivityFugacityOsmoticProp'])) {
                                $props[]=$group['ActivityFugacityOsmoticProp']['ePropName'];
                            } elseif(isset($group['VolumetricProp'])) {
                                $props[]=$group['VolumetricProp']['ePropName'];
                            } elseif(isset($group['HeatCapacityAndDerivedProp'])) {
                                $props[]=$group['HeatCapacityAndDerivedProp']['ePropName'];
                            } elseif(isset($group['ExcessPartialApparentEnergyProp'])) {
                                $props[]=$group['ExcessPartialApparentEnergyProp']['ePropName'];
                            } elseif(isset($group['TransportProp'])) {
                                $props[]=$group['TransportProp']['ePropName'];
                            } elseif(isset($group['RefractionSurfaceTensionSoundSpeed'])) {
                                $props[]=$group['RefractionSurfaceTensionSoundSpeed']['ePropName'];
                            } elseif(isset($group['BioProperties'])) {
                                $props[]=$group['BioProperties']['ePropName'];
                            }
                        }
                        $pnts+=count($set['NumValues']);
                    }
                } elseif(isset($trc['ReactionData'])) {
                    if(!isset($trc['ReactionData'][0])) {
                        $trc['ReactionData']=[0=>$trc['ReactionData']];
                    }
                    foreach($trc['ReactionData'] as $set) {
                        //debug($set);
                        if(!isset($set['Property'][0])) {
                            $set['Property']=[0=>$set['Property']];
                        }
                        foreach($set['Property'] as $prop) {
                            $group=$prop['Property-MethodID']['PropertyGroup'];
                            if(isset($group['ReactionStateChangeProp'])) {
                                $props[]=$group['ReactionStateChangeProp']['ePropName'];
                            } elseif(isset($group['ReactionEquilibriumProp'])) {
                                $props[]=$group['ReactionEquilibriumProp']['ePropName'];
                            }
                        }
                        $pnts+=count($set['NumValues']);
                    }
                }
                $props=array_unique($props);
                $props=array_values($props);
                $data=['Trcfile'=>['title'=>$title,'url'=>$url,'filename'=>$filename,'properties'=>json_encode($props),'datapoints'=>$pnts,'journal'=>$journal]];
                $this->Trcfile->create();
                $this->Trcfile->save($data);
                echo "Added: ".$url."<br />";
            } else {
                echo "Already added: ".$url."<br />";
            }
        }
        exit;
    }

    /**
     * Add TRC data
     * @param $test
     */
    public function getdata($test=0)
    {
        $path = WWW_ROOT . 'files' . DS . 'trc';
        $maindir = new Folder($path);
        $files = $maindir->tree();
        if($test) {
            $files[1]=[0=>'/usr/local/www/apache24/data/tatum/app/webroot/files/trc/jced/acs.jced.5b00021.xml'];
        }
        $count=0;
        foreach ($files[1] as $file) {
            $xml = simplexml_load_file($file);
            $trc = json_decode(json_encode($xml), true);
            // Get trcfile_id
            if (isset($trc['Citation']['sDOI'])) {
                $url = 'http://dx.doi.org/' . $trc['Citation']['sDOI'];
            } else {
                $id = $trc['Citation']['TRCRefID'];
                if (is_array($id['sAuthor2'])) {
                    $url = $id['yrYrPub'] . $id['sAuthor1'] . $id['nAuthorn'];
                } else {
                    $url = $id['yrYrPub'] . $id['sAuthor1'] . $id['sAuthor2'] . $id['nAuthorn'];
                }
            }
            $res = $this->Trcfile->find('list', ['fields' => ['url', 'id'], 'conditions' => ['url' => $url]]);
            $trcfileid = $res[$url];

            // Check to see of this file has been done
            $res=$this->Trcchemical->find('list',['conditions'=>['trcfile_id'=>$trcfileid]]);
            if(empty($res)) {
                // Grab the chemical info
                $compds = $trc['Compound'];
                $sids = $names = [];
                if (!isset($compds[0])) {
                    // Make a single compound into an array
                    $compds = ['0' => $compds];
                }
                foreach ($compds as $comp) {
                    $number = $comp['RegNum']['nOrgNum'];
                    $name = $comp['sCommonName'];
                    $formula = $comp['sFormulaMolec'];
                    if (isset($comp['Sample'])) {
                        if(isset($comp['Sample']['eSource'])) {
                            $source = $comp['Sample']['eSource'];
                        } else {
                            $source = null;
                        }
                        if(isset($comp['Sample']['purity'])) {
                            if(!isset($comp['Sample']['purity'][0])) {
                                $comp['Sample']['purity']=[0=>$comp['Sample']['purity']];
                            }
                            if(isset($comp['Sample']['purity'])) {
                                $spurities=$comp['Sample']['purity'];$analmeth=$purimeth=[];
                                if(!isset($spurities[0])) { $spurities=[0=>$spurities]; }
                                foreach($spurities as $p) {
                                    if(isset($p['nPurityMass'])) {
                                        $purity = $p['nPurityMass'];
                                        $puritysf = $p['nPurityMassDigits'];
                                        $purityunit=20;
                                    } elseif(isset($p['nPurityMol'])) {
                                        $purity = $p['nPurityMol'];
                                        $puritysf = $p['nPurityMolDigits'];
                                        $purityunit=75;
                                    } elseif(isset($p['nPurityVol'])) {
                                        $purity = $p['nPurityVol'];
                                        $puritysf = $p['nPurityVolDigits'];
                                        $purityunit=76;
                                    } elseif(isset($p['nUnknownPerCent'])) {
                                        $purity = $p['nUnknownPerCent'];
                                        $puritysf = $p['nUnknownPerCentDigits'];
                                        $purityunit=77;
                                    } elseif(isset($p['nWaterMassPerCent'])) {
                                        $purity = $p['nWaterMassPerCent'];
                                        $puritysf = $p['nWaterMassPerCentDigits'];
                                        $purityunit=20;
                                    } elseif(isset($p['nWaterMolPerCent'])) {
                                        $purity = $p['nWaterMolPerCent'];
                                        $puritysf = $p['nWaterMolPerCentDigits'];
                                        $purityunit=75;
                                    } elseif(isset($p['nHalideMolPerCent'])) {
                                        $purity = $p['nHalideMolPerCent'];
                                        $puritysf = $p['nHalideMolPerCentDigits'];
                                        $purityunit=75;
                                    } elseif(isset($p['nHalideMassPerCent'])) {
                                        $purity = $p['nHalideMassPerCent'];
                                        $puritysf = $p['nHalideMassPerCentDigits'];
                                        $purityunit=20;
                                    } else {
                                        $purity = null;
                                        $puritysf = null;
                                        $purityunit = null;
                                    }
                                    if(isset($p['eAnalMeth'])) {
                                        if(is_array($p['eAnalMeth'])) {
                                            $analmeth=array_merge($purimeth,$p['eAnalMeth']);
                                        } else {
                                            $analmeth[] = $p['eAnalMeth'];
                                        }
                                    } elseif(isset($p['sAnalMeth'])) {
                                        if(is_array($p['sAnalMeth'])) {
                                            $analmeth=array_merge($purimeth,$p['sAnalMeth']);
                                        } else {
                                            $analmeth[] = $p['sAnalMeth'];
                                        }
                                    }
                                    if(isset($p['ePurifMethod'])) {
                                        if(is_array($p['ePurifMethod'])) {
                                            $purimeth=array_merge($purimeth,$p['ePurifMethod']);
                                        } else {
                                            $purimeth[] = $p['ePurifMethod'];
                                        }
                                    } elseif(isset($p['sPurifMethod'])) {
                                        if(is_array($p['sPurifMethod'])) {
                                            $purimeth=array_merge($purimeth,$p['sPurifMethod']);
                                        } else {
                                            $purimeth[] = $p['sPurifMethod'];
                                        }
                                    }
                                }
                            } else {
                                $purity = null;
                                $puritysf = null;
                                $purityunit = null;
                                $analmeth = null;
                                $purimeth = null;
                            }
                        }
                    } else {
                        $source = null;
                        $purity = null;
                        $puritysf = null;
                        $purityunit = null;
                        $analmeth = null;
                        $purimeth = null;
                    }
                    if(is_array($analmeth)) {
                        if(!empty($analmeth)) {
                            if(count($analmeth)==1) {
                                $analmeth=$analmeth[0];
                            } else {
                                $analmeth=json_encode($analmeth);
                            }
                            $analmeth=json_encode($analmeth);
                        } else {
                            $analmeth = null;
                        }
                    }

                    if(is_array($purimeth)) {
                        if(empty($purimeth)) {
                            $purimeth = null;
                        } elseif(count($purimeth)==1) {
                            $purimeth=$purimeth[0];
                        } else {
                            $purimeth = json_encode($purimeth);
                        }
                    }

                    // Get substance id
                    // Get CASRN to send to datarectificiation code
                    $url = "https://cactus.nci.nih.gov/chemical/structure/" . $name . "/cas/xml";
                    $xml = simplexml_load_file($url);
                    $cir = json_decode(json_encode($xml), true);
                    $cas=$this->Chemical->getcas($name);
                    if (!empty($cir['data'])) {
                        if (!isset($cir['data'][0])) {
                            $cir['data'] = [0 => $cir['data']];
                        }
                        if(!isset($cir['data'][0]['item'][0])) {
                            $cir['data'][0]['item'] = [0 => $cir['data'][0]['item']];
                        }
                        $cas = $cir['data'][0]['item'][0];
                        $search = [0 => ['casrn' => $cas, 'name' => $name, 'formula' => $formula]];
                        $this->Datarectification->checkAndAddSubstances($search, true);
                        $sid = $search[0]['id'];
                    } elseif($cas) {
                        $search = [0 => ['casrn' => $cas, 'name' => $name, 'formula' => $formula]];
                        $this->Datarectification->checkAndAddSubstances($search, true);
                        $sid = $search[0]['id'];
                    } else {
                        $temp=['Substance'=>['name'=>$name,'formula'=>$formula,'casrn'=>null]];
                        $this->Substance->create();
                        $this->Substance->save($temp);
                        $sid = $this->Substance->id;
                    }
                    $sids[$number] = $sid;
                    $names[$number] = $name;
                    // Added already?
                    $res = $this->Trcchemical->find('first', ['conditions' => ['trcfile_id' => $trcfileid, 'number' => $number]]);
                    if (empty(($res))) {
                        $temp = ['Trcchemical' => ['trcfile_id' => $trcfileid, 'number' => $number, 'name' => $name, 'source' => $source, 'formula' => $formula, 'substance_id' => $sid, 'analmethod' => $analmeth, 'purimethod' => $purimeth, 'purity' => $purity, 'puritysf' => $puritysf,'purityunit_id'=>$purityunit]];
                        $this->Trcchemical->create();
                        $this->Trcchemical->save($temp);
                    }
                }

                // Props and data
                if (isset($trc['PureOrMixtureData'])) {
                    $datasets = $trc['PureOrMixtureData'];
                    if(!isset($datasets[0])) { $datasets=[0=>$datasets]; }
                    foreach ($datasets as $set) {
                        // Components
                        $coms = $set['Component'];
                        if (!isset($coms[0])) { $coms = ['0' => $coms]; }
                        $cids = [];$cnames = [];
                        foreach ($coms as $com) {
                            $org = $com['RegNum']['nOrgNum'];
                            $cids[] = $sids[$org];
                            $cnames[] = $names[$org];
                        }

                        // Add system
                        sort($cids);
                        if(count($cids)==1) {
                            $idstr=$cids[0];
                        } else {
                            $idstr = implode(":", $cids);
                        }
                        $sysid = $this->getsysid($idstr, $cnames);

                        // Create dataset
                        $temp = ['Dataset' => ['title' => 'Data from ' . $trcfileid, 'trcfile_id' => $trcfileid, 'system_id' => $sysid]];
                        $this->Dataset->create();
                        $this->Dataset->save($temp);
                        $dsid = $this->Dataset->id;

                        // Create dataseries
                        $temp = ['Dataseries' => ['dataset_id' => $dsid, 'type' => 'independent set']];
                        $this->Dataseries->create();
                        $this->Dataseries->save($temp);
                        $serid = $this->Dataseries->id;

                        // Get the properties
                        $props = $set['Property'];
                        $proparray = [];
                        if (!isset($props[0])) { $props = ['0' => $props]; }
                        foreach ($props as $prop) {
                            $number = $prop['nPropNumber'];
                            $group = $prop['Property-MethodID']['PropertyGroup'];
                            if (isset($group['Criticals'])) {
                                $proptype = $group['Criticals'];
                                $propgroup = 'Criticals';
                            } elseif (isset($group['VaporPBoilingTAzeotropTandP'])) {
                                $proptype = $group['VaporPBoilingTAzeotropTandP'];
                                $propgroup = 'VaporPBoilingTAzeotropTandP';
                            } elseif (isset($group['PhaseTransition'])) {
                                $proptype = $group['PhaseTransition'];
                                $propgroup = 'PhaseTransition';
                            } elseif (isset($group['CompositionAtPhaseEquilibrium'])) {
                                $proptype = $group['CompositionAtPhaseEquilibrium'];
                                $propgroup = 'CompositionAtPhaseEquilibrium';
                            } elseif (isset($group['ActivityFugacityOsmoticProp'])) {
                                $proptype = $group['ActivityFugacityOsmoticProp'];
                                $propgroup = 'ActivityFugacityOsmoticProp';
                            } elseif (isset($group['VolumetricProp'])) {
                                $proptype = $group['VolumetricProp'];
                                $propgroup = 'VolumetricProp';
                            } elseif (isset($group['HeatCapacityAndDerivedProp'])) {
                                $proptype = $group['HeatCapacityAndDerivedProp'];
                                $propgroup = 'HeatCapacityAndDerivedProp';
                            } elseif (isset($group['ExcessPartialApparentEnergyProp'])) {
                                $proptype = $group['ExcessPartialApparentEnergyProp'];
                                $propgroup = 'ExcessPartialApparentEnergyProp';
                            } elseif (isset($group['TransportProp'])) {
                                $proptype = $group['TransportProp'];
                                $propgroup = 'TransportProp';
                            } elseif (isset($group['RefractionSurfaceTensionSoundSpeed'])) {
                                $proptype = $group['RefractionSurfaceTensionSoundSpeed'];
                                $propgroup = 'RefractionSurfaceTensionSoundSpeed';
                            } elseif (isset($group['BioProperties'])) {
                                $proptype = $group['BioProperties'];
                                $propgroup = 'BioProperties';
                            }
                            $propname = $proptype['ePropName'];
                            $unitid = $this->getunit($propname);
                            $methname = $proptype['sMethodName'];
                            $phase = $prop['PropPhaseID']['ePropPhase'];
                            $pres = $prop['ePresentation'];
                            if(isset($prop['Solvent'])) {
                                $solvents = $prop['Solvent']['RegNum'];
                                if(isset($solvents[0])) {
                                    $temp=[];
                                    foreach($solvents as $s) {
                                        $temp[]=$s['nOrgNum'];
                                    }
                                    $solvent=json_encode($temp);
                                } else {
                                    $solvent=$solvents['nOrgNum'];
                                }
                            } else {
                                $solvent = null;
                            }
                            if(isset($prop['PropUncertainty']['nUncertAssessNum'])) {
                                $uncassnum = $prop['PropUncertainty']['nUncertAssessNum'];
                            } else {
                                $uncassnum=null;
                            }
                            if(isset($prop['PropUncertainty']['sUncertEvaluator'])) {
                                $unceval = $prop['PropUncertainty']['sUncertEvaluator'];
                            } else {
                                $unceval=null;
                            }

                            $temp = ["Trcsampleprop" => ['dataset_id' => $dsid, 'number' => $number, 'property_group' => $propgroup, 'property_name' => $propname,
                                'method_name' => $methname, 'phase' => $phase, 'presentation' => $pres, 'solventcmpnum' => $solvent,
                                'uncassessnum' => $uncassnum, 'uncevaluator' => $unceval]];
                            $this->Trcsampleprop->create();
                            $this->Trcsampleprop->save($temp);
                            $propid = $this->Trcsampleprop->id;
                            //$propid=0;
                            $proparray[$number] = $propid . ":" . $unitid;
                        }

                        // Series conditions
                        if(isset($set['Constraint'])) {
                            $serconds = $set['Constraint'];
                            if (!isset($serconds[0])) {
                                $serconds = [0 => $serconds];
                            }
                            foreach ($serconds as $sercond) {
                                $ctype = $sercond['ConstraintID']['ConstraintType'];
                                $res = $this->getpropunit($ctype);
                                list($propname, $unitid) = explode(":", $res);
                                $number = $sercond['nConstraintValue'];
                                $sf = $sercond['nConstrDigits'];
                                $temp = ['Condition' => ['dataseries_id' => $serid, 'property_name' => $propname, 'number' => $number, 'unit_id' => $unitid, 'accuracy' => $sf]];
                                $this->Condition->create();
                                $this->Condition->save($temp);
                            }
                        }

                        // Conditions
                        if(isset($set['Variable'])) {
                            $conds = $set['Variable'];
                            $condarray = [];
                            if (!isset($conds[0])) { $conds = [0 => $conds]; }
                            foreach ($conds as $cond) {
                                $temp = [];
                                $ctype = $cond['VariableID']['VariableType'];
                                $ctype = str_replace(":"," -",$ctype); // so : chunk works
                                $res = $this->getpropunit($ctype);
                                //debug($res);
                                list($propname, $unitid) = explode(":", $res);
                                $temp['propname'] = $propname;
                                $temp['unitid'] = $unitid;
                                $comps = $cnames = [];
                                if (isset($cond['VariableID']['RegNum'])) {
                                    // This is a solution based property and we need to get system info
                                    $solutes = $cond['VariableID']['RegNum'];
                                    if(!isset($solutes[0])) { $solutes=[0=>$solutes]; }
                                    foreach($solutes as $solute) {
                                        $comps[] = $sids[$solute['nOrgNum']];
                                        $cnames[] = $names[$solute['nOrgNum']];
                                    }
                                    if(isset($cond['Solvent'])) {
                                        $solvents = $cond['Solvent']['RegNum'];
                                        if(!isset($solvents[0])) { $solvents=[0=>$solvents]; }
                                        foreach($solvents as $solvent) {
                                            $comps[] = $sids[$solvent['nOrgNum']];
                                            $cnames[] = $names[$solvent['nOrgNum']];
                                        }
                                    }
                                    sort($comps);
                                    if(count($comps)==1) {
                                        $idstr=$comps[0];
                                    } else {
                                        $idstr = implode(":", $comps);
                                    }
                                    $temp['sysid'] = $this->getsysid($idstr, $cnames);
                                } else {
                                    $temp['sysid'] = null;
                                }
                                $condarray[$cond['nVarNumber']] = $temp;
                            }
                        } else {
                            $condarray = [];
                        }

                        // Grab the data
                        $data = $set['NumValues'];
                        if (!isset($data[0])) { $data = [0 => $data]; }
                        foreach ($data as $idx => $datum) {
                            // Add datapoint
                            $temp = ['Datapoint' => ['dataseries_id' => $serid, 'row_index' => ($idx + 1)]];
                            $this->Datapoint->create();
                            $this->Datapoint->save($temp);
                            $pntid = $this->Datapoint->id;
                            //$pntid=0;

                            // Add conditions
                            if(isset($datum['VariableValue'])) {
                                $conds = $datum['VariableValue'];
                                if (!isset($conds[0])) { $conds = [0 => $conds]; }
                                foreach ($conds as $cond) {
                                    $prop = $condarray[$cond['nVarNumber']];
                                    $temp = ['Condition' => ['datapoint_id' => $pntid, 'property_name' => $prop['propname'], 'number' => $cond['nVarValue'], 'system_id' => $prop['sysid'], 'unit_id' => $prop['unitid'], 'accuracy' => $cond['nVarDigits']]];
                                    $this->Condition->create();
                                    $this->Condition->save($temp);
                                }
                            }
                            // Add data
                            $edata = $datum['PropertyValue'];
                            if (!isset($edata[0])) { $edata = [0 => $edata]; }
                            foreach ($edata as $edatum) {
                                $propunit = $proparray[$edatum['nPropNumber']];
                                list($trcpropid, $unitid) = explode(":", $propunit);
                                $number = $edatum['nPropValue'];
                                $acc = $edatum['nPropDigits'];
                                if(isset($edatum['PropUncertainty']['nStdUncertValue'])) {
                                    $err = $edatum['PropUncertainty']['nStdUncertValue'];
                                } else {
                                    $err=null;
                                }
                                $temp = ['Data' => ['datapoint_id' => $pntid, 'trcsampleprop_id' => $trcpropid, 'number' => $number, 'unit_id' => $unitid, 'error' => $err, 'accuracy' => $acc]];
                                $this->Data->create();
                                $this->Data->save($temp);
                            }
                        }
                    }
                }

                if (isset($trc['ReactionData'])) {
                    $datasets = $trc['ReactionData'];
                    if(!isset($datasets[0])) { $datasets=[0=>$datasets]; }
                    foreach ($datasets as $set) {
                        // Components
                        $coms = $set['Participant'];
                        $cids = [];
                        $cnames = [];$reaction=[];
                        foreach ($coms as $com) {
                            $org = $com['RegNum']['nOrgNum'];
                            $cids[] = $sids[$org];
                            $cnames[] = $names[$org];
                            if(isset($com['nSampleNm'])) {
                                $number=$com['nSampleNm'];
                            } else {
                                $number=null;
                            }
                            $coef=$com['nStoichiometricCoef'];
                            $phase=$com['ePhase'];
                            $temp=['number'=>$number,'stoichcoef'=>$coef,'phase'=>$phase];
                            $reaction[]=$temp;
                        }

                        // Add system
                        sort($cids);
                        if(count($cids)==1) {
                            $idstr=$sids[0];
                        } else {
                            $idstr=implode(":", $cids);
                        }
                        $sysid = $this->getsysid($idstr, $cnames);

                        // Create dataset
                        $temp = ['Dataset' => ['title' => 'Data from ' . $trcfileid, 'trcfile_id' => $trcfileid, 'sys_id' => $sysid]];
                        $this->Dataset->create();
                        $this->Dataset->save($temp);
                        $dsid = $this->Dataset->id;

                        // Create dataseries
                        $temp = ['Dataseries' => ['dataset_id' => $dsid, 'type' => 'independent set']];
                        $this->Dataseries->create();
                        $this->Dataseries->save($temp);
                        $serid = $this->Dataseries->id;

                        // Get the properties
                        $props = $set['Property'];
                        $proparray = [];
                        if (!isset($props[0])) {
                            // Make a single compound into an array
                            $props = ['0' => $props];
                        }
                        foreach ($props as $prop) {
                            $number = $prop['nPropNumber'];
                            $group = $prop['Property-MethodID']['PropertyGroup'];
                            if (isset($group['ReactionStateChangeProp'])) {
                                $proptype = $group['ReactionStateChangeProp'];
                                $propgroup = 'ReactionStateChangeProp';
                            } elseif (isset($group['ReactionEquilibriumProp'])) {
                                $proptype = $group['ReactionEquilibriumProp'];
                                $propgroup = 'ReactionEquilibriumProp';
                            }
                            $propname = $proptype['ePropName'];
                            $unitid = $this->getunit($propname);
                            $methname = $proptype['sMethodName'];
                            $conditions = []; // Reaction conditions...
                            (isset($prop['Solvent'])) ? $solvent = $prop['Solvent'] : $solvent = null;
                            (isset($prop['Catalyst'])) ? $catalyst = $prop['Catalyst'] : $catalyst = null;
                            (isset($prop['eStandardState'])) ? $standardstate = $prop['eStandardState'] : $standardstate = null;
                            if (isset($prop['nTemperature-K'])) {
                                $temp = ['prop' => 'temperature', 'value' => $prop['nTemperature-K'], 'unitid' => 5, 'accuracy' => $prop['nTemperatureDigits']];
                                $conditions[] = $temp;
                            }
                            if (isset($prop['nPressure-kPa'])) {
                                $temp = ['prop' => 'pressure', 'value' => $prop['nPressure-kPa'], 'unitid' => 25, 'accuracy' => $prop['nPressureDigits']];
                                $conditions[] = $temp;
                            }
                            (isset($prop['PropDeviceSpec']['eDeviceSpecMethod'])) ? $specmethod = $prop['PropDeviceSpec']['eDeviceSpecMethod'] : $specmethod = null;

                            $temp = ["Trcreactionprop" => ['dataset_id' => $dsid, 'number' => $number, 'property_group' => $propgroup, 'property_name' => $propname,
                                'method_name' => $methname, 'reaction' => json_encode($reaction), 'solvent' => $solvent, 'catalyst' => $catalyst,
                                'standardstate' => $standardstate, 'devicespecmethod' => $specmethod]];
                            $this->Trcreactionprop->create();
                            $this->Trcreactionprop->save($temp);
                            $propid = $this->Trcreactionprop->id;
                            //$propid=0;
                            $proparray[$number] = $propid . ":" . $unitid;
                        }

                        // Series conditions
                        if(isset($set['Constraint'])) {
                            $serconds = $set['Constraint'];
                            if (!isset($serconds[0])) {
                                $serconds = [0 => $serconds];
                            }
                            foreach ($serconds as $sercond) {
                                $ctype = $sercond['ConstraintID']['ConstraintType'];
                                $res = $this->getpropunit($ctype);
                                list($propname, $unitid) = explode(":", $res);
                                $number = $sercond['nConstraintValue'];
                                $sf = $sercond['nConstrDigits'];
                                $temp = ['Condition' => ['dataseries_id' => $serid, 'property_name' => $propname, 'number' => $number, 'unit_id' => $unitid, 'accuracy' => $sf]];
                                $this->Condition->create();
                                $this->Condition->save($temp);
                            }
                        }

                        // Grab the data
                        $data = $set['NumValues'];
                        if (!isset($data[0])) {
                            $data = [0 => $data];
                        }
                        foreach ($data as $idx => $datum) {
                            // Add datapoint
                            $temp = ['Datapoint' => ['dataseries_id' => $serid, 'row_index' => ($idx + 1)]];
                            $this->Datapoint->create();
                            $this->Datapoint->save($temp);
                            $pntid = $this->Datapoint->id;
                            //$pntid=0;

                            // Add conditions
                            foreach ($conditions as $cond) {
                                $temp = ['Condition' => ['datapoint_id' => $pntid, 'property_name' => $cond['prop'], 'number' => $cond['value'], 'unit_id' => $cond['unitid'], 'accuracy' => $cond['accuracy']]];
                                $this->Condition->create();
                                $this->Condition->save($temp);
                            }

                            // Add data
                            $edata = $datum['PropertyValue'];
                            if (!isset($edata[0])) {
                                $edata = [0 => $edata];
                            }
                            foreach ($edata as $edatum) {
                                $propunit = $proparray[$edatum['nPropNumber']];
                                list($trcpropid, $unitid) = explode(":", $propunit);
                                $number = $edatum['nPropValue'];
                                $acc = $edatum['nPropDigits'];
                                if(isset($edatum['PropRepeatability'])) {
                                    $err = $edatum['PropRepeatability']['nPropRepeatValue'];
                                } else {
                                    $err = null;
                                }
                                $temp = ['Data' => ['datapoint_id' => $pntid, 'trcreactionprop_id' => $trcpropid, 'number' => $number, 'unit_id' => $unitid, 'error' => $err, 'accuracy' => $acc]];
                                $this->Data->create();
                                $this->Data->save($temp);
                            }
                        }
                    }
                }

                echo "File ".$file." added<br />";
                if($count==50) {
                    exit;
                } else {
                    $count++;
                }
            }
        }
    }

    /**
     * Test function
     */
    public function test()
    {
        $path=WWW_ROOT.'files'.DS.'trc';
        $maindir=new Folder($path);
        $files=$maindir->tree();
        foreach($files[1] as $file) {
            $xml=simplexml_load_file($file);
            $trc=json_decode(json_encode($xml),true);
            $parts=explode("/",$file);
            $filename=$parts[(count($parts)-1)];
            $res=$this->Trcfile->find('first',['conditions'=>['filename'=>$filename]]);
            $this->Trcfile->id=$res['Trcfile']['id'];
            $this->Trcfile->saveField('date',$trc['Citation']['dateCit']);
            $this->Trcfile->saveField('abstract',$trc['Citation']['sAbstract']);
            echo "Done ".$file."<br />";
        }
        exit;
    }

    /**
     * Get the refs for the TRC files
     */
    public function getrefs()
    {
        $refs = $this->Trcfile->find('list', ['fields' => ['id', 'doi'],'conditions'=>['reference_id'=>0]]);
        foreach($refs as $trcid=>$doi) {
            $meta=$this->Reference->addbydoi($doi);
            debug($meta);
            $this->Trcfile->id=$trcid;
            $this->Trcfile->savefield('reference_id',$meta['id']);
        }
        exit;
    }

    /**
     * Get the property and unit
     * @param $ctype
     * @return string
     */
    private function getpropunit($ctype)
    {
        //debug($ctype);
        if (isset($ctype['eTemperature'])) {
            $propname = $ctype['eTemperature'];
        } elseif (isset($ctype['ePressure'])) {
            $propname = $ctype['ePressure'];
        } elseif (isset($ctype['eComponentComposition'])) {
            $propname = $ctype['eComponentComposition'];
        } elseif (isset($ctype['eSolventComposition'])) {
            $propname = $ctype['eSolventComposition'];
        } elseif (isset($ctype['eMiscellaneous'])) {
            $propname = $ctype['eMiscellaneous'];
        } elseif (isset($ctype['eBioVariables'])) {
            $propname = $ctype['eBioVariables'];
        } elseif (isset($ctype['eParticipantAmount'])) {
            $propname = $ctype['eParticipantAmount'];
        }
        $propname=str_replace(":"," -",$propname);
        $unitid=$this->getunit($propname);
        list($propname,) = explode(",", $propname);
        return $propname.":".$unitid;
    }

    /**
     * Get the unit
     * @param $str
     * @return int
     */
    private function getunit($str)
    {
        //debug($str);
        if (preg_match('/, mol\/kg$/',$str)) {
            $unitid = 53;
        } elseif (preg_match('/, mol\/dm3$/',$str)) {
            $unitid = 21;
        } elseif (preg_match('/, kPa$/',$str)) {
            $unitid = 25;
        } elseif (preg_match('/, K$/',$str)) {
            $unitid = 5;
        } elseif (preg_match('/, kg\/m3$/',$str)) {
            $unitid = 32;
        } elseif (preg_match('/, nm$/',$str)) {
            $unitid = 26;
        } elseif (preg_match('/, MHz$/',$str)) {
            $unitid = 67;
        } elseif (preg_match('/, m3\/mol$/',$str)) {
            $unitid = 30;
        } elseif (preg_match('/, m3\/kg$/',$str)) {
            $unitid = 68;
        } elseif (preg_match('/, mol\/m3$/',$str)) {
            $unitid = 69;
        } elseif (preg_match('/, J\/K\/mol$/',$str)) {
            $unitid = 70;
        } elseif (preg_match('/, mol$/',$str)) {
            $unitid = 6;
        } elseif (preg_match('/, kg$/',$str)) {
            $unitid = 71;
        } elseif (preg_match('/, kJ\/mol$/',$str)) {
            $unitid = 35;
        } elseif (preg_match('/, kJ$/',$str)) {
            $unitid = 72;
        } elseif (preg_match('/, J\/g$/',$str)) {
            $unitid = 73;
        } elseif (preg_match('/, V$/',$str)) {
            $unitid = 74;
        } elseif (preg_match('/, \(mol\/kg\)^n$/',$str)) {
            $unitid = 53;
        } elseif (preg_match('/, \(mol\/dm3\)^n$/',$str)) {
            $unitid = 21;
        } elseif (preg_match('/, kPa^n$/',$str)) {
            $unitid = 25;
        } elseif (preg_match('/, m\/s$/',$str)) {
            $unitid = 31;
        } else {
            $unitid = 17;
        }

        return $unitid;
    }

    /**
     * Get the systemid
     * @param $idstr
     * @param $cnames
     * @return mixed
     */
    private function getsysid($idstr,$cnames)
    {
        $res = $this->System->find('list', ['fields' => ['identifier', 'id'], 'conditions' => ['identifier' => $idstr]]);
        if (empty($res)) {
            $scount=count($cnames);
            switch ($scount) {
                case 1:
                    $comp = 'pure compound';
                    break;
                case 2:
                    $comp = 'binary mixture';
                    break;
                case 3:
                    $comp = 'ternary mixture';
                    break;
                case 4:
                    $comp = 'quaternary mixture';
                    break;
                case 5:
                    $comp = 'quinternary mixture';
                    break;
                default:
                    $comp = null;
            }
            $name = implode("-", $cnames);
            $temp = ['System' => ['name' => $name, 'composition' => $comp, 'identifier' => $idstr]];
            $this->System->create();
            $this->System->save($temp);
            $sysid = $this->System->id;
            // Add substances/system entries
            if(stristr($idstr,":")) {
                $sids=explode(":",$idstr);
            } else {
                $sids=[0=>$idstr];
            }
            foreach($sids as $sid) {
                $this->SubstancesSystem->create();
                $this->SubstancesSystem->save(['SubstancesSystem'=>['substance_id'=>$sid,'system_id'=>$sysid]]);
            }
        } else {
            $sysid = $res[$idstr];
        }
        return $sysid;
    }

    /**
     * View file (in scidata format)
     * @param $id
     */
    public function view($id)
    {
        $c=['Trcchemical'=>[
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
                    'Substance'=>['fields'=>['name','formula','molweight'],
                        'Identifier'=>['fields'=>['type','value'],'conditions'=>['type'=>['inchi','inchikey','iupacname']]]]
                ]
            ]
        ];
        $data=$this->Trcfile->find('first',['conditions'=>['Trcfile.id'=>$id],'contain'=>$c,'recursive'=>-1]);
        $file=$data['Trcfile'];
        $ref=$data['Reference'];
        $chems=$data['Trcchemical'];
        $sets=$data['Dataset'];

        // Base
        $base="https://chalk.coas.unf.edu/trc/datasets/scidata/".$id."/";

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
        $json['uid']="trc:dataset:".$id;
        $json['title']=$file['title'];
        if($ref['authors']!=null) {
            if(stristr($ref['authors'],'[{')) {
                $authors=json_decode($ref['authors'],true);
            } else {
                $authors=explode(", ",$ref['authors']);
            }
            $acount=1;
            foreach ($authors as $au) {
                $json['author'][]=['@id'=>'author/'.$acount,'@type'=>'dc:creator','name'=>$au];
                $acount++;
            }
        }
        $json['description']="Report of termochemical data in ThermoML format via the NIST TRC website http://www.trc.nist.gov/ThermoML/";
        if(stristr($file['doi'],'10.1007')) {
            $json['publisher']='Springer Nature';
        } elseif(stristr($file['doi'],'10.1016')) {
            $json['publisher']='Elsevier B.V.';
        } elseif(stristr($file['doi'],'10.1021')) {
            $json['publisher']='ACS Publications';
        }
        $json['startdate']=$file['date'];
        $json['permalink']="http://chalk.coas.unf.edu/tatum/trcfiles/view/".$id;


        // Process data series to split out conditions, settings, and parameters
        $datas=$conds=$syss=[];
        foreach($sets as $set) {
            $sers=$set['Dataseries'];
            foreach($sers as $ser) {
                foreach($ser['Datapoint'] as $p=>$point) {
                    foreach($point['Data'] as $d=>$dval) {
                        $datas[$d][$p]=$dval;
                    }
                    foreach($point['Condition'] as $c=>$cval) {
                        $conds[$c][$p]=$cval;
                    }
                }
            }
            $syss[]=$set['System'];
        }

        // SciData
        $setj['@id']="scidata";
        $setj['@type']="sci:scientificData";
        $json['scidata']=$setj;

        // System
        $sysj=[];
        if(is_array($syss)&&!empty($syss)||is_array($chems)&&!empty($chems)||is_array($conds)&&!empty($conds)) {
            $json['toc']['sections'][]="system";
            $sysj['@id']='system';
            $sysj['@type']='sci:system';
            $sysj['discipline']='chemistry';
            $sysj['subdiscipline']='physical chemistry';
            $sysj['facets']=[];
        }

        //debug($chems);exit;

        // System sections
        // Mixture/Substance
        $systems=[];
        if(is_array($syss)&&!empty($syss)) {
            foreach($syss as $sysidx=>$sys) {
                if (count($sys['Substance']) > 1) {
                    $systems[] = $sys;
                    unset($syss[$sysidx]);
                }
            }
            if(!empty($systems)) {
                $syssj=[];
                foreach($systems as $sidx=>$system) {
                    $sysj=[];

                    $syssj[]=$sysj;
                }
                $sysj['facets']['substance'] = $syssj;
            }
            if(!empty($chems)) {
                $chemsj=$compsj=[];
                foreach($chems as $cidx=>$chem) {
                    // Create chemicals array
                    $chemj=[];
                    $chemj['@id']="chemical/".($cidx + 1).'/';
                    $json['toc']['sections'][] = $chemj['@id'];
                    $chemj['@type'] = "sci:chemical";
                    $chemj['name']=$chem['name'];
                    $chemj['scope']="compound/".($cidx + 1).'/';
                    if(!is_null($chem['source'])) {
                        $chemj['source']=$chem['source'];
                    }
                    if(!is_null($chem['analmethod'])) {
                        $chemj['analytical_method']=$chem['analmethod'];
                    }
                    if(!is_null($chem['purimethod'])) {
                        $chemj['purification_method']=$chem['purimethod'];
                    }
                    if(!is_null($chem['purity'])) {
                        $chemj['properties']=[];
                        $prop['@id']=$chemj['@id']."property/1/";
                        $prop['@type'] = "sci:purity";
                        $prop['number']=$chem['purity'];
                        if(isset($chem['puritysf'])) {
                            $prop['sigfigs']=$chem['puritysf'];
                        }
                        if (isset($chem['Unit']['qudt']) && !empty($chem['Unit']['qudt'])) {
                            $prop['unitref'] = 'qudt:'.$chem['Unit']['qudt'];
                        } elseif(isset($chem['Unit']['symbol'])) {
                            $prop['unitstr']=$chem['Unit']['symbol'];
                        }
                        $chemj['properties'][]=$prop;
                    }
                    $chemsj[]=$chemj;

                    // Create compounds array
                    $compj=[];
                    $compj['@id'] = "compound/".($cidx + 1).'/';
                    $json['toc']['sections'][] = $compj['@id'];
                    $compj['@type'] = "sci:compound";
                    $sub = $chem['Substance'];
                    $opts = ['name', 'formula', 'molweight'];
                    foreach ($opts as $opt) {
                        if (isset($sub[$opt]) && $sub[$opt] != "") {
                            $compj[$opt] = $sub[$opt];
                        }
                    }
                    if (isset($sub['Identifier'])) {
                        $opts = ['inchi', 'inchikey', 'iupacname'];
                        foreach ($sub['Identifier'] as $idn) {
                            foreach ($opts as $opt) {
                                if ($idn['type'] == $opt) {
                                    $compj[$opt] = $idn['value'];
                                }
                            }
                        }
                    }
                    $compsj[]=$compj;
                }
                $sysj['facets']['chemical'] = $chemsj;
                $sysj['facets']['compound'] = $compsj;
            }
        }

        // Conditions
        if(is_array($conds)&&!empty($conds)) {
            foreach($conds as $cid=>$cond) {
                //debug($cond);exit;
                $v=$vs=$condj = [];
                $condj['@id'] = "condition/".($cid + 1);
                $json['toc']['sections'][] = $condj['@id'];
                $condj['@type'] = "sci:condition";
                $condj['quantity'] = strtolower($cond[0]['Property']['Quantity']['name']);
                $condj['property'] = $cond[0]['Property']['name'];
                foreach ($cond as $cidx => $c) {
                    if(!in_array($c['number'],$vs)) {
                        $vs[]=$c['number'];
                        $v['@id'] = "condition/" . ($cid + 1) . "/value/".(array_search($c['number'],$vs)+1);
                        $v['@type'] = "sci:value";
                        if (!is_null($c['number'])) {
                            $v['number'] = $c['number'];
                            if (isset($c['Unit']['qudt']) && !empty($c['Unit']['qudt'])) {
                                $v['unitref'] = 'qudt:'.$c['Unit']['qudt'];
                            } elseif (isset($c['Unit']['symbol']) && !empty($c['Unit']['symbol'])) {
                                $v['unitstr'] = $this->Dataset->qudt($c['Unit']['symbol']);
                            }
                        } else {
                            $v['text'] = $c['text'];
                        }
                        $condj['value'][] = $v;
                    }
                    $conds[$cid][$cidx]['clink'][]="condition/".($cid+1)."/value/".(array_search($c['number'],$vs)+1);
                }
                $sysj['facets']['condition'] = $condj;
            }
        }

        $json['scidata']['system']=$sysj;

        // Data
        $resj=[];
        if(is_array($datas)&&!empty($datas)) {
            $resj['@type'] = 'sci:dataset';
            $resj['source'] = 'measurement/1';
            $resj['datagroup'] = [];
            // Group
            foreach($datas as $did=>$data) {
                $grpj['@id']='datagroup/'.($did+1);
                $json['toc']['sections'][] = $grpj['@id'];
                $grpj['@type'] = 'sci:datagroup';
                $grpj['quantity']=strtolower($data[0]['Property']['Quantity']['name']);
                $grpj['property']=$data[0]['Property']['name'];
                foreach($data as $d=>$dtm) {
                    $dtmj=[];
                    $dtmj['@id'] = 'datagroup/'.($did+1).'/datapoint/'.($d+1);
                    $dtmj['@type'] = 'sci:datapoint';
                    $dtmj['conditions']=$conds[$did][$d]['clink'];
                    if(!empty($setts)) {
                        $dtmj['settings']=$setts[$did][$d]['slink'];
                    }
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
                            $v['@id']=$dtmj['@id']."/value";
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
                            $v['@id']=$dtmj['@id']."/valuearray";
                            $v['@type']="sci:valuearray";
                            $v['numberarray']=json_decode($dtm['number'],true);
                            if($unit!="") { $v['unitref']=$unit; }
                            $dtmj['valuearray']=$v;
                        }
                    }
                    $grpj['datapoint'][]=$dtmj;
                }
                $resj['datagroup'][]=$grpj;
            }
        }
        $json['scidata']['dataset']=$resj;

        // Source
        // Original Paper
        $paper=['@id'=>'reference/1','@type'=>'dc:source'];
        if($ref['bibliography']!=null) {
            $paper['citation'] = $ref['bibliography'];
        } elseif($ref['citation']!=null) {
            $paper['citation'] = $ref['citation'];
        }
        if(isset($ref['doi'])&&$ref['doi']!=null) {
            $paper['url']="http://dx.doi.org/".$ref['doi'];
        }
        if(isset($ref['url'])&&$ref['url']!=null) {
            $paper['url']=$ref['url'];
        }
        // ThermoML
        $thermoml=['@id'=>'reference/2','@type'=>'dc:source'];
        $thermoml['citation'] = "TRC Group ThermoML Archive, NIST - http://www.trc.nist.gov/ThermoML/";
        $thermoml['url']='http://www.trc.nist.gov/ThermoML/'.$file['filename'];
        $json['references'][]=$paper;
        $json['references'][]=$thermoml;

        // Rights
        $json['rights']=['@id'=>'rights','@type'=>'dc:rights'];
        $json['rights']['holder']='NIST - TRC Group, Boulder CO';
        $json['rights']['license']='http://creativecommons.org/publicdomain/zero/1.0/';

        //exit;
        header("Content-Type: application/ld+json");
        echo json_encode($json,JSON_UNESCAPED_UNICODE);exit;
    }

    /**
     * Generate SciData
     * @param $id
     * @param $down
     */
    public function scidata($id,$down="")
    {
        // Note: there is an issue with the retrival of substances under system if id is not requested as a field
        // This is a bug in CakePHP as it works without id if its at the top level...
        $contains=[
            'Dataseries'=>[
                'Condition'=>['Unit',
                    'Property'=>['fields'=>['name'],
                        'Quantity'=>['fields'=>['name']]]],
                'Setting'=>['Unit', 'Property'=>['fields'=>['name'],
                    'Quantity'=>['fields'=>['name']]]],
                'Datapoint'=>[
                    'Condition'=>['Unit',
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]],
                    'Data'=>['Unit',
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]],
                    'Setting'=>['Unit',
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]],
                    'SupplementalData'=>['Unit',
                        'Metadata'=>['fields'=>['name']],
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]]],
                'Annotation'
            ],
            'Propertytype'=> [
                'Variable'=>['Property'=>['fields'=>['name'],
                    'Quantity'=>['fields'=>['name']]]],
                'Parameter'=>['Property'=>['fields'=>['name'],
                    'Quantity'=>['fields'=>['name']]]]
            ],
            'System'=>['fields'=>['id','name','description','type'],
                'Substance'=>['fields'=>['name','formula','molweight'],
                    'Identifier'=>['fields'=>['type','value'],'conditions'=>['type'=>['inchi','inchikey','iupacname']]]]
            ],
            'Report',
            'File'=>['Publication'],
            'Reference'

        ];
        $data=$this->Dataset->find('first',['conditions'=>['Dataset.id'=>$id],'contain'=>$contains,'recursive'=>-1]);
        //debug($data);exit;

        $rpt=$data['Report'];
        $ptype=$data['Propertytype'];
        $set=$data['Dataset'];
        $file=$data['File'];
        $pub=$file['Publication'];
        $ref=$data['Reference'];
        $ser=$data['Dataseries'];
        $sys=$data['System'];
        //debug($ser);exit;

        // Other systems -> related
        $othersys=$this->Dataset->find('list',['fields'=>['id'],'conditions'=>['system_id'=>$sys['id'],'propertytype_id'=>$ptype['id'],'NOT'=>['Dataset.id'=>$id]]]);
        //debug($othersys);exit;

        // Base
        $base="https://chalk.coas.unf.edu/springer/datasets/scidata/".$id."/";

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
        $json['uid']="springer:dataset:".$id;
        $json['title']=$rpt['title'];
        $json['author']=[];
        if($ref['authors']!=null) {
            if(stristr($ref['authors'],'[{')) {
                $authors=json_decode($ref['authors'],true);
            } else {
                $authors=explode(", ",$ref['authors']);
            }
            $acount=1;
            foreach ($authors as $au) {
                $json['author'][]=['@id'=>'author/'.$acount,'@type'=>'dc:creator','name'=>$au];
                $acount++;
            }
        }
        $json['description']=$rpt['title'];
        $json['publisher']='Springer Nature';
        $json['startdate']=$set['updated'];
        $json['permalink']="http://chalk.coas.unf.edu/springer/datasets/view/".$id;
        foreach($othersys as $os) {
            $json['related'][]="http://chalk.coas.unf.edu/springer/datasets/view/".$os;
        }
        $json['toc']=['@id'=>'toc','@type'=>'dc:tableOfContents','sections'=>[]];

        // Process data series to split out conditions, settings, and parameters
        $datas=$conds=$setts=$supps=[];
        foreach($ser[0]['Datapoint'] as $p=>$point) {
            foreach($point['Data'] as $d=>$dval) {
                $datas[$d][$p]=$dval;
            }
            foreach($point['Condition'] as $c=>$cval) {
                $conds[$c][$p]=$cval;
            }
            foreach($point['Setting'] as $s=>$sval) {
                $setts[$s][$p]=$sval;
            }
            foreach($point['SupplementalData'] as $u=>$uval) {
                $supps[$u][$p]=$uval;
            }
        }
        //debug($datas);debug($conds);debug($setts);debug($supps);exit;

        // SciData
        $setj['@id']="scidata";
        $setj['@type']="sci:scientificData";
        $json['scidata']=$setj;

        // Settings
        $metj=[];
        if(!empty($setts)) {
            // Methodology
            $metj['@id']='methodology';
            $metj['@type']='sci:methodology';
            $metj['evaluation']='experimental';
            $metj['aspects']=[];
            $json['toc']['sections'][] = $metj['@id'];
            $meaj['@id'] = 'measurement/1';
            $meaj['@type'] = 'meas:measurement';
            $json['toc']['sections'][] = $meaj['@id'];
            $meaj['settings'] = [];
            foreach($setts as $sid=>$sett) {
                //debug($sett);exit;
                $setgj = [];
                $setgj['@id'] = "setting/".($sid + 1);
                $setgj['@type'] = "sci:setting";
                $setgj['quantity'] = strtolower($sett[0]['Property']['Quantity']['name']);
                $setgj['property'] = $sett[0]['Property']['name'];
                foreach ($sett as $sidx => $s) {
                    $v=$vs=[];
                    if(!in_array($s['number'],$vs)) {
                        $vs[]=$s['number'];
                        $v['@id'] = "setting/" . ($sid + 1) . "/value/".(array_search($s['number'],$vs)+1);
                        $v['@type'] = "sci:value";
                        if (!is_null($s['number'])) {
                            $v['number'] = $s['number'];
                            if (isset($s['Unit']['symbol']) && !empty($s['Unit']['symbol'])) {
                                $v['unitref'] = $this->Dataset->qudt($s['Unit']['symbol']);
                            }
                        } else {
                            $v['text'] = $s['text'];
                        }
                        $setgj['value'] = $v;

                    }
                    $setts[$sid][$sidx]['slink'][]="setting/".($sid + 1) . "/value/".(array_search($s['number'],$vs)+1);
                }
                $meaj['settings'][] = $setgj;
            }
            $metj['aspects'][] = $meaj;
        }
        $json['scidata']['methodology']=$metj;

        // System
        $sysj=[];
        if(is_array($sys)&&!empty($sys)||is_array($conds)&&!empty($conds)) {
            $json['toc']['sections'][]="system";
            $sysj['@id']='system';
            $sysj['@type']='sci:system';
            $sysj['discipline']='chemistry';
            $sysj['subdiscipline']='physical chemistry';
            $sysj['facets']=[];
        }


        // Data
        $resj=[];
        if(is_array($datas)&&!empty($datas)) {
            $json['toc']['sections'][] = "dataset";
            $resj['@id'] = 'dataset';        // System sections
            // Mixture/Substance
            $type='';
            if(is_array($sys)&&!empty($sys)) {
                // System
                if (count($sys['Substance']) == 1) {
                    $type = "substance";
                } else {
                    $type = "mixture";
                }
                $sid = $type . "/1";
                $json['toc']['sections'][] = $sid;
                $mixj['@id'] = $sid;
                $mixj['@type'] = "sci:" . $type;
                $opts = ['name', 'description', 'type'];
                foreach ($opts as $opt) {
                    if (isset($sys[$opt]) && $sys[$opt] != "") {
                        $mixj[$opt] = $sys[$opt];
                    }
                }
                if (isset($sys['Substance'])) {
                    for ($j = 0; $j < count($sys['Substance']); $j++) {
                        // Components
                        $subj['@id'] = $sid . "/component/" . ($j + 1);
                        $subj['@type'] = "sci:chemical";
                        $subj['source'] = "compound/" . ($j + 1);
                        $mixj['components'][] = $subj;
                        // Chemicals
                        $sub = $sys['Substance'][$j];
                        $chmj['@id'] = "compound/" . ($j + 1);
                        $json['toc']['sections'][] = $chmj['@id'];
                        $chmj['@type'] = "sci:compound";
                        $opts = ['name', 'formula', 'molweight'];
                        foreach ($opts as $opt) {
                            if (isset($sub[$opt]) && $sub[$opt] != "") {
                                $chmj[$opt] = $sub[$opt];
                            }
                        }
                        if (isset($sub['Identifier'])) {
                            $opts = ['inchi', 'inchikey', 'iupacname'];
                            foreach ($sub['Identifier'] as $idn) {
                                foreach ($opts as $opt) {
                                    if ($idn['type'] == $opt) {
                                        $chmj[$opt] = $idn['value'];
                                    }
                                }
                            }
                        }
                        $sysj['facets'][] = $chmj;
                    }
                }
                $sysj['facets'][] = $mixj;
            }
            // Conditions
            if(is_array($conds)&&!empty($conds)) {
                foreach($conds as $cid=>$cond) {
                    //debug($cond);exit;
                    $v=$vs=$condj = [];
                    $condj['@id'] = "condition/".($cid + 1);
                    $json['toc']['sections'][] = $condj['@id'];
                    $condj['@type'] = "sci:condition";
                    $condj['quantity'] = strtolower($cond[0]['Property']['Quantity']['name']);
                    $condj['property'] = $cond[0]['Property']['name'];
                    foreach ($cond as $cidx => $c) {
                        if(!in_array($c['number'],$vs)) {
                            $vs[]=$c['number'];
                            $v['@id'] = "condition/" . ($cid + 1) . "/value/".(array_search($c['number'],$vs)+1);
                            $v['@type'] = "sci:value";
                            if (!is_null($c['number'])) {
                                $v['number'] = $c['number'];
                                if (isset($c['Unit']['symbol']) && !empty($c['Unit']['symbol'])) {
                                    $v['unitref'] = $this->Dataset->qudt($c['Unit']['symbol']);
                                }
                            } else {
                                $v['text'] = $c['text'];
                            }
                            $condj['value'][] = $v;
                        }
                        $conds[$cid][$cidx]['clink'][]="condition/".($cid+1)."/value/".(array_search($c['number'],$vs)+1);
                    }
                    $sysj['facets'][] = $condj;
                }
            }
            $json['scidata']['system']=$sysj;

            $resj['@type'] = 'sci:dataset';
            $resj['source'] = 'measurement/1';
            $resj['scope'] = $type . '/1';
            $resj['datagroup'] = [];
            // Group
            foreach($datas as $did=>$data) {
                $grpj['@id']='datagroup/'.($did+1);
                $json['toc']['sections'][] = $grpj['@id'];
                $grpj['@type'] = 'sci:datagroup';
                $grpj['quantity']=strtolower($data[0]['Property']['Quantity']['name']);
                $grpj['property']=$data[0]['Property']['name'];
                foreach($data as $d=>$dtm) {
                    $dtmj=[];
                    $dtmj['@id'] = 'datagroup/'.($did+1).'/datapoint/'.($d+1);
                    $dtmj['@type'] = 'sci:datapoint';
                    $dtmj['conditions']=$conds[$did][$d]['clink'];
                    if(!empty($setts)) {
                        $dtmj['settings']=$setts[$did][$d]['slink'];
                    } else {
                        $dtmj['settings']=[];
                    }
                    // Value
                    $v=[];
                    if(!is_null($dtm['number'])) {
                        $unit="";
                        if(isset($dtm['Unit']['symbol'])&&!empty($dtm['Unit']['symbol'])) {
                            $unit=$this->Dataset->qudt($dtm['Unit']['symbol']);
                        }
                        if($dtm['datatype']=="datum") {
                            $v['@id']=$dtmj['@id']."/value";
                            $v['@type']="sci:value";
                            $v['number']=$dtm['number'];
                            if($unit!="") { $v['unitref']=$unit; }
                            $dtmj['value']=$v;
                        } else {
                            $v['@id']=$dtmj['@id']."/valuearray";
                            $v['@type']="sci:valuearray";
                            $v['numberarray']=json_decode($dtm['number'],true);
                            if($unit!="") { $v['unitref']=$unit; }
                            $dtmj['valuearray']=$v;
                        }
                    }
                    $grpj['datapoint'][]=$dtmj;
                }
                $resj['datagroup'][]=$grpj;
            }
        }
        $json['scidata']['dataset']=$resj;

        // Sources
        // Original Paper
        $paper=['@id'=>'reference/1','@type'=>'dc:source'];
        if($ref['bibliography']!=null) {
            $paper['citation'] = $ref['bibliography'];
        } elseif($ref['citation']!=null) {
            $paper['citation'] = $ref['citation'];
        }
        if(isset($ref['doi'])&&$ref['doi']!=null) {
            $paper['url']="http://dx.doi.org/".$ref['doi'];
        }
        if(isset($ref['url'])&&$ref['url']!=null) {
            $paper['url']=$ref['url'];
        }
        // Springer Publication
        $volume=['@id'=>'reference/2','@type'=>'dc:source'];
        $volume['citation'] = $pub['citation'];
        if(isset($pub['doi'])&&$pub['doi']!=null) {
            $volume['url']="http://dx.doi.org/".$pub['doi'];
        }
        if(isset($pub['url'])&&$pub['url']!=null) {
            $volume['url']=$pub['url'];
        }
        if(isset($pub['eisbn'])&&$pub['eisbn']!=null) {
            $volume['eisbn'] = $pub['eisbn'];
        }
        $json['references'][]=$paper;
        $json['references'][]=$volume;

        // Rights
        $json['rights']=['@id'=>'rights','@type'=>'dc:rights'];
        $json['rights']['holder']='NIST - TRC Group, Boulder CO';
        $json['rights']['license']='http://creativecommons.org/publicdomain/zero/1.0/';

        // OK turn it back into JSON-LD
        header("Content-Type: application/ld+json");
        if($down=="download") { header('Content-Disposition: attachment; filename="'.$id.'.jsonld"'); }
        echo json_encode($json,JSON_UNESCAPED_UNICODE);exit;

    }

}