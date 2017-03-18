<?php

/**
 * Class TrcfilesController
 * @author Stuart Chalk <schalk@unf.edu>
 *
 */
class TrcfilesController extends AppController
{

    public $uses=['Trcfile','Trcchemical','Identifier','Datarectification'];

    /**
     * function beforeFilter
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    public function getdata()
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
                // Make a singel compound into an array
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
            debug($trc);exit;

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

}