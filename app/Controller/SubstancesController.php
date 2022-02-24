<?php

/**
 * Class SubstancesController
 * Actions related to dealing with chemical substances
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class SubstancesController extends AppController
{
    public $uses=['Substance','Dataset','File','Identifier','SubstancesSystem','System',
		'Pubchem.Compound','CommonChem.Cas','Chemspider.Rdf','Wikidata.Wiki'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('index','view');
    }

	/**
	 * view a list of substances
	 * @return void
	 */
	public function index()
	{
		$data=$this->Substance->find('list',['fields'=>['id','name','first'],'order'=>['first','name']]);
		$this->set('data',$data);
	}

	/**
     * view a substance
     * @param string|int $id
	 * @return void
	 */
    public function view(string $id)
    {
        $contain=['Identifier','System'=>['order'=>['name'],'Dataset']];
        if(is_numeric($id)) {
			$data=$this->Substance->find('first',['conditions'=>['Substance.id'=>$id],'contain'=>$contain]);
		} else {
			$data=$this->Substance->find('first',['conditions'=>['Substance.name'=>$id],'contain'=>$contain]);
		}
		$this->set('data',$data);
    }

	// functions requiring login (not in Auth::allow)

	/**
	 * add a new substance
	 * (view file not formatted using BootStrap)
	 * @return void
	 */
	public function add()
	{
		if($this->request->is('post')) {
			$this->Substance->create();
			if ($this->Substance->save($this->request->data)) {
				$this->Flash->set('Substance created.');
				$this->redirect(['action' => 'index']);
			} else {
				$this->Flash->set('Substance could not be created.');
			}
		}
	}

	/**
     * update a substance
	 * (view file not formatted using BootStrap)
	 * @return void
	 */
    public function update($id)
    {
        if($this->request->is('post')) {
            $data=['id'=>$id]+$this->request->data;
			if ($this->Substance->save($data)) {
                $this->Flash->set('Substance updated.');
                $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->set('Substance could not be updated.');
            }
        } else {
            $data=$this->Substance->find('first',['conditions'=>['Substance.id'=>$id],'recursive'=>3]);
            $this->set('data',$data);
            $this->set('id',$id);
        }
    }

    /**
     * delete a substance
	 * @return void
	 */
    public function delete($id)
    {
        $this->Substance->delete($id);
        $this->redirect(['action' => 'index']);
    }

	// special functions (mostly single use)

    /**
     * get meta for substances
     * @param $id
	 * @return void
	 */
    public function meta($id=null)
    {
        if(is_null($id)) {
			// all substances
            $cs=$this->Substance->find('all',['recursive'=>1,'order'=>['name'],'contain'=>['Identifier'=>['conditions'=>['type'=>'casrn']]]]);
            foreach($cs as $c) { $this->Substance->meta($c,true); }
        } else {
			// single substance
            $c=$this->Substance->find('first',['recursive'=>1,'conditions'=>['id'=>$id],'contain'=>['Identifier'=>['conditions'=>['type'=>'casrn']]]]);
            $this->Substance->meta($c,true);
        }
        exit;
    }

    /**
     * clean up the names of substances
	 * @return void
	 */
    public function cleanname()
    {
        // does not handle non-capitlaization of these prefixes ['cis','trans','alpha','beta','gamma']
        $subs=$this->Substance->find('list',['fields'=>['id','name'],'order'=>'name']);
        foreach($subs as $id=>$name) {
            $this->Substance->id=$id;
            $this->Substance->saveField('name',ucfirst($name));
            $this->Substance->clear();
            echo "Done ".ucfirst($name)."<br/>";
        }
        exit;
    }

    /**
     * get meta for chemicals using inchi string
	 * @return void
	 */
    public function inchi()
    {
        $cs=$this->Substance->find('all',['recursive'=>1,'order'=>['name'],'contain'=>['Identifier'=>['conditions'=>['type'=>'inchi']]]]);
        foreach($cs as $c) {
            $i=$c['Identifier'];$s=$c['Substance'];
            if(empty($i)) { continue; }

			// Search PubChem
            $cid=$this->Chemical->cid('inchi',$i[0]['value']);
            if($cid) {
                // Add the PubChem ID
                $test=$this->Identifier->find('first',['conditions'=>['substance_id'=>$s['id'],'type'=>'pubchemId']]);
                if(empty($test)) {
                    $this->Identifier->add(['substance_id'=>$s['id'],'type'=>'pubchemId','value'=>$cid]);
                }
                echo "<h3>".$s['name']." (PubChem)</h3>";
                echo "<ul>";
                $ps=['iupacname'=>'IUPACName','inchi'=>'InChI','inchikey'=>'InChIKey','mw'=>'MolecularWeight'];
                foreach($ps as $t=>$p) {
                    if($t=='mw') {
                        // Check to see if the value is already in the DB
                        $test=$this->Substance->find('list',['fields'=>['id',$t],'conditions'=>['id'=>$s['id']]]);
                        if($test[$s['id']]==''||$test[$s['id']]==0) {  // =='' covers NULL also
                            $meta=$this->Chemical->property($p,$cid);
                            if(isset($meta[$p])) {
                                echo "<li>".$p.": ".$meta[$p]."</li>";
                                $this->Substance->save(['id'=>$s['id'],$t=>$meta[$p]]);
                                $this->Substance->clear();
                            }
                        }
                    } else {
                        // Check to see if the value has already been added
                        $test=$this->Identifier->find('first',['conditions'=>['substance_id'=>$s['id'],'type'=>$t]]);
                        if(empty($test)) {
                            $meta=$this->Chemical->property($p,$cid);
                            if(isset($meta[$p])) {
                                echo "<li>".$p.": ".$meta[$p]."</li>";
                                $this->Identifier->add(['substance_id'=>$s['id'],'type'=>$t,'value'=>$meta[$p]]);
                            }
                        }
                    }
                }
                echo "</ul>";
            }
            // Search ChemSpider
            $meta=$this->Rdf->search($i[0]['value']);
            if($meta) {
                echo "<h3>".$s['name']." (ChemSpider)</h3>";
                echo "<ul>";
                $ps=['chemspiderId'=>'id','iupacname'=>'name','smiles'=>'smiles','inchi'=>'inchi','inchikey'=>'inchikey'];
                foreach($ps as $t=>$p) {
                    if(isset($meta[$p])) {
                        echo "<li>".$p.": ".$meta[$p]."</li>";
                        $test=$this->Identifier->find('first',['conditions'=>['substance_id'=>$s['id'],'type'=>$t]]);
						if(empty($test)) {
							$this->Identifier->add(['substance_id'=>$s['id'],'type'=>$t,'value'=>$meta[$p]]);
						}
                    }
                }
                //debug($meta);
                echo "</ul>";
            }
            // Cleanup
            echo "<h3>Cleanup</h3>";
            echo "<ul>";
            $pcid=$this->Identifier->find('list',['fields'=>['substance_id','value'],'conditions'=>['substance_id'=>$s['id'],'type'=>'pubchemId']]);
            if(empty($pcid)) {
                $cid=$this->Chemical->cid('name',$meta['inchikey']);
                if($cid) {
                    $this->Identifier->add(['substance_id'=>$s['id'],'type'=>'pubchemId','value'=>$cid]);
                } else {
                    $cid='';
                }
            } else {
                $cid=$pcid[$s['id']];
            }
            echo "<li>CID: ".$cid."</li>";
            $mw=$this->Substance->find('list',['fields'=>['id','mw'],'conditions'=>['id'=>$s['id']]]);
            if($mw[$s['id']]=='') {
                // Use inchikey from ChemSpider search to get molweight from PubChem
                $mw=$this->Chemical->property('MolecularWeight',$cid);
                if($mw) {
                    $this->Substance->save(['id'=>$s['id'],'mw'=>$mw['MolecularWeight']]);
                    $this->Substance->clear();
                }
                echo "<li>MW: ".$mw['MolecularWeight']."</li>";
                //debug($mw);exit;
            }
            echo "</ul>";
        }
        exit;
    }

    /**
     * get data from opsin using the API at
	 * http://opsin.ch.cam.ac.uk/opsin/
	 * @return void
	 */
    public function opsin()
    {
        $cs = $this->Substance->find('all', ['recursive' => 1, 'fields' => ['id', 'name'],'contain'=>['Identifier'=>['conditions'=>['type'=>'inchi']]]]);
        foreach ($cs as $c) {
            $name=$c['Substance']['name'];$id=$c['Substance']['id'];
            if(empty($c['Identifier'])) {
                $path = "http://opsin.ch.cam.ac.uk/opsin/";
                $json = file_get_contents($path . rawurlencode($name) . ".json");
                echo "<h3>".$name." (OPSIN)</h3>";
                if(substr($json,0,1)=="{") {
                    $meta=json_decode($json,true);
                    $ps=['inchi'=>'stdinchi','inchikey'=>'stdinchikey','smiles'=>'smiles'];
                    foreach($ps as $t=>$p) {
                        $test=$this->Identifier->find('list',['fields'=>['substance_id','value'],'conditions'=>['substance_id'=>$id,'type'=>$t]]);
                        if(empty($test)) {
                            echo "<li>".$p.": ".$meta[$p]."</li>";
                            $this->Identifier->add(['substance_id'=>$id,'type'=>$t,'value'=>$meta[$p]]);
                        }
                    }
                } else {
                    echo "<li>Not found: ".$name."</li>";
                }
            }
        }
        exit;
    }

	/**
	 * generate substance stats
	 * @return void
	 */
    public function stats()
    {
        $cs = $this->Substance->find('all',['contain' => ['Identifier'=>['fields'=>['type','value']]],'recursive'=>1]);
        $data=[];$stats=[];
        $stats['total']=count($cs);
        foreach ($cs as $c) {
            $is=$c['Identifier'];$s=$c['Substance'];
            foreach($is as $i) {
                $s[$i['type']]=$i['value'];
                $stats[$i['type']]++;
            }
            $data[$s['id']]=$s;
            if(!is_null($s['formula']))     { $stats['formula']++; }
            if(!is_null($s['mw']))   { $stats['mw']++; }
            debug($data);debug($stats);debug($s);exit;
        }
        exit;
    }

	/**
	 * check common chemistry for substance casrn's
	 * @param int $limit
	 * @return void
	 */
    public function chkcc(int $limit=10000)
	{
		// initially CC API was allowing search based on InChIKeys, now it's not :( - so using casrn, then name
		$cnks=$this->Substance->find('list',['fields'=>['id','casnamekey'],'conditions'=>['incc'=>null],'order'=>'id','limit'=>$limit]);
		foreach($cnks as $subid=>$cnk) {
			list($cas,$name,)=explode("|",$cnk);$term=null;$hit=null;
			if($cas=='NULL') {
				$hit=$this->Cas->search($name);$term=$name;
			} else {
				$hit=$this->Cas->search($cas);$term=$cas;
			}
			$this->Substance->id=$subid;
			if(!$hit) {
				$this->Substance->saveField('incc','no');
				echo $term." not in CC<br/>";
			} elseif($cas=='NULL') {
				//debug($hit);debug($cn);exit;
				$this->Substance->saveField('casrn',$hit);
				$this->Substance->saveField('incc','samecas');
				echo $term." added to DB (based on name)<br/>";
			} elseif($hit==$cas) {
				$this->Substance->saveField('incc','samecas');
				echo $term." same CAS as CC<br/>";
			} else {
				$this->Substance->saveField('incc','diffcas');
				// manully correction made for this situation after confirmation on commonchemistry.com
				echo $term." different CAS to CC<br/>";debug($cnk);debug($hit);exit;
			}
		}
		// update cassrc field once checks finished
		$subids=$this->Substance->find('list',['fields'=>['id'],'conditions'=>['incc'=>'samecase']]);
		foreach($subids as $subid) {
			$save=['id'=>$subid,'cassrc'=>'cas'];
			$this->Substance->save($save);
		}
		exit;
	}

	/**
	 * check pubchem for substance casrn's
	 * @param int $limit
	 * @return void
	 */
	public function chkpc(int $limit=4000)
	{
		$f=['id','casnamekey'];$c=['NOT'=>['casrn'=>null],'cassrc'=>null];
		$cnks=$this->Substance->find('list',['fields'=>$f,'conditions'=>$c,'order'=>'id','limit'=>$limit]);
		foreach($cnks as $subid=>$cnk) {
			list($cas,$name,$key) = explode("|", $cnk);$term = null;$cid=null;$inpc='no';
			// get pubchem cid for compound
			if(is_null($cid)) {
				$cid=$this->Compound->cid('name',$name);$term=$name;
			} elseif($cas=='NULL') {
				$cid=$this->Compound->cid('casrn',$cas);$term=$cas;
			} else {
				$cid=$this->Compound->cid('inchikey',$key);$term=$key;
			}
			// check pubchem synonyms for this compound for the casrn
			$nymlist=$this->Compound->property('synonyms',$cid);
			$nyms=explode("|",$nymlist);
			if($cas!='NULL') {
				if(in_array($cas,$nyms)) { $inpc='yes'; }
			} else {
				// search for string that looks like a casrn and test
				$regex='/\d{2,8}-d{2}-\d/';
				foreach($nyms as $nym) {
					if(stristr($nym,'-')) {
						preg_match($regex,$nym,$m);
						if(!empty($m)) { debug($m);debug($nyms);exit; }
					}
				}
			}
			if($inpc=="yes") {
				$save=['id'=>$subid,'cassrc'=>'pubchem'];
				$this->Substance->save($save);
				echo $term." CASRN found in PubChem<br/>";//exit;
			} else {
				echo $key." not found<br/>";//debug($cid);debug($cas);debug($nyms);
			}
		}
		exit;
	}

	/**
	 * get molecular weights from pubchem or calculate average mass at https://www.lfd.uci.edu/~gohlke/molmass/
	 * @return void
	 */
	public function getmw()
	{
		$formulae=$this->Substance->find('list',['fields'=>['id','name','formula'],'order'=>'formula','conditions'=>['mw'=>null]]);
		foreach($formulae as $formula=>$subids) {
			$mw=$this->Compound->getmw($formula);
			if(!is_null($mw)) {
				echo $formula.' has molecular weight: '.$mw.'<br/>';
				foreach($subids as $subid=>$name) {
					$save=['id'=>$subid,'mw'=>$mw,'mwsrc'=>'pubchem'];
					$this->Substance->save($save);
				}
			} else {
				echo "PubChem does not have any compounds with formula ".$formula."!<br/>";
				$mw=$this->Substance->ucimw($formula);
				foreach($subids as $subid=>$name) {
					$save=['id'=>$subid,'mw'=>$mw,'mwsrc'=>'uci'];
					$this->Substance->save($save);
					echo $formula.' has average mass: '.$mw.'<br/>';
				}
			}
		}
		exit;
	}

	/**
	 * check common chemistry for casrn and inchi key alignment
	 * added january 2021 as part of commonchemistry.cas.org website testing
	 * @return void
	 */
	public function chkcaskey()
	{
		// information in 'incc' field in the substances table
		$diffs=$this->Substance->find('list',['fields'=>['id','caskey'],'conditions'=>['incc'=>'no']]);
		foreach($diffs as $subid=>$ck) {
			list($cas,$key)=explode(":",$ck);
			$hit=$this->Cas->detail($cas);
			if(!isset($hit['message'])) {
				$this->Substance->id=$subid;
				if($hit['inchiKey']==$key) {
					echo "Key ".$key." matched<br/>";
					$this->Substance->saveField('incc','samecas');
				} else {
					echo "Key ".$key." not matched<br/>";
					$this->Substance->saveField('incc','diffkey');
				}
			} else {
				echo $cas." not found on Common Chemistry<br/>";
			}
		}
		exit;
	}

	/**
	 * check substances and systems
	 * @return void
	 */
	public function chksyss()
	{
		$syss=$this->System->find('list',['fields'=>['id','identifier'],'conditions'=>['syschk'=>null],'order'=>'id']);
		$sets=$this->Dataset->find('list',['fields'=>['id','system_id']]);
		$joins=$this->SubstancesSystem->find('list',['fields'=>['id','substance_id','system_id']]);
		foreach($syss as $sysid=>$ident) {
			$note="";
			$subids=explode(":",$ident);
			if(isset($joins[$sysid])) {
				foreach($subids as $subid) {
					if(!in_array($subid,$joins[$sysid])) {
						echo "Substance: ".$subid." not found in system: ".$sysid."<br/>";$note="Missing substance ".$subid;
						if(in_array($sysid,$sets)) {
							echo $sysid." has datasets<br/>";
						}
					}
				}
			} else {
				echo "System: ".$sysid." not found in join table<br/>";$note="Missing system in join table".$sysid;
				$sys = $this->System->find('first',['conditions'=>['id'=>$sysid],'contain'=>['Substance'],'recursive'=>-1]);
				$subids=explode(':',$sys['System']['identifier']);
				foreach($subids as $subid) {
					$this->SubstancesSystem->create();
					$this->SubstancesSystem->save(['SubstancesSystem'=>['substance_id'=>$subid,'system_id'=>$sysid]]);
					$this->SubstancesSystem->clear();
				}
				// check for entries
				$subsyss=$this->SubstancesSystem->find('list',['conditions'=>['system_id'=>$sysid]]);
				if(count($subsyss)==count($subids)) {
					echo "System: ".$sysid." fixed in substances_systems<br/>";
				} else {
					echo "Substances_systems not correctly updated for system: ".$sysid."<br/>";exit;
				}
				if(in_array($sysid,$sets)) { echo $sysid." has datasets<br/>"; }
			}
			if($note=="") {
				echo "System: ".$sysid." confirmed<br/>";
				$note="System OK";
			} else {
				echo "System: ".$sysid." has issues - see DB<br/>";
			}
			$this->System->id=$sysid;
			$this->System->saveField('syschk',$note);
		}
		exit;
	}

	/**
	 * recheck each XML files for the valid substances
	 * then recheck each prop dataset for system
	 * @return void
	 */
	public function chksyss2()
	{
		// Change
		$path = WWW_ROOT.'files'.DS.'trc'.DS.'jced'.DS;
		$maindir = new Folder($path);
		$files = $maindir->find('.\*\.xml',true);
		$done = $this->File->find('list', ['fields' => ['id','filename'],'conditions'=>['syschk'=>'yes'],'order'=>'id']);
		foreach ($files as $filename) {
			if (in_array($filename, $done)) { continue; }  // echo $filename." already processed<br/>";

			// import XML file
			$note = "";
			$filepath = $path . $filename;
			$xml = simplexml_load_file($filepath);
			$trc = json_decode(json_encode($xml), true);

			// get substances
			$subs=$trc['Compound'];
			if(!isset($subs[0])) { $subs=[0=>$subs]; }
			$keys=[]; // indexed by ['nOrgNum']
			foreach($subs as $sub) {
				if(isset($sub['sStandardInChIKey'])) {
					$keys[$sub['RegNum']['nOrgNum']]=$sub['sStandardInChIKey'];
				} else {
					echo "InChIKey not defined in file ".$filename."<br/>";
				}
			}
			//debug($keys);

			// get systems
			$sets=$trc['PureOrMixtureData'];
			if(!isset($sets[0])) { $sets=[0=>$sets]; }
			$fsyss=[]; // indexed by 'nPureOrMixtureDataNumber'
			foreach($sets as $set) {
				$comps=$set['Component'];
				if(!isset($comps[0])) { $comps=[0=>$comps]; }
				$sys=[]; // list of inchikeys
				foreach($comps as $cidx=>$comp) {
					$cnum=$cidx+1;
					$sys[$cnum]=$keys[$comp['RegNum']['nOrgNum']];
				}
				$fsyss[$set['nPureOrMixtureDataNumber']]=$sys;
			}
			//debug($fsyss);

			// get datasets for this file
			$file=$this->File->find('list',['fields'=>['filename','id'],'conditions'=>['filename'=>$filename]]);$fid=$file[$filename];
			$sets=$this->Dataset->find('list',['fields'=>['id','system_id'],'conditions'=>['file_id'=>$fid],'order'=>'setnum']);
			if(count($fsyss)!=count($sets)) { echo "Dataset count incorrect in ".$filename."!";$note = "Wrong dataset count"; }
			//debug($sets);exit;

			// get database systems and check
			$setnum=1; // can use as $sets is ordered by setnum...
			$dsdone=$this->Dataset->find('list',['fields'=>['id'],'conditions'=>['syschk'=>'yes'],'order'=>'setnum']);
			foreach($sets as $setid=>$sysid) {
				if(in_array($setid,$dsdone)) { echo "System in dataset ".$setid." already matched<br/>";$setnum++;continue; }

				// get system
				$sys=$this->System->find('first',['conditions'=>['id'=>$sysid],'contain'=>['Substance'],'recursive'=>-1]);
				$dsys=[];
				foreach($sys['Substance'] as $sub) { $dsys[]=$sub['inchikey']; }
				$this->Dataset->id=$setid;
				if(!empty(array_diff($fsyss[$setnum],$dsys))) {
					echo "System in dataset ".$setid." does not match file ".$filename.'<br/>';
					$this->Dataset->saveField('comments',"System in DS does not match file");
				} else {
					echo "System in dataset ".$setid." matches file<br/>";
					$this->Dataset->saveField('syschk','yes');
				}
				$setnum++;
			}

			// update file
			$this->File->id=$fid;
			if($note=="") {
				$this->File->saveField('syschk','yes');
			} else {
				$this->File->saveField('comments',$note);
			}
		}
		exit;
	}

	/**
	 * confirm that the identifiers table has the correct casrn from the substances table
	 * @return void
	 */
	public function aligncasrns()
	{
		$cass=$this->Substance->find('list',['fields'=>['id','casrn'],'conditions'=>['NOT'=>['casrn'=>null]]]);
		foreach($cass as $subid=>$cas) {
			$idents=$this->Identifier->find('list',['fields'=>['id','value'],'conditions'=>['substance_id'=>$subid,'type'=>'casrn']]);
			if(in_array($cas,$idents)) {
				echo 'CASRN '.$cas.' verified for substance '.$subid.'<br/>';
			} else {
				if(empty($idents)) {
					echo 'CASRN '.$cas.' added for substance '.$subid.'<br/>';
					$this->Identifier->create();
					$this->Identifier->save(['Identifier'=>['substance_id'=>$subid,'type'=>'casrn','value'=>$cas]]);
				} elseif(count($idents)==1) {
					echo 'CASRN '.$cas.' updated for substance '.$subid.'<br/>';
					$keys=array_keys($idents);$iid=$keys[0];
					$save=['id'=>$iid,'value'=>$cas];
					$this->Identifier->save($save);
				} else {
					debug($cas);debug($idents);exit;
				}
			}
		}
		exit;
	}

	/**
	 * confirm that the identifiers table has the correct inchikeys from the substances table
	 * @return void
	 */
	public function alignkeys()
	{
		$keys=$this->Substance->find('list',['fields'=>['id','inchikey']]);
		foreach($keys as $subid=>$key) {
			$idents=$this->Identifier->find('list',['fields'=>['id','value'],'conditions'=>['substance_id'=>$subid,'type'=>'inchikey']]);
			if(in_array($key,$idents)) {
				echo 'InChIKey '.$key.' verified for substance '.$subid.'<br/>';
			} else {
				if(empty($idents)) {
					echo 'InChIKey '.$key.' added for substance '.$subid.'<br/>';
					$this->Identifier->create();
					$this->Identifier->save(['Identifier'=>['substance_id'=>$subid,'type'=>'casrn','value'=>$key]]);
				} elseif(count($idents)==1) {
					echo 'InChIKey '.$key.' updated for substance '.$subid.'<br/>';
					$keys=array_keys($idents);$iid=$keys[0];
					$save=['id'=>$iid,'value'=>$key];
					$this->Identifier->save($save);
				} else {
					debug($key);debug($idents);exit;
				}
			}
		}
		exit;
	}

	/**
	 * update type and subtype from classyfire
	 * @return void
	 */
	public function updtypes()
	{
		$keys=$this->Substance->find('list',['fields'=>['id','inchikey'],'order'=>'id','conditions'=>['type'=>null],'limit'=>7000]);
		foreach($keys as $subid=>$key) {
			$types=$this->Identifier->classy($key);$save=null;
			if($types) {
				$save=['id'=>$subid,'type'=>$types['type'],'subtype'=>$types['subtype']];
				echo $key." found in Classyfire<br/>";
			} else {
				$save=['id'=>$subid,'type'=>'not found'];
				echo $key." not found in Classyfire<br/>";
			}
			$this->Substance->save($save);
			sleep(10);
		}
		exit;
	}

	/**
	 * get pubchem identifiers for substances
	 * (pubchemid, ismiles, csmiles, iupac name)
	 * NOTE: the code below also updated the entry for the inchi if available on pubchem (7099)
	 * After this all were reassigned to the trc as the XML files are the definitive source of inchis and inchikeys
	 * (verification of the consistency has not yet been done
	 * @return void
	 */
	public function getpcidents()
	{
		$subids=$this->Identifier->find('list',['fields'=>['substance_id'],'conditions'=>['source'=>null,'type'=>'pubchemId']]);
		$keys=$this->Substance->find('list',['fields'=>['id','inchikey'],'conditions'=>['id'=>$subids]]);
		//debug($keys);exit;
		foreach ($keys as $subid => $key) {
			// get substance info from PubChem
			$cid = $this->Compound->cid('inchikey', $key);
			if ($cid) {
				$data = $this->Compound->allcid($cid);
			} else {
				echo "Substance ".$subid.' not found on PubChem<br/>';sleep(1);continue;
			}
			if(empty($data['inchi']))  { $data['inchi']=''; }
			if(empty($data['csmiles']))  { $data['csmiles']=''; }
			if(empty($data['ismiles']))  { $data['ismiles']=''; }
			if(empty($data['iupacname']))  { $data['iupacname']=''; }

			// add identifiers
			$idents = ['inchi' => $data['inchi'], 'csmiles' => $data['csmiles'], 'ismiles' => $data['ismiles'],
				'pubchemId' => $cid, 'iupacname' => $data['iupacname']];
			foreach ($idents as $type => $value) {
				if (!empty($value)) {
					// check if it already exists in table
					$cnds = ['substance_id' => $subid, 'type' => $type, 'value' => $value];
					$done=$this->Identifier->find('list',['fields'=>['substance_id','id'],'conditions'=>$cnds]);
					if($done) {
						$save=['id'=>$done[$subid],'source'=>'pubchem'];
						$this->Identifier->save($save);
					} else {
						$cnds['source']='pubchem';
						$this->Identifier->add($cnds);
					}
				}
			}
			echo "Completed substance ".$subid.'<br/>';sleep(1);
		}
		exit;
	}

	/**
	 * get wikidata, chemspider, and dsstox ids from wikidata
	 * @param int $max
	 * @return void
	 */
	public function wikimeta(int $max=10000)
	{
		$count=0;
		$keys=$this->Substance->find('list',['fields'=>['id','inchikey'],'order'=>'id']);
		foreach($keys as $subid=>$key) {
			$meta=$this->Wiki->findbykey($key);
			if(empty($meta)) { echo $subid." not found on Wikidata<br/>";continue; }
			$added=0;
			foreach($meta as $type=>$value) {
				$found=$this->Identifier->find('list',['conditions'=>['substance_id'=>$subid,'type'=>$type]]);
				if(!$found) {
					$save=['substance_id'=>$subid,'type'=>$type,'value'=>$value,'source'=>'wikidata'];
					$this->Identifier->create();
					$this->Identifier->save(['Identifier'=>$save]);
					echo "Added '".$type."' for substance '".$subid."'<br/>";
					$added++;
				}
			}
			if($added>0) { $count++; }
			if($count==$max) { 	debug($meta);exit; }
		}
		exit;
	}
}
