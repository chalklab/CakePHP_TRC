<?php

/**
 * Class Solseries
 * Methods to access and convert SOLSERIES data
 */
class SolseriesController extends AppController
{
    /**
     * There is no solseries table, instead this controller gets data from the solseries database, plus
     * the Scidata model, the property table from the nistsds2 DB, the sol table from the crosswalks DB
     * and two plugins - Pubchem and Crossref
     * @var array
     */
    public $uses = ['Soldata','Mainsys','Comp','Scidata','Property','Reference','Report','Substance','System','Sol','Pubchem.Pugrest','Crossref.Api'];

    /**
     * this function gets data from a specific sysid (report of data about a chemical system and on ref)
     * as found in the Mainsys table in the solseries DB
     * @param string $sysid
	 * @param string $output
     */
	public function getdata($output='browser',$sysid='37_83')
	{
	    // this find is present to eventually get all data from the solseries DB, however it is currently
        // only grabbing on data set during testing
		$data=$this->Mainsys->find('list',['fields'=>['id','SYS_ID'],'conditions'=>['SYS_ID like'=>$sysid],'order'=>'SYS_ID']);
        // $c contains the associated tables of data from the solseries DB that needed for one report (sysid)
		$c=['Comp'=>[
		        'Substancelk'],
            'Soldata'=>['order'=>['TableNum','RowNum'],'conditions'=>['ignore_out'=>0]],
            'Gasdata'=>['order'=>['TableNum','RowNum']],
            'Soliddata'=>['order'=>['TableNum','RowNum']],
            'Sysnamelk'=>['Volume'],
            'Referencelst',
            'Footnote',
            'Parametertb',
            'Newparametertb',
            'Evalsys'=>['Critic','Referencerel'=>['Referencelst'],'Sysineval','Figure'],
            'Commenttb',
            'Exptdata',
            'Summarytable'=>['order'=>['TableNum','RowNum']]
		];

		// get the crosswalk data
		$fields=$nspaces=$ontlinks=[];
		$this->getcw('conditions',$fields,$nspaces,$ontlinks);
		$this->getcw('exptdata',$fields,$nspaces,$ontlinks);
		$this->getcw('deriveddata',$fields,$nspaces,$ontlinks);
		$this->getcw('suppdata',$fields,$nspaces,$ontlinks);
		//debug($fields);debug($nspaces);debug($ontlinks);exit;

		$nspaces['afrl']="http://purl.allotrope.org/ontologies/role#";

		// iterate over all the data sets (sysids) found
		foreach($data as $sysid) {
		    //echo "Report: ".$sysid."<br/>";
			$datum=$this->Mainsys->find('first',['conditions'=>['Mainsys.SYS_ID'=>$sysid],'contain'=>$c,'recursive'=>-1]);

			// Check to see if this is an evaluation or experimental data
			if(empty($datum['Evalsys'])) {
			    // Experimental data

				// Instantiate variables
				$sys=$chms=$cmps=$meths=$facets=$aspects=$links=$toc=$sources=$roles=$allconds=$points=[];
				$datatype=null;

				// Cleanup
				// Remove empty fields from the data tables
				if(!empty($datum['Soliddata'])) {
					foreach($datum['Soliddata'] as $idx=>$datapoint) {
						foreach($datapoint as $field=>$value) {
							if(is_null($value)) {
								unset($datum['Soliddata'][$idx][$field]);
							}
						}
					}
					$datatype="solid";$points=$datum['Soliddata'];
				}
				if(!empty($datum['Gasdata'])) {
					foreach($datum['Gasdata'] as $idx=>$datapoint) {
						foreach($datapoint as $field=>$value) {
							if(is_null($value)) {
								unset($datum['Gasdata'][$idx][$field]);
							}
						}
					}
					$datatype="gas";$points=$datum['Gasdata'];
				}
				if(!empty($datum['Soldata'])) {
				    foreach($datum['Soldata'] as $idx=>$datapoint) {
						foreach($datapoint as $field=>$value) {
							if(is_null($value)) {
								unset($datum['Soldata'][$idx][$field]);
							}
						}
					}
                    $datatype="liquid";$points=$datum['Soldata'];
				}

                // Consistency checks
				// Chemical system check
				if($datum['Mainsys']['NO_OF_COMPONENT']!=count($datum['Comp'])) {
					exit('Number of substances in system is inconsistent');
				}
				// Data check
                if(is_null($datatype)) {
                    exit('No data found');
                } else {
                    if($datatype=='liquid') {
                        foreach($datum['Soldata'][0] as $field) {
                            if(is_null($field)) {
                                debug($datum);exit;
                            }
                        }
                    }
                }


                // Process the data

				// Metadata
				$id=$datum['Mainsys']['SYS_ID'];

                // this creates a instance of the Scidata class
                $sds = new $this->Scidata;
                $sds->setnspaces($nspaces);
				$sds->setpath("https://scidata.coas.unf.edu/sds/");
				$sds->setbase($id."/");
				$sds->setid("https://scidata.coas.unf.edu/sds/".$id."/");
				$sds->setpid("sds:".$id);
				$sds->setdiscipline("chemistry");
				$sds->setsubdiscipline("physical chemistry");
				$meta=['title'=>'Solubility data for '.$datum['Sysnamelk']['SYS_NAME'],
                    'publisher'=>'IUPAC Subcommittee on Solubility and Equilibrium data (SSED)',
                    'description'=>'Critically reviewed solubility data reported in the IUPAC Solubility Data Series'];
                $sds->setmeta($meta);
                $url=Configure::read('url.system.detail');
                $related=[0=>str_replace('*sysID*',$id,$url)];
                $sds->setkeywords(['solubility','IUPAC','critically evaluated data']);
                $sds->setrelated($related);

                // Report information

                // specific to this project, not normally added to scidata
                $rep=[];
                if(!empty($datum['Exptdata'])) {
                    $expt=$datum['Exptdata'][0];
                    if(!is_null($expt['T_K']))              { $rep['conditions']['temp_k']=$expt['T_K']; }
                    if(!is_null($expt['pH']))               { $rep['conditions']['ph']=$expt['pH']; }
                    if(!is_null($expt['pressure']))         { $rep['conditions']['pressure']=$expt['pressure']; }
                    if(!is_null($expt['concentration']))    { $rep['conditions']['concentration']=$expt['concentration']; }
                    if(!is_null($expt['prepared_by']))      { $rep['annotations']['prepared_by']=$expt['prepared_by']; }
                    if(!is_null($expt['REMARKS']))          { $rep['annotations']['remarks']=str_replace(["<br/>","<p>"]," ",$expt['REMARKS']); }
                    if(!is_null($expt['comments']))         { $rep['updates']['exptdata']=$expt['comments']; }
                }
                if(!empty($datum['Commenttb'])) {
                    $cmmt=$datum['Commenttb'];
                    if(!is_null($cmmt['Reference1']))       { $rep['citations'][]=str_replace("  "," ",$cmmt['Reference1']); }
                    if(!is_null($cmmt['Reference2']))       { $rep['citations'][]=str_replace("  "," ",$cmmt['Reference2']); }
                    if(!is_null($cmmt['Reference3']))       { $rep['citations'][]=str_replace("  "," ",$cmmt['Reference3']); }
                    if(!is_null($cmmt['Reference4']))       { $rep['citations'][]=str_replace("  "," ",$cmmt['Reference4']); }
                    if(!is_null($cmmt['comments']))         { $rep['updates']['commenttb']=$cmmt['comments']; }
                }
                foreach($points as $point) {
                	if(!empty($point['edits'])) {
						$rep['edits'][$point['sysid_tablenum_rownum']]=$point['edits'];
					}
				}
                $sds->setreport($rep);

				// Footnotes
				$notes=$datum['Footnote'];

                // Methodology information

                // get general description of how solubility data was obtained (procedure)
                // also may contain apparatus and analysis method
                $proc=[];
                if(!empty($datum['Commenttb'])) {
                    // there is only one comment in index 0 (because of hasMany)
                    $comments = $datum['Commenttb'];
					$tmp=[];
					if(!is_null($comments['Method1'])) {
                        $tmp['description']=$comments['Method1'];
                        //$toc[]='procedure/1/';
                    }
					if(!is_null($comments['Method2'])) {
						$tmp['description'].=" ".$comments['Method2'];
					}
					if(!is_null($comments['Method3'])) {
						$tmp['description'].=" ".$comments['Method3'];
					}
					// cleanup
					$tmp['description']=str_replace("  "," ",$tmp['description']);
                    $proc[1]=$tmp;
				}
                $aspects['sci:procedure']=$proc;

                // some data tables have a column describing the method of analysis
                $this->getcw('method',$fields,$nspaces,$ontlinks);
                // this would normally be an reported one time, however in the situation where the it is in
                // table it is specific to each row so therefore has to be done in dataseries below
                // debug($fields);exit;

                $sds->setaspects($aspects);


                // System information

                // Get compounds (comp is short for components table model)
				$comps=$datum['Comp'];$syss=$chems=$sysrows=$sroles=$uchms=[];
				foreach($comps as $comp) {
					$num=$comp['component_no'];
					$sub=$comp['Substancelk'];
					if(!is_null($sub['CAS_No'])) {
						$tmp=[];
						$tmp['name']=$sub['CHEM_NAME'];
						$tmp['subid']=$sub['SUB_ID'];
						$tmp['casrn']=$sub['CAS_No'];
						// Find other metadata from pubchem (not using)
						// $ids=$this->Pugrest->check($tmp['name'],$tmp['casrn']);
						// if(isset($ids['InChIKey'])) { $tmp['inchikey']=$ids['InChIKey'];}
                        // Find other metadata from substances
                        $c2=['Identifier'=>['conditions'=>['type'=>['iupacname','inchikey']]]];
                        $chm=$this->Substance->find('first',['conditions'=>['casno'=>$tmp['casrn']],'contain'=>$c2,'recursive'=>-1]);
						foreach($chm['Identifier'] as $chmid) {
                            if($chmid['type']=='iupacname') {
                                $tmp['iupacname']=$chmid['value'];
                            } elseif($chmid['type']=='inchikey') {
                                $tmp['inchikey']=$chmid['value'];
                            }
                        }
						$cmps[$num]=$tmp;
						// add single chem to $syss entries
						if(!empty($syss)) {
							foreach($syss as $sidx=>$sys) {
								$syss[$sidx][]=$sys;
							}
						} else {
							// update crosslinks
							$links['compounds'][$num]='compound/'.$num.'/';
						}
						$chems[$num]=$tmp;
						//$toc[]='compound/'.$num.'/';
						if($comp['is_solvent']) {
							$sroles[$num]='solvent';
						} else {
							$sroles[$num]='solute';
						}
					} else {
						// Multiple chemicals in table check (data in chemicalName)
						if(isset($points[0]['ChemicalName'])) {
							foreach($points as $idx=>$pnt) {
								$cmeta=explode("; ",$pnt['ChemicalName']);
								if(!in_array($cmeta[0],$uchms)) {
									// if this chemical is not in any prior row
									$tmp=[];
									$tmp['name']=$cmeta[0];
									if(preg_match("/\[\d{2,7}-\d{2}-\d\]/",$cmeta[2])) {
										$cmeta[2]=str_replace(["[","]"],"",$cmeta[2]);
										$tmp['casrn']=$cmeta[2];
										$c2=['Identifier'=>['conditions'=>['type'=>['iupacname','inchikey']]]];
										$chm=$this->Substance->find('first',['conditions'=>['casno'=>$cmeta[2]],'contain'=>$c2,'recursive'=>-1]);
										if(!isset($chm['Identifier'])) { debug($cmeta); }
										foreach($chm['Identifier'] as $chmid) {
											if($chmid['type']=='iupacname') {
												$tmp['iupacname']=$chmid['value'];
											} elseif($chmid['type']=='inchikey') {
												$tmp['inchikey']=$chmid['value'];
											}
										}
									} else {
										echo "Chemical info?";debug($cmeta);exit;
									}
									$cid=$num+$pnt['RowNum']-1;
									$cmps[$cid]=$tmp;
									$chems[$num][($pnt['RowNum']-1)]=$tmp;
									//$toc[]='compound/'.$cid.'/';
									if(empty($syss[$pnt['RowNum']])) {
										foreach($chems as $cidx=>$chem) {
											if(isset($chem['casrn'])) {
												$syss[$pnt['RowNum']]=[$cidx=>$chem,$cid=>$tmp];
												// update crosslinks
												$links['compounds'][$pnt['RowNum']]=['compound/'.$cidx.'/','compound/'.$cid.'/'];
											}
										}
									}
									$uchms[$pnt['RowNum']]=$cmeta[0];
									$sysrows[$pnt['RowNum']]='system/'.$pnt['RowNum']."/";
								} else {
									// we have already had this chemical so dont add new system, just add row
									$uidx=array_search($cmeta[0],$uchms);
									$sysrows[$pnt['RowNum']]='system/'.$uidx."/";
								}
							}
						} else {
							echo "Missing CAS#";
							debug($sub);exit;
						}
					}
				}
				$facets['sci:compound']=$cmps;


				// Get chemical system(s)
				$headings=[];$props=[];
				foreach($points as $pidx=>$point) {
					if($point['RowNum']==1) {
						if(isset($point['Heading'])) {
							$headings[$point['TableNum']]=$point['Heading'];
						} else {
							$headings[$point['TableNum']]=null;
						}
					}
					foreach($point as $field=>$value) {
						if($point['RowNum']==1) {
							if(in_array($field,['x1','w1','x1Comp','w1Comp','g1/100g','g1/100gComp','g1/g2','g1/g3','g1/kg','g1/kgComp','g1/V2','g1/V2Comp','ρ1','c1','V1/V2','n1/100g2','n1/n2','n1/V2','phi1','w1/M1','w1/M1Comp','mol1/kg','mol1/kgComp'])) {
								if(isset($props[$point['TableNum']][1])) {
									$props[$point['TableNum']][1]++;
								} else {
									$props[$point['TableNum']][1]=1;
								}
							} elseif(in_array($field,['x2','w2','x2Comp','w2Comp','w2\'','w2\'\'','g2/100g','g2/100gComp','g2/g1','g2/kg','g2/V1','V2/g1','V2/V1','c2','phi2','mol2/kg','mol2/kgComp'])) {
								if(isset($props[$point['TableNum']][2])) {
									$props[$point['TableNum']][2]++;
								} else {
									$props[$point['TableNum']][2]=1;
								}
							} elseif(in_array($field,['x3','w3','x3Comp','w3Comp','V3/Vtot','g3/100g'])) {
								if(isset($props[$point['TableNum']][3])) {
									$props[$point['TableNum']][3]++;
								} else {
									$props[$point['TableNum']][3]=1;
								}
							} elseif(in_array($field,['x4','x4Comp','w4'])) {
								if(isset($props[$point['TableNum']][4])) {
									$props[$point['TableNum']][4]++;
								} else {
									$props[$point['TableNum']][4]=1;
								}
							}
						}
					}
				}
				$systypes=Configure::read('systypes');
				if(empty($syss)) {
					// detect if system is one solute in another (only one is_solvent=1),
					// has two systems where each is a solute in the other (both have is_solvent=0),
					// or a mutual solubility system (two or more phases) (both have is_solvent=1)
					$srolecnts=array_count_values($sroles);
					if(isset($srolecnts['solute'])&&$srolecnts['solute']==count($sroles)) {
						// mutual solubility
						debug($datum['Comp']);exit;
					} elseif(isset($srolecnts['solvent'])&&$srolecnts['solvent']==count($sroles)) {
						// two systems
						if(in_array(null,$headings)) {
							echo "How do we assign the systems to the series of data?";
							debug($headings);debug($props);
							debug($datum['Comp']);exit;
						} else {
							// create the two system variants
							foreach($datum['Comp'] as $cidx=>$comp) {
								$sys=[];
								$sys['name']=$datum['Sysnamelk']['SYS_NAME'];
								$sys['type']=$systypes[count($datum['Comp'])];
								$sys['components']=[];
								// system with one or more solutes and one solvent
								foreach($links['compounds'] as $cnum=>$clink) {
									$cmpnt=[];
									$cmpnt['@id']='component/'.$cnum.'/';
									$cmpnt['@type']='sci:component';
									$cmpnt['compound']=$clink;
									$solv=$datum['Comp'][($cnum-1)]['is_solvent'];
									if($solv) {
										$cmpnt['role']='afrl:AFRL_0000269';
									} else {
										$cmpnt['role']='afrl:AFRL_0000270';
									}
									$sys['components'][]=$cmpnt;
								}
								// add system facet
								$facets['sci:chemicalsystem'][1]=$sys;
								// add toc entry
								//$toc[]='chemicalsystem/1/';
							}
							if(count($headings)==2) {
								// assign systems to tables (series)
								foreach($headings as $hidx=>$h) {
									// add links to relate system to table
									// determine system from $props with higher count
									$links['systems'][$hidx]='chemicalsystem/1/';

								}
							}
						}
					} elseif(isset($srolecnts['solvent'])&&isset($srolecnts['solute'])&&$srolecnts['solvent']==1) {
						// add links to relate system to table
						$links['systems'][1]='chemicalsystem/1/';
						// create the system metadata
						$sys=[];$solute=$solvent="";
						foreach($comps as $c) {
							if($c['is_solvent']) {
								$solvent=$c['Substancelk']['CHEM_NAME']." (".$c['component_no'].")";
							} else {
								$solute=$c['Substancelk']['CHEM_NAME']." (".$c['component_no'].")";
							}
						}
						$sys['name']=$solute." in ".$solvent;
						$sys['type']=$systypes[count($datum['Comp'])];
						$sys['components']=[];
						// system with one or more solutes and one solvent
						foreach($links['compounds'] as $cnum=>$clink) {
							$cmpnt=[];
							$cmpnt['@id']='component/'.$cnum.'/';
							$cmpnt['@type']='sci:component';
							$cmpnt['compound']=$clink;
							$solv=$datum['Comp'][($cnum-1)]['is_solvent'];
							if($solv) {
								$cmpnt['role']='afrl:AFRL_0000269';
							} else {
								$cmpnt['role']='afrl:AFRL_0000270';
							}
							$sys['components'][]=$cmpnt;
						}
						// create headings if they don't already exist
						if(array_search(null,$headings)) {
							$heading="";
							if(count($datum['Comp'])==4) {
								$heading="Solubility of ".$datum['Comp'][0]['Substancelk']['CHEM_NAME']." (1) and ".$datum['Comp'][1]['Substancelk']['CHEM_NAME']." (2) and ".$datum['Comp'][2]['Substancelk']['CHEM_NAME']." (3) in ".$datum['Comp'][3]['Substancelk']['CHEM_NAME']." (4)";
							} elseif(count($datum['Comp'])==3) {
								$heading="Solubility of ".$datum['Comp'][0]['Substancelk']['CHEM_NAME']." (1) and ".$datum['Comp'][1]['Substancelk']['CHEM_NAME']." (2) in ".$datum['Comp'][2]['Substancelk']['CHEM_NAME']." (3)";
							} elseif(count($datum['Comp'])==2) {
								$heading="Solubility of ".$datum['Comp'][0]['Substancelk']['CHEM_NAME']." (1) in ".$datum['Comp'][1]['Substancelk']['CHEM_NAME']." (2)";
							}
							foreach($headings as $tablenum=>$h) {
								if($h==null) {
									$headings[$tablenum]=$heading;
								}
							}
						}
						// add system facet
						$facets['sci:chemicalsystem'][1]=$sys;
						// add toc entry
						//$toc[]='chemicalsystem/1/';
					} else {
						// unexpected combination
						echo "System composition?";debug($sroles);exit;
					}
				} else {
					debug($syss);exit;
					foreach($syss as $sidx=>$s) {
						$sys['name']="";$cidx=1;
						foreach($s as $chm) { // $nidx starts at 1
							if($cidx==count($s)) {
								$sys['name'].=" and ".$chm['name'];
							} elseif($cidx==1&&count($s)==2) {
								$sys['name'].=$chm['name'];
							} else {
								$sys['name'].=$chm['name'].", ";
							}
							$cidx++;
//							if($comp['is_solvent']==1) {
//								$tmp['role']='solvent';
//							} else {
//								$tmp['role']='solute';
//							}

						}
						$sys['type']=$systypes[count($s)];
						$sys['components']=$links['compounds'][$sidx];
						$facets['sci:chemicalsystem'][$sidx]=$sys;
						//$toc[]='chemicalsystem/'.$sidx.'/';
					}
				}

				// set system rows if defined (mapping of system to rows of data)
				if(!empty($sysrows)) {
					$sds->setsysrows($sysrows);
				}

                // Add chemical info (purity) if present
				if(!empty($datum['Commenttb'])) {
					$comments=$datum['Commenttb'];
					if(!is_null($comments['Source1'])) {
						$src=$comments['Source1'];
						$src=preg_replace("/(&nbsp;)+/",",",$src);
						$tmp=[];
						$tmp['purity']=$src;
						$tmp['compound']='compound/1/';
						//$toc[]='chemical/1/';
						$chms[1]=$tmp;
					}
					if(!is_null($comments['Source2'])) {
						$src=$comments['Source2'];
						$src=str_replace("&nbsp;","",$src);
						if(!empty($syss)) {
							foreach($syss as $sys) {
								$i=1;
								foreach($sys as $cid=>$comp) {
									if($i==1) {
										$i++;
									} elseif($i==2) {
										$tmp=[];
										$tmp['purity']=$src;
										$tmp['compound']='compound/'.$cid.'/';
										//$toc[]='chemical/'.$cid.'/';
										$chms[$cid]=$tmp;
									}
								}
							}
						} else {
							$tmp=[];
							$tmp['purity']=$src;
							$tmp['compound']='compound/2/';
							//$toc[]='chemical/2/';
							$chms[2]=$tmp;
						}
					}
					if(!is_null($comments['Source3'])) {
						$src=$comments['Source3'];
						$src=str_replace("&nbsp;","",$src);
						$tmp=[];
						$tmp['purity']=$src;
						$tmp['compound']='compound/3/';
                        //$toc[]='chemical/3/';
                        $chms[3]=$tmp;
					}
					if(!is_null($comments['Source4'])) {
						$src=$comments['Source4'];
						$src=str_replace("&nbsp;","",$src);
						$tmp=[];
						$tmp['purity']=$src;
						$tmp['compound']='compound/4/';
                        //$toc[]='compound/4/';
                        $chms[4]=$tmp;
					}
				}
				if(!empty($chms)) { $facets['sci:chemical']=$chms; }


				// Data

                // get errors
                $errs=[];$datasets=[];
                if(!empty($datum['Commenttb'])) {
                    $cmmt=$datum['Commenttb'];
                    if(!is_null($cmmt['err_temp_val']))         {
                        $errs['temp']['val']=$cmmt['err_temp_val'];
                        $errs['temp']['unit']=$cmmt['err_temp_unit'];
                        $errs['temp']['note']=$cmmt['err_temp_note'];
                    }
                    if(!is_null($cmmt['err_sol_val']))          {
                        $errs['sol']['prop']=$cmmt['err_sol_prop'];
                        $errs['sol']['val']=$cmmt['err_sol_val'];
                        $errs['sol']['unit']=$cmmt['err_sol_unit'];
                        $errs['sol']['note']=$cmmt['err_sol_note'];
                    }
                    if(!is_null($cmmt['err_press_val']))        {
                        $errs['press']['val']=$cmmt['err_press_val'];
                        $errs['press']['unit']=$cmmt['err_press_unit'];
                        $errs['press']['note']=$cmmt['err_press_note'];
                    }
                    if(!is_null($cmmt['err_comp_val']))         {
                        $errs['comp']['val']=$cmmt['err_comp_val'];
                    }
                    if(!is_null($cmmt['err_titer_val']))        {
                        $errs['titer']['val']=$cmmt['err_titer'];
                        $errs['titer']['unit']=$cmmt['err_titer_unit'];
                        $errs['titer']['note']=$cmmt['err_titer_note'];
                    }
                    if(!is_null($cmmt['err_weighting_val']))    {
                        $errs['weigh']['val']=$cmmt['err_weighting'];
                        $errs['weigh']['unit']=$cmmt['err_weighting_unit'];
                        $errs['weigh']['note']=$cmmt['err_weighting_note'];
                    }
                    if(!is_null($cmmt['err_other_val']))    {
                        $errs['other']['prop']=$cmmt['err_other_prop'];
                        $errs['other']['val']=$cmmt['err_other_val'];
                        $errs['other']['unit']=$cmmt['err_other_unit'];
                        $errs['other']['note']=$cmmt['err_other_note'];
                    }
                }

                // split out conditions, data, supplemental data and annotations
                if($datatype=="solid") {
                    // TODO
                } elseif($datatype=="liquid") {
                    // split tables as dataseries
                    $series=[];
                    foreach($points as $point) {
                        $series[$point['TableNum']][$point['RowNum']]=$point;
                    }

					// array of math symbols to check from in numeric values
					$msymbols=['>','<','≥','≤','≈'];

                    // process each series (data table)
					foreach($series as $serid=>$ser) {
                        $conds=$datums=$supps=$drvs=$anns=$ids=$cmpds=[];$hdr=null;
                        $scnt=count($ser); // count of rows in series
						foreach($ser as $pnt) {
                            // collect ids (sysid_tablenum_rownum)
                            $ids[$pnt['RowNum']]=$pnt['sysid_tablenum_rownum'];

                            // get table heading
                            if($pnt['RowNum']==1) {
                                if(!empty($pnt['Heading'])) {
                                    $hdr=$pnt['Heading'];
                                } elseif(!empty($headings[$serid])) {
									$hdr=$headings[$serid];
								} else {
                                    $hdr=null;
                                }
                            }

                            // get table system
							if(!empty($links['systems'][$serid])) {
								$sys=$links['systems'][$serid];
							} else {
								$sys=null;
							}

							// identify conditions (find fields that are conditions and get crosswalk data from crosswalks DB)
							foreach ($fields['conditions'] as $f) {
								if (isset($pnt[$f])) {
									$mult = 0;
                                    if (isset($pnt[$f.'Exp']))   { $mult+=$pnt[$f.'Exp'];unset($pnt[$f.'Exp']); }
                                    if (isset($pnt[$f.'Power'])) { $mult-=$pnt[$f.'Power'];unset($pnt[$f.'Power']); }
                                    if (isset($pnt[$f.'Note']))  { $anns['table'][$f][$pnt['RowNum']] = $pnt[$f.'Note'];unset($pnt[$f.'Note']); }
									// check value field for comparison math symbol
									$equality=null;
									foreach($msymbols as $msymbol) {
										if(stristr($pnt[$f],$msymbol)) {
											$equality=$msymbol;
											$pnt[$f]=str_replace($msymbol,'',$pnt[$f]);
										}
									}
									$value=$this->exponentialGen($pnt[$f]);$debugpnt=$pnt[$f];unset($pnt[$f]);
									if(!is_null($equality)) {
										$value['equality']=$equality;
									}
									if($mult!=0) {
                                        $value['exponent']+=$mult;
                                        $value['scinot']=$value['significand'].'e'.$value['exponent'];
                                        if($mult<0) {
                                            $value['dp']+=abs($mult);
                                            $value['error']=$value['error']*pow(10,$mult);
											$value['value'] = $value['value'] * pow(10, $mult);
											$value['text'] = sprintf('%.' . $value['dp'] . 'f', $value['value']);
										} else {
											if($f=='pressure') {
												if ($value['dp'] < $mult) {
													$value['dp'] = 0;
												} else {
													$value['dp'] -= $mult;
												}
												// update error, value, and text
												$value['error'] = $value['error'] * pow(10, $mult);
												$value['value'] = $value['value'] * pow(10, $mult);
												$value['text'] = sprintf('%.' . $value['dp'] . 'f', $value['value']);
											} else {
												debug($f);debug($debugpnt);debug($value);debug($mult);exit;
											}
										}
                                    }
                                    $conds[$f][$pnt['RowNum']] = $value;

									// get the unit
									$conds[$f][$pnt['RowNum']]['unit']=$this->checkunits($f,$pnt);

									// replace out errors estimated by exponentialGen function
									if (isset($pnt[$f.'Err'])) {
										$conds[$f][$pnt['RowNum']]['error'] = strval(trim($pnt[$f.'Err']) *pow(10,$mult));unset($pnt[$f.'Err']);
									} else {
										$conds[$f][$pnt['RowNum']]['error'] = '';
									}
								}
							}

							// identify data fields
							foreach ($fields['exptdata'] as $f) {
								if (isset($pnt[$f])) {
									$mult = 0;
									if (isset($pnt[$f.'Exp']))   { $mult+=$pnt[$f.'Exp'];unset($pnt[$f.'Exp']); }
									if (isset($pnt[$f.'Power'])) { $mult-=$pnt[$f.'Power'];unset($pnt[$f.'Power']); }
									if (isset($pnt[$f.'Note']))  { $anns['table'][$f][$pnt['RowNum']] = $pnt[$f.'Note'];unset($pnt[$f.'Note']); }
									// check value field for comparison math symbol
									$equality=null;
									foreach($msymbols as $msymbol) {
										if(stristr($pnt[$f],$msymbol)) {
											$equality=$msymbol;
											$pnt[$f]=str_replace($msymbol,'',$pnt[$f]);
										}
									}
									// process value
									$value=[];
									if($pnt[$f]=='-') {
										$value['text']=$pnt[$f];
									} elseif(preg_match('/([\d\.]+)-([\d\.]+)/',$pnt[$f],$m)) {
										$value=$this->exponentialGen($m[1]);
										$value['max']=$m[2];
										$debugpnt=$pnt[$f];unset($pnt[$f]);
									} else {
										$value=$this->exponentialGen($pnt[$f]);$debugpnt=$pnt[$f];unset($pnt[$f]);
									}
									// check for math equality symbol
									if(!is_null($equality)) {
										$value['equality']=$equality;
									}
									if($mult!=0) {
                                        $value['exponent']+=$mult;
                                        $value['scinot']=$value['significand'].'e'.$value['exponent'];
                                        if($mult<0) {
											$value['dp']+=abs($mult);
                                            $value['error'] = $value['error']*pow(10,$mult);
											$value['value'] = $value['value']*pow(10,$mult);
											$value['text'] = sprintf('%.' . $value['dp'] . 'f', $value['value']);
										} else {
                                            if($f=='V2/g1'||$f=='g1/V2'||$f=='ρ1'||$f=='g1/kg'||$f=='HenryCC'||'g2/g1'||($f=='V1/V2'&&$pnt[$f.'Units']=='mL1/L2')||$f=='D'||($f=='c1'&&$pnt[$f.'Units']=='mol m**-3')) {
                                                if ($value['dp'] < $mult) {
                                                    $value['dp'] = 0;
                                                } else {
                                                    $value['dp'] -= $mult;
                                                }
                                                // update error, value, and text
                                                $value['error'] = $value['error'] * pow(10, $mult);
                                                $value['value'] = $value['value'] * pow(10, $mult);
                                                $value['text'] = sprintf('%.' . $value['dp'] . 'f', $value['value']);
                                            } else {
                                                debug($f);debug($debugpnt);debug($value);debug($mult);exit;
                                            }
                                        }
                                    }

									$datums[$f][$pnt['RowNum']] = $value;

									// get the unit
									$datums[$f][$pnt['RowNum']]['unit']=$this->checkunits($f,$pnt);

                                    // replace out errors estimated by exponentialGen function
									if (isset($pnt[$f . 'Err'])) {
										$datums[$f][$pnt['RowNum']]['error'] = strval(trim($pnt[$f.'Err']) *pow(10,$mult));unset($pnt[$f.'Err']);
									} else {
										$datums[$f][$pnt['RowNum']]['error'] = '';
									}
								}
							}

							// Check to make sure there is data in the report
							if(empty($datums)&&!in_array($sysid,['37_75','72_65','77_25','77_27','69_10','69_16','69_22','69_31','69_48','69_49','69_50','69_51','69_67','69_72','69_79','69_86','69_94','69_97','69_102','69_110','69_113','69_116','69_121','69_124','69_164','69_189','69_232','69_254','69_260'])) {
							    echo "No experimental data<br/>";
							    debug($pnt);exit;
                            }

							// identify derived (compiler data)
							foreach ($fields['deriveddata'] as $f) {
								if (isset($pnt[$f])) {
									$mult = 0;
                                    if (isset($pnt[$f.'Exp']))   { $mult+=$pnt[$f.'Exp'];unset($pnt[$f.'Exp']); }
                                    if (isset($pnt[$f.'Power'])) { $mult-=$pnt[$f.'Power'];unset($pnt[$f.'Power']); }
                                    if (isset($pnt[$f.'Note']))  { $anns['table'][$f][$pnt['RowNum']] = $pnt[$f.'Note'];unset($pnt[$f.'Note']); }
									// check value field for comparison math symbol
									$equality=null;
									foreach($msymbols as $msymbol) {
										if(stristr($pnt[$f],$msymbol)) {
											$equality=$msymbol;
											$pnt[$f]=str_replace($msymbol,'',$pnt[$f]);
										}
									}
									// process value
									$value=[];
									if($pnt[$f]=='-') {
										$value['text'] = $pnt[$f];
									} elseif(preg_match('/([\d\.]+)-([\d\.]+)/',$pnt[$f],$m)) {
										$value=$this->exponentialGen($m[1]);
										$value['max']=$m[2];
										$debugpnt=$pnt[$f];unset($pnt[$f]);
									} else {
										$value=$this->exponentialGen($pnt[$f]);$debugpnt=$pnt[$f];unset($pnt[$f]);
									}
									// check for math equality symbol
									if(!is_null($equality)) {
										$value['equality']=$equality;
									}
									if($mult!=0) {
                                        $value['exponent']+=$mult;
                                        $value['scinot']=$value['significand'].'e'.$value['exponent'];
                                        if($mult<0) {
                                            $value['dp']+=abs($mult);
                                            $value['error'] = $value['error']*pow(10,$mult);
											$value['value'] = $value['value']*pow(10,$mult);
											$value['text'] = sprintf('%.' . $value['dp'] . 'f', $value['value']);
										} else {
											if($f=='g1/V2Comp') {
												if ($value['dp'] < $mult) {
													$value['dp'] = 0;
												} else {
													$value['dp'] -= $mult;
												}
												// update error, value, and text
												$value['error'] = $value['error'] * pow(10, $mult);
												$value['value'] = $value['value'] * pow(10, $mult);
												$value['text'] = sprintf('%.' . $value['dp'] . 'f', $value['value']);
											} else {
												debug($f);debug($debugpnt);debug($value);debug($mult);exit;
											}
                                        }
                                    }
                                    $drvs[$f][$pnt['RowNum']] = $value;

									// get the unit
									$drvs[$f][$pnt['RowNum']]['unit']=$this->checkunits($f,$pnt);

									// replace out errors estimated by exponentialGen function
									if (isset($pnt[$f.'Err'])) {
                                        $drvs[$f][$pnt['RowNum']]['error'] = strval(trim($pnt[$f.'Err']) *pow(10,$mult));unset($pnt[$f.'Err']);
									} else {
                                        $drvs[$f][$pnt['RowNum']]['error'] = '';
									}
								}
							}

                            // identify suppdata (compiler data)
                            foreach ($fields['suppdata'] as $f) {
                                if (isset($pnt[$f])) {
                                    $mult = 0;
                                    if (isset($pnt[$f.'Exp']))   { $mult+=$pnt[$f.'Exp'];unset($pnt[$f.'Exp']); }
                                    if (isset($pnt[$f.'Power'])) { $mult-=$pnt[$f.'Power'];unset($pnt[$f.'Power']); }
                                    if (isset($pnt[$f.'Note']))  { $anns['table'][$f][$pnt['RowNum']] = $pnt[$f.'Note'];unset($pnt[$f.'Note']); }
									// check value field for comparison math symbol
									$equality=null;
									foreach($msymbols as $msymbol) {
										if(stristr($pnt[$f],$msymbol)) {
											$equality=$msymbol;
											$pnt[$f]=str_replace($msymbol,'',$pnt[$f]);
										}
									}
									// process value
									$value=[];
									if($pnt[$f]=='-') {
										$value['text']=$pnt[$f];
									} elseif(preg_match('/([\d\.]+)-([\d\.]+)/',$pnt[$f],$m)) {
										$value=$this->exponentialGen($m[1]);
										$value['max']=$m[2];
										$debugpnt=$pnt[$f];unset($pnt[$f]);
									} else {
										$value=$this->exponentialGen($pnt[$f]);$debugpnt=$pnt[$f];unset($pnt[$f]);
									}
									// check for math equality symbol
									if(!is_null($equality)) {
										$value['equality']=$equality;
									}
									if($mult!=0) {
                                        $value['exponent']+=$mult;
                                        $value['scinot']=$value['significand'].'e'.$value['exponent'];
                                        if($mult<0) {
                                            $value['dp']+=abs($mult);
                                            $value['error']=$value['error']*pow(10,$mult);
											$value['value'] = $value['value'] * pow(10, $mult);
											$value['text'] = sprintf('%.' . $value['dp'] . 'f', $value['value']);
										} else {
                                            // TODO
                                            debug($value);debug($mult);exit;
                                        }
                                    }
                                    $supps[$f][$pnt['RowNum']] = $value;

									// get the unit
									$supps[$f][$pnt['RowNum']]['unit']=$this->checkunits($f,$pnt);

									// replace out errors estimated by exponentialGen function
									if (isset($pnt[$f.'Err'])) {
                                        $supps[$f][$pnt['RowNum']]['error'] = strval(trim($pnt[$f.'Err']) *pow(10,$mult));unset($pnt[$f.'Err']);
                                    } else {
                                        $supps[$f][$pnt['RowNum']]['error'] = '';
                                    }
                                }
                            }

                            // identify annotations
                            if(isset($pnt['phase']))        { $anns['general']['phase'][$pnt['RowNum']]=$pnt['phase'];unset($pnt['phase']); }
							if(isset($pnt['solvent']))      { $anns['general']['solvent'][$pnt['RowNum']]=$pnt['solvent'];unset($pnt['solvent']); }
							if(isset($pnt['comments']))     { $anns['general']['comments'][$pnt['RowNum']]=$pnt['comments'];unset($pnt['comments']); }

							// not needed but kept for completeness
							//if(isset($pnt['Compound2']))    { $anns['general']['Compound2'][$pnt['RowNum']]=$pnt['Compound2'];unset($pnt['Compound2']); }
							//if(isset($pnt['Compound3']))    { $anns['general']['Compound3'][$pnt['RowNum']]=$pnt['Compound3'];unset($pnt['Compound3']); }

							// chemicals that are by row in a table and needed to split out...
							if(isset($pnt['ChemicalName'])) {
							    $cmds[$pnt['RowNum']]=$pnt['ChemicalName'];unset($pnt['ChemicalName']);
							}
                        }

						// add table to conditions idx
						foreach($conds as $cond=>$vals) {
                            $newvals=[];
                            foreach($vals as $idx=>$val) {
                                $newvals[$serid.':'.$idx]=$val;
                            }
                            if(isset($allconds[$cond])) {
                                $allconds[$cond]=array_merge($allconds[$cond],$newvals);
                            } else {
                                $allconds[$cond]=$newvals;
                            }
                        }

						// footnotes
						if(isset($anns['table'])&&!empty($anns['table'])) {
							$anns['column']=[];
							$anns['rows']=[];
							foreach($anns['table'] as $prop=>$rows) {
								//debug($prop);debug($rows);
								$ncols=null;
								if(!empty(preg_grep('/;/', $rows))) {
									// notes field that has multiple notes per cell in a least some of the rows
									// determine # rows
									$cols=0;
									foreach($rows as $row) {
										$count=substr_count($row,";");
										if(($count+1)>$cols) { $cols=$count+1; }
									}
									// split and process
									foreach($rows as $idx=>$row) {
										$row=str_replace("; ",";",$row);
										$chunks=explode(";",$row);
										for($x=0;$x<$cols;$x++) {
											if(isset($chunks[$x])) {
												$ncols[$x][$idx]=$chunks[$x];
											} else {
												$ncols[$x][$idx]=null;
											}
										}
									}
								} else {
									$ncols[0]=$rows;
								}
								foreach($ncols as $rows) {
									$urows=array_unique($rows);
									if(count($urows)==1&&count($rows)==$scnt) {
										// all notes same -> column annotation
										$urows=array_values($urows); // reset array indexes
										$fnote=$urows[0];$updated=null;
										foreach($notes as $note) {
											//debug($note);debug($fnote);
											if($note['footnote']==$fnote&&($note['TableNum']==$serid||is_null($note['TableNum']))) {
												$anns['column'][$prop]=$note['longnote'];$updated='yes';break;
											}
										}
										// if the note is not a footnote save as is
										if(is_null($updated)) {
											$anns['column'][$prop]=$fnote;
										}
									} else {
										// notes different (including null)
										$updated=null;
										foreach($rows as $idx=>$row) {
											foreach($notes as $note) {
												//debug($note);debug($fnote);
												if($note['footnote']==$row&&($note['TableNum']==$serid||is_null($note['TableNum']))) {
													$anns['rows'][$prop][$idx]=$note['longnote'];$updated='yes';break;
												}
											}
											// if the note is not a footnote save as is
											if(is_null($updated)) {
												$anns['rows'][$prop][$idx]=$row;
											}
										}
									}
								}
							}
						}

						// add series to dataset
						$datasets[$serid]=['ids'=>$ids,'title'=>$hdr,'system'=>$sys,'cons'=>$conds,'data'=>$datums,'sups'=>$supps,'drvs'=>$drvs,'anns'=>$anns];
					}
                } elseif($datatype=="gas") {
                    // TODO
                }
                $sds->setontlinks($ontlinks);
                //debug($datasets);exit;

                // deduplicate conditions (allconds)
                $tmp=$allconds;$allconds=[];
                //debug($tmp);
                foreach($tmp as $type=>$vals) {
                    $allconds[$type]=[];
                    foreach($vals as $idx=>$val) {
                        if(!isset($allconds[$type][$val['text']])) {
                            $allconds[$type][$val['text']]['meta']=$val;
                            $allconds[$type][$val['text']]['rows']=[0=>$idx];
                        } else {
                            $allconds[$type][$val['text']]['rows'][]=$idx;
                        }
                    }
                    ksort($allconds[$type]);
                }

                $condidx=1;
                foreach($allconds as $prop=>$vals) {
                    $propmeta=$this->Property->find('first',['conditions'=>['datafield like'=>"%'".$prop."'%"],'contain'=>['Quantity'=>'Unit'],'recursive'=>-1]);
					if($propmeta['Quantity']['name']=='Temperature') {
                        $tmp1=null;
                        if(isset($errs['temp'])) { $tmp1=$errs['temp']; }
                    } else {
						$tmp1=null;
						if(isset($errs['err_other'])) { debug($allconds);debug($errs); }
					}
					$unitid=null;
					foreach($propmeta['Quantity']['Unit'] as $unit) {
						if(stristr($unit['field'],$prop)) {
							$unitid=$unit['id'];
						}
					}
					$propid=$ontlinks['conditions'][$prop];
					$facets['sci:condition'][$condidx]=['property'=>$propmeta['Property']['name'],'propid'=>$propid,'unit'=>$unitid,'value'=>$vals,'errors'=>$tmp1];
					$condidx++;
                }
				//debug($facets);exit;

                // data
                $series=[];
                foreach($datasets as $serid=>$dataset) {
                    $series[$serid]['title']=$dataset['title'];
					$series[$serid]['system']=$dataset['system'];
					$series[$serid]['ids']=$dataset['ids'];
                    $series[$serid]['data']=$dataset['data'];
                    $series[$serid]['sups']=$dataset['sups'];
                    $series[$serid]['drvs']=$dataset['drvs'];
                    $series[$serid]['anns']=$dataset['anns'];
                }
				//debug($series);
				$sds->setdataseries($datasets);
                $sds->setfacets($facets);
                $sds->settoc($toc);

                // Sources
                // SDS Volume
                $vol=$datum['Sysnamelk']['Volume'];
                if($vol['inJPCRD']) {
                    // Go get the DOI
                    $bib=$vol['title']." ".$vol['authors']." ".$vol['pub_year']." ".$vol['volume']." ".$vol['firstPage'];
                    $src=[];
                    $src['type']="critically evaluated report";
                    $src['citation']=$bib;
                    $src['url']=$this->Api->doibycite($bib);
                    $sources[]=$src;
                    //debug($src);exit;
                } else {
                    $src=[];
                    $src['type']="critically evaluated report";
                    $src['citation']=$vol['Series_name'];
                    $sources[]=$src;
                }

                // Original research paper
                // Use data already in Report and References tables
                $report=$this->Report->find('first',['conditions'=>['sysid'=>$sysid],'contain'=>['Reference'=>['conditions'=>[]]],'recursive'=>-1]);
                foreach($report['Reference'] as $r) {
                	if($r['ReferencesReport']['role']=='original') {
						$ref=[];
						$ref['type']=$r['type'];
						$ref['citation']=$r['raw'];
						if(!is_null($r['doi'])) {
							$ref['doi']=$r['doi'];
						} elseif(!is_null($r['url'])) {
							$ref['url']=$r['url'];
						}
						$sources[]=$ref;
					}
                }
                $sds->setsources($sources);

                // Rights
                $rights = [];
                $rights['holder'] = 'NIST & IUPAC';
                $rights['license'] = 'http://creativecommons.org/publicdomain/zero/1.0/';
                $rights['url'] = 'https://srdata.nist.gov/solubility';
                $sds->setrights($rights);

                // Output
				//debug($series);debug($conds);debug($datums);debug($supps);debug($anns);exit;
				if($output=='browser') {
					$sd=$sds->asarray();
					debug($sd);
				} else {
					$sd=$sds->asjsonld();
					header('Content-Type: application/ld+json');
					header('Content-Disposition: attachment; filename="'.$id.'.jsonld"');
					header('Content-Length: '.strlen($sd));
					echo $sd;exit;
				}

				// send to nistsdm
            } else {
				// Critical Evaluation

				// Instantiate variables
				$sys=$chms=$cmps=$meths=$facets=$aspects=$links=$toc=$sources=$roles=$allconds=$points=[];
				$datatype=null;

				// Cleanup
				// Remove empty fields from the data tables
				if(!empty($datum['Summarytable'])) {
					foreach($datum['Summarytable'] as $idx=>$datapoint) {
						foreach($datapoint as $field=>$value) {
							if(is_null($value)) {
								unset($datum['Summarytable'][$idx][$field]);
							}
						}
					}
				}
				$points=$datum['Summarytable'];

				// identify dataype (gas, liquid, solid) using volumes
				if(in_array(substr($sysid,1,2),[66,73])) {
					$datatype="solid";
				} elseif(in_array(substr($sysid,1,2),[57,62,70])) {
					$datatype="gas";
				} else {
					$datatype="liquid";
				}

				// Consistency checks
				// Chemical system check
				if($datum['Mainsys']['NO_OF_COMPONENT']!=count($datum['Comp'])) {
					exit('Number of substances in system is inconsistent');
				}

				// Data check
				if(is_null($datatype)) {
					exit('No data found');
				} else {
					if($datatype=='liquid') {
						foreach($datum['Summarytable'][0] as $field) {
							if(is_null($field)) {
								debug($datum);exit;
							}
						}
					}
				}


				// Process the data

				// Metadata
				$id=$datum['Mainsys']['SYS_ID'];

				// this creates a instance of the Scidata class
				$sds = new $this->Scidata;
				$sds->setnspaces($nspaces);
				$sds->setpath("https://scidata.coas.unf.edu/sds/");
				$sds->setbase($id."/");
				$sds->setid("https://scidata.coas.unf.edu/sds/".$id."/");
				$sds->setpid("sds:".$id);
				$sds->setdiscipline("chemistry");
				$sds->setsubdiscipline("physical chemistry");
				$meta=['title'=>'Solubility data for '.$datum['Sysnamelk']['SYS_NAME'],
					'publisher'=>'IUPAC Subcommittee on Solubility and Equilibrium data (SSED)',
					'description'=>'Critically reviewed solubility data reported in the IUPAC Solubility Data Series'];
				$sds->setmeta($meta);
				$url=Configure::read('url.system.detail');
				$related=[0=>str_replace('*sysID*',$id,$url)];
				$sds->setkeywords(['solubility','IUPAC','critically evaluated data']);
				$sds->setrelated($related);

				// Report information

				// specific to this project, not normally added to scidata
				$rep=[];
				if(!empty($datum['Exptdata'])) {
					$expt=$datum['Exptdata'][0];
					if(!is_null($expt['T_K']))              { $rep['conditions']['temp_k']=$expt['T_K']; }
					if(!is_null($expt['pH']))               { $rep['conditions']['ph']=$expt['pH']; }
					if(!is_null($expt['pressure']))         { $rep['conditions']['pressure']=$expt['pressure']; }
					if(!is_null($expt['concentration']))    { $rep['conditions']['concentration']=$expt['concentration']; }
					if(!is_null($expt['prepared_by']))      { $rep['annotations']['prepared_by']=$expt['prepared_by']; }
					if(!is_null($expt['REMARKS']))          { $rep['annotations']['remarks']=str_replace(["<br/>","<p>"]," ",$expt['REMARKS']); }
					if(!is_null($expt['comments']))         { $rep['updates']['exptdata']=$expt['comments']; }
				}
				if(!empty($datum['Commenttb'])) {
					$cmmt=$datum['Commenttb'];
					if(!is_null($cmmt['Reference1']))       { $rep['citations'][]=str_replace("  "," ",$cmmt['Reference1']); }
					if(!is_null($cmmt['Reference2']))       { $rep['citations'][]=str_replace("  "," ",$cmmt['Reference2']); }
					if(!is_null($cmmt['Reference3']))       { $rep['citations'][]=str_replace("  "," ",$cmmt['Reference3']); }
					if(!is_null($cmmt['Reference4']))       { $rep['citations'][]=str_replace("  "," ",$cmmt['Reference4']); }
					if(!is_null($cmmt['comments']))         { $rep['updates']['commenttb']=$cmmt['comments']; }
				}
				foreach($points as $point) {
					if(!empty($point['edits'])) {
						$rep['edits'][$point['sysid_tablenum_rownum']]=$point['edits'];
					}
				}
				debug($rep);
				$sds->setreport($rep);

				// Footnotes
				$notes=$datum['Footnote'];

				$sd=$sds->asarray();
				debug($sd);

				debug($datum);
			}
        }
        exit;
	}

	/**
	 * function to set the unit correctly
	 * @param $f
	 * @param $pnt
	 * @return string
	 */
	public function checkunits($f,$pnt) {
		$propmeta=$this->Property->find('first',['conditions'=>['datafield like'=>"%'".$f."'%"],'contain'=>['Quantity'=>'Unit'],'recursive'=>-1]);
		$unit="";
		if (isset($pnt[$f.'Units'])) {
			if(!empty($propmeta)) {
				if(!empty($propmeta['Quantity']['Unit'])) {
					foreach($propmeta['Quantity']['Unit'] as $u) {
						if(stristr($u['encodings'],"'".$pnt[$f.'Units']."'")) {
							$unit=$u['qudt'];break;
						}
					}
					if($unit=="") {
						echo "Which unit?";
						debug($propmeta['Quantity']['Unit']);debug($pnt[$f.'Units']);exit;
					}
				} else {
					$unit = $pnt[$f.'Units'];
				}
			} else {
				$unit = $pnt[$f.'Units'];
			}
		} else {
			if(!empty($propmeta)) {
				if(!empty($propmeta['Quantity']['Unit'])) {
					if(count($propmeta['Quantity']['Unit'])==1) {
						$unit=$propmeta['Quantity']['Unit'][0]['qudt'];
					} else {
						foreach($propmeta['Quantity']['Unit'] as $u) {
							if(stristr($u['field'],"'".$f."'")) {
								$unit=$u['qudt'];break;
							}
						}
						if($unit=="") {
							echo "Which unit?";
							debug($propmeta['Quantity']['Unit']);debug($f);exit;
						}
					}
				} else {
					$unit = '';
				}
			} else {
				$unit = '';
			}
		}
		return $unit;
	}

	public function getpages()
    {
        $pages=$this->Mainsys->find('list',['fields'=>['id','SYS_ID'],'order'=>'SYS_ID']);
        $url='https://srdata.nist.gov/solubility/sol_detail.aspx?goBack=Y&sysID=';
        foreach($pages as $page) {
            $filename=WWW_ROOT.'srd'.DS.$page.'.html';
            if(!file_exists($filename)) {
                $text=file_get_contents($url.$page);
                $file = new File($filename, true, 0777);
                $file->write($text,'w');
                $file->close();
                echo 'Done '.$page."<br/>";
            } else {
                echo 'Exists '.$page."<br/>";
            }
        }
        exit;
    }

    /**
     * Generates a exponential number removing any zeros at the end not needed
     * @param $string
     * @return array
     */
    private function exponentialGen($string) {
    	$return=[];
        $return['text']=$string;
        $return['value']=floatval($string);
        if($string==0) {
            $return+=['dp'=>0,'scinot'=>'0e+0','exponent'=>0,'significand'=>0,'error'=>null,'sf'=>0];
        } elseif(stristr($string,'E')) {
            list($man,$exp)=explode('E',$string);
            if($man>0){
                $sf=strlen($man)-1;
            } else {
                $sf=strlen($man)-2;
            }
            $return['scinot']=$string;
            $return['error']=pow(10,$exp-$sf+1);
            $return['exponent']=$exp;
            $return['significand']=$man;
            $return['dp']=$sf;
        } else {
            $string=str_replace([",","+"],"",$string);
            $num=explode(".",$string);
            $neg=false;
            if(stristr($num[0],'-')) {
                $neg=true;
            }
            // If there is something after the decimal
            if(isset($num[1])){
                $return['dp']=strlen($num[1]);
                if($num[0]!=""&&$num[0]!=0) {
                    // All digits count (-1 for period)
                    if($neg) {
                        // substract 1 for the minus sign and 1 for decimal point
                        $return['sf']=strlen($string)-2;
                        $return['exponent']=strlen($num[0])-2;
                    } else {
                        $return['sf']=strlen($string)-1;
                        $return['exponent']=strlen($num[0])-1;
                    }
                    // Exponent is based on digit before the decimal -1
                } else {
                    // Remove any leading zeroes after decimal and count string length
                    $return['sf']=strlen(ltrim($num[1],'0'));
                    // Count leading zeros
                    preg_match('/^(0*)[1234567890]+$/',$num[1],$match);
                    $return['exponent']=-1*(strlen($match[1]) + 1);
                }
                $return['scinot']=sprintf("%." .($return['sf']-1). "e", $string);
                $s=explode("e",$return['scinot']);
                $return['significand']=$s[0];
                $return['error']=pow(10,$return['exponent']-$return['sf']+1);
            } else {
                $return['dp']=0;
                $return['scinot']=sprintf("%." .(strlen($string)-1). "e", $string);
                $s=explode("e",$return['scinot']);
                $return['significand']=$s[0];
                $return['exponent'] = $s[1];
                $z=explode(".",$return['significand']);
                $return['sf']=strlen($return['significand'])-1;
                // Check for negative
                if(isset($z[1])) {
                    $return['error']=pow(10,strlen($z[1])-$s[1]-$neg); // # SF after decimal - exponent
                } else {
                    $return['error']=pow(10,0-$s[1]); // # SF after decimal - exponent
                }
            }
        }
		return $return;
    }

    /**
     * Get crosswalk info for fields that are a specific $type
     * @param $type
     * @param $fields
     * @param $nspaces
     * @param $ontlinks
     */
    private function getcw($type,&$fields,&$nspaces,&$ontlinks) {
        $c=['Ontterm'=>['Nspace']];
        $metas = $this->Sol->find('all',['contain'=>$c, 'recursive'=>-1]);
        //debug($metas);
        $fields[$type]=$ontlinks[$type]=[];
        foreach ($metas as $meta) {
            if($meta['Sol']['sdsubsection']==$type) {
                $fields[$type][]=$meta['Sol']['field'];
            }
            if($meta['Ontterm']['sdsubsection']==$type&&$meta['Sol']['sdsubsection']==null) {
                $fields[$type][]=$meta['Sol']['field'];
            }
            if(in_array($meta['Sol']['field'],$fields[$type])) {
                $ontlinks[$type][$meta['Sol']['field']]=$meta['Ontterm']['url'];
                $nspaces[$meta['Ontterm']['Nspace']['ns']]=$meta['Ontterm']['Nspace']['path'];
            }
        }
        return;
    }

	/**
	 * Get crosswalk info for fields that are a specific $type by section
	 * @param $type
	 * @param $fields
	 * @param $nspaces
	 * @param $ontlinks
	 */
	private function getcwbysect($type,&$fields,&$nspaces,&$ontlinks) {
		$c=['Ontterm'=>['Nspace']];$table='Ccdc';
		$metas = $this->$table->find('all',['contain'=>$c, 'recursive'=>-1]);
		$fields[$type]=$ontlinks[$type]=[];$flist=[];
		foreach ($metas as $meta) {
			if($meta[$table]['sdsection']==$type) {
				$fields[$type][$meta[$table]['sdsubsection']][]=$meta[$table]['field'];
				$ontlinks[$type][$meta[$table]['sdsubsection']][$meta[$table]['field']]=$meta['Ontterm']['url'];
				$flist[]=$meta[$table]['field'];
			}
			if($meta['Ontterm']['sdsection']==$type&&$meta[$table]['sdsection']==null) {
				$fields[$type][$meta['Ontterm']['sdsubsection']][]=$meta[$table]['field'];
				$ontlinks[$type][$meta['Ontterm']['sdsubsection']][$meta[$table]['field']]=$meta['Ontterm']['url'];
				$flist[]=$meta[$table]['field'];
			}
			if(in_array($meta[$table]['field'],$flist)) {
				$nspaces[$meta['Ontterm']['Nspace']['ns']]=$meta['Ontterm']['Nspace']['path'];
			}
		}
		return;
	}

	/**
	 * Get crosswalk info for fields that are a specific $type by subsection
	 * @param $type
	 * @param $fields
	 * @param $nspaces
	 * @param $ontlinks
	 */
	private function getcwbysubsect($type,&$fields,&$nspaces,&$ontlinks) {
		$c=['Ontterm'=>['Nspace']];$table='Ccdc';
		$metas = $this->$table->find('all',['contain'=>$c, 'recursive'=>-1]);
		$fields[$type]=$ontlinks[$type]=[];
		foreach ($metas as $meta) {
			if($meta[$table]['sdsubsection']==$type) {
				$fields[$type][]=$meta[$table]['field'];
			}
			if($meta['Ontterm']['sdsubsection']==$type&&$meta[$table]['sdsubsection']==null) {
				$fields[$type][]=$meta[$table]['field'];
			}
			if(in_array($meta[$table]['field'],$fields[$type])) {
				$ontlinks[$type][$meta[$table]['field']]=$meta['Ontterm']['url'];
				$nspaces[$meta['Ontterm']['Nspace']['ns']]=$meta['Ontterm']['Nspace']['path'];
			}
		}
		return;
	}

}
