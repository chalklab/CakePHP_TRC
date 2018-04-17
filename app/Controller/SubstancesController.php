<?php

/**
 * Class SubstancesController
 */
class SubstancesController extends AppController
{
    public $uses=['Substance','Identifier','Pubchem.Chemical','Chemspider.Rdf'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * Add a new substance
     */
    public function add()
    {
        if($this->request->is('post'))
        {
            //echo "<pre>";print_r($this->request->data);echo "</pre>";exit;
            $this->Substance->create();
            if ($this->Substance->save($this->request->data))
            {
                $this->Session->setFlash('Substance created.');
                $this->redirect(['action' => 'index']);
            } else {
                $this->Session->setFlash('Substance could not be created.');
            }
        } else {
            // Nothing to do here?
        }
    }

    /**
     * View a substance
     * @param $id
     * @param $debug
     */
    public function view($id,$debug=false)
    {
        $contain=[
            'Identifier',
            'System'=>['order'=>['name'],
                'Dataset'=>[
                    'Propertytype'
                ]
            ]
        ];
        if(is_numeric($id)) {
			$data=$this->Substance->find('first',['conditions'=>['Substance.id'=>$id],'contain'=>$contain]);
		} else {
			$data=$this->Substance->find('first',['conditions'=>['Substance.name'=>$id],'contain'=>$contain]);
		}
        if($debug) { debug($data);exit; }
        $this->set('data',$data);
    }

    /**
     * Update a substance
     */
    public function update($id)
    {
        if($this->request->is('post'))
        {
            $this->Substance->create();
            if ($this->Substance->save($this->request->data))
            {
                $this->Session->setFlash('Substance udated.');
                $this->redirect(['action' => 'index']);
            } else {
                $this->Session->setFlash('Substance could not be updated.');
            }
        } else {
            $data=$this->Substance->find('first',['conditions'=>['Substance.id'=>$id],'recursive'=>3]);
            $this->set('data',$data);
            $this->set('id',$id);
        }
    }

    /**
     * Delete a substance
     */
    public function delete($id)
    {
        $this->Substance->delete($id);
        $this->redirect(['action' => 'index']);
    }

    /**
     * View a list of substances
     */
    public function index()
    {
        $data=$this->Substance->find('list',['fields'=>['id','name','first'],'order'=>['first','name']]);
        $this->set('data',$data);
    }

    /**
     * Get meta for chemicals
     * @param $id
     */
    public function meta($id=null)
    {
        if(is_null($id)) {
            $cs=$this->Substance->find('all',['recursive'=>1,'conditions'=>['pcformula'=>null],'order'=>['name'],'contain'=>['Identifier'=>['conditions'=>['type'=>'casrn']]]]);
            foreach($cs as $c) {
                $this->Substance->meta($c,true);
            }
        } else{
            $c=$this->Substance->find('first',['recursive'=>1,'conditions'=>['id'=>$id],'contain'=>['Identifier'=>['conditions'=>['type'=>'casrn']]]]);
            $i=$c['Identifier'];$s=$c['Substance'];
            $this->Substance->meta($c,true);
        }
        exit;
    }

    /**
     * Clean up the names of substances
     */
    public function cleanname()
    {
        $exs=['cis','trans','alpha','beta','gamma'];
        $subs=$this->Substance->find('list',['fields'=>['id','name'],'order'=>'name']);
        foreach($subs as $id=>$name) {
            $this->Substance->id=$id;
            $this->Substance->saveField('name',ucfirst($name));
            $this->Substance->clear;
            echo "Done ".ucfirst($name)."<br>";
        }
        debug($subs);exit;
    }

    /**
     * Get filenames from RI/ST files that are not correct in DB
     */
    public function check()
    {
        $files=['ri','ri2','ri3'];
        foreach($files as $file) {
            $file=file('files/'.$file.'.txt',FILE_IGNORE_NEW_LINES);
            $cond=['id >'=>9824];
            $c=['Identifier'=>['conditions'=>['Identifier.type ='=>'casrn']]];
            $subs=$this->Substance->find('all',['conditions'=>$cond,'contain'=>$c,'recursive'=>-1]);
            foreach($subs as $sub) {
                $sid=$sub['Substance']['id'];
                $cas=$sub['Identifier'][0]['value'];
                foreach($file as $line) {
                    if(stristr($line,$cas)) {
                        $line=preg_replace("/\s+/i"," ",$line); // add regex pattern
                        $chunks=explode(" ",$line);
                        //debug($chunks);
                        $id=[];
                        foreach($chunks as $chunk) {
                            // assign a type to each chunk of data
                            if(preg_match("/[0-9]{2,7}-[0-9]{2}-[0-9]/i",$chunk)) {
                                $id['cas'][]=$chunk;
                            } elseif(is_numeric($chunk)) {
                                $id['id'][]=$chunk;
                            } elseif(preg_match("/[a-zA-Z0-9\+\-\(\)]{4,}/i",$chunk)) {
                                $id['name'][]=$chunk;
                            } elseif(preg_match("/[A-Z][a-z]?[0-9]{0,3}/i",$chunk)) {
                                $id['formula'][]=$chunk;
                            } else {
                                $id['unk'][]=$chunk;
                            }
                        }
                        $name=implode(" ",$id['name']);
                        $formula=implode(" ",$id['formula']);
                        echo $sid.": ".$name."<br />";
                        //debug($id);exit;
                        $this->Substance->id=$sid;
                        $this->Substance->saveField('name',ucfirst($name));
                        $this->Substance->saveField('formula',$formula);
                        $this->Substance->clear();
                    }
                }
            }
        }
        exit;
    }

    /**
     * Find inchi from springer materials search and store in Identifiers table
     */
    public function sm()
    {
        $cs=$this->Substance->find('all',['order'=>['name'],'contain'=>['Identifier'=>['conditions'=>['type'=>['casrn','inchi']]]]]);
        //debug($cs);exit;
        foreach($cs as $c) {
            if(count($c['Identifier'])==1) { // Only process if no inchi
                $sid=$c['Substance']['id'];
                $cas=$c['Identifier'][0]['value'];
                $path="http://materials.springer.com/search?searchTerm=";
                $page=file_get_contents($path.$cas);
                //debug($page);exit;
                if(stristr($page,"/smsid_")) {
                    list($junk,$chunk)=explode("/smsid_",$page);
                    list($smsid,)=explode("\"",$chunk);
                    $path2="http://materials.springer.com/substanceprofile/docs/smsid_";
                    $page2=file_get_contents($path2.$smsid);
                    //echo $page2;exit;
                    list($junk,$chunk2)=explode("InChI=",$page2);
                    list($inchi,)=explode(" ",$chunk2);
                    $inchi="InChI=" . trim($inchi);
                    if(empty($this->Identifier->find('first',['conditions'=>['value'=>$smsid]]))) {
                        $this->Identifier->add(['substance_id'=>$sid,'type'=>'springerId','value'=>$smsid]);
                        if(empty($this->Identifier->find('first',['conditions'=>['value'=>$inchi]]))) {
                            $this->Identifier->add(['substance_id' => $sid, 'type' => 'inchi', 'value' => $inchi]);
                        }
                        $this->Substance->save(['id'=>$sid,'molweight'=>0]);
                        $this->Substance->clear();
                    }
                    echo $inchi.":".$smsid."<br />";
                } else {
                    echo "Not found (".$cas.")<br />";
                }
            }
        }
        exit;
    }

    /**
     * Get meta for chemicals using inchi string
     */
    public function inchi()
    {
        $cs=$this->Substance->find('all',['recursive'=>1,'conditions'=>['pcformula'=>null],'order'=>['name'],'contain'=>['Identifier'=>['conditions'=>['type'=>'inchi']]]]);
        foreach($cs as $c) {
            $i=$c['Identifier'];$s=$c['Substance'];
            if(empty($i)) { continue; }
            // Search PubChem
            $cid=$this->Chemical->cid('inchi',$i[0]['value']);
            //debug($cid);exit;
            if($cid) {
                // Add the PubChem ID
                $test=$this->Identifier->find('first',['conditions'=>['substance_id'=>$s['id'],'type'=>'pubchemId']]);
                if(empty($test)) {
                    $this->Identifier->add(['substance_id'=>$s['id'],'type'=>'pubchemId','value'=>$cid]);
                }
                echo "<h3>".$s['name']." (PubChem)</h3>";
                echo "<ul>";
                $ps=['pcformula'=>'MolecularFormula','iupacname'=>'IUPACName','inchi'=>'InChI','inchikey'=>'InChIKey','molweight'=>'MolecularWeight'];
                foreach($ps as $t=>$p) {
                    if($t=='pcformula'||$t=='molweight') {
                        // Check to see if the value is already in the DB
                        $test=$this->Substance->find('list',['fields'=>['id',$t],'conditions'=>['id'=>$s['id']]]);
                        if($test[$s['id']]==''||$test[$s['id']]==0||is_null($test[$s['id']])) {
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
                $ps=['chemspiderId'=>'id','pcformula'=>'formula','iupacname'=>'name','smiles'=>'smiles','inchi'=>'inchi','inchikey'=>'inchikey'];
                foreach($ps as $t=>$p) {
                    if(isset($meta[$p])) {
                        echo "<li>".$p.": ".$meta[$p]."</li>";
                        if($t=='pcformula') {
                            $this->Substance->save(['id'=>$s['id'],$t=>$meta[$p]]);
                            $this->Substance->clear();
                        } else {
                            $test=$this->Identifier->find('first',['conditions'=>['substance_id'=>$s['id'],'type'=>$t]]);
                            if(empty($test)) {
                                $this->Identifier->add(['substance_id'=>$s['id'],'type'=>$t,'value'=>$meta[$p]]);
                            }
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
            $mw=$this->Substance->find('list',['fields'=>['id','molweight'],'conditions'=>['id'=>$s['id']]]);
            if($mw[$s['id']]=='') {
                // Use inchikey from ChemSpider search to get molweight from PubChem
                $mw=$this->Chemical->property('MolecularWeight',$cid);
                if($mw) {
                    $this->Substance->save(['id'=>$s['id'],'molweight'=>$mw['MolecularWeight']]);
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
     * Get data from opsin
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

    public function stat()
    {
        $cs = $this->Substance->find('all',['contain' => ['Identifier'=>['fields'=>['type','value']]],'recursive'=>1]);
        debug($cs);exit;
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
            if(!is_null($s['pcformula']))   { $stats['pcformula']++; }
            if(!is_null($s['molweight']))   { $stats['molweight']++; }
            debug($stats);debug($s);exit;
        }
        debug($stats);exit;
    }
}